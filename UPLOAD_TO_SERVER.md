# 프로덕션 서버 업로드 가이드

## 📍 업로드할 파일

다음 파일들을 Synology NAS 프로덕션 서버에 업로드해야 합니다:

1. **csv_deployment.tar.gz** (12 KB) - 배포 패키지
2. **DOWNLOAD_CSV_DEPLOYMENT.html** (10 KB) - 다운로드 페이지

## 🎯 업로드 위치

```
/volume1/web/hyundai-parts/
```

---

## 방법 1: GitHub에서 직접 다운로드 (가장 쉬움)

### 1단계: GitHub에서 파일 다운로드

**csv_deployment.tar.gz**:
```
https://raw.githubusercontent.com/kimmin0242/kimnote/genspark_ai_developer/csv_deployment.tar.gz
```

**DOWNLOAD_CSV_DEPLOYMENT.html**:
```
https://raw.githubusercontent.com/kimmin0242/kimnote/genspark_ai_developer/DOWNLOAD_CSV_DEPLOYMENT.html
```

### 2단계: Synology NAS SSH에서 실행

```bash
# SSH 접속
ssh admin@59.19.231.47

# 프로덕션 디렉토리로 이동
cd /volume1/web/hyundai-parts/

# GitHub에서 직접 다운로드
wget https://raw.githubusercontent.com/kimmin0242/kimnote/genspark_ai_developer/csv_deployment.tar.gz

wget https://raw.githubusercontent.com/kimmin0242/kimnote/genspark_ai_developer/DOWNLOAD_CSV_DEPLOYMENT.html

# 권한 설정
chmod 644 csv_deployment.tar.gz
chmod 644 DOWNLOAD_CSV_DEPLOYMENT.html

# 파일 확인
ls -lh csv_deployment.tar.gz DOWNLOAD_CSV_DEPLOYMENT.html
```

### 3단계: 웹 접속 테스트

```
http://59.19.231.47/hyundai-parts/DOWNLOAD_CSV_DEPLOYMENT.html
http://59.19.231.47/hyundai-parts/csv_deployment.tar.gz
```

---

## 방법 2: SFTP/FTP 사용

### FileZilla 사용:

1. **FileZilla 실행 및 접속**
   - 호스트: `sftp://59.19.231.47`
   - 사용자: `admin`
   - 비밀번호: (관리자 비밀번호)
   - 포트: `22`

2. **원격 디렉토리로 이동**
   ```
   /volume1/web/hyundai-parts/
   ```

3. **파일 업로드**
   - 로컬에서 다운로드한 파일들을 드래그 앤 드롭
   - `csv_deployment.tar.gz`
   - `DOWNLOAD_CSV_DEPLOYMENT.html`

4. **권한 확인**
   - 파일 우클릭 → 파일 권한
   - 644 (rw-r--r--) 확인

---

## 방법 3: WinSCP 사용 (Windows)

1. **WinSCP 실행**
2. **새 세션 생성**:
   - 파일 프로토콜: `SFTP`
   - 호스트 이름: `59.19.231.47`
   - 포트 번호: `22`
   - 사용자 이름: `admin`
   - 비밀번호: (관리자 비밀번호)

3. **로그인 후 업로드**
   - 원격: `/volume1/web/hyundai-parts/`
   - 파일 드래그 앤 드롭

---

## 방법 4: scp 명령어 사용 (Linux/Mac)

로컬 컴퓨터에서 실행:

```bash
# GitHub에서 파일 다운로드
wget https://raw.githubusercontent.com/kimmin0242/kimnote/genspark_ai_developer/csv_deployment.tar.gz
wget https://raw.githubusercontent.com/kimmin0242/kimnote/genspark_ai_developer/DOWNLOAD_CSV_DEPLOYMENT.html

# scp로 서버에 업로드
scp csv_deployment.tar.gz admin@59.19.231.47:/volume1/web/hyundai-parts/
scp DOWNLOAD_CSV_DEPLOYMENT.html admin@59.19.231.47:/volume1/web/hyundai-parts/

# SSH 접속하여 권한 설정
ssh admin@59.19.231.47
cd /volume1/web/hyundai-parts/
chmod 644 csv_deployment.tar.gz DOWNLOAD_CSV_DEPLOYMENT.html
```

---

## ✅ 업로드 확인

### 1. SSH에서 파일 확인
```bash
ssh admin@59.19.231.47
ls -lh /volume1/web/hyundai-parts/csv_deployment.tar.gz
ls -lh /volume1/web/hyundai-parts/DOWNLOAD_CSV_DEPLOYMENT.html
```

**예상 출력**:
```
-rw-r--r-- 1 admin users  12K Nov 22 09:06 csv_deployment.tar.gz
-rw-r--r-- 1 admin users 9.7K Nov 22 09:06 DOWNLOAD_CSV_DEPLOYMENT.html
```

### 2. 웹 브라우저에서 접속 테스트

**다운로드 페이지**:
```
http://59.19.231.47/hyundai-parts/DOWNLOAD_CSV_DEPLOYMENT.html
```

**직접 다운로드**:
```
http://59.19.231.47/hyundai-parts/csv_deployment.tar.gz
```

---

## 🚀 업로드 후 배포 진행

파일 업로드가 완료되면, 이제 배포를 진행할 수 있습니다:

```bash
# SSH 접속
ssh admin@59.19.231.47

# 임시 디렉토리로 이동
cd /tmp

# wget으로 배포 패키지 다운로드 (이제 서버 내부에서!)
wget http://59.19.231.47/hyundai-parts/csv_deployment.tar.gz

# 압축 해제
tar -xzf csv_deployment.tar.gz

# 배포 디렉토리로 이동
cd csv_deployment

# 자동 배포 실행
chmod +x DEPLOY.sh && ./DEPLOY.sh
```

---

## 📊 전체 프로세스 요약

1. ✅ **GitHub에서 파일 다운로드** (또는 SFTP로 업로드)
2. ✅ **프로덕션 서버에 업로드** → `/volume1/web/hyundai-parts/`
3. ✅ **웹 접속 테스트** → 다운로드 페이지 확인
4. ✅ **배포 실행** → SSH에서 wget + DEPLOY.sh
5. ✅ **관리자 페이지 확인** → CSV 관리 기능 테스트

---

**추천 방법**: **방법 1 (GitHub 직접 다운로드)** - 가장 빠르고 간단합니다! 🚀
