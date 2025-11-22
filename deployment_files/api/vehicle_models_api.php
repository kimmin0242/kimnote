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

// 목록 조회
if ($action === 'list') {
    $stmt = $pdo->query("SELECT * FROM car_models ORDER BY brand, model_name, generation");
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'models' => $models]);
    exit;
}

// 저장 (추가/수정)
if ($action === 'save') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        if (!empty($data['id'])) {
            // 수정
            $stmt = $pdo->prepare("UPDATE car_models SET 
                manufacturer = ?, brand = ?, category = ?, model_name = ?, generation = ?, description = ?
                WHERE id = ?");
            $stmt->execute([
                $data['manufacturer'], $data['brand'], $data['category'],
                $data['model_name'], $data['generation'], $data['description'],
                $data['id']
            ]);
        } else {
            // 추가
            $stmt = $pdo->prepare("INSERT INTO car_models 
                (manufacturer, brand, category, model_name, generation, description) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['manufacturer'], $data['brand'], $data['category'],
                $data['model_name'], $data['generation'], $data['description']
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
        $stmt = $pdo->prepare("DELETE FROM car_models WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
