# RezlyX UI 컴포넌트 문서

이 문서는 RezlyX에서 사용되는 재사용 가능한 UI 컴포넌트에 대한 가이드입니다.

---

## 목차

1. [컴포넌트 개요](#1-컴포넌트-개요)
2. [전화번호 입력 컴포넌트](#2-전화번호-입력-컴포넌트)
3. [헤더/푸터 컴포넌트](#3-헤더푸터-컴포넌트)
4. [페이지 컴포넌트](#4-페이지-컴포넌트)
5. [관리자 탭 컴포넌트](#5-관리자-탭-컴포넌트)
6. [이미지 크롭 컴포넌트](#6-이미지-크롭-컴포넌트)

---

## 1. 컴포넌트 개요

### 1.1 파일 구조

```
resources/views/
├── components/           # 재사용 가능한 UI 컴포넌트
│   └── phone-input.php   # 전화번호 입력 컴포넌트
├── partials/             # 레이아웃 부분 템플릿
│   ├── header.php        # 공통 헤더
│   └── footer.php        # 공통 푸터
├── admin/
│   └── settings/
│       └── system/       # 시스템 설정 하위 페이지
│           ├── _tabs.php # 탭 네비게이션 컴포넌트
│           ├── info.php  # 정보관리
│           ├── cache.php # 캐시관리
│           ├── mode.php  # 모드관리
│           └── logs.php  # 로그관리
└── ...

assets/js/
└── phone-input.js        # 전화번호 입력 JavaScript 모듈
```

### 1.2 컴포넌트 사용 원칙

- **재사용성**: 여러 페이지에서 동일한 기능을 일관되게 제공
- **설정 가능**: `$config` 배열을 통해 동작 커스터마이징
- **다국어 지원**: 번역 함수 `__()` 사용
- **다크모드 지원**: Tailwind CSS `dark:` 접두사 활용

---

## 2. 전화번호 입력 컴포넌트

국가 코드 선택과 전화번호 자동 포맷팅을 제공하는 재사용 가능한 입력 컴포넌트입니다.

### 2.1 기능

- **180개+ 전세계 국가 코드 지원**
- **네이티브 국가명(Endonym) 표시**: 각 국가가 해당 국가 언어로 표시됨
  - 🇰🇷 대한민국, 🇯🇵 日本, 🇩🇪 Deutschland, 🇪🇸 España
- **검색 기능**: 국가명, 국가코드, ISO 코드로 빠른 검색
- 언어별 우선 국가 자동 정렬 (한국어→한국, 일본어→일본, 영어→미국)
- 국가별 전화번호 자동 포맷팅
- 라이트/다크모드 지원
- 모바일 친화적 UI

### 2.2 지원 국가 (전세계 180개+)

| 지역 | 국가 |
|------|------|
| 아시아-태평양 | 🇰🇷 대한민국, 🇯🇵 日本, 🇨🇳 中国, 🇹🇼 臺灣, 🇭🇰 香港, 🇲🇴 澳門, 🇸🇬 Singapore, 🇹🇭 ไทย, 🇻🇳 Việt Nam, 🇲🇾 Malaysia, 🇮🇩 Indonesia, 🇵🇭 Pilipinas, 🇮🇳 India 등 28개국 |
| 유럽 | 🇬🇧 UK, 🇩🇪 Deutschland, 🇫🇷 France, 🇮🇹 Italia, 🇪🇸 España, 🇳🇱 Nederland, 🇷🇺 Россия, 🇺🇦 Україна, 🇵🇱 Polska, 🇬🇷 Ελλάδα 등 45개국 |
| 아메리카 | 🇺🇸 United States, 🇨🇦 Canada, 🇲🇽 México, 🇧🇷 Brasil, 🇦🇷 Argentina, 🇨🇱 Chile, 🇨🇴 Colombia 등 24개국 |
| 중동 | 🇦🇪 UAE, 🇸🇦 السعودية, 🇰🇼 الكويت, 🇶🇦 قطر, 🇮🇱 Israel, 🇯🇴 الأردن 등 13개국 |
| 아프리카 | 🇪🇬 مصر, 🇲🇦 المغرب, 🇿🇦 South Africa, 🇳🇬 Nigeria, 🇰🇪 Kenya 등 55개국 |
| 오세아니아 | 🇦🇺 Australia, 🇳🇿 New Zealand, 🇫🇯 Fiji, 🇵🇬 Papua New Guinea 등 13개국 |
| 카리브해/기타 | 🇵🇷 Puerto Rico, 🇯🇲 Jamaica, 🇧🇸 Bahamas, 🇧🇧 Barbados 등 25개국 |

### 2.3 네이티브 국가명 시스템

번역 파일 없이 각 국가명을 해당 국가 언어로 표시합니다:

```php
// phone-input.php 내 국가 배열 구조
$countries = [
    ['+82', 'kr', '대한민국'],      // 한국어
    ['+81', 'jp', '日本'],          // 일본어
    ['+49', 'de', 'Deutschland'],   // 독일어
    ['+34', 'es', 'España'],        // 스페인어
    ['+966', 'sa', 'السعودية'],    // 아랍어
    // ...
];
```

**장점**:
- 번역 파일 유지보수 불필요
- 각 국가 사용자가 자신의 국가명을 쉽게 인식
- 국기 이모지로 낯선 문자도 식별 가능

### 2.4 기본 사용법

```php
<?php
$phoneInputConfig = [
    'name' => 'phone',
    'id' => 'phone',
    'label' => __('auth.register.phone'),
    'required' => false,
];
include BASE_PATH . '/resources/views/components/phone-input.php';
?>

<!-- 페이지 하단에 JavaScript 포함 -->
<script src="<?= $baseUrl ?>/assets/js/phone-input.js"></script>
```

### 2.5 설정 옵션

| 옵션 | 타입 | 기본값 | 설명 |
|------|------|--------|------|
| `name` | string | `'phone'` | 폼 필드 이름 (필수) |
| `id` | string | `name` 값 | ID 접두사 |
| `label` | string | `__('auth.register.phone')` | 라벨 텍스트 |
| `value` | string | `''` | 초기값 (전체 전화번호) |
| `country_code` | string | `'+82'` | 초기 국가 코드 |
| `phone_number` | string | `''` | 초기 전화번호 (포맷팅 전) |
| `required` | bool | `false` | 필수 여부 |
| `hint` | string | `''` | 힌트 텍스트 |
| `placeholder` | string | `'010-1234-5678'` | placeholder |
| `show_label` | bool | `true` | 라벨 표시 여부 |

### 2.6 전체 예제

```php
<?php
$phoneInputConfig = [
    'name' => 'contact_phone',
    'id' => 'contact_phone',
    'label' => '연락처',
    'value' => $formData['phone'] ?? '',
    'country_code' => $formData['phone_country'] ?? '+82',
    'phone_number' => $formData['phone_number'] ?? '',
    'required' => true,
    'hint' => '예약 확인 문자가 발송됩니다.',
    'placeholder' => '010-1234-5678',
    'show_label' => true,
];
include BASE_PATH . '/resources/views/components/phone-input.php';
?>
```

### 2.7 폼 데이터 처리

컴포넌트는 3개의 폼 필드를 생성합니다:

| 필드명 | 예시 | 설명 |
|--------|------|------|
| `{name}` | `phone` | 조합된 전체 전화번호 (예: `+821012345678`) |
| `{name}_country` | `phone_country` | 선택된 국가 코드 (예: `+82`) |
| `{name}_number` | `phone_number` | 입력된 전화번호 (예: `010-1234-5678`) |

```php
// 서버 측 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullPhone = $_POST['phone'];           // +821012345678
    $countryCode = $_POST['phone_country']; // +82
    $phoneNumber = $_POST['phone_number'];  // 010-1234-5678

    // DB 저장 시 전체 번호 사용
    $reservation->customer_phone = $fullPhone;
}
```

### 2.8 JavaScript API

```javascript
// 컴포넌트 초기화 (자동 실행됨)
PhoneInput.init();

// 특정 컨테이너 내 컴포넌트만 초기화
PhoneInput.init(document.querySelector('.my-form'));

// 값 가져오기
const value = PhoneInput.getValue('phone');
// { countryCode: '+82', phoneNumber: '010-1234-5678', fullNumber: '+821012345678' }

// 값 설정하기
PhoneInput.setValue('phone', '+81', '09012345678');

// 전화번호 포맷팅
const formatted = PhoneInput.formatPhoneNumber('01012345678', '+82');
// '010-1234-5678'
```

### 2.9 국가별 포맷팅 규칙

| 국가 | 포맷 | 예시 |
|------|------|------|
| 한국 (+82) | 3-4-4 / 2-4-4 | 010-1234-5678 / 02-1234-5678 |
| 일본 (+81) | 3-4-4 | 090-1234-5678 |
| 미국/캐나다 (+1) | 3-3-4 | 123-456-7890 |
| 호주 (+61) | 4-3-3 | 0412-345-678 |
| 중국 (+86) | 3-4-4 | 138-1234-5678 |
| 영국 (+44) | 4-3-3 | 7123-456-789 |
| 프랑스 (+33) | 1-2-2-2-2 | 6-12-34-56-78 |
| 기타 | 숫자만 | 최대 15자리 |

### 2.10 적용된 페이지

- **회원가입** (`/register`) - 연락처 입력
- **예약 조회** (`/booking/lookup`) - 전화번호 검색

---

## 3. 헤더/푸터 컴포넌트

### 3.1 헤더 컴포넌트

공통 네비게이션과 사용자 메뉴를 제공합니다.

**파일**: `resources/views/partials/header.php`

```php
<?php
$pageTitle = '페이지 제목';
include BASE_PATH . '/resources/views/partials/header.php';
?>
```

**주요 기능**:
- 반응형 네비게이션
- 다국어 전환
- 다크모드 토글
- 로그인/사용자 메뉴

### 3.2 푸터 컴포넌트

저작권 및 링크를 제공합니다.

**파일**: `resources/views/partials/footer.php`

```php
<?php
include BASE_PATH . '/resources/views/partials/footer.php';
?>
```

**주요 기능**:
- 저작권 표시
- 이용약관/개인정보처리방침 링크
- 문의하기 링크

---

## 4. 페이지 컴포넌트

### 4.1 서비스 목록 페이지

서비스 카탈로그를 표시하는 페이지입니다.

**파일**: `resources/views/customer/services.php`
**URL**: `/services`, `/services?category={id}`

**기능**:
- 카테고리별 필터링
- 서비스 카드 그리드 (이미지, 가격, 소요시간)
- 상세보기/예약하기 버튼
- CTA(Call-to-Action) 섹션

**데이터 요구사항**:
```php
// DB에서 로드되는 데이터
$categories = [...]; // rzx_categories 테이블
$services = [...];   // rzx_services 테이블 (is_active = 1)
```

### 4.2 서비스 상세 페이지

개별 서비스의 상세 정보를 표시합니다.

**파일**: `resources/views/customer/services/detail.php`
**URL**: `/services/{id}`

**기능**:
- Breadcrumb 네비게이션
- 서비스 이미지, 가격, 소요시간
- 서비스 설명
- 예약하기 버튼
- 관련 서비스 추천 (동일 카테고리)

**번역 키 사용**:
```php
__('services.title')           // 서비스
__('services.view_detail')     // 상세보기
__('services.book_now')        // 예약하기
__('services.about_service')   // 서비스 소개
__('services.related_services') // 관련 서비스
```

---

## 5. 관리자 탭 컴포넌트

### 5.1 시스템 설정 탭 네비게이션

관리자 시스템 설정 페이지에서 사용되는 탭 네비게이션 컴포넌트입니다.

**파일**: `resources/views/admin/settings/system/_tabs.php`

**기능**:
- 정보관리, 캐시관리, 모드관리, 로그관리 탭 전환
- 현재 활성 탭 하이라이트
- 다크모드 지원
- 반응형 디자인 (모바일에서 스크롤 가능)

### 5.2 탭 구조

```php
// 각 탭 페이지에서 설정
$currentSystemTab = 'info'; // 'info', 'cache', 'mode', 'logs'

// 탭 네비게이션 include
include __DIR__ . '/_tabs.php';
```

**사용 가능한 탭**:

| 탭 ID | URL | 설명 |
|-------|-----|------|
| `info` | `/admin/settings/system/info` | 시스템 정보 (앱, 서버, PHP, DB) |
| `cache` | `/admin/settings/system/cache` | 캐시 관리 (뷰, 설정, 라우트) |
| `mode` | `/admin/settings/system/mode` | 모드 관리 (디버그, 유지보수) |
| `logs` | `/admin/settings/system/logs` | 로그 관리 (조회, 삭제) |

### 5.3 탭 페이지 구현 패턴

각 탭 페이지는 동일한 레이아웃 패턴을 따릅니다:

```php
<?php
// 1. 초기화
require_once __DIR__ . '/../_init.php';

$pageTitle = __('admin.settings.system.tabs.info') . ' - Admin';
$currentSettingsPage = 'system';
$currentSystemTab = 'info';  // 현재 탭

// 2. POST 처리 (필요시)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 액션 처리
}

// 3. 콘텐츠 버퍼링
ob_start();
?>

<?php include __DIR__ . '/_tabs.php'; ?>

<!-- 탭 콘텐츠 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6">
    <!-- 내용 -->
</div>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layout.php';
```

### 5.4 캐시 관리 UI

**기능**:
- 뷰 캐시 삭제 (컴파일된 템플릿)
- 설정 캐시 삭제 (config.php)
- 라우트 캐시 삭제 (routes.php)
- 전체 캐시 삭제

**캐시 타입 표시**:
```php
// 캐시 크기 계산
$viewCacheSize = formatSize(getDirSize(BASE_PATH . '/storage/framework/views'));

// 캐시 존재 여부 표시
$configCacheExists = file_exists(BASE_PATH . '/storage/framework/cache/config.php');
```

### 5.5 모드 관리 UI

**표시 정보**:
- 디버그 모드 상태 (읽기 전용)
- 유지보수 모드 토글 (활성화/비활성화)
- 환경 정보 (production/development)

**유지보수 모드 파일**:
```
BASE_PATH/storage/framework/down
```

### 5.6 로그 관리 UI

**기능**:
- 로그 파일 목록 표시 (파일명, 크기, 수정일)
- 로그 내용 보기 (AJAX 로드)
- 개별/전체 로그 삭제

**로그 경로**:
```
BASE_PATH/storage/logs/*.log
```

---

## 6. 이미지 크롭 컴포넌트

프로필 사진 등 이미지 업로드 시 크롭/편집 기능을 제공하는 모달 컴포넌트입니다.

### 6.1 기능

- **Cropper.js 기반**: 검증된 라이브러리 활용
- **확대/축소**: 슬라이더 또는 버튼으로 줌 조절
- **회전**: 90° 단위 좌/우 회전
- **드래그 이동**: 이미지 위치 조정
- **비율 고정**: 설정된 가로세로 비율 유지
- **Base64 출력**: 폼 전송에 최적화
- **드래그 앤 드롭**: 이미지 파일 드롭 지원
- **다크모드 지원**: 라이트/다크 테마 자동 적용
- **다국어 지원**: ko/en/ja 번역 내장

### 6.2 파일 구조

```
skins/default/components/
├── image_cropper.php         # 크롭 모달 컴포넌트
└── register_fields.php       # 회원가입 필드 (profile_photo와 연동)
```

### 6.3 기본 사용법

```php
<?php
$cropperConfig = [
    'id' => 'my_cropper',           // 고유 ID
    'inputName' => 'cropped_image', // hidden 필드명
    'aspectRatio' => 1,             // 가로세로 비율 (1 = 정사각형)
    'outputWidth' => 400,           // 출력 너비 (px)
    'outputHeight' => 400,          // 출력 높이 (px)
    'outputFormat' => 'image/jpeg', // MIME 타입
    'outputQuality' => 0.9,         // 품질 (0-1)
    'translations' => [...]         // 선택: 커스텀 번역
];
include BASE_PATH . '/skins/default/components/image_cropper.php';
?>

<!-- 트리거 버튼 -->
<button type="button" onclick="ImageCropper.trigger('my_cropper')">
    이미지 선택
</button>
```

### 6.4 설정 옵션

| 옵션 | 타입 | 기본값 | 설명 |
|------|------|--------|------|
| `id` | string | `'image_cropper'` | 컴포넌트 고유 ID |
| `inputName` | string | `'cropped_image'` | 폼 필드 이름 |
| `aspectRatio` | float | `1` | 가로세로 비율 (16/9, 4/3 등) |
| `outputWidth` | int | `400` | 출력 이미지 너비 |
| `outputHeight` | int | `400` | 출력 이미지 높이 |
| `outputFormat` | string | `'image/jpeg'` | 출력 포맷 |
| `outputQuality` | float | `0.9` | JPEG 품질 (0.0-1.0) |
| `cropBoxResizable` | bool | `true` | 크롭 박스 크기 조절 |
| `translations` | array | `[]` | 커스텀 번역 |

### 6.5 JavaScript API

```javascript
// 모달 열기 + 파일 선택
ImageCropper.trigger('my_cropper');

// 모달만 열기
ImageCropper.open('my_cropper');

// 모달 닫기
ImageCropper.close('my_cropper');

// 확대/축소 (양수: 확대, 음수: 축소)
ImageCropper.zoom('my_cropper', 0.1);

// 회전 (90, -90)
ImageCropper.rotate('my_cropper', 90);

// 초기화
ImageCropper.reset('my_cropper');

// 크롭 적용
ImageCropper.apply('my_cropper');

// 콜백 설정
ImageCropper.setOnApply('my_cropper', (dataUrl, canvas, id) => {
    console.log('크롭 완료:', dataUrl.substring(0, 50));
});
```

### 6.6 이벤트

```javascript
// 크롭 완료 이벤트 리스닝
document.addEventListener('imageCropped', function(e) {
    const { id, dataUrl, canvas } = e.detail;

    // 미리보기 업데이트
    document.getElementById('preview').src = dataUrl;
});
```

### 6.7 서버 측 처리

```php
use RzxLib\Core\Helpers\ImageHelper;

// Base64 이미지 저장
$imageHelper = new ImageHelper();
$base64Data = $_POST['cropped_image'];

$result = $imageHelper->saveBase64Image($base64Data, 'profile_' . $userId);

if ($result['success']) {
    $filePath = $result['relative_path'];  // /uploads/profiles/profile_123.jpg
    // DB에 경로 저장
}
```

### 6.8 회원가입 연동

`register_fields.php`에서 `profile_photo` 필드 선택 시 자동으로 이미지 크롭이 연동됩니다:

```php
// 관리자 설정에서 profile_photo 활성화 시
$registerFields = ['name', 'email', 'password', 'phone', 'profile_photo'];
include 'skins/default/components/register_fields.php';

// 자동으로:
// 1. 이미지 크롭 컴포넌트 포함
// 2. 미리보기 썸네일 표시
// 3. 삭제 버튼 제공
// 4. cropped_profile_photo 필드에 Base64 저장
```

### 6.9 번역 키

| 키 | 한국어 | English | 日本語 |
|----|--------|---------|--------|
| `edit_image` | 이미지 편집 | Edit Image | 画像を編集 |
| `select_image` | 이미지 선택 | Select Image | 画像を選択 |
| `drag_drop_image` | 또는 이미지를 여기에 드래그하세요 | Or drag and drop an image here | または画像をここにドラッグ＆ドロップ |
| `zoom_in` | 확대 | Zoom In | 拡大 |
| `zoom_out` | 축소 | Zoom Out | 縮小 |
| `rotate_left` | 왼쪽 회전 | Rotate Left | 左回転 |
| `rotate_right` | 오른쪽 회전 | Rotate Right | 右回転 |
| `reset` | 초기화 | Reset | リセット |
| `cancel` | 취소 | Cancel | キャンセル |
| `apply` | 적용 | Apply | 適用 |

### 6.10 적용된 페이지

- **회원가입** (`/register`) - 프로필 사진 등록
- **프로필 수정** (`/mypage/edit`) - 프로필 사진 변경 (예정)

---

## 버전 정보

- **문서 버전**: 1.4.0
- **최종 수정일**: 2026-03-05
- **적용 대상**: RezlyX UI Components

### 변경 이력

| 버전 | 날짜 | 변경 내용 |
|------|------|-----------|
| 1.4.0 | 2026-03-05 | 이미지 크롭 컴포넌트 추가 (Cropper.js 기반) |
| 1.3.0 | 2026-03-05 | 전화번호 컴포넌트 180개+ 국가 지원, 네이티브 국가명 시스템, 검색 기능 추가 |
| 1.2.0 | 2026-03-02 | 관리자 탭 컴포넌트, 시스템 설정 UI 추가 |
| 1.1.0 | 2026-03-01 | 서비스 페이지 컴포넌트 추가 |
| 1.0.0 | 2026-02-28 | 최초 작성 |
