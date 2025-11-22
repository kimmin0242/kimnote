# 설치 가이드 (시놀로지 NAS)

## 빠른 설치 (Quick Install)

### 1단계: 파일 업로드
File Station을 사용하여 모든 파일을 업로드합니다:
```
/volume1/web/hyundai-parts/
```

### 2단계: SSH 접속 및 Composer 설치
```bash
# SSH로 NAS 접속
ssh admin@59.19.231.47

# 프로젝트 디렉토리로 이동
cd /volume1/web/hyundai-parts

# Composer 의존성 설치
composer install
```

**Composer가 없는 경우:**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer install
```

### 3단계: 데이터베이스 설정

#### phpMyAdmin 사용
1. 시놀로지 패키지 센터에서 phpMyAdmin 설치
2. phpMyAdmin 접속 (http://59.19.231.47/phpMyAdmin)
3. 새 데이터베이스 생성:
   - 이름: `hyundai_parts`
   - 정렬: `utf8mb4_unicode_ci`
4. SQL 탭에서 `database_setup.sql` 파일 내용 실행

#### SSH 사용
```bash
# 데이터베이스 생성 및 테이블 설정
mysql -u root -p < /volume1/web/hyundai-parts/database_setup.sql
```

### 4단계: 데이터베이스 연결 설정
`config/db.php` 파일 수정:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hyundai_parts');
define('DB_USER', 'root');              // 실제 사용자명
define('DB_PASS', 'your_password');     // 실제 비밀번호
```

### 5단계: 권한 설정
```bash
chmod 755 /volume1/web/hyundai-parts
chmod 777 /volume1/web/hyundai-parts/uploads
```

### 6단계: 엑셀 데이터 Import

#### 방법 1: 관리자 페이지 사용 (권장)
1. 브라우저에서 접속: `http://59.19.231.47/hyundai-parts/admin/?auth=admin123`
2. "데이터 가져오기" 메뉴 선택
3. 엑셀 파일 업로드

#### 방법 2: 직접 스크립트 실행
1. `현대차량 순정부품.xlsx` 파일을 `uploads/` 폴더에 복사
2. 브라우저에서 접속: `http://59.19.231.47/hyundai-parts/import_excel_data.php?clear=true`

### 7단계: 접속 확인
- 사용자 페이지: `http://59.19.231.47/hyundai-parts/`
- 관리자 페이지: `http://59.19.231.47/hyundai-parts/admin/?auth=admin123`

## 상세 설정

### Apache 가상 호스트 (선택사항)
더 깔끔한 URL을 원하는 경우:

`/usr/local/etc/httpd/conf/extra/httpd-vhosts.conf` 편집:
```apache
<VirtualHost *:80>
    ServerName parts.yourdomain.com
    DocumentRoot "/volume1/web/hyundai-parts"
    
    <Directory "/volume1/web/hyundai-parts">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### SSL 인증서 설정 (선택사항)
시놀로지 제어판 > 보안 > 인증서에서 Let's Encrypt 인증서 발급

### 데이터베이스 백업 자동화
```bash
# cron 작업 추가
crontab -e

# 매일 새벽 2시 백업
0 2 * * * mysqldump -u root -p'your_password' hyundai_parts > /volume1/backup/hyundai_parts_$(date +\%Y\%m\%d).sql
```

### PHP 설정 최적화
`/etc/php/php.ini` 또는 `.user.ini` 파일:
```ini
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
memory_limit = 256M
```

## 문제 해결

### 1. 500 Internal Server Error
- Apache 로그 확인: `/var/log/httpd/error_log`
- PHP 에러 로그 확인: `/var/log/php/php-errors.log`
- 파일 권한 확인

### 2. 데이터베이스 연결 실패
```bash
# MariaDB 서비스 상태 확인
sudo synoservice --status MariaDB10

# MariaDB 재시작
sudo synoservice --restart MariaDB10
```

### 3. Composer 의존성 오류
```bash
# Composer 업데이트
composer update

# 캐시 삭제
composer clear-cache
composer install
```

### 4. 엑셀 Import 메모리 부족
`php.ini` 또는 `.htaccess`에서 메모리 증가:
```ini
memory_limit = 512M
```

### 5. 업로드 파일 크기 제한
`.htaccess` 파일 확인:
```apache
php_value upload_max_filesize 20M
php_value post_max_size 20M
```

## 보안 강화

### 1. 관리자 비밀번호 변경
`admin/index.php` 5번째 줄:
```php
$isAuthenticated = isset($_GET['auth']) && $_GET['auth'] === 'your_strong_password';
```

### 2. 데이터베이스 사용자 분리
```sql
CREATE USER 'hyundai_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON hyundai_parts.* TO 'hyundai_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. config 폴더 보호
`.htaccess` 파일에 추가:
```apache
<FilesMatch "^(config|\.env)">
    Require all denied
</FilesMatch>
```

### 4. IP 기반 접근 제한 (관리자 페이지)
`admin/.htaccess` 생성:
```apache
Order Deny,Allow
Deny from all
Allow from 192.168.1.0/24  # 내부 네트워크만 허용
```

## 성능 최적화

### 1. MySQL 인덱스 추가
```sql
CREATE INDEX idx_part_number ON genuine_parts(part_number);
CREATE INDEX idx_engine_type ON car_engines(engine_type);
CREATE INDEX idx_model_name ON car_models(model_name);
```

### 2. PHP OpCache 활성화
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### 3. Gzip 압축 활성화
`.htaccess`에 추가:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

## 업데이트 방법

### 코드 업데이트
```bash
# 백업
cp -r /volume1/web/hyundai-parts /volume1/backup/hyundai-parts_backup_$(date +%Y%m%d)

# Git 사용 시
git pull origin main

# 수동 업데이트
# 새 파일을 업로드하고 config/db.php는 유지
```

### 데이터베이스 스키마 업데이트
```bash
mysql -u root -p hyundai_parts < database_update.sql
```

## 모니터링

### 로그 확인
```bash
# Apache 로그
tail -f /var/log/httpd/access_log
tail -f /var/log/httpd/error_log

# PHP 로그
tail -f /var/log/php/php-errors.log

# MariaDB 로그
tail -f /var/log/mariadb/mariadb.log
```

### 디스크 사용량 확인
```bash
du -sh /volume1/web/hyundai-parts
```

---

**설치 완료 후 반드시 테스트하세요!**
1. 사용자 페이지 접속 확인
2. 관리자 페이지 로그인
3. 부품 검색 테스트
4. CRUD 기능 테스트
5. 엑셀 Import 테스트
