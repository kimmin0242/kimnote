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

    echo "<h1>오일량 데이터 정리</h1>";
    
    // 오일량 매핑 데이터 확인
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vehicle_parts_mapping WHERE part_type = '오일량'");
    $result = $stmt->fetch();
    $count = $result['count'];
    
    echo "<p>현재 '오일량' 타입 매핑: <strong>$count 개</strong></p>";
    
    if ($count > 0) {
        echo "<form method='post' style='margin: 20px 0;'>";
        echo "<button type='submit' name='confirm' value='yes' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>";
        echo "🗑️ 오일량 데이터 삭제하기";
        echo "</button>";
        echo "</form>";
        
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
            $stmt = $pdo->prepare("DELETE FROM vehicle_parts_mapping WHERE part_type = '오일량'");
            $stmt->execute();
            
            echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #28a745; border-radius: 5px;'>";
            echo "<h2>✅ 삭제 완료!</h2>";
            echo "<p>$count 개의 '오일량' 매핑 데이터가 삭제되었습니다.</p>";
            echo "<p><a href='/hyundai-parts/'>메인 페이지에서 확인하기</a></p>";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #28a745; border-radius: 5px;'>";
        echo "<h2>✅ 이미 정리되었습니다!</h2>";
        echo "<p>'오일량' 타입 매핑 데이터가 없습니다.</p>";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "<h1 style='color:red'>에러 발생</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
