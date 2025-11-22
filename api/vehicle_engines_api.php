<?php
header('Content-Type: application/json; charset=UTF-8');

$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Kdmdtt1225**';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

// 목록 조회 (차종 정보 포함)
if ($action === 'list') {
    $stmt = $pdo->query("SELECT ce.*, cm.model_name, cm.generation 
                         FROM car_engines ce
                         JOIN car_models cm ON ce.car_model_id = cm.id
                         ORDER BY cm.model_name, cm.generation, ce.fuel_type, ce.engine_type");
    $engines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'engines' => $engines]);
    exit;
}

// 저장 (추가/수정)
if ($action === 'save') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        if (!empty($data['id'])) {
            // 수정
            $stmt = $pdo->prepare("UPDATE car_engines SET 
                car_model_id = ?, fuel_type = ?, engine_type = ?, 
                displacement = ?, power_output = ?, description = ?
                WHERE id = ?");
            $stmt->execute([
                $data['car_model_id'], $data['fuel_type'], $data['engine_type'],
                $data['displacement'], $data['power_output'], $data['description'],
                $data['id']
            ]);
        } else {
            // 추가
            $stmt = $pdo->prepare("INSERT INTO car_engines 
                (car_model_id, fuel_type, engine_type, displacement, power_output, description) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['car_model_id'], $data['fuel_type'], $data['engine_type'],
                $data['displacement'], $data['power_output'], $data['description']
            ]);
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 삭제
if ($action === 'delete') {
    $id = $_GET['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM car_engines WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
