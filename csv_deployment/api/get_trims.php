<?php
/**
 * Get trims (generation) for a selected model
 */
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $modelName = isset($_GET['model_name']) ? trim($_GET['model_name']) : '';
    
    if (empty($modelName)) {
        echo json_encode(['success' => false, 'message' => 'Model name is required']);
        exit;
    }
    
    $sql = "SELECT DISTINCT generation, category, manufacturer 
            FROM car_models 
            WHERE model_name = :model_name 
            ORDER BY generation";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':model_name', $modelName);
    $stmt->execute();
    
    $trims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'trims' => $trims
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '트림 목록 조회 중 오류가 발생했습니다.',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
