<?php
require_once 'config/db.php';

echo "=== Checking Category Distribution ===\n\n";

// Get all distinct category_main values
$stmt = $pdo->query("
    SELECT 
        category_main,
        COUNT(*) as count
    FROM genuine_parts
    GROUP BY category_main
    ORDER BY count DESC
");

echo "Category Main 분포:\n";
echo str_repeat("-", 50) . "\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cat = $row['category_main'] ?: '(비어있음)';
    printf("%-30s : %3d개\n", $cat, $row['count']);
}

echo "\n\n=== Sample Parts by Category ===\n\n";

// Sample parts from each category
$stmt = $pdo->query("
    SELECT 
        category_main,
        category_sub,
        product_name,
        part_number
    FROM genuine_parts
    LIMIT 20
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Category: {$row['category_main']} - {$row['category_sub']}\n";
    echo "Product: {$row['product_name']} ({$row['part_number']})\n";
    echo str_repeat("-", 50) . "\n";
}
?>
