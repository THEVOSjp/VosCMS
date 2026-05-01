<?php
/**
 * Cloudflare DNS API 통합 모듈.
 *
 * 호스팅 자동화 — voscms 가 고객 도메인의 DNS 레코드를 직접 추가/수정/삭제.
 * 21ces.net (서브도메인용), voscms.com, 고객 도메인 등 모두 같은 토큰으로 처리.
 *
 * 인증: Cloudflare API Token (zone:read + dns:edit 권한)
 * .env 의 CLOUDFLARE_API_TOKEN 또는 생성자 인자로 전달.
 *
 * ionCube 인코딩 권장 (사용 도메인 토큰이 들어있어 보호 필요).
 */

namespace RzxLib\Core\Dns;

class CloudflareDns
{
    private const API_BASE = 'https://api.cloudflare.com/client/v4';

    private string $apiToken;
    private array $zoneCache = [];

    public function __construct(?string $apiToken = null)
    {
        $this->apiToken = $apiToken ?? ($_ENV['CLOUDFLARE_API_TOKEN'] ?? '');
        if ($this->apiToken === '') {
            throw new \RuntimeException('Cloudflare API token 미설정 — .env 의 CLOUDFLARE_API_TOKEN');
        }
    }

    /**
     * 도메인의 zone ID 조회 (캐시 적용).
     */
    public function getZoneId(string $domain): ?string
    {
        $domain = strtolower(trim($domain));
        if (isset($this->zoneCache[$domain])) {
            return $this->zoneCache[$domain];
        }
        $res = $this->call('GET', '/zones?name=' . urlencode($domain));
        $zoneId = $res['result'][0]['id'] ?? null;
        $this->zoneCache[$domain] = $zoneId;
        return $zoneId;
    }

    /**
     * 토큰으로 접근 가능한 모든 zone 목록.
     */
    public function listZones(): array
    {
        $res = $this->call('GET', '/zones?per_page=50');
        return $res['result'] ?? [];
    }

    /**
     * 사용자 보유 도메인을 Cloudflare zone 으로 새로 추가 (existing 케이스).
     *
     * @param string $domain      예: 'mydomain.com'
     * @param string|null $accountId  Cloudflare 계정 ID (null 이면 토큰 사용자 첫 계정 자동)
     * @return array ['id', 'name', 'status', 'name_servers'(alice/max), 'original_name_servers']
     */
    public function createZone(string $domain, ?string $accountId = null): array
    {
        if ($accountId === null) {
            $accounts = $this->call('GET', '/accounts');
            $accountId = $accounts['result'][0]['id'] ?? null;
            if ($accountId === null) {
                throw new \RuntimeException('Cloudflare account 조회 실패');
            }
        }
        $body = [
            'name' => $domain,
            'account' => ['id' => $accountId],
            'type' => 'full',
            'jump_start' => true,
        ];
        $res = $this->call('POST', '/zones', $body);
        $zone = $res['result'] ?? [];
        // 캐시에도 저장
        $this->zoneCache[$domain] = $zone['id'] ?? null;
        return $zone;
    }

    /**
     * zone 활성화 상태 조회 — NS 변경 후 'active' 가 됨.
     */
    public function getZoneStatus(string $zoneId): array
    {
        $res = $this->call('GET', "/zones/$zoneId");
        return $res['result'] ?? [];
    }

    /**
     * Cloudflare 가 발급한 NS 페어 조회.
     */
    public function getNameServers(string $domain): array
    {
        $zoneId = $this->getZoneId($domain);
        if (!$zoneId) return [];
        $info = $this->getZoneStatus($zoneId);
        return $info['name_servers'] ?? [];
    }

    /**
     * 메일 셋업용 도메인 가용성 검사.
     *
     * 검사 순서:
     *   1. DB (rzx_reserved_subdomains) 에 등록되어 있으면 즉시 거절 (빠름)
     *   2. DB 에 없으면 Cloudflare API 로 실시간 확인 (이중 안전)
     *
     * @param string $zoneDomain  zone (예: '21ces.com')
     * @param string $subdomain   prefix (예: 'thevos')
     * @param \PDO|null $pdo      DB 연결 (null 이면 Cloudflare 만)
     * @return array ['available' => bool, 'conflicts' => array, 'source' => 'db'|'cloudflare']
     */
    public function checkSubdomainAvailability(string $zoneDomain, string $subdomain, ?\PDO $pdo = null): array
    {
        $subdomain = strtolower(trim($subdomain));
        $zoneDomain = strtolower(trim($zoneDomain));
        $fqdn = "$subdomain.$zoneDomain";

        // 1. DB 우선 조회
        if ($pdo !== null) {
            $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
            try {
                $stmt = $pdo->prepare("SELECT record_type, reserved_by, reason FROM {$prefix}reserved_subdomains WHERE zone = ? AND subdomain = ? LIMIT 1");
                $stmt->execute([$zoneDomain, $subdomain]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    return [
                        'available' => false,
                        'conflicts' => [[
                            'type' => $row['record_type'] ?? '?',
                            'name' => $fqdn,
                            'content' => $row['reason'] ?? 'reserved',
                        ]],
                        'source' => 'db',
                        'fqdn' => $fqdn,
                    ];
                }
            } catch (\Throwable $e) {
                // DB 조회 실패 시 Cloudflare 로 fallback
                error_log('[checkSubdomainAvailability] DB 조회 실패: ' . $e->getMessage());
            }
        }

        // 2. Cloudflare API fallback
        $zoneId = $this->getZoneId($zoneDomain);
        if (!$zoneId) {
            return ['available' => false, 'conflicts' => [], 'source' => 'cloudflare', 'reason' => 'zone_not_found'];
        }
        $records = $this->listRecords($zoneId);
        $conflicts = [];
        foreach ($records as $r) {
            if (strcasecmp($r['name'], $fqdn) === 0 && in_array($r['type'], ['A', 'AAAA', 'CNAME'], true)) {
                $conflicts[] = [
                    'type' => $r['type'],
                    'name' => $r['name'],
                    'content' => substr($r['content'], 0, 80),
                ];
                // DB 에 자동 등록 (다음 조회는 DB 에서 빠르게)
                if ($pdo !== null) {
                    try {
                        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
                        $rt = ($r['type'] === 'CNAME' && str_contains($r['content'] ?? '', 'cfargotunnel.com')) ? 'tunnel' : $r['type'];
                        $pdo->prepare("INSERT INTO {$prefix}reserved_subdomains (zone, subdomain, record_type, reserved_by, reason) VALUES (?, ?, ?, 'system', 'Cloudflare 점유 (auto-detected)') ON DUPLICATE KEY UPDATE updated_at=NOW()")
                            ->execute([$zoneDomain, $subdomain, $rt]);
                    } catch (\Throwable $e) { /* silent */ }
                }
            }
        }

        return [
            'available' => empty($conflicts),
            'conflicts' => $conflicts,
            'source' => 'cloudflare',
            'fqdn' => $fqdn,
        ];
    }

    /**
     * zone 의 모든 DNS 레코드.
     */
    public function listRecords(string $zoneId): array
    {
        $res = $this->call('GET', "/zones/$zoneId/dns_records?per_page=100");
        return $res['result'] ?? [];
    }

    /**
     * 새 레코드 추가.
     * record: ['type','name','content','ttl','proxied','priority']
     */
    public function addRecord(string $zoneId, array $record): array
    {
        $record['ttl'] = $record['ttl'] ?? 1;          // 1 = auto
        $record['proxied'] = $record['proxied'] ?? false;
        $res = $this->call('POST', "/zones/$zoneId/dns_records", $record);
        return $res['result'] ?? [];
    }

    public function updateRecord(string $zoneId, string $recordId, array $record): array
    {
        $res = $this->call('PUT', "/zones/$zoneId/dns_records/$recordId", $record);
        return $res['result'] ?? [];
    }

    public function deleteRecord(string $zoneId, string $recordId): array
    {
        $res = $this->call('DELETE', "/zones/$zoneId/dns_records/$recordId");
        return $res['result'] ?? [];
    }

    /**
     * CNAME 레코드 업서트 (없으면 생성, 있으면 갱신, 다른 type 충돌 시 삭제 후 생성).
     * 호스팅 도메인을 Cloudflare Tunnel 로 라우팅 — RFC 1034 의 와일드카드 우회용.
     */
    public function upsertCname(string $zoneDomain, string $name, string $target, bool $proxied = true): array
    {
        $zoneId = $this->getZoneId($zoneDomain);
        if (!$zoneId) {
            throw new \RuntimeException("Cloudflare zone 없음: $zoneDomain");
        }
        $existing = $this->listRecords($zoneId);
        $rec = [
            'type' => 'CNAME',
            'name' => $name,
            'content' => $target,
            'ttl' => 1,
            'proxied' => $proxied,
        ];
        // 같은 name 의 A/AAAA/CNAME 레코드가 있으면 (RFC 1034 충돌) 삭제 후 새 CNAME 생성
        foreach ($existing as $ex) {
            if (strcasecmp($ex['name'], $name) === 0 && in_array($ex['type'], ['A', 'AAAA', 'CNAME'], true)) {
                if ($ex['type'] === 'CNAME') {
                    return ['action' => 'update', 'record' => $this->updateRecord($zoneId, $ex['id'], $rec)];
                }
                $this->deleteRecord($zoneId, $ex['id']);
            }
        }
        return ['action' => 'create', 'record' => $this->addRecord($zoneId, $rec)];
    }

    /**
     * 도메인의 메일 레코드 일괄 셋업 (MX, SPF, DKIM, DMARC).
     * 같은 type+name 의 기존 레코드는 업데이트, 없으면 추가 (멱등성).
     *
     * $opts:
     *   mail_host        — MX 가리킬 서버 (기본 'mail.voscms.com')
     *   spf_include      — SPF include 도메인 (기본 'voscms.com')
     *   dkim_selector    — DKIM selector (기본 'mail')
     *   dkim_pubkey      — DKIM 공개키 ("v=DKIM1; k=rsa; p=...")
     *   dmarc_report_to  — DMARC rua 주소 (기본 postmaster@{domain})
     *   subdomain        — 셋업 대상 서브도메인 (예: '@' 또는 'customer' for customer.21ces.net)
     */
    public function setupMailRecords(string $zoneDomain, array $opts = []): array
    {
        $zoneId = $this->getZoneId($zoneDomain);
        if (!$zoneId) {
            throw new \RuntimeException("Cloudflare zone 없음: $zoneDomain");
        }

        $mailHost      = $opts['mail_host']       ?? 'mail.voscms.com';
        $spfInclude    = $opts['spf_include']     ?? 'voscms.com';
        $dkimSelector  = $opts['dkim_selector']   ?? 'mail';
        $dkimPubkey    = $opts['dkim_pubkey']     ?? '';
        $subdomain     = $opts['subdomain']       ?? '@';
        $hostFor       = function ($prefix) use ($subdomain, $zoneDomain) {
            // '@' 인 경우 그냥 zone 도메인 또는 prefix.zoneDomain
            // 'customer' 같은 서브도메인 모드면 prefix.customer.zoneDomain
            $base = ($subdomain === '@' || $subdomain === '') ? $zoneDomain : "$subdomain.$zoneDomain";
            return $prefix === '@' ? $base : "$prefix.$base";
        };
        $rootName = $hostFor('@');
        $dmarcReportTo = $opts['dmarc_report_to'] ?? "postmaster@$rootName";

        $desiredRecords = [
            [
                'type' => 'MX', 'name' => $rootName,
                'content' => $mailHost, 'priority' => 10, 'ttl' => 1,
            ],
            [
                'type' => 'TXT', 'name' => $rootName,
                'content' => "v=spf1 include:$spfInclude ~all", 'ttl' => 1,
            ],
            [
                'type' => 'TXT', 'name' => "_dmarc.$rootName",
                'content' => "v=DMARC1; p=none; rua=mailto:$dmarcReportTo", 'ttl' => 1,
            ],
        ];

        if ($dkimPubkey !== '') {
            $desiredRecords[] = [
                'type' => 'TXT', 'name' => "{$dkimSelector}._domainkey.$rootName",
                'content' => $dkimPubkey, 'ttl' => 1,
            ];
        }

        // 기존 레코드 조회 → upsert
        $existing = $this->listRecords($zoneId);
        $results = [];
        foreach ($desiredRecords as $rec) {
            $found = null;
            foreach ($existing as $ex) {
                if ($ex['type'] === $rec['type'] && strcasecmp($ex['name'], $rec['name']) === 0) {
                    // SPF / DMARC 는 v=... prefix 로 추가 식별 (다른 TXT 와 구분)
                    if ($rec['type'] === 'TXT') {
                        if ($rec['name'] === $rootName && stripos($ex['content'], 'v=spf1') === 0 && stripos($rec['content'], 'v=spf1') === 0) {
                            $found = $ex; break;
                        }
                        if (str_starts_with($rec['name'], '_dmarc') && stripos($ex['content'], 'v=DMARC1') !== false) {
                            $found = $ex; break;
                        }
                        if (str_contains($rec['name'], '_domainkey') && stripos($ex['content'], 'v=DKIM1') !== false) {
                            $found = $ex; break;
                        }
                    } else {
                        $found = $ex; break;
                    }
                }
            }
            if ($found) {
                $results[] = ['action' => 'update', 'record' => $this->updateRecord($zoneId, $found['id'], $rec)];
            } else {
                $results[] = ['action' => 'create', 'record' => $this->addRecord($zoneId, $rec)];
            }
        }
        return $results;
    }

    /**
     * 서브도메인 셋업 — 21ces.net 의 customer.21ces.net 같은 임시 발급용.
     * A 레코드 (서브도메인 → IP) + 메일 레코드 셋업.
     */
    public function setupSubdomain(string $zoneDomain, string $subdomain, string $targetIp, array $mailOpts = []): array
    {
        $zoneId = $this->getZoneId($zoneDomain);
        if (!$zoneId) {
            throw new \RuntimeException("Cloudflare zone 없음: $zoneDomain");
        }

        // A 레코드
        $aRecord = [
            'type' => 'A',
            'name' => "$subdomain.$zoneDomain",
            'content' => $targetIp,
            'ttl' => 1,
            'proxied' => false,
        ];

        $existing = $this->listRecords($zoneId);
        $aResult = null;
        foreach ($existing as $ex) {
            if ($ex['type'] === 'A' && strcasecmp($ex['name'], $aRecord['name']) === 0) {
                $aResult = ['action' => 'update', 'record' => $this->updateRecord($zoneId, $ex['id'], $aRecord)];
                break;
            }
        }
        if (!$aResult) {
            $aResult = ['action' => 'create', 'record' => $this->addRecord($zoneId, $aRecord)];
        }

        // 메일 레코드 (같은 zone 안 서브도메인 모드)
        $mailResults = $this->setupMailRecords($zoneDomain, array_merge($mailOpts, ['subdomain' => $subdomain]));

        return [
            'a_record' => $aResult,
            'mail_records' => $mailResults,
        ];
    }

    /**
     * Cloudflare API 호출 (curl, JSON).
     */
    private function call(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init(self::API_BASE . $path);
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
        }
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            throw new \RuntimeException("Cloudflare API curl error: $err");
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException("Cloudflare API non-JSON response (HTTP $code): " . substr($response ?? '', 0, 200));
        }
        if (empty($decoded['success'])) {
            $msg = $decoded['errors'][0]['message'] ?? 'unknown';
            $errCode = $decoded['errors'][0]['code'] ?? 0;
            throw new \RuntimeException("Cloudflare API failed (HTTP $code, code $errCode): $msg");
        }
        return $decoded;
    }
}
