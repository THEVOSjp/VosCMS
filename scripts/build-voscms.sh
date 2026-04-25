#!/bin/bash
#
# VosCMS 코어 배포 빌드 스크립트
# 사용법: sudo bash build-voscms.sh
#

set -e

VERSION=$(php8.3 -r "echo json_decode(file_get_contents('/var/www/voscms/version.json'))->version;")
SRC="/var/www/voscms"
DIST="/var/www/voscms-dist"
TARGET="${DIST}/voscms-${VERSION}"

echo "========================================="
echo "  VosCMS v${VERSION} Build"
echo "========================================="

# 1. 기존 배포 디렉토리 정리
echo "[1/8] Cleaning..."
rm -rf "${TARGET}"
mkdir -p "${TARGET}"

# 2. 파일 복사 (제외 항목 적용)
echo "[2/8] Copying files..."
rsync -a \
  --exclude='.env' --exclude='.git' --exclude='.claude' \
  --exclude='node_modules' --exclude='storage/logs/*' \
  --exclude='storage/.installed' --exclude='storage/.license_cache' \
  --exclude='storage/.update_cache' --exclude='storage/.widget_sync' \
  --exclude='storage/.migration_checked' --exclude='storage/cache/*' \
  --exclude='docs' \
  --exclude='plugins/vos-license-manager' \
  --exclude='plugins/vos-shop' \
  --exclude='plugins/vos-marketplace' \
  --exclude='api/license' \
  --exclude='api/developer' \
  --exclude='api/notices.php' \
  "${SRC}/" "${TARGET}/"

# 3. 개발용 파일 제거
echo "[3/8] Removing dev files..."
rm -f "${TARGET}/add_source_locale.php"
rm -f "${TARGET}/add_term_translations.php"
rm -f "${TARGET}/delete_test_translations.php"
rm -f "${TARGET}/insert_term_translations.php"
rm -f "${TARGET}/postcss.config.js"
rm -f "${TARGET}/tailwind.config.js"
rm -f "${TARGET}/vite.config.js"
rm -f "${TARGET}/update-api.php"
rm -rf "${TARGET}/install/"
rm -f "${TARGET}/.gitignore"

# 4. 비번들 위젯 제거 (widget.json 의 "bundled": true 만 유지)
#    스킨/레이아웃은 필터링 없이 전부 번들 (rsync 결과 그대로)
echo "[4/8] Filtering widgets by bundled flag..."
KEPT=0
REMOVED=0
for d in "${TARGET}"/widgets/*/; do
    [ -d "$d" ] || continue
    w=$(basename "$d")
    JSON="${d}widget.json"
    if [ ! -f "$JSON" ]; then
        rm -rf "$d"
        echo "  removed: widgets/${w} (no widget.json)"
        REMOVED=$((REMOVED+1))
        continue
    fi
    BUNDLED=$(php8.3 -r "\$j = json_decode(file_get_contents('$JSON'), true); echo !empty(\$j['bundled']) ? '1' : '0';")
    if [ "$BUNDLED" = "1" ]; then
        KEPT=$((KEPT+1))
    else
        rm -rf "$d"
        echo "  removed: widgets/${w}"
        REMOVED=$((REMOVED+1))
    fi
done
echo "  → kept: ${KEPT}, removed: ${REMOVED}"

# 5. storage 디렉토리 생성
echo "[5/8] Creating storage directories..."
mkdir -p "${TARGET}/storage/"{cache,logs,sessions,tmp,uploads}

# 6. .env.example 생성
echo "[6/8] Creating .env.example..."
cat > "${TARGET}/.env.example" << 'ENVEOF'
# VosCMS Configuration
APP_NAME="VosCMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=Asia/Tokyo
APP_LOCALE=ko
ADMIN_PATH=admin
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=voscms
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX=rzx_

SESSION_DRIVER=file
SESSION_LIFETIME=10080
SESSION_SECURE_COOKIE=true

JWT_SECRET=
JWT_TTL=60
JWT_REFRESH_TTL=20160

CACHE_DRIVER=file
CACHE_PREFIX=vos_

LICENSE_KEY=
LICENSE_DOMAIN=
LICENSE_REGISTERED_AT=
LICENSE_SERVER=https://vos.21ces.com/api
ENVEOF

# 7. 코드 인코딩 (ionCube)
#    핵심 보안 모듈을 ionCube 바이트코드로 인코딩 → Loader 필요
echo "[7/8] Encoding with ionCube..."
IC_SH="${DIST}/ioncube/ioncube_encoder_evaluation/ioncube_encoder.sh"
if [ ! -x "$IC_SH" ]; then
    echo "  ⚠ ionCube encoder not found at $IC_SH — falling back to obfuscate.php"
    php8.3 "${DIST}/obfuscate.php"
else
    # 인코딩 대상 (배포 디렉토리 기준 상대 경로)
    # ⚠ install.php 는 인코딩 제외 — Loader 없는 신규 설치자가 환경 체크
    #   페이지를 정상적으로 봐야 ionCube 설치 안내 UI 가 노출됨
    IC_TARGETS=(
        "rzxlib/Core/License/LicenseClient.php"
        "rzxlib/Core/License/LicenseStatus.php"
        "rzxlib/Core/Auth/AdminAuth.php"
        "rzxlib/Core/Auth/Auth.php"
        "rzxlib/Core/Auth/SessionGuard.php"
        "rzxlib/Core/Plugin/PluginManager.php"
        "rzxlib/Core/Plugin/Hook.php"
        "plugins/vos-autoinstall/src/MarketplaceService.php"
        "plugins/vos-autoinstall/src/LicenseService.php"
        "plugins/vos-autoinstall/src/InstallerService.php"
        "plugins/vos-autoinstall/src/CatalogClient.php"
    )
    IC_OK=0
    IC_SKIP=0
    for rel in "${IC_TARGETS[@]}"; do
        SRC_F="${TARGET}/${rel}"
        if [ ! -f "$SRC_F" ]; then
            echo "  skip (missing): $rel"
            IC_SKIP=$((IC_SKIP+1))
            continue
        fi
        TMP_F="${SRC_F}.enc"
        if bash "$IC_SH" "$SRC_F" -o "$TMP_F" >/dev/null 2>&1; then
            mv -f "$TMP_F" "$SRC_F"
            IC_OK=$((IC_OK+1))
        else
            rm -f "$TMP_F"
            echo "  ⚠ encoding failed: $rel"
            IC_SKIP=$((IC_SKIP+1))
        fi
    done
    echo "  → encoded: ${IC_OK}, skipped/failed: ${IC_SKIP}"
fi

# 8. ZIP 패키징
echo "[8/8] Packaging..."
cd "${DIST}"
rm -f "voscms-${VERSION}.zip"
zip -rq "voscms-${VERSION}.zip" "voscms-${VERSION}/"

# 다운로드 링크 업데이트
mkdir -p "${DIST}/download"
ln -sf "${DIST}/voscms-${VERSION}.zip" "${DIST}/download/voscms-${VERSION}.zip"
ln -sf "${DIST}/voscms-${VERSION}.zip" "${DIST}/download/voscms-latest.zip"

# 권한 설정
chown -R www-data:www-data "${TARGET}/"
chmod -R 775 "${TARGET}/storage/"
chmod 775 "${TARGET}/"

echo ""
echo "========================================="
echo "  Build complete!"
echo "  ${DIST}/voscms-${VERSION}.zip"
echo "  $(du -sh "${DIST}/voscms-${VERSION}.zip" | awk '{print $1}')"
echo "  Widgets: $(ls "${TARGET}/widgets/" | wc -l)"
echo "  Download: https://vos.21ces.com/download/voscms-latest.zip"
echo "========================================="
