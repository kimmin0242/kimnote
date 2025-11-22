<?php
require_once 'config/db.php';

echo "=== G80 RG3 부품 분석 ===\n\n";

// G80 RG3 엔진 타입 확인
$sql = "SELECT DISTINCT ce.engine_type 
        FROM car_engines ce
        JOIN car_models cm ON ce.car_model_id = cm.id
        WHERE cm.model_name = 'G80' 
        AND cm.generation = 'RG3'";
$stmt = $pdo->query($sql);
$engines = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "엔진 타입들:\n";
foreach ($engines as $eng) {
    echo "  - $eng\n";
}
echo "\n";

// 현재 수정된 로직으로 검색
$engineConditions = [
    "(compatible_engines = '전체' AND category_main IN ('엔진오일(대)', '엔진오일(소)', '오일필터', '오일량'))"
];

$params = [];
foreach ($engines as $index => $engine) {
    $paramKey = ":engine_" . $index;
    $engineConditions[] = "compatible_engines LIKE $paramKey";
    $params[$paramKey] = '%' . $engine . '%';
}

$sql = "SELECT category_main, category_sub, product_name, part_number, compatible_engines, capacity
        FROM genuine_parts
        WHERE (" . implode(' OR ', $engineConditions) . ")
        ORDER BY 
            CASE 
                WHEN category_main LIKE '엔진%' THEN 1
                WHEN category_main LIKE '%필터%' THEN 2
                ELSE 3
            END,
            category_main, product_name";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$parts = $stmt->fetchAll();

echo "총 " . count($parts) . "개 부품 검색됨\n\n";

// 카테고리별 그룹화
$byCategory = [];
foreach ($parts as $part) {
    $cat = $part['category_main'];
    if (!isset($byCategory[$cat])) {
        $byCategory[$cat] = [];
    }
    $byCategory[$cat][] = $part;
}

foreach ($byCategory as $cat => $items) {
    echo "[$cat] (" . count($items) . "개)\n";
    foreach ($items as $item) {
        echo sprintf("  - %s (%s)\n", $item['product_name'], $item['part_number']);
        echo sprintf("    용량: %s | 호환: %s\n", 
            $item['capacity'] ?: 'N/A', 
            $item['compatible_engines']
        );
    }
    echo "\n";
}
?>
