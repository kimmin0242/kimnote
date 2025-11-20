<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>DB 마이그레이션 실행</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            padding: 20px; 
            background: #1e1e1e; 
            color: #00ff00; 
        }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .info { color: #ffff00; }
        pre { 
            background: #000; 
            padding: 15px; 
            border: 1px solid #333; 
            overflow-x: auto;
        }
        button { 
            padding: 10px 20px; 
            font-size: 16px; 
            cursor: pointer; 
            margin: 5px;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 4px;
        }
        button:hover {
            background: #0052a3;
        }
    </style>
</head>
<body>
    <h1>🚀 DB 마이그레이션 도구</h1>
    <p class="info">⚠️ 실행 전에 데이터베이스를 백업하세요!</p>
    
    <form method="post">
        <button type="submit" name="action" value="check">1. 테이블 구조 확인</button>
        <button type="submit" name="action" value="create">2. 새 테이블 생성</button>
        <button type="submit" name="action" value="import">3. 샘플 데이터 임포트</button>
        <button type="submit" name="action" value="verify">4. 검증</button>
    </form>
    
    <hr>
    
    <pre><?php
require_once 'config/db.php';

if (!isset($_POST['action'])) {
    echo "버튼을 클릭하여 시작하세요.\n";
    exit;
}

$action = $_POST['action'];

try {
    if ($action == 'check') {
        echo "=== car_models 테이블 구조 ===\n\n";
        $result = $pdo->query("DESCRIBE car_models");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            $required = ($col['Null'] == 'NO') ? '[필수]' : '[선택]';
            $default = $col['Default'] ? "기본값: {$col['Default']}" : '기본값 없음';
            echo sprintf("%-20s %-15s %s %s\n", 
                $col['Field'], 
                $col['Type'], 
                $required,
                $default
            );
        }
        
        echo "\n=== car_engines 테이블 구조 ===\n\n";
        $result = $pdo->query("DESCRIBE car_engines");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            $required = ($col['Null'] == 'NO') ? '[필수]' : '[선택]';
            echo sprintf("%-20s %-15s %s\n", 
                $col['Field'], 
                $col['Type'], 
                $required
            );
        }
        
        echo "\n=== 테이블 존재 확인 ===\n\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('vehicle_parts_mapping', $tables)) {
            echo "✓ vehicle_parts_mapping 테이블 존재함\n";
        } else {
            echo "✗ vehicle_parts_mapping 테이블 없음 (생성 필요)\n";
        }
        
    } elseif ($action == 'create') {
        echo "=== vehicle_parts_mapping 테이블 생성 ===\n\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS vehicle_parts_mapping (
            id INT PRIMARY KEY AUTO_INCREMENT,
            car_engine_id INT NOT NULL,
            part_id INT NOT NULL,
            part_type VARCHAR(100) NOT NULL,
            quantity VARCHAR(50),
            position VARCHAR(50),
            is_required BOOLEAN DEFAULT TRUE,
            replacement_cycle VARCHAR(100),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (car_engine_id) REFERENCES car_engines(id) ON DELETE CASCADE,
            FOREIGN KEY (part_id) REFERENCES genuine_parts(id) ON DELETE CASCADE,
            UNIQUE KEY idx_vehicle_part (car_engine_id, part_type, position),
            INDEX idx_part_type (part_type),
            INDEX idx_part_id (part_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✓ 테이블 생성 완료!\n";
        
    } elseif ($action == 'import') {
        echo "=== G80 RG3 샘플 데이터 임포트 ===\n\n";
        
        $pdo->beginTransaction();
        
        // Step 1: 차량 모델
        echo "Step 1: 차량 모델 입력...\n";
        
        $sql = "INSERT INTO car_models (manufacturer, brand_name, category, model_name, generation) 
                VALUES ('현대', '제네시스', '세단', 'G80', 'RG3 (3세대)')
                ON DUPLICATE KEY UPDATE manufacturer = VALUES(manufacturer)";
        
        $pdo->exec($sql);
        
        $stmt = $pdo->query("SELECT id FROM car_models WHERE model_name = 'G80' AND generation LIKE '%RG3%'");
        $modelId = $stmt->fetchColumn();
        
        if (!$modelId) {
            throw new Exception("차량 모델을 찾을 수 없습니다.");
        }
        
        echo "✓ 모델 ID: {$modelId}\n";
        
        // Step 2: 엔진
        echo "\nStep 2: 엔진 정보 입력...\n";
        
        $sql = "INSERT INTO car_engines (car_model_id, fuel_type, engine_type) 
                VALUES ({$modelId}, '가솔린', '직렬 4기통 2.5 가솔린 터보')
                ON DUPLICATE KEY UPDATE fuel_type = VALUES(fuel_type)";
        
        $pdo->exec($sql);
        
        $stmt = $pdo->query("SELECT id FROM car_engines WHERE car_model_id = {$modelId} AND engine_type LIKE '%2.5%'");
        $engineId = $stmt->fetchColumn();
        
        if (!$engineId) {
            throw new Exception("엔진 정보를 찾을 수 없습니다.");
        }
        
        echo "✓ 엔진 ID: {$engineId}\n";
        
        // Step 3: 부품
        echo "\nStep 3: 부품 및 매핑 입력...\n\n";
        
        $parts = array(
            array('type' => '엔진오일(대)', 'number' => '05100-2S400', 'name' => '엔진오일 4L', 'capacity' => '4L', 'qty' => '1개'),
            array('type' => '엔진오일(소)', 'number' => '05100-2S100', 'name' => '엔진오일 1L', 'capacity' => '1L', 'qty' => '2개'),
            array('type' => '오일필터', 'number' => '26350 2T000', 'name' => '오일필터', 'capacity' => '1개', 'qty' => '1개'),
            array('type' => '에어필터', 'number' => '28113 T1210', 'name' => '에어필터', 'capacity' => '1개', 'qty' => '1개')
        );
        
        foreach ($parts as $part) {
            // genuine_parts에 입력
            $stmt = $pdo->prepare("INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
                                   VALUES (?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE category_main = VALUES(category_main)");
            $stmt->execute(array($part['type'], $part['name'], $part['number'], $part['capacity']));
            
            // part_id 조회
            $stmt = $pdo->prepare("SELECT id FROM genuine_parts WHERE part_number = ?");
            $stmt->execute(array($part['number']));
            $partId = $stmt->fetchColumn();
            
            // vehicle_parts_mapping에 입력
            $stmt = $pdo->prepare("INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity) 
                                   VALUES (?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)");
            $stmt->execute(array($engineId, $partId, $part['type'], $part['qty']));
            
            echo "✓ {$part['type']}: {$part['number']}\n";
        }
        
        $pdo->commit();
        echo "\n<span class='success'>✓ 임포트 완료!</span>\n";
        
    } elseif ($action == 'verify') {
        echo "=== 검증 ===\n\n";
        
        $sql = "SELECT 
                    cm.model_name,
                    cm.generation,
                    ce.engine_type,
                    gp.category_main,
                    gp.part_number,
                    vpm.quantity
                FROM vehicle_parts_mapping vpm
                JOIN car_engines ce ON vpm.car_engine_id = ce.id
                JOIN car_models cm ON ce.car_model_id = cm.id
                JOIN genuine_parts gp ON vpm.part_id = gp.id
                WHERE cm.model_name = 'G80' 
                AND cm.generation LIKE '%RG3%'
                ORDER BY gp.category_main";
        
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) > 0) {
            echo "✓ 매핑 데이터: " . count($results) . "개\n\n";
            foreach ($results as $row) {
                echo "[{$row['category_main']}] {$row['part_number']} - {$row['quantity']}\n";
            }
        } else {
            echo "✗ 매핑 데이터 없음\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n<span class='error'>오류: " . $e->getMessage() . "</span>\n";
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "<span class='error'>롤백됨</span>\n";
    }
}
?></pre>

</body>
</html>
