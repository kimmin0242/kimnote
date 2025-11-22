<?php
require_once 'config/db.php';

echo "=== 데이터베이스 구조 확인 ===\n\n";

// 1. 테이블 목록
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "테이블 목록:\n";
foreach ($tables as $table) {
    echo "  - $table\n";
}
echo "\n";

// 2. genuine_parts 테이블 구조
echo "=== genuine_parts 테이블 구조 ===\n";
$columns = $pdo->query("DESCRIBE genuine_parts")->fetchAll();
foreach ($columns as $col) {
    echo sprintf("  %s: %s\n", $col['Field'], $col['Type']);
}
echo "\n";

// 3. car_engines 테이블 구조 (있다면)
if (in_array('car_engines', $tables)) {
    echo "=== car_engines 테이블 구조 ===\n";
    $columns = $pdo->query("DESCRIBE car_engines")->fetchAll();
    foreach ($columns as $col) {
        echo sprintf("  %s: %s\n", $col['Field'], $col['Type']);
    }
    echo "\n";
}

// 4. car_models 테이블 구조
if (in_array('car_models', $tables)) {
    echo "=== car_models 테이블 구조 ===\n";
    $columns = $pdo->query("DESCRIBE car_models")->fetchAll();
    foreach ($columns as $col) {
        echo sprintf("  %s: %s\n", $col['Field'], $col['Type']);
    }
    echo "\n";
}

// 5. 엑셀 데이터처럼 차량-부품 매핑 테이블이 있는지 확인
echo "=== 테이블별 데이터 샘플 ===\n\n";

// genuine_parts 샘플
echo "[genuine_parts] 샘플 (5개):\n";
$parts = $pdo->query("SELECT * FROM genuine_parts LIMIT 5")->fetchAll();
foreach ($parts as $part) {
    print_r($part);
    echo "\n";
}

echo "\n";
?>
