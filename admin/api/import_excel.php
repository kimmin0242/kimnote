<?php
/**
 * 엑셀 파일 업로드 및 Import API
 */
require_once '../../config/db.php';

set_time_limit(300);
ini_set('memory_limit', '256M');

header('Content-Type: application/json; charset=utf-8');

try {
    // 파일 업로드 확인
    if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => true, 'message' => '파일 업로드 실패'], 400);
    }
    
    $uploadedFile = $_FILES['excelFile']['tmp_name'];
    $clearExisting = isset($_POST['clearExisting']) && $_POST['clearExisting'] === '1';
    
    // PhpSpreadsheet 사용 (Composer 필요)
    if (!file_exists('../../vendor/autoload.php')) {
        // Composer가 없는 경우 간단한 파서 사용
        jsonResponse([
            'error' => true, 
            'message' => 'PhpSpreadsheet가 설치되지 않았습니다. Composer로 설치하세요: composer require phpoffice/phpspreadsheet'
        ], 500);
    }
    
    require_once '../../vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\IOFactory;
    
    // 엑셀 파일 로드
    $spreadsheet = IOFactory::load($uploadedFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 기존 데이터 삭제 (옵션)
    if ($clearExisting) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $pdo->exec("TRUNCATE TABLE car_engines");
        $pdo->exec("TRUNCATE TABLE car_models");
        $pdo->exec("TRUNCATE TABLE genuine_parts");
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    }
    
    $insertedModels = 0;
    $insertedEngines = 0;
    $insertedParts = 0;
    
    // 부품 카테고리 매핑 (2행 헤더)
    $partCategories = [];
    for ($col = 8; $col <= 24; $col++) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $categoryName = $worksheet->getCell($colLetter . '2')->getValue();
        if ($categoryName) {
            $partCategories[$col] = trim($categoryName);
        }
    }
    
    $currentModel = null;
    $currentModelId = null;
    
    // 데이터 처리 (3행부터)
    for ($row = 3; $row <= $highestRow; $row++) {
        $manufacturer = $worksheet->getCell('A' . $row)->getValue();
        $category = $worksheet->getCell('B' . $row)->getValue();
        $modelName = $worksheet->getCell('D' . $row)->getValue();
        $generation = $worksheet->getCell('E' . $row)->getValue();
        $powerType = $worksheet->getCell('F' . $row)->getValue();
        $engineDetail = $worksheet->getCell('G' . $row)->getValue();
        
        // 새로운 차량 모델 발견
        if ($modelName && trim($modelName) !== '') {
            $modelKey = trim($modelName) . '|' . trim($generation ?? '');
            
            if ($currentModel !== $modelKey) {
                // 차량 모델 저장
                $sql = "INSERT INTO car_models (manufacturer, category, brand_name, model_name, generation) 
                        VALUES (:manufacturer, :category, :brand_name, :model_name, :generation)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':manufacturer' => trim($manufacturer ?? '현대'),
                    ':category' => trim($category ?? '기타'),
                    ':brand_name' => trim($modelName),
                    ':model_name' => trim($modelName),
                    ':generation' => trim($generation ?? '')
                ]);
                
                $currentModelId = $pdo->lastInsertId();
                $currentModel = $modelKey;
                $insertedModels++;
            }
            
            // 엔진 정보 저장
            if ($engineDetail && $currentModelId) {
                $engineType = trim($powerType) . ' ' . trim($engineDetail);
                
                $sql = "INSERT INTO car_engines (car_model_id, engine_type, engine_name) 
                        VALUES (:car_model_id, :engine_type, :engine_name)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':car_model_id' => $currentModelId,
                    ':engine_type' => $engineType,
                    ':engine_name' => trim($engineDetail)
                ]);
                $insertedEngines++;
            }
            
            // 부품 정보 처리
            for ($col = 8; $col <= 24; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $partNumber = $worksheet->getCell($colLetter . $row)->getValue();
                
                if ($partNumber && isset($partCategories[$col])) {
                    $partNumber = trim($partNumber);
                    
                    // 부품번호 형식 확인 (xxxxx-xxxxx)
                    if (preg_match('/^\d{5}-\d{5}$/', $partNumber)) {
                        // 중복 확인
                        $checkSql = "SELECT id FROM genuine_parts WHERE part_number = :part_number";
                        $checkStmt = $pdo->prepare($checkSql);
                        $checkStmt->execute([':part_number' => $partNumber]);
                        
                        if (!$checkStmt->fetch()) {
                            $sql = "INSERT INTO genuine_parts 
                                    (category_main, product_name, part_number, compatible_engines) 
                                    VALUES (:category_main, :product_name, :part_number, :compatible_engines)";
                            
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                ':category_main' => $partCategories[$col],
                                ':product_name' => $partCategories[$col] . ' (' . $partNumber . ')',
                                ':part_number' => $partNumber,
                                ':compatible_engines' => isset($engineType) ? $engineType : '전체'
                            ]);
                            $insertedParts++;
                        }
                    }
                }
            }
        }
    }
    
    // 트랜잭션 커밋
    $pdo->commit();
    
    jsonResponse([
        'success' => true,
        'models' => $insertedModels,
        'engines' => $insertedEngines,
        'parts' => $insertedParts
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    jsonResponse([
        'error' => true,
        'message' => $e->getMessage()
    ], 500);
}
?>
