<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// 데이터베이스 연결
require_once '../config/db.php';

// 로그아웃 처리
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// 로그인 체크
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (isset($_POST['login'])) {
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $isLoggedIn = true;
    } else {
        $loginError = '아이디 또는 비밀번호가 틀렸습니다.';
    }
}

if (!$isLoggedIn) {
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <title>관리자 로그인</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4>🔐 관리자 로그인</h4>
                        </div>
                        <div class="card-body">
                            <?php if (isset($loginError)): ?>
                                <div class="alert alert-danger"><?php echo $loginError; ?></div>
                            <?php endif; ?>
                            <form method="post">
                                <div class="mb-3">
                                    <label>아이디</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>비밀번호</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary w-100">로그인</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 통계 데이터 가져오기
try {
    $stats = [
        'models' => $pdo->query("SELECT COUNT(DISTINCT id) as count FROM car_models")->fetch()['count'] ?? 0,
        'engines' => $pdo->query("SELECT COUNT(*) as count FROM car_engines")->fetch()['count'] ?? 0,
        'parts' => $pdo->query("SELECT COUNT(*) as count FROM genuine_parts")->fetch()['count'] ?? 0,
        'mappings' => 0
    ];
    
    // vehicle_parts_mapping 테이블 존재 여부 확인
    $tables = $pdo->query("SHOW TABLES LIKE 'vehicle_parts_mapping'")->fetchAll();
    if (count($tables) > 0) {
        $stats['mappings'] = $pdo->query("SELECT COUNT(*) as count FROM vehicle_parts_mapping")->fetch()['count'] ?? 0;
    }
    
} catch (PDOException $e) {
    $dbError = $e->getMessage();
    $stats = [
        'models' => 0,
        'engines' => 0,
        'parts' => 0,
        'mappings' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 대시보드 - 현대차 순정부품</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
        }
        .stat-card {
            border-left: 4px solid #0046a8;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .menu-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .menu-card:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="fas fa-cog"></i> 부품 관리 시스템 - 관리자
            </span>
            <div>
                <a href="../index.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-home"></i> 메인으로
                </a>
                <a href="?logout=1" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> 로그아웃
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                
                <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> 관리자 대시보드</h2>

                <?php if (isset($dbError)): ?>
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> 데이터베이스 연결 오류</h5>
                        <p><?php echo htmlspecialchars($dbError); ?></p>
                    </div>
                <?php endif; ?>

                <!-- 통계 카드 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted"><i class="fas fa-car"></i> 등록된 부품</h6>
                                <h2 class="text-primary"><?php echo $stats['models']; ?></h2>
                                <small class="text-muted">차종/세대</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted"><i class="fas fa-cogs"></i> 차량 모델</h6>
                                <h2 class="text-success"><?php echo $stats['engines']; ?></h2>
                                <small class="text-muted">엔진 타입</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted"><i class="fas fa-link"></i> 엔진 타입</h6>
                                <h2 class="text-warning"><?php echo $stats['mappings']; ?></h2>
                                <small class="text-muted">부품 매핑</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted"><i class="fas fa-tools"></i> 부품 매핑</h6>
                                <h2 class="text-info"><?php echo $stats['parts']; ?></h2>
                                <small class="text-muted">순정부품</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 메뉴 카드 -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <a href="../admin_vehicle_manager.php" class="text-decoration-none">
                            <div class="card menu-card">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-car fa-3x text-primary mb-3"></i>
                                    <h5>차량 정보 관리</h5>
                                    <p class="text-muted mb-0">차종, 세대, 엔진 정보 관리</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="../admin_vehicle_parts.php" class="text-decoration-none">
                            <div class="card menu-card">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-link fa-3x text-success mb-3"></i>
                                    <h5>부품 매핑 관리</h5>
                                    <p class="text-muted mb-0">차량별 부품 연결 관리</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="../admin_excel_converter.php" class="text-decoration-none">
                            <div class="card menu-card">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-file-excel fa-3x text-warning mb-3"></i>
                                    <h5>Excel 변환 도구</h5>
                                    <p class="text-muted mb-0">차종별 시트 분리 변환</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- CSV 데이터 관리 -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-file-csv"></i> CSV 데이터 관리</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- CSV 다운로드 섹션 -->
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-download text-success"></i> CSV 내려받기</h6>
                                        <p class="card-text text-muted">현재 등록된 모든 차량-부품 매핑 데이터를 CSV 파일로 다운로드합니다.</p>
                                        <a href="../export_parts_csv.php" class="btn btn-success btn-lg w-100">
                                            <i class="fas fa-file-download"></i> CSV 다운로드
                                        </a>
                                        <small class="text-muted d-block mt-2">
                                            <i class="fas fa-info-circle"></i> 엑셀에서 편집 가능한 형식으로 다운로드됩니다.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- CSV 업로드 섹션 -->
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-upload text-primary"></i> CSV 업로드</h6>
                                        <p class="card-text text-muted">수정한 CSV 파일을 업로드하여 데이터를 업데이트합니다.</p>
                                        <form id="csvUploadForm" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <input type="file" class="form-control" id="csvFile" name="csv_file" accept=".csv" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-lg w-100" id="uploadBtn">
                                                <i class="fas fa-upload"></i> CSV 업로드
                                            </button>
                                        </form>
                                        <small class="text-muted d-block mt-2">
                                            <i class="fas fa-exclamation-triangle"></i> 업로드 전 백업을 권장합니다.
                                        </small>
                                        <!-- 업로드 진행 상태 -->
                                        <div id="uploadProgress" class="mt-3" style="display:none;">
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                            </div>
                                            <small class="text-muted">처리 중...</small>
                                        </div>
                                        <!-- 결과 메시지 -->
                                        <div id="uploadResult" class="mt-3" style="display:none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CSV 업로드 사용 안내 -->
                        <div class="alert alert-warning mt-3">
                            <h6><i class="fas fa-info-circle"></i> CSV 업로드 사용 방법</h6>
                            <ol class="mb-0">
                                <li><strong>CSV 다운로드</strong>: 위의 'CSV 다운로드' 버튼을 클릭하여 현재 데이터를 받습니다.</li>
                                <li><strong>엑셀에서 편집</strong>: 다운로드한 파일을 엑셀이나 구글 스프레드시트에서 열어 수정합니다.</li>
                                <li><strong>CSV로 저장</strong>: 수정 완료 후 반드시 <strong>CSV UTF-8 (쉼표로 분리)</strong> 형식으로 저장합니다.</li>
                                <li><strong>업로드</strong>: 저장한 CSV 파일을 위의 업로드 폼에서 선택하여 업로드합니다.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- 시스템 정보 -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5><i class="fas fa-check-circle"></i> 시스템 정보</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>✅ 차량-부품 매핑 시스템</h6>
                                        <ul>
                                            <li><strong>차량 정보</strong>: 제조사, 브랜드, 카테고리, 모델명, 세대</li>
                                            <li><strong>엔진 정보</strong>: 연료 타입, 엔진 타입별 관리</li>
                                            <li><strong>부품 매핑</strong>: 차량별/엔진별 정확한 부품 연결</li>
                                            <li><strong>2단계 분류</strong>: 부품 카테고리 체계적 관리</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>🆕 최신 업데이트 (2025-11-21)</h6>
                                        <ul>
                                            <li>✅ 완전한 차량-부품 매핑 시스템 구현</li>
                                            <li>✅ 동적 컬럼 체크로 완벽한 하위 호환성</li>
                                            <li>✅ 2단계 드롭다운 부품 분류 시스템</li>
                                            <li>✅ 차량별 연료 타입 지원</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- CSV 업로드 처리 스크립트 -->
    <script>
    document.getElementById('csvUploadForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('csvFile');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        const uploadResult = document.getElementById('uploadResult');
        
        // 파일 선택 확인
        if (!fileInput.files || !fileInput.files[0]) {
            alert('CSV 파일을 선택해주세요.');
            return;
        }
        
        // 파일 확장자 확인
        const fileName = fileInput.files[0].name;
        if (!fileName.toLowerCase().endsWith('.csv')) {
            alert('CSV 파일만 업로드 가능합니다.');
            return;
        }
        
        // UI 상태 변경
        uploadBtn.disabled = true;
        uploadProgress.style.display = 'block';
        uploadResult.style.display = 'none';
        
        try {
            const formData = new FormData();
            formData.append('csv_file', fileInput.files[0]);
            
            const response = await fetch('../import_parts_csv.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            // 결과 표시
            uploadProgress.style.display = 'none';
            uploadResult.style.display = 'block';
            
            if (result.success) {
                uploadResult.className = 'alert alert-success mt-3';
                uploadResult.innerHTML = `
                    <h6><i class="fas fa-check-circle"></i> 업로드 성공!</h6>
                    <ul class="mb-0">
                        <li>처리된 행: ${result.processed_rows}개</li>
                        <li>성공: ${result.success_count}개</li>
                        ${result.created_parts > 0 ? `<li>신규 부품 생성: ${result.created_parts}개</li>` : ''}
                        ${result.updated_count > 0 ? `<li>업데이트: ${result.updated_count}개</li>` : ''}
                        ${result.inserted_count > 0 ? `<li>신규 매핑: ${result.inserted_count}개</li>` : ''}
                        ${result.errors && result.errors.length > 0 ? `<li class="text-danger">오류: ${result.errors.length}개</li>` : ''}
                    </ul>
                    ${result.errors && result.errors.length > 0 ? `
                        <hr>
                        <h6>오류 상세:</h6>
                        <ul class="small mb-0">
                            ${result.errors.slice(0, 10).map(err => `<li>${err}</li>`).join('')}
                            ${result.errors.length > 10 ? `<li><em>... 외 ${result.errors.length - 10}개 오류</em></li>` : ''}
                        </ul>
                    ` : ''}
                `;
                
                // 성공 시 폼 리셋
                fileInput.value = '';
                
                // 5초 후 페이지 새로고침 (통계 업데이트)
                setTimeout(() => {
                    location.reload();
                }, 5000);
            } else {
                uploadResult.className = 'alert alert-danger mt-3';
                uploadResult.innerHTML = `
                    <h6><i class="fas fa-exclamation-circle"></i> 업로드 실패</h6>
                    <p class="mb-0">${result.message || '알 수 없는 오류가 발생했습니다.'}</p>
                    ${result.errors && result.errors.length > 0 ? `
                        <hr>
                        <ul class="small mb-0">
                            ${result.errors.slice(0, 10).map(err => `<li>${err}</li>`).join('')}
                            ${result.errors.length > 10 ? `<li><em>... 외 ${result.errors.length - 10}개 오류</em></li>` : ''}
                        </ul>
                    ` : ''}
                `;
            }
            
        } catch (error) {
            uploadProgress.style.display = 'none';
            uploadResult.style.display = 'block';
            uploadResult.className = 'alert alert-danger mt-3';
            uploadResult.innerHTML = `
                <h6><i class="fas fa-exclamation-circle"></i> 업로드 오류</h6>
                <p class="mb-0">서버 통신 중 오류가 발생했습니다: ${error.message}</p>
            `;
        } finally {
            uploadBtn.disabled = false;
        }
    });
    </script>
</body>
</html>
