# VosCMS Changelog 시스템 매뉴얼

> 작성일: 2026-04-21
> 대상: VosCMS 관리자
> 접근 경로: `https://your-domain.com/changelog`

---

## 목차

1. [시스템 개요](#1-시스템-개요)
2. [마크다운 파일 작성법](#2-마크다운-파일-작성법)
3. [업로드 & 병합 (편집 페이지)](#3-업로드--병합-편집-페이지)
4. [버전 목록 관리](#4-버전-목록-관리)
5. [디자인 설정](#5-디자인-설정)
6. [공개 페이지 구조](#6-공개-페이지-구조)
7. [다국어 처리](#7-다국어-처리)
8. [AI 번역 (Phase 2)](#8-ai-번역-phase-2)
9. [파일/경로 참조](#9-파일경로-참조)

---

## 1. 시스템 개요

### 1.1 기본 개념

Changelog 시스템은 **마크다운 파일(.md)을 업로드** 하면 자동으로 파싱·DB 저장·화면 출력까지 처리하는 버전 관리 페이지다.

```
CHANGELOG.md 작성
    ↓
/changelog/edit 에서 업로드
    ↓
ChangelogParser → 버전별 블록으로 파싱
    ↓
ChangelogImporter → DB UPSERT (rzx_changelog)
    ↓
/changelog 공개 페이지에서 렌더링
```

### 1.2 DB 구조 (rzx_changelog)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| `version` | VARCHAR(50) | 버전 번호 (예: 2.3.5) |
| `version_label` | VARCHAR(200) | 화면에 표시되는 버전 제목 |
| `release_date` | DATE | 릴리즈 날짜 |
| `locale` | VARCHAR(10) | 언어 코드 (ko, en, ja ...) |
| `content` | LONGTEXT | 원본 마크다운 내용 |
| `content_hash` | CHAR(32) | 내용 변경 감지용 MD5 해시 |
| `translation_source` | ENUM | original / ai / manual |
| `is_internal` | TINYINT | 0=공개, 1=내부 전용 |
| `is_active` | TINYINT | 0=숨김, 1=표시 |

**유니크 키:** `(version, locale)` — 동일 버전·언어 조합은 1행만 존재

### 1.3 접근 경로

| URL | 용도 |
|-----|------|
| `/changelog` | 공개 페이지 (방문자 열람) |
| `/changelog/edit` | 콘텐츠 편집 (관리자 전용) |
| `/changelog/settings` | 디자인 설정 (관리자 전용) |

---

## 2. 마크다운 파일 작성법

### 2.1 버전 헤더 형식

```markdown
## [VosCMS 2.3.5] - 2026-04-21 — 라이선스 자동 등록
```

**규칙:**
- `##` + 공백 + `[` 로 시작 (필수)
- 대괄호 안에 버전 번호 (접두사 자유)
- ` - ` (공백-하이픈-공백) 뒤에 날짜 `YYYY-MM-DD`
- ` — ` 뒤 헤드라인은 선택사항

**유효한 형식 예시:**
```markdown
## [VosCMS 2.3.5] - 2026-04-21 — 라이선스 자동 등록
## [2.3.5] - 2026-04-21
## [2.1.1-hotfix] - 2026-04-16
```

### 2.2 섹션 헤더

섹션은 `### 섹션명` 으로 구분한다. 섹션명 뒤에 ` — 부제목` 추가 가능.

**공개 섹션 (방문자에게 노출)**

| 섹션명 | 색상 기본값 | 의미 |
|--------|-----------|------|
| `Added` | 초록 | 신규 기능 추가 |
| `Changed` | 파랑 | 기존 기능 변경 |
| `Fixed` | 노랑 | 버그 수정 |
| `Removed` | 빨강 | 기능 제거 |
| `Security` | 진빨강 | 보안 패치 |
| `Deprecated` | 노랑 | 사용 중단 예정 |

**내부 섹션 (기본 비공개, 설정에서 표시 가능)**

| 섹션명 | 의미 |
|--------|------|
| `Infrastructure` | 서버/인프라 작업 |
| `Internal` | 내부 로직 변경 |
| `Refactor` | 코드 리팩토링 |
| `Chore` | 유지보수 작업 |
| `Docs` | 문서 작업 |
| `Test` | 테스트 추가 |

### 2.3 인라인 포맷

```markdown
**굵게** → <strong>
*기울임* → <em>
`코드` → <code> (스타일 자동 적용)
> 인용구 → <blockquote>
- 항목 → <li>
```

### 2.4 전체 예시

```markdown
## [VosCMS 2.3.5] - 2026-04-21 — 라이선스 자동 등록

### Fixed — LicenseClient 자동 등록 버그

`LicenseClient::check()` 에서 `$this->licenseKey` 가 갱신되지 않던 버그 수정.

- AdminRouter 에서 인스턴스 생성 후 `$_ENV` 갱신이 인스턴스 내부에 반영되지 않던 문제
- 동일 요청에서 `check()` 호출 시 여전히 unregistered 상태를 반환하던 현상 해소

### Changed — LicenseClient::check() 자동 등록 통합

- 라이선스 키 없을 시 **즉시 `/api/license/register` 자동 호출**
- 성공 시 `.env` 파일에 `LICENSE_KEY` 저장 후 verify 단계 진행
- `saveKeyToEnv()` 신규: 빈 `LICENSE_KEY=` 라인도 실제 키로 교체

### Infrastructure

- `version.json` 2.3.4 → 2.3.5
- GitHub 태그 `v2.3.5` 생성

---

## [VosCMS 2.3.4] - 2026-04-20 — 콘텐츠 오염 정화

### Fixed
...
```

> **팁:** 버전 구분선 `---` 은 있어도 없어도 파싱에 영향 없음.

### 2.5 다국어 파일 명명 규칙

| 파일명 | 언어 |
|--------|------|
| `CHANGELOG.md` | 기본 (업로드 시 언어 직접 선택) |
| `CHANGELOG.ko.md` | 한국어 자동 감지 |
| `CHANGELOG.en.md` | 영어 자동 감지 |
| `CHANGELOG.ja.md` | 일본어 자동 감지 |
| `CHANGELOG.zh_CN.md` | 중국어 간체 자동 감지 |

파일명에 언어 코드가 포함되어 있으면 업로드 시 자동으로 해당 언어로 지정된다.

---

## 3. 업로드 & 병합 (편집 페이지)

### 3.1 편집 페이지 접근

관리자로 로그인 후 `/changelog/edit` 접속.

> **[이미지: 편집 페이지 전체 화면]**

### 3.2 업로드 절차

**1단계 — 언어 선택**

언어 드롭다운에서 업로드할 언어를 선택한다.  
파일명에 언어 코드가 있으면 (`CHANGELOG.en.md`) 자동으로 해당 언어가 지정된다.

> **[이미지: 언어 선택 드롭다운]**

**2단계 — 파일 선택**

`.md` 파일을 선택한다.

**3단계 — 미리보기 (권장)**

`미리보기` 버튼을 클릭하면 **DB 변경 없이** 파싱 결과를 보여준다.

> **[이미지: 미리보기 결과 화면]**

미리보기 결과에서 각 버전은 아래 4가지 상태 중 하나로 표시된다:

| 아이콘 | 상태 | 의미 |
|--------|------|------|
| 🆕 신규 | created | DB에 없는 버전 → 새로 추가됨 |
| ✏ 변경 | updated | 내용이 바뀐 버전 → 덮어쓰기 |
| ⏸ 동일 | skipped | 내용이 같음 → 건너뜀 |
| 🛡 보호 | protected | `manual` 번역 → 절대 덮어쓰지 않음 |

**4단계 — 업로드 & 병합 실행**

내용이 맞으면 `업로드 & 병합` 버튼으로 실제 DB에 반영한다.

결과 통계 예시:
```
✅ 완료 — 신규: 1 / 변경: 0 / 동일: 12 / 보호: 0
```

### 3.3 병합 규칙 (중요)

- **덮어쓰기 조건:** `translation_source = 'original'` 이고 `content_hash` 가 다를 때만
- **보호 조건:** `translation_source = 'manual'` 이면 절대 덮어쓰지 않음
- **삭제 없음:** 파일에 없는 버전은 DB에서 삭제되지 않음 (안전한 UPSERT)
- **중복 없음:** `(version, locale)` 유니크 키로 동일 버전·언어 중복 방지

### 3.4 경고 메시지

파싱 중 형식 오류가 있으면 경고(`warnings[]`)가 표시된다. 오류가 있는 버전은 건너뛰고 나머지는 정상 처리된다.

---

## 4. 버전 목록 관리

### 4.1 버전 목록 테이블

편집 페이지 하단에 전체 버전 목록이 표시된다.

> **[이미지: 버전 목록 테이블]**

**컬럼 설명:**

| 컬럼 | 설명 |
|------|------|
| 버전 | 버전 번호 |
| 날짜 | 릴리즈 날짜 |
| 다국어 커버리지 | 언어별 번역 상태 매트릭스 |
| Internal | 내부 전용 여부 |
| Active | 공개/숨김 여부 |
| 삭제 | 해당 행 삭제 버튼 |

### 4.2 다국어 커버리지 매트릭스

각 언어 코드 셀에 표시되는 배지의 의미:

| 배지 | 색상 | 의미 |
|------|------|------|
| O | 초록 | original — 직접 업로드한 원본 |
| A | 노랑 | ai — AI 자동 번역 |
| M | 파랑 | manual — 수동으로 편집한 번역 |
| (빈칸) | 회색 | 미번역 |

### 4.3 항목 삭제

삭제 버튼은 **해당 `(version, locale)` 행 하나만** 삭제한다.  
동일 버전의 다른 언어 행은 영향받지 않는다.

> **주의:** 삭제 후 복구 불가. 재업로드로만 복원 가능.

---

## 5. 디자인 설정

### 5.1 설정 페이지 접근

`/changelog/settings` 또는 관리자 패널 → 사이트 → 페이지 → changelog → 설정.

> **[이미지: 설정 페이지]**

### 5.2 레이아웃

| 설정 | 옵션 | 설명 |
|------|------|------|
| 콘텐츠 너비 | max-w-3xl / **max-w-4xl** / max-w-5xl | 페이지 최대 너비 |

### 5.3 카드 스타일

| 설정 | 옵션 | 설명 |
|------|------|------|
| 카드 스타일 | **filled** / outlined / flat | filled=배경색, outlined=테두리만, flat=구분선만 |

### 5.4 버전 배지

| 설정 | 기본값 | 설명 |
|------|--------|------|
| 배지 배경색 | `#eef2ff` | 버전 번호 배지 배경 |
| 배지 글자색 | `#4338ca` | 버전 번호 배지 텍스트 |
| 배지 모양 | **pill** / rounded / square | pill=둥근 알약형 |

### 5.5 섹션 색상

각 섹션 타입별로 색상을 개별 지정할 수 있다.

| 섹션 | 기본 색상 |
|------|----------|
| Added | `#22c55e` (초록) |
| Changed | `#3b82f6` (파랑) |
| Fixed | `#f59e0b` (노랑) |
| Removed | `#ef4444` (빨강) |
| Security | `#dc2626` (진빨강) |
| Deprecated | `#eab308` (노랑) |

### 5.6 날짜 형식

| 옵션 | 출력 예시 |
|------|----------|
| `iso` | 2026-04-21 |
| `ko_long` | 2026년 4월 21일 |
| `en_long` | April 21, 2026 |
| `dmy` | 21/04/2026 |

**상대 날짜 표시** 켜면 날짜 옆에 "3일 전", "2개월 전" 같은 상대 시간이 추가로 표시된다.

### 5.7 표시 옵션

| 설정 | 기본값 | 설명 |
|------|--------|------|
| 내부 섹션 표시 | OFF | Infrastructure, Internal, Refactor 등 표시 여부 |
| 최대 버전 수 | 0 (전체) | 0=전부, N=최신 N개만 표시 |

### 5.8 커스텀

| 설정 | 설명 |
|------|------|
| 커스텀 CSS | 추가 CSS 직접 입력 |
| 상단 HTML | 콘텐츠 위에 삽입할 HTML |
| 하단 HTML | 콘텐츠 아래에 삽입할 HTML |

---

## 6. 공개 페이지 구조

### 6.1 렌더링 흐름

```
방문자 /changelog 접속
    ↓
현재 로케일 확인 (세션/쿠키)
    ↓
rzx_changelog WHERE is_active=1 AND locale=:locale
    ↓
데이터 없으면 폴백 체인 시도 (현재 언어 → en → ko)
    ↓
release_date DESC 정렬 → 버전별 카드 렌더링
    ↓
마크다운 → HTML 변환 (서버사이드)
```

### 6.2 버전 카드 구조

각 버전은 카드 형태로 표시된다:

```
┌─────────────────────────────────────┐
│  [v2.3.5]  2026-04-21 (3일 전)      │  ← 버전 배지 + 날짜
│  라이선스 자동 등록                   │  ← 헤드라인
├─────────────────────────────────────┤
│  ● Fixed — LicenseClient 버그        │  ← 섹션 헤더 (색상 도트)
│    - 항목 1                          │
│    - 항목 2                          │
│                                     │
│  ● Changed — 자동 등록 통합           │
│    - `check()` 내부 등록 처리         │
└─────────────────────────────────────┘
```

### 6.3 관리자 전용 UI

관리자 로그인 상태에서 `/changelog` 접속 시 우측 상단에 `편집` / `설정` 버튼이 나타난다.

---

## 7. 다국어 처리

### 7.1 지원 언어 (13개)

`ko`, `en`, `ja`, `zh_CN`, `zh_TW`, `de`, `es`, `fr`, `id`, `mn`, `ru`, `tr`, `vi`

### 7.2 폴백 체인

방문자의 현재 언어로 데이터가 없을 때 자동으로 다른 언어로 대체 표시한다.

```
현재 언어 (예: ja) → 없으면 en → 없으면 ko
```

폴백 발생 시 페이지 상단에 안내 메시지가 표시된다.

### 7.3 다국어 콘텐츠 추가 방법

**방법 A — 언어별 파일 업로드**

```
CHANGELOG.ko.md → /changelog/edit 에서 ko 로 업로드
CHANGELOG.en.md → /changelog/edit 에서 en 으로 업로드
CHANGELOG.ja.md → /changelog/edit 에서 ja 로 업로드
```

각 언어 파일을 별도로 업로드하면 된다. 동일 버전이면 UPSERT 처리된다.

**방법 B — AI 번역 (Phase 2, 현재 비활성)**

AI 번역 기능이 활성화되면 원본 업로드 후 버튼 하나로 전 언어 번역이 가능하다.

### 7.4 번역 보호

`translation_source = 'manual'` 로 설정된 항목은 이후 파일 재업로드나 AI 번역으로 절대 덮어쓰이지 않는다.  
수동으로 교정한 번역을 보호하려면 DB에서 `translation_source` 를 `manual` 로 변경한다.

---

## 8. AI 번역 (Phase 2)

> **현재 상태: 비활성 (GPU 서버 준비 중)**

### 8.1 활성화 조건

`config/translator.php` 에서 드라이버 변경:

```php
'driver' => 'gemma',  // 현재 'null'
'gemma' => [
    'endpoint' => 'https://ai.21ces.com/translate',
    'model' => 'gemma-4',
    'timeout' => 30,
],
```

활성화되면 편집 페이지의 `🤖 AI 번역 저장` 버튼이 활성화된다.

### 8.2 동작 방식

1. 원본 언어(`ko`) 업로드
2. `AI 번역 저장` 클릭
3. 지정한 대상 언어로 자동 번역 → DB 저장
4. `translation_source = 'ai'` 로 기록
5. 원본 내용이 바뀌면 `source_hash` 불일치 → "재번역 필요" 감지 가능

### 8.3 보호 규칙

- `translation_source = 'manual'` 항목은 AI 번역으로 덮어쓰지 않음
- 이미 해당 언어 행이 있고 `manual` 이면 스킵

---

## 9. 파일/경로 참조

| 역할 | 경로 |
|------|------|
| 공개 뷰 | `resources/views/system/changelog/index.php` |
| 편집 UI | `resources/views/system/changelog/edit.php` |
| API 핸들러 | `resources/views/admin/site/changelog-api.php` |
| 파서 | `rzxlib/Core/Changelog/ChangelogParser.php` |
| 임포터 | `rzxlib/Core/Changelog/ChangelogImporter.php` |
| 번역 인터페이스 | `rzxlib/Core/Translate/TranslatorInterface.php` |
| NullTranslator | `rzxlib/Core/Translate/NullTranslator.php` |
| TranslatorFactory | `rzxlib/Core/Translate/TranslatorFactory.php` |
| 번역 설정 | `config/translator.php` |
| 스킨 정의 | `skins/page/changelog/skin.json` |
| CLI 임포트 스크립트 | `scripts/import-changelog.php` |
| DB 마이그레이션 | `database/migrations/032_create_changelog.sql` |

---

## 부록 — CLI로 직접 임포트

서버에서 직접 마크다운을 임포트할 때:

```bash
php scripts/import-changelog.php CHANGELOG.md ko
php scripts/import-changelog.php CHANGELOG.en.md en
```

관리자 UI를 거치지 않고 동일한 파서/임포터를 사용한다.

---

*이미지는 추후 추가 예정.*
