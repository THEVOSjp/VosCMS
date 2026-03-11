# RezlyX Core 라이브러리 문서

이 문서는 RezlyX 프레임워크의 핵심 라이브러리인 **Database**, **Auth**, **Validation**에 대한 상세 가이드입니다.

---

## 목차

1. [Database (데이터베이스)](#1-database-데이터베이스)
2. [Auth (인증)](#2-auth-인증)
3. [Validation (유효성 검사)](#3-validation-유효성-검사)
4. [Notification (알림 시스템)](#4-notification-알림-시스템)
5. [Skin (스킨 시스템)](#5-skin-스킨-시스템)
6. [헬퍼 함수](#6-헬퍼-함수)

---

## 1. Database (데이터베이스)

데이터베이스 라이브러리는 PDO를 기반으로 하며, 안전하고 편리한 데이터베이스 작업을 지원합니다.

### 1.1 파일 구조

```
rzxlib/Core/Database/
├── Connection.php      # PDO 연결 래퍼
├── QueryBuilder.php    # SQL 쿼리 빌더
└── DatabaseManager.php # 다중 연결 매니저
```

### 1.2 기본 사용법

#### 데이터베이스 연결

```php
// 헬퍼 함수 사용 (권장)
$db = db();

// 또는 앱 컨테이너에서 직접 가져오기
$db = app('db');

// 특정 연결 사용
$mysqlDb = db('mysql');
$sqliteDb = db('sqlite');
```

#### 데이터 조회 (SELECT)

```php
// 모든 레코드 조회
$users = db()->table('users')->get();

// 조건부 조회
$activeUsers = db()->table('users')
    ->where('status', 'active')
    ->get();

// 단일 레코드 조회
$user = db()->table('users')
    ->where('id', 1)
    ->first();

// ID로 조회 (단축 메서드)
$user = db()->table('users')->find(1);

// 특정 컬럼만 조회
$emails = db()->table('users')
    ->select('email', 'name')
    ->get();

// 단일 값 조회
$email = db()->table('users')
    ->where('id', 1)
    ->value('email');

// 컬럼 값 배열로 조회
$emails = db()->table('users')->pluck('email');

// key => value 형태로 조회
$users = db()->table('users')->pluck('name', 'id');
// 결과: [1 => '홍길동', 2 => '김철수', ...]
```

#### WHERE 조건

```php
// 기본 조건
->where('status', 'active')           // status = 'active'
->where('age', '>', 18)               // age > 18
->where('name', 'LIKE', '%홍%')       // name LIKE '%홍%'

// 배열로 다중 조건
->where([
    'status' => 'active',
    'role' => 'admin'
])

// OR 조건
->where('status', 'active')
->orWhere('role', 'admin')

// IN 조건
->whereIn('status', ['active', 'pending'])
->whereNotIn('role', ['guest', 'banned'])

// NULL 조건
->whereNull('deleted_at')
->whereNotNull('email_verified_at')

// BETWEEN 조건
->whereBetween('age', [18, 65])

// LIKE 조건
->whereLike('name', '%홍%')
```

#### 정렬 및 제한

```php
// 정렬
->orderBy('created_at', 'DESC')
->orderByDesc('created_at')  // DESC 단축 메서드
->latest()                   // created_at DESC
->oldest()                   // created_at ASC

// 개수 제한
->limit(10)
->take(10)  // limit의 별칭

// 건너뛰기
->offset(20)
->skip(20)  // offset의 별칭

// 페이지네이션
->forPage(2, 15)  // 2페이지, 페이지당 15개
```

#### JOIN

```php
// INNER JOIN
db()->table('reservations')
    ->join('users', 'reservations.user_id', '=', 'users.id')
    ->select('reservations.*', 'users.name as user_name')
    ->get();

// LEFT JOIN
db()->table('reservations')
    ->leftJoin('services', 'reservations.service_id', '=', 'services.id')
    ->get();

// RIGHT JOIN
->rightJoin('table', 'column1', '=', 'column2')
```

#### 그룹화 및 집계

```php
// GROUP BY
db()->table('reservations')
    ->select('service_id', 'COUNT(*) as count')
    ->groupBy('service_id')
    ->get();

// HAVING
db()->table('reservations')
    ->select('service_id', 'COUNT(*) as count')
    ->groupBy('service_id')
    ->having('count', '>', 5)
    ->get();

// 집계 함수
$count = db()->table('users')->count();
$max = db()->table('products')->max('price');
$min = db()->table('products')->min('price');
$avg = db()->table('products')->avg('price');
$sum = db()->table('orders')->sum('total');

// 존재 여부 확인
$exists = db()->table('users')
    ->where('email', 'test@example.com')
    ->exists();
```

#### 데이터 삽입 (INSERT)

```php
// 단일 레코드 삽입
db()->table('users')->insert([
    'name' => '홍길동',
    'email' => 'hong@example.com',
    'password' => bcrypt('password123'),
    'created_at' => date('Y-m-d H:i:s'),
]);

// 삽입 후 ID 반환
$userId = db()->table('users')->insertGetId([
    'name' => '홍길동',
    'email' => 'hong@example.com',
]);

// 다중 레코드 삽입
db()->table('users')->insert([
    ['name' => '홍길동', 'email' => 'hong@example.com'],
    ['name' => '김철수', 'email' => 'kim@example.com'],
    ['name' => '이영희', 'email' => 'lee@example.com'],
]);
```

#### 데이터 수정 (UPDATE)

```php
// 조건부 업데이트
$affected = db()->table('users')
    ->where('id', 1)
    ->update([
        'name' => '홍길동 (수정)',
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

// 다중 조건 업데이트
db()->table('users')
    ->where('status', 'pending')
    ->where('created_at', '<', '2024-01-01')
    ->update(['status' => 'expired']);
```

#### 데이터 삭제 (DELETE)

```php
// 조건부 삭제
$deleted = db()->table('users')
    ->where('id', 1)
    ->delete();

// 다중 조건 삭제
db()->table('sessions')
    ->where('expires_at', '<', date('Y-m-d H:i:s'))
    ->delete();
```

### 1.3 트랜잭션

데이터 무결성을 보장하기 위한 트랜잭션 지원:

```php
// 방법 1: 수동 트랜잭션
db()->beginTransaction();

try {
    db()->table('accounts')
        ->where('id', 1)
        ->update(['balance' => 900]);

    db()->table('accounts')
        ->where('id', 2)
        ->update(['balance' => 1100]);

    db()->commit();
} catch (Exception $e) {
    db()->rollBack();
    throw $e;
}

// 방법 2: 콜백 트랜잭션 (권장)
db()->transaction(function ($db) {
    $db->table('accounts')
        ->where('id', 1)
        ->update(['balance' => 900]);

    $db->table('accounts')
        ->where('id', 2)
        ->update(['balance' => 1100]);
});
// 예외 발생 시 자동 롤백
```

### 1.4 원시 쿼리

복잡한 쿼리가 필요한 경우:

```php
// SELECT
$users = db()->select(
    "SELECT * FROM users WHERE status = ? AND age > ?",
    ['active', 18]
);

// 단일 레코드
$user = db()->selectOne(
    "SELECT * FROM users WHERE id = ?",
    [1]
);

// INSERT
db()->insert(
    "INSERT INTO users (name, email) VALUES (?, ?)",
    ['홍길동', 'hong@example.com']
);

// UPDATE
$affected = db()->update(
    "UPDATE users SET status = ? WHERE id = ?",
    ['inactive', 1]
);

// DELETE
$deleted = db()->delete(
    "DELETE FROM users WHERE id = ?",
    [1]
);

// 일반 쿼리
db()->statement("TRUNCATE TABLE logs");
```

### 1.5 쿼리 로깅

개발 중 쿼리 디버깅:

```php
// 로깅 활성화
db()->connection()->enableQueryLog();

// 쿼리 실행
db()->table('users')->get();
db()->table('posts')->where('user_id', 1)->get();

// 로그 확인
$queries = db()->connection()->getQueryLog();

foreach ($queries as $query) {
    echo "SQL: {$query['query']}\n";
    echo "바인딩: " . implode(', ', $query['bindings']) . "\n";
    echo "실행 시간: {$query['time']}ms\n";
    echo "---\n";
}

// 로깅 비활성화
db()->connection()->disableQueryLog();
```

---

## 2. Auth (인증)

인증 라이브러리는 사용자 인증, 세션 관리, JWT 토큰 등을 지원합니다.

### 2.1 파일 구조

```
rzxlib/Core/Auth/
├── AuthManager.php           # 인증 매니저
├── GuardInterface.php        # 가드 인터페이스
├── UserProviderInterface.php # 사용자 제공자 인터페이스
├── DatabaseUserProvider.php  # DB 기반 사용자 제공자
├── SessionGuard.php          # 세션 기반 인증 (웹)
├── JwtGuard.php             # JWT 기반 인증 (API)
└── PasswordHasher.php       # 비밀번호 해싱
```

### 2.2 기본 사용법

#### 인증 확인

```php
// 로그인 여부 확인
if (auth()->check()) {
    echo "로그인된 사용자입니다.";
}

// 게스트 여부 확인
if (auth()->guest()) {
    echo "로그인이 필요합니다.";
}

// 현재 사용자 정보
$user = auth()->user();
echo "안녕하세요, {$user['name']}님!";

// 현재 사용자 ID
$userId = auth()->id();
```

#### 로그인

```php
// 자격 증명으로 로그인 시도
$credentials = [
    'email' => $_POST['email'],
    'password' => $_POST['password'],
];

if (auth()->attempt($credentials)) {
    // 로그인 성공
    redirect('/dashboard');
} else {
    // 로그인 실패
    $error = '이메일 또는 비밀번호가 올바르지 않습니다.';
}

// "로그인 상태 유지" 옵션
$remember = isset($_POST['remember']);
auth()->attempt($credentials, $remember);

// ID로 직접 로그인
auth()->loginUsingId(1);

// 사용자 배열로 로그인
$user = db()->table('users')->find(1);
auth()->login($user);

// 자격 증명 검증만 (로그인 없이)
if (auth()->validate($credentials)) {
    echo "자격 증명이 유효합니다.";
}
```

#### 로그아웃

```php
auth()->logout();
redirect('/login');
```

#### 가드 사용

여러 인증 유형을 지원합니다:

```php
// 웹 가드 (기본값, 세션 기반)
auth('web')->check();

// 관리자 가드
auth('admin')->attempt([
    'email' => $email,
    'password' => $password,
]);

// API 가드 (JWT 기반)
$user = auth('api')->user();
```

### 2.3 비밀번호 관리

#### 비밀번호 해싱

```php
use RzxLib\Core\Auth\PasswordHasher;

// 비밀번호 해시 생성
$hash = PasswordHasher::hash('my_password');
// 또는 헬퍼 함수 사용
$hash = bcrypt('my_password');

// 비밀번호 검증
$isValid = PasswordHasher::verify('my_password', $hash);
// 또는 헬퍼 함수 사용
$isValid = password_check('my_password', $hash);

// 재해싱 필요 여부 확인 (보안 업그레이드 시)
if (PasswordHasher::needsRehash($hash)) {
    $newHash = PasswordHasher::hash('my_password');
    // 새 해시로 업데이트
}
```

#### 비밀번호 강도 검사

```php
$result = PasswordHasher::checkStrength('MyP@ssw0rd');

// 결과 예시:
// [
//     'valid' => true,
//     'errors' => [],
//     'strength' => 5,
//     'level' => 'strong'
// ]

// 약한 비밀번호 예시
$result = PasswordHasher::checkStrength('1234');
// [
//     'valid' => false,
//     'errors' => [
//         '비밀번호는 최소 8자 이상이어야 합니다.',
//         '대문자를 포함해야 합니다.',
//         '소문자를 포함해야 합니다.',
//         '특수문자를 포함해야 합니다.'
//     ],
//     'strength' => 1,
//     'level' => 'weak'
// ]
```

#### 임시 비밀번호 생성

```php
// 12자리 임시 비밀번호 생성
$tempPassword = PasswordHasher::generateTemporary();
// 예: "aB3$xK9@mN2!"

// 길이 지정
$tempPassword = PasswordHasher::generateTemporary(16);
```

### 2.4 사용자 관리 (DatabaseUserProvider)

```php
$provider = auth()->guard()->getProvider();

// 사용자 생성
$userId = $provider->create([
    'name' => '홍길동',
    'email' => 'hong@example.com',
    'password' => 'password123',  // 자동으로 해시됨
    'phone' => '010-1234-5678',
]);

// 사용자 조회
$user = $provider->retrieveById($userId);
$user = $provider->findByEmail('hong@example.com');

// 사용자 수정
$provider->update($userId, [
    'name' => '홍길동 (수정)',
    'phone' => '010-9876-5432',
]);

// 비밀번호 변경
$provider->update($userId, [
    'password' => 'new_password123',  // 자동으로 해시됨
]);

// 이메일 중복 확인
if ($provider->emailExists('hong@example.com')) {
    $error = '이미 사용 중인 이메일입니다.';
}

// 특정 ID 제외하고 중복 확인 (수정 시)
if ($provider->emailExists('hong@example.com', excludeId: $userId)) {
    $error = '이미 사용 중인 이메일입니다.';
}
```

### 2.5 비밀번호 재설정

#### Auth 클래스를 통한 비밀번호 재설정

```php
use RzxLib\Core\Auth\Auth;

// 1. 비밀번호 재설정 이메일 발송 (토큰 자동 생성)
$result = Auth::sendPasswordResetEmail('hong@example.com');
// 반환값: ['success' => bool, 'error' => string|null, 'debug_link' => string|null]

// 개발 환경(APP_DEBUG=true, APP_ENV=local)에서는 이메일 발송 대신 debug_link 반환
if ($result['debug_link']) {
    // 테스트용 링크 직접 표시 가능
}

// 2. 토큰 검증 (reset-password 페이지에서)
$token = $_GET['token'] ?? '';
$user = Auth::verifyPasswordResetToken($token);

if ($user) {
    // 유효한 토큰 - 비밀번호 변경 폼 표시
}

// 3. 새 비밀번호로 변경
$result = Auth::resetPassword($token, $_POST['new_password']);

if ($result['success']) {
    // 비밀번호 변경 완료
}
```

#### 기존 방식 (Provider 직접 사용)

```php
$provider = auth()->guard()->getProvider();

// 1. 재설정 토큰 생성
$token = $provider->createPasswordResetToken('hong@example.com');

if ($token) {
    // 이메일로 토큰 전송
    // 링크 예: /reset-password?email=hong@example.com&token={$token}
}

// 2. 토큰 검증
$isValid = $provider->validatePasswordResetToken(
    'hong@example.com',
    $_GET['token']
);

if ($isValid) {
    // 3. 비밀번호 변경
    $user = $provider->findByEmail('hong@example.com');
    $provider->update($user['id'], [
        'password' => $_POST['new_password'],
    ]);

    // 4. 토큰 삭제
    $provider->deletePasswordResetToken('hong@example.com');
}
```

### 2.6 메일 발송 설정

비밀번호 재설정 이메일은 관리자 설정에서 구성된 메일 설정을 자동으로 적용합니다.

#### 관리자 메일 설정 (Admin > 일반 설정)

| 설정 | DB 키 | 설명 |
|------|-------|------|
| 발신자 이름 | `mail_from_name` | 수신자에게 표시되는 이름 |
| 발신자 이메일 | `mail_from_email` | From 주소 |
| Reply-To 주소 | `mail_reply_to` | 답장 시 사용되는 주소 |
| 발송 방법 | `mail_driver` | `mail` (PHP mail) 또는 `smtp` |
| SMTP 호스트 | `smtp_host` | SMTP 서버 주소 |
| SMTP 포트 | `smtp_port` | 포트 번호 (기본: 587) |
| SMTP 암호화 | `smtp_encryption` | `tls`, `ssl`, `none` |
| SMTP 사용자명 | `smtp_username` | 인증 사용자명 |
| SMTP 비밀번호 | `smtp_password` | 인증 비밀번호 |

#### 발송 방법

1. **PHP mail()** (기본): 서버의 sendmail 설정에 의존
2. **SMTP**: 외부 메일 서버 사용 (Gmail, AWS SES 등)

```php
// Auth 클래스에서 메일 설정 자동 적용
// - rzx_settings 테이블에서 메일 설정 조회
// - mail_driver에 따라 PHP mail() 또는 SMTP 사용
$result = Auth::sendPasswordResetEmail($email);
```

#### 개발 환경 (DEV MODE)

`.env` 설정:
```env
APP_DEBUG=true
APP_ENV=local
```

이 환경에서는 실제 이메일을 발송하지 않고 콘솔에 로그를 남기며,
`debug_link`를 반환하여 화면에 테스트 링크를 표시할 수 있습니다.

### 2.7 JWT 인증 (API)

API 인증을 위한 JWT 토큰 사용:

```php
// JWT 가드 사용
$jwt = auth('api');

// 로그인하여 토큰 발급
if ($jwt->attempt($credentials)) {
    $token = $jwt->getToken();

    // JSON 응답
    echo json_encode([
        'access_token' => $token,
        'token_type' => 'Bearer',
    ]);
}

// 토큰으로 인증된 요청 처리
// 헤더: Authorization: Bearer {token}
$user = auth('api')->user();

// 토큰 갱신
$newToken = $jwt->refresh();

// 페이로드 확인
$payload = $jwt->getPayload();
// [
//     'iss' => 'https://example.com',
//     'iat' => 1234567890,
//     'exp' => 1234571490,
//     'sub' => 1,  // 사용자 ID
//     'jti' => 'unique-token-id'
// ]
```

### 2.7 인증 미들웨어 예시

```php
// 페이지 보호
function requireAuth(): void
{
    if (auth()->guest()) {
        // 로그인 페이지로 리다이렉트
        redirect('/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

// 관리자 전용
function requireAdmin(): void
{
    requireAuth();

    $user = auth()->user();
    if ($user['role'] !== 'admin') {
        abort(403, '접근 권한이 없습니다.');
    }
}

// 사용 예시
requireAuth();
$user = auth()->user();
echo "환영합니다, {$user['name']}님!";
```

---

## 3. Validation (유효성 검사)

유효성 검사 라이브러리는 입력 데이터의 검증을 쉽고 안전하게 수행합니다.

### 3.1 파일 구조

```
rzxlib/Core/Validation/
├── Validator.php           # 검증기 클래스
├── Factory.php            # Validator 팩토리
├── ValidationException.php # 검증 예외
└── Rules.php              # 공통 규칙 헬퍼
```

### 3.2 기본 사용법

#### 간단한 검증

```php
// 헬퍼 함수 사용 (권장)
try {
    $validated = validate($_POST, [
        'name' => 'required|string|max:50',
        'email' => 'required|email',
        'age' => 'required|integer|min:1|max:150',
    ]);

    // 검증 통과, $validated에 검증된 데이터만 포함
    db()->table('users')->insert($validated);

} catch (ValidationException $e) {
    // 검증 실패
    $errors = $e->errors();
}
```

#### Validator 인스턴스 사용

```php
$validator = validator($_POST, [
    'name' => 'required|string|max:50',
    'email' => 'required|email',
]);

if ($validator->passes()) {
    // 검증 성공
    $data = $validator->validated();
} else {
    // 검증 실패
    $errors = $validator->errors();
}

// 또는
if ($validator->fails()) {
    $firstError = $validator->first();  // 첫 번째 오류 메시지
    $allErrors = $validator->all();     // 모든 오류 메시지 (배열)
}
```

### 3.3 검증 규칙

#### 필수/선택 규칙

| 규칙 | 설명 | 예시 |
|------|------|------|
| `required` | 필수 값 | `'name' => 'required'` |
| `nullable` | null 허용 (빈 값이면 검증 스킵) | `'bio' => 'nullable|string'` |
| `sometimes` | 필드 존재 시에만 검증 | `'nickname' => 'sometimes|string'` |

#### 타입 규칙

| 규칙 | 설명 | 예시 |
|------|------|------|
| `string` | 문자열 | `'name' => 'string'` |
| `integer` | 정수 | `'age' => 'integer'` |
| `numeric` | 숫자 (정수/소수) | `'price' => 'numeric'` |
| `boolean` | 불리언 (true/false/1/0) | `'active' => 'boolean'` |
| `array` | 배열 | `'items' => 'array'` |

#### 크기 규칙

| 규칙 | 설명 | 예시 |
|------|------|------|
| `min:값` | 최소값/길이 | `'password' => 'min:8'` |
| `max:값` | 최대값/길이 | `'name' => 'max:50'` |
| `between:최소,최대` | 범위 | `'age' => 'between:1,150'` |
| `digits:자릿수` | 정확한 자릿수 | `'pin' => 'digits:6'` |
| `digits_between:최소,최대` | 자릿수 범위 | `'code' => 'digits_between:4,8'` |

#### 형식 규칙

| 규칙 | 설명 | 예시 |
|------|------|------|
| `email` | 이메일 형식 | `'email' => 'email'` |
| `url` | URL 형식 | `'website' => 'url'` |
| `date` | 날짜 형식 | `'birthday' => 'date'` |
| `date_format:형식` | 특정 날짜 형식 | `'time' => 'date_format:H:i'` |
| `alpha` | 알파벳만 | `'code' => 'alpha'` |
| `alpha_num` | 알파벳+숫자 | `'username' => 'alpha_num'` |
| `alpha_dash` | 알파벳+숫자+대시+밑줄 | `'slug' => 'alpha_dash'` |
| `regex:패턴` | 정규식 | `'code' => 'regex:/^[A-Z]{3}[0-9]{4}$/'` |
| `phone` | 전화번호 | `'phone' => 'phone'` |
| `password` | 강력한 비밀번호 | `'password' => 'password'` |

#### 비교 규칙

| 규칙 | 설명 | 예시 |
|------|------|------|
| `confirmed` | 확인 필드와 일치 | `'password' => 'confirmed'` |
| `same:필드` | 다른 필드와 동일 | `'email2' => 'same:email'` |
| `different:필드` | 다른 필드와 다름 | `'new_pass' => 'different:old_pass'` |
| `in:값1,값2,...` | 목록 중 하나 | `'role' => 'in:admin,user,guest'` |
| `not_in:값1,값2,...` | 목록에 없음 | `'status' => 'not_in:banned,deleted'` |

### 3.4 사용자 정의 메시지

```php
$validator = validator($_POST, [
    'email' => 'required|email',
    'password' => 'required|min:8',
], [
    // 규칙별 메시지
    'required' => ':attribute은(는) 필수 입력 항목입니다.',
    'email' => '올바른 이메일 주소를 입력해주세요.',

    // 필드+규칙별 메시지
    'password.required' => '비밀번호를 입력해주세요.',
    'password.min' => '비밀번호는 최소 8자 이상이어야 합니다.',
]);
```

### 3.5 속성 별명

```php
$validator = validator($_POST, [
    'email' => 'required|email',
    'password' => 'required|min:8',
], [], [
    // 속성 별명 (오류 메시지에서 사용)
    'email' => '이메일',
    'password' => '비밀번호',
]);

// 오류 메시지: "이메일 필드는 유효한 이메일 주소여야 합니다."
```

### 3.6 Rules 헬퍼 클래스

자주 사용되는 규칙 조합을 간편하게 사용:

```php
use RzxLib\Core\Validation\Rules;

// 개별 규칙 사용
$rules = [
    'email' => Rules::email(),           // 'required|email|max:255'
    'password' => Rules::password(),     // 'required|string|min:8|password'
    'name' => Rules::name(),             // 'required|string|min:2|max:50'
    'phone' => Rules::phoneNullable(),   // 'nullable|phone'
];

// 사전 정의된 규칙 세트 사용
$rules = Rules::userRegistration();
// [
//     'name' => 'required|string|min:2|max:50',
//     'email' => 'required|email|max:255',
//     'password' => 'required|string|min:8|confirmed',
//     'phone' => 'nullable|phone',
// ]

$rules = Rules::userLogin();
// [
//     'email' => 'required|email|max:255',
//     'password' => 'required|string',
//     'remember' => 'nullable|boolean',
// ]

$rules = Rules::reservation();
// [
//     'service_id' => 'required|integer',
//     'date' => 'required|date_format:Y-m-d',
//     'time' => 'required|date_format:H:i',
//     'name' => 'required|string|min:2|max:50',
//     'email' => 'required|email|max:255',
//     'phone' => 'required|phone',
//     'memo' => 'nullable|string|min:0|max:500',
// ]
```

### 3.7 폼 검증 예시

#### 회원가입 폼

```php
// register.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $validated = validate($_POST, Rules::userRegistration(), [
            'email.required' => '이메일을 입력해주세요.',
            'email.email' => '올바른 이메일 형식이 아닙니다.',
            'password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
        ], [
            'name' => '이름',
            'email' => '이메일',
            'password' => '비밀번호',
            'phone' => '전화번호',
        ]);

        // 이메일 중복 확인
        $provider = auth()->guard()->getProvider();
        if ($provider->emailExists($validated['email'])) {
            throw ValidationException::withMessages([
                'email' => ['이미 사용 중인 이메일입니다.'],
            ]);
        }

        // 사용자 생성
        $userId = $provider->create($validated);

        // 자동 로그인
        auth()->loginUsingId($userId);

        redirect('/dashboard');

    } catch (ValidationException $e) {
        $errors = $e->errors();
    }
}
```

#### 예약 폼

```php
// booking.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $validated = validate($_POST, [
            'service_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
            'name' => Rules::name(),
            'email' => Rules::email(),
            'phone' => Rules::phone(),
            'memo' => 'nullable|string|max:500',
        ]);

        // 예약 생성
        $reservationId = db()->table('reservations')->insertGetId([
            ...$validated,
            'user_id' => auth()->id(),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        redirect("/reservations/{$reservationId}");

    } catch (ValidationException $e) {
        $errors = $e->errors();
    }
}
```

### 3.8 AJAX/API 검증

```php
// api/validate.php
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    $validated = validate($data, [
        'email' => 'required|email',
        'password' => 'required|min:8',
    ]);

    echo json_encode([
        'success' => true,
        'data' => $validated,
    ]);

} catch (ValidationException $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => '입력값 검증에 실패했습니다.',
        'errors' => $e->errors(),
    ]);
}
```

---

## 4. Notification (알림 시스템)

알림 라이브러리는 웹푸시 알림과 사용자 메시지 인박스를 지원합니다.

### 4.1 데이터베이스 테이블

```sql
-- 사용자 알림 인박스
CREATE TABLE rzx_user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,                -- NULL이면 게스트/익명 사용자
    message_id INT NULL,             -- rzx_push_messages 참조
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    url VARCHAR(500),
    icon VARCHAR(500),
    type ENUM('push', 'system', 'reservation', 'promotion') DEFAULT 'push',
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### 4.2 알림 조회

```php
// 사용자의 알림 목록 조회
$notifications = db()->table('rzx_user_notifications')
    ->where('user_id', auth()->id())
    ->orderBy('created_at', 'DESC')
    ->limit(20)
    ->get();

// 읽지 않은 알림 수
$unreadCount = db()->table('rzx_user_notifications')
    ->where('user_id', auth()->id())
    ->where('is_read', 0)
    ->count();
```

### 4.3 알림 상태 변경

```php
// 읽음 처리
db()->table('rzx_user_notifications')
    ->where('id', $notificationId)
    ->where('user_id', auth()->id())
    ->update([
        'is_read' => 1,
        'read_at' => date('Y-m-d H:i:s'),
    ]);

// 모두 읽음 처리
db()->table('rzx_user_notifications')
    ->where('user_id', auth()->id())
    ->where('is_read', 0)
    ->update([
        'is_read' => 1,
        'read_at' => date('Y-m-d H:i:s'),
    ]);

// 알림 삭제
db()->table('rzx_user_notifications')
    ->where('id', $notificationId)
    ->where('user_id', auth()->id())
    ->delete();
```

### 4.4 알림 생성 (관리자)

```php
// 특정 사용자에게 알림 저장
db()->table('rzx_user_notifications')->insert([
    'user_id' => $userId,
    'title' => '예약 확정 안내',
    'body' => '예약이 확정되었습니다.',
    'type' => 'reservation',
    'url' => '/mypage/reservations/' . $reservationId,
    'created_at' => date('Y-m-d H:i:s'),
]);

// 모든 사용자에게 알림 저장 (프로모션 등)
$users = db()->table('rzx_users')->pluck('id');
foreach ($users as $userId) {
    db()->table('rzx_user_notifications')->insert([
        'user_id' => $userId,
        'title' => '새해 프로모션',
        'body' => '새해 맞이 20% 할인 이벤트!',
        'type' => 'promotion',
        'url' => '/promotions/new-year',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}
```

### 4.5 알림 타입

| 타입 | 설명 |
|------|------|
| `push` | 웹푸시 알림 |
| `system` | 시스템 알림 |
| `reservation` | 예약 관련 알림 |
| `promotion` | 프로모션/이벤트 알림 |

---

## 5. Skin (스킨 시스템)

스킨 시스템은 회원 페이지의 디자인을 쉽게 변경할 수 있도록 지원합니다.

> 상세 문서: [MEMBER_SKIN.md](MEMBER_SKIN.md)

### 5.1 파일 구조

```
rzxlib/Core/Skin/
└── MemberSkinLoader.php    # 회원 스킨 로더

skins/member/
├── default/                # 기본 스킨
│   ├── config.php         # 스킨 설정
│   ├── login.php          # 로그인 템플릿
│   ├── register.php       # 회원가입 템플릿
│   ├── mypage.php         # 마이페이지 템플릿
│   └── components/        # 재사용 컴포넌트
└── modern/                 # 추가 스킨
```

### 5.2 기본 사용법

```php
use RezlyX\Core\Skin\MemberSkinLoader;

// 스킨 로더 초기화
$skinLoader = new MemberSkinLoader('/path/to/skins/member', 'default');

// 컬러셋 변경
$skinLoader->setColorset('dark');

// 페이지 렌더링
echo $skinLoader->render('login', [
    'errors' => [],
    'csrfToken' => $token,
]);
```

### 5.3 사용 가능한 스킨 조회

```php
$skins = $skinLoader->getAvailableSkins();
// [
//     'default' => [
//         'name' => 'Default',
//         'version' => '1.0.0',
//         'colorsets' => ['default', 'dark', 'blue'],
//     ],
// ]
```

### 5.4 컬러셋 시스템

각 스킨은 여러 컬러셋을 지원합니다:

```php
// config.php
'colorsets' => [
    'default' => [
        'primary' => '#3B82F6',
        'secondary' => '#6B7280',
        'background' => '#FFFFFF',
        'text' => '#1F2937',
    ],
    'dark' => [
        'primary' => '#60A5FA',
        'background' => '#1F2937',
        'text' => '#F9FAFB',
    ],
],
```

템플릿에서 CSS 변수로 사용:

```html
<style>
:root {
    --skin-primary: <?= $colorset['primary'] ?>;
    --skin-text: <?= $colorset['text'] ?>;
}
</style>
<button style="background: var(--skin-primary)">버튼</button>
```

---

## 6. 헬퍼 함수

편리한 사용을 위한 전역 헬퍼 함수들:

### 데이터베이스

```php
db()                    // DatabaseManager 반환
db('mysql')             // 특정 연결 반환
```

### 인증

```php
auth()                  // AuthManager 반환
auth('admin')           // 특정 가드 반환
bcrypt($password)       // 비밀번호 해시
password_check($pw, $hash)  // 비밀번호 검증
```

### 검증

```php
validate($data, $rules) // 검증 후 데이터 반환 (실패 시 예외)
validator($data, $rules) // Validator 인스턴스 반환
```

### 설정 및 환경

```php
config('app.name')      // 설정값 조회
env('DB_HOST', '127.0.0.1')  // 환경변수 조회
app()                   // Application 인스턴스
app('service')          // 서비스 컨테이너에서 조회
```

### 경로

```php
base_path('config')     // 프로젝트 루트 경로
storage_path('logs')    // storage 경로
public_path('assets')   // public 경로
resource_path('views')  // resources 경로
```

### URL 및 리다이렉트

```php
url('/users')           // URL 생성
asset('css/app.css')    // 에셋 URL 생성
redirect('/login')      // 리다이렉트
```

### 기타

```php
e($string)              // HTML 이스케이프
now()                   // 현재 시간 (DateTimeImmutable)
dd($var)                // 변수 덤프 후 종료
dump($var)              // 변수 덤프 (계속 실행)
```

### 이미지 처리 (ImageHelper)

`RzxLib\Core\Helpers\ImageHelper` 클래스는 이미지 업로드 및 처리를 위한 유틸리티입니다.

#### 기본 사용법

```php
use RzxLib\Core\Helpers\ImageHelper;

$imageHelper = new ImageHelper();

// Base64 이미지 저장
$result = $imageHelper->saveBase64Image($base64Data, 'filename', 'subdir');

// 프로필 이미지 저장 (회원가입/프로필 수정)
$result = $imageHelper->saveProfileImage($base64Data, $userId);

// 이미지 삭제
$deleted = $imageHelper->deleteImage('/uploads/profiles/image.jpg');

// 이미지 리사이즈
$resized = $imageHelper->resizeImage($sourcePath, 400, 400, $destPath);
```

#### 주요 메서드

| 메서드 | 설명 | 반환값 |
|--------|------|--------|
| `saveBase64Image($data, $filename, $subDir)` | Base64 이미지 저장 | `array` (success, path, relative_path 등) |
| `saveProfileImage($data, $userId)` | 프로필 이미지 저장 | `array` (success, path, relative_path 등) |
| `deleteImage($path)` | 이미지 파일 삭제 | `bool` |
| `resizeImage($source, $w, $h, $dest)` | 이미지 리사이즈 | `bool` |
| `setUploadPath($path)` | 업로드 경로 설정 | `self` |
| `setMaxFileSize($bytes)` | 최대 파일 크기 설정 | `self` |

#### 지원 이미지 형식

- JPEG (`image/jpeg`)
- PNG (`image/png`)
- GIF (`image/gif`)
- WebP (`image/webp`)

#### 기본 설정

- **기본 업로드 경로**: `BASE_PATH/uploads/profiles`
- **최대 파일 크기**: 5MB
- **프로필 이미지 저장 위치**: `uploads/profiles/profiles/`

---

## 버전 정보

- **문서 버전**: 1.3.0
- **최종 수정일**: 2026-03-05
- **적용 대상**: RezlyX Core Libraries

### 변경 이력

| 버전 | 날짜 | 변경 내용 |
|------|------|-----------|
| 1.3.0 | 2026-03-05 | ImageHelper 클래스 문서 추가 |
| 1.2.0 | 2026-03-03 | Skin 시스템 섹션 추가 (MemberSkinLoader) |
| 1.1.0 | 2026-03-02 | Notification 시스템 섹션 추가 |
| 1.0.0 | 2026-02-28 | 최초 작성 |
