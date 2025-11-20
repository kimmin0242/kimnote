# 데이터베이스 구조 개선 마이그레이션 가이드

## 📋 개요

기존의 `compatible_engines = '전체'` 방식에서 **차량별 부품 매핑 테이블**을 사용하는 정확한 구조로 변경합니다.

---

## 🎯 새로운 DB 구조의 장점

### 기존 문제점
- ❌ 모든 '전체' 부품이 검색됨 (엔진오일 05100-00451, 05100-2S400 모두 표시)
- ❌ 차량별로 정확한 부품을 지정할 수 없음
- ❌ 오일량, 수량 등 부가 정보 저장 불가

### 새로운 구조
- ✅ 차량-부품 정확한 매핑 (G80 RG3 2.5T → 05100-2S400만 표시)
- ✅ 부품 타입, 수량, 위치 정보 저장
- ✅ 엑셀 데이터 구조 그대로 반영
- ✅ 향후 관리자 페이지에서 CRUD 가능

---

## 🗄️ 새 테이블 구조

```
car_models (기존)
  ↓ 1:N
car_engines (기존)
  ↓ 1:N
vehicle_parts_mapping ★ NEW ★
  ↓ N:1
genuine_parts (기존)
```

### vehicle_parts_mapping 테이블 (핵심)

| 컬럼 | 타입 | 설명 | 예시 |
|------|------|------|------|
| car_engine_id | INT | 엔진 ID | 1 (G80 RG3 2.5T) |
| part_id | INT | 부품 ID | 10 (05100-2S400) |
| part_type | VARCHAR | 부품 타입 | '엔진오일(대)' |
| quantity | VARCHAR | 수량/용량 | '1개', '4L+1L 2개' |
| position | VARCHAR | 위치 | '좌', '우', '앞축' |
| notes | TEXT | 비고 | '총 오일량: 6L' |

---

## 📝 마이그레이션 단계

### 1단계: 백업 (필수!)

```bash
# Synology SSH 접속
ssh kdm0242@your-synology-ip

# 데이터베이스 백업
mysqldump -u root -p hyundai_parts > /volume1/backup/hyundai_parts_$(date +%Y%m%d_%H%M%S).sql
```

### 2단계: 새 테이블 생성

```bash
cd /volume1/web/hyundai-parts

# SQL 파일 실행
mysql -u root -p hyundai_parts < database_structure_v2.sql
```

또는 phpMyAdmin에서:
1. `hyundai_parts` 데이터베이스 선택
2. SQL 탭 클릭
3. `database_structure_v2.sql` 파일 내용 붙여넣기
4. 실행

### 3단계: 엑셀 데이터 임포트

#### 방법 A: 샘플 데이터 임포트 (테스트용)

```bash
cd /volume1/web/hyundai-parts
php import_excel_to_mapping.php
```

이 스크립트는 G80 RG3 3개 엔진의 샘플 데이터를 입력합니다:
- 가솔린 2.5 터보
- 가솔린 3.5 터보
- 디젤 2.2

#### 방법 B: 전체 엑셀 데이터 임포트 (권장)

1. **엑셀 데이터를 PHP 배열로 변환**
   - `import_excel_to_mapping.php` 파일 열기
   - `$excelData` 배열에 모든 차량 데이터 추가
   - 엑셀의 각 행을 배열 형태로 입력

2. **스크립트 실행**
```bash
php import_excel_to_mapping.php
```

### 4단계: 검색 API 변경

#### 옵션 1: 자동 전환 (권장)

`api/search_parts_advanced.php`를 `api/search_parts_with_mapping.php`로 교체:

```bash
cd /volume1/web/hyundai-parts/api
cp search_parts_advanced.php search_parts_advanced.php.old
cp search_parts_with_mapping.php search_parts_advanced.php
```

이 파일은:
- ✅ `vehicle_parts_mapping` 테이블이 있으면 새 방식 사용
- ✅ 없으면 기존 방식으로 자동 폴백
- ✅ 점진적 마이그레이션 가능

#### 옵션 2: 수동 교체

프론트엔드(`index.php`)의 API 호출 부분 변경:
```javascript
// 기존
const response = await fetch(`api/search_parts_advanced.php?${params}`);

// 변경
const response = await fetch(`api/search_parts_with_mapping.php?${params}`);
```

### 5단계: 테스트

1. **G80 RG3 가솔린 2.5 터보 검색**
   - 예상 결과: 4개 부품 (엔진오일 대/소, 오일필터, 오일량)
   - ✅ 05100-2S400, 05100-2S100만 표시
   - ❌ 05100-00451, 05100-00151 표시 안 됨

2. **G80 RG3 가솔린 3.5 터보 검색**
   - 예상 결과: 다른 부품 (3.5T 전용)

3. **카테고리별 확인**
   - 엔진류: 엔진오일, 오일필터만
   - 필터류: 에어필터, 에어컨필터만
   - 와이퍼: 좌/우 구분

---

## 🔧 트러블슈팅

### 문제: "Table 'vehicle_parts_mapping' doesn't exist"

**해결:**
```sql
-- phpMyAdmin 또는 MySQL에서 실행
USE hyundai_parts;
SHOW TABLES;

-- vehicle_parts_mapping이 없으면
SOURCE /volume1/web/hyundai-parts/database_structure_v2.sql;
```

### 문제: "Duplicate entry" 오류

**해결:**
```sql
-- 기존 데이터 확인
SELECT * FROM vehicle_parts_mapping WHERE car_engine_id = 1;

-- 중복 삭제
DELETE FROM vehicle_parts_mapping WHERE id IN (SELECT id FROM ... LIMIT 1);
```

### 문제: 부품이 여전히 많이 나옴

**원인:** 데이터 임포트 안 됨

**해결:**
```bash
# 임포트 스크립트 실행
php import_excel_to_mapping.php

# 데이터 확인
mysql -u root -p
USE hyundai_parts;
SELECT COUNT(*) FROM vehicle_parts_mapping;
```

---

## 📊 데이터 확인 쿼리

### G80 RG3 부품 확인

```sql
SELECT 
    cm.model_name,
    cm.generation,
    ce.fuel_type,
    ce.engine_type,
    gp.category_main,
    gp.part_number,
    gp.product_name,
    vpm.quantity,
    vpm.position
FROM vehicle_parts_mapping vpm
JOIN car_engines ce ON vpm.car_engine_id = ce.id
JOIN car_models cm ON ce.car_model_id = cm.id
JOIN genuine_parts gp ON vpm.part_id = gp.id
WHERE cm.model_name = 'G80' 
  AND cm.generation LIKE '%RG3%'
ORDER BY ce.engine_type, gp.category_main;
```

### 통계 확인

```sql
-- 차량별 부품 개수
SELECT 
    cm.model_name,
    cm.generation,
    ce.fuel_type,
    ce.engine_type,
    COUNT(*) as parts_count
FROM vehicle_parts_mapping vpm
JOIN car_engines ce ON vpm.car_engine_id = ce.id
JOIN car_models cm ON ce.car_model_id = cm.id
GROUP BY cm.model_name, cm.generation, ce.fuel_type, ce.engine_type;
```

---

## 🚀 롤백 방법 (문제 발생 시)

### 1. 데이터베이스 복원

```bash
# 백업 파일 복원
mysql -u root -p hyundai_parts < /volume1/backup/hyundai_parts_20250120_123456.sql
```

### 2. API 원복

```bash
cd /volume1/web/hyundai-parts/api
cp search_parts_advanced.php.old search_parts_advanced.php
```

### 3. 새 테이블 삭제 (선택)

```sql
DROP TABLE IF EXISTS vehicle_parts_mapping;
```

---

## 📈 향후 작업

### 1. 관리자 페이지 개선
- [ ] 차량별 부품 매핑 CRUD
- [ ] 엑셀 업로드 기능
- [ ] 부품 일괄 수정

### 2. 데이터 입력
- [ ] 모든 차량 데이터 입력
- [ ] 부품 상세 정보 입력 (가격, 제조사 등)
- [ ] 교체 주기 정보 입력

### 3. 검색 기능 개선
- [ ] 부품별 가격 표시
- [ ] 교체 주기 알림
- [ ] 유사 부품 추천

---

## 📞 지원

- GitHub: https://github.com/kimmin0242/kimnote
- 문제 발생 시 Issues에 등록

---

**마이그레이션 완료 체크리스트:**

- [ ] 데이터베이스 백업 완료
- [ ] 새 테이블 생성 완료
- [ ] 샘플 데이터 임포트 완료
- [ ] 검색 API 교체 완료
- [ ] G80 RG3 테스트 완료
- [ ] 다른 차량 테스트 완료
- [ ] 기존 API 백업 완료

모든 항목 체크 후 프로덕션 배포!
