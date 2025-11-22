# 수동 설치 가이드 (Zeus 서버 Python 3.8)

## 🎯 한 번에 복사해서 실행

아래 전체 명령을 **한 번에 복사해서 붙여넣기** 하세요:

```bash
cd /tmp && \
wget https://bootstrap.pypa.io/pip/3.8/get-pip.py && \
python3 get-pip.py --user && \
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc && \
source ~/.bashrc && \
$HOME/.local/bin/pip3 install openpyxl --user && \
python3 -c "import openpyxl; print('✅ 설치 완료! 버전:', openpyxl.__version__)"
```

---

## 📋 단계별 실행 (문제 발생 시)

### 1단계: get-pip.py 다운로드
```bash
cd /tmp
wget https://bootstrap.pypa.io/pip/3.8/get-pip.py
```

### 2단계: pip 설치
```bash
python3 get-pip.py --user
```

**예상 출력:**
```
Collecting pip
  ...
Successfully installed pip-XX.X.X
```

### 3단계: PATH 확인 및 설정
```bash
# pip 위치 확인
ls -la $HOME/.local/bin/pip*

# PATH 추가
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc

# 현재 세션에 적용
export PATH="$HOME/.local/bin:$PATH"

# 또는
source ~/.bashrc
```

### 4단계: pip 확인
```bash
# 직접 경로로 실행
$HOME/.local/bin/pip3 --version

# 또는 PATH 설정 후
pip3 --version
```

### 5단계: openpyxl 설치
```bash
# 직접 경로 사용
$HOME/.local/bin/pip3 install openpyxl --user

# 또는 PATH 설정 후
pip3 install openpyxl --user
```

### 6단계: 설치 확인
```bash
python3 -c "import openpyxl; print('버전:', openpyxl.__version__)"
```

---

## ✅ 설치 완료 후

```bash
cd /volume1/web/hyundai-parts/

# 샘플 파일 생성
python3 create_sample_excel.py

# 변환 테스트
python3 excel_converter_method2.py sample_parts_data.xlsx
```

---

## 🔧 문제 해결

### 문제 1: wget이 없는 경우

```bash
# curl 사용
cd /tmp
curl -o get-pip.py https://bootstrap.pypa.io/pip/3.8/get-pip.py
python3 get-pip.py --user
```

### 문제 2: 다운로드가 안 되는 경우

PC에서 다운로드 후 파일 전송:
1. PC에서 https://bootstrap.pypa.io/pip/3.8/get-pip.py 다운로드
2. SCP나 FTP로 Zeus 서버 /tmp에 업로드
3. `python3 /tmp/get-pip.py --user`

### 문제 3: pip 설치 후에도 "No module named pip"

```bash
# Python 경로 확인
which python3

# 정확한 Python으로 pip 설치
/usr/bin/python3 get-pip.py --user
# 또는
/usr/local/bin/python3 get-pip.py --user
```

### 문제 4: PATH가 적용 안 됨

```bash
# 새 터미널 열기 또는
source ~/.bashrc

# 또는 직접 경로로 실행
/home/kdm0242/.local/bin/pip3 install openpyxl --user
```

---

## 🚨 긴급 대안

openpyxl 설치가 계속 실패하는 경우:

### 대안 1: 로컬 PC에서 변환

1. 로컬 PC에 Python 설치
2. `pip install openpyxl`
3. Excel 파일을 PC로 복사
4. 변환 후 결과 파일 업로드

### 대안 2: Docker 사용

```bash
# Synology Docker가 있다면
docker run --rm -v /volume1/web/hyundai-parts:/app python:3.8 bash -c "pip install openpyxl && cd /app && python excel_converter_method2.py sample_parts_data.xlsx"
```

### 대안 3: Python 3.9 이상 설치

Synology 패키지 센터에서 Python 3.9 이상 버전 설치 후:
```bash
python3.9 -m pip install openpyxl --user
python3.9 excel_converter_method2.py sample_parts_data.xlsx
```

---

## 📞 여전히 문제가 있다면

다음 정보를 알려주세요:

```bash
# 시스템 정보
uname -a

# Python 정보
which python3
python3 --version
ls -la /usr/bin/python*
ls -la /usr/local/bin/python*

# 사용자 홈 디렉토리
echo $HOME
ls -la $HOME/.local/bin/ 2>/dev/null || echo "디렉토리 없음"

# PATH 정보
echo $PATH

# 권한 확인
id
```
