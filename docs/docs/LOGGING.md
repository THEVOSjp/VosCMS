# RezlyX 로깅 시스템

> **Version**: 1.0.0
> **Last Updated**: 2026-03-02

---

## 1. 개요

RezlyX 로깅 시스템은 애플리케이션의 이벤트, 오류, 성능 정보를 기록하고 관리합니다.

### 1.1 주요 기능

- 로그 레벨별 기록 (DEBUG, INFO, WARNING, ERROR, CRITICAL 등)
- 일별 로그 파일 자동 생성
- 로그 파일 자동 로테이션 (크기 제한)
- 오래된 로그 자동 정리
- 관리자 페이지에서 로그 조회/삭제

### 1.2 파일 구조

```
storage/logs/
├── rezlyx-2026-03-02.log    # 일별 로그 파일
├── rezlyx-2026-03-01.log
└── .gitkeep

rzxlib/Core/Helpers/
├── Logger.php               # Logger 클래스
└── functions.php            # 로그 헬퍼 함수
```

---

## 2. 사용법

### 2.1 헬퍼 함수 사용 (권장)

```php
// 디버그 로그
log_debug('User logged in', ['user_id' => 123]);

// 정보 로그
log_info('Order created', ['order_id' => 456]);

// 경고 로그
log_warning('Low stock alert', ['product_id' => 789]);

// 에러 로그
log_error('Payment failed', ['reason' => 'timeout']);

// 예외 로그
try {
    // 작업 수행
} catch (Exception $e) {
    log_exception($e, ['context' => 'payment']);
}
```

### 2.2 Logger 클래스 직접 사용

```php
use RzxLib\Core\Helpers\Logger;

// 인스턴스 가져오기
$logger = Logger::getInstance();
// 또는
$logger = rzx_logger();

// 로그 기록
$logger->debug('Debug message');
$logger->info('Info message');
$logger->notice('Notice message');
$logger->warning('Warning message');
$logger->error('Error message');
$logger->critical('Critical message');
$logger->alert('Alert message');
$logger->emergency('Emergency message');

// 예외 로그
$logger->exception($exception);

// 요청 로그 (HTTP 정보 자동 포함)
$logger->request('API request received');

// 성능 측정
$start = microtime(true);
// ... 작업 수행 ...
$logger->performance('Database query', $start);

// SQL 쿼리 로그 (QUERY_LOG_ENABLED=true 필요)
$logger->query('SELECT * FROM users WHERE id = ?', [123], 0.05);
```

### 2.3 정적 메서드 사용

```php
use RzxLib\Core\Helpers\Logger;

Logger::logDebug('Debug message');
Logger::logInfo('Info message');
Logger::logWarning('Warning message');
Logger::logError('Error message');
Logger::logException($exception);
```

---

## 3. 로그 레벨

| 레벨 | 우선순위 | 설명 |
|------|---------|------|
| DEBUG | 100 | 상세 디버그 정보 |
| INFO | 200 | 일반 정보 메시지 |
| NOTICE | 250 | 중요 정보 |
| WARNING | 300 | 경고 (오류 아님) |
| ERROR | 400 | 런타임 에러 |
| CRITICAL | 500 | 치명적 에러 |
| ALERT | 550 | 즉각 조치 필요 |
| EMERGENCY | 600 | 시스템 사용 불가 |

---

## 4. 환경 설정

### 4.1 .env 설정

```env
# 로그 채널 (현재 daily 지원)
LOG_CHANNEL=daily

# 최소 로그 레벨 (이 레벨 이상만 기록)
LOG_LEVEL=debug

# 로그 보관 일수
LOG_DAYS=30

# SQL 쿼리 로그 활성화
QUERY_LOG_ENABLED=true
```

### 4.2 로그 레벨 설정

**개발 환경:**
```env
LOG_LEVEL=debug
```

**운영 환경:**
```env
LOG_LEVEL=error
```

---

## 5. 로그 파일 형식

### 5.1 파일명

```
rezlyx-YYYY-MM-DD.log
```

예: `rezlyx-2026-03-02.log`

### 5.2 로그 형식

```
[2026-03-02 14:30:15] local.ERROR: Payment failed {"reason":"timeout","user_id":123}
[2026-03-02 14:30:16] local.INFO: Order created {"order_id":456}
[2026-03-02 14:30:17] local.DEBUG: Query executed {"query":"SELECT * FROM users","time_ms":12.5}
```

형식: `[타임스탬프] 환경.레벨: 메시지 {컨텍스트}`

---

## 6. 관리자 페이지

### 6.1 로그 관리

**경로**: `/admin/settings/system/logs`

**기능**:
- 로그 파일 목록 조회
- 로그 파일 내용 보기 (최근 200줄)
- 개별 로그 파일 삭제
- 모든 로그 파일 삭제

### 6.2 스크린샷

```
┌─────────────────────────────────────────────────────────────────┐
│  📋 로그 관리                                                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ 파일명              │ 크기      │ 수정일        │ 액션      ││
│  ├─────────────────────┼───────────┼───────────────┼───────────┤│
│  │ rezlyx-2026-03-02   │ 125 KB    │ 03/02 14:30   │ [보기][삭제]│
│  │ rezlyx-2026-03-01   │ 89 KB     │ 03/01 23:59   │ [보기][삭제]│
│  └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│  [모든 로그 삭제]                                                │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. 모범 사례

### 7.1 적절한 로그 레벨 사용

```php
// DEBUG: 개발 중 디버깅 정보
log_debug('Variable value', ['var' => $value]);

// INFO: 일반적인 애플리케이션 이벤트
log_info('User registered', ['user_id' => $userId]);

// WARNING: 잠재적 문제 (정상 동작 계속)
log_warning('API rate limit approaching', ['usage' => '80%']);

// ERROR: 오류 발생 (기능 실패)
log_error('Database connection failed', ['host' => $host]);

// CRITICAL: 심각한 오류 (서비스 영향)
log_critical('Payment gateway unavailable');
```

### 7.2 컨텍스트 정보 포함

```php
// 좋은 예
log_error('Order creation failed', [
    'user_id' => $userId,
    'product_ids' => $productIds,
    'total_amount' => $totalAmount,
    'error_code' => $errorCode,
]);

// 나쁜 예
log_error('Order failed');
```

### 7.3 민감한 정보 제외

```php
// 좋은 예
log_info('User login', ['user_id' => $userId, 'email' => '***@***.com']);

// 나쁜 예 (비밀번호, 카드번호 등 포함하지 말 것)
log_info('User login', ['password' => $password]);
```

### 7.4 성능 측정 로그

```php
$startTime = microtime(true);

// 무거운 작업 수행
$result = $this->heavyOperation();

// 성능 로그
rzx_logger()->performance('Heavy operation', $startTime, [
    'records_processed' => count($result),
]);
```

---

## 8. 오류 처리 예시

### 8.1 try-catch 블록

```php
try {
    $result = $paymentGateway->process($order);
    log_info('Payment processed', ['order_id' => $order->id]);
} catch (PaymentException $e) {
    log_exception($e, [
        'order_id' => $order->id,
        'amount' => $order->total,
    ]);
    // 사용자에게 오류 메시지 표시
}
```

### 8.2 전역 예외 핸들러

```php
set_exception_handler(function (Throwable $e) {
    log_exception($e);

    if (env('APP_DEBUG', false)) {
        throw $e;
    }

    // 사용자 친화적 오류 페이지 표시
    include BASE_PATH . '/resources/views/errors/500.php';
});
```

---

## 9. 로그 로테이션

### 9.1 자동 로테이션

Logger는 다음 조건에서 자동으로 로그 파일을 로테이션합니다:

- 파일 크기가 10MB 초과 시
- 새 파일명: `rezlyx-2026-03-02.log.1`, `.log.2` 등

### 9.2 자동 정리

- `LOG_DAYS` 환경변수로 설정된 일수보다 오래된 로그는 자동 삭제
- 기본값: 30일
- 약 1% 확률로 정리 작업 실행 (성능 최적화)

---

## 10. 버전 정보

| 버전 | 날짜 | 변경 내용 |
|------|------|----------|
| 1.0.0 | 2026-03-02 | 초기 버전 |
