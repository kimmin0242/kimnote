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

// 데이터베이스 연결
$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Hyundai@2025';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("DB 연결 실패: " . $e->getMessage());
}

// 차량 목록 가져오기 (엔진 정보도 함께)
$stmt = $pdo->query("SELECT cm.*, 
                     COUNT(DISTINCT ce.id) as engine_count
                     FROM car_models cm
                     LEFT JOIN car_engines ce ON cm.id = ce.car_model_id
                     GROUP BY cm.id
                     ORDER BY cm.model_name, cm.generation");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 각 차량의 엔진 목록 가져오기
$vehicleEngines = [];
// 먼저 car_engines 테이블의 컬럼 확인
$stmt = $pdo->query("SHOW COLUMNS FROM car_engines");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$hasDisplacement = in_array('displacement', $columns);
$hasPowerOutput = in_array('power_output', $columns);
$hasDescription = in_array('description', $columns);

// 존재하는 컬럼만 SELECT
$selectColumns = 'id, car_model_id, fuel_type, engine_type';
if ($hasDisplacement) $selectColumns .= ', displacement';
if ($hasPowerOutput) $selectColumns .= ', power_output';
if ($hasDescription) $selectColumns .= ', description';

foreach ($vehicles as $vehicle) {
    $stmt = $pdo->prepare("SELECT $selectColumns FROM car_engines WHERE car_model_id = ? ORDER BY engine_type");
    $stmt->execute([$vehicle['id']]);
    $vehicleEngines[$vehicle['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .vehicle-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .vehicle-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .engine-item {
            padding: 8px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid #0d6efd;
        }
        .fuel-badge {
            font-size: 0.8em;
            padding: 3px 8px;
        }
        .badge-gasoline { background-color: #ff6b6b; }
        .badge-diesel { background-color: #4ecdc4; }
        .badge-electric { background-color: #95e1d3; }
        .badge-hybrid { background-color: #ffd93d; }
        .badge-lpg { background-color: #a29bfe; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">🚗 차량 정보 관리 시스템</span>
            <div>
                <a href="admin_vehicle_parts.php" class="btn btn-outline-light btn-sm me-2">📦 부품 매핑</a>
                <a href="/hyundai-parts/" class="btn btn-outline-light btn-sm">🏠 메인</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- 상단 버튼 -->
        <div class="row mb-4">
            <div class="col-12">
                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                    <i class="fas fa-plus"></i> 새 차종/세대 추가
                </button>
                <div class="float-end">
                    <span class="badge bg-secondary">전체 차량: <?php echo count($vehicles); ?>개</span>
                </div>
            </div>
        </div>

        <!-- 차량 목록 -->
        <div class="row">
            <?php foreach ($vehicles as $vehicle): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card vehicle-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <?php echo htmlspecialchars($vehicle['model_name']); ?>
                            <small class="float-end"><?php echo htmlspecialchars($vehicle['generation']); ?></small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">
                            <small>
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($vehicle['manufacturer'] ?? '현대'); ?> |
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($vehicle['brand'] ?? '-'); ?> |
                                <i class="fas fa-car"></i> <?php echo htmlspecialchars($vehicle['category'] ?? '-'); ?>
                            </small>
                        </p>
                        
                        <div class="mb-2">
                            <strong>등록된 엔진: <?php echo $vehicle['engine_count']; ?>개</strong>
                        </div>

                        <!-- 엔진 목록 (항상 표시) -->
                        <div class="engines-container mb-3">
                            <?php if (!empty($vehicleEngines[$vehicle['id']])): ?>
                                <?php foreach ($vehicleEngines[$vehicle['id']] as $engine): ?>
                                    <?php
                                    $fuelBadgeClass = 'bg-secondary';
                                    switch($engine['fuel_type']) {
                                        case '가솔린': $fuelBadgeClass = 'badge-gasoline'; break;
                                        case '디젤': $fuelBadgeClass = 'badge-diesel'; break;
                                        case '전기': $fuelBadgeClass = 'badge-electric'; break;
                                        case '하이브리드': case '플러그인 하이브리드': $fuelBadgeClass = 'badge-hybrid'; break;
                                        case 'LPG': $fuelBadgeClass = 'badge-lpg'; break;
                                    }
                                    ?>
                                    <div class="engine-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge <?php echo $fuelBadgeClass; ?> fuel-badge"><?php echo htmlspecialchars($engine['fuel_type']); ?></span>
                                                <strong><?php echo htmlspecialchars($engine['engine_type']); ?></strong>
                                                <?php if (isset($engine['displacement']) && !empty($engine['displacement'])): ?>
                                                    <small class="text-muted">(<?php echo htmlspecialchars($engine['displacement']); ?>)</small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-warning btn-sm" onclick="editEngine(<?php echo $engine['id']; ?>)" title="이 엔진 수정">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" onclick="deleteEngine(<?php echo $engine['id']; ?>, '<?php echo htmlspecialchars($engine['engine_type'], ENT_QUOTES); ?>')" title="이 엔진 삭제">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small mb-0">등록된 엔진이 없습니다.</p>
                            <?php endif; ?>
                        </div>

                        <!-- 액션 버튼 -->
                        <div class="btn-group w-100" role="group">
                            <button class="btn btn-success btn-sm" onclick="addEngine(<?php echo $vehicle['id']; ?>, '<?php echo htmlspecialchars($vehicle['model_name'] . ' ' . $vehicle['generation'], ENT_QUOTES); ?>')">
                                <i class="fas fa-plus"></i> 엔진 추가
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="editVehicle(<?php echo $vehicle['id']; ?>)">
                                <i class="fas fa-edit"></i> 차량정보 수정
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteVehicle(<?php echo $vehicle['id']; ?>, '<?php echo htmlspecialchars($vehicle['model_name'] . ' ' . $vehicle['generation'], ENT_QUOTES); ?>')">
                                <i class="fas fa-trash"></i> 삭제
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (count($vehicles) === 0): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5>등록된 차량이 없습니다</h5>
                    <p>상단의 "새 차종/세대 추가" 버튼을 눌러 첫 차량을 등록하세요.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 새 차종/세대 추가 모달 -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-car"></i> 새 차종/세대 추가</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addVehicleForm">
                        <div class="mb-3">
                            <label class="form-label">제조사</label>
                            <input type="text" class="form-control" name="manufacturer" value="현대" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">카테고리</label>
                            <select class="form-select" name="category">
                                <option value="">선택...</option>
                                <option value="세단">세단</option>
                                <option value="SUV">SUV</option>
                                <option value="쿠페">쿠페</option>
                                <option value="해치백">해치백</option>
                                <option value="왜건">왜건</option>
                                <option value="MPV">MPV</option>
                                <option value="전기차">전기차</option>
                                <option value="하이브리드">하이브리드</option>
                                <option value="상용차">상용차</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">브랜드/라인업</label>
                            <select class="form-select" name="brand" id="brand_select">
                                <option value="">선택...</option>
                                <optgroup label="제너시스">
                                    <option value="G90">G90 (EQ900)</option>
                                    <option value="G80">G80</option>
                                    <option value="G70">G70</option>
                                    <option value="GV80">GV80</option>
                                    <option value="GV70">GV70</option>
                                    <option value="GV60">GV60</option>
                                    <option value="Electrified G80">Electrified G80</option>
                                    <option value="Electrified GV70">Electrified GV70</option>
                                </optgroup>
                                <optgroup label="N 라인">
                                    <option value="아반떼 N">아반떼 N</option>
                                    <option value="벨로스터 N">벨로스터 N</option>
                                    <option value="i30 N">i30 N</option>
                                    <option value="코나 N">코나 N</option>
                                    <option value="아이오닉 5 N">아이오닉 5 N</option>
                                </optgroup>
                                <optgroup label="아이오닉">
                                    <option value="아이오닉 5">아이오닉 5</option>
                                    <option value="아이오닉 6">아이오닉 6</option>
                                    <option value="아이오닉 7">아이오닉 7</option>
                                </optgroup>
                                <optgroup label="현대 승용">
                                    <option value="그랜저">그랜저</option>
                                    <option value="소나타">소나타</option>
                                    <option value="아반떼">아반떼</option>
                                    <option value="쏘나타">쏘나타</option>
                                </optgroup>
                                <optgroup label="현대 SUV">
                                    <option value="팰리세이드">팰리세이드</option>
                                    <option value="싼타페">싼타페</option>
                                    <option value="투싼">투싼</option>
                                    <option value="코나">코나</option>
                                    <option value="베뉴">베뉴</option>
                                    <option value="캐스퍼">캐스퍼</option>
                                </optgroup>
                                <optgroup label="현대 MPV">
                                    <option value="스타리아">스타리아</option>
                                    <option value="스타렉스">스타렉스</option>
                                    <option value="쏠라티">쏠라티</option>
                                </optgroup>
                                <optgroup label="기타">
                                    <option value="직접입력">직접 입력...</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="mb-3" id="custom_brand_div" style="display:none;">
                            <label class="form-label">브랜드 직접 입력</label>
                            <input type="text" class="form-control" id="custom_brand" placeholder="예: 스타리아, 넥쏘">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">차종명 *</label>
                            <input type="text" class="form-control" name="model_name" placeholder="예: G80, 소나타, 아이오닉6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">세대 *</label>
                            <input type="text" class="form-control" name="generation" placeholder="예: RG3 (3세대), DN8 (8세대)" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">설명</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="saveVehicle()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 차종 수정 모달 -->
    <div class="modal fade" id="editVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> 차종/세대 수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editVehicleForm">
                        <input type="hidden" name="id" id="edit_vehicle_id">
                        <div class="mb-3">
                            <label class="form-label">제조사</label>
                            <input type="text" class="form-control" name="manufacturer" id="edit_manufacturer" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">카테고리</label>
                            <select class="form-select" name="category" id="edit_category">
                                <option value="">선택...</option>
                                <option value="세단">세단</option>
                                <option value="SUV">SUV</option>
                                <option value="쿠페">쿠페</option>
                                <option value="해치백">해치백</option>
                                <option value="왜건">왜건</option>
                                <option value="MPV">MPV</option>
                                <option value="전기차">전기차</option>
                                <option value="하이브리드">하이브리드</option>
                                <option value="상용차">상용차</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">브랜드/라인업</label>
                            <select class="form-select" name="brand" id="edit_brand">
                                <option value="">선택...</option>
                                <optgroup label="제너시스">
                                    <option value="G90">G90 (EQ900)</option>
                                    <option value="G80">G80</option>
                                    <option value="G70">G70</option>
                                    <option value="GV80">GV80</option>
                                    <option value="GV70">GV70</option>
                                    <option value="GV60">GV60</option>
                                    <option value="Electrified G80">Electrified G80</option>
                                    <option value="Electrified GV70">Electrified GV70</option>
                                </optgroup>
                                <optgroup label="N 라인">
                                    <option value="아반떼 N">아반떼 N</option>
                                    <option value="벨로스터 N">벨로스터 N</option>
                                    <option value="i30 N">i30 N</option>
                                    <option value="코나 N">코나 N</option>
                                    <option value="아이오닉 5 N">아이오닉 5 N</option>
                                </optgroup>
                                <optgroup label="아이오닉">
                                    <option value="아이오닉 5">아이오닉 5</option>
                                    <option value="아이오닉 6">아이오닉 6</option>
                                    <option value="아이오닉 7">아이오닉 7</option>
                                </optgroup>
                                <optgroup label="현대 승용">
                                    <option value="그랜저">그랜저</option>
                                    <option value="소나타">소나타</option>
                                    <option value="아반떼">아반떼</option>
                                    <option value="쏘나타">쏘나타</option>
                                </optgroup>
                                <optgroup label="현대 SUV">
                                    <option value="팰리세이드">팰리세이드</option>
                                    <option value="싼타페">싼타페</option>
                                    <option value="투싼">투싼</option>
                                    <option value="코나">코나</option>
                                    <option value="베뉴">베뉴</option>
                                    <option value="캐스퍼">캐스퍼</option>
                                </optgroup>
                                <optgroup label="현대 MPV">
                                    <option value="스타리아">스타리아</option>
                                    <option value="스타렉스">스타렉스</option>
                                    <option value="쏠라티">쏠라티</option>
                                </optgroup>
                                <optgroup label="기타">
                                    <option value="직접입력">직접 입력...</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="mb-3" id="edit_custom_brand_div" style="display:none;">
                            <label class="form-label">브랜드 직접 입력</label>
                            <input type="text" class="form-control" id="edit_custom_brand" placeholder="예: 스타리아, 넥쏘">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">차종명</label>
                            <input type="text" class="form-control" name="model_name" id="edit_model_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">세대</label>
                            <input type="text" class="form-control" name="generation" id="edit_generation" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">설명</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-warning" onclick="updateVehicle()">수정 저장</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 엔진 추가 모달 -->
    <div class="modal fade" id="addEngineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-cog"></i> 엔진 추가: <span id="engine_vehicle_name"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEngineForm">
                        <input type="hidden" name="car_model_id" id="engine_car_model_id">
                        <div class="mb-3">
                            <label class="form-label">연료 타입 * ⛽</label>
                            <select class="form-select" name="fuel_type" required>
                                <option value="">선택...</option>
                                <option value="가솔린">가솔린</option>
                                <option value="디젤">디젤</option>
                                <option value="전기">전기</option>
                                <option value="하이브리드">하이브리드</option>
                                <option value="플러그인 하이브리드">플러그인 하이브리드</option>
                                <option value="LPG">LPG</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">엔진 타입 *</label>
                            <input type="text" class="form-control" name="engine_type" placeholder="예: 2.5 가솔린 터보, 2.2 디젤, 전기 모터" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">배기량</label>
                            <input type="text" class="form-control" name="displacement" placeholder="예: 2.5L, 2.2L">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">출력</label>
                            <input type="text" class="form-control" name="power_output" placeholder="예: 304마력, 150kW">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">설명</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-success" onclick="saveEngine()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 엔진 수정 모달 -->
    <div class="modal fade" id="editEngineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> 엔진 수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editEngineForm">
                        <input type="hidden" name="id" id="edit_engine_id">
                        <div class="mb-3">
                            <label class="form-label">연료 타입 *</label>
                            <select class="form-select" name="fuel_type" id="edit_fuel_type" required>
                                <option value="">선택...</option>
                                <option value="가솔린">가솔린</option>
                                <option value="디젤">디젤</option>
                                <option value="전기">전기</option>
                                <option value="하이브리드">하이브리드</option>
                                <option value="플러그인 하이브리드">플러그인 하이브리드</option>
                                <option value="LPG">LPG</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">엔진 타입 *</label>
                            <input type="text" class="form-control" name="engine_type" id="edit_engine_type" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">배기량</label>
                            <input type="text" class="form-control" name="displacement" id="edit_displacement">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">출력</label>
                            <input type="text" class="form-control" name="power_output" id="edit_power_output">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">설명</label>
                            <textarea class="form-control" name="description" id="edit_engine_description" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-warning" onclick="updateEngine()">수정 저장</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 브랜드 직접 입력 처리
        document.getElementById('brand_select').addEventListener('change', function() {
            if (this.value === '직접입력') {
                document.getElementById('custom_brand_div').style.display = 'block';
            } else {
                document.getElementById('custom_brand_div').style.display = 'none';
            }
        });

        // 차량 저장
        async function saveVehicle() {
            const form = document.getElementById('addVehicleForm');
            const formData = new FormData(form);
            
            // 직접 입력인 경우 처리
            const brandSelect = document.getElementById('brand_select');
            if (brandSelect.value === '직접입력') {
                const customBrand = document.getElementById('custom_brand').value.trim();
                if (!customBrand) {
                    alert('⚠️ 브랜드를 직접 입력해주세요.');
                    return;
                }
                formData.set('brand', customBrand);
            }
            
            try {
                const response = await fetch('admin_vehicle_api.php?action=add_vehicle', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ 차량이 추가되었습니다!');
                    location.reload();
                } else {
                    alert('❌ 오류: ' + result.message);
                }
            } catch (error) {
                alert('❌ 저장 실패: ' + error);
            }
        }

        // 수정 모달 브랜드 직접 입력 처리
        document.getElementById('edit_brand').addEventListener('change', function() {
            if (this.value === '직접입력') {
                document.getElementById('edit_custom_brand_div').style.display = 'block';
            } else {
                document.getElementById('edit_custom_brand_div').style.display = 'none';
            }
        });

        // 차량 수정
        async function editVehicle(id) {
            try {
                console.log('차량 정보 로드 시작:', id);
                const response = await fetch('admin_vehicle_api.php?action=get_vehicle&id=' + id);
                const data = await response.json();
                
                console.log('서버 응답:', data);
                
                if (data.success) {
                    document.getElementById('edit_vehicle_id').value = data.vehicle.id;
                    document.getElementById('edit_manufacturer').value = data.vehicle.manufacturer || '';
                    document.getElementById('edit_category').value = data.vehicle.category || '';
                    
                    // 브랜드 값 설정
                    const brandSelect = document.getElementById('edit_brand');
                    const brandValue = data.vehicle.brand || '';
                    
                    // 옵션에 있는지 확인
                    let foundOption = false;
                    for (let option of brandSelect.options) {
                        if (option.value === brandValue) {
                            brandSelect.value = brandValue;
                            foundOption = true;
                            break;
                        }
                    }
                    
                    // 옵션에 없으면 직접 입력으로 설정
                    if (!foundOption && brandValue) {
                        brandSelect.value = '직접입력';
                        document.getElementById('edit_custom_brand_div').style.display = 'block';
                        document.getElementById('edit_custom_brand').value = brandValue;
                    } else {
                        document.getElementById('edit_custom_brand_div').style.display = 'none';
                    }
                    
                    document.getElementById('edit_model_name').value = data.vehicle.model_name || '';
                    document.getElementById('edit_generation').value = data.vehicle.generation || '';
                    document.getElementById('edit_description').value = data.vehicle.description || '';
                    
                    new bootstrap.Modal(document.getElementById('editVehicleModal')).show();
                } else {
                    alert('❌ 차량 정보 로드 실패: ' + (data.message || '알 수 없는 오류'));
                }
            } catch (error) {
                console.error('차량 수정 오류:', error);
                alert('❌ 로드 실패: ' + error);
            }
        }

        async function updateVehicle() {
            const form = document.getElementById('editVehicleForm');
            const formData = new FormData(form);
            
            // 직접 입력인 경우 처리
            const brandSelect = document.getElementById('edit_brand');
            if (brandSelect.value === '직접입력') {
                const customBrand = document.getElementById('edit_custom_brand').value.trim();
                if (!customBrand) {
                    alert('⚠️ 브랜드를 직접 입력해주세요.');
                    return;
                }
                formData.set('brand', customBrand);
            }
            
            try {
                const response = await fetch('admin_vehicle_api.php?action=update_vehicle', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ 수정되었습니다!');
                    location.reload();
                } else {
                    alert('❌ 오류: ' + result.message);
                }
            } catch (error) {
                alert('❌ 수정 실패: ' + error);
            }
        }

        // 차량 삭제
        async function deleteVehicle(id, name) {
            if (!confirm(`"${name}" 차량을 삭제하시겠습니까?\n\n⚠️ 관련된 모든 엔진 정보와 부품 매핑도 함께 삭제됩니다!`)) {
                return;
            }
            
            try {
                const response = await fetch('admin_vehicle_api.php?action=delete_vehicle&id=' + id, {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ 삭제되었습니다!');
                    location.reload();
                } else {
                    alert('❌ 오류: ' + result.message);
                }
            } catch (error) {
                alert('❌ 삭제 실패: ' + error);
            }
        }

        // 엔진 추가 모달
        function addEngine(vehicleId, vehicleName) {
            document.getElementById('engine_car_model_id').value = vehicleId;
            document.getElementById('engine_vehicle_name').textContent = vehicleName;
            document.getElementById('addEngineForm').reset();
            new bootstrap.Modal(document.getElementById('addEngineModal')).show();
        }

        // 엔진 저장
        async function saveEngine() {
            const form = document.getElementById('addEngineForm');
            const formData = new FormData(form);
            
            try {
                const response = await fetch('admin_vehicle_api.php?action=add_engine', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ 엔진이 추가되었습니다!');
                    location.reload();
                } else {
                    alert('❌ 오류: ' + result.message);
                }
            } catch (error) {
                alert('❌ 저장 실패: ' + error);
            }
        }

        // 엔진 수정
        async function editEngine(id) {
            try {
                const response = await fetch('admin_vehicle_api.php?action=get_engine&id=' + id);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('edit_engine_id').value = data.engine.id;
                    document.getElementById('edit_fuel_type').value = data.engine.fuel_type || '';
                    document.getElementById('edit_engine_type').value = data.engine.engine_type || '';
                    document.getElementById('edit_displacement').value = data.engine.displacement || '';
                    document.getElementById('edit_power_output').value = data.engine.power_output || '';
                    document.getElementById('edit_engine_description').value = data.engine.description || '';
                    
                    new bootstrap.Modal(document.getElementById('editEngineModal')).show();
                }
            } catch (error) {
                alert('❌ 로드 실패: ' + error);
            }
        }

        async function updateEngine() {
            const form = document.getElementById('editEngineForm');
            const formData = new FormData(form);
            
            try {
                const response = await fetch('admin_vehicle_api.php?action=update_engine', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ 수정되었습니다!');
                    location.reload();
                } else {
                    alert('❌ 오류: ' + result.message);
                }
            } catch (error) {
                alert('❌ 수정 실패: ' + error);
            }
        }

        // 엔진 삭제
        async function deleteEngine(id, name) {
            if (!confirm(`"${name}" 엔진을 삭제하시겠습니까?\n\n⚠️ 관련된 모든 부품 매핑도 함께 삭제됩니다!`)) {
                return;
            }
            
            try {
                const response = await fetch('admin_vehicle_api.php?action=delete_engine&id=' + id, {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ 삭제되었습니다!');
                    location.reload();
                } else {
                    alert('❌ 오류: ' + result.message);
                }
            } catch (error) {
                alert('❌ 삭제 실패: ' + error);
            }
        }
    </script>
</body>
</html>
