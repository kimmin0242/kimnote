<?php
/**
 * 부품 CRUD API
 */
require_once '../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // 단일 부품 조회
                $id = intval($_GET['id']);
                $stmt = $pdo->prepare("SELECT * FROM genuine_parts WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $part = $stmt->fetch();
                
                jsonResponse($part ?: ['error' => true, 'message' => '부품을 찾을 수 없습니다.']);
            } else {
                // 전체 부품 목록 또는 검색
                $search = $_GET['search'] ?? '';
                
                $sql = "SELECT * FROM genuine_parts";
                
                if ($search) {
                    $sql .= " WHERE product_name LIKE :search 
                             OR part_number LIKE :search 
                             OR category_main LIKE :search";
                }
                
                $sql .= " ORDER BY category_main, product_name LIMIT 100";
                
                $stmt = $pdo->prepare($sql);
                
                if ($search) {
                    $searchParam = "%{$search}%";
                    $stmt->bindParam(':search', $searchParam);
                }
                
                $stmt->execute();
                $parts = $stmt->fetchAll();
                
                jsonResponse($parts);
            }
            break;
            
        case 'POST':
            // 생성 또는 수정
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['id']) && $input['id']) {
                // 수정
                $sql = "UPDATE genuine_parts SET 
                        category_main = :category_main,
                        category_sub = :category_sub,
                        product_name = :product_name,
                        capacity = :capacity,
                        part_number = :part_number,
                        compatible_engines = :compatible_engines,
                        notes = :notes
                        WHERE id = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $input['id'],
                    ':category_main' => $input['category_main'],
                    ':category_sub' => $input['category_sub'] ?? null,
                    ':product_name' => $input['product_name'],
                    ':capacity' => $input['capacity'] ?? null,
                    ':part_number' => $input['part_number'],
                    ':compatible_engines' => $input['compatible_engines'] ?? null,
                    ':notes' => $input['notes'] ?? null
                ]);
                
                jsonResponse(['success' => true, 'message' => '수정되었습니다.']);
            } else {
                // 생성
                $sql = "INSERT INTO genuine_parts 
                        (category_main, category_sub, product_name, capacity, part_number, compatible_engines, notes) 
                        VALUES (:category_main, :category_sub, :product_name, :capacity, :part_number, :compatible_engines, :notes)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':category_main' => $input['category_main'],
                    ':category_sub' => $input['category_sub'] ?? null,
                    ':product_name' => $input['product_name'],
                    ':capacity' => $input['capacity'] ?? null,
                    ':part_number' => $input['part_number'],
                    ':compatible_engines' => $input['compatible_engines'] ?? null,
                    ':notes' => $input['notes'] ?? null
                ]);
                
                jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
            }
            break;
            
        case 'DELETE':
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id === 0) {
                jsonResponse(['error' => true, 'message' => 'ID가 필요합니다.'], 400);
            }
            
            $stmt = $pdo->prepare("DELETE FROM genuine_parts WHERE id = :id");
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
