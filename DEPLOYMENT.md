# 시놀로지 NAS 배포 가이드

## 🚀 빠른 배포 체크리스트

- [ ] 1. 모든 파일을 `/volume1/web/hyundai-parts/` 에 업로드
- [ ] 2. Composer 의존성 설치 (`composer install`)
- [ ] 3. 데이터베이스 생성 및 테이블 설정
- [ ] 4. `config/db.php` 수정 (DB 연결 정보)
- [ ] 5. uploads 폴더 권한 설정 (777)
- [ ] 6. 엑셀 파일 업로드 및 Import
- [ ] 7. 사이트 접속 테스트
- [ ] 8. 관리자 비밀번호 변경

---

## 📂 시놀로지 NAS로 파일 전송

### 방법 1: File Station (GUI)
1. 시놀로지 File Station 열기
2. `web` 폴더로 이동
3. `hyundai-parts` 폴더 생성
4. 모든 파일 드래그 앤 드롭으로 업로드

### 방법 2: SFTP (FileZilla)
```
호스트: sftp://59.19.231.47
포트: 22
사용자명: admin
비밀번호: [NAS 관리자 비밀번호]
원격 경로: /volume1/web/hyundai-parts/
```

### 방법 3: SSH (rsync)
```bash
rsync -avz --progress ./ admin@59.19.231.47:/volume1/web/hyundai-parts/
```

---

## 🗄 데이터베이스 설정

### phpMyAdmin 사용

#### 1단계: phpMyAdmin 접속
```
URL: http://59.19.231.47/phpMyAdmin
사용자: root
비밀번호: [MariaDB 비밀번호]
```

#### 2단계: 데이터베이스 생성
1. 좌측 "새로 만들기" 클릭
2. 데이터베이스 이름: `hyundai_parts`
3. 정렬: `utf8mb4_unicode_ci`
4. "만들기" 클릭

#### 3단계: 테이블 생성
1. `hyundai_parts` 데이터베이스 선택
2. 상단 "SQL" 탭 클릭
3. `database_setup.sql` 파일 내용 붙여넣기
4. "실행" 클릭

### SSH 사용

```bash
# SSH 접속
ssh admin@59.19.231.47

# 데이터베이스 생성
mysql -u root -p

CREATE DATABASE hyundai_parts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# 테이블 생성
mysql -u root -p hyundai_parts < /volume1/web/hyundai-parts/database_setup.sql
```

---

## ⚙️ PHP 설정

### 시놀로지 PHP 설정 확인
1. 제어판 > 웹 서비스 > PHP 설정
2. PHP 8.0 선택
3. "PHP 확장" 탭에서 필수 확장 활성화:
   - ✅ mysqli
   - ✅ pdo_mysql
   - ✅ zip
   - ✅ gd
   - ✅ mbstring

### .user.ini 생성 (프로젝트 루트)
```ini
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
memory_limit = 256M
```

---

## 📦 Composer 설치 및 의존성 설치

### Composer 설치 (처음 한 번만)
```bash
ssh admin@59.19.231.47

# Composer 다운로드
curl -sS https://getcomposer.org/installer | php

# 전역 설치
sudo mv composer.phar /usr/local/bin/composer

# 설치 확인
composer --version
```

### 의존성 설치
```bash
cd /volume1/web/hyundai-parts
composer install
```

**중요**: `vendor/` 폴더가 생성되어야 합니다.

---

## 🔑 데이터베이스 연결 설정

`config/db.php` 파일 편집:

```php
// 기본 설정 (대부분의 경우)
define('DB_HOST', 'localhost');
define('DB_NAME', 'hyundai_parts');
define('DB_USER', 'root');
define('DB_PASS', '');  // 비밀번호 설정한 경우 입력

// 또는 별도 사용자 생성 시
define('DB_USER', 'hyundai_user');
define('DB_PASS', 'your_secure_password');
```

---

## 📁 권한 설정

```bash
ssh admin@59.19.231.47

# 기본 권한
sudo chmod -R 755 /volume1/web/hyundai-parts

# uploads 폴더는 쓰기 권한 필요
sudo chmod 777 /volume1/web/hyundai-parts/uploads

# 소유자 변경 (선택사항)
sudo chown -R http:http /volume1/web/hyundai-parts
```

---

## 📊 엑셀 데이터 Import

### 준비
1. `현대차량 순정부품.xlsx` 파일을 `uploads/` 폴더에 복사

### Import 실행

#### 방법 1: 관리자 페이지 (권장)
1. 브라우저에서 접속: `http://59.19.231.47/hyundai-parts/admin/?auth=admin123`
2. 좌측 메뉴 "데이터 가져오기" 클릭
3. 엑셀 파일 선택
4. "기존 데이터 삭제 후 가져오기" 체크 (최초 1회만)
5. "업로드 및 가져오기" 클릭
6. 결과 확인

#### 방법 2: 직접 스크립트 실행
```
URL: http://59.19.231.47/hyundai-parts/import_excel_data.php?clear=true
```

**주의**: `clear=true`는 기존 데이터를 모두 삭제합니다. 최초 1회만 사용!

---

## 🧪 테스트

### 1. 사용자 페이지 테스트
```
URL: http://59.19.231.47/hyundai-parts/
```

**확인 사항:**
- ✅ 페이지가 정상적으로 로드됨
- ✅ 차량 모델 선택 가능
- ✅ 엔진 선택 가능
- ✅ 부품 검색 작동
- ✅ 검색 결과 표시

### 2. 관리자 페이지 테스트
```
URL: http://59.19.231.47/hyundai-parts/admin/?auth=admin123
```

**확인 사항:**
- ✅ 로그인 성공
- ✅ 대시보드 통계 표시
- ✅ 차량 모델 목록 표시
- ✅ 엔진 목록 표시
- ✅ 부품 목록 표시
- ✅ CRUD 기능 작동 (추가/수정/삭제)

### 3. API 테스트
```bash
# 모델 목록
curl http://59.19.231.47/hyundai-parts/api/get_models.php

# 엔진 목록 (model_id=1)
curl http://59.19.231.47/hyundai-parts/api/get_engines.php?model_id=1

# 부품 검색
curl http://59.19.231.47/hyundai-parts/api/search_parts.php?search=오일
```

---

## 🔒 보안 설정

### 1. 관리자 비밀번호 변경 (필수!)
`admin/index.php` 파일 5번째 줄 수정:
```php
// 기존
$isAuthenticated = isset($_GET['auth']) && $_GET['auth'] === 'admin123';

// 변경
$isAuthenticated = isset($_GET['auth']) && $_GET['auth'] === 'MySecurePassword2025!';
```

### 2. 데이터베이스 사용자 분리 (권장)
```sql
-- phpMyAdmin 또는 SSH에서 실행
CREATE USER 'hyundai_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT SELECT, INSERT, UPDATE, DELETE ON hyundai_parts.* TO 'hyundai_user'@'localhost';
FLUSH PRIVILEGES;
```

그리고 `config/db.php` 수정:
```php
define('DB_USER', 'hyundai_user');
define('DB_PASS', 'StrongPassword123!');
```

### 3. 개발 모드 비활성화
`config/db.php` 파일에서 수정:
```php
// 개발 환경 (에러 표시)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 운영 환경 (에러 숨김)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');
```

---

## 🔄 백업 설정

### 데이터베이스 자동 백업
```bash
# crontab 편집
crontab -e

# 매일 새벽 2시 백업
0 2 * * * mysqldump -u root -p'your_password' hyundai_parts | gzip > /volume1/backup/hyundai_parts_$(date +\%Y\%m\%d).sql.gz

# 30일 이상 된 백업 자동 삭제
0 3 * * * find /volume1/backup -name "hyundai_parts_*.sql.gz" -mtime +30 -delete
```

### 파일 백업
```bash
# 수동 백업
tar -czf /volume1/backup/hyundai-parts_$(date +%Y%m%d).tar.gz /volume1/web/hyundai-parts

# 자동 백업 (매주 일요일)
0 1 * * 0 tar -czf /volume1/backup/hyundai-parts_$(date +\%Y\%m\%d).tar.gz /volume1/web/hyundai-parts
```

---

## 📈 모니터링

### 로그 위치
```bash
# Apache 로그
tail -f /var/log/httpd/access_log
tail -f /var/log/httpd/error_log

# PHP 로그 (에러 발생 시)
tail -f /var/log/php/error.log

# MariaDB 로그
tail -f /var/log/mariadb/mariadb.log
```

### 디스크 사용량 확인
```bash
du -sh /volume1/web/hyundai-parts
df -h /volume1
```

---

## ⚠️ 문제 해결

### 500 Internal Server Error
1. Apache 로그 확인: `tail /var/log/httpd/error_log`
2. 파일 권한 확인: `chmod 755` 또는 `chmod 777`
3. `.htaccess` 문법 오류 확인

### 데이터베이스 연결 실패
1. MariaDB 서비스 확인: `synoservice --status MariaDB10`
2. 연결 정보 확인: `config/db.php`
3. 사용자 권한 확인

### Composer 오류
1. PHP 버전 확인: `php -v` (8.0 이상 필요)
2. 메모리 부족: `composer install --no-dev`
3. 캐시 삭제: `composer clear-cache`

### 엑셀 Import 실패
1. PhpSpreadsheet 설치 확인
2. PHP 메모리 증가: `memory_limit = 512M`
3. 파일 권한 확인: `uploads/` 폴더

---

## 📞 지원

배포 후 문제가 발생하면:
1. 로그 파일 확인
2. README.md의 문제 해결 섹션 참조
3. 백업에서 복구

---

**배포 완료 후 체크리스트**
- [ ] 사이트 접속 확인
- [ ] 데이터베이스 연결 확인
- [ ] 엑셀 Import 성공 확인
- [ ] 관리자 비밀번호 변경
- [ ] 백업 설정 완료
- [ ] 보안 설정 적용
- [ ] 로그 모니터링 설정
