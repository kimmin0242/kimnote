<?php
/**
 * 엔진 CRUD API
 */
require_once '../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // 단일 엔진 조회
                $id = intval($_GET['id']);
                $stmt = $pdo->prepare("SELECT * FROM car_engines WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $engine = $stmt->fetch();
                
                jsonResponse($engine ?: ['error' => true, 'message' => '엔진을 찾을 수 없습니다.']);
            } else {
                // 전체 엔진 목록 (차량 모델 정보 포함)
                $sql = "SELECT e.*, m.model_name 
                        FROM car_engines e
                        LEFT JOIN car_models m ON e.car_model_id = m.id
                        ORDER BY m.model_name, e.engine_type";
                $stmt = $pdo->query($sql);
                $engines = $stmt->fetchAll();
                
                jsonResponse($engines);
            }
            break;
            
        case 'POST':
            // 생성 또는 수정
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['id']) && $input['id']) {
                // 수정
                $sql = "UPDATE car_engines SET 
                        car_model_id = :car_model_id,
                        engine_type = :engine_type,
                        engine_name = :engine_name
                        WHERE id = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $input['id'],
                    ':car_model_id' => $input['car_model_id'],
                    ':engine_type' => $input['engine_type'],
                    ':engine_name' => $input['engine_name'] ?? null
                ]);
                
                jsonResponse(['success' => true, 'message' => '수정되었습니다.']);
            } else {
                // 생성
                $sql = "INSERT INTO car_engines (car_model_id, engine_type, engine_name) 
                        VALUES (:car_model_id, :engine_type, :engine_name)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':car_model_id' => $input['car_model_id'],
                    ':engine_type' => $input['engine_type'],
                    ':engine_name' => $input['engine_name'] ?? null
                ]);
                
                jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
            }
            break;
            
        case 'DELETE':
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id === 0) {
                jsonResponse(['error' => true, 'message' => 'ID가 필요합니다.'], 400);
            }
            
            $stmt = $pdo->prepare("DELETE FROM car_engines WHERE id = :id");
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
