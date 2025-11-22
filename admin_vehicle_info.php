<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

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

// DB 연결
$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Hyundai@2025';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("DB 연결 실패: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>차량 정보 관리</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link { cursor: pointer; }
        .table-hover tbody tr:hover { background-color: #f8f9fa; cursor: pointer; }
        .badge { font-size: 0.9em; }
    </style>
</head>
<body class="bg-light">
    <!-- 상단 네비게이션 -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">🚗 차량 정보 관리</span>
            <div>
                <a href="admin_vehicle_parts.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-cog"></i> 부품 매핑 관리
                </a>
                <a href="/hyundai-parts/" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-home"></i> 메인
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- 탭 메뉴 -->
        <ul class="nav nav-tabs mb-4" id="managementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="models-tab" data-bs-toggle="tab" data-bs-target="#models" type="button">
                    <i class="fas fa-car"></i> 차종 관리
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="engines-tab" data-bs-toggle="tab" data-bs-target="#engines" type="button">
                    <i class="fas fa-cogs"></i> 엔진/연료 관리
                </button>
            </li>
        </ul>

        <!-- 탭 컨텐츠 -->
        <div class="tab-content" id="managementTabsContent">
            <!-- 차종 관리 탭 -->
            <div class="tab-pane fade show active" id="models" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">차종 및 세대 목록</h5>
                        <button class="btn btn-light btn-sm" onclick="showModelForm()">
                            <i class="fas fa-plus"></i> 새 차종 추가
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>브랜드</th>
                                        <th>카테고리</th>
                                        <th>차종</th>
                                        <th>세대</th>
                                        <th>설명</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody id="modelsTableBody">
                                    <tr><td colspan="7" class="text-center">로딩 중...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 엔진/연료 관리 탭 -->
            <div class="tab-pane fade" id="engines" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">엔진 및 연료 타입 목록</h5>
                        <button class="btn btn-light btn-sm" onclick="showEngineForm()">
                            <i class="fas fa-plus"></i> 새 엔진 추가
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>차종</th>
                                        <th>세대</th>
                                        <th>연료 타입 ⭐</th>
                                        <th>엔진</th>
                                        <th>배기량</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody id="enginesTableBody">
                                    <tr><td colspan="7" class="text-center">로딩 중...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 차종 추가/수정 모달 -->
    <div class="modal fade" id="modelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelModalTitle">차종 추가</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="modelForm">
                        <input type="hidden" id="model_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">제조사</label>
                            <input type="text" class="form-control" id="manufacturer" name="manufacturer" value="현대" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">브랜드</label>
                            <select class="form-select" id="brand" name="brand" required>
                                <option value="">선택...</option>
                                <option value="현대">현대</option>
                                <option value="제너시스">제너시스</option>
                                <option value="N">N</option>
                                <option value="아이오닉">아이오닉</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">카테고리</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">선택...</option>
                                <option value="세단">세단</option>
                                <option value="SUV">SUV</option>
                                <option value="해치백">해치백</option>
                                <option value="쿠페">쿠페</option>
                                <option value="MPV">MPV</option>
                                <option value="전기차">전기차</option>
                                <option value="상용차">상용차</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">차종명 *</label>
                            <input type="text" class="form-control" id="model_name" name="model_name" placeholder="예: G80, 소나타, 아반떼" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">세대 *</label>
                            <input type="text" class="form-control" id="generation" name="generation" placeholder="예: RG3 (3세대), DN8 (8세대)" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">설명</label>
                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="예: G80 3세대 모델"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="saveModel()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 엔진 추가/수정 모달 -->
    <div class="modal fade" id="engineModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="engineModalTitle">엔진 추가</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="engineForm">
                        <input type="hidden" id="engine_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">차종 선택 *</label>
                            <select class="form-select" id="car_model_id" name="car_model_id" required>
                                <option value="">먼저 차종을 로드하세요...</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">연료 타입 ⭐ *</label>
                                <select class="form-select" id="fuel_type" name="fuel_type" required>
                                    <option value="">선택...</option>
                                    <option value="가솔린">가솔린</option>
                                    <option value="디젤">디젤</option>
                                    <option value="LPG">LPG</option>
                                    <option value="하이브리드">하이브리드 (HEV)</option>
                                    <option value="플러그인하이브리드">플러그인 하이브리드 (PHEV)</option>
                                    <option value="전기">전기 (EV)</option>
                                    <option value="수소">수소 (FCEV)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">배기량</label>
                                <input type="text" class="form-control" id="displacement" name="displacement" placeholder="예: 2.5L, 3.5L">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">엔진 타입 *</label>
                            <input type="text" class="form-control" id="engine_type" name="engine_type" placeholder="예: 직렬 4기통 2.5 가솔린 터보 (I4 2.5 T-GDi)" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">출력</label>
                            <input type="text" class="form-control" id="power_output" name="power_output" placeholder="예: 304마력, 225kW">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">설명</label>
                            <textarea class="form-control" id="engine_description" name="description" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="saveEngine()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let modelModal, engineModal;
        let models = [];
        let engines = [];

        document.addEventListener('DOMContentLoaded', function() {
            modelModal = new bootstrap.Modal(document.getElementById('modelModal'));
            engineModal = new bootstrap.Modal(document.getElementById('engineModal'));
            
            loadModels();
            loadEngines();
        });

        // ========== 차종 관리 ==========
        async function loadModels() {
            try {
                const response = await fetch('api/vehicle_models_api.php?action=list');
                const data = await response.json();
                
                if (data.success) {
                    models = data.models;
                    renderModelsTable();
                    updateModelSelect();
                }
            } catch (error) {
                console.error('Error loading models:', error);
            }
        }

        function renderModelsTable() {
            const tbody = document.getElementById('modelsTableBody');
            if (models.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">등록된 차종이 없습니다.</td></tr>';
                return;
            }

            tbody.innerHTML = models.map(m => `
                <tr>
                    <td>${m.id}</td>
                    <td><span class="badge bg-primary">${m.brand || '-'}</span></td>
                    <td><span class="badge bg-info">${m.category || '-'}</span></td>
                    <td><strong>${m.model_name}</strong></td>
                    <td>${m.generation}</td>
                    <td>${m.description || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editModel(${m.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteModel(${m.id}, '${m.model_name} ${m.generation}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function showModelForm(modelId = null) {
            document.getElementById('modelForm').reset();
            document.getElementById('model_id').value = '';
            document.getElementById('modelModalTitle').textContent = '차종 추가';
            
            if (modelId) {
                const model = models.find(m => m.id == modelId);
                if (model) {
                    document.getElementById('modelModalTitle').textContent = '차종 수정';
                    document.getElementById('model_id').value = model.id;
                    document.getElementById('manufacturer').value = model.manufacturer;
                    document.getElementById('brand').value = model.brand;
                    document.getElementById('category').value = model.category;
                    document.getElementById('model_name').value = model.model_name;
                    document.getElementById('generation').value = model.generation;
                    document.getElementById('description').value = model.description || '';
                }
            }
            
            modelModal.show();
        }

        function editModel(id) {
            showModelForm(id);
        }

        async function saveModel() {
            const formData = new FormData(document.getElementById('modelForm'));
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('api/vehicle_models_api.php?action=save', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ 저장되었습니다!');
                    modelModal.hide();
                    loadModels();
                } else {
                    alert('❌ 오류: ' + result.message);
                }
            } catch (error) {
                alert('❌ 저장 실패: ' + error.message);
            }
        }

        async function deleteModel(id, name) {
            if (!confirm(`"${name}"을(를) 삭제하시겠습니까?\n\n⚠️ 주의: 연결된 엔진 정보와 부품 매핑도 모두 삭제됩니다!`)) {
                return;
            }
            
            try {
                const response = await fetch(`api/vehicle_models_api.php?action=delete&id=${id}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ 삭제되었습니다!');
                    loadModels();
                    loadEngines();
                } else {
                    alert('❌ 오류: ' + result.message);
                }
            } catch (error) {
                alert('❌ 삭제 실패: ' + error.message);
            }
        }

        // ========== 엔진 관리 ==========
        async function loadEngines() {
            try {
                const response = await fetch('api/vehicle_engines_api.php?action=list');
                const data = await response.json();
                
                if (data.success) {
                    engines = data.engines;
                    renderEnginesTable();
                }
            } catch (error) {
                console.error('Error loading engines:', error);
            }
        }

        function renderEnginesTable() {
            const tbody = document.getElementById('enginesTableBody');
            if (engines.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">등록된 엔진이 없습니다.</td></tr>';
                return;
            }

            tbody.innerHTML = engines.map(e => `
                <tr>
                    <td>${e.id}</td>
                    <td><strong>${e.model_name}</strong></td>
                    <td>${e.generation}</td>
                    <td><span class="badge bg-warning text-dark">${e.fuel_type}</span></td>
                    <td>${e.engine_type}</td>
                    <td>${e.displacement || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editEngine(${e.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteEngine(${e.id}, '${e.model_name} ${e.fuel_type} ${e.engine_type}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function showEngineForm(engineId = null) {
            document.getElementById('engineForm').reset();
            document.getElementById('engine_id').value = '';
            document.getElementById('engineModalTitle').textContent = '엔진 추가';
            updateModelSelect();
            
            if (engineId) {
                const engine = engines.find(e => e.id == engineId);
                if (engine) {
                    document.getElementById('engineModalTitle').textContent = '엔진 수정';
                    document.getElementById('engine_id').value = engine.id;
                    document.getElementById('car_model_id').value = engine.car_model_id;
                    document.getElementById('fuel_type').value = engine.fuel_type;
                    document.getElementById('engine_type').value = engine.engine_type;
                    document.getElementById('displacement').value = engine.displacement || '';
                    document.getElementById('power_output').value = engine.power_output || '';
                    document.getElementById('engine_description').value = engine.description || '';
                }
            }
            
            engineModal.show();
        }

        function editEngine(id) {
            showEngineForm(id);
        }

        function updateModelSelect() {
            const select = document.getElementById('car_model_id');
            select.innerHTML = '<option value="">선택...</option>' + 
                models.map(m => `<option value="${m.id}">${m.model_name} - ${m.generation}</option>`).join('');
        }

        async function saveEngine() {
            const formData = new FormData(document.getElementById('engineForm'));
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('api/vehicle_engines_api.php?action=save', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ 저장되었습니다!');
                    engineModal.hide();
                    loadEngines();
                } else {
                    alert('❌ 오류: ' + result.message);
                }
            } catch (error) {
                alert('❌ 저장 실패: ' + error.message);
            }
        }

        async function deleteEngine(id, name) {
            if (!confirm(`"${name}"을(를) 삭제하시겠습니까?\n\n⚠️ 주의: 연결된 부품 매핑도 모두 삭제됩니다!`)) {
                return;
            }
            
            try {
                const response = await fetch(`api/vehicle_engines_api.php?action=delete&id=${id}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ 삭제되었습니다!');
                    loadEngines();
                } else {
                    alert('❌ 오류: ' + result.message);
                }
            } catch (error) {
                alert('❌ 삭제 실패: ' + error.message);
            }
        }
    </script>
</body>
</html>
