# Composer 없이 사용하기

## ✅ 가능한 기능 (Composer 불필요)

- ✅ 메인 페이지 (부품 검색)
- ✅ 관리자 페이지
- ✅ 차량/엔진/부품 CRUD
- ✅ 검색 기능
- ✅ 대시보드

## ❌ 제한 기능 (PhpSpreadsheet 필요)

- ❌ 엑셀 Import 기능
- ❌ `import_excel_data.php` 실행 불가
- ❌ 관리자 페이지의 "데이터 가져오기" 메뉴

## 🎯 해결 방법

### Option 1: 수동으로 데이터 입력
관리자 페이지에서 차량/엔진/부품을 하나씩 추가

### Option 2: SQL로 직접 입력
phpMyAdmin에서 SQL INSERT 문으로 데이터 추가

### Option 3: Composer 설치 (위 방법 1 참조)

## 📝 엑셀 없이 시작하기

1. 데이터베이스만 생성
2. 관리자 페이지에서 수동 입력
3. 나중에 Composer 설치 후 엑셀 Import

**대부분의 기능은 정상 작동합니다!**
