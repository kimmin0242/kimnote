<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// 로그인 체크
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

// 데이터베이스 연결
require_once '../config/db.php';

// JSON 입력 받기
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'add_category':
            // 새 카테고리 추가
            $categoryName = trim($input['category_name'] ?? '');
            $firstType = trim($input['first_type'] ?? '');
            
            if (empty($categoryName)) {
                throw new Exception('카테고리 이름을 입력하세요.');
            }
            
            // 카테고리가 이미 존재하는지 확인
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM part_categories WHERE category_name = ?");
            $stmt->execute([$categoryName]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('이미 존재하는 카테고리입니다.');
            }
            
            // 첫 부품 타입 추가 (선택사항)
            if (!empty($firstType)) {
                $stmt = $pdo->prepare("INSERT INTO part_categories (category_name, part_type, display_order) VALUES (?, ?, 1)");
                $stmt->execute([$categoryName, $firstType]);
            }
            
            echo json_encode(['success' => true, 'message' => '카테고리가 추가되었습니다.']);
            break;
            
        case 'add_part_type':
            // 부품 타입 추가
            $categoryName = trim($input['category_name'] ?? '');
            $partType = trim($input['part_type'] ?? '');
            
            if (empty($categoryName) || empty($partType)) {
                throw new Exception('모든 필드를 입력하세요.');
            }
            
            // 중복 확인
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM part_categories WHERE category_name = ? AND part_type = ?");
            $stmt->execute([$categoryName, $partType]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('이미 존재하는 부품 타입입니다.');
            }
            
            // 다음 표시 순서 가져오기
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 FROM part_categories WHERE category_name = ?");
            $stmt->execute([$categoryName]);
            $nextOrder = $stmt->fetchColumn();
            
            // 추가
            $stmt = $pdo->prepare("INSERT INTO part_categories (category_name, part_type, display_order) VALUES (?, ?, ?)");
            $stmt->execute([$categoryName, $partType, $nextOrder]);
            
            echo json_encode(['success' => true, 'message' => '부품 타입이 추가되었습니다.']);
            break;
            
        case 'update_part_type':
            // 부품 타입 수정
            $id = intval($input['id'] ?? 0);
            $categoryName = trim($input['category_name'] ?? '');
            $partType = trim($input['part_type'] ?? '');
            
            if ($id <= 0 || empty($categoryName) || empty($partType)) {
                throw new Exception('유효하지 않은 데이터입니다.');
            }
            
            // 중복 확인 (자기 자신 제외)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM part_categories WHERE category_name = ? AND part_type = ? AND id != ?");
            $stmt->execute([$categoryName, $partType, $id]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('이미 존재하는 부품 타입입니다.');
            }
            
            // 수정
            $stmt = $pdo->prepare("UPDATE part_categories SET category_name = ?, part_type = ? WHERE id = ?");
            $stmt->execute([$categoryName, $partType, $id]);
            
            echo json_encode(['success' => true, 'message' => '수정되었습니다.']);
            break;
            
        case 'toggle_active':
            // 활성화/비활성화 토글
            $id = intval($input['id'] ?? 0);
            $isActive = intval($input['is_active'] ?? 1);
            
            if ($id <= 0) {
                throw new Exception('유효하지 않은 ID입니다.');
            }
            
            $stmt = $pdo->prepare("UPDATE part_categories SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $id]);
            
            echo json_encode(['success' => true, 'message' => '상태가 변경되었습니다.']);
            break;
            
        case 'delete_part_type':
            // 부품 타입 삭제
            $id = intval($input['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('유효하지 않은 ID입니다.');
            }
            
            // 삭제
            $stmt = $pdo->prepare("DELETE FROM part_categories WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => '삭제되었습니다.']);
            break;
            
        case 'get_categories':
            // 카테고리 목록 가져오기 (JSON 형식)
            $stmt = $pdo->query("
                SELECT category_name, part_type, display_order
                FROM part_categories
                WHERE is_active = 1
                ORDER BY category_name, display_order, part_type
            ");
            
            $categories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[$row['category_name']][] = $row['part_type'];
            }
            
            echo json_encode(['success' => true, 'categories' => $categories]);
            break;
            
        default:
            throw new Exception('유효하지 않은 액션입니다.');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
