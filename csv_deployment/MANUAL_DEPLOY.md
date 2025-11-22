# 수동 배포 가이드

자동 스크립트 사용이 어렵거나 단계별로 배포하고 싶은 경우 이 가이드를 따르세요.

---

## 📋 배포 체크리스트

- [ ] Synology NAS SSH 접속 완료
- [ ] 배포 파일 다운로드 완료
- [ ] 기존 파일 백업 완료
- [ ] 새 파일 복사 완료
- [ ] 권한 설정 완료
- [ ] 웹 페이지 접속 테스트 완료

---

## 1️⃣ SSH 접속

### Windows (PowerShell 또는 PuTTY)
```powershell
ssh admin@59.19.231.47
```

### macOS / Linux
```bash
ssh admin@59.19.231.47
```

---

## 2️⃣ 기존 파일 백업

```bash
# 관리자 페이지 백업
cd /volume1/web/hyundai-parts/admin
cp index.php index.php.backup.$(date +%Y%m%d_%H%M%S)

# 백업 확인
ls -la index.php.backup*
```

**예상 출력**:
```
-rw-r--r-- 1 admin users 14725 Nov 22 05:15 index.php
-rw-r--r-- 1 admin users 14725 Nov 22 14:30 index.php.backup.20251122_143000
```

---

## 3️⃣ 배포 파일 다운로드

### 방법 1: GitHub Release에서 직접 다운로드 (추천)

```bash
# 임시 디렉토리로 이동
cd /tmp

# wget으로 다운로드 (Release가 준비되면)
wget https://github.com/kimmin0242/kimnote/releases/download/csv-v1.0/csv_deployment.tar.gz

# 압축 해제
tar -xzf csv_deployment.tar.gz

# 디렉토리 이동
cd csv_deployment
```

### 방법 2: Git Clone 사용

```bash
# 임시 디렉토리로 이동
cd /tmp

# Git repository clone
git clone https://github.com/kimmin0242/kimnote.git

# 배포 디렉토리로 이동
cd kimnote/csv_deployment
```

### 방법 3: FTP/SFTP로 업로드

1. FileZilla 또는 WinSCP 사용
2. `/tmp` 디렉토리에 `csv_deployment` 폴더 업로드
3. SSH로 접속하여 `/tmp/csv_deployment`로 이동

---

## 4️⃣ 파일 배포

### CSV Export/Import 파일 복사

```bash
# 현재 위치 확인
pwd
# /tmp/csv_deployment 여야 함

# 루트 디렉토리에 CSV 파일 복사
cp export_parts_csv.php /volume1/web/hyundai-parts/
cp import_parts_csv.php /volume1/web/hyundai-parts/

# 복사 확인
ls -la /volume1/web/hyundai-parts/*_parts_csv.php
```

**예상 출력**:
```
-rw-r--r-- 1 admin users 3456 Nov 22 14:35 /volume1/web/hyundai-parts/export_parts_csv.php
-rw-r--r-- 1 admin users 8901 Nov 22 14:35 /volume1/web/hyundai-parts/import_parts_csv.php
```

### 관리자 페이지 복사

```bash
# 관리자 페이지 업데이트
cp admin_index.php /volume1/web/hyundai-parts/admin/index.php

# 복사 확인
ls -la /volume1/web/hyundai-parts/admin/index.php
```

**예상 출력**:
```
-rw-r--r-- 1 admin users 17856 Nov 22 14:36 /volume1/web/hyundai-parts/admin/index.php
```

---

## 5️⃣ 파일 권한 설정

```bash
# CSV 파일 권한 설정
chmod 644 /volume1/web/hyundai-parts/export_parts_csv.php
chmod 644 /volume1/web/hyundai-parts/import_parts_csv.php

# 관리자 페이지 권한 설정
chmod 644 /volume1/web/hyundai-parts/admin/index.php

# 권한 확인
ls -la /volume1/web/hyundai-parts/*.php | grep csv
ls -la /volume1/web/hyundai-parts/admin/index.php
```

**예상 출력** (644 = rw-r--r--):
```
-rw-r--r-- 1 admin users 3456 Nov 22 14:35 export_parts_csv.php
-rw-r--r-- 1 admin users 8901 Nov 22 14:35 import_parts_csv.php
-rw-r--r-- 1 admin users 17856 Nov 22 14:36 admin/index.php
```

---

## 6️⃣ 배포 확인

### 파일 존재 확인

```bash
# 모든 배포 파일 확인
echo "=== CSV 파일 확인 ==="
ls -lh /volume1/web/hyundai-parts/export_parts_csv.php
ls -lh /volume1/web/hyundai-parts/import_parts_csv.php

echo ""
echo "=== 관리자 페이지 확인 ==="
ls -lh /volume1/web/hyundai-parts/admin/index.php

echo ""
echo "=== 백업 파일 확인 ==="
ls -lh /volume1/web/hyundai-parts/admin/index.php.backup*
```

### 파일 내용 확인 (선택사항)

```bash
# CSV Export 파일의 첫 10줄 확인
head -10 /volume1/web/hyundai-parts/export_parts_csv.php

# "CSV 데이터 관리" 문구가 있는지 확인
grep -n "CSV 데이터 관리" /volume1/web/hyundai-parts/admin/index.php
```

---

## 7️⃣ 웹 브라우저 테스트

### 1. 관리자 페이지 접속

```
URL: http://59.19.231.47/hyundai-parts/admin/
ID: admin
PW: admin123
```

### 2. 확인 사항

- [x] 로그인 성공
- [x] 페이지가 정상적으로 로드됨
- [x] "CSV 데이터 관리" 섹션이 보임
- [x] "CSV 다운로드" 버튼이 있음
- [x] "CSV 업로드" 폼이 있음
- [x] 기존 "데이터베이스 유틸리티" 섹션이 사라짐

### 3. CSV 다운로드 테스트

1. "CSV 다운로드" 버튼 클릭
2. `vehicle_parts_mapping_2025-11-22.csv` 파일 다운로드 확인
3. Excel에서 파일 열기 → 한글이 정상적으로 보이는지 확인

### 4. CSV 업로드 테스트 (선택사항)

1. 다운로드한 CSV 파일을 Excel에서 열기
2. 테스트로 한 행의 "비고" 컬럼 수정
3. "CSV UTF-8 (쉼표로 분리)" 형식으로 저장
4. 관리자 페이지에서 파일 선택
5. "CSV 업로드" 버튼 클릭
6. 성공 메시지 확인

---

## 8️⃣ 정리 작업

```bash
# 임시 파일 삭제 (선택사항)
rm -rf /tmp/csv_deployment
rm -f /tmp/csv_deployment.tar.gz

# 또는 Git clone을 사용했다면
rm -rf /tmp/kimnote
```

---

## ⚠️ 문제 해결

### 문제 1: "Permission denied" 오류

**증상**:
```
cp: cannot create regular file '/volume1/web/hyundai-parts/...': Permission denied
```

**해결**:
```bash
# sudo 권한으로 복사
sudo cp export_parts_csv.php /volume1/web/hyundai-parts/

# 또는 관리자 그룹에 쓰기 권한 부여
sudo chmod 775 /volume1/web/hyundai-parts
```

### 문제 2: 웹 페이지가 빈 화면으로 나옴

**증상**: 관리자 페이지 접속 시 하얀 화면

**해결**:
```bash
# PHP 오류 확인
tail -50 /var/log/php-fpm.log

# 백업으로 롤백
cp /volume1/web/hyundai-parts/admin/index.php.backup.* \
   /volume1/web/hyundai-parts/admin/index.php
```

### 문제 3: CSV 다운로드 시 404 오류

**증상**: "파일을 찾을 수 없습니다" 오류

**해결**:
```bash
# 파일 존재 확인
ls -la /volume1/web/hyundai-parts/export_parts_csv.php

# 파일이 없으면 다시 복사
cp /tmp/csv_deployment/export_parts_csv.php /volume1/web/hyundai-parts/

# 권한 설정
chmod 644 /volume1/web/hyundai-parts/export_parts_csv.php
```

### 문제 4: CSV 업로드가 작동하지 않음

**증상**: 업로드 버튼 클릭 시 아무 반응 없음

**해결**:
```bash
# import_parts_csv.php 파일 확인
ls -la /volume1/web/hyundai-parts/import_parts_csv.php

# 파일이 없으면 다시 복사
cp /tmp/csv_deployment/import_parts_csv.php /volume1/web/hyundai-parts/

# 웹 브라우저 콘솔에서 JavaScript 오류 확인
# (F12 > Console 탭)
```

---

## 🔙 롤백 (이전 상태로 되돌리기)

배포 후 문제가 발생하면:

```bash
# 1. 백업 파일 목록 확인
ls -la /volume1/web/hyundai-parts/admin/index.php.backup*

# 2. 최신 백업으로 복구
LATEST_BACKUP=$(ls -t /volume1/web/hyundai-parts/admin/index.php.backup* | head -1)
cp "$LATEST_BACKUP" /volume1/web/hyundai-parts/admin/index.php

# 3. CSV 파일 삭제 (선택사항)
rm /volume1/web/hyundai-parts/export_parts_csv.php
rm /volume1/web/hyundai-parts/import_parts_csv.php

# 4. 웹 브라우저에서 페이지 새로고침하여 확인
```

---

## 📞 도움이 필요하신가요?

- **로그 파일 확인**:
  ```bash
  tail -100 /var/log/php-fpm.log
  tail -100 /var/log/nginx/error.log
  ```

- **GitHub Issues**: https://github.com/kimmin0242/kimnote/issues

- **Pull Request**: https://github.com/kimmin0242/kimnote/pull/1

---

**완료!** 🎉

모든 단계를 완료했다면 관리자 페이지에서 새로운 CSV 데이터 관리 기능을 사용할 수 있습니다.
