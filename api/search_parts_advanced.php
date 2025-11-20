<?php
/**
 * Advanced parts search API with proper "전체" handling
 * 
 * This API handles the 5-level search system:
 * 1. 차명 (model_name) - Required
 * 2. 상세트림/세대 (generation) - Required
 * 3. 연료형식 (fuel_type) - Optional
 * 4. 엔진형식 (engine_type) - Optional
 * 5. 부품명 (part_name) - Optional
 */
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $modelName = isset($_GET['model_name']) ? trim($_GET['model_name']) : '';
    $generation = isset($_GET['generation']) ? trim($_GET['generation']) : '';
    $fuelType = isset($_GET['fuel_type']) ? trim($_GET['fuel_type']) : '';
    $engineType = isset($_GET['engine_type']) ? trim($_GET['engine_type']) : '';
    $partName = isset($_GET['part_name']) ? trim($_GET['part_name']) : '';
    
    // Validate required parameters
    if (empty($modelName) || empty($generation)) {
        echo json_encode([
            'success' => false,
            'message' => '차명과 세대는 필수 선택 항목입니다.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // First, get all engine types for this vehicle
    $engineSql = "SELECT DISTINCT ce.engine_type 
                  FROM car_engines ce
                  JOIN car_models cm ON ce.car_model_id = cm.id
                  WHERE cm.model_name = :model_name 
                  AND cm.generation = :generation";
    
    $engineParams = [
        ':model_name' => $modelName,
        ':generation' => $generation
    ];
    
    // Apply optional fuel type filter to vehicle engines
    if ($fuelType) {
        $engineSql .= " AND ce.engine_type LIKE :fuel_type";
        $engineParams[':fuel_type'] = $fuelType . '%';
    }
    
    // Apply optional specific engine type filter
    if ($engineType) {
        $engineSql .= " AND ce.engine_type = :engine_type";
        $engineParams[':engine_type'] = $engineType;
    }
    
    $engineStmt = $pdo->prepare($engineSql);
    foreach ($engineParams as $key => $value) {
        $engineStmt->bindValue($key, $value);
    }
    $engineStmt->execute();
    $vehicleEngines = $engineStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($vehicleEngines)) {
        echo json_encode([
            'success' => true,
            'parts' => [],
            'message' => '해당 차량에 대한 엔진 정보가 없습니다.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Now search for parts
    $sql = "SELECT 
                id,
                category_main,
                category_sub,
                product_name,
                capacity,
                part_number,
                compatible_engines,
                notes,
                created_at
            FROM genuine_parts
            WHERE 1=1";
    
    $params = [];
    
    // **CRITICAL FIX**: Only match engine-specific parts, exclude universal '전체' parts
    // This prevents showing all master catalog parts
    $engineConditions = [];
    
    foreach ($vehicleEngines as $index => $engine) {
        $paramKey = ":engine_" . $index;
        $engineConditions[] = "compatible_engines LIKE $paramKey";
        $params[$paramKey] = '%' . $engine . '%';
    }
    
    if (!empty($engineConditions)) {
        $sql .= " AND (" . implode(' OR ', $engineConditions) . ")";
    }
    
    // Explicitly exclude '전체' parts to prevent showing all universal parts
    $sql .= " AND compatible_engines != '전체'";
    
    // Optional part name filter
    if ($partName) {
        $sql .= " AND (product_name LIKE :part_name 
                    OR part_number LIKE :part_name 
                    OR category_main LIKE :part_name
                    OR category_sub LIKE :part_name)";
        $params[':part_name'] = "%{$partName}%";
    }
    
    $sql .= " ORDER BY category_main, category_sub, product_name";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $parts = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'parts' => $parts,
        'vehicle_engines' => $vehicleEngines,
        'count' => count($parts)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => '부품 검색 중 오류가 발생했습니다.',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
