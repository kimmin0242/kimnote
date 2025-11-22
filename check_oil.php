<?php
require_once 'config/db.php';

echo "=== Checking Engine Oil Parts ===\n\n";

$stmt = $pdo->query("SELECT id, part_number, product_name, compatible_engines, category_main FROM genuine_parts WHERE product_name LIKE '%엔진오일%'");
$parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($parts) . " engine oil parts:\n\n";
foreach ($parts as $part) {
    echo "ID: {$part['id']}\n";
    echo "Part Number: {$part['part_number']}\n";
    echo "Product Name: {$part['product_name']}\n";
    echo "Compatible Engines: {$part['compatible_engines']}\n";
    echo "Category: {$part['category_main']}\n";
    echo "---\n\n";
}

// Also check what engines are available for G80 RG3
echo "\n=== G80 RG3 Engines ===\n\n";
$stmt = $pdo->query("
    SELECT ce.engine_type 
    FROM car_engines ce
    JOIN car_models cm ON ce.car_model_id = cm.id
    WHERE cm.model_name = 'G80' AND cm.generation = 'RG3 (3세대)'
");
$engines = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($engines as $engine) {
    echo "- {$engine['engine_type']}\n";
}
?>
