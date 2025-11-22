<?php
header('Content-Type: text/html; charset=UTF-8');

$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Hyundai@2025';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "<h3>1. 현재 car_engines 테이블 구조 확인</h3>";
    echo "<pre>";
    $stmt = $pdo->query("DESCRIBE car_engines");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['Field']} - {$col['Type']} - {$col['Null']} - {$col['Key']}\n";
    }
    echo "</pre>";
    
    // fuel_type 컬럼이 있는지 확인
    $hasFuelType = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'fuel_type') {
            $hasFuelType = true;
            break;
        }
    }
    
    if ($hasFuelType) {
        echo "<p style='color: green;'><strong>✅ fuel_type 컬럼이 이미 존재합니다!</strong></p>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ fuel_type 컬럼이 없습니다. 추가하시겠습니까?</strong></p>";
        
        if (isset($_POST['add_column'])) {
            echo "<h3>2. fuel_type 컬럼 추가 중...</h3>";
            
            // 컬럼 추가
            $pdo->exec("ALTER TABLE car_engines ADD COLUMN fuel_type VARCHAR(50) AFTER car_model_id");
            echo "<p style='color: green;'>✅ fuel_type 컬럼 추가 완료!</p>";
            
            // 인덱스 추가
            $pdo->exec("ALTER TABLE car_engines ADD INDEX idx_fuel_type (fuel_type)");
            echo "<p style='color: green;'>✅ 인덱스 추가 완료!</p>";
            
            echo "<h3>3. 기존 데이터에 기본값 설정 (가솔린)</h3>";
            $pdo->exec("UPDATE car_engines SET fuel_type = '가솔린' WHERE fuel_type IS NULL");
            echo "<p style='color: green;'>✅ 기본값 설정 완료!</p>";
            
            echo "<hr>";
            echo "<p><strong>완료!</strong> 이제 <a href='admin_vehicle_manager.php'>차량 관리 페이지</a>로 돌아가세요.</p>";
            echo "<p>각 엔진의 연료 타입을 수정하여 정확한 값으로 변경하세요.</p>";
        } else {
            echo '<form method="post">';
            echo '<button type="submit" name="add_column" class="btn btn-primary" style="padding: 10px 20px; font-size: 16px;">fuel_type 컬럼 추가하기</button>';
            echo '</form>';
            echo '<p><small>기존 데이터는 모두 "가솔린"으로 설정됩니다. 이후 수정하실 수 있습니다.</small></p>';
        }
    }
    
    echo "<h3>현재 등록된 엔진 목록</h3>";
    echo "<pre>";
    $stmt = $pdo->query("SELECT ce.*, cm.model_name, cm.generation 
                         FROM car_engines ce 
                         LEFT JOIN car_models cm ON ce.car_model_id = cm.id 
                         LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['model_name']} {$row['generation']} - ";
        if (isset($row['fuel_type'])) {
            echo "[{$row['fuel_type']}] ";
        }
        echo "{$row['engine_type']}\n";
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>에러: " . $e->getMessage() . "</p>";
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>fuel_type 컬럼 추가</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
</body>
</html>
