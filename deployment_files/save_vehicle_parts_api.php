<?php
header('Content-Type: application/json; charset=utf-8');

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

    // POST 데이터 받기
    $input = json_decode(file_get_contents('php://input'), true);
    
    $engineId = $input['engine_id'] ?? '';
    $parts = $input['parts'] ?? [];
    
    if (empty($engineId)) {
        echo json_encode(['success' => false, 'message' => 'engine_id 필요']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    try {
        // 기존 매핑 삭제
        $stmt = $pdo->prepare("DELETE FROM vehicle_parts_mapping WHERE car_engine_id = ?");
        $stmt->execute([$engineId]);
        
        // 새로운 부품 추가
        foreach ($parts as $part) {
            $partNumber = trim($part['part_number']);
            if (empty($partNumber)) continue;
            
            // 1. genuine_parts에서 부품 찾기
            $stmt = $pdo->prepare("SELECT id FROM genuine_parts WHERE part_number = ?");
            $stmt->execute([$partNumber]);
            $existingPart = $stmt->fetch();
            
            if ($existingPart) {
                $partId = $existingPart['id'];
            } else {
                // 2. 부품이 없으면 자동 생성
                $stmt = $pdo->prepare("INSERT INTO genuine_parts 
                                       (part_number, product_name, category_main, capacity) 
                                       VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $partNumber,
                    $part['part_type'] . ' - ' . $partNumber,
                    $part['part_type'],
                    $part['capacity'] ?? ''
                ]);
                $partId = $pdo->lastInsertId();
            }
            
            // 3. notes 컬럼 존재 여부 확인
            $stmt = $pdo->query("SHOW COLUMNS FROM vehicle_parts_mapping LIKE 'notes'");
            $hasNotes = $stmt->fetch();
            
            // 4. 매핑 추가
            if ($hasNotes) {
                $stmt = $pdo->prepare("INSERT INTO vehicle_parts_mapping 
                                       (car_engine_id, part_id, part_type, quantity, position, notes) 
                                       VALUES (?, ?, ?, ?, '', ?)");
                $stmt->execute([
                    $engineId,
                    $partId,
                    $part['part_type'],
                    $part['quantity'] ?? '1개',
                    $part['notes'] ?? ''
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO vehicle_parts_mapping 
                                       (car_engine_id, part_id, part_type, quantity, position) 
                                       VALUES (?, ?, ?, ?, '')");
                $stmt->execute([
                    $engineId,
                    $partId,
                    $part['part_type'],
                    $part['quantity'] ?? '1개'
                ]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '저장 완료',
            'parts_count' => count($parts)
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
