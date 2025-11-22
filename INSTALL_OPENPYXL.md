# openpyxl 설치 가이드

Excel 변환 도구를 사용하려면 Python의 `openpyxl` 모듈이 필요합니다.

## 🚀 설치 방법

### 방법 1: pip3로 설치 (추천)

```bash
# 일반 사용자 권한으로 설치
pip3 install openpyxl --user

# 또는 시스템 전체에 설치 (관리자 권한 필요)
sudo pip3 install openpyxl
```

### 방법 2: requirements 파일 사용

```bash
cd /volume1/web/hyundai-parts/
pip3 install -r requirements_excel.txt --user
```

### 방법 3: Synology 패키지 관리자 사용

1. **DSM 웹 인터페이스 접속**
2. **패키지 센터** 열기
3. **Python 3** 패키지 설치 (아직 설치 안 됐으면)
4. **터미널/SSH 접속**
5. 위의 pip3 명령 실행

## ✅ 설치 확인

```bash
python3 -c "import openpyxl; print('openpyxl 버전:', openpyxl.__version__)"
```

**예상 출력:**
```
openpyxl 버전: 3.1.5
```

## 🔧 문제 해결

### 1. "pip3: command not found" 오류

Python3가 설치되지 않았거나 PATH에 없을 수 있습니다.

**해결책:**
```bash
# Python3 설치 확인
which python3

# pip3 설치
sudo apt-get install python3-pip
# 또는 Synology DSM에서
sudo apt install python3-pip
```

### 2. 권한 오류 발생

**해결책:**
```bash
# --user 플래그 추가 (사용자 홈 디렉토리에 설치)
pip3 install openpyxl --user

# 또는 가상환경 사용
python3 -m venv venv
source venv/bin/activate
pip install openpyxl
```

### 3. Synology NAS 특정 문제

일부 Synology 모델에서는 pip가 기본으로 포함되지 않을 수 있습니다.

**해결책 A: 패키지 센터에서 Python3 설치**
```
패키지 센터 → Python 3 검색 → 설치
```

**해결책 B: 수동으로 pip 설치**
```bash
# pip 다운로드 및 설치
wget https://bootstrap.pypa.io/get-pip.py
python3 get-pip.py --user
```

### 4. 여러 Python 버전이 설치된 경우

특정 Python 버전에 설치:
```bash
python3.9 -m pip install openpyxl --user
# 또는
python3.12 -m pip install openpyxl --user
```

## 📦 필요한 의존성

`openpyxl`은 다음 패키지를 자동으로 설치합니다:
- `et-xmlfile` (Excel XML 처리)

## 🌐 웹 인터페이스 사용 시

웹 인터페이스(`admin_excel_converter.php`)를 통해 변환하려면:

1. openpyxl이 웹 서버 사용자 권한으로 설치되어 있어야 합니다
2. PHP의 `exec()` 함수가 활성화되어 있어야 합니다

**설치 확인:**
```bash
# 웹 서버 사용자로 확인 (보통 www-data 또는 http)
sudo -u www-data python3 -c "import openpyxl; print('OK')"
```

만약 실패하면:
```bash
# 웹 서버 사용자로 설치
sudo -u www-data pip3 install openpyxl --user
```

## 📝 설치 후 테스트

```bash
cd /volume1/web/hyundai-parts/

# 샘플 파일 생성
python3 create_sample_excel.py

# 변환 테스트
python3 excel_converter_method2.py sample_parts_data.xlsx

# 성공 시 다음 메시지 표시:
# ✅ 변환 완료!
# 📊 변환 통계: ...
```

## 🔍 추가 정보

- **공식 문서:** https://openpyxl.readthedocs.io/
- **PyPI:** https://pypi.org/project/openpyxl/
- **GitHub:** https://github.com/theorchard/openpyxl

## 💡 대안 (설치 없이 사용)

openpyxl 설치가 어려운 경우:

1. **다른 서버/컴퓨터에서 변환**
   - 스크립트 복사: `excel_converter_method2.py`
   - 로컬 PC에서 변환 후 파일 업로드

2. **온라인 Python 환경 사용**
   - Google Colab
   - Replit
   - PythonAnywhere

## 🆘 문제가 계속되면

다음 정보와 함께 문의하세요:

```bash
# 시스템 정보
uname -a
python3 --version
pip3 --version

# Python 경로
which python3
which pip3

# 설치된 모듈 확인
pip3 list | grep openpyxl
```
