<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Wkddnjsqls!@12';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h1>데이터베이스 상태 확인</h1>";
    
    // 1. car_models 테이블 확인
    echo "<h2>1. car_models 테이블 (G80 모델)</h2>";
    $stmt = $pdo->query("SELECT * FROM car_models WHERE model_name = 'G80'");
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($models);
    echo "</pre>";
    echo "<p>총 개수: " . count($models) . "</p>";
    
    // 2. car_engines 테이블 확인
    echo "<h2>2. car_engines 테이블</h2>";
    $stmt = $pdo->query("SELECT ce.*, cm.model_name, cm.generation 
                         FROM car_engines ce 
                         JOIN car_models cm ON ce.car_model_id = cm.id 
                         WHERE cm.model_name = 'G80'");
    $engines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($engines);
    echo "</pre>";
    echo "<p>총 개수: " . count($engines) . "</p>";
    
    // 3. vehicle_parts_mapping 테이블 확인
    echo "<h2>3. vehicle_parts_mapping 테이블</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vehicle_parts_mapping");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>총 매핑 개수: " . $result['count'] . "</p>";
    
    // 4. 매핑된 부품 상세 확인
    echo "<h2>4. G80 RG3 매핑된 부품</h2>";
    $stmt = $pdo->query("SELECT 
                            cm.model_name,
                            cm.generation,
                            ce.engine_type,
                            gp.part_number,
                            gp.part_name,
                            vpm.part_type,
                            vpm.quantity
                         FROM vehicle_parts_mapping vpm
                         JOIN car_engines ce ON vpm.car_engine_id = ce.id
                         JOIN car_models cm ON ce.car_model_id = cm.id
                         JOIN genuine_parts gp ON vpm.part_id = gp.id
                         WHERE cm.model_name = 'G80'");
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($parts);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<h1 style='color:red'>에러 발생</h1>";
    echo "<p><strong>에러 메시지:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
