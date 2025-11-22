# 🚀 wget 배포 가이드

## 빠른 배포 (3단계)

### 1️⃣ SSH 접속
```bash
ssh admin@59.19.231.47
```

### 2️⃣ 배포 패키지 다운로드 및 설치
```bash
# 임시 디렉토리로 이동
cd /tmp

# wget으로 다운로드 (현재 서버에서)
wget http://59.19.231.47/hyundai-parts/csv_deployment.tar.gz

# 압축 해제
tar -xzf csv_deployment.tar.gz

# 배포 디렉토리로 이동
cd csv_deployment

# 실행 권한 부여 및 배포 실행
chmod +x DEPLOY.sh && ./DEPLOY.sh
```

### 3️⃣ 확인
```
브라우저에서 접속: http://59.19.231.47/hyundai-parts/admin/
로그인: admin / admin123
"CSV 데이터 관리" 섹션 확인
```

---

## 📥 다운로드 페이지

웹 브라우저에서 접속:
```
http://59.19.231.47/hyundai-parts/DOWNLOAD_CSV_DEPLOYMENT.html
```

**또는 직접 다운로드:**
```
http://59.19.231.47/hyundai-parts/csv_deployment.tar.gz
```

---

## 📦 패키지에 포함된 파일

```
csv_deployment.tar.gz (12 KB)
└── csv_deployment/
    ├── DEPLOY.sh                # 자동 배포 스크립트
    ├── README.md                # 상세 가이드
    ├── MANUAL_DEPLOY.md         # 수동 배포 가이드
    ├── export_parts_csv.php     # CSV 내보내기
    ├── import_parts_csv.php     # CSV 가져오기
    └── admin_index.php          # 관리자 페이지 (업데이트됨)
```

---

## ✨ 자동 배포 스크립트 기능

`DEPLOY.sh`가 자동으로:

1. ✅ 기존 파일 백업 (`index.php.backup.20251122_HHMMSS`)
2. ✅ CSV 파일 복사 (`export_parts_csv.php`, `import_parts_csv.php`)
3. ✅ 관리자 페이지 업데이트
4. ✅ 파일 권한 설정 (644)
5. ✅ 배포 완료 확인

---

## 🔍 배포 후 확인사항

### 파일 존재 확인
```bash
ls -la /volume1/web/hyundai-parts/export_parts_csv.php
ls -la /volume1/web/hyundai-parts/import_parts_csv.php
ls -la /volume1/web/hyundai-parts/admin/index.php
```

### 백업 파일 확인
```bash
ls -la /volume1/web/hyundai-parts/admin/index.php.backup*
```

### 웹 접속 테스트
- URL: `http://59.19.231.47/hyundai-parts/admin/`
- "CSV 데이터 관리" 섹션이 보여야 함
- "CSV 다운로드" 버튼 클릭 테스트
- "CSV 업로드" 폼 확인

---

## 🔙 롤백 (문제 발생 시)

```bash
# 백업 파일 목록 확인
ls -la /volume1/web/hyundai-parts/admin/index.php.backup*

# 최신 백업으로 복구
LATEST=$(ls -t /volume1/web/hyundai-parts/admin/index.php.backup* | head -1)
cp "$LATEST" /volume1/web/hyundai-parts/admin/index.php

# CSV 파일 삭제 (필요시)
rm /volume1/web/hyundai-parts/export_parts_csv.php
rm /volume1/web/hyundai-parts/import_parts_csv.php
```

---

## 💡 사용 방법

배포 완료 후:

1. **CSV 다운로드**: 관리자 페이지에서 "CSV 다운로드" 클릭
2. **Excel 편집**: 다운받은 파일을 Excel에서 수정
3. **CSV 저장**: "CSV UTF-8 (쉼표로 분리)" 형식으로 저장
4. **업로드**: 관리자 페이지에서 수정한 파일 업로드
5. **결과 확인**: 처리 결과 및 오류 메시지 확인

---

## 📞 지원

- **상세 가이드**: `csv_deployment/README.md`
- **수동 배포**: `csv_deployment/MANUAL_DEPLOY.md`
- **GitHub**: https://github.com/kimmin0242/kimnote
- **Pull Request**: https://github.com/kimmin0242/kimnote/pull/1

---

**배포 시간**: ~1분  
**패키지 크기**: 12 KB  
**필요 권한**: SSH 접속 가능한 관리자 계정
