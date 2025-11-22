<?php
header('Content-Type: application/json; charset=UTF-8');

$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Hyundai@2025';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ========================================
// 차량 관리
// ========================================

// 차량 추가
if ($action === 'add_vehicle') {
    try {
        // 테이블 컬럼 확인
        $stmt = $pdo->query("SHOW COLUMNS FROM car_models");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $hasBrand = in_array('brand', $columns);
        $hasCategory = in_array('category', $columns);
        $hasDescription = in_array('description', $columns);
        
        // 동적으로 쿼리 생성
        $insertCols = ['manufacturer', 'model_name', 'generation'];
        $insertVals = [$_POST['manufacturer'] ?? '현대', $_POST['model_name'], $_POST['generation']];
        
        if ($hasBrand) {
            $insertCols[] = 'brand';
            $insertVals[] = $_POST['brand'] ?? null;
        }
        if ($hasCategory) {
            $insertCols[] = 'category';
            $insertVals[] = $_POST['category'] ?? null;
        }
        if ($hasDescription) {
            $insertCols[] = 'description';
            $insertVals[] = $_POST['description'] ?? null;
        }
        
        $colsList = implode(', ', $insertCols);
        $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
        
        $stmt = $pdo->prepare("INSERT INTO car_models ($colsList) VALUES ($placeholders)");
        $stmt->execute($insertVals);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 차량 정보 가져오기
if ($action === 'get_vehicle') {
    try {
        // 테이블 컬럼 확인
        $stmt = $pdo->query("SHOW COLUMNS FROM car_models");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $hasBrand = in_array('brand', $columns);
        $hasCategory = in_array('category', $columns);
        $hasDescription = in_array('description', $columns);
        
        // 존재하는 컬럼만 SELECT
        $selectColumns = 'id, manufacturer, model_name, generation';
        if ($hasBrand) $selectColumns .= ', brand';
        if ($hasCategory) $selectColumns .= ', category';
        if ($hasDescription) $selectColumns .= ', description';
        
        $stmt = $pdo->prepare("SELECT $selectColumns FROM car_models WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'vehicle' => $vehicle]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 차량 수정
if ($action === 'update_vehicle') {
    try {
        // 테이블 컬럼 확인
        $stmt = $pdo->query("SHOW COLUMNS FROM car_models");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $hasBrand = in_array('brand', $columns);
        $hasCategory = in_array('category', $columns);
        $hasDescription = in_array('description', $columns);
        
        // 동적으로 UPDATE 쿼리 생성
        $updateParts = ['manufacturer = ?', 'model_name = ?', 'generation = ?'];
        $updateVals = [$_POST['manufacturer'], $_POST['model_name'], $_POST['generation']];
        
        if ($hasBrand) {
            $updateParts[] = 'brand = ?';
            $updateVals[] = $_POST['brand'] ?? null;
        }
        if ($hasCategory) {
            $updateParts[] = 'category = ?';
            $updateVals[] = $_POST['category'] ?? null;
        }
        if ($hasDescription) {
            $updateParts[] = 'description = ?';
            $updateVals[] = $_POST['description'] ?? null;
        }
        
        $updateVals[] = $_POST['id']; // WHERE 조건의 ID
        
        $updateClause = implode(', ', $updateParts);
        $stmt = $pdo->prepare("UPDATE car_models SET $updateClause WHERE id = ?");
        $stmt->execute($updateVals);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 차량 삭제 (CASCADE로 엔진, 부품 매핑도 자동 삭제)
if ($action === 'delete_vehicle') {
    try {
        $stmt = $pdo->prepare("DELETE FROM car_models WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ========================================
// 엔진 관리
// ========================================

// 엔진 목록 가져오기
if ($action === 'get_engines') {
    try {
        // 테이블 컬럼 확인
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
        
        $stmt = $pdo->prepare("SELECT $selectColumns FROM car_engines WHERE car_model_id = ? ORDER BY fuel_type, engine_type");
        $stmt->execute([$_GET['vehicle_id']]);
        $engines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'engines' => $engines]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 엔진 추가
if ($action === 'add_engine') {
    try {
        // 테이블 컬럼 확인
        $stmt = $pdo->query("SHOW COLUMNS FROM car_engines");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // displacement, power_output, description 컬럼이 있는지 확인
        $hasDisplacement = in_array('displacement', $columns);
        $hasPowerOutput = in_array('power_output', $columns);
        $hasDescription = in_array('description', $columns);
        
        if ($hasDisplacement && $hasPowerOutput && $hasDescription) {
            $stmt = $pdo->prepare("INSERT INTO car_engines (car_model_id, fuel_type, engine_type, displacement, power_output, description) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['car_model_id'],
                $_POST['fuel_type'],
                $_POST['engine_type'],
                $_POST['displacement'] ?? null,
                $_POST['power_output'] ?? null,
                $_POST['description'] ?? null
            ]);
        } else {
            // 기본 컬럼만 사용
            $stmt = $pdo->prepare("INSERT INTO car_engines (car_model_id, fuel_type, engine_type) 
                                   VALUES (?, ?, ?)");
            $stmt->execute([
                $_POST['car_model_id'],
                $_POST['fuel_type'],
                $_POST['engine_type']
            ]);
        }
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 엔진 정보 가져오기
if ($action === 'get_engine') {
    try {
        // 테이블 컬럼 확인
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
        
        $stmt = $pdo->prepare("SELECT $selectColumns FROM car_engines WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $engine = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'engine' => $engine]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 엔진 수정
if ($action === 'update_engine') {
    try {
        // 테이블 컬럼 확인
        $stmt = $pdo->query("SHOW COLUMNS FROM car_engines");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $hasDisplacement = in_array('displacement', $columns);
        $hasPowerOutput = in_array('power_output', $columns);
        $hasDescription = in_array('description', $columns);
        
        if ($hasDisplacement && $hasPowerOutput && $hasDescription) {
            $stmt = $pdo->prepare("UPDATE car_engines 
                                   SET fuel_type = ?, engine_type = ?, displacement = ?, power_output = ?, description = ?
                                   WHERE id = ?");
            $stmt->execute([
                $_POST['fuel_type'],
                $_POST['engine_type'],
                $_POST['displacement'] ?? null,
                $_POST['power_output'] ?? null,
                $_POST['description'] ?? null,
                $_POST['id']
            ]);
        } else {
            // 기본 컬럼만 수정
            $stmt = $pdo->prepare("UPDATE car_engines 
                                   SET fuel_type = ?, engine_type = ?
                                   WHERE id = ?");
            $stmt->execute([
                $_POST['fuel_type'],
                $_POST['engine_type'],
                $_POST['id']
            ]);
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 엔진 삭제 (CASCADE로 부품 매핑도 자동 삭제)
if ($action === 'delete_engine') {
    try {
        $stmt = $pdo->prepare("DELETE FROM car_engines WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 잘못된 액션
echo json_encode(['success' => false, 'message' => 'Invalid action']);
