#!/bin/bash
# Synology NAS용 pip 설치 스크립트
# 사용법: bash install_pip_synology.sh

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Synology NAS - pip3 및 openpyxl 설치"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Python 버전 확인
echo "🔍 Python 버전 확인..."
PYTHON_CMD=$(which python3)
if [ -z "$PYTHON_CMD" ]; then
    echo "❌ Python3를 찾을 수 없습니다."
    echo "   DSM 패키지 센터에서 'Python 3'를 설치해주세요."
    exit 1
fi

PYTHON_VERSION=$($PYTHON_CMD --version 2>&1)
echo "✅ $PYTHON_VERSION"
echo "   경로: $PYTHON_CMD"
echo ""

# pip3 확인
echo "🔍 pip3 확인..."
PIP_CMD=$(which pip3 2>/dev/null)
if [ -z "$PIP_CMD" ]; then
    echo "⚠️  pip3가 설치되지 않았습니다."
    echo ""
    echo "📦 pip 설치 중..."
    
    # Python 버전별 get-pip.py 다운로드
    cd /tmp
    
    # Python 버전 확인 (메이저.마이너)
    PYTHON_VER=$($PYTHON_CMD -c "import sys; print(f'{sys.version_info.major}.{sys.version_info.minor}')")
    echo "   Python 버전: $PYTHON_VER"
    
    # Python 3.8의 경우 특별한 URL 사용
    if [[ "$PYTHON_VER" == "3.8" ]]; then
        PIP_URL="https://bootstrap.pypa.io/pip/3.8/get-pip.py"
        echo "   Python 3.8 전용 get-pip.py 사용"
    else
        PIP_URL="https://bootstrap.pypa.io/get-pip.py"
    fi
    
    wget $PIP_URL -O get-pip.py
    
    if [ $? -eq 0 ]; then
        echo "✅ get-pip.py 다운로드 완료"
        
        # pip 설치
        echo "   pip 설치 중..."
        $PYTHON_CMD get-pip.py --user
        
        if [ $? -eq 0 ]; then
            echo "✅ pip 설치 완료"
            
            # pip 경로 확인
            export PATH="$HOME/.local/bin:$PATH"
            PIP_CMD="$HOME/.local/bin/pip3"
            
            # .bashrc 또는 .profile에 PATH 추가
            if [ -f "$HOME/.bashrc" ]; then
                if ! grep -q ".local/bin" "$HOME/.bashrc"; then
                    echo 'export PATH="$HOME/.local/bin:$PATH"' >> "$HOME/.bashrc"
                    echo "✅ PATH를 .bashrc에 추가했습니다"
                fi
            fi
        else
            echo "❌ pip 설치 실패"
            exit 1
        fi
    else
        echo "❌ get-pip.py 다운로드 실패"
        echo ""
        echo "수동 설치 방법:"
        echo "  1. https://bootstrap.pypa.io/get-pip.py 다운로드"
        echo "  2. python3 get-pip.py --user"
        exit 1
    fi
else
    echo "✅ pip3가 이미 설치되어 있습니다: $PIP_CMD"
fi

echo ""
echo "📦 openpyxl 설치 중..."

# openpyxl 설치 시도
if [ -n "$PIP_CMD" ] && [ -f "$PIP_CMD" ]; then
    $PIP_CMD install openpyxl --user
else
    $PYTHON_CMD -m pip install openpyxl --user
fi

if [ $? -eq 0 ]; then
    echo "✅ openpyxl 설치 완료"
else
    echo "❌ openpyxl 설치 실패"
    exit 1
fi

echo ""
echo "✅ 설치 확인 중..."
$PYTHON_CMD -c "import openpyxl; print('✅ openpyxl 버전:', openpyxl.__version__)"

if [ $? -eq 0 ]; then
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "  ✅ 설치 완료!"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "⚠️  중요: 새 터미널을 열거나 다음 명령 실행:"
    echo ""
    echo "  source ~/.bashrc"
    echo ""
    echo "그 다음 Excel 변환 도구를 테스트할 수 있습니다:"
    echo ""
    echo "  cd /volume1/web/hyundai-parts/"
    echo "  python3 create_sample_excel.py"
    echo "  python3 excel_converter_method2.py sample_parts_data.xlsx"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
else
    echo "❌ 설치는 완료되었으나 import에 실패했습니다."
    echo ""
    echo "다음을 시도해보세요:"
    echo "  1. 터미널 재시작 또는: source ~/.bashrc"
    echo "  2. Python 경로 확인: which python3"
    echo "  3. 모듈 위치 확인: python3 -m pip show openpyxl"
    exit 1
fi
