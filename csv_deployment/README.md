# CSV 데이터 관리 시스템 배포 가이드

## 📦 패키지 내용

이 배포 패키지는 현대차 부품 검색 시스템에 **CSV 데이터 관리** 기능을 추가합니다.

### 포함 파일
```
csv_deployment/
├── DEPLOY.sh                # 자동 배포 스크립트
├── README.md                # 이 파일
├── MANUAL_DEPLOY.md         # 수동 배포 가이드
├── export_parts_csv.php     # CSV 내보내기 파일
├── import_parts_csv.php     # CSV 가져오기 파일
└── admin_index.php          # 업데이트된 관리자 페이지
```

---

## 🚀 빠른 배포 (wget 사용)

### 1단계: 배포 패키지 다운로드

Synology NAS SSH에 접속한 후:

```bash
# 임시 디렉토리로 이동
cd /tmp

# 배포 패키지 다운로드 (GitHub Release에서)
wget https://github.com/kimmin0242/kimnote/releases/download/csv-v1.0/csv_deployment.tar.gz

# 압축 해제
tar -xzf csv_deployment.tar.gz

# 배포 디렉토리로 이동
cd csv_deployment
```

### 2단계: 자동 배포 실행

```bash
# 실행 권한 부여
chmod +x DEPLOY.sh

# 배포 스크립트 실행
./DEPLOY.sh
```

배포 스크립트가 자동으로:
- ✅ 기존 파일 백업
- ✅ 새 파일 복사
- ✅ 권한 설정
- ✅ 배포 완료 메시지 표시

---

## 📝 수동 배포 방법

자동 스크립트 사용이 어려운 경우:

### 1. 파일 복사

```bash
# CSV 파일을 루트 디렉토리에 복사
cp export_parts_csv.php /volume1/web/hyundai-parts/
cp import_parts_csv.php /volume1/web/hyundai-parts/

# 관리자 페이지 백업 후 교체
cp /volume1/web/hyundai-parts/admin/index.php /volume1/web/hyundai-parts/admin/index.php.backup
cp admin_index.php /volume1/web/hyundai-parts/admin/index.php
```

### 2. 권한 설정

```bash
chmod 644 /volume1/web/hyundai-parts/export_parts_csv.php
chmod 644 /volume1/web/hyundai-parts/import_parts_csv.php
chmod 644 /volume1/web/hyundai-parts/admin/index.php
```

---

## ✨ 새로운 기능

### 관리자 페이지 변경사항

**변경 전**: 데이터베이스 유틸리티 섹션
- fuel_type 컬럼 추가
- 엔진 데이터 정리
- 테이블 구조 확인
- 파일 다운로드

**변경 후**: CSV 데이터 관리 섹션
- 📥 **CSV 다운로드**: 모든 차량-부품 매핑 데이터를 Excel 파일로 내려받기
- 📤 **CSV 업로드**: 수정한 CSV 파일을 업로드하여 데이터 업데이트
- 📖 **사용 안내**: 4단계 워크플로우 설명

---

## 💼 사용 워크플로우

1. **관리자 페이지 접속**
   ```
   http://59.19.231.47/hyundai-parts/admin/
   ID: admin
   PW: admin123
   ```

2. **CSV 다운로드**
   - "CSV 데이터 관리" 섹션 찾기
   - "CSV 다운로드" 버튼 클릭
   - `vehicle_parts_mapping_2025-11-22.csv` 파일 다운로드

3. **Excel에서 편집**
   - 다운로드한 CSV 파일을 Excel이나 구글 스프레드시트에서 열기
   - 데이터 수정 (행 추가, 수정, 삭제)
   - 컬럼 순서 변경 금지!

4. **CSV로 저장**
   - Excel에서 "파일 > 다른 이름으로 저장"
   - 파일 형식: **CSV UTF-8 (쉼표로 분리)(*.csv)** 선택
   - 저장

5. **CSV 업로드**
   - 관리자 페이지로 돌아가기
   - "CSV 업로드" 섹션에서 파일 선택
   - "CSV 업로드" 버튼 클릭
   - 처리 결과 확인 (성공/실패 개수)

---

## 🔧 기술 사양

### CSV 내보내기 (export_parts_csv.php)
- **출력 형식**: CSV UTF-8 with BOM
- **컬럼**: 차명, 세대, 연료, 엔진, 부품번호, 부품명, 카테고리, 용량, 부품타입, 수량, 위치, 비고, 교체주기
- **파일명**: `vehicle_parts_mapping_YYYY-MM-DD.csv`
- **Excel 호환**: UTF-8 BOM으로 한글 깨짐 방지

### CSV 가져오기 (import_parts_csv.php)
- **입력 형식**: CSV UTF-8 (with/without BOM)
- **트랜잭션**: 오류 시 자동 롤백
- **자동 생성**: 누락된 차량 모델, 엔진, 부품 자동 추가
- **업데이트**: 기존 매핑 업데이트 또는 신규 삽입
- **검증**: 필수 필드 및 데이터 무결성 체크
- **오류 보고**: 행 번호 포함 상세 오류 메시지

---

## 📊 CSV 파일 구조

### 컬럼 순서 (변경 금지)

| 순서 | 컬럼명 | 설명 | 필수 | 예시 |
|------|--------|------|------|------|
| 1 | 차명 | 차량 모델명 | ✅ | 아반떼 |
| 2 | 세대 | 세대 정보 | ✅ | 7세대 (CN7) |
| 3 | 연료 | 연료 타입 | ✅ | 가솔린 |
| 4 | 엔진 | 엔진 타입 | ✅ | 1.6 터보 |
| 5 | 부품번호 | 부품 번호 | ✅ | 26300-2B000 |
| 6 | 부품명 | 부품 이름 | ✅ | 엔진오일 필터 |
| 7 | 주카테고리 | 메인 카테고리 | ✅ | 필터 |
| 8 | 부카테고리 | 서브 카테고리 | ❌ | 오일 필터 |
| 9 | 용량 | 부품 용량/규격 | ❌ | 1L |
| 10 | 부품타입 | 부품 유형 | ❌ | engine_oil |
| 11 | 수량 | 필요 수량 | ❌ | 1 |
| 12 | 위치 | 부품 위치 | ❌ | 엔진룸 |
| 13 | 비고 | 추가 정보 | ❌ | 정기 교환 |
| 14 | 교체주기 | 교환 주기 | ❌ | 10000km |

---

## ⚠️ 주의사항

### CSV 편집 시
- ✅ **첫 번째 행(헤더)은 삭제하지 마세요**
- ✅ **컬럼 순서를 변경하지 마세요**
- ✅ **필수 컬럼(차명, 세대, 연료, 엔진, 부품번호, 부품명, 주카테고리)은 비워두지 마세요**
- ✅ **Excel에서 저장 시 반드시 "CSV UTF-8" 형식 선택**
- ⚠️ 쉼표(,)가 포함된 데이터는 큰따옴표("")로 감싸집니다

### 데이터 업로드 시
- 💾 **업로드 전 데이터베이스 백업 권장**
- 🔄 **트랜잭션 사용**: 오류 발생 시 모든 변경사항이 자동으로 롤백됩니다
- ➕ **자동 생성**: 없는 차량 모델, 엔진, 부품은 자동으로 추가됩니다
- 🔄 **중복 처리**: 같은 매핑이 있으면 업데이트, 없으면 새로 추가됩니다

---

## 🐛 문제 해결

### 1. CSV 다운로드가 안 되는 경우
```bash
# 파일 존재 확인
ls -la /volume1/web/hyundai-parts/export_parts_csv.php

# 파일 권한 확인 (644 여야 함)
chmod 644 /volume1/web/hyundai-parts/export_parts_csv.php
```

### 2. CSV 업로드 오류
- **"파일을 선택해주세요"**: CSV 파일 선택 확인
- **"CSV 파일만 업로드 가능합니다"**: 파일 확장자가 .csv인지 확인
- **"헤더 검증 실패"**: CSV 파일의 첫 번째 행(헤더)이 올바른지 확인
- **"필수 컬럼 누락"**: 차명, 세대, 연료 등 필수 항목이 비어있지 않은지 확인

### 3. 한글 깨짐 현상
- **Excel에서 열 때**: UTF-8 BOM으로 자동 다운로드되므로 정상적으로 보여야 함
- **저장 시**: 반드시 "CSV UTF-8 (쉼표로 분리)" 형식으로 저장

### 4. 로그 확인
```bash
# PHP 오류 로그 확인
tail -f /var/log/php-fpm.log

# 웹서버 오류 로그 확인
tail -f /var/log/nginx/error.log
```

---

## 🔙 롤백 방법

배포 후 문제가 발생한 경우:

```bash
# 백업 파일 확인
ls -la /volume1/web/hyundai-parts/admin/index.php.backup*

# 최신 백업으로 복구
cp /volume1/web/hyundai-parts/admin/index.php.backup.YYYYMMDD_HHMMSS \
   /volume1/web/hyundai-parts/admin/index.php

# CSV 파일 삭제 (필요시)
rm /volume1/web/hyundai-parts/export_parts_csv.php
rm /volume1/web/hyundai-parts/import_parts_csv.php
```

---

## 📞 지원

- **GitHub**: https://github.com/kimmin0242/kimnote
- **Pull Request**: https://github.com/kimmin0242/kimnote/pull/1
- **이슈 등록**: https://github.com/kimmin0242/kimnote/issues

---

## 📄 라이선스

현대차 부품 검색 시스템의 일부로, 동일한 라이선스 적용

---

**배포 버전**: 1.0.0  
**배포 날짜**: 2025-11-22  
**호환 버전**: PHP 8.0+, MariaDB 10+
