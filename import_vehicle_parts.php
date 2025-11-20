<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    echo "<h1>차량별 부품 매핑 임포트</h1>";
    
    // CSV 데이터 (위 엑셀을 복사-붙여넣기)
    $csvFile = '/volume1/web/hyundai-parts/uploads/vehicle_parts.csv';
    
    if (!file_exists($csvFile)) {
        echo "<div style='background:#fff3cd; padding:20px; border:1px solid #ffc107; border-radius:5px;'>";
        echo "<h3>📝 사용 방법:</h3>";
        echo "<ol>";
        echo "<li>엑셀 데이터를 CSV로 저장 (다른 이름으로 저장 → CSV UTF-8)</li>";
        echo "<li>파일명을 <code>vehicle_parts.csv</code>로 변경</li>";
        echo "<li><code>/volume1/web/hyundai-parts/uploads/</code> 폴더에 업로드</li>";
        echo "<li>이 페이지를 새로고침</li>";
        echo "</ol>";
        echo "<p><strong>또는</strong> 아래 폼에 엑셀 데이터를 붙여넣고 임포트하세요:</p>";
        echo "</div>";
        
        // 수동 입력 폼
        ?>
        <form method="post" style="margin-top:20px;">
            <h3>엑셀 데이터 붙여넣기 (탭으로 구분됨)</h3>
            <textarea name="excel_data" rows="20" style="width:100%; font-family:monospace; font-size:12px;" placeholder="엑셀에서 복사해서 여기에 붙여넣으세요..."></textarea>
            <br><br>
            <button type="submit" style="padding:10px 20px; background:#007bff; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">
                📥 데이터 임포트 시작
            </button>
        </form>
        <?php
        
        // POST 데이터 처리
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['excel_data'])) {
            processExcelData($_POST['excel_data'], $pdo);
        }
        
    } else {
        // CSV 파일 처리
        $csvData = file_get_contents($csvFile);
        processExcelData($csvData, $pdo);
    }

} catch (PDOException $e) {
    echo "<h1 style='color:red'>데이터베이스 에러</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}

function processExcelData($data, $pdo) {
    $lines = explode("\n", $data);
    
    // 첫 번째 줄은 헤더 (건너뛰기)
    $header = array_shift($lines);
    
    echo "<h2>임포트 진행 상황</h2>";
    echo "<div style='background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:20px;'>";
    
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    
    $pdo->beginTransaction();
    
    foreach ($lines as $lineNum => $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // 탭으로 분리
        $cols = explode("\t", $line);
        
        if (count($cols) < 25) {
            echo "<p style='color:orange;'>⚠️ 줄 " . ($lineNum + 2) . ": 컬럼 수 부족 (" . count($cols) . "개) - 건너뜀</p>";
            $skipCount++;
            continue;
        }
        
        // 데이터 추출
        $manufacturer = trim($cols[0]);  // 제조사
        $category = trim($cols[1]);      // 대분류
        $brand = trim($cols[2]);         // 소분류
        $modelName = trim($cols[3]);     // 모델명
        $generation = trim($cols[4]);    // 상세 트림/세대
        $fuelType = trim($cols[5]);      // 동력원 유형
        $engineType = trim($cols[6]);    // 세부 엔진/동력계
        
        // 부품 데이터
        $parts = [
            ['type' => '엔진오일(대)', 'number' => trim($cols[7]), 'capacity' => trim($cols[8])],
            ['type' => '엔진오일(소)', 'number' => trim($cols[9]), 'capacity' => trim($cols[10])],
            ['type' => '오일량', 'number' => trim($cols[11]), 'capacity' => ''],
            ['type' => '오일필터', 'number' => trim($cols[12]), 'capacity' => ''],
            ['type' => '에어필터', 'number' => trim($cols[13]), 'capacity' => ''],
            ['type' => '에어필터(2)', 'number' => trim($cols[14]), 'capacity' => ''],
            ['type' => '에어컨필터(실내)', 'number' => trim($cols[15]), 'capacity' => ''],
            ['type' => '에어컨필터(외기)', 'number' => trim($cols[16]), 'capacity' => ''],
            ['type' => '와이퍼(좌)', 'number' => trim($cols[17]), 'capacity' => ''],
            ['type' => '와이퍼(우)', 'number' => trim($cols[18]), 'capacity' => ''],
            ['type' => '브레이크 패드(앞축)', 'number' => trim($cols[19]), 'capacity' => ''],
            ['type' => '브레이크 패드(뒤축)', 'number' => trim($cols[20]), 'capacity' => ''],
            ['type' => '브레이크 디스크', 'number' => trim($cols[21]), 'capacity' => ''],
            ['type' => '미션오일', 'number' => trim($cols[22]), 'capacity' => ''],
            ['type' => '브레이크오일', 'number' => trim($cols[23]), 'capacity' => ''],
            ['type' => '디퍼런션오일', 'number' => trim($cols[24]), 'capacity' => ''],
        ];
        
        // "/" 또는 빈 값인 부품은 건너뛰기
        $validParts = array_filter($parts, function($part) {
            return !empty($part['number']) && $part['number'] !== '/' && $part['number'] !== '-';
        });
        
        if (empty($validParts)) {
            echo "<p style='color:#6c757d;'>⏭️ 줄 " . ($lineNum + 2) . ": $modelName $generation $engineType - 유효한 부품 데이터 없음, 건너뜀</p>";
            $skipCount++;
            continue;
        }
        
        try {
            // 1. car_model 찾기 또는 생성
            $stmt = $pdo->prepare("SELECT id FROM car_models WHERE model_name = ? AND generation = ? LIMIT 1");
            $stmt->execute([$modelName, $generation]);
            $model = $stmt->fetch();
            
            if (!$model) {
                // 모델 생성
                $stmt = $pdo->prepare("INSERT INTO car_models (manufacturer, category, brand_name, model_name, generation) 
                                       VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$manufacturer, $category, $brand, $modelName, $generation]);
                $modelId = $pdo->lastInsertId();
                echo "<p style='color:#28a745;'>✅ 모델 생성: $modelName $generation (ID: $modelId)</p>";
            } else {
                $modelId = $model['id'];
            }
            
            // 2. car_engine 찾기 또는 생성
            $stmt = $pdo->prepare("SELECT id FROM car_engines WHERE car_model_id = ? AND engine_type LIKE ? LIMIT 1");
            $stmt->execute([$modelId, '%' . $fuelType . '%']);
            $engine = $stmt->fetch();
            
            if (!$engine) {
                $fullEngineType = $fuelType . ' ' . $engineType;
                $stmt = $pdo->prepare("INSERT INTO car_engines (car_model_id, engine_type, engine_name) 
                                       VALUES (?, ?, ?)");
                $stmt->execute([$modelId, $fullEngineType, $engineType]);
                $engineId = $pdo->lastInsertId();
                echo "<p style='color:#28a745;'>✅ 엔진 생성: $fullEngineType (ID: $engineId)</p>";
            } else {
                $engineId = $engine['id'];
            }
            
            // 3. 부품 매핑
            $partCount = 0;
            foreach ($validParts as $partInfo) {
                // genuine_parts에서 부품 찾기
                $stmt = $pdo->prepare("SELECT id FROM genuine_parts WHERE part_number = ? LIMIT 1");
                $stmt->execute([$partInfo['number']]);
                $part = $stmt->fetch();
                
                if (!$part) {
                    echo "<p style='color:#ffc107;'>⚠️ 부품 없음: {$partInfo['number']} ({$partInfo['type']}) - 건너뜀</p>";
                    continue;
                }
                
                $partId = $part['id'];
                
                // 이미 매핑되어 있는지 확인
                $stmt = $pdo->prepare("SELECT id FROM vehicle_parts_mapping 
                                       WHERE car_engine_id = ? AND part_id = ? AND part_type = ?");
                $stmt->execute([$engineId, $partId, $partInfo['type']]);
                
                if ($stmt->fetch()) {
                    // 이미 존재하면 건너뛰기
                    continue;
                }
                
                // 매핑 추가
                $quantity = $partInfo['capacity'] ?: '1개';
                $stmt = $pdo->prepare("INSERT INTO vehicle_parts_mapping 
                                       (car_engine_id, part_id, part_type, quantity, position) 
                                       VALUES (?, ?, ?, ?, '')");
                $stmt->execute([$engineId, $partId, $partInfo['type'], $quantity]);
                $partCount++;
            }
            
            echo "<p style='color:#007bff;'>📦 <strong>$modelName $generation - $engineType</strong>: $partCount 개 부품 매핑 완료</p>";
            $successCount++;
            
        } catch (PDOException $e) {
            echo "<p style='color:red;'>❌ 줄 " . ($lineNum + 2) . " 에러: " . $e->getMessage() . "</p>";
            $errorCount++;
        }
    }
    
    $pdo->commit();
    
    echo "</div>";
    
    echo "<div style='background:#d4edda; padding:20px; border:1px solid #28a745; border-radius:5px; margin-top:20px;'>";
    echo "<h2>✅ 임포트 완료!</h2>";
    echo "<ul>";
    echo "<li><strong>성공:</strong> $successCount 개 차량</li>";
    echo "<li><strong>건너뜀:</strong> $skipCount 개 (부품 데이터 없음)</li>";
    echo "<li><strong>에러:</strong> $errorCount 개</li>";
    echo "</ul>";
    echo "<p><a href='/hyundai-parts/' style='color:#007bff; text-decoration:none; font-weight:bold;'>🏠 메인 페이지로 이동</a></p>";
    echo "</div>";
}
?>
