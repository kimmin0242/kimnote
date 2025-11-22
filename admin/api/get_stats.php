<?php
/**
 * 대시보드 통계 API
 */
require_once '../../config/db.php';

try {
    // 차량 모델 수
    $modelsCount = $pdo->query("SELECT COUNT(*) FROM car_models")->fetchColumn();
    
    // 엔진 수
    $enginesCount = $pdo->query("SELECT COUNT(*) FROM car_engines")->fetchColumn();
    
    // 부품 수
    $partsCount = $pdo->query("SELECT COUNT(*) FROM genuine_parts")->fetchColumn();
    
    jsonResponse([
        'models' => $modelsCount,
        'engines' => $enginesCount,
        'parts' => $partsCount
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => true, 'message' => $e->getMessage()], 500);
}
?>
