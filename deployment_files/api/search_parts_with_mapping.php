<?php
/**
 * Advanced parts search API - Version 3 with vehicle_parts_mapping
 * 
 * This API uses the new database structure with proper vehicle-part mapping
 * 
 * 5-level search system:
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
    
    // Check if new mapping table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'vehicle_parts_mapping'")->fetchAll();
    $useMappingTable = !empty($tables);
    
    if ($useMappingTable) {
        // ★★★ NEW APPROACH: Use vehicle_parts_mapping table ★★★
        
        // Build the query to get parts based on vehicle-part mapping
        $sql = "SELECT 
                    gp.id,
                    gp.category_main,
                    gp.category_sub,
                    gp.product_name,
                    gp.capacity,
                    gp.part_number,
                    gp.compatible_engines,
                    gp.notes,
                    ce.engine_type as engine_type_detail,
                    vpm.part_type,
                    vpm.quantity,
                    vpm.position,
                    vpm.replacement_cycle,
                    vpm.notes as mapping_notes,
                    cm.model_name,
                    cm.generation,
                    ce.fuel_type,
                    gp.created_at
                FROM vehicle_parts_mapping vpm
                JOIN car_engines ce ON vpm.car_engine_id = ce.id
                JOIN car_models cm ON ce.car_model_id = cm.id
                JOIN genuine_parts gp ON vpm.part_id = gp.id
                WHERE cm.model_name = :model_name 
                AND cm.generation LIKE :generation";
        
        $params = [
            ':model_name' => $modelName,
            ':generation' => '%' . $generation . '%'
        ];
        
        // Apply optional fuel type filter
        if ($fuelType) {
            $sql .= " AND ce.fuel_type = :fuel_type";
            $params[':fuel_type'] = $fuelType;
        }
        
        // Apply optional engine type filter
        if ($engineType) {
            $sql .= " AND ce.engine_type LIKE :engine_type";
            $params[':engine_type'] = '%' . $engineType . '%';
        }
        
        // Apply optional part name filter
        if ($partName) {
            $sql .= " AND (gp.product_name LIKE :part_name 
                        OR gp.part_number LIKE :part_name 
                        OR gp.category_main LIKE :part_name
                        OR gp.category_sub LIKE :part_name
                        OR vpm.part_type LIKE :part_name)";
            $params[':part_name'] = "%{$partName}%";
        }
        
        // Order by category and position
        $sql .= " ORDER BY 
                    CASE gp.category_main
                        WHEN '엔진오일(대)' THEN 1
                        WHEN '엔진오일(소)' THEN 2
                        WHEN '오일필터' THEN 3
                        WHEN '오일량' THEN 4
                        WHEN '에어필터' THEN 5
                        WHEN '에어컨필터' THEN 6
                        WHEN '와이퍼' THEN 7
                        WHEN '브레이크 패드' THEN 8
                        ELSE 99
                    END,
                    vpm.position,
                    gp.product_name";
        
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $parts = $stmt->fetchAll();
        
        // Get vehicle engines for info
        $engineSql = "SELECT DISTINCT ce.engine_type 
                      FROM car_engines ce
                      JOIN car_models cm ON ce.car_model_id = cm.id
                      WHERE cm.model_name = :model_name 
                      AND cm.generation LIKE :generation";
        
        if ($fuelType) {
            $engineSql .= " AND ce.fuel_type = :fuel_type";
        }
        if ($engineType) {
            $engineSql .= " AND ce.engine_type LIKE :engine_type";
        }
        
        $engineStmt = $pdo->prepare($engineSql);
        $engineStmt->execute($params);
        $vehicleEngines = $engineStmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode([
            'success' => true,
            'parts' => $parts,
            'vehicle_engines' => $vehicleEngines,
            'count' => count($parts),
            'using_mapping_table' => true,
            'message' => 'Using new vehicle_parts_mapping table'
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // ★★★ FALLBACK: Use old method with compatible_engines ★★★
        
        // First, get all engine types for this vehicle
        $engineSql = "SELECT DISTINCT ce.engine_type 
                      FROM car_engines ce
                      JOIN car_models cm ON ce.car_model_id = cm.id
                      WHERE cm.model_name = :model_name 
                      AND cm.generation LIKE :generation";
        
        $engineParams = [
            ':model_name' => $modelName,
            ':generation' => '%' . $generation . '%'
        ];
        
        if ($fuelType) {
            $engineSql .= " AND ce.fuel_type = :fuel_type";
            $engineParams[':fuel_type'] = $fuelType;
        }
        
        if ($engineType) {
            $engineSql .= " AND ce.engine_type LIKE :engine_type";
            $engineParams[':engine_type'] = '%' . $engineType . '%';
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
                'message' => '해당 차량에 대한 엔진 정보가 없습니다.',
                'using_mapping_table' => false
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Search for parts using old method
        $sql = "SELECT 
                    id,
                    category_main,
                    category_sub,
                    product_name,
                    capacity,
                    part_number,
                    compatible_engines,
                    created_at
                FROM genuine_parts
                WHERE 1=1";
        
        $params = [];
        
        $engineConditions = [
            "(compatible_engines = '전체' AND category_main IN ('엔진오일(대)', '엔진오일(소)', '오일필터', '오일량'))"
        ];
        
        foreach ($vehicleEngines as $index => $engine) {
            $paramKey = ":engine_" . $index;
            $engineConditions[] = "compatible_engines LIKE $paramKey";
            $params[$paramKey] = '%' . $engine . '%';
        }
        
        if (!empty($engineConditions)) {
            $sql .= " AND (" . implode(' OR ', $engineConditions) . ")";
        }
        
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
            'count' => count($parts),
            'using_mapping_table' => false,
            'message' => 'Using old compatible_engines method. Please create vehicle_parts_mapping table for accurate results.'
        ], JSON_UNESCAPED_UNICODE);
    }
    
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
