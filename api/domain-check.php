<?php
/**
 * 도메인 가용성 확인 API — WHOIS Socket 직접 쿼리
 *
 * GET /api/domain-check.php?domain=example
 *   → 관리자 설정에서 active=true인 TLD만 검색
 *   → 각 TLD별 WHOIS 서버로 직접 쿼리
 *
 * 응답: { success: true, domain: "example", results: [{ tld, fqdn, available, price, vip_price }] }
 */
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

// .env 로드
$envFile = BASE_PATH . '/.env';
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

$domainName = trim($_GET['domain'] ?? '');
$domainName = preg_replace('/\.[a-z.]+$/i', '', $domainName); // TLD 제거
$domainName = strtolower($domainName);

if (!$domainName || strlen($domainName) < 2 || !preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $domainName)) {
    echo json_encode(['success' => false, 'message' => '올바른 도메인 이름을 입력하세요 (영문, 숫자, 하이픈, 2자 이상)']);
    exit;
}

// TLD별 WHOIS 서버 매핑
$whoisServers = [
    // gTLD
    '.com' => 'whois.verisign-grs.com',
    '.net' => 'whois.verisign-grs.com',
    '.org' => 'whois.pir.org',
    '.info' => 'whois.afilias.net',
    '.biz' => 'whois.biz',
    '.xyz' => 'whois.nic.xyz',
    '.online' => 'whois.nic.online',
    '.site' => 'whois.nic.site',
    '.store' => 'whois.nic.store',
    '.shop' => 'whois.nic.shop',
    '.app' => 'whois.nic.google',
    '.dev' => 'whois.nic.google',
    '.io' => 'whois.nic.io',
    '.co' => 'whois.nic.co',
    '.me' => 'whois.nic.me',
    '.tv' => 'whois.nic.tv',
    '.cc' => 'ccwhois.verisign-grs.com',
    '.blog' => 'whois.nic.blog',
    '.club' => 'whois.nic.club',
    '.life' => 'whois.nic.life',
    '.live' => 'whois.nic.live',
    '.world' => 'whois.nic.world',
    '.tech' => 'whois.nic.tech',
    '.space' => 'whois.nic.space',
    '.fun' => 'whois.nic.fun',
    '.top' => 'whois.nic.top',
    '.icu' => 'whois.nic.icu',
    '.monster' => 'whois.nic.monster',
    '.email' => 'whois.nic.email',
    '.website' => 'whois.nic.website',
    '.agency' => 'whois.nic.agency',
    '.design' => 'whois.nic.design',
    '.digital' => 'whois.nic.digital',
    '.studio' => 'whois.nic.studio',
    '.media' => 'whois.nic.media',
    '.cloud' => 'whois.nic.cloud',
    '.host' => 'whois.nic.host',
    '.pro' => 'whois.nic.pro',
    '.academy' => 'whois.nic.academy',
    '.center' => 'whois.nic.center',
    '.company' => 'whois.nic.company',
    '.network' => 'whois.nic.network',
    '.solutions' => 'whois.nic.solutions',
    '.systems' => 'whois.nic.systems',
    '.technology' => 'whois.nic.technology',
    '.works' => 'whois.nic.works',
    // ccTLD — 일본
    '.jp' => 'whois.jprs.jp',
    '.co.jp' => 'whois.jprs.jp',
    '.ne.jp' => 'whois.jprs.jp',
    '.or.jp' => 'whois.jprs.jp',
    '.ac.jp' => 'whois.jprs.jp',
    '.go.jp' => 'whois.jprs.jp',
    // ccTLD — 한국
    '.kr' => 'whois.kr',
    '.co.kr' => 'whois.kr',
    '.or.kr' => 'whois.kr',
    '.ne.kr' => 'whois.kr',
    '.pe.kr' => 'whois.kr',
    '.re.kr' => 'whois.kr',
    // ccTLD — 기타
    '.cn' => 'whois.cnnic.cn',
    '.tw' => 'whois.twnic.net.tw',
    '.com.tw' => 'whois.twnic.net.tw',
    '.hk' => 'whois.hkirc.hk',
    '.com.hk' => 'whois.hkirc.hk',
    '.de' => 'whois.denic.de',
    '.fr' => 'whois.nic.fr',
    '.uk' => 'whois.nic.uk',
    '.co.uk' => 'whois.nic.uk',
    '.eu' => 'whois.eu',
    '.ru' => 'whois.tcinet.ru',
    '.us' => 'whois.nic.us',
    '.ca' => 'whois.cira.ca',
    '.au' => 'whois.auda.org.au',
    '.com.au' => 'whois.auda.org.au',
    '.in' => 'whois.registry.in',
    '.br' => 'whois.registro.br',
    '.com.br' => 'whois.registro.br',
];

// "등록 가능" 판별 패턴
$availablePatterns = [
    'No match',
    'NOT FOUND',
    'No entries found',
    'No Data Found',
    'Domain not found',
    'is free',
    'Status: AVAILABLE',
    'No information available',
    'The queried object does not exist',
    '위의 도메인이름은 등록되어 있지 않습니다',  // .kr
];

// DB에서 active TLD + 가격 로드
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_domain_pricing'");
    $stmt->execute();
    $domainPricing = json_decode($stmt->fetchColumn() ?: '[]', true) ?: [];
} catch (Exception $e) {
    $domainPricing = [];
}

// 검색 대상 TLD 로드
$searchTLDs = [];
try {
    $stmt2 = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_search_tlds'");
    $stmt2->execute();
    $searchTLDs = json_decode($stmt2->fetchColumn() ?: '', true) ?: [];
} catch (Exception $e) {}

// 검색 TLD 미설정 시 기본값
if (empty($searchTLDs)) {
    $searchTLDs = ['.com','.net','.org','.jp','.co.jp','.kr','.co.kr'];
}

// 도메인 가격 맵
$priceMap = [];
foreach ($domainPricing as $dp) {
    if (!empty($dp['tld'])) $priceMap[$dp['tld']] = $dp;
}

// 검색 대상만 필터
$activeTLDs = [];
foreach ($searchTLDs as $tld) {
    if (isset($priceMap[$tld])) {
        $activeTLDs[$tld] = $priceMap[$tld];
    } else {
        $activeTLDs[$tld] = ['tld' => $tld, 'price' => 0, 'vip_price' => 0, 'discount' => 0];
    }
}

if (empty($activeTLDs)) {
    echo json_encode(['success' => false, 'message' => '검색 가능한 TLD가 없습니다.']);
    exit;
}

// WHOIS 쿼리 함수
function checkWhois($fqdn, $server, $availablePatterns) {
    $fp = @fsockopen($server, 43, $errno, $errstr, 1);
    if (!$fp) return null; // 연결 실패

    // .jp 도메인은 쿼리 형식이 다름
    if (strpos($server, 'jprs.jp') !== false) {
        fwrite($fp, "$fqdn/e\r\n");
    } elseif (strpos($server, 'denic.de') !== false) {
        fwrite($fp, "-T dn,ace $fqdn\r\n");
    } else {
        fwrite($fp, "$fqdn\r\n");
    }

    $response = '';
    stream_set_timeout($fp, 1);
    while (!feof($fp)) {
        $line = fgets($fp, 512);
        if ($line === false) break;
        $response .= $line;
        // 판별에 충분한 데이터가 모이면 조기 종료
        if (strlen($response) > 500) break;
    }
    fclose($fp);

    if (empty(trim($response))) return null;

    foreach ($availablePatterns as $pattern) {
        if (stripos($response, $pattern) !== false) {
            return true; // 등록 가능
        }
    }
    return false; // 등록됨
}

// RDAP 폴백 함수 (WHOIS 실패 시)
function checkRdap($fqdn) {
    $url = 'https://rdap.org/domain/' . $fqdn;
    $ctx = stream_context_create(['http' => ['timeout' => 3, 'method' => 'HEAD']]);
    $headers = @get_headers($url, false, $ctx);
    if (!$headers) return null;
    $status = (int)substr($headers[0], 9, 3);
    if ($status === 404) return true;  // 등록 가능
    if ($status === 200 || $status === 301 || $status === 302) return false; // 등록됨
    return null;
}

// 순차 쿼리 (socket이므로 빠름)
$results = [];
foreach ($activeTLDs as $tld => $info) {
    // ccTLD 2단계 (.co.kr 등)는 도메인 결합 방식이 다름
    $fqdn = $domainName . $tld;

    $server = $whoisServers[$tld] ?? null;
    if (!$server) {
        // 범용 폴백: whois.nic.{tld without dot}
        $tldClean = ltrim($tld, '.');
        if (strpos($tldClean, '.') === false) {
            $server = 'whois.nic.' . $tldClean;
        }
    }

    $available = null;
    if ($server) {
        $available = checkWhois($fqdn, $server, $availablePatterns);
    }
    // WHOIS 실패 또는 서버 없음 → RDAP 폴백
    if ($available === null) {
        $available = checkRdap($fqdn);
    }
    $results[] = [
        'tld' => $tld,
        'fqdn' => $fqdn,
        'available' => $available,
        'price' => (int)($info['price'] ?? 0),
        'vip_price' => (int)($info['vip_price'] ?? 0),
        'discount' => (int)($info['discount'] ?? 0),
    ];
}

// 등록 가능한 것을 먼저, 그 다음 등록됨, 마지막 오류
usort($results, function($a, $b) {
    $order = function($v) { return $v === true ? 0 : ($v === false ? 1 : 2); };
    return $order($a['available']) - $order($b['available']);
});

echo json_encode([
    'success' => true,
    'domain' => $domainName,
    'checked' => count($results),
    'results' => $results,
], JSON_UNESCAPED_UNICODE);
