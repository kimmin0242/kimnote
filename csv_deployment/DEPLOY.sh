#!/bin/bash

###############################################################################
# CSV 데이터 관리 시스템 배포 스크립트
# 현대차 부품 검색 시스템 - CSV 관리 기능 추가
###############################################################################

echo "================================================"
echo "CSV 데이터 관리 시스템 배포"
echo "================================================"
echo ""

# 배포 대상 디렉토리
DEPLOY_DIR="/volume1/web/hyundai-parts"

echo "📁 배포 대상: $DEPLOY_DIR"
echo ""

# 1. 루트 디렉토리에 CSV 파일 및 카테고리 관리 페이지 배포
echo "1️⃣ CSV Export/Import 및 카테고리 관리 파일 배포..."
cp -v export_parts_csv.php "$DEPLOY_DIR/"
cp -v import_parts_csv.php "$DEPLOY_DIR/"
cp -v admin_part_categories.php "$DEPLOY_DIR/"
echo "   ✅ export_parts_csv.php 배포 완료"
echo "   ✅ import_parts_csv.php 배포 완료"
echo "   ✅ admin_part_categories.php 배포 완료"
echo ""

# 1.5 API 디렉토리 배포
echo "1️⃣.5 API 파일 배포..."
mkdir -p "$DEPLOY_DIR/api"
cp -rv api/* "$DEPLOY_DIR/api/"
echo "   ✅ API 파일 배포 완료"
echo ""

# 2. 관리자 페이지 백업
echo "2️⃣ 관리자 페이지 백업 생성..."
if [ -f "$DEPLOY_DIR/admin/index.php" ]; then
    BACKUP_FILE="$DEPLOY_DIR/admin/index.php.backup.$(date +%Y%m%d_%H%M%S)"
    cp -v "$DEPLOY_DIR/admin/index.php" "$BACKUP_FILE"
    echo "   ✅ 백업 생성: $BACKUP_FILE"
else
    echo "   ⚠️  기존 파일 없음 (신규 설치)"
fi
echo ""

# 3. 관리자 페이지 배포
echo "3️⃣ 관리자 페이지 업데이트..."
cp -v admin_index.php "$DEPLOY_DIR/admin/index.php"
echo "   ✅ admin/index.php 업데이트 완료"
echo ""

# 4. 파일 권한 설정
echo "4️⃣ 파일 권한 설정..."
chmod 644 "$DEPLOY_DIR/export_parts_csv.php"
chmod 644 "$DEPLOY_DIR/import_parts_csv.php"
chmod 644 "$DEPLOY_DIR/admin/index.php"
echo "   ✅ 권한 설정 완료 (644)"
echo ""

# 5. 배포 완료
echo "================================================"
echo "✅ 배포 완료!"
echo "================================================"
echo ""
echo "📋 배포된 파일 목록:"
echo "   • $DEPLOY_DIR/export_parts_csv.php"
echo "   • $DEPLOY_DIR/import_parts_csv.php"
echo "   • $DEPLOY_DIR/admin/index.php"
echo ""
echo "🌐 접속 URL:"
echo "   http://59.19.231.47/hyundai-parts/admin/"
echo ""
echo "💡 사용 방법:"
echo "   1. 관리자 페이지 접속 (admin/admin123)"
echo "   2. 'CSV 데이터 관리' 섹션 확인"
echo "   3. CSV 다운로드 버튼 클릭하여 데이터 다운로드"
echo "   4. Excel에서 편집 후 CSV UTF-8로 저장"
echo "   5. CSV 업로드 버튼으로 파일 업로드"
echo ""
echo "================================================"
