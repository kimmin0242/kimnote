# Synology NAS 설정 가이드

Excel 변환 도구를 Synology NAS에서 사용하기 위한 설정입니다.

## 🚀 빠른 설치 (자동)

```bash
cd /volume1/web/hyundai-parts/
bash install_pip_synology.sh
```

설치 완료 후:
```bash
source ~/.bashrc
python3 excel_converter_method2.py sample_parts_data.xlsx
```

---

## 📋 수동 설치 (권장)

### 1단계: pip 설치

**Python 3.8 사용 시 (Zeus 서버):**
```bash
# Python 3.8 전용 get-pip.py 다운로드
cd /tmp
wget https://bootstrap.pypa.io/pip/3.8/get-pip.py

# pip 설치
python3 get-pip.py --user
```

**Python 3.9 이상 사용 시:**
```bash
# 일반 get-pip.py 다운로드
cd /tmp
wget https://bootstrap.pypa.io/get-pip.py

# pip 설치
python3 get-pip.py --user
```

### 2단계: PATH 설정

```bash
# .bashrc에 PATH 추가
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc

# 적용
source ~/.bashrc
```

### 3단계: openpyxl 설치

```bash
# pip 확인
pip3 --version

# openpyxl 설치
pip3 install openpyxl --user
```

또는:

```bash
python3 -m pip install openpyxl --user
```

### 4단계: 설치 확인

```bash
python3 -c "import openpyxl; print('버전:', openpyxl.__version__)"
```

---

## 🧪 테스트

```bash
cd /volume1/web/hyundai-parts/

# 샘플 파일 생성
python3 create_sample_excel.py

# 변환 테스트
python3 excel_converter_method2.py sample_parts_data.xlsx
```

**성공 시:**
```
✅ 변환 완료!
📊 변환 통계:
  총 처리 차량: 7개
  총 부품 레코드: 46개
```

---

## 🔧 문제 해결

### 문제 1: "pip3: command not found"

**원인:** pip가 설치되지 않았거나 PATH에 없음

**해결:**
```bash
# pip 경로 확인
find ~/.local/bin -name "pip*"

# PATH 추가
export PATH="$HOME/.local/bin:$PATH"

# 또는 직접 실행
python3 -m pip install openpyxl --user
```

### 문제 2: "ModuleNotFoundError: No module named 'openpyxl'"

**원인:** 모듈이 다른 Python 버전에 설치됨

**해결:**
```bash
# Python 경로 확인
which python3

# 해당 Python으로 직접 설치
/usr/local/bin/python3 -m pip install openpyxl --user
# 또는
/usr/bin/python3 -m pip install openpyxl --user
```

### 문제 3: 권한 오류

**원인:** 시스템 디렉토리에 쓰기 권한 없음

**해결:**
```bash
# 반드시 --user 플래그 사용
python3 -m pip install openpyxl --user
```

### 문제 4: 인터넷 연결 불가

**해결:** 다른 컴퓨터에서 openpyxl를 다운로드하여 수동 설치

```bash
# 1. 다른 PC에서 다운로드
pip3 download openpyxl

# 2. NAS로 파일 전송 (openpyxl-xxx.whl)

# 3. NAS에서 설치
python3 -m pip install openpyxl-3.1.5-py2.py3-none-any.whl --user
```

---

## 🌐 웹 서버 사용자 설정

웹 인터페이스(`admin_excel_converter.php`)를 사용하려면 웹 서버 사용자도 openpyxl이 필요합니다.

```bash
# 웹 서버 사용자 확인 (보통 http 또는 www-data)
ps aux | grep httpd | head -1

# 웹 서버 사용자로 설치
sudo -u http python3 -m pip install openpyxl --user
# 또는
sudo -u www-data python3 -m pip install openpyxl --user
```

---

## 📊 대안 방법

### 방법 A: Docker 사용

Synology Docker를 사용하여 Python 환경 구축:

```bash
docker run -it --rm -v /volume1/web/hyundai-parts:/app python:3.8 bash
pip install openpyxl
cd /app
python excel_converter_method2.py sample_parts_data.xlsx
```

### 방법 B: 로컬 PC에서 변환

1. 로컬 PC에 Python 설치
2. Excel 파일을 PC로 복사
3. PC에서 변환 실행
4. 결과를 NAS로 업로드

**Windows PC:**
```cmd
pip install openpyxl
python excel_converter_method2.py 파일.xlsx
```

**Mac:**
```bash
pip3 install openpyxl
python3 excel_converter_method2.py 파일.xlsx
```

---

## ✅ 확인 체크리스트

- [ ] Python 3.8 이상 설치됨
- [ ] pip3 설치됨
- [ ] PATH에 ~/.local/bin 추가됨
- [ ] openpyxl 설치됨
- [ ] import openpyxl 성공
- [ ] 샘플 파일 변환 성공

---

## 💡 팁

1. **터미널 재시작**: PATH 변경 후 새 터미널을 열거나 `source ~/.bashrc` 실행

2. **Python 버전 확인**: `which python3`로 정확한 경로 확인

3. **모듈 위치 확인**: `python3 -m pip show openpyxl`

4. **권한 문제**: 항상 `--user` 플래그 사용

---

## 📞 추가 도움말

문제가 계속되면 다음 정보를 수집하세요:

```bash
# 시스템 정보
uname -a
python3 --version
which python3

# pip 정보
which pip3
python3 -m pip --version

# PATH 정보
echo $PATH

# Python 경로
python3 -c "import sys; print('\n'.join(sys.path))"

# 설치된 패키지
python3 -m pip list
```
