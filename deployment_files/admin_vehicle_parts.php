<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// 로그인 체크 (간단 버전)
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// 로그인 처리
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
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

// 데이터베이스 연결
$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Kdmdtt1225**';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("DB 연결 실패: " . $e->getMessage());
}

// 차량 목록 가져오기
$models = $pdo->query("SELECT DISTINCT model_name FROM car_models ORDER BY model_name")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>차량별 부품 매핑 관리 v2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .part-row { margin-bottom: 10px; }
        .delete-btn { cursor: pointer; color: #dc3545; }
        .add-part-btn { margin-top: 10px; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">⚙️ 차량별 부품 매핑 관리 (2단계 드롭다운)</span>
            <a href="/hyundai-parts/" class="btn btn-outline-light btn-sm">🏠 메인</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- 왼쪽: 차량 선택 -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>🚗 차량 선택</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label>차종</label>
                            <select class="form-select" id="modelSelect">
                                <option value="">선택...</option>
                                <?php foreach ($models as $model): ?>
                                    <option value="<?php echo htmlspecialchars($model); ?>">
                                        <?php echo htmlspecialchars($model); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>세대</label>
                            <select class="form-select" id="generationSelect" disabled>
                                <option value="">먼저 차종 선택...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>엔진</label>
                            <select class="form-select" id="engineSelect" disabled>
                                <option value="">먼저 세대 선택...</option>
                            </select>
                        </div>
                        <button class="btn btn-primary w-100" id="loadPartsBtn" disabled>
                            📦 부품 불러오기
                        </button>
                    </div>
                </div>
            </div>

            <!-- 오른쪽: 부품 매핑 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>📦 부품 매핑 (2단계 선택)</h5>
                    </div>
                    <div class="card-body">
                        <div id="selectedVehicle" class="alert alert-info" style="display:none;">
                            선택된 차량: <strong id="vehicleInfo"></strong>
                        </div>
                        
                        <div id="partsContainer">
                            <p class="text-muted">왼쪽에서 차량을 선택하세요.</p>
                        </div>
                        
                        <button class="btn btn-success add-part-btn" id="addPartBtn" style="display:none;">
                            <i class="fas fa-plus"></i> 부품 추가
                        </button>
                        
                        <button class="btn btn-primary mt-3" id="saveBtn" style="display:none;">
                            💾 저장하기
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CSV 데이터 관리 섹션 -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5>📊 CSV 데이터 관리</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- CSV 다운로드 -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6><i class="fas fa-download"></i> CSV 내보내기</h6>
                                    <p class="text-muted small">모든 차량-부품 매핑 데이터를 CSV 파일로 다운로드합니다.</p>
                                    <a href="export_parts_csv.php" class="btn btn-success" download>
                                        <i class="fas fa-file-csv"></i> CSV 다운로드
                                    </a>
                                </div>
                            </div>

                            <!-- CSV 업로드 -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6><i class="fas fa-upload"></i> CSV 가져오기</h6>
                                    <p class="text-muted small">CSV 파일을 수정하여 일괄 업로드할 수 있습니다.</p>
                                    <form id="csvUploadForm" enctype="multipart/form-data">
                                        <div class="input-group mb-2">
                                            <input type="file" class="form-control" id="csvFile" name="csv_file" accept=".csv" required>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload"></i> 업로드
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            ⚠️ 기존 데이터와 중복되면 업데이트됩니다.
                                        </div>
                                    </form>
                                    <div id="uploadResult" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ========================================
        // 2단계 드롭다운: 부품 카테고리 및 하위 항목 정의
        // ========================================
        const partCategories = {
            '오일 및 액체류': [
                '엔진오일(대)',
                '엔진오일(소)',
                '미션오일',
                '브레이크오일',
                '냉각수/부동액',
                '파워스티어링오일',
                '워셔액',
                '디퍼런셜오일'
            ],
            '필터류': [
                '에어필터',
                '오일필터',
                '에어컨필터(실내)',
                '에어컨필터(외기)',
                '연료필터'
            ],
            '제동류': [
                '브레이크 패드(앞축)',
                '브레이크 패드(뒤축)',
                '브레이크 디스크(앞)',
                '브레이크 디스크(뒤)',
                '타이어(앞)',
                '타이어(뒤)'
            ],
            '전장 및 기타 부품류': [
                '배터리',
                '점화플러그',
                '점화코일',
                '구동벨트 (V벨트)',
                '타이밍벨트',
                '와이퍼 블레이드(좌)',
                '와이퍼 블레이드(우)',
                '와이퍼 블레이드(뒤)'
            ],
            '기타': []  // 기타는 커스텀 입력
        };

        // 카테고리에서 부품 타입 찾기
        function findCategoryForPartType(partType) {
            for (const [category, types] of Object.entries(partCategories)) {
                if (types.includes(partType)) {
                    return category;
                }
            }
            return '기타';
        }

        let currentEngineId = null;
        let partsList = [];

        // 차종 선택 시
        document.getElementById('modelSelect').addEventListener('change', async function() {
            const modelName = this.value;
            const generationSelect = document.getElementById('generationSelect');
            
            generationSelect.innerHTML = '<option value="">로딩 중...</option>';
            generationSelect.disabled = true;
            document.getElementById('engineSelect').disabled = true;
            
            if (!modelName) return;
            
            const response = await fetch(`/hyundai-parts/api/get_trims.php?model_name=${encodeURIComponent(modelName)}`);
            const data = await response.json();
            
            generationSelect.innerHTML = '<option value="">세대 선택...</option>';
            if (data.success && data.trims) {
                data.trims.forEach(trim => {
                    const option = document.createElement('option');
                    option.value = trim.generation;
                    option.textContent = trim.generation;
                    generationSelect.appendChild(option);
                });
                generationSelect.disabled = false;
            }
        });

        // 세대 선택 시
        document.getElementById('generationSelect').addEventListener('change', async function() {
            const modelName = document.getElementById('modelSelect').value;
            const generation = this.value;
            const engineSelect = document.getElementById('engineSelect');
            
            engineSelect.innerHTML = '<option value="">로딩 중...</option>';
            engineSelect.disabled = true;
            
            if (!generation) return;
            
            const response = await fetch(`/hyundai-parts/api/get_engines.php?model_name=${encodeURIComponent(modelName)}&generation=${encodeURIComponent(generation)}`);
            const engines = await response.json();
            
            engineSelect.innerHTML = '<option value="">엔진 선택...</option>';
            if (engines && engines.length > 0) {
                engines.forEach(engine => {
                    const option = document.createElement('option');
                    option.value = engine.id;
                    option.textContent = engine.engine_type;
                    engineSelect.appendChild(option);
                });
                engineSelect.disabled = false;
            }
        });

        // 엔진 선택 시
        document.getElementById('engineSelect').addEventListener('change', function() {
            document.getElementById('loadPartsBtn').disabled = !this.value;
        });

        // 부품 불러오기
        document.getElementById('loadPartsBtn').addEventListener('click', async function() {
            const engineId = document.getElementById('engineSelect').value;
            const engineText = document.getElementById('engineSelect').selectedOptions[0].text;
            const modelName = document.getElementById('modelSelect').value;
            const generation = document.getElementById('generationSelect').value;
            
            currentEngineId = engineId;
            
            document.getElementById('vehicleInfo').textContent = `${modelName} ${generation} - ${engineText}`;
            document.getElementById('selectedVehicle').style.display = 'block';
            
            // 기존 매핑된 부품 불러오기
            const response = await fetch(`get_vehicle_parts_api.php?engine_id=${engineId}`);
            const data = await response.json();
            
            partsList = data.parts || [];
            renderParts();
            
            document.getElementById('addPartBtn').style.display = 'block';
            document.getElementById('saveBtn').style.display = 'block';
        });

        // ========================================
        // 2단계 드롭다운: 부품 렌더링
        // ========================================
        function renderParts() {
            const container = document.getElementById('partsContainer');
            
            if (partsList.length === 0) {
                container.innerHTML = '<p class="text-muted">매핑된 부품이 없습니다. "부품 추가" 버튼을 눌러 추가하세요.</p>';
                return;
            }
            
            container.innerHTML = '';
            
            partsList.forEach((part, index) => {
                const category = findCategoryForPartType(part.part_type);
                const isCustom = category === '기타';
                
                const row = document.createElement('div');
                row.className = 'part-row row g-2 mb-3';
                
                // 1단계: 카테고리 선택 HTML 생성
                let categoryOptions = '';
                for (const cat of Object.keys(partCategories)) {
                    const selected = cat === category ? 'selected' : '';
                    categoryOptions += `<option value="${cat}" ${selected}>${cat}</option>`;
                }
                
                // 2단계: 하위 항목 선택 HTML 생성
                let subTypeOptions = '';
                if (!isCustom && partCategories[category]) {
                    subTypeOptions = '<option value="">선택...</option>';
                    for (const subType of partCategories[category]) {
                        const selected = subType === part.part_type ? 'selected' : '';
                        subTypeOptions += `<option value="${subType}" ${selected}>${subType}</option>`;
                    }
                }
                
                row.innerHTML = `
                    <div class="col-md-2">
                        <label class="form-label small">카테고리</label>
                        <select class="form-select part-category" data-index="${index}">
                            ${categoryOptions}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">하위항목</label>
                        <select class="form-select part-subtype" data-index="${index}" ${isCustom ? 'style="display:none;"' : ''}>
                            ${subTypeOptions}
                        </select>
                        <input type="text" class="form-control part-custom-subtype" data-index="${index}" 
                               placeholder="기타 하위분류" value="${isCustom ? part.part_type : ''}"
                               ${!isCustom ? 'style="display:none;"' : ''}>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">부품번호</label>
                        <input type="text" class="form-control part-number" data-index="${index}" 
                               placeholder="부품번호" value="${part.part_number || ''}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">용량</label>
                        <input type="text" class="form-control capacity" data-index="${index}" 
                               placeholder="용량" value="${part.capacity || ''}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">수량</label>
                        <input type="text" class="form-control quantity" data-index="${index}" 
                               placeholder="수량" value="${part.quantity || ''}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">비고 (연식, 특이사항 등)</label>
                        <input type="text" class="form-control notes" data-index="${index}" 
                               placeholder="예: 2021~2023년식, 고급형 전용 등" value="${part.notes || ''}">
                    </div>
                    <div class="col-md-1 text-center">
                        <label class="form-label small">&nbsp;</label>
                        <div><i class="fas fa-trash delete-btn" data-index="${index}"></i></div>
                    </div>
                `;
                container.appendChild(row);
            });
            
            // 카테고리 변경 이벤트 리스너
            document.querySelectorAll('.part-category').forEach(select => {
                select.addEventListener('change', function() {
                    const index = this.dataset.index;
                    const category = this.value;
                    const row = this.closest('.part-row');
                    const subTypeSelect = row.querySelector('.part-subtype');
                    const customInput = row.querySelector('.part-custom-subtype');
                    
                    if (category === '기타') {
                        // 기타 선택시 커스텀 입력 표시
                        subTypeSelect.style.display = 'none';
                        customInput.style.display = 'block';
                        customInput.value = '';
                    } else {
                        // 일반 카테고리 선택시 하위 항목 표시
                        subTypeSelect.style.display = 'block';
                        customInput.style.display = 'none';
                        
                        // 하위 항목 옵션 재생성
                        let options = '<option value="">선택...</option>';
                        for (const subType of partCategories[category]) {
                            options += `<option value="${subType}">${subType}</option>`;
                        }
                        subTypeSelect.innerHTML = options;
                    }
                });
            });
            
            // 삭제 버튼 이벤트 리스너
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index);
                    partsList.splice(index, 1);
                    renderParts();
                });
            });
        }

        // 부품 추가
        document.getElementById('addPartBtn').addEventListener('click', function() {
            partsList.push({
                part_type: '엔진오일(대)',
                part_number: '',
                capacity: '',
                quantity: '1개',
                notes: ''
            });
            renderParts();
        });

        // ========================================
        // 2단계 드롭다운: 저장 (카테고리 + 하위항목)
        // ========================================
        document.getElementById('saveBtn').addEventListener('click', async function() {
            // 입력값 수집
            const updatedParts = [];
            document.querySelectorAll('.part-row').forEach(row => {
                const category = row.querySelector('.part-category').value;
                let partType;
                
                if (category === '기타') {
                    // 기타 카테고리는 커스텀 입력값 사용
                    partType = row.querySelector('.part-custom-subtype').value.trim();
                } else {
                    // 일반 카테고리는 하위 항목 선택값 사용
                    partType = row.querySelector('.part-subtype').value;
                }
                
                // 부품 타입이 비어있으면 경고
                if (!partType) {
                    alert('⚠️ 모든 부품의 타입을 선택해주세요.');
                    return;
                }
                
                updatedParts.push({
                    part_type: partType,
                    part_number: row.querySelector('.part-number').value,
                    capacity: row.querySelector('.capacity').value,
                    quantity: row.querySelector('.quantity').value,
                    notes: row.querySelector('.notes').value
                });
            });
            
            // 검증 실패시 중단
            if (updatedParts.length === 0 && document.querySelectorAll('.part-row').length > 0) {
                return;
            }
            
            // 서버로 전송
            const response = await fetch('save_vehicle_parts_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    engine_id: currentEngineId,
                    parts: updatedParts
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('✅ 저장되었습니다!');
                // 저장 후 다시 불러오기
                document.getElementById('loadPartsBtn').click();
            } else {
                alert('❌ 에러: ' + result.message);
            }
        });

        // CSV 업로드 처리
        document.getElementById('csvUploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('csvFile');
            const resultDiv = document.getElementById('uploadResult');
            
            if (!fileInput.files.length) {
                resultDiv.innerHTML = '<div class="alert alert-danger">파일을 선택하세요.</div>';
                return;
            }
            
            const formData = new FormData();
            formData.append('csv_file', fileInput.files[0]);
            
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> 업로드 중...</div>';
            
            try {
                const response = await fetch('import_parts_csv.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    let html = `<div class="alert alert-success">
                        <strong>✅ ${result.message}</strong><br>
                        성공: ${result.success_count}건, 실패: ${result.error_count}건
                    </div>`;
                    
                    if (result.errors && result.errors.length > 0) {
                        html += '<div class="alert alert-warning"><strong>에러 목록:</strong><ul class="mb-0">';
                        result.errors.forEach(err => {
                            html += `<li>${err}</li>`;
                        });
                        html += '</ul></div>';
                    }
                    
                    resultDiv.innerHTML = html;
                    fileInput.value = ''; // 파일 입력 초기화
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">❌ ${result.message}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">❌ 업로드 실패: ${error.message}</div>`;
            }
        });
    </script>
</body>
</html>
