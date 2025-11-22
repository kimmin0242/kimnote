# ⚡ 빠른 시작 가이드

## 🎯 5분 안에 시작하기

### 1️⃣ 파일 업로드 (1분)
시놀로지 File Station에서:
```
/volume1/web/hyundai-parts/
```
경로에 모든 파일 업로드

---

### 2️⃣ Composer 설치 (2분)
```bash
# SSH 접속
ssh admin@59.19.231.47

# 프로젝트 디렉토리로 이동
cd /volume1/web/hyundai-parts

# Composer 의존성 설치
composer install
```

**Composer가 없다면:**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer install
```

---

### 3️⃣ 데이터베이스 설정 (1분)

#### phpMyAdmin 사용
1. http://59.19.231.47/phpMyAdmin 접속
2. "새로 만들기" → 이름: `hyundai_parts`
3. SQL 탭 → `database_setup.sql` 내용 붙여넣기 → 실행

#### 또는 SSH 사용
```bash
mysql -u root -p < database_setup.sql
```

---

### 4️⃣ DB 연결 설정 (30초)
`config/db.php` 파일 수정:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hyundai_parts');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');  // 실제 비밀번호 입력
```

---

### 5️⃣ 권한 설정 (30초)
```bash
chmod 777 /volume1/web/hyundai-parts/uploads
```

---

### 6️⃣ 접속 테스트 ✅
- **사용자 페이지**: http://59.19.231.47/hyundai-parts/
- **관리자 페이지**: http://59.19.231.47/hyundai-parts/admin/?auth=admin123

---

### 7️⃣ 엑셀 데이터 Import (선택사항)
관리자 페이지 → "데이터 가져오기" → 엑셀 파일 업로드

---

## 🚨 문제 해결

### 500 Error
```bash
# 로그 확인
tail -f /var/log/httpd/error_log

# 권한 재설정
chmod -R 755 /volume1/web/hyundai-parts
chmod 777 /volume1/web/hyundai-parts/uploads
```

### DB 연결 실패
1. MariaDB 서비스 확인: `synoservice --status MariaDB10`
2. `config/db.php` 정보 재확인
3. 데이터베이스 이름 확인

### Composer 오류
```bash
# 캐시 삭제 후 재설치
composer clear-cache
composer install
```

---

## ✅ 완료!

이제 시스템을 사용할 수 있습니다.

**다음 단계:**
1. 관리자 비밀번호 변경 (`admin/index.php` 5번째 줄)
2. 엑셀 데이터 Import
3. 차량/엔진/부품 정보 확인 및 추가

**자세한 내용:**
- `README.md` - 프로젝트 개요
- `INSTALL.md` - 상세 설치 가이드
- `DEPLOYMENT.md` - 배포 가이드
- `PROJECT_SUMMARY.md` - 프로젝트 전체 요약
