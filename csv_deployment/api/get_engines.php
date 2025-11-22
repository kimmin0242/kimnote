<?php
/**
 * 엔진 목록 조회 API (Updated for advanced search)
 */
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Support both old (model_id) and new (model_name + generation) parameters
    $modelId = isset($_GET['model_id']) ? intval($_GET['model_id']) : 0;
    $modelName = isset($_GET['model_name']) ? trim($_GET['model_name']) : '';
    $generation = isset($_GET['generation']) ? trim($_GET['generation']) : '';
    $fuelType = isset($_GET['fuel_type']) ? trim($_GET['fuel_type']) : '';
    
    // Build query based on provided parameters
    if ($modelName && $generation) {
        // New advanced search method
        $sql = "SELECT 
                    ce.id,
                    ce.car_model_id,
                    ce.engine_type,
                    ce.engine_name
                FROM car_engines ce
                JOIN car_models cm ON ce.car_model_id = cm.id
                WHERE cm.model_name = :model_name 
                AND cm.generation = :generation";
        
        $params = [
            ':model_name' => $modelName,
            ':generation' => $generation
        ];
        
        // Optional fuel type filter
        if ($fuelType) {
            $sql .= " AND ce.engine_type LIKE :fuel_type";
            $params[':fuel_type'] = $fuelType . '%';
        }
        
        $sql .= " ORDER BY ce.engine_type";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
    } else if ($modelId > 0) {
        // Old method for backward compatibility
        $sql = "SELECT 
                    id,
                    car_model_id,
                    engine_type,
                    engine_name
                FROM car_engines
                WHERE car_model_id = :model_id
                ORDER BY engine_type";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':model_id', $modelId, PDO::PARAM_INT);
        
    } else {
        echo json_encode([]);
        exit;
    }
    
    $stmt->execute();
    $engines = $stmt->fetchAll();
    
    echo json_encode($engines, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => '엔진 조회 중 오류가 발생했습니다.',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
