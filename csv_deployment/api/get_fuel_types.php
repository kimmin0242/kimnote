<?php
/**
 * Get fuel types for a selected model and generation
 */
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $modelName = isset($_GET['model_name']) ? trim($_GET['model_name']) : '';
    $generation = isset($_GET['generation']) ? trim($_GET['generation']) : '';
    
    if (empty($modelName) || empty($generation)) {
        echo json_encode(['success' => false, 'message' => 'Model name and generation are required']);
        exit;
    }
    
    // Extract fuel type from engine_type (first word before space)
    $sql = "SELECT DISTINCT SUBSTRING_INDEX(ce.engine_type, ' ', 1) as fuel_type
            FROM car_engines ce
            JOIN car_models cm ON ce.car_model_id = cm.id
            WHERE cm.model_name = :model_name 
            AND cm.generation = :generation
            ORDER BY fuel_type";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':model_name', $modelName);
    $stmt->bindValue(':generation', $generation);
    $stmt->execute();
    
    $fuelTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'fuel_types' => $fuelTypes
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '연료 타입 조회 중 오류가 발생했습니다.',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
