<?php
/**
 * Test script to verify advanced search works correctly
 */
require_once 'config/db.php';

echo "=== Testing Advanced Search for G80 RG3 ===\n\n";

// Test parameters
$modelName = 'G80';
$generation = 'RG3 (3세대)';

echo "Search Parameters:\n";
echo "- Model: $modelName\n";
echo "- Generation: $generation\n\n";

// Step 1: Get vehicle engines
echo "Step 1: Getting vehicle engines...\n";
$engineSql = "SELECT DISTINCT ce.engine_type 
              FROM car_engines ce
              JOIN car_models cm ON ce.car_model_id = cm.id
              WHERE cm.model_name = :model_name 
              AND cm.generation = :generation";

$engineStmt = $pdo->prepare($engineSql);
$engineStmt->bindValue(':model_name', $modelName);
$engineStmt->bindValue(':generation', $generation);
$engineStmt->execute();
$vehicleEngines = $engineStmt->fetchAll(PDO::FETCH_COLUMN);

echo "Found " . count($vehicleEngines) . " engine types:\n";
foreach ($vehicleEngines as $engine) {
    echo "  - $engine\n";
}
echo "\n";

// Step 2: Search for parts
echo "Step 2: Searching for parts...\n";

$sql = "SELECT 
            id,
            category_main,
            category_sub,
            product_name,
            part_number,
            compatible_engines
        FROM genuine_parts
        WHERE 1=1";

$params = [];

// Build engine compatibility condition
$engineConditions = ["compatible_engines = '전체'"];

foreach ($vehicleEngines as $index => $engine) {
    $paramKey = ":engine_" . $index;
    $engineConditions[] = "compatible_engines LIKE $paramKey";
    $params[$paramKey] = '%' . $engine . '%';
}

$sql .= " AND (" . implode(' OR ', $engineConditions) . ")";
$sql .= " ORDER BY category_main, product_name";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($parts) . " parts total\n\n";

// Check for engine oil specifically
echo "Step 3: Checking for engine oil parts...\n";
$engineOilParts = array_filter($parts, function($part) {
    return strpos($part['product_name'], '엔진오일') !== false;
});

if (count($engineOilParts) > 0) {
    echo "✅ SUCCESS: Found " . count($engineOilParts) . " engine oil parts:\n";
    foreach ($engineOilParts as $part) {
        echo "  - {$part['product_name']} (Part#: {$part['part_number']}, Compatible: {$part['compatible_engines']})\n";
    }
} else {
    echo "❌ FAIL: No engine oil parts found!\n";
}
echo "\n";

// Show all parts by category
echo "Step 4: Parts breakdown by category:\n";
$categories = [];
foreach ($parts as $part) {
    $cat = $part['category_main'];
    if (!isset($categories[$cat])) {
        $categories[$cat] = 0;
    }
    $categories[$cat]++;
}

foreach ($categories as $cat => $count) {
    echo "  - $cat: $count parts\n";
}
?>
