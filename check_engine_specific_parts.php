<?php
require_once 'config/db.php';

echo "=== G80 RG3 엔진별 부품 확인 ===\n\n";

// 1. G80 RG3의 엔진 타입 확인
$sql1 = "SELECT DISTINCT ce.engine_type 
         FROM car_engines ce
         JOIN car_models cm ON ce.car_model_id = cm.id
         WHERE cm.model_name = 'G80' 
         AND cm.generation = 'RG3'
         ORDER BY ce.engine_type";

$stmt1 = $pdo->query($sql1);
$engines = $stmt1->fetchAll(PDO::FETCH_COLUMN);

echo "G80 RG3의 엔진 타입:\n";
foreach ($engines as $engine) {
    echo "  - $engine\n";
}
echo "\n";

// 2. 각 엔진에 맞는 부품 확인
foreach ($engines as $engine) {
    echo "=== [$engine] 전용 부품 ===\n";
    
    $sql2 = "SELECT category_main, product_name, part_number, compatible_engines, capacity
             FROM genuine_parts
             WHERE compatible_engines LIKE :engine
             ORDER BY category_main, product_name";
    
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->bindValue(':engine', '%' . $engine . '%');
    $stmt2->execute();
    $parts = $stmt2->fetchAll();
    
    if (count($parts) > 0) {
        foreach ($parts as $part) {
            echo sprintf("  [%s] %s - %s (용량: %s)\n", 
                $part['category_main'],
                $part['product_name'],
                $part['part_number'],
                $part['capacity'] ?: 'N/A'
            );
            echo "    → 호환: {$part['compatible_engines']}\n";
        }
    } else {
        echo "  (전용 부품 없음)\n";
    }
    echo "\n";
}

// 3. '전체' 호환 엔진 관련 부품
echo "=== [전체] 호환 엔진 부품 ===\n";
$sql3 = "SELECT category_main, product_name, part_number, capacity
         FROM genuine_parts
         WHERE compatible_engines = '전체'
         AND category_main IN ('엔진오일(대)', '엔진오일(소)', '오일필터', '오일량')
         ORDER BY category_main, product_name";

$stmt3 = $pdo->query($sql3);
$universalParts = $stmt3->fetchAll();

foreach ($universalParts as $part) {
    echo sprintf("  [%s] %s - %s (용량: %s)\n", 
        $part['category_main'],
        $part['product_name'],
        $part['part_number'],
        $part['capacity'] ?: 'N/A'
    );
}

echo "\n완료!\n";
?>
