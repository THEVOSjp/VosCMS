<?php
/**
 * 메일 도메인 자동 프로비저닝 (3-way 분기).
 *
 * order.domain_option 별 처리:
 *   ─ 'free'     무료 서브도메인 (abc.21ces.net 등). 그대로 즉시 등록.
 *   ─ 'new'      신규 도메인 구매 (예: abc.com). 임시 abc.voscms.com 발급.
 *                정식 도메인은 admin "도메인 취득 완료" 버튼 → 마이그레이션.
 *   ─ 'existing' 보유 도메인 (예: mydomain.com). Cloudflare zone 자동 추가 + NS 안내.
 *                NS 변경 전파 후 자동 메일 레코드 등록.
 */

namespace RzxLib\Core\Mail;

use PDO;
use RzxLib\Core\Dns\CloudflareDns;

class MailDomainProvisioner
{
    private const VOSCMS_ZONE = 'voscms.com';     // new 케이스 임시 발급용
    private const MAIL_HOST = 'mail.voscms.com';

    /** 무료 서브도메인으로 허용된 zone */
    private const FREE_SUBDOMAIN_ZONES = ['21ces.com', '21ces.net'];

    private CloudflareDns $cf;
    private PDO $pdo;
    private string $tablePrefix;
    private string $mx1Ip;

    public function __construct(?PDO $pdo = null, ?CloudflareDns $cf = null)
    {
        $this->cf = $cf ?? new CloudflareDns();
        $this->pdo = $pdo ?? $this->makePdo();
        $this->tablePrefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        $this->mx1Ip = $_ENV['MAIL_SERVER_HOST'] ?? '133.117.72.149';
    }

    /**
     * 주문에 대해 도메인 자동 프로비저닝 실행 (멱등성).
     */
    public function provisionForOrder(int $orderId): array
    {
        $order = $this->loadOrder($orderId);
        if (!$order) {
            throw new \RuntimeException("Order #$orderId 또는 hosting subscription 없음");
        }

        // 이미 발급되었으면 skip
        $existing = $this->getProvisionInfo($orderId);
        if (!empty($existing['provisioned_at'])) {
            return [
                'provisioned' => false,
                'reason' => 'already_provisioned',
                'previous' => $existing,
            ];
        }

        // 활성 상태인지 확인
        if (!in_array($order['status'] ?? '', ['active', 'paid'], true)) {
            throw new \RuntimeException("Order #$orderId 활성 상태 아님 (status={$order['status']})");
        }

        $domain = strtolower(trim($order['domain'] ?? ''));
        $domainOption = $order['domain_option'] ?? 'free';

        if ($domain === '') {
            throw new \RuntimeException("Order #$orderId 도메인 없음");
        }

        switch ($domainOption) {
            case 'free':     return $this->provisionFreeSubdomain($order);
            case 'new':      return $this->provisionNewDomainTemp($order);
            case 'existing': return $this->provisionExistingDomain($order);
            default:
                throw new \RuntimeException("Unknown domain_option: $domainOption");
        }
    }

    /**
     * 무료 서브도메인 (abc.21ces.net 등) — 그대로 즉시 활성화.
     */
    private function provisionFreeSubdomain(array $order): array
    {
        $domain = strtolower($order['domain']);

        // zone 분리: 'abc.21ces.net' → subdomain='abc', zone='21ces.net'
        $zoneInfo = $this->detectFreeZone($domain);
        if (!$zoneInfo) {
            throw new \RuntimeException("무료 서브도메인 zone 미지원: $domain");
        }
        [$subdomain, $zone] = $zoneInfo;

        // 1. mx1 에 DKIM 키 생성 (도메인별 전용 키)
        $dkimPubkey = $this->generateDkimKey($domain);

        // 2. Cloudflare DNS: A + 메일 레코드 (DKIM 포함)
        $cfResult = $this->cf->setupSubdomain($zone, $subdomain, $this->mx1Ip, [
            'mail_host' => self::MAIL_HOST,
            'dkim_pubkey' => $dkimPubkey,
        ]);
        $this->registerDomainOnMx1($domain);
        $this->saveProvisionInfo((int)$order['id'], [
            'mode' => 'active',
            'origin' => 'free',
            'domain' => $domain,
            'zone' => $zone,
            'subdomain' => $subdomain,
            'provisioned_at' => date('c'),
            'completed_at' => date('c'),
            'cloudflare_records' => count($cfResult['mail_records'] ?? []),
        ]);

        return [
            'provisioned' => true,
            'mode' => 'active',
            'origin' => 'free',
            'domain' => $domain,
            'cloudflare' => $cfResult,
        ];
    }

    /**
     * 신규 구매 (abc.com 사고 싶다) — 임시 메일 발급 안 함.
     * 결제 직후엔 'new_pending' 상태만 기록.
     * admin 이 NameSilo 구매 + NS 변경 후 「등록 완료」 클릭 → completeNewDomainAcquisition() 호출.
     */
    private function provisionNewDomainTemp(array $order): array
    {
        $userDomain = strtolower($order['domain']);

        $this->saveProvisionInfo((int)$order['id'], [
            'mode' => 'new_pending',
            'domain' => $userDomain,
            'provisioned_at' => date('c'),
            'note' => 'awaiting_admin_acquisition',
        ]);

        return [
            'provisioned' => true,
            'mode' => 'new_pending',
            'domain' => $userDomain,
            'next_action' => 'admin_acquire_then_click_complete',
        ];
    }

    /**
     * 보유 도메인 (mydomain.com) — Cloudflare zone 자동 추가 + NS 안내.
     * NS 변경 전파 (수십분~24시간) 후 별도 처리(activateExistingDomain)에서 메일 레코드 등록.
     */
    private function provisionExistingDomain(array $order): array
    {
        $domain = strtolower($order['domain']);

        // 이미 zone 존재 확인 (다른 사용자가 같은 도메인 등록 시도 등)
        $existingZoneId = $this->cf->getZoneId($domain);
        $zone = null;
        if ($existingZoneId) {
            $zone = $this->cf->getZoneStatus($existingZoneId);
        } else {
            // 새 zone 생성
            $zone = $this->cf->createZone($domain);
        }

        $nameServers = $zone['name_servers'] ?? [];
        $zoneStatus = $zone['status'] ?? 'pending';
        // 'active' 인 경우 (이미 NS 변경 완료) 즉시 메일 레코드 + DKIM 셋업
        $mailResult = null;
        if ($zoneStatus === 'active') {
            $dkimPubkey = $this->generateDkimKey($domain);
            $mailResult = $this->cf->setupMailRecords($domain, [
                'mail_host' => self::MAIL_HOST,
                'dkim_pubkey' => $dkimPubkey,
            ]);
            $this->registerDomainOnMx1($domain);
        }

        // mode 결정: zone active 면 'active', 아니면 'existing_pending'
        $finalMode = $zoneStatus === 'active' ? 'active' : 'existing_pending';

        $this->saveProvisionInfo((int)$order['id'], [
            'mode' => $finalMode,
            'origin' => 'existing',
            'domain' => $domain,
            'zone_id' => $zone['id'] ?? null,
            'zone_status' => $zoneStatus,
            'name_servers' => $nameServers,
            'mail_records_setup' => $mailResult !== null,
            'provisioned_at' => date('c'),
            'completed_at' => $zoneStatus === 'active' ? date('c') : null,
        ]);

        return [
            'provisioned' => true,
            'mode' => $finalMode,
            'domain' => $domain,
            'zone_status' => $zoneStatus,
            'name_servers' => $nameServers,
            'next_action' => $zoneStatus === 'active'
                ? 'mail_records_setup_complete'
                : 'awaiting_ns_change',
        ];
    }

    /**
     * 신규 도메인 등록 완료 (admin 「등록 완료」 클릭).
     * NameSilo 구매 + NS Cloudflare 변경 완료된 도메인을 즉시 활성화.
     * 임시 메일 없으니 마이그레이션 불필요 — 신규 셋업만.
     */
    public function completeNewDomainAcquisition(int $orderId): array
    {
        $info = $this->getProvisionInfo($orderId);
        if (!$info || ($info['mode'] ?? '') !== 'new_pending') {
            throw new \RuntimeException("Order #$orderId new_pending 상태 아님");
        }
        $domain = $info['domain'];

        // 1. Cloudflare zone 확인 (사장님이 NS 변경 후 active 상태)
        $zoneId = $this->cf->getZoneId($domain);
        if (!$zoneId) {
            throw new \RuntimeException("$domain Cloudflare zone 없음 — NS 변경 + zone 추가 확인 필요");
        }
        $zone = $this->cf->getZoneStatus($zoneId);
        if (($zone['status'] ?? '') !== 'active') {
            throw new \RuntimeException("$domain Cloudflare zone 미활성 (status={$zone['status']}). NS 변경 전파 대기 또는 미완료.");
        }

        // 2. DKIM + 메일 레코드 셋업
        $dkimPubkey = $this->generateDkimKey($domain);
        $mailResult = $this->cf->setupMailRecords($domain, [
            'mail_host' => self::MAIL_HOST,
            'dkim_pubkey' => $dkimPubkey,
        ]);
        $this->registerDomainOnMx1($domain);

        // 3. 발급 정보 갱신
        $this->saveProvisionInfo($orderId, array_merge($info, [
            'mode' => 'active',
            'completed_at' => date('c'),
            'cloudflare_records' => count($mailResult),
        ]));

        return [
            'completed' => true,
            'domain' => $domain,
            'cloudflare_records' => count($mailResult),
        ];
    }

    /**
     * 보유 도메인 zone 의 NS 활성화 후 호출 — 메일 레코드 셋업 마무리.
     */
    public function activateExistingDomain(int $orderId): array
    {
        $info = $this->getProvisionInfo($orderId);
        if (!$info || ($info['mode'] ?? '') !== 'existing_pending') {
            throw new \RuntimeException("Order #$orderId existing_pending 모드 아님");
        }
        $domain = $info['domain'];

        $zone = $this->cf->getZoneStatus($info['zone_id']);
        if (($zone['status'] ?? '') !== 'active') {
            throw new \RuntimeException("$domain zone 미활성 (NS 전파 대기 중)");
        }

        $dkimPubkey = $this->generateDkimKey($domain);
        $mailResult = $this->cf->setupMailRecords($domain, [
            'mail_host' => self::MAIL_HOST,
            'dkim_pubkey' => $dkimPubkey,
        ]);
        $this->registerDomainOnMx1($domain);
        $this->saveProvisionInfo($orderId, array_merge($info, [
            'mode' => 'active',
            'zone_status' => 'active',
            'mail_records_setup' => true,
            'completed_at' => date('c'),
        ]));

        return [
            'activated' => true,
            'domain' => $domain,
            'cloudflare_records' => count($mailResult),
        ];
    }

    // ─────────────────────────────────────────────────────────
    // 내부 helpers
    // ─────────────────────────────────────────────────────────

    /**
     * 도메인 'abc.21ces.net' → ['abc', '21ces.net']
     * 지원 zone 외는 null.
     */
    private function detectFreeZone(string $domain): ?array
    {
        foreach (self::FREE_SUBDOMAIN_ZONES as $zone) {
            $suffix = '.' . $zone;
            if (str_ends_with($domain, $suffix)) {
                $sub = substr($domain, 0, -strlen($suffix));
                if ($sub !== '' && !str_contains($sub, '.')) {
                    return [$sub, $zone];
                }
            }
        }
        return null;
    }

    /**
     * 신규 구매 케이스의 임시 서브도메인 이름 생성 (충돌 방지 — 주문번호 끝 4자리).
     */
    private function generateNewSubdomain(string $userDomain, string $orderNumber): string
    {
        $prefix = strstr($userDomain, '.', true) ?: $userDomain;
        $prefix = preg_replace('/[^a-z0-9]/', '', strtolower($prefix));
        $prefix = substr($prefix, 0, 20) ?: 'svc';
        $suffix = strtolower(substr(preg_replace('/[^a-z0-9]/', '', $orderNumber), -4));
        return trim("$prefix-$suffix", '-');
    }

    private function loadOrder(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, s.id AS hosting_sub_id, s.metadata AS hosting_metadata
            FROM {$this->tablePrefix}orders o
            JOIN {$this->tablePrefix}subscriptions s
                ON s.order_id = o.id AND s.type = 'hosting'
            WHERE o.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getProvisionInfo(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT JSON_EXTRACT(metadata, '$.mail_provision') AS info
            FROM {$this->tablePrefix}subscriptions
            WHERE order_id = :id AND type = 'hosting' LIMIT 1
        ");
        $stmt->execute(['id' => $orderId]);
        $info = $stmt->fetchColumn();
        if (!$info) return null;
        return json_decode($info, true) ?: null;
    }

    private function saveProvisionInfo(int $orderId, array $data): void
    {
        $stmt = $this->pdo->prepare("
            SELECT id, metadata FROM {$this->tablePrefix}subscriptions
            WHERE order_id = :id AND type = 'hosting' LIMIT 1
        ");
        $stmt->execute(['id' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return;
        $meta = json_decode($row['metadata'] ?? '{}', true) ?: [];
        $meta['mail_provision'] = $data;
        $upd = $this->pdo->prepare("UPDATE {$this->tablePrefix}subscriptions SET metadata = :m WHERE id = :id");
        $upd->execute(['m' => json_encode($meta, JSON_UNESCAPED_UNICODE), 'id' => $row['id']]);
    }

    /**
     * mx1 에서 도메인 전용 DKIM 키 생성 (멱등성 — 이미 있으면 기존 키 반환).
     * SSH 로 rspamadm dkim_keygen 실행 → 공개키 (TXT 레코드용) 반환.
     *
     * @return string 공개키 콘텐츠 ("v=DKIM1; k=rsa; p=MIGfMA0...")
     */
    private function generateDkimKey(string $domain): string
    {
        $dkimDir = '/var/lib/rspamd/dkim';
        $selector = 'mail';
        $keyFile = "$dkimDir/$domain.$selector.key";
        $txtFile = "$dkimDir/$domain.$selector.txt";

        $script = <<<SH
            set -e
            if [ ! -f "$keyFile" ]; then
                rspamadm dkim_keygen -s "$selector" -d "$domain" -k "$keyFile" > "$txtFile"
                chown _rspamd:_rspamd "$keyFile" "$txtFile"
                chmod 640 "$keyFile"
                chmod 644 "$txtFile"
            fi
            cat "$txtFile"
        SH;

        $output = $this->execMx1Shell($script);
        // .txt 형식: mail._domainkey IN TXT ( "v=DKIM1; k=rsa;" "p=..." ) ;
        // 멀티라인 따옴표 합쳐서 단일 콘텐츠 추출
        $pattern = '/"([^"]+)"/';
        if (!preg_match_all($pattern, $output, $matches)) {
            throw new \RuntimeException("DKIM 키 파싱 실패: $domain\n$output");
        }
        return implode('', $matches[1]);   // 따옴표 안 텍스트들 모두 합침
    }

    private function registerDomainOnMx1(string $domain): void
    {
        $sql = "INSERT INTO virtual_domains (name) VALUES ('" . addslashes($domain) . "') ON DUPLICATE KEY UPDATE active=1";
        $this->execMx1Sql($sql);
    }

    private function disableMx1Domain(string $domain): void
    {
        $sql = "UPDATE virtual_domains SET active=0 WHERE name='" . addslashes($domain) . "'";
        $this->execMx1Sql($sql);
    }

    private function migrateMx1Domain(string $oldDomain, string $newDomain): void
    {
        // virtual_users 의 email 도메인 부분 교체 + domain_id 변경
        $oldEsc = addslashes($oldDomain);
        $newEsc = addslashes($newDomain);
        $sql = "
            INSERT INTO virtual_domains (name) VALUES ('$newEsc') ON DUPLICATE KEY UPDATE active=1;
            UPDATE virtual_users v
              JOIN virtual_domains old_d ON old_d.id = v.domain_id AND old_d.name = '$oldEsc'
              JOIN virtual_domains new_d ON new_d.name = '$newEsc'
            SET v.email = REPLACE(v.email, '@$oldEsc', '@$newEsc'),
                v.domain_id = new_d.id;
        ";
        $this->execMx1Sql($sql);

        // 메일박스 폴더 이동 (rsync + rm)
        $sshKey = $_ENV['MAIL_SERVER_SSH_KEY'] ?? '/home/thevos/.ssh/id_rsa';
        $host = $_ENV['MAIL_SERVER_HOST'] ?? '133.117.72.149';
        $user = $_ENV['MAIL_SERVER_USER'] ?? 'root';
        $cmd = sprintf(
            'ssh -i %s -o StrictHostKeyChecking=accept-new %s@%s %s',
            escapeshellarg($sshKey),
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg(sprintf(
                'mkdir -p /var/vmail/%s; if [ -d /var/vmail/%s ]; then rsync -a /var/vmail/%s/ /var/vmail/%s/ && rm -rf /var/vmail/%s; fi; chown -R vmail:vmail /var/vmail/%s',
                escapeshellarg($newDomain), escapeshellarg($oldDomain),
                escapeshellarg($oldDomain), escapeshellarg($newDomain),
                escapeshellarg($oldDomain), escapeshellarg($newDomain)
            ))
        );
        shell_exec($cmd);
    }

    private function execMx1Sql(string $sql): void
    {
        $pass = $_ENV['MAIL_SERVER_MAILSYNC_PASS'] ?? '';
        if ($pass === '') {
            error_log('[provisioner] MAIL_SERVER_MAILSYNC_PASS 미설정');
            return;
        }
        $output = $this->execMx1Shell(sprintf("mariadb -u mailsync -p'%s' mail_lookup -e \"%s\"", $pass, $sql));
        if ($output && stripos($output, 'error') !== false && stripos($output, 'warning') === false) {
            throw new \RuntimeException("mx1 SQL 실패: " . substr($output, 0, 200));
        }
    }

    /**
     * SSH 로 mx1 에서 임의 shell 명령 실행 — 공통 헬퍼.
     */
    private function execMx1Shell(string $shellCommand): string
    {
        $sshKey = $_ENV['MAIL_SERVER_SSH_KEY'] ?? '/home/thevos/.ssh/id_rsa';
        $host = $_ENV['MAIL_SERVER_HOST'] ?? '133.117.72.149';
        $user = $_ENV['MAIL_SERVER_USER'] ?? 'root';
        $cmd = sprintf(
            'ssh -i %s -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15 %s@%s %s 2>&1',
            escapeshellarg($sshKey),
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($shellCommand)
        );
        return (string) shell_exec($cmd);
    }

    private function makePdo(): PDO
    {
        $envFile = dirname(__DIR__, 4) . '/.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, '=') !== false) {
                    [$k, $v] = explode('=', $line, 2);
                    $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
                }
            }
        }
        return new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}
