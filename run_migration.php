<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>DB 마이그레이션 실행</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #00ff00; }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .info { color: #ffff00; }
        pre { background: #000; padding: 10px; border: 1px solid #333; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; margin: 5px; }
    </style>
</head>
<body>
    <h1>🚀 DB 마이그레이션 도구</h1>
    <p class="info">⚠️ 실행 전에 데이터베이스를 백업하세요!</p>
    
    <form method="post">
        <button type="submit" name="action" value="check">1. 현재 상태 확인</button>
        <button type="submit" name="action" value="create_tables">2. 새 테이블 생성</button>
        <button type="submit" name="action" value="import_sample">3. 샘플 데이터 임포트</button>
        <button type="submit" name="action" value="verify">4. 검증</button>
    </form>
    
    <hr>
    
    <pre><?php

require_once 'config/db.php';

if (!isset($_POST['action'])) {
    echo "버튼을 클릭하여 마이그레이션을 시작하세요.\n";
    exit;
}

$action = $_POST['action'];

try {
    switch ($action) {
        case 'check':
            echo "=== 현재 데이터베이스 상태 ===\n\n";
            
            // 테이블 목록
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "기존 테이블:\n";
            foreach ($tables as $table) {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "  ✓ $table ($count 행)\n";
            }
            
            // vehicle_parts_mapping 존재 확인
            if (in_array('vehicle_parts_mapping', $tables)) {
                echo "\n<span class='success'>✓ vehicle_parts_mapping 테이블이 이미 존재합니다.</span>\n";
            } else {
                echo "\n<span class='error'>✗ vehicle_parts_mapping 테이블이 없습니다. 생성이 필요합니다.</span>\n";
            }
            
            // car_models 테이블 구조 확인
            echo "\n\n=== car_models 테이블 구조 ===\n";
            $columns = $pdo->query("DESCRIBE car_models")->fetchAll();
            foreach ($columns as $col) {
                $null = $col['Null'] == 'NO' ? '필수' : '선택';
                $default = $col['Default'] ? "기본값: {$col['Default']}" : '기본값 없음';
                echo "  - {$col['Field']}: {$col['Type']} ({$null}, {$default})\n";
            }
            
            // car_engines 테이블 구조 확인
            echo "\n=== car_engines 테이블 구조 ===\n";
            $columns = $pdo->query("DESCRIBE car_engines")->fetchAll();
            foreach ($columns as $col) {
                $null = $col['Null'] == 'NO' ? '필수' : '선택';
                echo "  - {$col['Field']}: {$col['Type']} ({$null})\n";
            }
            break;
            
        case 'create_tables':
            echo "=== 새 테이블 생성 중... ===\n\n";
            
            // vehicle_parts_mapping 테이블 생성
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
            echo "<span class='success'>✓ vehicle_parts_mapping 테이블 생성 완료!</span>\n\n";
            
            // 확인
            $tables = $pdo->query("SHOW TABLES LIKE 'vehicle_parts_mapping'")->fetchAll();
            if (!empty($tables)) {
                $columns = $pdo->query("DESCRIBE vehicle_parts_mapping")->fetchAll();
                echo "테이블 구조:\n";
                foreach ($columns as $col) {
                    echo "  - {$col['Field']}: {$col['Type']}\n";
                }
            }
            break;
            
        case 'import_sample':
            echo "=== G80 RG3 샘플 데이터 임포트 중... ===\n\n";
            
            $pdo->beginTransaction();
            
            // 1. 차량 모델 (기존 테이블 구조에 맞춰서 필수 필드 모두 포함)
            // manufacturer 필드가 필수일 경우를 대비
            $checkStmt = $pdo->query("DESCRIBE car_models");
            $columns = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('manufacturer', $columns)) {
                // manufacturer 필드 있음
                $pdo->exec("INSERT INTO car_models (manufacturer, model_name, generation) 
                            VALUES ('현대', 'G80', 'RG3 (3세대)')
                            ON DUPLICATE KEY UPDATE model_name = VALUES(model_name)");
            } else {
                // manufacturer 필드 없음
                $pdo->exec("INSERT INTO car_models (model_name, generation) 
                            VALUES ('G80', 'RG3 (3세대)')
                            ON DUPLICATE KEY UPDATE model_name = VALUES(model_name)");
            }
            
            $modelStmt = $pdo->query("SELECT id FROM car_models WHERE model_name = 'G80' AND generation = 'RG3 (3세대)'");
            $modelId = $modelStmt->fetchColumn();
            echo "✓ 차량 모델 ID: $modelId\n";
            
            // 2. 엔진 (가솔린 2.5 터보)
            $pdo->exec("INSERT INTO car_engines (car_model_id, fuel_type, engine_type, displacement) 
                        VALUES ($modelId, '가솔린', '직렬 4기통 2.5 가솔린 터보 (I4 2.5 T-GDi)', '2.5L')
                        ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
            
            $engineStmt = $pdo->query("SELECT id FROM car_engines WHERE car_model_id = $modelId AND engine_type LIKE '%2.5%'");
            $engineId = $engineStmt->fetchColumn();
            echo "✓ 엔진 ID: $engineId\n\n";
            
            // 3. 부품 및 매핑
            $parts = [
                ['type' => '엔진오일(대)', 'number' => '05100-2S400', 'name' => '엔진오일 4L', 'capacity' => '4L', 'qty' => '1개'],
                ['type' => '엔진오일(소)', 'number' => '05100-2S100', 'name' => '엔진오일 1L', 'capacity' => '1L', 'qty' => '2개'],
                ['type' => '오일필터', 'number' => '26350 2T000', 'name' => '오일필터', 'capacity' => '1개', 'qty' => '1개'],
                ['type' => '에어필터', 'number' => '28113 T1210', 'name' => '에어필터', 'capacity' => '1개', 'qty' => '1개'],
            ];
            
            foreach ($parts as $part) {
                // genuine_parts 입력
                $stmt = $pdo->prepare("INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
                                       VALUES (?, ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
                $stmt->execute([$part['type'], $part['name'], $part['number'], $part['capacity']]);
                
                $partStmt = $pdo->prepare("SELECT id FROM genuine_parts WHERE part_number = ?");
                $partStmt->execute([$part['number']]);
                $partId = $partStmt->fetchColumn();
                
                // vehicle_parts_mapping 입력
                $stmt = $pdo->prepare("INSERT INTO vehicle_parts_mapping 
                                       (car_engine_id, part_id, part_type, quantity) 
                                       VALUES (?, ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)");
                $stmt->execute([$engineId, $partId, $part['type'], $part['qty']]);
                
                echo "✓ {$part['type']}: {$part['number']} - {$part['name']}\n";
            }
            
            $pdo->commit();
            echo "\n<span class='success'>샘플 데이터 임포트 완료!</span>\n";
            break;
            
        case 'verify':
            echo "=== 데이터 검증 ===\n\n";
            
            $sql = "SELECT 
                        cm.model_name,
                        cm.generation,
                        ce.fuel_type,
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
            $results = $stmt->fetchAll();
            
            if (count($results) > 0) {
                echo "<span class='success'>✓ G80 RG3 매핑 데이터 (" . count($results) . "개):</span>\n\n";
                foreach ($results as $row) {
                    echo "  [{$row['category_main']}] {$row['part_number']} - {$row['quantity']}\n";
                }
            } else {
                echo "<span class='error'>✗ 매핑 데이터가 없습니다.</span>\n";
            }
            break;
    }
    
} catch (Exception $e) {
    echo "\n<span class='error'>오류 발생: " . $e->getMessage() . "</span>\n";
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "<span class='error'>트랜잭션 롤백됨</span>\n";
    }
}

?></pre>

</body>
</html>
