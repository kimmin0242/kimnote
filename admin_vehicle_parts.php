<?php
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
$password = 'Hyundai@2025';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>차량별 부품 매핑 관리</title>
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
            <span class="navbar-brand">⚙️ 차량별 부품 매핑 관리</span>
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
                        <h5>📦 부품 매핑</h5>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        // 부품 렌더링
        function renderParts() {
            const container = document.getElementById('partsContainer');
            
            if (partsList.length === 0) {
                container.innerHTML = '<p class="text-muted">매핑된 부품이 없습니다. "부품 추가" 버튼을 눌러 추가하세요.</p>';
                return;
            }
            
            container.innerHTML = '';
            
            partsList.forEach((part, index) => {
                const row = document.createElement('div');
                row.className = 'part-row row g-2';
                row.innerHTML = `
                    <div class="col-md-3">
                        <select class="form-select part-type" data-index="${index}">
                            <option value="엔진오일(대)" ${part.part_type === '엔진오일(대)' ? 'selected' : ''}>엔진오일(대)</option>
                            <option value="엔진오일(소)" ${part.part_type === '엔진오일(소)' ? 'selected' : ''}>엔진오일(소)</option>
                            <option value="오일필터" ${part.part_type === '오일필터' ? 'selected' : ''}>오일필터</option>
                            <option value="에어필터" ${part.part_type === '에어필터' ? 'selected' : ''}>에어필터</option>
                            <option value="에어컨필터(실내)" ${part.part_type === '에어컨필터(실내)' ? 'selected' : ''}>에어컨필터(실내)</option>
                            <option value="에어컨필터(외기)" ${part.part_type === '에어컨필터(외기)' ? 'selected' : ''}>에어컨필터(외기)</option>
                            <option value="와이퍼(좌)" ${part.part_type === '와이퍼(좌)' ? 'selected' : ''}>와이퍼(좌)</option>
                            <option value="와이퍼(우)" ${part.part_type === '와이퍼(우)' ? 'selected' : ''}>와이퍼(우)</option>
                            <option value="브레이크 패드(앞축)" ${part.part_type === '브레이크 패드(앞축)' ? 'selected' : ''}>브레이크 패드(앞축)</option>
                            <option value="브레이크 패드(뒤축)" ${part.part_type === '브레이크 패드(뒤축)' ? 'selected' : ''}>브레이크 패드(뒤축)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control part-number" data-index="${index}" 
                               placeholder="부품번호" value="${part.part_number || ''}">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control capacity" data-index="${index}" 
                               placeholder="용량" value="${part.capacity || ''}">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control quantity" data-index="${index}" 
                               placeholder="수량" value="${part.quantity || ''}">
                    </div>
                    <div class="col-md-1 text-center">
                        <i class="fas fa-trash delete-btn" data-index="${index}"></i>
                    </div>
                `;
                container.appendChild(row);
            });
            
            // 이벤트 리스너
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
                quantity: '1개'
            });
            renderParts();
        });

        // 저장
        document.getElementById('saveBtn').addEventListener('click', async function() {
            // 입력값 수집
            const updatedParts = [];
            document.querySelectorAll('.part-row').forEach(row => {
                const index = row.querySelector('.part-type').dataset.index;
                updatedParts.push({
                    part_type: row.querySelector('.part-type').value,
                    part_number: row.querySelector('.part-number').value,
                    capacity: row.querySelector('.capacity').value,
                    quantity: row.querySelector('.quantity').value
                });
            });
            
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
            } else {
                alert('❌ 에러: ' + result.message);
            }
        });
    </script>
</body>
</html>
