<?php
/**
 * 부품 검색 API
 */
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $modelId = isset($_GET['model_id']) ? intval($_GET['model_id']) : 0;
    $engineType = isset($_GET['engine_type']) ? sanitizeInput($_GET['engine_type']) : '';
    $searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    
    // 기본 쿼리
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
    
    // 엔진 타입으로 필터링
    if ($engineType) {
        $sql .= " AND (compatible_engines LIKE :engine_type OR compatible_engines = '전체')";
        $params[':engine_type'] = "%{$engineType}%";
    }
    
    // 검색어로 필터링 (부품명 또는 부품번호)
    if ($searchTerm) {
        $sql .= " AND (product_name LIKE :search 
                    OR part_number LIKE :search 
                    OR category_main LIKE :search
                    OR category_sub LIKE :search)";
        $params[':search'] = "%{$searchTerm}%";
    }
    
    $sql .= " ORDER BY category_main, category_sub, product_name";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $parts = $stmt->fetchAll();
    
    echo json_encode($parts, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => '부품 검색 중 오류가 발생했습니다.',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
