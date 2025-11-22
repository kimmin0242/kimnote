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

    $engineId = $_GET['engine_id'] ?? '';
    
    if (empty($engineId)) {
        echo json_encode(['success' => false, 'message' => 'engine_id 필요']);
        exit;
    }
    
    // notes 컬럼 존재 여부 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM vehicle_parts_mapping LIKE 'notes'");
    $hasNotes = $stmt->fetch();
    
    // 매핑된 부품 가져오기
    $selectCols = "vpm.id, vpm.part_type, vpm.quantity, vpm.position, gp.part_number, gp.capacity";
    if ($hasNotes) {
        $selectCols .= ", vpm.notes";
    }
    
    $sql = "SELECT 
                $selectCols
            FROM vehicle_parts_mapping vpm
            LEFT JOIN genuine_parts gp ON vpm.part_id = gp.id
            WHERE vpm.car_engine_id = ?
            ORDER BY 
                FIELD(vpm.part_type, '엔진오일(대)', '엔진오일(소)', '오일필터', '에어필터', '에어컨필터(실내)', '에어컨필터(외기)'),
                vpm.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$engineId]);
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'parts' => $parts
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
