<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Hyundai@2025';

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );

    // Get search parameters
    $model_name = $_GET['model_name'] ?? '';
    $generation = $_GET['generation'] ?? '';
    $fuel_type = $_GET['fuel_type'] ?? '';
    $engine_type = $_GET['engine_type'] ?? '';
    $displacement = $_GET['displacement'] ?? '';
    $category_main = $_GET['category_main'] ?? '';
    $category_sub = $_GET['category_sub'] ?? '';
    $search_query = $_GET['search_query'] ?? '';

    // Validate required parameters
    if (empty($model_name)) {
        echo json_encode([
            'success' => false,
            'error' => '차종을 선택해주세요',
            'params_received' => $_GET
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check if new mapping table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'vehicle_parts_mapping'")->fetchAll();
    $useMappingTable = !empty($tables);

    if ($useMappingTable) {
        // === NEW METHOD: Use vehicle_parts_mapping table ===
        
        // Build base query with JOINs
        $sql = "SELECT 
                    gp.*,
                    vpm.quantity,
                    vpm.position,
                    vpm.part_type,
                    ce.engine_type as compatible_engines,
                    ce.engine_name,
                    cm.model_name,
                    cm.generation
                FROM vehicle_parts_mapping vpm
                JOIN car_engines ce ON vpm.car_engine_id = ce.id
                JOIN car_models cm ON ce.car_model_id = cm.id
                JOIN genuine_parts gp ON vpm.part_id = gp.id
                WHERE cm.model_name = :model_name";

        $params = [':model_name' => $model_name];

        // Add generation filter
        if (!empty($generation)) {
            $sql .= " AND cm.generation LIKE :generation";
            $params[':generation'] = '%' . $generation . '%';
        }

        // Add engine type filter (map fuel_type parameter to engine_type column)
        if (!empty($fuel_type)) {
            $sql .= " AND ce.engine_type LIKE :engine_type";
            $params[':engine_type'] = '%' . $fuel_type . '%';
        } elseif (!empty($engine_type)) {
            $sql .= " AND ce.engine_type LIKE :engine_type";
            $params[':engine_type'] = '%' . $engine_type . '%';
        }

        // Add category filters
        if (!empty($category_main)) {
            $sql .= " AND gp.category_main = :category_main";
            $params[':category_main'] = $category_main;
        }

        if (!empty($category_sub)) {
            $sql .= " AND gp.category_sub = :category_sub";
            $params[':category_sub'] = $category_sub;
        }

        // Add search query filter
        if (!empty($search_query)) {
            $sql .= " AND (gp.part_number LIKE :search_query 
                      OR gp.product_name LIKE :search_query
                      OR gp.category_main LIKE :search_query
                      OR gp.category_sub LIKE :search_query)";
            $params[':search_query'] = '%' . $search_query . '%';
        }

        // Add ordering
        $sql .= " ORDER BY 
                    FIELD(vpm.part_type, '엔진오일(대)', '엔진오일(소)', '오일필터', '에어필터', '연료필터', '에어컨필터'),
                    gp.category_main,
                    gp.category_sub,
                    gp.part_number";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $parts = $stmt->fetchAll();

        // Format results
        $formattedParts = [];
        foreach ($parts as $part) {
            $formattedParts[] = [
                'id' => $part['id'],
                'part_number' => $part['part_number'],
                'part_name' => $part['product_name'],  // ← 수정: product_name 사용
                'category_main' => $part['category_main'],
                'category_sub' => $part['category_sub'],
                'compatible_engines' => $part['compatible_engines'],
                'engine_name' => $part['engine_name'] ?? '',
                'capacity' => $part['capacity'] ?? '',
                'quantity' => $part['quantity'] ?? '',
                'position' => $part['position'] ?? '',
                'part_type' => $part['part_type'] ?? '',
                'model_name' => $part['model_name'] ?? '',
                'generation' => $part['generation'] ?? '',
                'price' => 0,  // ← price 컬럼 없음
                'stock_status' => '확인 필요',  // ← stock_status 컬럼 없음
                'manufacturer' => '현대',
                'description' => $part['notes'] ?? ''  // ← 수정: notes 사용
            ];
        }

        echo json_encode([
            'success' => true,
            'parts' => $formattedParts,
            'count' => count($formattedParts),
            'using_mapping_table' => true,
            'search_params' => [
                'model_name' => $model_name,
                'generation' => $generation,
                'fuel_type' => $fuel_type,
                'engine_type' => $engine_type,
                'category_main' => $category_main,
                'category_sub' => $category_sub
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    } else {
        // === OLD METHOD: Fallback to compatible_engines string matching ===
        
        $sql = "SELECT * FROM genuine_parts WHERE 1=1";
        $params = [];

        // Build engine compatibility conditions
        $engineConditions = [];
        
        if (!empty($fuel_type)) {
            $engineConditions[] = "compatible_engines LIKE :fuel_type";
            $params[':fuel_type'] = '%' . $fuel_type . '%';
        }

        if (!empty($engine_type)) {
            $engineConditions[] = "compatible_engines LIKE :engine_type";
            $params[':engine_type'] = '%' . $engine_type . '%';
        }

        if (!empty($displacement)) {
            $engineConditions[] = "compatible_engines LIKE :displacement";
            $params[':displacement'] = '%' . $displacement . '%';
        }

        // Add special handling for "전체" (universal parts)
        // Only include universal parts for engine oil related categories
        if (!empty($engineConditions)) {
            $engineSQL = '(' . implode(' OR ', $engineConditions);
            $engineSQL .= " OR (compatible_engines = '전체' AND category_main IN ('엔진오일(대)', '엔진오일(소)', '오일필터', '오일량')))";
            $sql .= " AND $engineSQL";
        }

        // Add category filters
        if (!empty($category_main)) {
            $sql .= " AND category_main = :category_main";
            $params[':category_main'] = $category_main;
        }

        if (!empty($category_sub)) {
            $sql .= " AND category_sub = :category_sub";
            $params[':category_sub'] = $category_sub;
        }

        // Add search query filter
        if (!empty($search_query)) {
            $sql .= " AND (part_number LIKE :search_query 
                      OR product_name LIKE :search_query
                      OR category_main LIKE :search_query
                      OR category_sub LIKE :search_query)";
            $params[':search_query'] = '%' . $search_query . '%';
        }

        $sql .= " ORDER BY category_main, category_sub, part_number";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $parts = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'parts' => $parts,
            'count' => count($parts),
            'using_mapping_table' => false,
            'warning' => '구형 검색 방식을 사용 중입니다. 더 정확한 검색을 위해 데이터 마이그레이션을 권장합니다.',
            'search_params' => [
                'model_name' => $model_name,
                'generation' => $generation,
                'fuel_type' => $fuel_type,
                'engine_type' => $engine_type,
                'displacement' => $displacement,
                'category_main' => $category_main,
                'category_sub' => $category_sub
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}
?>
