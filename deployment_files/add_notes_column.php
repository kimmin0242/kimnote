<?php
header('Content-Type: text/html; charset=UTF-8');

$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Kdmdtt1225**';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<h2>vehicle_parts_mapping 테이블에 notes 컬럼 추가</h2>";
    
    // 컬럼 존재 여부 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM vehicle_parts_mapping LIKE 'notes'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<p style='color: orange;'>✓ notes 컬럼이 이미 존재합니다.</p>";
    } else {
        // notes 컬럼 추가
        $pdo->exec("ALTER TABLE vehicle_parts_mapping ADD COLUMN notes TEXT NULL AFTER quantity");
        echo "<p style='color: green;'>✅ notes 컬럼이 성공적으로 추가되었습니다.</p>";
    }
    
    // 테이블 구조 확인
    echo "<h3>업데이트된 테이블 구조:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM vehicle_parts_mapping");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><p style='color: blue;'>✅ 완료! 이제 부품 매핑 시 비고를 입력할 수 있습니다.</p>";
    echo "<p><a href='admin_vehicle_parts.php'>부품 매핑 페이지로 이동</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
