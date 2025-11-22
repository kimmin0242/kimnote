#!/bin/bash
# openpyxl 설치 스크립트
# 사용법: bash install_openpyxl.sh

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Excel 변환 도구 - openpyxl 설치"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Python 버전 확인
echo "🔍 Python 버전 확인..."
PYTHON_VERSION=$(python3 --version 2>&1)
if [ $? -eq 0 ]; then
    echo "✅ $PYTHON_VERSION"
else
    echo "❌ Python3가 설치되지 않았습니다."
    echo "   Synology DSM 패키지 센터에서 'Python 3'를 설치해주세요."
    exit 1
fi

echo ""
echo "📦 openpyxl 설치 시도 중..."

# 방법 1: 사용자 권한으로 설치
echo "   방법 1: pip3 install --user 시도..."
pip3 install openpyxl --user 2>&1 | tee /tmp/openpyxl_install.log

if [ $? -eq 0 ]; then
    echo "✅ 설치 성공!"
else
    echo "⚠️  방법 1 실패. 다른 방법 시도 중..."
    
    # 방법 2: python -m pip 사용
    echo "   방법 2: python3 -m pip 시도..."
    python3 -m pip install openpyxl --user
    
    if [ $? -eq 0 ]; then
        echo "✅ 설치 성공!"
    else
        echo "❌ 설치 실패"
        echo ""
        echo "수동 설치 방법:"
        echo "  1. Synology DSM 패키지 센터에서 'Python 3' 재설치"
        echo "  2. SSH/터미널에서: pip3 install openpyxl --user"
        echo "  3. 관리자 권한 필요 시: sudo pip3 install openpyxl"
        exit 1
    fi
fi

echo ""
echo "✅ 설치 확인 중..."
python3 -c "import openpyxl; print('✅ openpyxl 버전:', openpyxl.__version__)"

if [ $? -eq 0 ]; then
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "  ✅ 설치 완료!"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "다음 명령으로 변환 도구를 테스트할 수 있습니다:"
    echo ""
    echo "  cd /volume1/web/hyundai-parts/"
    echo "  python3 create_sample_excel.py"
    echo "  python3 excel_converter_method2.py sample_parts_data.xlsx"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
else
    echo "❌ 설치는 완료되었으나 import에 실패했습니다."
    echo "   Python 경로 문제일 수 있습니다."
    echo ""
    echo "다음 명령으로 확인해보세요:"
    echo "  which python3"
    echo "  pip3 list | grep openpyxl"
    exit 1
fi
