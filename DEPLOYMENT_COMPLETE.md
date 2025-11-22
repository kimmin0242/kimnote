# ✅ CSV 데이터 관리 시스템 - 배포 준비 완료

## 📦 배포 패키지 생성 완료

모든 파일이 준비되었으며, wget을 사용한 배포가 가능합니다.

---

## 🚀 빠른 배포 방법

### Synology NAS SSH에서 실행:

```bash
# SSH 접속
ssh admin@59.19.231.47

# 배포 패키지 다운로드 및 자동 설치
cd /tmp
wget http://59.19.231.47/hyundai-parts/csv_deployment.tar.gz
tar -xzf csv_deployment.tar.gz
cd csv_deployment
chmod +x DEPLOY.sh && ./DEPLOY.sh
```

**소요 시간**: 약 1분

---

## 📥 다운로드 링크

### 1. 웹 인터페이스 (추천)
```
http://59.19.231.47/hyundai-parts/DOWNLOAD_CSV_DEPLOYMENT.html
```
- 시각적인 가이드와 단계별 설명
- 다운로드 버튼 클릭으로 간편하게 받기

### 2. 직접 다운로드
```bash
wget http://59.19.231.47/hyundai-parts/csv_deployment.tar.gz
```

### 3. 간편 가이드
```
http://59.19.231.47/hyundai-parts/WGET_DEPLOYMENT_GUIDE.md
```

---

## 📁 패키지 내용

```
csv_deployment.tar.gz (12 KB)
├── DEPLOY.sh                 # ⚡ 자동 배포 스크립트
├── README.md                 # 📖 상세 사용 가이드
├── MANUAL_DEPLOY.md          # 📝 수동 배포 가이드
├── export_parts_csv.php      # 📥 CSV 내보내기
├── import_parts_csv.php      # 📤 CSV 가져오기 (검증 포함)
└── admin_index.php           # 🎨 업데이트된 관리자 페이지
```

---

## ✨ 주요 변경사항

### 관리자 페이지 (admin/index.php)

**변경 전:**
```
데이터베이스 유틸리티
├── fuel_type 컬럼 추가
├── 엔진 데이터 정리
├── 테이블 구조 확인
└── 파일 다운로드
```

**변경 후:**
```
CSV 데이터 관리
├── 📥 CSV 다운로드 (모든 매핑 데이터)
├── 📤 CSV 업로드 (일괄 업데이트)
├── 📖 사용 방법 안내
└── ⚙️ 자동 검증 및 오류 보고
```

---

## 🎯 배포 후 기능

### 1. CSV 내보내기
- **파일명**: `vehicle_parts_mapping_2025-11-22.csv`
- **인코딩**: UTF-8 BOM (Excel 호환)
- **컬럼**: 차명, 세대, 연료, 엔진, 부품번호, 부품명, 카테고리, 용량, 수량 등
- **다운로드**: 관리자 페이지에서 버튼 클릭

### 2. CSV 가져오기
- **자동 검증**: 필수 필드 및 데이터 무결성 체크
- **자동 생성**: 누락된 차량 모델, 엔진, 부품 자동 추가
- **트랜잭션**: 오류 발생 시 자동 롤백
- **상세 리포트**: 처리된 행, 성공/실패 개수, 오류 목록

### 3. 사용 워크플로우
1. CSV 다운로드 (관리자 페이지)
2. Excel에서 편집
3. CSV UTF-8로 저장
4. CSV 업로드 (관리자 페이지)
5. 결과 확인 및 페이지 자동 새로고침

---

## 🛠 자동 배포 스크립트 기능

`DEPLOY.sh`가 자동으로 처리:

1. ✅ **백업 생성**
   - `admin/index.php` → `admin/index.php.backup.20251122_143000`
   
2. ✅ **파일 복사**
   - `export_parts_csv.php` → `/volume1/web/hyundai-parts/`
   - `import_parts_csv.php` → `/volume1/web/hyundai-parts/`
   - `admin_index.php` → `/volume1/web/hyundai-parts/admin/index.php`

3. ✅ **권한 설정**
   - 모든 PHP 파일: `644` (rw-r--r--)

4. ✅ **배포 확인**
   - 파일 존재 확인
   - 백업 파일 위치 출력
   - 접속 URL 안내

---

## 🔍 배포 확인 방법

### 1. 파일 확인
```bash
ls -la /volume1/web/hyundai-parts/export_parts_csv.php
ls -la /volume1/web/hyundai-parts/import_parts_csv.php
ls -la /volume1/web/hyundai-parts/admin/index.php
```

### 2. 백업 확인
```bash
ls -la /volume1/web/hyundai-parts/admin/index.php.backup*
```

### 3. 웹 접속
```
URL: http://59.19.231.47/hyundai-parts/admin/
ID: admin
PW: admin123
```

**확인 사항:**
- [x] "CSV 데이터 관리" 섹션이 보임
- [x] "CSV 다운로드" 버튼 작동
- [x] "CSV 업로드" 폼 작동
- [x] 기존 "데이터베이스 유틸리티" 섹션 사라짐

---

## 🔙 롤백 방법 (문제 발생 시)

```bash
# 백업 파일 목록 확인
ls -la /volume1/web/hyundai-parts/admin/index.php.backup*

# 최신 백업으로 복구
LATEST=$(ls -t /volume1/web/hyundai-parts/admin/index.php.backup* | head -1)
cp "$LATEST" /volume1/web/hyundai-parts/admin/index.php

# CSV 파일 삭제 (필요시)
rm /volume1/web/hyundai-parts/export_parts_csv.php
rm /volume1/web/hyundai-parts/import_parts_csv.php

# 웹 브라우저 새로고침
```

---

## 📊 CSV 파일 구조

| 컬럼명 | 필수 | 설명 | 예시 |
|--------|------|------|------|
| 차명 | ✅ | 차량 모델명 | 아반떼 |
| 세대 | ✅ | 세대 정보 | 7세대 (CN7) |
| 연료 | ✅ | 연료 타입 | 가솔린 |
| 엔진 | ✅ | 엔진 타입 | 1.6 터보 |
| 부품번호 | ✅ | 부품 번호 | 26300-2B000 |
| 부품명 | ✅ | 부품 이름 | 엔진오일 필터 |
| 주카테고리 | ✅ | 메인 카테고리 | 필터 |
| 부카테고리 | ❌ | 서브 카테고리 | 오일 필터 |
| 용량 | ❌ | 부품 용량 | 1L |
| 부품타입 | ❌ | 부품 유형 | engine_oil |
| 수량 | ❌ | 필요 수량 | 1 |
| 위치 | ❌ | 부품 위치 | 엔진룸 |
| 비고 | ❌ | 추가 정보 | 정기 교환 |
| 교체주기 | ❌ | 교환 주기 | 10000km |

---

## ⚠️ 주의사항

### CSV 편집 시
- ✅ 첫 번째 행(헤더)은 삭제하지 마세요
- ✅ 컬럼 순서를 변경하지 마세요
- ✅ 필수 컬럼은 반드시 입력하세요
- ✅ Excel 저장 시 "CSV UTF-8" 형식 사용

### 데이터 업로드 시
- 💾 업로드 전 데이터베이스 백업 권장
- 🔄 오류 발생 시 자동 롤백
- ➕ 없는 데이터는 자동 생성
- 🔄 중복 데이터는 자동 업데이트

---

## 📞 지원 및 문서

### GitHub
- **Repository**: https://github.com/kimmin0242/kimnote
- **Pull Request**: https://github.com/kimmin0242/kimnote/pull/1

### 문서
- **상세 가이드**: `csv_deployment/README.md` (패키지 내)
- **수동 배포**: `csv_deployment/MANUAL_DEPLOY.md` (패키지 내)
- **빠른 시작**: `WGET_DEPLOYMENT_GUIDE.md`

### 배포 패키지
- **다운로드 페이지**: `DOWNLOAD_CSV_DEPLOYMENT.html`
- **압축 파일**: `csv_deployment.tar.gz` (12 KB)

---

## 🎉 배포 완료 체크리스트

배포 후 다음 사항을 확인하세요:

- [ ] SSH 접속 가능
- [ ] wget으로 `csv_deployment.tar.gz` 다운로드 완료
- [ ] 압축 해제 완료
- [ ] `DEPLOY.sh` 실행 완료
- [ ] 백업 파일 생성 확인
- [ ] 새 파일 복사 확인
- [ ] 관리자 페이지 접속 테스트
- [ ] "CSV 데이터 관리" 섹션 확인
- [ ] CSV 다운로드 테스트
- [ ] CSV 업로드 테스트 (선택사항)

---

**배포 버전**: 1.0.0  
**배포 날짜**: 2025-11-22  
**패키지 크기**: 12 KB  
**배포 소요 시간**: ~1분  

---

## 🚀 지금 바로 배포하기!

```bash
ssh admin@59.19.231.47
cd /tmp && wget http://59.19.231.47/hyundai-parts/csv_deployment.tar.gz
tar -xzf csv_deployment.tar.gz && cd csv_deployment
chmod +x DEPLOY.sh && ./DEPLOY.sh
```

**완료!** 🎉
