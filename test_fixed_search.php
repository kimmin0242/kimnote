<?php
require_once 'config/db.php';

echo "=== G80 RG3 검색 테스트 ===\n\n";

// 1. G80 RG3의 엔진 정보 확인
$sql = "SELECT DISTINCT ce.engine_type 
        FROM car_engines ce
        JOIN car_models cm ON ce.car_model_id = cm.id
        WHERE cm.model_name = 'G80' 
        AND cm.generation = 'RG3'";

$stmt = $pdo->query($sql);
$engines = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "G80 RG3의 엔진 타입들:\n";
foreach ($engines as $engine) {
    echo "- $engine\n";
}
echo "\n";

// 2. '전체' 부품 중 엔진 관련 카테고리만 조회
$sql2 = "SELECT category_main, product_name, part_number, compatible_engines
         FROM genuine_parts
         WHERE compatible_engines = '전체'
         AND category_main IN ('엔진오일(대)', '엔진오일(소)', '오일필터', '오일량')
         ORDER BY category_main";

$stmt2 = $pdo->query($sql2);
$parts = $stmt2->fetchAll();

echo "=== '전체' 호환 엔진 관련 부품 (" . count($parts) . "개) ===\n";
foreach ($parts as $part) {
    echo sprintf("[%s] %s - %s (호환: %s)\n", 
        $part['category_main'], 
        $part['product_name'], 
        $part['part_number'],
        $part['compatible_engines']
    );
}
echo "\n";

// 3. 실제 API 검색 시뮬레이션
if (!empty($engines)) {
    $engineConditions = [
        "(compatible_engines = '전체' AND category_main IN ('엔진오일(대)', '엔진오일(소)', '오일필터', '오일량'))"
    ];
    
    $params = [];
    foreach ($engines as $index => $engine) {
        $paramKey = ":engine_" . $index;
        $engineConditions[] = "compatible_engines LIKE $paramKey";
        $params[$paramKey] = '%' . $engine . '%';
    }
    
    $sql3 = "SELECT category_main, product_name, part_number, compatible_engines
             FROM genuine_parts
             WHERE (" . implode(' OR ', $engineConditions) . ")
             ORDER BY category_main, product_name";
    
    $stmt3 = $pdo->prepare($sql3);
    foreach ($params as $key => $value) {
        $stmt3->bindValue($key, $value);
    }
    $stmt3->execute();
    $allParts = $stmt3->fetchAll();
    
    echo "=== 전체 검색 결과 (" . count($allParts) . "개) ===\n";
    
    // 카테고리별 분류
    $byCategory = [];
    foreach ($allParts as $part) {
        $cat = $part['category_main'];
        if (!isset($byCategory[$cat])) {
            $byCategory[$cat] = [];
        }
        $byCategory[$cat][] = $part;
    }
    
    foreach ($byCategory as $cat => $items) {
        echo "\n[$cat] (" . count($items) . "개)\n";
        foreach ($items as $item) {
            echo "  - {$item['product_name']} ({$item['part_number']}) - 호환: {$item['compatible_engines']}\n";
        }
    }
}

echo "\n완료!\n";
?>
