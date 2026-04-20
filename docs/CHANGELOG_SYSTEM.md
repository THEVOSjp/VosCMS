# VosCMS Changelog 시스템

> **버전**: 2.3.0 · **최종 업데이트**: 2026-04-20

VosCMS 2.3.0 부터 도입된 **버전별 변경 이력 관리 시스템**. 마크다운 파일 업로드 → 자동 파싱 → DB 저장 → 다국어 지원 → (향후) AI 번역 워크플로우.

## 1. 개요

### 설계 원칙

- **MD 파일 업로드만으로 자동 정리** — 매번 부분 잘라 붙일 필요 없음
- **버전 단위 idempotent 병합** — 재업로드 안전 (내용 동일하면 스킵, 변경됐으면 업데이트)
- **다국어 13개 locale 지원** — `CHANGELOG.{locale}.md` 파일명 컨벤션
- **AI 번역 준비** — Gemma 4 연동 인프라만 만들어두고 GPU 준비되는 순간 활성화
- **설정 = 모양 + 기능 / 편집 = 데이터** 원칙 준수

### 구성 요소

| 계층 | 파일 |
|---|---|
| DB | `rzx_changelog` 테이블 (버전 × locale) |
| 파서 | `rzxlib/Core/Changelog/ChangelogParser.php` |
| 임포터 | `rzxlib/Core/Changelog/ChangelogImporter.php` |
| CLI | `scripts/import-changelog.php` |
| 번역 인터페이스 | `rzxlib/Core/Translate/{TranslatorInterface, NullTranslator, TranslatorFactory}.php` |
| 번역 설정 | `config/translator.php` |
| 시스템 페이지 등록 | `config/system-pages.php` (`slug=changelog`) |
| 공개 뷰 | `resources/views/system/changelog/index.php` |
| 편집 UI | `resources/views/system/changelog/edit.php` |
| 관리 API | `resources/views/admin/site/changelog-api.php` |
| 스킨 | `skins/page/changelog/skin.json` |
| 라우트 | `routes/web.php` 의 `/admin/site/changelog/api` |
| 배포 자동화 | `scripts/deploy-voscms.sh` 의 `1.5/5` 단계 |

## 2. 데이터 모델

```sql
CREATE TABLE `rzx_changelog` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `version`            VARCHAR(50) NOT NULL,
    `version_label`      VARCHAR(200),
    `release_date`       DATE NOT NULL,
    `locale`             VARCHAR(10) NOT NULL DEFAULT 'ko',
    `content`            LONGTEXT,
    `content_hash`       CHAR(32),
    `translation_source` ENUM('original','ai','manual') DEFAULT 'original',
    `source_locale`      VARCHAR(10),
    `source_hash`        CHAR(32),
    `is_internal`        TINYINT(1) DEFAULT 0,
    `is_active`          TINYINT(1) DEFAULT 1,
    `created_at`, `updated_at`,
    UNIQUE KEY `uk_version_locale` (`version`, `locale`),
    KEY `idx_date` (`release_date` DESC)
);
```

**핵심 컬럼**:
- `(version, locale)` UNIQUE — 한 버전이 locale 당 1행
- `content_hash` (md5) — 재업로드 시 변경 감지
- `translation_source` — `original` (원본 업로드) / `ai` (자동 번역) / `manual` (수동 번역)
- `source_hash` — AI/수동 번역 시점의 원본 해시 → 원본 변경되면 "재번역 필요" 표시
- `is_internal` — 내부용 플래그 (공개 페이지 숨김)

## 3. 마크다운 포맷 (엄격)

### 버전 헤더

```markdown
## [버전] - YYYY-MM-DD — 제목(선택)
```

**규칙**:
- `##` + 공백 필수
- `[ ]` 안에 시맨틱 버전 (숫자.숫자.숫자) — 프리픽스 `VosCMS` 허용, pre-release `2.1.1-hotfix` 지원
- 공백-하이픈-공백 (` - `)
- 날짜 `YYYY-MM-DD`
- (선택) 공백-em dash-공백 + 헤드라인

**허용**:
- `## [VosCMS 2.2.2] - 2026-04-19 — stats 위젯 개선`
- `## [2.2.2] - 2026-04-19`
- `## [2.1.1-hotfix] - 2026-04-16`

**거부**:
- `## [v2.2.2]` (v 접두사)
- `## 2.2.2 - 2026-04-19` (대괄호 누락)
- `## [2.2.2] 2026-04-19` (하이픈 누락)

### 섹션 화이트리스트

**공개** (`is_internal=0`):
- `Added` (➕ 추가) · 녹색
- `Changed` (✏️ 변경) · 파랑
- `Deprecated` (⚠️ 폐지 예정) · 노랑
- `Removed` (➖ 제거) · 빨강
- `Fixed` (🐛 수정) · 주황
- `Security` (🔒 보안) · 로즈

**비공개** (`is_internal=1`, 기본 숨김):
- `Infrastructure` · 🏗️
- `Internal` · 🔧
- `Refactor` · ♻️
- `Chore` · 🧹
- `Docs` · 📚
- `Test` · 🧪

섹션명 뒤에 `— 부제목` 자유 추가 가능:
```markdown
### Added — stats 위젯 CRUD
- feat: ...
```

**화이트리스트 외 섹션**은 경고 + 기본적으로 공개 처리.

## 4. CLI 사용법

### 기본 임포트

```bash
php8.3 scripts/import-changelog.php --file=docs/CHANGELOG.md --locale=ko
```

### Dry-run (미리보기)

```bash
php8.3 scripts/import-changelog.php --file=docs/CHANGELOG.md --locale=ko --dry-run
```

출력:
```
📋 Changelog import — /var/www/voscms/docs/CHANGELOG.md → locale=ko (DRY RUN)
  블록 42 개 파싱됨
  ⚠ 경고 8 건
    - 버전 2.1.5: 알 수 없는 섹션 "### DB Changes" — 화이트리스트에 없음. 공개로 가정.

예상 결과:
  🆕 신규 3 건: 2.2.2, 2.2.1, 2.2.0
  ✏ 변경 1 건: 2.1.5
  ⏸ 스킵 38 건 (내용 동일)

DRY RUN — 변경사항 없음.
```

### 병합 동작

- DB 에 없음 → INSERT
- 있고 `content_hash` 동일 → 스킵
- 있고 `content_hash` 다름 → UPDATE (원본만, 수동 번역은 보호)
- `translation_source = 'manual'` → **건드리지 않음** (관리자 수작업 보호)
- MD 파일엔 없는데 DB 엔 있음 → **건드리지 않음** (안전)

### --silent 옵션

배포 스크립트에서 사용. 에러 외엔 출력 억제.

## 5. 배포 자동화

`scripts/deploy-voscms.sh` 의 `1.5/5 Changelog import` 스텝:

```bash
if [ -f "$SRC_DIR/docs/CHANGELOG.md" ]; then
    php8.3 "$SRC_DIR/scripts/import-changelog.php" \
        --file="$SRC_DIR/docs/CHANGELOG.md" --locale=ko --silent
fi
for LANG in en ja zh_CN zh_TW de es fr id mn ru tr vi; do
    LANG_FILE="$SRC_DIR/docs/CHANGELOG.$LANG.md"
    [ -f "$LANG_FILE" ] && php8.3 ... --locale=$LANG --silent
done
```

**워크플로우**:
1. 개발 중 `docs/CHANGELOG.md` 에 버전 추가
2. `deploy-voscms.sh` 실행
3. 개발 DB 에 자동 병합 → mysqldump 가 프로덕션으로 복사 → 프로덕션도 자동 반영

## 6. 관리자 UI

### 접근 URL

- `https://vos.21ces.com/changelog` — 공개 페이지
- `https://vos.21ces.com/changelog/settings` — 모양·기능 설정 (스킨/권한)
- `https://vos.21ces.com/changelog/edit` — **데이터 관리** (MD 업로드·버전·번역)

### 편집 페이지 기능

1. **MD 업로드** — 파일 선택 → 언어 자동 감지 (`CHANGELOG.en.md` → en)
2. **미리보기** — 신규/변경/스킵 건수 프리뷰 (적용 전)
3. **버전 목록** — 다국어 커버리지 매트릭스 (O=original, A=AI, M=manual)
4. **항목별 토글** — is_active / is_internal (미래 구현)
5. **삭제** — 버전별 삭제 (UI 는 부분 구현)
6. **AI 번역 버튼** — 현재 비활성 (Phase 2 준비)

## 7. 번역 인프라

### 현재 (Phase 1) — NullTranslator

`config/translator.php`:
```php
return [
    'driver' => 'null',
    'gemma' => [
        'endpoint' => 'https://ai.21ces.com/translate',
        'timeout'  => 30,
        'model'    => 'gemma-4',
    ],
];
```

`NullTranslator` 가 기본값. `isAvailable()` 이 false 반환 → 관리자 UI 번역 버튼 비활성.

### 미래 (Phase 2) — GemmaTranslator

GPU 서버 준비 후 활성화:

1. `rzxlib/Core/Translate/GemmaTranslator.php` 구현 (`TranslatorInterface`)
2. `config/translator.php` 의 `driver` 를 `'gemma'` 로 변경
3. `TranslatorFactory::make()` 의 match 블록에서 `'gemma' => new GemmaTranslator($config)` 활성화

이후 관리자 UI 번역 버튼 자동 활성화. API 호출 로직은 이미 존재.

### 번역 안전장치

- `translation_source='manual'` → AI 번역이 덮어쓰지 않음
- `source_hash` 저장 → 원본 변경 후 번역 오래됐는지 감지
- locale 체인 폴백 (현재 → en → ko → 아무거나) — 번역 없어도 빈 화면 안 뜸

## 8. 공개 페이지 스킨

`skins/page/changelog/skin.json` 변수:

| 카테고리 | 변수 | 설명 |
|---|---|---|
| 레이아웃 | `content_width` | max-w-3xl/4xl/5xl |
| 카드 | `card_variant` | filled / outlined / flat |
| 배지 | `badge_bg`, `badge_fg`, `badge_shape` | 버전 배지 스타일 |
| 섹션 색상 | `color_added`, `color_changed`, `color_fixed`, `color_removed`, `color_security`, `color_deprecated` | 섹션 타입별 색상 |
| 날짜 | `date_format` (iso/ko_long/en_long/dmy) · `show_relative_date` | "3일 전" 등 상대시간 |
| 표시 | `show_internal_sections` | 내부용 섹션 공개 여부 |
| 표시 | `max_versions` | 최신 N개만 (0=전체) |

표준 페이지 스킨 변수(`show_title`, `title_bg_*`, `content_bg`, `custom_css` 등)도 모두 지원.

## 9. 확장 방법

### 새 섹션 타입 추가

`rzxlib/Core/Changelog/ChangelogParser.php` 의 화이트리스트:
```php
public const PUBLIC_SECTIONS = ['Added', 'Changed', ..., 'NewType'];
public const INTERNAL_SECTIONS = ['Infrastructure', ..., 'AnotherInternal'];
```

스킨에 색상도 추가: `skin.json` 의 `color_newtype` 변수 + 공개 뷰의 `sectionColors` 맵핑.

### 새 번역 드라이버 (Google Translate 등)

1. `rzxlib/Core/Translate/GoogleTranslator.php` 작성 (`TranslatorInterface` 구현)
2. `TranslatorFactory::make()` 의 match 블록 확장
3. `config/translator.php` 의 driver 변경

### 관리자 편집 UI 기능 추가

`resources/views/system/changelog/edit.php` 및 `resources/views/admin/site/changelog-api.php` 수정. API action 추가 시 switch 케이스 추가.

## 10. 알려진 제약 · 향후 개선

- **일괄 삭제 UI 부분 구현** — 현재 버전별 버튼만 자리. 대량 삭제 시 DB 직접 처리
- **프론트 편집 기능** — 프론트에서 CHANGELOG.md 직접 편집(WYSIWYG) 미구현 — MD 업로드로만
- **AI 번역** — Phase 2 (GPU 준비) 대기 중
- **버전 diff 뷰** — content 변경 시 diff 표시 (미구현)
- **RSS/Atom 피드** — changelog 구독 기능 (미구현)

## 참고

- [CHANGELOG.md](CHANGELOG.md) — 실제 버전 이력
- [PAGE_ADMIN_GUIDE.md](PAGE_ADMIN_GUIDE.md) — 페이지 관리 시스템 가이드 (탭 구조 · edit_view)
- [PAGE_SYSTEM.md](PAGE_SYSTEM.md) — 페이지 시스템 개념
- [I18N.md](I18N.md) — 다국어 시스템
