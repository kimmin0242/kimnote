<?php
/**
 * 차량 모델 CRUD API
 */
require_once '../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // 단일 모델 조회
                $id = intval($_GET['id']);
                $stmt = $pdo->prepare("SELECT * FROM car_models WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $model = $stmt->fetch();
                
                jsonResponse($model ?: ['error' => true, 'message' => '모델을 찾을 수 없습니다.']);
            } else {
                // 전체 모델 목록
                $stmt = $pdo->query("SELECT * FROM car_models ORDER BY manufacturer, category, model_name");
                $models = $stmt->fetchAll();
                
                jsonResponse($models);
            }
            break;
            
        case 'POST':
            // 생성 또는 수정
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['id']) && $input['id']) {
                // 수정
                $sql = "UPDATE car_models SET 
                        manufacturer = :manufacturer,
                        category = :category,
                        brand_name = :brand_name,
                        model_name = :model_name,
                        generation = :generation
                        WHERE id = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $input['id'],
                    ':manufacturer' => $input['manufacturer'],
                    ':category' => $input['category'],
                    ':brand_name' => $input['brand_name'],
                    ':model_name' => $input['model_name'],
                    ':generation' => $input['generation'] ?? null
                ]);
                
                jsonResponse(['success' => true, 'message' => '수정되었습니다.']);
            } else {
                // 생성
                $sql = "INSERT INTO car_models (manufacturer, category, brand_name, model_name, generation) 
                        VALUES (:manufacturer, :category, :brand_name, :model_name, :generation)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':manufacturer' => $input['manufacturer'],
                    ':category' => $input['category'],
                    ':brand_name' => $input['brand_name'],
                    ':model_name' => $input['model_name'],
                    ':generation' => $input['generation'] ?? null
                ]);
                
                jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
            }
            break;
            
        case 'DELETE':
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id === 0) {
                jsonResponse(['error' => true, 'message' => 'ID가 필요합니다.'], 400);
            }
            
            $stmt = $pdo->prepare("DELETE FROM car_models WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            jsonResponse(['success' => true, 'message' => '삭제되었습니다.']);
            break;
            
        default:
            jsonResponse(['error' => true, 'message' => '지원하지 않는 메소드입니다.'], 405);
    }
    
} catch (PDOException $e) {
    jsonResponse(['error' => true, 'message' => $e->getMessage()], 500);
}
?>
