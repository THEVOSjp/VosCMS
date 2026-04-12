# NameSilo API 연동 가이드

VosCMS 서비스 신청 페이지에서 도메인 검색/등록/관리를 자동화하기 위한 문서.

---

## 기본 정보

| 항목 | 내용 |
|------|------|
| 공식 사이트 | https://www.namesilo.com |
| API 레퍼런스 | https://www.namesilo.com/api-reference |
| API Manager | https://www.namesilo.com/support/v2/articles/account-options/api-manager |
| API 비용 | **무료** (호출 수 제한 없음, 과금 없음) |
| 인증 | API Key (Account → API Manager에서 발급) |
| 응답 형식 | XML (기본) 또는 JSON |
| 샌드박스 | 있음 (support@namesilo.com에 요청) |
| IP 제한 | API Manager에서 최대 5개 IP 등록 가능 |
| PHP SDK | https://github.com/sed-seyedi/namesilo-php-sdk |

---

## API 키 발급

1. https://www.namesilo.com 로그인
2. **Account** → **API Manager** 이동
3. API 설정 폼 작성 (IP 제한 등)
4. Submit → API 키 표시 (1회만 표시되므로 반드시 저장)

---

## 리셀러 / 할인 프로그램

- **별도 신청 불필요** — Account에서 Discount Program 토글 ON
- 최소 도메인 수 요구 없음 (1개부터 할인가 적용)
- .com 기준 약 $10~11/년 (리셀러가)
- NS Rewards 포인트 프로그램 (구매 시 적립 → 다음 구매 할인)

---

## 주요 API 엔드포인트

### Base URL

```
https://www.namesilo.com/api/{operation}?version=1&type=xml&key={API_KEY}
```

type=json 으로 변경하면 JSON 응답.

---

### 1. 도메인 검색 (등록 가능 여부)

```
GET /api/checkRegisterAvailability
```

| 파라미터 | 필수 | 설명 |
|---------|------|------|
| domains | O | 쉼표 구분 도메인 목록 (최대 200개) |

```
GET /api/checkRegisterAvailability?version=1&type=xml&key=API_KEY&domains=example.com,example.net
```

**응답 예시:**
```xml
<namesilo>
  <request><operation>checkRegisterAvailability</operation><ip>1.2.3.4</ip></request>
  <reply>
    <code>300</code>
    <detail>success</detail>
    <available>
      <domain price="10.79">example123456.com</domain>
    </available>
    <unavailable>
      <domain>example.com</domain>
    </unavailable>
  </reply>
</namesilo>
```

---

### 2. 도메인 등록

```
GET /api/registerDomain
```

| 파라미터 | 필수 | 설명 |
|---------|------|------|
| domain | O | 등록할 도메인 (예: example.com) |
| years | O | 등록 기간 (1~10년) |
| private | - | WHOIS 프라이버시 (1=활성, 기본 무료) |
| auto_renew | - | 자동 갱신 (1=활성) |
| ns1, ns2 | - | 네임서버 (미지정 시 NameSilo 기본) |
| fn, ln, ad, cy, st, zp, ct, em, ph | - | 등록자 정보 (WHOIS) |

```
GET /api/registerDomain?version=1&type=xml&key=API_KEY
    &domain=example.com&years=1&private=1&auto_renew=1
    &ns1=ns1.example.com&ns2=ns2.example.com
```

**응답 코드:**
- `300` — 성공
- `261` — 잔액 부족
- `262` — 도메인 이미 등록됨

---

### 3. 도메인 갱신

```
GET /api/renewDomain
```

| 파라미터 | 필수 | 설명 |
|---------|------|------|
| domain | O | 갱신할 도메인 |
| years | O | 갱신 기간 (1~10년) |

---

### 4. DNS 레코드 조회

```
GET /api/dnsListRecords
```

| 파라미터 | 필수 | 설명 |
|---------|------|------|
| domain | O | 도메인명 |

**응답:** A, AAAA, CNAME, MX, TXT 등 모든 DNS 레코드 목록

---

### 5. DNS 레코드 추가

```
GET /api/dnsAddRecord
```

| 파라미터 | 필수 | 설명 |
|---------|------|------|
| domain | O | 도메인명 |
| rrtype | O | 레코드 타입 (A, AAAA, CNAME, MX, TXT) |
| rrhost | O | 호스트명 (서브도메인, @=루트) |
| rrvalue | O | 값 (IP, 도메인 등) |
| rrdistance | - | MX 우선순위 |
| rrttl | - | TTL (기본 7207) |

```
GET /api/dnsAddRecord?version=1&type=xml&key=API_KEY
    &domain=example.com&rrtype=A&rrhost=@&rrvalue=123.456.789.0&rrttl=3600
```

---

### 6. DNS 레코드 수정

```
GET /api/dnsUpdateRecord
```

| 파라미터 | 필수 | 설명 |
|---------|------|------|
| domain | O | 도메인명 |
| rrid | O | 레코드 ID (dnsListRecords에서 조회) |
| rrhost | O | 호스트명 |
| rrvalue | O | 새 값 |
| rrttl | - | TTL |

---

### 7. DNS 레코드 삭제

```
GET /api/dnsDeleteRecord
```

| 파라미터 | 필수 | 설명 |
|---------|------|------|
| domain | O | 도메인명 |
| rrid | O | 레코드 ID |

---

### 8. 네임서버 변경

```
GET /api/changeNameServers
```

| 파라미터 | 필수 | 설명 |
|---------|------|------|
| domain | O | 도메인명 |
| ns1, ns2 | O | 네임서버 (최대 13개까지) |

---

### 9. 도메인 목록 조회

```
GET /api/listDomains
```

계정에 등록된 전체 도메인 목록 반환.

---

### 10. 도메인 정보 조회

```
GET /api/getDomainInfo
```

| 파라미터 | 필수 | 설명 |
|---------|------|------|
| domain | O | 도메인명 |

만료일, 네임서버, 잠금 상태, 자동갱신 여부 등 상세 정보.

---

### 11. 도메인 이전 (Transfer)

```
GET /api/transferDomain
```

| 파라미터 | 필수 | 설명 |
|---------|------|------|
| domain | O | 이전할 도메인 |
| auth | O | 인증 코드 (EPP Code) |
| private | - | WHOIS 프라이버시 |
| auto_renew | - | 자동 갱신 |

---

### 12. 가격 조회

```
GET /api/getPrices
```

모든 TLD의 등록/갱신/이전 가격 목록 반환.

---

## 응답 코드

| 코드 | 의미 |
|------|------|
| 300 | 성공 |
| 301 | 성공 (추가 정보 있음) |
| 302 | 처리 중 |
| 110 | 잘못된 API 키 |
| 200 | 잘못된 도메인 형식 |
| 250 | 도메인을 찾을 수 없음 |
| 261 | 잔액 부족 |
| 262 | 도메인 이미 등록됨 |
| 280 | DNS 오류 |

---

## VosCMS 연동 계획

### PHP 구현 예시

```php
class NameSiloAPI {
    private string $apiKey;
    private string $baseUrl = 'https://www.namesilo.com/api/';

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    // 도메인 검색
    public function checkAvailability(array $domains): array {
        $response = $this->call('checkRegisterAvailability', [
            'domains' => implode(',', $domains)
        ]);
        return [
            'available' => $response['reply']['available'] ?? [],
            'unavailable' => $response['reply']['unavailable'] ?? [],
        ];
    }

    // 도메인 등록
    public function register(string $domain, int $years = 1, array $options = []): array {
        return $this->call('registerDomain', array_merge([
            'domain' => $domain,
            'years' => $years,
            'private' => 1,
            'auto_renew' => 1,
        ], $options));
    }

    // DNS 설정 (호스팅 서버 연결)
    public function setDNS(string $domain, string $serverIP): bool {
        // 기존 레코드 삭제
        $records = $this->call('dnsListRecords', ['domain' => $domain]);
        foreach ($records['reply']['resource_record'] ?? [] as $rr) {
            if ($rr['type'] === 'A') {
                $this->call('dnsDeleteRecord', ['domain' => $domain, 'rrid' => $rr['record_id']]);
            }
        }
        // A 레코드 추가
        $this->call('dnsAddRecord', [
            'domain' => $domain, 'rrtype' => 'A',
            'rrhost' => '@', 'rrvalue' => $serverIP, 'rrttl' => 3600
        ]);
        // www CNAME
        $this->call('dnsAddRecord', [
            'domain' => $domain, 'rrtype' => 'CNAME',
            'rrhost' => 'www', 'rrvalue' => $domain, 'rrttl' => 3600
        ]);
        return true;
    }

    private function call(string $operation, array $params = []): array {
        $url = $this->baseUrl . $operation . '?' . http_build_query(array_merge([
            'version' => 1, 'type' => 'xml', 'key' => $this->apiKey
        ], $params));
        $xml = simplexml_load_string(file_get_contents($url));
        return json_decode(json_encode($xml), true);
    }
}
```

### 자동화 플로우

```
1. 고객 도메인 검색
   → checkRegisterAvailability API
   → 결과: 등록 가능/불가 + 가격

2. 고객 결제 완료
   → registerDomain API (즉시 등록)
   → 계정 잔액에서 차감

3. DNS 자동 설정
   → changeNameServers (NameSilo 기본 NS 사용)
   → dnsAddRecord (A 레코드 → 호스팅 서버 IP)

4. SSL 자동 발급
   → Let's Encrypt certbot (서버 측)

5. VosCMS 자동 설치
   → nginx vhost 생성 → install.php 실행
```

### .env 설정

```
NAMESILO_API_KEY=your_api_key_here
NAMESILO_SANDBOX=false
NAMESILO_DEFAULT_NS1=ns1.yourdns.com
NAMESILO_DEFAULT_NS2=ns2.yourdns.com
```

---

## 지원 TLD (주요)

| TLD | 등록 가격 (약) | 갱신 가격 (약) |
|-----|-------------|-------------|
| .com | $10.79 | $10.79 |
| .net | $12.79 | $12.79 |
| .org | $12.79 | $12.79 |
| .io | $39.99 | $39.99 |
| .co | $29.99 | $29.99 |
| .dev | $15.99 | $15.99 |
| .jp | 지원 (가격 변동) | - |

> .co.kr / .kr 한국 도메인은 NameSilo 미지원 → 호스팅케이알 등 국내 공인 등록대행자 별도 연동 필요.

---

## 주의사항

1. **잔액 선충전** — 도메인 등록 시 계정 잔액에서 차감 (PayPal/카드로 충전)
2. **WHOIS Privacy** — NameSilo는 무료 제공 (private=1)
3. **API Rate Limit** — 공식 제한 없으나 과도한 호출 시 제한 가능
4. **한국 도메인** — .co.kr/.kr은 KISA 공인 등록대행자만 가능 (별도 연동)
5. **샌드박스** — 실제 등록 전 반드시 샌드박스 테스트 권장

---

## 참고 링크

- [NameSilo API Reference](https://www.namesilo.com/api-reference)
- [API Manager 설정](https://www.namesilo.com/support/v2/articles/account-options/api-manager)
- [리셀러 FAQ](https://www.namesilo.com/support/v2/articles/domain-manager/reseller-frequently-asked)
- [리셀러 옵션](https://www.namesilo.com/support/v2/articles/about/reseller-options)
- [PHP SDK (GitHub)](https://github.com/sed-seyedi/namesilo-php-sdk)
- [API 기반 도메인 관리 가이드](https://www.namesilo.com/blog/en/automation/domain-name-api-automating-registration-and-dns-management)
- [도메인 리셀러 가이드 2026](https://www.namesilo.com/blog/en/domain-investing/how-to-become-a-domain-reseller-in-2026-api-integration-and-margins)
