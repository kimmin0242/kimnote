<?php
/**
 * 차량 모델 목록 조회 API
 */
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 제조사별 차량 모델 조회
    $manufacturer = isset($_GET['manufacturer']) ? trim($_GET['manufacturer']) : '';
    
    // Return DISTINCT model_name list for advanced search
    $sql = "SELECT DISTINCT model_name
            FROM car_models";
    
    if ($manufacturer) {
        $sql .= " WHERE manufacturer = :manufacturer";
    }
    
    $sql .= " ORDER BY model_name";
    
    $stmt = $pdo->prepare($sql);
    
    if ($manufacturer) {
        $stmt->bindParam(':manufacturer', $manufacturer);
    }
    
    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'models' => $models
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => '모델 조회 중 오류가 발생했습니다.',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
