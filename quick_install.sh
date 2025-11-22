#!/bin/bash
# 초간단 설치 스크립트 - Python 3.8용

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Excel 변환 도구 빠른 설치 (Python 3.8)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# pip 설치
echo "📦 Step 1/3: pip 설치 중..."
cd /tmp
wget -q https://bootstrap.pypa.io/pip/3.8/get-pip.py
python3 get-pip.py --user --quiet

# PATH 설정
echo "🔧 Step 2/3: PATH 설정 중..."
if [ -f "$HOME/.bashrc" ]; then
    if ! grep -q ".local/bin" "$HOME/.bashrc"; then
        echo 'export PATH="$HOME/.local/bin:$PATH"' >> "$HOME/.bashrc"
    fi
fi
export PATH="$HOME/.local/bin:$PATH"

# openpyxl 설치
echo "📦 Step 3/3: openpyxl 설치 중..."
python3 -m pip install openpyxl --user --quiet

# 확인
echo ""
if python3 -c "import openpyxl" 2>/dev/null; then
    VERSION=$(python3 -c "import openpyxl; print(openpyxl.__version__)")
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "  ✅ 설치 완료! (openpyxl $VERSION)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "⚠️  중요: 다음 명령을 실행하세요:"
    echo ""
    echo "  source ~/.bashrc"
    echo ""
    echo "그 다음 테스트:"
    echo ""
    echo "  python3 excel_converter_method2.py sample_parts_data.xlsx"
    echo ""
else
    echo "❌ 설치 실패. 다음 명령으로 수동 설치하세요:"
    echo ""
    echo "  cd /tmp"
    echo "  wget https://bootstrap.pypa.io/pip/3.8/get-pip.py"
    echo "  python3 get-pip.py --user"
    echo "  export PATH=\"\$HOME/.local/bin:\$PATH\""
    echo "  python3 -m pip install openpyxl --user"
    echo ""
    exit 1
fi
