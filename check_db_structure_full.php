<?php
$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Hyundai@2025';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    echo "=== car_models 테이블 구조 ===\n";
    $stmt = $pdo->query("DESCRIBE car_models");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} ({$row['Type']}) - {$row['Null']} - {$row['Key']}\n";
    }
    
    echo "\n=== car_engines 테이블 구조 ===\n";
    $stmt = $pdo->query("DESCRIBE car_engines");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} ({$row['Type']}) - {$row['Null']} - {$row['Key']}\n";
    }
    
    echo "\n=== 샘플 데이터 ===\n";
    $stmt = $pdo->query("SELECT cm.*, ce.engine_type, ce.engine_name 
                         FROM car_models cm 
                         LEFT JOIN car_engines ce ON cm.id = ce.car_model_id 
                         LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
