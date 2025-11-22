<?php
header('Content-Type: text/html; charset=UTF-8');

$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Hyundai@2025';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "<h2>엔진 데이터 정리</h2>";
    
    if (isset($_POST['cleanup'])) {
        echo "<h3>정리 시작...</h3>";
        $pdo->beginTransaction();
        
        try {
            // 모든 엔진 가져오기
            $stmt = $pdo->query("SELECT * FROM car_engines");
            $engines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updated = 0;
            foreach ($engines as $engine) {
                $engineType = $engine['engine_type'];
                $fuelType = $engine['fuel_type'];
                $newFuelType = $fuelType;
                $newEngineType = $engineType;
                
                // engine_type에서 연료 타입 추출 및 제거
                if (stripos($engineType, '디젤') !== false) {
                    $newFuelType = '디젤';
                    // "디젤" 단어 제거
                    $newEngineType = preg_replace('/디젤\s*/ui', '', $engineType);
                } elseif (stripos($engineType, '가솔린') !== false) {
                    $newFuelType = '가솔린';
                    // "가솔린" 단어 제거
                    $newEngineType = preg_replace('/가솔린\s*/ui', '', $engineType);
                } elseif (stripos($engineType, '전기') !== false || stripos($engineType, 'electric') !== false) {
                    $newFuelType = '전기';
                    $newEngineType = preg_replace('/(전기|electric)\s*/ui', '', $engineType);
                } elseif (stripos($engineType, '하이브리드') !== false || stripos($engineType, 'hybrid') !== false) {
                    $newFuelType = '하이브리드';
                    $newEngineType = preg_replace('/(하이브리드|hybrid)\s*/ui', '', $engineType);
                }
                
                // 앞뒤 공백 제거
                $newEngineType = trim($newEngineType);
                
                // 변경사항이 있으면 업데이트
                if ($newFuelType !== $fuelType || $newEngineType !== $engineType) {
                    $updateStmt = $pdo->prepare("UPDATE car_engines SET fuel_type = ?, engine_type = ? WHERE id = ?");
                    $updateStmt->execute([$newFuelType, $newEngineType, $engine['id']]);
                    
                    echo "<div style='padding: 10px; margin: 5px; background: #fff3cd; border-left: 3px solid #ffc107;'>";
                    echo "<strong>ID {$engine['id']}:</strong><br>";
                    echo "연료: <del>{$fuelType}</del> → <strong style='color: green;'>{$newFuelType}</strong><br>";
                    echo "엔진: <del>{$engineType}</del> → <strong style='color: green;'>{$newEngineType}</strong>";
                    echo "</div>";
                    
                    $updated++;
                }
            }
            
            $pdo->commit();
            
            echo "<hr>";
            echo "<h3 style='color: green;'>✅ 완료! {$updated}개 엔진 정보가 정리되었습니다.</h3>";
            echo "<p><a href='admin_vehicle_manager.php' class='btn btn-primary'>차량 관리 페이지로 이동</a></p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p style='color: red;'>에러 발생: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<h3>현재 문제가 있는 엔진 데이터:</h3>";
        echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0;'>";
        
        $stmt = $pdo->query("SELECT ce.*, cm.model_name, cm.generation 
                             FROM car_engines ce 
                             LEFT JOIN car_models cm ON ce.car_model_id = cm.id 
                             ORDER BY cm.model_name, ce.engine_type");
        
        $issues = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $engineType = $row['engine_type'];
            $hasIssue = false;
            
            // 문제 검사
            if (stripos($engineType, '가솔린') !== false && $row['fuel_type'] === '가솔린') {
                $hasIssue = true;
            } elseif (stripos($engineType, '디젤') !== false) {
                $hasIssue = true;
            } elseif (stripos($engineType, '전기') !== false) {
                $hasIssue = true;
            }
            
            if ($hasIssue) {
                echo "<div style='padding: 8px; margin: 5px 0; background: #fff; border: 1px solid #dee2e6;'>";
                echo "<strong>{$row['model_name']} {$row['generation']}</strong><br>";
                echo "연료: <span style='color: red;'>[{$row['fuel_type']}]</span> ";
                echo "엔진: <span style='color: orange;'>{$engineType}</span>";
                echo "</div>";
                $issues++;
            }
        }
        
        echo "</div>";
        
        if ($issues > 0) {
            echo "<p><strong style='color: orange;'>⚠️ {$issues}개의 중복/불필요한 연료 표기가 발견되었습니다.</strong></p>";
            echo "<form method='post'>";
            echo "<button type='submit' name='cleanup' style='padding: 15px 30px; font-size: 16px; background: #0d6efd; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
            echo "🔧 자동으로 정리하기";
            echo "</button>";
            echo "</form>";
            echo "<p><small>정리 내용:</small></p>";
            echo "<ul>";
            echo "<li>engine_type에서 '가솔린', '디젤', '전기', '하이브리드' 단어 제거</li>";
            echo "<li>fuel_type을 올바른 값으로 설정</li>";
            echo "<li>예: [가솔린] 디젤 2.2 디젤 → fuel_type: 디젤, engine_type: 2.2</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: green;'>✅ 모든 데이터가 정상입니다!</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>에러: " . $e->getMessage() . "</p>";
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>엔진 데이터 정리</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <style>
        .btn { padding: 10px 20px; text-decoration: none; display: inline-block; margin: 5px; }
        .btn-primary { background: #0d6efd; color: white; border-radius: 5px; }
    </style>
</body>
</html>
