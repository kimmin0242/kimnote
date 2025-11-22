## 해결된 문제
- G80 RG3 검색 시 24개 범용 부품 반환 문제 해결 (정확한 3-4개 차량 전용 부품으로 개선)
- 근본 원인: `compatible_engines = '전체'`가 차량 필터링 없이 모든 범용 부품을 가져옴
- ✅ **displacement 컬럼 오류 완전 해결** (`SQLSTATE[42S22]` 오류)
- ✅ **제품명 표시 오류 수정** (검색 결과에 "부품명 없음" 대신 실제 제품명 표시)

## 데이터베이스 구조
- 차량-부품 매핑 구조 구현:
  * `car_models` (제조사, 브랜드, 카테고리, 모델명, 세대)
  * `car_engines` (차량모델ID, 연료타입, 엔진타입)
  * `vehicle_parts_mapping` (엔진ID, 부품ID, 부품타입, 수량)
- CASCADE 삭제로 데이터 무결성 보장
- 런타임 컬럼 존재 여부 체크로 하위 호환성 확보

## 신규 기능

### 1. 차량 정보 관리 시스템 (`admin_vehicle_manager.php`)
- 차량 모델, 세대, 엔진 완전 CRUD 구현
- **UI 개선사항:**
  * ✅ 카테고리 드롭다운을 브랜드 위로 이동
  * ✅ 브랜드 옵션 대폭 확장 (옵트그룹 구조):
    - **제너시스**: G90, G80, G70, GV80, GV70, GV60, Electrified G80, Electrified GV70
    - **N 라인**: 아반떼 N, 벨로스터 N, i30 N, 코나 N, 아이오닉 5 N
    - **아이오닉**: 아이오닉 5, 아이오닉 6, 아이오닉 7
    - **현대 승용**: 그랜저, 소나타, 아반떼, 쏘나타
    - **현대 SUV**: 팰리세이드, 싼타페, 투싼, 코나, 베뉴, 캐스퍼
    - **현대 MPV**: 스타리아, 스타렉스, 쏠라티
    - **기타**: 직접 입력... (커스텀 브랜드 입력 기능)
  * ✅ '직접 입력' 선택 시 동적 입력 필드 표시
- 연료 타입 지원: 가솔린, 디젤, LPG, 하이브리드, 플러그인 하이브리드, 전기
- 엔진 목록 항상 표시 ('목록 보기' 버튼 제거)
- 각 엔진별 개별 수정/삭제 버튼

### 2. 차량-부품 매핑 시스템 (`admin_vehicle_parts.php`)
- **2단계 드롭다운 카테고리 선택:**
  * 주요 카테고리: 오일 및 액체류, 필터류, 제동류, 전장 및 기타 부품류, 기타
  * 카테고리별 세부 타입 동적 로딩
  * '기타' 카테고리에 커스텀 입력 지원
- `genuine_parts` 테이블에 부품 자동 생성
- 트랜잭션 기반 일괄 부품 매핑
- AJAX 기반 실시간 차량/엔진/부품 선택

### 3. 향상된 검색 API (`api/search_parts_advanced.php`)
- `vehicle_parts_mapping` 테이블 자동 감지
- 매핑 테이블 없을 시 레거시 검색으로 자동 폴백
- 정확한 수량과 함께 차량 전용 부품 반환
- 별도 '오일량' 표시 제거 (부품 데이터에 통합)
- ✅ **product_name 필드 반환으로 제품명 정상 표시**

### 4. 🆕 Excel 데이터 변환 도구 (방법 2: 차종별 시트 분리)
- **Python 변환 스크립트** (`excel_converter_method2.py`):
  * 가로 형식 부품 데이터를 세로 형식으로 자동 변환
  * 5개 시트로 자동 분류:
    - `제너시스_세단` (G90, G80, G70)
    - `제너시스_SUV` (GV60, GV70, GV80, GV90)
    - `현대_승용차` (아반떼, 쏘나타, 그랜저 등)
    - `현대_SUV` (코나, 투싼, 싼타페, 팰리세이드 등)
    - `현대_친환경차` (아이오닉5, 아이오닉6, 넥쏘 등)
  * 부품 옵션 자동 분리 처리 (`[고급형/활성탄]`, `[일반형]` 등)
  * 부품 대분류 자동 매핑 (오일류, 필터류, 제동류 등)
  * Excel 스타일링: 헤더 고정, 컬럼 너비 자동 조정

- **웹 관리 인터페이스** (`admin_excel_converter.php`):
  * 드래그 앤 드롭 파일 업로드
  * 실시간 변환 진행상황 표시
  * 변환 완료 후 통계 표시
  * 변환된 파일 즉시 다운로드

- **API 백엔드** (`admin_excel_converter_api.php`):
  * 파일 업로드 처리 및 검증
  * Python 스크립트 실행
  * 변환 결과 통계 파싱
  * 오류 처리 및 사용자 피드백

- **샘플 데이터 생성기** (`create_sample_excel.py`):
  * 테스트용 Excel 파일 자동 생성
  * 7개 차종 샘플 데이터 포함

### 5. 데이터베이스 마이그레이션 도구
- `add_fuel_type_column.php`: 기존 설치에 fuel_type 컬럼 추가
- `cleanup_engine_data.php`: engine_type에서 중복 연료 타입 텍스트 제거
- `check_car_engines_structure.php`: 테이블 구조 검증

### 6. API 엔드포인트
- `admin_vehicle_api.php`: 차량/엔진 CRUD 작업
  * ✅ **완전한 컬럼 존재 체크** (displacement, power_output, description)
  * ✅ **모든 SELECT 쿼리 안전화** (`get_vehicle`, `get_engine`, `get_engines`)
  * ✅ **모든 INSERT/UPDATE 쿼리 안전화** (`add_vehicle`, `update_vehicle`, `add_engine`, `update_engine`)
  * ✅ **car_models 테이블 description 컬럼 체크 추가**
  * ✅ **SQLSTATE[42S22] 오류 완전 해결**
- `vehicle_models_api.php`: 제조사별 차량 모델 조회
- `vehicle_engines_api.php`: 모델 ID별 엔진 조회 (연료 필터링)

## 기술 개선사항
- 모든 PHP 파일에 UTF-8 인코딩 헤더 적용
- 세션 기반 관리자 인증 (admin/admin123)
- Bootstrap 5.1.3 반응형 UI
- 적절한 에러 처리와 함께 JSON API 응답
- 롤백 지원 트랜잭션 기반 데이터 작업
- 기존 데이터베이스 스키마와 완전 호환
- Python openpyxl 라이브러리 활용 (Excel 처리)

## 버그 수정
- ✅ **차량 수정 시 displacement 컬럼 오류 완전 수정**
- ✅ `admin_vehicle_api.php`의 모든 액션에 런타임 컬럼 체크 추가
- ✅ `admin_vehicle_manager.php`의 엔진 표시 시 컬럼 체크 추가
- ✅ 선택적 컬럼 없을 때 기본 컬럼으로 폴백 구현
- ✅ `car_models` 및 `car_engines` 테이블 모든 쿼리 안전화
- ✅ **검색 결과 제품명 표시 오류 수정** (`part.part_name` → `part.product_name`)

## 변경된 파일
- **수정**: `index.php` (제품명 필드 수정)
- **수정**: `admin_vehicle_parts.php` (2단계 드롭다운)
- **수정**: `admin/index.php` (Excel 변환 도구 링크 추가)
- **수정**: `api/search_parts_advanced.php` (매핑 기반 검색 + product_name 반환)
- **신규**: `admin_vehicle_manager.php` (차량 관리 인터페이스 + 완전한 컬럼 체크)
- **신규**: `admin_vehicle_api.php` (완전한 런타임 컬럼 체크 + 모든 쿼리 안전화)
- **신규**: `admin_excel_converter.php` (Excel 변환 웹 인터페이스)
- **신규**: `admin_excel_converter_api.php` (변환 API 백엔드)
- **신규**: `excel_converter_method2.py` (Python 변환 스크립트)
- **신규**: `create_sample_excel.py` (샘플 데이터 생성기)
- **신규**: `EXCEL_CONVERTER_README.md` (변환 도구 상세 문서)
- **신규**: `requirements_excel.txt` (Python 의존성)
- **신규**: `api/vehicle_models_api.php`, `api/vehicle_engines_api.php`
- **신규**: `add_fuel_type_column.php`, `cleanup_engine_data.php`
- **신규**: `DOWNLOAD_ADMIN_V2.html`, `download_files.html`

## 테스트 상태
- ✅ G80 RG3 2.5 T-GDi 완전 매핑 및 정확한 부품 개수
- ✅ 선택적 컬럼 있는/없는 시스템에서 데이터베이스 마이그레이션 테스트 완료
- ✅ **displacement 컬럼 오류 완전 해결 및 테스트 완료**
- ✅ **제품명 정상 표시 확인**
- ✅ **Excel 변환 도구 샘플 데이터 테스트 완료** (7개 차종 → 37개 부품 레코드)
- ⚠️ 커스텀 브랜드 입력 구현 완료 (사용자 테스트 필요)

## 배포 지침
1. **Python 환경 설정 (Excel 변환용):**
   ```bash
   pip3 install -r requirements_excel.txt
   ```

2. **수정된 파일 프로덕션 서버에 업로드:**
   - `admin_vehicle_manager.php` (UI 개선 + 컬럼 체크)
   - `admin_vehicle_api.php` (완전한 런타임 컬럼 체크)
   - `admin_vehicle_parts.php`
   - `admin_excel_converter.php`, `admin_excel_converter_api.php`
   - `excel_converter_method2.py`, `create_sample_excel.py`
   - `index.php` (제품명 수정)
   - `api/search_parts_advanced.php`
   - 모든 신규 API 파일
   
3. **디렉토리 생성:**
   ```bash
   mkdir -p temp_uploads converted_files
   chmod 755 temp_uploads converted_files
   ```

4. 구 스키마에서 업그레이드 시 `add_fuel_type_column.php` 1회 실행
5. 커스텀 브랜드 입력 기능 테스트 ('직접 입력...' 옵션)
6. `cleanup_engine_data.php`로 엔진 데이터 정리
7. **Ctrl + Shift + R**로 브라우저 캐시 완전 새로고침

## 다음 단계
1. ✅ **Excel 변환 도구로 부품 데이터 정리** (방법 2 완료)
2. 변환된 Excel 데이터 임포트 기능 개발 예정
3. 실제 데이터로 커스텀 브랜드 입력 테스트
4. 모든 CRUD 작업 정상 동작 확인
5. displacement 컬럼 오류 재발 없음 확인

## 사용자 요청 반영
- ✅ "방법 2번을 먼저 해주시면 엑셀에 새로운 시트로 넣어볼깨요" - **완료**
  * 가로 형식 데이터를 차종별 5개 시트로 자동 분리
  * 세로 형식으로 정규화하여 변환
  * 웹 인터페이스와 CLI 모두 지원
