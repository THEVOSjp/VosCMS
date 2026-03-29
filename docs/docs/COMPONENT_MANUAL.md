# RezlyX 컴포넌트 및 함수 매뉴얼

이 문서는 RezlyX에서 사용되는 모든 컴포넌트와 헬퍼 함수의 종합 레퍼런스입니다.

---

## 목차

1. [UI 컴포넌트](#1-ui-컴포넌트)
2. [번역 함수](#2-번역-함수)
3. [포맷팅 함수](#3-포맷팅-함수)
4. [경로 함수](#4-경로-함수)
5. [인증 함수](#5-인증-함수)
6. [데이터베이스 함수](#6-데이터베이스-함수)
7. [유효성 검사 함수](#7-유효성-검사-함수)
8. [암호화 함수](#8-암호화-함수)
9. [로깅 함수](#9-로깅-함수)
10. [유틸리티 함수](#10-유틸리티-함수)
11. [클래스 레퍼런스](#11-클래스-레퍼런스)

---

## 1. UI 컴포넌트

### 1.1 전화번호 입력 컴포넌트

| 항목 | 내용 |
|------|------|
| **컴포넌트명** | `phone-input` |
| **파일** | `resources/views/components/phone-input.php` |
| **JavaScript** | `assets/js/phone-input.js` |

#### 기능
- 180개+ 전세계 국가 코드 지원
- 네이티브 국가명(Endonym) 표시
- 국가/코드 검색 기능
- 국가별 전화번호 자동 포맷팅
- 라이트/다크모드 지원

#### 사용법

```php
<?php
$phoneInputConfig = [
    'name' => 'phone',           // 필드명 (필수)
    'id' => 'phone',             // ID 접두사
    'label' => '전화번호',        // 라벨 텍스트
    'value' => '',               // 전체 전화번호
    'country_code' => '+82',     // 초기 국가코드
    'phone_number' => '',        // 초기 전화번호
    'required' => false,         // 필수 여부
    'hint' => '',                // 힌트 텍스트
    'placeholder' => '010-1234-5678',
    'show_label' => true,
];
include BASE_PATH . '/resources/views/components/phone-input.php';
?>

<!-- 페이지 하단에 JavaScript 포함 -->
<script src="/assets/js/phone-input.js"></script>
```

#### JavaScript API

```javascript
// 값 가져오기
const value = PhoneInput.getValue('phone');
// { countryCode: '+82', phoneNumber: '010-1234-5678', fullNumber: '+821012345678' }

// 값 설정하기
PhoneInput.setValue('phone', '+81', '09012345678');

// 전화번호 포맷팅
const formatted = PhoneInput.formatPhoneNumber('01012345678', '+82');
// '010-1234-5678'
```

#### 폼 데이터

| 필드명 | 예시 값 | 설명 |
|--------|---------|------|
| `phone` | `+821012345678` | 조합된 전체 전화번호 |
| `phone_country` | `+82` | 선택된 국가 코드 |
| `phone_number` | `010-1234-5678` | 입력된 전화번호 |

---

### 1.2 약관 동의 컴포넌트

| 항목 | 내용 |
|------|------|
| **컴포넌트명** | `terms_agreement` |
| **파일** | `skins/member/default/components/terms_agreement.php` |

#### 기능
- 이용약관, 개인정보처리방침 등 약관 표시
- 필수/선택 약관 구분
- 전체 동의 체크박스
- 약관 내용 모달 표시

#### 사용법

```php
<?php
$termsSettings = [
    1 => ['consent' => 'required', 'title' => '이용약관', 'content' => '...'],
    2 => ['consent' => 'required', 'title' => '개인정보처리방침', 'content' => '...'],
    3 => ['consent' => 'optional', 'title' => '마케팅 수신 동의', 'content' => '...'],
];
include 'components/terms_agreement.php';
?>
```

---

### 1.3 소셜 로그인 컴포넌트

| 항목 | 내용 |
|------|------|
| **컴포넌트명** | `social_login` |
| **파일** | `skins/member/default/components/social_login.php` |

#### 기능
- Google, Kakao, Naver, Line 소셜 로그인
- 아이콘 및 브랜드 색상 적용
- 다크모드 지원

#### 사용법

```php
<?php
$socialProviders = ['google', 'kakao', 'naver', 'line'];
include 'components/social_login.php';
?>
```

---

### 1.4 이미지 크롭 컴포넌트

| 항목 | 내용 |
|------|------|
| **컴포넌트명** | `image_cropper` |
| **파일** | `skins/default/components/image_cropper.php` |
| **의존성** | Cropper.js 1.6.x (CDN) |

#### 기능
- Cropper.js 기반 이미지 편집
- 확대/축소, 회전 (좌/우 90°)
- 드래그로 위치 조정
- 커스텀 가로세로 비율 설정
- 크롭 후 Base64 출력
- 드래그 앤 드롭 지원
- 다크모드/라이트모드 지원
- 다국어 지원 (ko/en/ja)

#### 사용법

```php
<?php
$cropperConfig = [
    'id' => 'profile_photo_cropper',    // 고유 ID (필수)
    'inputName' => 'cropped_image',     // 폼 필드명
    'aspectRatio' => 1,                 // 가로세로 비율 (1 = 정사각형)
    'outputWidth' => 400,               // 출력 너비
    'outputHeight' => 400,              // 출력 높이
    'outputFormat' => 'image/jpeg',     // 출력 포맷
    'outputQuality' => 0.9,             // JPEG 품질 (0-1)
    'cropBoxResizable' => true,         // 크롭 박스 크기 조절
    'translations' => [                 // 번역 (선택)
        'title' => '이미지 편집',
        'zoom_in' => '확대',
        // ... 기타 번역
    ]
];
include BASE_PATH . '/skins/default/components/image_cropper.php';
?>
```

#### JavaScript API

```javascript
// 모달 열기 + 파일 선택 다이얼로그
ImageCropper.trigger('profile_photo_cropper');

// 모달만 열기
ImageCropper.open('profile_photo_cropper');

// 모달 닫기
ImageCropper.close('profile_photo_cropper');

// 확대/축소
ImageCropper.zoom('profile_photo_cropper', 0.1);   // 확대
ImageCropper.zoom('profile_photo_cropper', -0.1);  // 축소

// 회전
ImageCropper.rotate('profile_photo_cropper', 90);  // 시계방향
ImageCropper.rotate('profile_photo_cropper', -90); // 반시계방향

// 초기화
ImageCropper.reset('profile_photo_cropper');

// 크롭 적용 (자동으로 hidden 필드에 저장)
ImageCropper.apply('profile_photo_cropper');

// 콜백 설정
ImageCropper.setOnApply('profile_photo_cropper', function(dataUrl, canvas, id) {
    console.log('크롭 완료:', dataUrl);
});
```

#### 커스텀 이벤트

```javascript
// 크롭 완료 시 발생하는 이벤트
document.addEventListener('imageCropped', function(e) {
    console.log('크롭 ID:', e.detail.id);
    console.log('Base64 데이터:', e.detail.dataUrl);
    console.log('캔버스:', e.detail.canvas);
});
```

#### 폼 데이터

| 필드명 | 예시 값 | 설명 |
|--------|---------|------|
| `cropped_image` (설정 가능) | `data:image/jpeg;base64,...` | 크롭된 이미지 Base64 |

---

### 1.5 동적 회원가입 필드 컴포넌트

| 항목 | 내용 |
|------|------|
| **컴포넌트명** | `register_fields` |
| **파일** | `skins/default/components/register_fields.php` |

#### 기능
- 관리자 설정에 따른 동적 필드 렌더링
- 확장 가능한 필드 정의 구조
- 프로필 사진 필드는 자동으로 이미지 크롭 컴포넌트 연동

#### 지원 필드

| 필드명 | 타입 | 필수 | 설명 |
|--------|------|------|------|
| `name` | text | O | 이름 |
| `email` | email | O | 이메일 |
| `password` | password | O | 비밀번호 (확인 포함) |
| `phone` | phone | X | 전화번호 (국가코드 포함) |
| `birth_date` | date | X | 생년월일 |
| `gender` | radio | X | 성별 (남/여/기타) |
| `company` | text | X | 회사/소속 |
| `blog` | url | X | 블로그/웹사이트 |
| `profile_photo` | file | X | 프로필 사진 (크롭 지원) |

#### 사용법

```php
<?php
// 활성화할 필드 목록
$registerFields = ['name', 'email', 'password', 'phone', 'profile_photo'];
$translations = $skinLoader->getTranslations();
$oldInput = $_POST ?? [];

include BASE_PATH . '/skins/default/components/register_fields.php';
?>
```

---

## 2. 번역 함수

### 2.1 `__()`

| 항목 | 내용 |
|------|------|
| **함수명** | `__($key, $replace, $locale)` |
| **설명** | 파일 기반 번역 가져오기 |
| **반환값** | `string` |

```php
// 기본 사용
echo __('common.nav.home');  // "홈"

// 변수 치환
echo __('common.pagination.showing', ['from' => 1, 'to' => 10, 'total' => 100]);
// "100개 중 1 - 10 표시"

// 특정 로케일
echo __('common.nav.home', [], 'en');  // "Home"
```

---

### 2.2 `trans()`

| 항목 | 내용 |
|------|------|
| **함수명** | `trans($key, $replace, $locale)` |
| **설명** | `__()` 함수의 별칭 |
| **반환값** | `string` |

```php
echo trans('booking.title');  // __('booking.title')과 동일
```

---

### 2.3 `current_locale()`

| 항목 | 내용 |
|------|------|
| **함수명** | `current_locale()` |
| **설명** | 현재 로케일 코드 반환 |
| **반환값** | `string` (ko, en, ja) |

```php
$locale = current_locale();  // "ko"
```

---

### 2.4 `db_trans()`

| 항목 | 내용 |
|------|------|
| **함수명** | `db_trans($langKey, $locale, $default)` |
| **설명** | DB에서 다국어 번역 가져오기 |
| **반환값** | `string` |

```php
// 기본 사용
$tagline = db_trans('site.tagline');

// 특정 로케일 + 기본값
$name = db_trans('site.name', 'en', 'RezlyX');
```

**우선순위**: 현재 로케일 번역 → source_locale(원본) 번역 → 기본값

---

### 2.5 `is_translation_fallback()`

| 항목 | 내용 |
|------|------|
| **함수명** | `is_translation_fallback($langKey, $locale)` |
| **설명** | 번역이 fallback(원본 언어) 사용 중인지 확인 |
| **반환값** | `bool` |

```php
if (is_translation_fallback('term.3.content')) {
    echo '<span class="badge">번역 대기</span>';
}
```

---

### 2.6 `get_site_name()`

| 항목 | 내용 |
|------|------|
| **함수명** | `get_site_name($locale)` |
| **설명** | 사이트 이름 가져오기 (다국어) |
| **반환값** | `string` |

```php
$siteName = get_site_name();  // 현재 로케일
$siteName = get_site_name('ja');  // 일본어
```

**우선순위**: DB 번역 → rzx_settings → APP_NAME 환경변수 → 'RezlyX'

---

### 2.7 `get_site_tagline()`

| 항목 | 내용 |
|------|------|
| **함수명** | `get_site_tagline($locale)` |
| **설명** | 사이트 제목/슬로건 가져오기 (다국어) |
| **반환값** | `string` |

```php
$tagline = get_site_tagline();
```

---

## 3. 포맷팅 함수

### 3.1 `formatReservationDate()` - 다국어 날짜 포맷팅

| 항목 | 내용 |
|------|------|
| **함수명** | `formatReservationDate($dateString, $locale)` |
| **설명** | 날짜를 로케일에 맞게 포맷팅 (13개 언어 지원) |
| **파일** | `resources/views/customer/booking/detail.php` (라인 44-95) |
| **반환값** | `string` |

#### 기능

- **13개 언어 자동 포맷팅**: ko, en, ja, zh_CN, zh_TW, de, es, fr, id, mn, ru, tr, vi
- **현재 로케일 자동 감지**: 파라미터 생략 시 현재 로케일 사용
- **날짜 구성 요소**: 년/월/일 + 요일명 (현지화)
- **폴백 처리**: 미지원 로케일은 영어(en) 형식으로 자동 전환

#### 지원하는 날짜 형식

| 언어 | 형식 | 예시 |
|------|------|------|
| **한국어(ko)** | `YYYY년 M월 D일 (요일)` | 2026년 3월 30일 (월) |
| **일본어(ja)** | `YYYY年M月D日 (曜日)` | 2026年3月30日 (月) |
| **영어(en)** | `Mon D, YYYY (Dayname)` | Mar 30, 2026 (Mon) |
| **중국어 간체(zh_CN)** | `YYYY年M月D日 (曜日)` | 2026年3月30日 (一) |
| **중국어 번체(zh_TW)** | `YYYY年M月D日 (曜日)` | 2026年3月30日 (一) |
| **독일어(de)** | `D. Monthname YYYY (Dayname)` | 30. März 2026 (Mo) |
| **스페인어(es)** | `D monthname YYYY (Dayname)` | 30 marzo 2026 (Lun) |
| **프랑스어(fr)** | `D monthname YYYY (Dayname)` | 30 mars 2026 (Lun) |
| **인도네시아어(id)** | `D Monthname YYYY (Dayname)` | 30 Maret 2026 (Sen) |
| **몽골어(mn)** | `YYYY оны M сарын D (요일)` | 2026 оны 3 сарын 30 (Дав) |
| **러시아어(ru)** | `D monthname YYYY (Dayname)` | 30 марта 2026 (Пн) |
| **터키어(tr)** | `D Monthname YYYY (Dayname)` | 30 Mart 2026 (Pzt) |
| **베트남어(vi)** | `Ngày D tháng M năm YYYY (Dayname)` | Ngày 30 tháng 3 năm 2026 (T2) |

#### 사용법

```php
<?php
// 현재 로케일 사용 (권장)
$dateStr = formatReservationDate('2026-03-30', $currentLocale);
// 출력 (로케일이 'ko'인 경우): "2026년 3월 30일 (월)"

// 특정 로케일 지정
$dateStr = formatReservationDate('2026-03-30', 'ja');
// 출력: "2026年3月30日 (月)"

// 영어
$dateStr = formatReservationDate('2026-03-30', 'en');
// 출력: "Mar 30, 2026 (Mon)"

// 예약 상세 페이지에서 사용
<p class="text-lg font-semibold">
    <?= formatReservationDate($reservation['reservation_date'], $currentLocale) ?>
</p>
?>
```

#### 파라미터

| 파라미터 | 타입 | 필수 | 설명 |
|---------|------|------|------|
| `$dateString` | string | O | 날짜 문자열 (YYYY-MM-DD 형식) |
| `$locale` | string | O | 로케일 코드 (ko, en, ja, 등) |

#### 반환값

| 경우 | 반환값 | 예시 |
|------|--------|------|
| 유효한 날짜 + 지원 로케일 | 포맷된 날짜 | "2026년 3월 30일 (월)" |
| 유효한 날짜 + 미지원 로케일 | 영어 형식 | "Mar 30, 2026 (Mon)" |
| 유효하지 않은 날짜 | 공문자열 `''` | "" |

#### 내부 구조

**배열 기반 다국어 처리:**
- 요일명 배열 (`dayNamesMap`) - 각 언어별 요일 약자
- 월명 배열 (`monthNamesMap`) - 각 언어별 월명
- switch 문으로 언어별 포맷팅 로직 분리

**예시:**
```php
$dayNamesMap = [
    'ko' => ['일', '월', '화', '수', '목', '금', '토'],
    'en' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    'ja' => ['日', '月', '火', '水', '木', '金', '土'],
    // ... 나머지 10개 언어
];
```

#### 통합 예제

**예약 상세 페이지:**
```php
<?php
// 예약 데이터 로드
$reservation = $pdo->query("SELECT * FROM rzx_reservations WHERE id = ?")
    ->fetch(PDO::FETCH_ASSOC);

$currentLocale = current_locale();  // 현재 로케일 (ko, en, ja 등)

// 날짜 포맷팅
$formattedDate = formatReservationDate($reservation['reservation_date'], $currentLocale);
?>

<div class="booking-info">
    <h2><?= __('booking.detail.title') ?></h2>

    <!-- 예약 날짜 (다국어 적용) -->
    <p class="date">
        <?= formatReservationDate($reservation['reservation_date'], $currentLocale) ?>
    </p>

    <!-- 시간 정보 -->
    <p class="time">
        <?= date('H:i', strtotime($reservation['start_time'])) ?> ~
        <?= date('H:i', strtotime($reservation['end_time'])) ?>
    </p>
</div>
```

#### 언어별 출력 예시

동일한 날짜 `2026-03-30`을 다양한 언어로 표시:

```
한국어:     2026년 3월 30일 (월)
영어:       Mar 30, 2026 (Mon)
일본어:     2026年3月30日 (月)
중국어:     2026年3月30日 (一)
독일어:     30. März 2026 (Mo)
스페인어:   30 marzo 2026 (Lun)
프랑스어:   30 mars 2026 (Lun)
```

#### 주의사항

- 입력 형식은 반드시 `YYYY-MM-DD` (ISO 8601)
- 유효하지 않은 날짜는 빈 문자열 반환
- 로케일 코드는 소문자 (예: 'en', 'ja')
- 월과 일 앞의 0은 정수로 변환되어 제거 (3월 → `3월`, 09일 → `9일`)

#### 관련 함수

- `date()` - PHP 내장 함수로 기본 형식 제공
- `current_locale()` - 현재 로케일 취득
- `__()` - 다국어 번역 (라벨용)

---

## 4. 경로 함수

### 3.1 `base_path()`

| 항목 | 내용 |
|------|------|
| **함수명** | `base_path($path)` |
| **설명** | 프로젝트 루트 경로 |
| **반환값** | `string` |

```php
$path = base_path();  // /var/www/rezlyx
$path = base_path('config/app.php');  // /var/www/rezlyx/config/app.php
```

---

### 3.2 `storage_path()`

| 항목 | 내용 |
|------|------|
| **함수명** | `storage_path($path)` |
| **설명** | storage 폴더 경로 |
| **반환값** | `string` |

```php
$path = storage_path('logs/app.log');
```

---

### 3.3 `resource_path()`

| 항목 | 내용 |
|------|------|
| **함수명** | `resource_path($path)` |
| **설명** | resources 폴더 경로 |
| **반환값** | `string` |

```php
$path = resource_path('views/home.php');
```

---

### 3.4 `url()`

| 항목 | 내용 |
|------|------|
| **함수명** | `url($path, $params)` |
| **설명** | URL 생성 |
| **반환값** | `string` |

```php
$url = url('/services');  // https://example.com/services
$url = url('/search', ['q' => 'test']);  // https://example.com/search?q=test
```

---

### 3.5 `asset()`

| 항목 | 내용 |
|------|------|
| **함수명** | `asset($path)` |
| **설명** | 에셋 URL 생성 |
| **반환값** | `string` |

```php
$url = asset('css/app.css');  // https://example.com/assets/css/app.css
```

---

### 3.6 `route()`

| 항목 | 내용 |
|------|------|
| **함수명** | `route($name, $params)` |
| **설명** | 이름 기반 라우트 URL 생성 |
| **반환값** | `string` |

```php
$url = route('services.show', ['id' => 1]);
```

---

## 4. 인증 함수

### 4.1 `auth()`

| 항목 | 내용 |
|------|------|
| **함수명** | `auth($guard)` |
| **설명** | 인증 매니저 인스턴스 |
| **반환값** | `AuthManager` |

```php
$user = auth()->user();
$isLoggedIn = auth()->check();
auth()->logout();
```

---

### 4.2 `csrf_token()`

| 항목 | 내용 |
|------|------|
| **함수명** | `csrf_token()` |
| **설명** | CSRF 토큰 값 |
| **반환값** | `string` |

```php
$token = csrf_token();
```

---

### 4.3 `csrf_field()`

| 항목 | 내용 |
|------|------|
| **함수명** | `csrf_field()` |
| **설명** | CSRF 토큰 hidden input HTML |
| **반환값** | `string` |

```php
echo csrf_field();
// <input type="hidden" name="_token" value="...">
```

---

### 4.4 `bcrypt()`

| 항목 | 내용 |
|------|------|
| **함수명** | `bcrypt($value)` |
| **설명** | 비밀번호 해시 생성 |
| **반환값** | `string` |

```php
$hash = bcrypt('password123');
```

---

### 4.5 `password_check()`

| 항목 | 내용 |
|------|------|
| **함수명** | `password_check($value, $hashedValue)` |
| **설명** | 비밀번호 검증 |
| **반환값** | `bool` |

```php
if (password_check($input, $user->password)) {
    // 비밀번호 일치
}
```

---

## 5. 데이터베이스 함수

### 5.1 `db()`

| 항목 | 내용 |
|------|------|
| **함수명** | `db($connection)` |
| **설명** | 데이터베이스 매니저 또는 연결 |
| **반환값** | `DatabaseManager` \| `Connection` |

```php
$db = db();  // 기본 연결
$db = db('mysql');  // 특정 연결

// 쿼리 실행
$users = db()->table('users')->get();
```

---

### 5.2 `get_setting()`

| 항목 | 내용 |
|------|------|
| **함수명** | `get_setting($key, $default)` |
| **설명** | rzx_settings 테이블에서 설정값 조회 |
| **반환값** | `string` |

```php
$siteName = get_setting('site_name', 'RezlyX');
$isEnabled = get_setting('maintenance_mode', 'false');
```

---

## 6. 유효성 검사 함수

### 6.1 `validate()`

| 항목 | 내용 |
|------|------|
| **함수명** | `validate($data, $rules, $messages, $customAttributes)` |
| **설명** | 데이터 유효성 검사 수행 |
| **반환값** | `array` (검증된 데이터) |

```php
$validated = validate($_POST, [
    'name' => 'required|min:2',
    'email' => 'required|email',
    'phone' => 'nullable|regex:/^[0-9\-]+$/',
]);
```

---

### 6.2 `validator()`

| 항목 | 내용 |
|------|------|
| **함수명** | `validator($data, $rules, $messages, $customAttributes)` |
| **설명** | Validator 인스턴스 생성 |
| **반환값** | `Validator` |

```php
$v = validator($_POST, ['email' => 'required|email']);

if ($v->fails()) {
    $errors = $v->errors();
}
```

---

## 7. 암호화 함수

### 7.1 `encrypt()`

| 항목 | 내용 |
|------|------|
| **함수명** | `encrypt($value)` |
| **설명** | AES-256-CBC 암호화 |
| **반환값** | `string` \| `null` |

```php
$encrypted = encrypt('sensitive data');
```

---

### 7.2 `decrypt()`

| 항목 | 내용 |
|------|------|
| **함수명** | `decrypt($value)` |
| **설명** | 암호화된 값 복호화 |
| **반환값** | `string` \| `null` |

```php
$original = decrypt($encrypted);
```

---

### 7.3 `encrypt_fields()`

| 항목 | 내용 |
|------|------|
| **함수명** | `encrypt_fields($data, $fields)` |
| **설명** | 배열의 특정 필드 암호화 |
| **반환값** | `array` |

```php
$data = encrypt_fields($userData, ['phone', 'address']);
```

---

### 7.4 `decrypt_fields()`

| 항목 | 내용 |
|------|------|
| **함수명** | `decrypt_fields($data, $fields)` |
| **설명** | 배열의 특정 필드 복호화 |
| **반환값** | `array` |

```php
$data = decrypt_fields($dbRow, ['phone', 'address']);
```

---

## 8. 로깅 함수

### 8.1 `log_debug()`

| 항목 | 내용 |
|------|------|
| **함수명** | `log_debug($message, $context)` |
| **설명** | 디버그 로그 기록 |

```php
log_debug('사용자 로그인 시도', ['email' => $email]);
```

---

### 8.2 `log_info()`

| 항목 | 내용 |
|------|------|
| **함수명** | `log_info($message, $context)` |
| **설명** | 정보 로그 기록 |

```php
log_info('예약 생성', ['id' => $reservation->id]);
```

---

### 8.3 `log_warning()`

| 항목 | 내용 |
|------|------|
| **함수명** | `log_warning($message, $context)` |
| **설명** | 경고 로그 기록 |

```php
log_warning('API 응답 지연', ['duration' => $ms]);
```

---

### 8.4 `log_error()`

| 항목 | 내용 |
|------|------|
| **함수명** | `log_error($message, $context)` |
| **설명** | 에러 로그 기록 |

```php
log_error('결제 실패', ['error' => $e->getMessage()]);
```

---

### 8.5 `log_exception()`

| 항목 | 내용 |
|------|------|
| **함수명** | `log_exception($exception, $context)` |
| **설명** | 예외 로그 기록 (스택 트레이스 포함) |

```php
try {
    // ...
} catch (Exception $e) {
    log_exception($e, ['user_id' => $userId]);
}
```

---

## 9. 유틸리티 함수

### 9.1 `env()`

| 항목 | 내용 |
|------|------|
| **함수명** | `env($key, $default)` |
| **설명** | 환경변수 값 가져오기 |
| **반환값** | `mixed` |

```php
$debug = env('APP_DEBUG', false);
$dbHost = env('DB_HOST', '127.0.0.1');
```

---

### 9.2 `config()`

| 항목 | 내용 |
|------|------|
| **함수명** | `config($key, $default)` |
| **설명** | 설정값 가져오기 |
| **반환값** | `mixed` |

```php
$appName = config('app.name');
$timezone = config('app.timezone', 'Asia/Seoul');
```

---

### 9.3 `now()`

| 항목 | 내용 |
|------|------|
| **함수명** | `now($tz)` |
| **설명** | 현재 시간 DateTimeImmutable |
| **반환값** | `DateTimeImmutable` |

```php
$now = now();
$tokyoNow = now('Asia/Tokyo');
```

---

### 9.4 `redirect()`

| 항목 | 내용 |
|------|------|
| **함수명** | `redirect($url, $status)` |
| **설명** | 페이지 리다이렉트 |
| **반환값** | `never` |

```php
redirect('/login');
redirect('/dashboard', 301);  // 영구 이동
```

---

### 9.5 `abort()`

| 항목 | 내용 |
|------|------|
| **함수명** | `abort($code, $message)` |
| **설명** | HTTP 예외 발생 |
| **반환값** | `never` |

```php
abort(404, '페이지를 찾을 수 없습니다.');
abort(403, '접근 권한이 없습니다.');
```

---

### 9.6 `e()`

| 항목 | 내용 |
|------|------|
| **함수명** | `e($value)` |
| **설명** | HTML 엔티티 이스케이프 |
| **반환값** | `string` |

```php
echo e($userInput);  // XSS 방지
```

---

### 9.7 `dd()` / `dump()`

| 항목 | 내용 |
|------|------|
| **함수명** | `dd(...$vars)` / `dump(...$vars)` |
| **설명** | 변수 덤프 (dd는 종료) |

```php
dump($data);  // 덤프 후 계속 실행
dd($data);    // 덤프 후 종료
```

---

### 9.8 `old()`

| 항목 | 내용 |
|------|------|
| **함수명** | `old($key, $default)` |
| **설명** | 이전 입력값 가져오기 |
| **반환값** | `mixed` |

```php
<input name="email" value="<?= e(old('email')) ?>">
```

---

## 10. 클래스 레퍼런스

### 10.1 MemberSkinLoader

| 항목 | 내용 |
|------|------|
| **클래스** | `RezlyX\Core\Skin\MemberSkinLoader` |
| **파일** | `rzxlib/Core/Skin/MemberSkinLoader.php` |
| **설명** | 회원 스킨 로더 |

```php
use RezlyX\Core\Skin\MemberSkinLoader;

$skinLoader = new MemberSkinLoader('/path/to/skins/member', 'default');
$skinLoader->setColorset('dark');
$skinLoader->setTranslations(['login_title' => '로그인']);

echo $skinLoader->render('login', [
    'errors' => [],
    'csrfToken' => $token,
]);
```

**주요 메서드**:
- `setSkin(string $skin)` - 스킨 변경
- `setColorset(string $colorset)` - 컬러셋 변경
- `setTranslations(array $translations)` - 번역 설정
- `render(string $page, array $data)` - 페이지 렌더링
- `getAvailableSkins()` - 사용 가능한 스킨 목록
- `getCssVariables()` - CSS 변수 생성

---

### 10.2 Translator

| 항목 | 내용 |
|------|------|
| **클래스** | `RzxLib\Core\I18n\Translator` |
| **파일** | `rzxlib/Core/I18n/Translator.php` |
| **설명** | 다국어 번역 관리 |

```php
use RzxLib\Core\I18n\Translator;

Translator::init(BASE_PATH . '/resources/lang');
Translator::setLocale('en');

$text = Translator::get('common.nav.home');
$locales = Translator::getAllLocales();
```

**주요 메서드**:
- `init(string $path, ?string $locale)` - 초기화
- `setLocale(string $locale)` - 로케일 변경
- `getLocale()` - 현재 로케일
- `get(string $key, array $replace, ?string $locale)` - 번역 가져오기
- `getSupportedLocales()` - 지원 로케일 목록
- `getAllLocales()` - 로케일 정보 (드롭다운용)

---

### 10.3 Logger

| 항목 | 내용 |
|------|------|
| **클래스** | `RzxLib\Core\Helpers\Logger` |
| **파일** | `rzxlib/Core/Helpers/Logger.php` |
| **설명** | 로깅 유틸리티 |

```php
use RzxLib\Core\Helpers\Logger;

Logger::logInfo('메시지', ['key' => 'value']);
Logger::logError('에러 발생', ['error' => $e->getMessage()]);
Logger::logException($exception);
```

---

### 10.4 Encryption

| 항목 | 내용 |
|------|------|
| **클래스** | `RzxLib\Core\Helpers\Encryption` |
| **파일** | `rzxlib/Core/Helpers/Encryption.php` |
| **설명** | AES-256-CBC 암호화 |

```php
use RzxLib\Core\Helpers\Encryption;

$encrypted = Encryption::encrypt('data');
$decrypted = Encryption::decrypt($encrypted);

$data = Encryption::encryptFields($array, ['phone', 'email']);
$data = Encryption::decryptFields($array, ['phone', 'email']);
```

---

### 10.5 ImageHelper

| 항목 | 내용 |
|------|------|
| **클래스** | `RzxLib\Core\Helpers\ImageHelper` |
| **파일** | `rzxlib/Core/Helpers/ImageHelper.php` |
| **설명** | 이미지 처리 및 저장 유틸리티 |

```php
use RzxLib\Core\Helpers\ImageHelper;

$imageHelper = new ImageHelper();  // 기본 경로: uploads/profiles
$imageHelper = new ImageHelper('/custom/upload/path');  // 커스텀 경로

// Base64 이미지 저장
$result = $imageHelper->saveBase64Image($base64Data, 'filename', 'subdir');
// 결과: ['success' => true, 'path' => '...', 'relative_path' => '...', 'filename' => '...']

// 프로필 이미지 저장 (회원가입용)
$result = $imageHelper->saveProfileImage($base64Data, $userId);

// 이미지 삭제
$imageHelper->deleteImage('/path/to/image.jpg');

// 이미지 리사이즈
$imageHelper->resizeImage($sourcePath, 200, 200, $destPath);
```

**주요 메서드**:
- `saveBase64Image($base64Data, $filename, $subDir)` - Base64 이미지 저장
- `saveProfileImage($base64Data, $userId)` - 프로필 이미지 저장
- `deleteImage($path)` - 이미지 삭제
- `resizeImage($source, $width, $height, $dest)` - 이미지 리사이즈
- `setUploadPath($path)` - 업로드 경로 설정
- `setMaxFileSize($bytes)` - 최대 파일 크기 설정

**지원 포맷**: JPEG, PNG, GIF, WebP

---

## 버전 정보

- **문서 버전**: 1.1.0
- **최종 수정일**: 2026-03-05
- **적용 대상**: RezlyX v1.x

### 관련 문서

- [UI_COMPONENTS.md](UI_COMPONENTS.md) - UI 컴포넌트 상세
- [I18N.md](I18N.md) - 다국어 시스템
- [MEMBER_SKIN.md](MEMBER_SKIN.md) - 회원 스킨 시스템
- [CORE_LIBRARIES.md](CORE_LIBRARIES.md) - 코어 라이브러리
