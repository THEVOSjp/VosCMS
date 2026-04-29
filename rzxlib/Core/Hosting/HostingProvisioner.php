<?php
namespace RzxLib\Core\Hosting;

/**
 * 호스팅 자동 프로비저닝 — Phase 1
 *
 * 결제 완료 시 호출되어 다음 작업을 자동 수행:
 *   1. Linux 시스템 사용자 생성 (host_<order_short>)
 *   2. 디렉토리 구조 (/var/www/customers/<order>/{public_html,logs,tmp})
 *   3. 디스크 쿼터 (플랜별 용량)
 *   4. Nginx vhost 자동 생성 + reload
 *   5. welcome.html 배포
 *
 * 실패 시 트랜잭션 롤백 (역순 정리).
 *
 * Phase 2: PHP-FPM pool 격리 + Let's Encrypt
 * Phase 3: MySQL DB + user
 * Phase 4: voscms 자동 설치
 */
class HostingProvisioner
{
    private \PDO $pdo;
    private string $prefix;
    private string $hostingRoot;       // /var/www/customers
    private string $templateDir;       // /var/www/voscms/config/templates
    private string $nginxAvailable;    // /etc/nginx/sites-available
    private string $nginxEnabled;      // /etc/nginx/sites-enabled
    private string $fpmPoolDir;        // /etc/php/8.3/fpm/pool.d
    private string $sslEmail;          // Let's Encrypt 등록 이메일
    private ?string $dbAdminUser;      // hosting_admin
    private ?string $dbAdminPass;

    /** @var array<string,callable> 롤백 액션 */
    private array $rollbackActions = [];

    public function __construct(\PDO $pdo, string $prefix = 'rzx_')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->hostingRoot = '/var/www/customers';
        $this->templateDir = defined('BASE_PATH') ? BASE_PATH . '/config/templates' : '/var/www/voscms/config/templates';
        $this->nginxAvailable = '/etc/nginx/sites-available';
        $this->nginxEnabled = '/etc/nginx/sites-enabled';
        $this->fpmPoolDir = '/etc/php/8.3/fpm/pool.d';
        $this->sslEmail = $_ENV['LE_EMAIL'] ?? 'webmaster@voscms.com';
        $this->dbAdminUser = $_ENV['HOSTING_DB_ADMIN_USER'] ?? null;
        $this->dbAdminPass = $_ENV['HOSTING_DB_ADMIN_PASS'] ?? null;
    }

    /**
     * 호스팅 신규 셋업.
     *
     * @param string $orderNumber 주문번호 (예: SVC260428AE7476)
     * @param string $domain      도메인 (예: hotel.21ces.com)
     * @param string $capacity    용량 (예: 1GB / 5GB / 10GB)
     * @return array{success:bool, username?:string, docroot?:string, vhost?:string, message?:string, errors?:array}
     */
    public function provision(string $orderNumber, string $domain, string $capacity): array
    {
        $this->rollbackActions = [];
        $orderNumber = strtoupper(trim($orderNumber));
        $domain = strtolower(trim($domain));

        try {
            // 1. 입력 검증
            $this->validate($orderNumber, $domain, $capacity);

            // 2. 사용자명 생성 + 충돌 검사
            $username = $this->makeUsername($orderNumber);
            if ($this->userExists($username)) {
                throw new \RuntimeException("사용자 이미 존재: {$username}");
            }

            // 3. 디렉토리 베이스 (먼저 생성, useradd 의 home 으로 사용)
            $home = $this->hostingRoot . '/' . $orderNumber;
            $docroot = $home . '/public_html';
            $logs = $home . '/logs';
            $tmp = $home . '/tmp';

            // 4. 디렉토리 생성 — useradd 의 -m 이 home 만들지만 명시적으로
            $this->run("/usr/bin/mkdir -p " . escapeshellarg($home));
            $this->rollbackActions[] = function() use ($home) {
                @$this->run("/usr/bin/rm -rf " . escapeshellarg($home), true);
            };

            // 5. Linux 사용자 생성
            $this->run(sprintf(
                '/usr/sbin/useradd -m -d %s -s /usr/sbin/nologin -G www-data %s',
                escapeshellarg($home),
                escapeshellarg($username)
            ));
            $this->rollbackActions[] = function() use ($username) {
                @$this->run("/usr/sbin/userdel -r " . escapeshellarg($username), true);
            };

            // 6. 하위 디렉토리 생성
            foreach ([$docroot, $logs, $tmp] as $dir) {
                $this->run("/usr/bin/mkdir -p " . escapeshellarg($dir));
            }
            $this->run(sprintf('/usr/bin/chown -R %s:www-data %s', escapeshellarg($username), escapeshellarg($home)));
            $this->run('/usr/bin/chmod 750 ' . escapeshellarg($home));
            $this->run('/usr/bin/chmod 2775 ' . escapeshellarg($docroot));
            $this->run('/usr/bin/chmod 750 ' . escapeshellarg($logs));
            $this->run('/usr/bin/chmod 1770 ' . escapeshellarg($tmp));

            // 7. 디스크 쿼터 설정
            [$softKb, $hardKb] = $this->capacityToKb($capacity);
            $this->run(sprintf(
                '/usr/sbin/setquota -u %s %d %d 0 0 /var/www',
                escapeshellarg($username),
                $softKb,
                $hardKb
            ));
            // 쿼터는 useradd rollback 시 자동 해제됨 (사용자 삭제로)

            // 8. welcome.html 배포
            $this->deployWelcome($docroot, $orderNumber, $domain, $capacity, $username);

            // 9. PHP-FPM pool 생성 (사용자 격리)
            $fpmPoolPath = $this->fpmPoolDir . '/' . $orderNumber . '.conf';
            $this->writeFpmPool($fpmPoolPath, $orderNumber, $username, $domain);
            $this->rollbackActions[] = function() use ($fpmPoolPath) {
                @$this->run('/usr/bin/rm ' . escapeshellarg($fpmPoolPath), true);
                @$this->run('/usr/bin/systemctl reload php8.3-fpm', true);
            };
            $this->run('/usr/bin/systemctl reload php8.3-fpm');

            // 10. Nginx vhost 생성
            $vhostPath = $this->nginxAvailable . '/' . $domain . '.conf';
            $vhostEnabled = $this->nginxEnabled . '/' . $domain . '.conf';
            $this->writeNginxVhost($vhostPath, $domain, $docroot, $orderNumber, $username);
            $this->rollbackActions[] = function() use ($vhostPath, $vhostEnabled) {
                @$this->run("/usr/bin/rm " . escapeshellarg($vhostEnabled), true);
                @$this->run("/usr/bin/rm " . escapeshellarg($vhostPath), true);
            };

            // 11. enable
            $this->run(sprintf('/usr/bin/ln -sf %s %s', escapeshellarg($vhostPath), escapeshellarg($vhostEnabled)));

            // 12. nginx 검증 + reload
            $this->run('/usr/sbin/nginx -t');
            $this->run('/usr/bin/systemctl reload nginx');

            // 13. SSL 발급 (Let's Encrypt) — 실패해도 호스팅은 정상 (HTTP 만 동작)
            $sslResult = $this->setupSsl($domain, $orderNumber);

            // 14. MySQL DB + user 자동 생성
            $dbResult = $this->createDatabase($orderNumber, $username);
            if ($dbResult['success']) {
                $this->rollbackActions[] = function() use ($orderNumber, $username) {
                    @$this->dropDatabase($orderNumber, $username);
                };
            }

            // 15. 로그
            $this->logProvision($orderNumber, 'hosting_provisioned', [
                'username' => $username,
                'home' => $home,
                'docroot' => $docroot,
                'capacity' => $capacity,
                'quota_kb' => ['soft' => $softKb, 'hard' => $hardKb],
                'vhost' => $vhostPath,
                'fpm_pool' => $fpmPoolPath,
                'ssl' => $sslResult,
                'db' => $dbResult['success'] ? ['name' => $dbResult['db_name'], 'user' => $dbResult['db_user']] : ['error' => $dbResult['error'] ?? 'unknown'],
            ]);

            return [
                'success' => true,
                'order_number' => $orderNumber,
                'username' => $username,
                'home' => $home,
                'docroot' => $docroot,
                'vhost' => $vhostPath,
                'fpm_pool' => $fpmPoolPath,
                'capacity' => $capacity,
                'quota_kb' => ['soft' => $softKb, 'hard' => $hardKb],
                'ssl' => $sslResult,
                'db' => $dbResult,
            ];
        } catch (\Throwable $e) {
            $this->rollback();
            error_log('[HostingProvisioner] provision error: ' . $e->getMessage());
            return [
                'success' => false,
                'order_number' => $orderNumber,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 호스팅 해제 — 사용자 + 디렉토리 + nginx vhost 모두 삭제.
     */
    public function deprovision(string $orderNumber, string $domain): array
    {
        $orderNumber = strtoupper(trim($orderNumber));
        $domain = strtolower(trim($domain));
        $username = $this->makeUsername($orderNumber);
        $home = $this->hostingRoot . '/' . $orderNumber;
        $vhostPath = $this->nginxAvailable . '/' . $domain . '.conf';
        $vhostEnabled = $this->nginxEnabled . '/' . $domain . '.conf';
        $fpmPoolPath = $this->fpmPoolDir . '/' . $orderNumber . '.conf';
        $errors = [];

        // 1. SSL 인증서 삭제 (Let's Encrypt) — 없으면 certbot 가 알아서 처리
        try {
            $this->run("/usr/bin/certbot delete --cert-name " . escapeshellarg($domain) . " --non-interactive");
        } catch (\Throwable $e) {
            // 'No certificate found' 같은 에러는 정상 (이미 없음)
            if (stripos($e->getMessage(), 'No certificate') === false) {
                $errors[] = 'cert delete: ' . $e->getMessage();
            }
        }

        // 2. nginx 비활성화
        if (file_exists($vhostEnabled)) {
            try { $this->run("/usr/bin/rm " . escapeshellarg($vhostEnabled)); }
            catch (\Throwable $e) { $errors[] = 'rm enabled: ' . $e->getMessage(); }
        }
        if (file_exists($vhostPath)) {
            try { $this->run("/usr/bin/rm " . escapeshellarg($vhostPath)); }
            catch (\Throwable $e) { $errors[] = 'rm available: ' . $e->getMessage(); }
        }
        try { $this->run('/usr/bin/systemctl reload nginx'); }
        catch (\Throwable $e) { $errors[] = 'nginx reload: ' . $e->getMessage(); }

        // 3. PHP-FPM pool 제거
        if (file_exists($fpmPoolPath)) {
            try { $this->run("/usr/bin/rm " . escapeshellarg($fpmPoolPath)); }
            catch (\Throwable $e) { $errors[] = 'rm fpm pool: ' . $e->getMessage(); }
            try { $this->run('/usr/bin/systemctl reload php8.3-fpm'); }
            catch (\Throwable $e) { $errors[] = 'php-fpm reload: ' . $e->getMessage(); }
        }

        // 4. MySQL DB + user 삭제
        try {
            $this->dropDatabase($orderNumber, $username);
        } catch (\Throwable $e) { $errors[] = 'db drop: ' . $e->getMessage(); }

        // 5. 사용자 + 홈 삭제 (userdel -r 가 home 도 자동 삭제)
        if ($this->userExists($username)) {
            try { $this->run("/usr/sbin/userdel -r " . escapeshellarg($username)); }
            catch (\Throwable $e) { $errors[] = 'userdel: ' . $e->getMessage(); }
        }
        // 사용자 삭제 후 잔여 홈 디렉토리 강제 정리
        if (is_dir($home)) {
            try { $this->run("/usr/bin/rm -rf " . escapeshellarg($home)); }
            catch (\Throwable $e) { $errors[] = 'rm home: ' . $e->getMessage(); }
        }

        $this->logProvision($orderNumber, 'hosting_deprovisioned', [
            'username' => $username,
            'errors' => $errors,
        ]);

        return [
            'success' => empty($errors),
            'order_number' => $orderNumber,
            'errors' => $errors,
        ];
    }

    // ======= 내부 헬퍼 =======

    private function validate(string $orderNumber, string $domain, string $capacity): void
    {
        if (!preg_match('/^[A-Z0-9-]{6,40}$/', $orderNumber)) {
            throw new \InvalidArgumentException("잘못된 주문 번호: {$orderNumber}");
        }
        if (!preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?\.[a-z]{2,}$/', $domain)) {
            throw new \InvalidArgumentException("잘못된 도메인: {$domain}");
        }
        if (!preg_match('/^[\d.]+\s*(GB|TB|MB)$/i', $capacity)) {
            throw new \InvalidArgumentException("잘못된 용량: {$capacity}");
        }
        // nginx vhost 충돌 검사 — 같은 도메인 server_name 이 이미 있으면 거부
        $existingVhosts = @glob('/etc/nginx/sites-available/*');
        if ($existingVhosts) {
            foreach ($existingVhosts as $vhost) {
                if (basename($vhost) === $domain . '.conf') continue; // 자기 자신 (재시도 시)
                $content = @file_get_contents($vhost);
                if ($content === false) continue;
                // server_name 라인에서 정확히 매칭 (단어 경계)
                if (preg_match('/^\s*server_name\s+[^;]*\b' . preg_quote($domain, '/') . '\b/m', $content)) {
                    throw new \RuntimeException("도메인 {$domain} 이 이미 다른 vhost (" . basename($vhost) . ") 에서 사용 중입니다.");
                }
            }
        }
        // reserved_subdomains 에 등록된 시스템 도메인 거부
        $parts = explode('.', $domain, 2);
        if (count($parts) === 2) {
            try {
                $stmt = $this->pdo->prepare("SELECT reserved_by, reason FROM {$this->prefix}reserved_subdomains WHERE zone = ? AND subdomain = ? LIMIT 1");
                $stmt->execute([$parts[1], $parts[0]]);
                if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    throw new \RuntimeException("도메인 {$domain} 은 시스템 예약 도메인입니다 ({$row['reason']})");
                }
            } catch (\PDOException $e) { /* table 없으면 skip */ }
        }
    }

    /** 주문번호 → vos_<주문번호 전체> (대소문자 보존, 영숫자만) */
    private function makeUsername(string $orderNumber): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $orderNumber);
        if (strlen($clean) < 6) {
            throw new \InvalidArgumentException("주문번호로 사용자명 생성 불가: {$orderNumber}");
        }
        // Linux 사용자명 32자 한계 — vos_ (4자) + 28자
        $clean = substr($clean, 0, 28);
        return 'vos_' . $clean;
    }

    private function userExists(string $username): bool
    {
        return posix_getpwnam($username) !== false;
    }

    /** "1GB" / "5GB" / "1TB" / "500MB" → [softKb, hardKb] (hard = soft + 10%) */
    private function capacityToKb(string $capacity): array
    {
        if (!preg_match('/^([\d.]+)\s*(GB|TB|MB)$/i', $capacity, $m)) {
            throw new \InvalidArgumentException("용량 파싱 실패: {$capacity}");
        }
        $n = (float)$m[1];
        $unit = strtoupper($m[2]);
        $kb = match($unit) {
            'TB' => (int)round($n * 1024 * 1024 * 1024),
            'GB' => (int)round($n * 1024 * 1024),
            'MB' => (int)round($n * 1024),
        };
        $soft = $kb;
        $hard = (int)round($kb * 1.1); // 10% grace
        return [$soft, $hard];
    }

    private function deployWelcome(string $docroot, string $order, string $domain, string $capacity, string $username): void
    {
        $tplPath = $this->templateDir . '/welcome.html.tpl';
        if (!is_readable($tplPath)) return;
        $tpl = file_get_contents($tplPath);
        $html = strtr($tpl, [
            '{{DOMAIN}}' => htmlspecialchars($domain, ENT_QUOTES),
            '{{ORDER}}' => htmlspecialchars($order, ENT_QUOTES),
            '{{CAPACITY}}' => htmlspecialchars($capacity, ENT_QUOTES),
            '{{DATE}}' => date('Y-m-d H:i:s'),
        ]);
        $tmpFile = sys_get_temp_dir() . '/welcome_' . uniqid() . '.html';
        file_put_contents($tmpFile, $html);
        @chmod($tmpFile, 0644);
        $dest = $docroot . '/index.html';
        $this->run(sprintf('/usr/bin/cp %s %s', escapeshellarg($tmpFile), escapeshellarg($dest)));
        $this->run(sprintf('/usr/bin/chown %s:www-data %s', escapeshellarg($username), escapeshellarg($dest)));
        $this->run('/usr/bin/chmod 644 ' . escapeshellarg($dest));
        @unlink($tmpFile);
    }

    private function writeNginxVhost(string $path, string $domain, string $docroot, string $order, string $username): void
    {
        $tplPath = $this->templateDir . '/nginx-vhost.conf.tpl';
        if (!is_readable($tplPath)) {
            throw new \RuntimeException("nginx 템플릿 없음: {$tplPath}");
        }
        $conf = strtr(file_get_contents($tplPath), [
            '{{DOMAIN}}' => $domain,
            '{{DOCROOT}}' => $docroot,
            '{{ORDER}}' => $order,
            '{{USER}}' => $username,
        ]);
        $this->writeRootFile($path, $conf);
    }

    private function writeFpmPool(string $path, string $order, string $username, string $domain): void
    {
        $tplPath = $this->templateDir . '/php-fpm-pool.conf.tpl';
        if (!is_readable($tplPath)) {
            throw new \RuntimeException("FPM pool 템플릿 없음: {$tplPath}");
        }
        $conf = strtr(file_get_contents($tplPath), [
            '{{ORDER}}' => $order,
            '{{USER}}' => $username,
            '{{DOMAIN}}' => $domain,
        ]);
        $this->writeRootFile($path, $conf);
    }

    /**
     * Let's Encrypt SSL 발급 — Cloudflare DNS-01 challenge.
     * Cloudflare proxy 뒤에 있어도 / 외부 도달성 무관하게 발급 가능.
     * 실패해도 호스팅은 정상 (HTTP 만 동작), 결과만 반환.
     *
     * @return array{success:bool, cert?:string, expires?:string, error?:string, skipped?:bool}
     */
    private function setupSsl(string $domain, string $orderNumber): array
    {
        $cfCredentials = '/etc/letsencrypt/cloudflare/credentials.ini';
        if (!file_exists($cfCredentials)) {
            return ['success' => false, 'skipped' => true, 'error' => 'Cloudflare credentials 파일 없음'];
        }

        try {
            $cmd = sprintf(
                '/usr/bin/certbot certonly --dns-cloudflare --dns-cloudflare-credentials %s --dns-cloudflare-propagation-seconds 30 -d %s --agree-tos --non-interactive --email %s',
                escapeshellarg($cfCredentials),
                escapeshellarg($domain),
                escapeshellarg($this->sslEmail)
            );
            $this->run($cmd);

            // vhost 에 HTTPS server 블록 추가
            $this->addHttpsToVhost($domain, $orderNumber);
            $this->run('/usr/sbin/nginx -t');
            $this->run('/usr/bin/systemctl reload nginx');

            $certPath = "/etc/letsencrypt/live/{$domain}/fullchain.pem";
            $expires = null;
            if (file_exists($certPath)) {
                $expires = trim(shell_exec("openssl x509 -enddate -noout -in " . escapeshellarg($certPath) . " | cut -d= -f2") ?: '');
            }
            return ['success' => true, 'cert' => $certPath, 'expires' => $expires];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 기존 vhost 에 SSL 적용 — Cloudflare Tunnel 호환 단일 server 블록.
     * 80/443 모두 같은 컨텐츠 서빙 (HTTP→HTTPS 리다이렉트 없음).
     * 터널은 origin 에 HTTP(80) 로 전달, 직접 접속은 HTTPS(443) 사용.
     */
    private function addHttpsToVhost(string $domain, string $orderNumber): void
    {
        $vhostPath = $this->nginxAvailable . '/' . $domain . '.conf';
        if (!file_exists($vhostPath)) {
            throw new \RuntimeException("vhost 파일 없음: {$vhostPath}");
        }
        $content = @file_get_contents($vhostPath);
        if ($content === false) {
            throw new \RuntimeException("vhost 읽기 실패: {$vhostPath}");
        }
        if (strpos($content, 'listen 443') !== false) {
            return; // 이미 HTTPS 적용됨
        }

        $docroot = $this->hostingRoot . '/' . $orderNumber . '/public_html';
        $username = $this->makeUsername($orderNumber);

        $block = <<<NGINX
server {
    listen 80;
    listen [::]:80;
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name {$domain};

    ssl_certificate     /etc/letsencrypt/live/{$domain}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{$domain}/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;

    root {$docroot};
    index index.html index.php;

    access_log /var/www/customers/{$orderNumber}/logs/access.log;
    error_log  /var/www/customers/{$orderNumber}/logs/error.log warn;

    client_max_body_size 64M;

    location /.well-known/acme-challenge/ {
        root {$docroot};
    }

    location ~ /\.(?!well-known) { deny all; }

    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/{$orderNumber}.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param HOSTING_ORDER {$orderNumber};
        fastcgi_param HOSTING_USER {$username};
        fastcgi_read_timeout 300s;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
}
NGINX;

        $this->writeRootFile($vhostPath, $block . "\n");
    }

    /**
     * MySQL DB + 전용 user 생성. 사용자명 = vos_<order> (Linux 사용자명과 동일).
     * DB 명도 동일. 비밀번호는 랜덤 32 hex.
     *
     * @return array{success:bool, db_name?:string, db_user?:string, db_pass?:string, error?:string}
     */
    private function createDatabase(string $orderNumber, string $username): array
    {
        if (!$this->dbAdminUser || !$this->dbAdminPass) {
            return ['success' => false, 'error' => 'HOSTING_DB_ADMIN_USER/PASS not set in .env'];
        }
        // MySQL 사용자명/DB명 64자 한계 — username 그대로 사용 (vos_ + order)
        $dbName = $username;
        $dbUser = $username;
        $dbPass = bin2hex(random_bytes(16)); // 32 hex chars

        try {
            $admin = new \PDO(
                'mysql:host=127.0.0.1;charset=utf8mb4',
                $this->dbAdminUser,
                $this->dbAdminPass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $dbNameQuoted = '`' . str_replace('`', '``', $dbName) . '`';
            // CREATE DATABASE
            $admin->exec("CREATE DATABASE IF NOT EXISTS {$dbNameQuoted} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            // CREATE USER (있으면 무시)
            $stmt = $admin->prepare("CREATE USER IF NOT EXISTS ?@'localhost' IDENTIFIED BY ?");
            $stmt->execute([$dbUser, $dbPass]);
            // 비밀번호는 항상 새로 설정 (CREATE IF NOT EXISTS 가 옛 비번 유지하므로)
            $stmt = $admin->prepare("ALTER USER ?@'localhost' IDENTIFIED BY ?");
            $stmt->execute([$dbUser, $dbPass]);
            // GRANT — 자기 DB 만 (격리)
            $admin->exec("GRANT ALL PRIVILEGES ON {$dbNameQuoted}.* TO " . $admin->quote($dbUser) . "@'localhost'");
            $admin->exec("FLUSH PRIVILEGES");

            return [
                'success' => true,
                'db_host' => 'localhost',
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * VosCMS 자동 설치 — 신청 시 'install' addon 선택한 경우 호출.
     * 1) voscms-X.Y.Z.zip 압축 해제 → public_html
     * 2) .env 자동 생성 (DB 정보 주입)
     * 3) install-core.php 를 internal HTTP 로 호출하여 DB 마이그레이션
     *
     * @return array{success:bool, version?:string, admin_url?:string, error?:string}
     */
    public function installVoscms(string $orderNumber, string $domain, array $dbInfo, array $installInfo): array
    {
        $username = $this->makeUsername($orderNumber);
        $docroot = $this->hostingRoot . '/' . $orderNumber . '/public_html';

        // 1. 최신 voscms-dist zip 찾기
        $distDir = '/var/www/voscms-dist';
        $zips = glob($distDir . '/voscms-*.zip') ?: [];
        if (empty($zips)) {
            return ['success' => false, 'error' => 'voscms-dist zip 패키지를 찾을 수 없습니다.'];
        }
        usort($zips, 'version_compare');
        $latestZip = end($zips);
        if (!preg_match('/voscms-(.+)\.zip$/', basename($latestZip), $m)) {
            return ['success' => false, 'error' => '잘못된 zip 파일명: ' . basename($latestZip)];
        }
        $version = $m[1];

        try {
            // 2. 임시 디렉토리에 압축 해제
            $tmpDir = sys_get_temp_dir() . '/voscms_install_' . uniqid();
            mkdir($tmpDir, 0700, true);
            $zip = new \ZipArchive();
            if ($zip->open($latestZip) !== true) {
                throw new \RuntimeException('zip 열기 실패');
            }
            $zip->extractTo($tmpDir);
            $zip->close();

            // 압축 결과: $tmpDir/voscms-X.Y.Z/ 또는 $tmpDir/* 직접
            $srcDir = $tmpDir;
            $sub = $tmpDir . '/voscms-' . $version;
            if (is_dir($sub)) $srcDir = $sub;

            // 3. public_html 으로 복사 (chown vos_<order>) — 단 기존 index.html (welcome) 보존
            $this->run(sprintf('/usr/bin/cp -a %s/. %s/', escapeshellarg($srcDir), escapeshellarg($docroot)));
            // welcome.html 을 index.html 로 보존 (사용자가 install 완료 시 자동 삭제됨)
            // voscms 의 index.html 가 있으면 무시되고, 우리가 만든 welcome 이 nginx index 우선
            $this->run(sprintf('/usr/bin/chown -R %s:www-data %s', escapeshellarg($username), escapeshellarg($docroot)));

            // 4. 임시 디렉토리 정리
            $this->run('/usr/bin/rm -rf ' . escapeshellarg($tmpDir));

            // 5. .env 자동 생성
            $envPath = $docroot . '/.env';
            $appKey = 'base64:' . base64_encode(random_bytes(32));
            $envContent = <<<ENV
APP_NAME="{$installInfo['site_title']}"
APP_ENV=production
APP_DEBUG=false
APP_KEY={$appKey}
APP_URL=https://{$domain}

DB_CONNECTION=mysql
DB_HOST={$dbInfo['db_host']}
DB_PORT=3306
DB_DATABASE={$dbInfo['db_name']}
DB_USERNAME={$dbInfo['db_user']}
DB_PASSWORD={$dbInfo['db_pass']}
DB_PREFIX=rzx_

ADMIN_PATH=admin
SESSION_LIFETIME=10080

# 호스팅 자동 셋업 — by VosCMS Hosting Provisioner
HOSTING_ORDER={$orderNumber}
HOSTING_PROVISIONED_AT={$this->isoNow()}
ENV;
            $tmpEnv = sys_get_temp_dir() . '/env_' . uniqid();
            file_put_contents($tmpEnv, $envContent);
            $this->run(sprintf('/usr/bin/cp %s %s', escapeshellarg($tmpEnv), escapeshellarg($envPath)));
            $this->run(sprintf('/usr/bin/chown %s:www-data %s', escapeshellarg($username), escapeshellarg($envPath)));
            $this->run('/usr/bin/chmod 640 ' . escapeshellarg($envPath));
            @unlink($tmpEnv);

            // 6. install-core.php 자동 실행 (headless) — step 3 → step 4 순차 호출
            $this->orderForCurrentInstall = $orderNumber;
            $autoResult = $this->runInstallCoreSteps($domain, $dbInfo, $installInfo);

            if (!$autoResult['success']) {
                return [
                    'success' => false,
                    'version' => $version,
                    'error' => 'auto-install failed: ' . ($autoResult['error'] ?? 'unknown'),
                    'install_url_fallback' => "https://{$domain}/install.php",
                    'auto_install' => $autoResult,
                ];
            }

            // 7. .installed 마커 검증 + HOSTING_* 변수 .env 에 재주입 (install-core.php 가 .env 덮어씀)
            $installedFlag = $docroot . '/storage/.installed';
            $installedAt = file_exists($installedFlag) ? trim(@file_get_contents($installedFlag) ?: '') : null;
            $this->appendHostingEnv($docroot, $orderNumber, $username);

            // 8. welcome page (index.html) 제거 — VosCMS index.php 가 우선되도록
            // docroot 가 mode 2775 (group www-data 쓰기 가능) 이므로 unlink 로 충분
            $welcomePath = $docroot . '/index.html';
            if (file_exists($welcomePath)) {
                if (!@unlink($welcomePath)) {
                    // 폴백: sudo (sudoers 에 rm 패턴이 있을 때만 동작)
                    @$this->run('/usr/bin/rm -f ' . escapeshellarg($welcomePath), true);
                }
            }

            return [
                'success' => true,
                'version' => $version,
                'admin_url' => "https://{$domain}/" . ($installInfo['admin_path'] ?? 'admin'),
                'installed_at' => $installedAt ?: date('c'),
                'auto_install' => $autoResult,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * install-core.php 를 HTTP 로 step 3 → step 4 순차 호출 (headless install).
     * _db POST fallback 활용 — 세션 의존성 우회.
     *
     * @return array{success:bool, error?:string, http_codes?:array, response_excerpts?:array}
     */
    private function runInstallCoreSteps(string $domain, array $dbInfo, array $installInfo): array
    {
        $dbPayload = [
            'dbHost' => $dbInfo['db_host'] ?? '127.0.0.1',
            'dbPort' => $dbInfo['db_port'] ?? '3306',
            'dbName' => $dbInfo['db_name'] ?? '',
            'dbUser' => $dbInfo['db_user'] ?? '',
            'dbPass' => $dbInfo['db_pass'] ?? '',
            'dbPrefix' => $dbInfo['db_prefix'] ?? 'rzx_',
        ];
        $dbBlob = base64_encode(json_encode($dbPayload));

        $excerpts = [];
        $codes = [];

        // Step 3: 마이그레이션 SQL 실행
        $r3 = $this->callInstallCore($domain, 3, ['_db' => $dbBlob]);
        $codes[3] = $r3['http_code'];
        $excerpts[3] = $r3['response_excerpt'];
        if ($r3['http_code'] !== 200) {
            return [
                'success' => false,
                'error' => 'step 3 failed (HTTP ' . $r3['http_code'] . ')',
                'http_codes' => $codes,
                'response_excerpts' => $excerpts,
            ];
        }

        // Step 4: admin 계정 + 시드 + 라이선스 + .env 재생성 + .installed
        $siteUrl = "https://{$domain}";
        $r4 = $this->callInstallCore($domain, 4, [
            '_db'         => $dbBlob,
            'admin_email' => $installInfo['admin_email'] ?? 'admin@' . $domain,
            'admin_pass'  => $installInfo['admin_pw'] ?? '',
            'admin_name'  => $installInfo['admin_id'] ?? $installInfo['site_title'] ?? 'Administrator',
            'site_name'   => $installInfo['site_title'] ?? $domain,
            'site_url'    => $siteUrl,
            'admin_path'  => $installInfo['admin_path'] ?? 'admin',
            'locale'      => $installInfo['locale'] ?? 'ko',
            'timezone'    => $installInfo['timezone'] ?? 'Asia/Seoul',
        ]);
        $codes[4] = $r4['http_code'];
        $excerpts[4] = $r4['response_excerpt'];
        if ($r4['http_code'] !== 200) {
            return [
                'success' => false,
                'error' => 'step 4 failed (HTTP ' . $r4['http_code'] . ')',
                'http_codes' => $codes,
                'response_excerpts' => $excerpts,
            ];
        }

        // .installed 마커 검증
        $installedFlag = $this->hostingRoot . '/' . $this->orderForCurrentInstall . '/public_html/storage/.installed';
        if (!file_exists($installedFlag)) {
            return [
                'success' => false,
                'error' => 'step 4 응답 200 이지만 .installed 마커 없음',
                'http_codes' => $codes,
                'response_excerpts' => $excerpts,
            ];
        }

        return ['success' => true, 'http_codes' => $codes];
    }

    /**
     * install-core.php 단일 step 호출 (loopback HTTP).
     * 호스팅 vhost 가 HTTP-only (Cloudflare edge SSL 사용) 이므로 port 80 직결.
     * Tunnel 우회 — 127.0.0.1 직접 호출.
     */
    private function callInstallCore(string $domain, int $step, array $extra): array
    {
        $payload = http_build_query(array_merge(['step' => (string)$step], $extra));
        $ch = curl_init("http://{$domain}/install-core.php?step={$step}");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_RESOLVE => [
                $domain . ':80:127.0.0.1',
            ],
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return [
            'http_code' => $httpCode,
            'curl_error' => $err ?: null,
            'response_excerpt' => $response ? substr($response, 0, 800) : null,
        ];
    }

    /**
     * install-core.php 가 .env 덮어쓴 후 호스팅 식별 정보 재주입.
     */
    private function appendHostingEnv(string $docroot, string $orderNumber, string $username): void
    {
        $envPath = $docroot . '/.env';
        if (!file_exists($envPath)) return;
        $current = @file_get_contents($envPath) ?: '';
        if (str_contains($current, 'HOSTING_ORDER=')) return; // 이미 있음
        $append = "\n# 호스팅 자동 셋업 — by VosCMS Hosting Provisioner\n"
                . "HOSTING_ORDER={$orderNumber}\n"
                . "HOSTING_USER={$username}\n"
                . "HOSTING_PROVISIONED_AT=" . $this->isoNow() . "\n";
        $tmpEnv = sys_get_temp_dir() . '/env_append_' . uniqid();
        file_put_contents($tmpEnv, $current . $append);
        $this->run(sprintf('/usr/bin/cp %s %s', escapeshellarg($tmpEnv), escapeshellarg($envPath)));
        $this->run(sprintf('/usr/bin/chown %s:www-data %s', escapeshellarg($username), escapeshellarg($envPath)));
        $this->run('/usr/bin/chmod 640 ' . escapeshellarg($envPath));
        @unlink($tmpEnv);
    }

    private string $orderForCurrentInstall = '';

    private function isoNow(): string { return date('c'); }

    /** MySQL DB + user 삭제 */
    private function dropDatabase(string $orderNumber, string $username): bool
    {
        if (!$this->dbAdminUser || !$this->dbAdminPass) return false;
        $dbName = $username;
        $dbUser = $username;
        try {
            $admin = new \PDO(
                'mysql:host=127.0.0.1;charset=utf8mb4',
                $this->dbAdminUser,
                $this->dbAdminPass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            $dbNameQuoted = '`' . str_replace('`', '``', $dbName) . '`';
            $admin->exec("DROP DATABASE IF EXISTS {$dbNameQuoted}");
            $admin->exec("DROP USER IF EXISTS " . $admin->quote($dbUser) . "@'localhost'");
            $admin->exec("FLUSH PRIVILEGES");
            return true;
        } catch (\Throwable $e) {
            error_log('[HostingProvisioner] dropDatabase: ' . $e->getMessage());
            return false;
        }
    }

    /** root 권한 파일 쓰기 (tee) */
    private function writeRootFile(string $path, string $content): void
    {
        $tmpFile = sys_get_temp_dir() . '/' . basename($path) . '.' . uniqid();
        file_put_contents($tmpFile, $content);
        $this->run(sprintf(
            '/usr/bin/tee %s < %s > /dev/null',
            escapeshellarg($path),
            escapeshellarg($tmpFile)
        ));
        @unlink($tmpFile);
    }

    /**
     * sudo 명령 실행. 실패 시 RuntimeException.
     * @param bool $silent 실패 무시
     */
    private function run(string $cmd, bool $silent = false): string
    {
        $full = 'sudo -n ' . $cmd . ' 2>&1';
        $output = [];
        $exit = 0;
        exec($full, $output, $exit);
        $out = implode("\n", $output);
        if ($exit !== 0 && !$silent) {
            throw new \RuntimeException("명령 실패 ({$exit}): {$cmd}\n{$out}");
        }
        return $out;
    }

    private function rollback(): void
    {
        // 역순 실행
        foreach (array_reverse($this->rollbackActions) as $action) {
            try { $action(); }
            catch (\Throwable $e) { error_log('[HostingProvisioner] rollback action failed: ' . $e->getMessage()); }
        }
        $this->rollbackActions = [];
    }

    private function logProvision(string $orderNumber, string $action, array $detail): void
    {
        try {
            // order_number → orders.id 조회
            $stmt = $this->pdo->prepare("SELECT id FROM {$this->prefix}orders WHERE order_number = ? LIMIT 1");
            $stmt->execute([$orderNumber]);
            $orderId = $stmt->fetchColumn();
            if (!$orderId) return;
            $this->pdo->prepare("INSERT INTO {$this->prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, ?, ?, 'system', '')")
                ->execute([$orderId, $action, json_encode($detail, JSON_UNESCAPED_UNICODE)]);
        } catch (\Throwable $e) {
            error_log('[HostingProvisioner] log error: ' . $e->getMessage());
        }
    }
}
