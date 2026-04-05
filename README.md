# VosCMS

**Value Of Style CMS** — 플러그인 기반 범용 콘텐츠 관리 시스템

[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)]()

---

## 소개

VosCMS는 어떤 스타일의 웹사이트든 만들 수 있는 범용 CMS입니다.
코어는 가볍고, 플러그인으로 기능을 확장합니다.

- **기업 홈페이지** → 코어만 설치
- **미용실/살롱** → 코어 + 살롱 번들 플러그인
- **쇼핑몰** → 코어 + 커머스 플러그인 (예정)
- **클리닉** → 코어 + 클리닉 번들 플러그인 (예정)

---

## 코어 기능

| 기능 | 설명 |
|------|------|
| 📄 **페이지 관리** | 문서, 위젯, 외부 페이지 |
| 📋 **메뉴 관리** | 계층 메뉴, 사이트맵, 6가지 메뉴 타입 |
| 📝 **게시판** | 4종 스킨, 카테고리, 확장변수, 투표, 댓글 |
| 🧩 **위젯 빌더** | 드래그&드롭 배치 |
| 👥 **회원 관리** | 가입/로그인, 등급, 소셜 로그인 |
| 🌐 **다국어** | 13개국어 지원 (ko, ja, en, zh_CN, zh_TW, de, es, fr, id, mn, ru, tr, vi) |
| 🎨 **스킨/레이아웃** | 테마 시스템 |
| 🔌 **플러그인** | plugin.json 기반 설치/활성화/비활성화/삭제 |
| ⭐ **포인트** | 적립/사용/등급 |
| 🔔 **푸시 알림** | 웹 푸시 |

---

## 플러그인

플러그인으로 기능을 확장합니다. `plugins/` 디렉토리에 설치.

### 공식 플러그인

| 플러그인 | 설명 |
|----------|------|
| [vos-salon](https://github.com/THEVOSjp) | 살롱 번들 (예약 + 서비스 + 스태프 + 번들) |
| [vos-pos](https://github.com/THEVOSjp) | POS 시스템 |
| [vos-kiosk](https://github.com/THEVOSjp) | 셀프 체크인 키오스크 |
| [vos-attendance](https://github.com/THEVOSjp) | 직원 근태 관리 |

### plugin.json 예시

```json
{
  "id": "vos-salon",
  "name": { "ko": "살롱 관리", "en": "Salon Management" },
  "version": "1.0.0",
  "routes": {
    "admin": [
      { "path": "reservations", "view": "reservations/index.php" }
    ]
  },
  "menus": {
    "admin": [{
      "title": { "ko": "예약 관리", "en": "Reservations" },
      "icon": "SVG path",
      "items": [
        { "title": { "ko": "예약 관리" }, "route": "reservations" },
        { "title": { "ko": "캘린더" }, "route": "reservations/calendar" }
      ]
    }]
  }
}
```

---

## 요구사항

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- PDO, mbstring, json, openssl, fileinfo

---

## 설치

### 1. 소스 다운로드

```bash
git clone https://github.com/THEVOSjp/VosCMS.git
cd VosCMS
```

### 2. 의존성 설치

```bash
composer install
```

### 3. 웹 브라우저로 설치

```
https://your-domain.com/install.php
```

5단계 설치 마법사:
1. 환경 체크 (PHP/확장모듈/권한)
2. 데이터베이스 설정
3. 테이블 생성
4. 사이트 설정 + 관리자 계정
5. 완료

### 4. 플러그인 설치 (선택)

`plugins/` 디렉토리에 플러그인을 복사한 후
관리자 > 플러그인 페이지에서 설치/활성화

---

## 디렉토리 구조

```
VosCMS/
├── index.php                 # 엔트리 포인트
├── install.php               # 설치 마법사
├── .env                      # 환경 설정 (설치 시 생성)
├── config/                   # 앱 설정
├── database/
│   └── migrations/
│       └── core/             # 코어 마이그레이션 (5개)
├── rzxlib/                   # 코어 라이브러리
│   └── Core/
│       ├── Auth/             # 인증
│       ├── I18n/             # 다국어
│       ├── Plugin/           # PluginManager, Hook
│       └── Skin/             # 스킨 시스템
├── resources/
│   ├── views/
│   │   ├── admin/            # 관리자 UI
│   │   └── customer/         # 프론트 UI
│   └── lang/                 # 13개 언어 파일
├── plugins/                  # 플러그인 디렉토리
├── skins/                    # 스킨
├── layouts/                  # 레이아웃
├── widgets/                  # 위젯
├── storage/                  # 업로드/캐시
└── docs/                     # 문서
```

---

## 브랜드

| 항목 | 값 |
|------|-----|
| 이름 | VosCMS |
| 풀네임 | Value Of Style CMS |
| 도메인 | voscms.com |
| 계보 | THE VOS (THE Value Of Style) |

---

## 관련 프로젝트

- **[RezlyX](https://github.com/THEVOSjp/RezlyX)** — VosCMS + 살롱 번들 + POS + 키오스크 + 근태. 미용실/살롱 특화 올인원 솔루션.

---

## 라이선스

Proprietary. © 2026 THE VOS Inc.
