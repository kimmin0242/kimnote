# 현대차 순정부품 관리 시스템

시놀로지 NAS 환경에서 현대차 순정부품을 효율적으로 관리하기 위한 웹 기반 시스템입니다.

## 📋 기능

### 사용자 페이지
- 차량 모델 및 엔진별 부품 검색
- 부품명, 부품번호로 검색
- 호환 엔진 정보 표시
- 반응형 디자인 (모바일 지원)

### 관리자 페이지
- 차량 모델 관리 (추가/수정/삭제)
- 엔진 정보 관리 (추가/수정/삭제)
- 부품 정보 관리 (추가/수정/삭제)
- 엑셀 파일 대량 import 기능
- 대시보드 통계

## 🛠 시스템 요구사항

- PHP 8.0 이상
- MariaDB 10 이상
- Apache HTTP Server 2.4
- Composer (PHP 의존성 관리)

## 📦 설치 방법

### 1. 파일 업로드
시놀로지 NAS의 웹 서버 디렉토리에 파일을 업로드합니다.
```
/volume1/web/hyundai-parts/
```

### 2. Composer 의존성 설치
SSH로 NAS에 접속하여 프로젝트 디렉토리로 이동 후 실행:
```bash
cd /volume1/web/hyundai-parts
composer install
```

### 3. 데이터베이스 설정

#### 3-1. MariaDB 데이터베이스 생성
phpMyAdmin 또는 SSH로 접속하여 실행:
```sql
CREATE DATABASE hyundai_parts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 3-2. 데이터베이스 사용자 생성 (선택사항)
```sql
CREATE USER 'hyundai_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON hyundai_parts.* TO 'hyundai_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 3-3. 테이블 생성
```bash
mysql -u root -p hyundai_parts < database_setup.sql
```

또는 phpMyAdmin에서 `database_setup.sql` 파일을 import합니다.

### 4. 데이터베이스 연결 설정
`config/db.php` 파일을 열어 데이터베이스 정보를 수정합니다:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hyundai_parts');
define('DB_USER', 'root');          // 사용자명
define('DB_PASS', 'your_password'); // 비밀번호
```

### 5. 권한 설정
```bash
chmod 755 /volume1/web/hyundai-parts
chmod 777 /volume1/web/hyundai-parts/uploads
```

### 6. 엑셀 데이터 Import

#### 6-1. 엑셀 파일 업로드
`uploads/` 폴더에 "현대차량 순정부품.xlsx" 파일을 업로드합니다.

#### 6-2. 관리자 페이지에서 Import
1. 브라우저에서 접속: `http://59.19.231.47/hyundai-parts/admin/?auth=admin123`
2. 좌측 메뉴에서 "데이터 가져오기" 클릭
3. 엑셀 파일 선택 후 업로드

## 🌐 접속 URL

- **사용자 페이지**: `http://59.19.231.47/hyundai-parts/`
- **관리자 페이지**: `http://59.19.231.47/hyundai-parts/admin/?auth=admin123`

## 🔐 보안

### 관리자 인증
기본 인증키: `admin123`

**중요**: 실제 운영 시 반드시 변경하세요!
`admin/index.php` 파일 5번째 줄에서 변경 가능:
```php
$isAuthenticated = isset($_GET['auth']) && $_GET['auth'] === 'your_new_password';
```

더 강력한 보안을 위해서는 세션 기반 로그인 시스템을 구현하는 것을 권장합니다.

## 📁 디렉토리 구조

```
hyundai-parts/
├── config/              # 설정 파일
│   └── db.php          # 데이터베이스 연결
├── api/                # 사용자 API
│   ├── get_models.php
│   ├── get_engines.php
│   └── search_parts.php
├── admin/              # 관리자 페이지
│   ├── index.php
│   ├── js/
│   │   └── admin.js
│   └── api/           # 관리자 API
│       ├── get_stats.php
│       ├── models.php
│       ├── engines.php
│       ├── parts.php
│       └── import_excel.php
├── uploads/           # 업로드 파일 (권한: 777)
├── vendor/            # Composer 의존성
├── index.php          # 메인 페이지
├── database_setup.sql # DB 스키마
├── composer.json
└── README.md
```

## 🗄 데이터베이스 스키마

### car_models (차량 모델)
- id (PK)
- manufacturer (제조사)
- category (카테고리)
- brand_name (브랜드명)
- model_name (모델명)
- generation (세대)
- created_at (생성일)

### car_engines (엔진 정보)
- id (PK)
- car_model_id (FK → car_models)
- engine_type (엔진 타입)
- engine_name (엔진명)

### genuine_parts (순정부품)
- id (PK)
- category_main (대분류)
- category_sub (소분류)
- product_name (제품명)
- capacity (용량)
- part_number (부품번호, UNIQUE)
- compatible_engines (호환 엔진)
- notes (비고)
- created_at (생성일)

## 📊 엑셀 데이터 형식

엑셀 파일은 다음 구조를 따라야 합니다:

- **1행**: 메인 헤더
- **2행**: 부품 카테고리 헤더
- **3행 이후**: 데이터
  - A열: 제조사
  - B열: 대분류
  - C열: 소분류
  - D열: 모델명
  - E열: 상세 트림/세대
  - F열: 동력원 유형
  - G열: 세부 엔진/동력계
  - H열~X열: 부품번호

## 🔧 문제 해결

### Composer가 설치되어 있지 않은 경우
시놀로지 NAS에 SSH로 접속하여:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 데이터베이스 연결 오류
1. `config/db.php`의 연결 정보 확인
2. MariaDB 서비스가 실행 중인지 확인
3. 데이터베이스와 사용자 권한 확인

### 엑셀 Import 오류
1. PhpSpreadsheet 설치 확인: `composer require phpoffice/phpspreadsheet`
2. PHP 메모리 제한 확인: `.htaccess` 또는 `php.ini`에서 설정
3. 업로드 파일 크기 제한 확인

### 403 Forbidden 오류
디렉토리 권한 확인:
```bash
chmod -R 755 /volume1/web/hyundai-parts
chmod -R 777 /volume1/web/hyundai-parts/uploads
```

## 📝 향후 개선 사항

- [ ] 세션 기반 로그인 시스템
- [ ] 부품 이미지 업로드 기능
- [ ] 부품 가격 정보 추가
- [ ] 재고 관리 기능
- [ ] PDF 출력 기능
- [ ] 부품 비교 기능
- [ ] 검색 히스토리
- [ ] 사용자 권한 관리

## 📞 지원

문제가 발생하거나 개선 사항이 있으면 관리자에게 문의하세요.

---

**Version**: 1.0  
**Last Updated**: 2025-11  
**License**: Private Use
