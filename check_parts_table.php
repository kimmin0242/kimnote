<?php
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Hyundai@2025';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h1>genuine_parts 테이블 구조 확인</h1>";
    
    // 테이블 컬럼 확인
    $stmt = $pdo->query("DESCRIBE genuine_parts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>테이블 컬럼 목록:</h2>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 샘플 데이터 확인 (처음 3개)
    echo "<h2>샘플 데이터 (처음 3개):</h2>";
    $stmt = $pdo->query("SELECT * FROM genuine_parts LIMIT 3");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($samples);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<h1 style='color:red'>에러 발생</h1>";
    echo "<p><strong>에러 메시지:</strong> " . $e->getMessage() . "</p>";
}
?>
