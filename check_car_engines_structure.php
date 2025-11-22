<?php
$pdo = new PDO("mysql:host=localhost;dbname=hyundai_parts", "root", "Hyundai@2025");
$stmt = $pdo->query("DESCRIBE car_engines");
echo "=== car_engines 테이블 구조 ===\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['Field']} - {$row['Type']}\n";
}

echo "\n=== 샘플 데이터 ===\n";
$stmt = $pdo->query("SELECT * FROM car_engines LIMIT 2");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
