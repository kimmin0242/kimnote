<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// Simple authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel 데이터 변환 도구 - 차종별 시트 분리</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .upload-area {
            border: 3px dashed #dee2e6;
            border-radius: 10px;
            padding: 50px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .upload-area.dragover {
            border-color: #0d6efd;
            background-color: #cfe2ff;
        }
        .file-info {
            background-color: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        .sheet-info {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .sheet-info h6 {
            color: #0d6efd;
            margin-bottom: 10px;
        }
        .vehicle-list {
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin/">
                <i class="bi bi-file-earmark-excel"></i> Excel 변환 도구
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin/">
                    <i class="bi bi-house-door"></i> 관리자 홈
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-arrow-left-right"></i> 방법 2: 차종별 시트 분리 변환
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="bi bi-info-circle"></i> 변환 방식 안내
                            </h6>
                            <p class="mb-2">가로 형식의 부품 데이터를 차종별 시트로 분리하여 세로 형식으로 변환합니다.</p>
                            <hr>
                            <p class="mb-1"><strong>생성되는 시트:</strong></p>
                            <ul class="mb-0">
                                <li><strong>제너시스_세단</strong>: G90, G80, G70</li>
                                <li><strong>제너시스_SUV</strong>: GV60, GV70, GV80, GV90</li>
                                <li><strong>현대_승용차</strong>: 아반떼, 쏘나타, 그랜저 등</li>
                                <li><strong>현대_SUV</strong>: 코나, 투싼, 싼타페, 팰리세이드 등</li>
                                <li><strong>현대_친환경차</strong>: 아이오닉5, 아이오닉6, 넥쏘 등</li>
                            </ul>
                        </div>

                        <!-- Upload Area -->
                        <div class="upload-area" id="uploadArea">
                            <i class="bi bi-cloud-upload" style="font-size: 3rem; color: #6c757d;"></i>
                            <h5 class="mt-3">Excel 파일을 드래그하거나 클릭하여 선택하세요</h5>
                            <p class="text-muted">지원 형식: .xlsx</p>
                            <input type="file" id="fileInput" accept=".xlsx" style="display: none;">
                        </div>

                        <!-- File Info -->
                        <div id="fileInfo" style="display: none;">
                            <div class="file-info">
                                <h6><i class="bi bi-file-earmark-check"></i> 선택된 파일</h6>
                                <p class="mb-1"><strong id="fileName"></strong></p>
                                <p class="mb-0 text-muted">크기: <span id="fileSize"></span></p>
                            </div>
                        </div>

                        <!-- Progress -->
                        <div class="progress-container" id="progressContainer">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     id="progressBar" 
                                     style="width: 0%">0%</div>
                            </div>
                            <p class="text-center mt-2" id="progressText">업로드 중...</p>
                        </div>

                        <!-- Convert Button -->
                        <div class="text-center mt-3">
                            <button class="btn btn-primary btn-lg" id="convertBtn" style="display: none;">
                                <i class="bi bi-arrow-repeat"></i> 변환 시작
                            </button>
                        </div>

                        <!-- Result -->
                        <div id="resultContainer" style="display: none; margin-top: 30px;">
                            <div class="alert alert-success">
                                <h5 class="alert-heading">
                                    <i class="bi bi-check-circle"></i> 변환 완료!
                                </h5>
                                <hr>
                                <div id="resultStats"></div>
                                <div class="mt-3">
                                    <a href="#" id="downloadLink" class="btn btn-success">
                                        <i class="bi bi-download"></i> 변환된 파일 다운로드
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Error -->
                        <div id="errorContainer" style="display: none; margin-top: 20px;">
                            <div class="alert alert-danger">
                                <h5 class="alert-heading">
                                    <i class="bi bi-exclamation-triangle"></i> 오류 발생
                                </h5>
                                <p id="errorMessage"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sheet Information -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle"></i> 차종별 시트 구성 상세
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="sheet-info">
                            <h6><i class="bi bi-file-earmark-text"></i> 제너시스_세단</h6>
                            <p class="vehicle-list mb-0">G90, G80, G70 등 제네시스 브랜드 세단 모델</p>
                        </div>
                        <div class="sheet-info">
                            <h6><i class="bi bi-file-earmark-text"></i> 제너시스_SUV</h6>
                            <p class="vehicle-list mb-0">GV60, GV70, GV80, GV90 등 제네시스 브랜드 SUV 모델</p>
                        </div>
                        <div class="sheet-info">
                            <h6><i class="bi bi-file-earmark-text"></i> 현대_승용차</h6>
                            <p class="vehicle-list mb-0">아반떼, 벨로스터, 쏘나타, 그랜저, 에쿠스, i30, i40 등</p>
                        </div>
                        <div class="sheet-info">
                            <h6><i class="bi bi-file-earmark-text"></i> 현대_SUV</h6>
                            <p class="vehicle-list mb-0">코나, 투싼, 싼타페, 팰리세이드, 베뉴, 맥스크루즈 등</p>
                        </div>
                        <div class="sheet-info">
                            <h6><i class="bi bi-file-earmark-text"></i> 현대_친환경차</h6>
                            <p class="vehicle-list mb-0">아이오닉5, 아이오닉6, 넥쏘, GV60 등 전기차/수소차</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const convertBtn = document.getElementById('convertBtn');
        const progressContainer = document.getElementById('progressContainer');
        const resultContainer = document.getElementById('resultContainer');
        const errorContainer = document.getElementById('errorContainer');

        let selectedFile = null;

        // Click to select file
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            handleFileSelect(e.target.files[0]);
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const file = e.dataTransfer.files[0];
            handleFileSelect(file);
        });

        function handleFileSelect(file) {
            if (!file) return;

            // Check file type
            if (!file.name.endsWith('.xlsx')) {
                showError('Excel 파일(.xlsx)만 업로드할 수 있습니다.');
                return;
            }

            selectedFile = file;
            
            // Display file info
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
            fileInfo.style.display = 'block';
            convertBtn.style.display = 'inline-block';
            
            // Hide previous results/errors
            resultContainer.style.display = 'none';
            errorContainer.style.display = 'none';
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }

        // Convert button
        convertBtn.addEventListener('click', async () => {
            if (!selectedFile) return;

            const formData = new FormData();
            formData.append('file', selectedFile);

            // Show progress
            progressContainer.style.display = 'block';
            convertBtn.disabled = true;
            resultContainer.style.display = 'none';
            errorContainer.style.display = 'none';

            try {
                const response = await fetch('admin_excel_converter_api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess(result);
                } else {
                    showError(result.error || '변환 중 오류가 발생했습니다.');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('서버 통신 중 오류가 발생했습니다: ' + error.message);
            } finally {
                progressContainer.style.display = 'none';
                convertBtn.disabled = false;
            }
        });

        function showSuccess(result) {
            const statsHtml = `
                <p><strong>변환 통계:</strong></p>
                <ul>
                    <li>총 처리 차량: ${result.stats.total_vehicles || 0}개</li>
                    <li>총 부품 레코드: ${result.stats.total_parts || 0}개</li>
                </ul>
                <p><strong>시트별 부품 수:</strong></p>
                <ul>
                    ${Object.entries(result.stats.sheets || {}).map(([name, count]) => 
                        `<li>${name}: ${count}개</li>`
                    ).join('')}
                </ul>
            `;
            
            document.getElementById('resultStats').innerHTML = statsHtml;
            document.getElementById('downloadLink').href = result.download_url;
            resultContainer.style.display = 'block';
        }

        function showError(message) {
            document.getElementById('errorMessage').textContent = message;
            errorContainer.style.display = 'block';
        }
    </script>
</body>
</html>
