<?php
/**
 * Excel 데이터를 vehicle_parts_mapping 구조로 임포트
 * 
 * 사용법:
 * 1. 엑셀 데이터를 배열 형태로 준비
 * 2. 이 스크립트 실행: php import_excel_to_mapping.php
 */

require_once 'config/db.php';

echo "=== 엑셀 데이터 임포트 시작 ===\n\n";

// 엑셀 데이터 (배열 형태)
$excelData = [
    [
        'manufacturer' => '현대',
        'brand' => '제너시스',
        'category' => '제너시스 세단',
        'model_name' => 'G80',
        'generation' => 'RG3 (3세대)',
        'fuel_type' => '가솔린',
        'engine_type' => '직렬 4기통 2.5 가솔린 터보 (I4 2.5 T-GDi)',
        'parts' => [
            ['type' => '엔진오일(대)', 'part_number' => '05100-2S400', 'capacity' => '4L', 'quantity' => '1개'],
            ['type' => '엔진오일(소)', 'part_number' => '05100-2S100', 'capacity' => '1L', 'quantity' => '2개'],
            ['type' => '오일량', 'part_number' => null, 'capacity' => '4L+1L 2개', 'quantity' => '총 6L', 'notes' => '엔진오일 총량'],
            ['type' => '오일필터', 'part_number' => '26350 2T000', 'capacity' => '1개', 'quantity' => '1개'],
            ['type' => '에어필터', 'part_number' => '28113 T1210', 'capacity' => '1개', 'quantity' => '1개'],
            ['type' => '에어컨필터', 'part_number' => '97133 T6500', 'capacity' => '1개', 'quantity' => '1개', 'position' => '실내'],
            ['type' => '에어컨필터', 'part_number' => '97133 T6700', 'capacity' => '1개', 'quantity' => '1개', 'position' => '외기'],
            ['type' => '와이퍼', 'part_number' => '98350 0100', 'capacity' => '1개', 'quantity' => '1개', 'position' => '좌'],
            ['type' => '와이퍼', 'part_number' => '98360 0100', 'capacity' => '1개', 'quantity' => '1개', 'position' => '우'],
            ['type' => '브레이크 패드', 'part_number' => '58101 T1A00', 'capacity' => '1세트', 'quantity' => '1세트', 'position' => '앞축'],
        ]
    ],
    [
        'manufacturer' => '현대',
        'brand' => '제너시스',
        'category' => '제너시스 세단',
        'model_name' => 'G80',
        'generation' => 'RG3 (3세대)',
        'fuel_type' => '가솔린',
        'engine_type' => 'V형 6기통 3.5 가솔린 터보 (V6 3.5 T-GDi)',
        'parts' => [
            ['type' => '엔진오일(대)', 'part_number' => '05100-2S400', 'capacity' => '4L', 'quantity' => '1개'],
            ['type' => '엔진오일(소)', 'part_number' => '05100-2S100', 'capacity' => '1L', 'quantity' => '3개'],
            ['type' => '오일량', 'part_number' => null, 'capacity' => '4L+1L 3개', 'quantity' => '총 7L', 'notes' => '엔진오일 총량'],
            ['type' => '오일필터', 'part_number' => '26320 3N000', 'capacity' => '1개', 'quantity' => '1개'],
            ['type' => '에어필터', 'part_number' => '28113 T1310', 'capacity' => '1개', 'quantity' => '2개'],
        ]
    ],
    [
        'manufacturer' => '현대',
        'brand' => '제너시스',
        'category' => '제너시스 세단',
        'model_name' => 'G80',
        'generation' => 'RG3 (3세대)',
        'fuel_type' => '디젤',
        'engine_type' => '직렬 4기통 2.2 디젤 (I4 2.2 e-VGT)',
        'parts' => [
            ['type' => '엔진오일(대)', 'part_number' => '05200-00450', 'capacity' => '4L', 'quantity' => '1개'],
            ['type' => '엔진오일(소)', 'part_number' => '05200-00150', 'capacity' => '1L', 'quantity' => '1개'],
            ['type' => '오일량', 'part_number' => null, 'capacity' => '4L+1L 1개', 'quantity' => '총 5L', 'notes' => '엔진오일 총량'],
            ['type' => '오일필터', 'part_number' => '26320 2R000', 'capacity' => '1개', 'quantity' => '1개'],
            ['type' => '에어필터', 'part_number' => '28113 T1610', 'capacity' => '1개', 'quantity' => '1개'],
        ]
    ],
    // 추가 차량 데이터...
];

try {
    $pdo->beginTransaction();
    
    $importedVehicles = 0;
    $importedParts = 0;
    $importedMappings = 0;
    
    foreach ($excelData as $vehicleData) {
        echo "처리중: {$vehicleData['model_name']} {$vehicleData['generation']} {$vehicleData['fuel_type']} {$vehicleData['engine_type']}\n";
        
        // 1. car_models 입력 또는 업데이트
        $modelSql = "INSERT INTO car_models (manufacturer, brand, category, model_name, generation) 
                     VALUES (:manufacturer, :brand, :category, :model_name, :generation)
                     ON DUPLICATE KEY UPDATE 
                        brand = VALUES(brand),
                        category = VALUES(category),
                        updated_at = CURRENT_TIMESTAMP";
        
        $modelStmt = $pdo->prepare($modelSql);
        $modelStmt->execute([
            ':manufacturer' => $vehicleData['manufacturer'],
            ':brand' => $vehicleData['brand'],
            ':category' => $vehicleData['category'],
            ':model_name' => $vehicleData['model_name'],
            ':generation' => $vehicleData['generation']
        ]);
        
        // car_model_id 가져오기
        $modelId = $pdo->lastInsertId();
        if (!$modelId) {
            // 이미 존재하는 경우
            $stmt = $pdo->prepare("SELECT id FROM car_models WHERE model_name = ? AND generation = ?");
            $stmt->execute([$vehicleData['model_name'], $vehicleData['generation']]);
            $modelId = $stmt->fetchColumn();
        }
        
        // 2. car_engines 입력 또는 업데이트
        $engineSql = "INSERT INTO car_engines (car_model_id, fuel_type, engine_type) 
                      VALUES (:car_model_id, :fuel_type, :engine_type)
                      ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP";
        
        $engineStmt = $pdo->prepare($engineSql);
        $engineStmt->execute([
            ':car_model_id' => $modelId,
            ':fuel_type' => $vehicleData['fuel_type'],
            ':engine_type' => $vehicleData['engine_type']
        ]);
        
        $engineId = $pdo->lastInsertId();
        if (!$engineId) {
            $stmt = $pdo->prepare("SELECT id FROM car_engines WHERE car_model_id = ? AND fuel_type = ? AND engine_type = ?");
            $stmt->execute([$modelId, $vehicleData['fuel_type'], $vehicleData['engine_type']]);
            $engineId = $stmt->fetchColumn();
        }
        
        $importedVehicles++;
        
        // 3. 부품 및 매핑 입력
        foreach ($vehicleData['parts'] as $partData) {
            // 오일량은 별도 처리 (부품번호 없음)
            if ($partData['type'] === '오일량') {
                // 엔진오일(대) 부품 ID 찾기
                $stmt = $pdo->prepare("SELECT id FROM genuine_parts WHERE category_main = '엔진오일(대)' AND part_number = '05100-2S400' LIMIT 1");
                $stmt->execute();
                $partId = $stmt->fetchColumn();
                
                if (!$partId) {
                    echo "  ⚠ 오일량 매핑용 엔진오일(대) 부품을 찾을 수 없습니다.\n";
                    continue;
                }
                
                // 매핑 입력
                $mappingSql = "INSERT INTO vehicle_parts_mapping 
                               (car_engine_id, part_id, part_type, quantity, notes) 
                               VALUES (:car_engine_id, :part_id, :part_type, :quantity, :notes)
                               ON DUPLICATE KEY UPDATE 
                                  quantity = VALUES(quantity),
                                  notes = VALUES(notes),
                                  updated_at = CURRENT_TIMESTAMP";
                
                $mappingStmt = $pdo->prepare($mappingSql);
                $mappingStmt->execute([
                    ':car_engine_id' => $engineId,
                    ':part_id' => $partId,
                    ':part_type' => $partData['type'],
                    ':quantity' => $partData['capacity'],
                    ':notes' => $partData['notes'] ?? $partData['capacity']
                ]);
                
                $importedMappings++;
                echo "  ✓ 오일량 매핑 완료: {$partData['capacity']}\n";
                continue;
            }
            
            // genuine_parts 입력
            $partSql = "INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
                        VALUES (:category_main, :product_name, :part_number, :capacity)
                        ON DUPLICATE KEY UPDATE 
                           category_main = VALUES(category_main),
                           capacity = VALUES(capacity),
                           updated_at = CURRENT_TIMESTAMP";
            
            $productName = $partData['type'];
            if (isset($partData['position'])) {
                $productName .= ' (' . $partData['position'] . ')';
            }
            
            $partStmt = $pdo->prepare($partSql);
            $partStmt->execute([
                ':category_main' => $partData['type'],
                ':product_name' => $productName,
                ':part_number' => $partData['part_number'],
                ':capacity' => $partData['capacity']
            ]);
            
            $partId = $pdo->lastInsertId();
            if (!$partId) {
                $stmt = $pdo->prepare("SELECT id FROM genuine_parts WHERE part_number = ?");
                $stmt->execute([$partData['part_number']]);
                $partId = $stmt->fetchColumn();
            }
            
            $importedParts++;
            
            // vehicle_parts_mapping 입력
            $mappingSql = "INSERT INTO vehicle_parts_mapping 
                           (car_engine_id, part_id, part_type, quantity, position) 
                           VALUES (:car_engine_id, :part_id, :part_type, :quantity, :position)
                           ON DUPLICATE KEY UPDATE 
                              quantity = VALUES(quantity),
                              updated_at = CURRENT_TIMESTAMP";
            
            $mappingStmt = $pdo->prepare($mappingSql);
            $mappingStmt->execute([
                ':car_engine_id' => $engineId,
                ':part_id' => $partId,
                ':part_type' => $partData['type'],
                ':quantity' => $partData['quantity'],
                ':position' => $partData['position'] ?? null
            ]);
            
            $importedMappings++;
            echo "  ✓ 부품 매핑 완료: {$partData['part_number']} - {$productName}\n";
        }
        
        echo "\n";
    }
    
    $pdo->commit();
    
    echo "=== 임포트 완료 ===\n";
    echo "차량: {$importedVehicles}대\n";
    echo "부품: {$importedParts}개\n";
    echo "매핑: {$importedMappings}개\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "오류 발생: " . $e->getMessage() . "\n";
    echo "임포트 롤백됨\n";
}
?>
