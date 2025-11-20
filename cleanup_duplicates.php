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

    echo "<h1>중복 데이터 정리 작업</h1>";

    // 트랜잭션 시작
    $pdo->beginTransaction();

    // 1. 현재 매핑 데이터 확인
    echo "<h2>1. 현재 vehicle_parts_mapping 데이터</h2>";
    $stmt = $pdo->query("SELECT * FROM vehicle_parts_mapping");
    $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($mappings);
    echo "</pre>";

    // 2. car_engine_id 439를 사용하는 매핑이 있는지 확인
    $stmt = $pdo->query("SELECT * FROM vehicle_parts_mapping WHERE car_engine_id = 439");
    $mappingsWithEngine439 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($mappingsWithEngine439) > 0) {
        echo "<h2>2. car_engine_id 439를 377로 변경</h2>";
        echo "<p>변경할 매핑 개수: " . count($mappingsWithEngine439) . "</p>";
        
        // car_engine_id를 439에서 377로 변경 (기존 엔진 사용)
        $pdo->exec("UPDATE vehicle_parts_mapping SET car_engine_id = 377 WHERE car_engine_id = 439");
        echo "<p style='color:green;'>✅ 매핑 업데이트 완료</p>";
    } else {
        echo "<h2>2. 매핑 변경 필요 없음</h2>";
        echo "<p>car_engine_id 439를 사용하는 매핑이 없습니다.</p>";
    }

    // 3. 중복 엔진 삭제 (ID 439)
    echo "<h2>3. 중복 엔진 삭제 (ID 439)</h2>";
    $pdo->exec("DELETE FROM car_engines WHERE id = 439");
    echo "<p style='color:green;'>✅ 엔진 ID 439 삭제 완료</p>";

    // 4. 중복 모델 삭제 (ID 175)와 관련 엔진들
    echo "<h2>4. 중복 모델 삭제 (ID 175)</h2>";
    
    // ID 175와 연결된 엔진 확인
    $stmt = $pdo->query("SELECT * FROM car_engines WHERE car_model_id = 175");
    $enginesOf175 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($enginesOf175) > 0) {
        echo "<p>모델 ID 175에 연결된 엔진: " . count($enginesOf175) . "개</p>";
        echo "<pre>";
        print_r($enginesOf175);
        echo "</pre>";
        
        // 관련 엔진 삭제 (CASCADE로 자동 삭제될 수도 있음)
        $pdo->exec("DELETE FROM car_engines WHERE car_model_id = 175");
        echo "<p style='color:green;'>✅ 관련 엔진 삭제 완료</p>";
    }
    
    $pdo->exec("DELETE FROM car_models WHERE id = 175");
    echo "<p style='color:green;'>✅ 모델 ID 175 삭제 완료</p>";

    // 5. 최종 확인
    echo "<h2>5. 정리 후 데이터 확인</h2>";
    
    echo "<h3>G80 모델 목록:</h3>";
    $stmt = $pdo->query("SELECT * FROM car_models WHERE model_name = 'G80'");
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($models);
    echo "</pre>";
    
    echo "<h3>G80 엔진 목록:</h3>";
    $stmt = $pdo->query("SELECT ce.*, cm.generation 
                         FROM car_engines ce 
                         JOIN car_models cm ON ce.car_model_id = cm.id 
                         WHERE cm.model_name = 'G80'");
    $engines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($engines);
    echo "</pre>";
    
    echo "<h3>vehicle_parts_mapping:</h3>";
    $stmt = $pdo->query("SELECT * FROM vehicle_parts_mapping");
    $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($mappings);
    echo "</pre>";

    // 트랜잭션 커밋
    $pdo->commit();
    
    echo "<h2 style='color:green;'>✅ 모든 작업이 성공적으로 완료되었습니다!</h2>";
    echo "<p><a href='check_database.php'>데이터베이스 상태 다시 확인하기</a></p>";

} catch (PDOException $e) {
    // 에러 발생 시 롤백
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h1 style='color:red'>에러 발생 - 모든 변경사항이 취소되었습니다</h1>";
    echo "<p><strong>에러 메시지:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
