<?php
/**
 * 엑셀 데이터 Import 스크립트
 * 현대차량 순정부품 엑셀 데이터를 데이터베이스로 가져오기
 */

require_once 'config/db.php';
require_once 'vendor/autoload.php'; // Composer autoload

use PhpOffice\PhpSpreadsheet\IOFactory;

set_time_limit(300); // 5분 제한

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>엑셀 Import</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";
echo "<h1>엑셀 데이터 Import</h1>";

try {
    // 엑셀 파일 경로
    $excelFile = 'uploads/현대차량 순정부품.xlsx';
    
    if (!file_exists($excelFile)) {
        throw new Exception("엑셀 파일을 찾을 수 없습니다: {$excelFile}");
    }
    
    echo "<p class='info'>엑셀 파일 로딩 중...</p>";
    
    // 엑셀 파일 읽기
    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    
    echo "<p class='info'>총 {$highestRow}행, {$highestColumn}열 데이터 발견</p>";
    
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 기존 데이터 삭제 (옵션)
    $clearData = isset($_GET['clear']) && $_GET['clear'] === 'true';
    if ($clearData) {
        echo "<p class='info'>기존 데이터 삭제 중...</p>";
        $pdo->exec("DELETE FROM car_engines");
        $pdo->exec("DELETE FROM car_models");
        $pdo->exec("DELETE FROM genuine_parts");
        echo "<p class='success'>기존 데이터 삭제 완료</p>";
    }
    
    // 헤더 정보 읽기 (1~2행)
    $partCategories = [];
    
    // 2행: 부품 카테고리 헤더
    for ($col = 8; $col <= 24; $col++) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $categoryName = $worksheet->getCell($colLetter . '2')->getValue();
        if ($categoryName && $categoryName !== 'None') {
            $partCategories[$col] = trim($categoryName);
        }
    }
    
    echo "<p class='info'>부품 카테고리: " . implode(', ', array_unique($partCategories)) . "</p>";
    
    // 차량 모델 및 부품 데이터 처리
    $insertedModels = 0;
    $insertedEngines = 0;
    $insertedParts = 0;
    
    $currentModel = null;
    $currentModelId = null;
    
    for ($row = 3; $row <= $highestRow; $row++) {
        // A열: 제조사, D열: 모델명, E열: 세대, F열: 동력원, G열: 엔진
        $manufacturer = $worksheet->getCell('A' . $row)->getValue();
        $modelName = $worksheet->getCell('D' . $row)->getValue();
        $generation = $worksheet->getCell('E' . $row)->getValue();
        $powerType = $worksheet->getCell('F' . $row)->getValue();
        $engineDetail = $worksheet->getCell('G' . $row)->getValue();
        
        // 새로운 차량 모델 발견
        if ($modelName && trim($modelName) !== '') {
            $modelName = trim($modelName);
            $generation = trim($generation ?? '');
            $powerType = trim($powerType ?? '');
            $engineDetail = trim($engineDetail ?? '');
            
            // 모델이 변경되었는지 확인
            if ($currentModel !== $modelName . '|' . $generation) {
                // 차량 모델 저장
                $category = $worksheet->getCell('B' . $row)->getValue() ?? '기타';
                $brandName = $modelName;
                
                $sql = "INSERT INTO car_models (manufacturer, category, brand_name, model_name, generation) 
                        VALUES (:manufacturer, :category, :brand_name, :model_name, :generation)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':manufacturer' => $manufacturer ?? '현대',
                    ':category' => $category,
                    ':brand_name' => $brandName,
                    ':model_name' => $modelName,
                    ':generation' => $generation
                ]);
                
                $currentModelId = $pdo->lastInsertId();
                $currentModel = $modelName . '|' . $generation;
                $insertedModels++;
            }
            
            // 엔진 정보 저장
            if ($engineDetail && $currentModelId) {
                $engineType = $powerType . ' ' . $engineDetail;
                
                $sql = "INSERT INTO car_engines (car_model_id, engine_type, engine_name) 
                        VALUES (:car_model_id, :engine_type, :engine_name)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':car_model_id' => $currentModelId,
                    ':engine_type' => trim($engineType),
                    ':engine_name' => $engineDetail
                ]);
                $insertedEngines++;
            }
            
            // 부품 정보 처리 (8열~24열)
            for ($col = 8; $col <= 24; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $partNumber = $worksheet->getCell($colLetter . $row)->getValue();
                
                if ($partNumber && isset($partCategories[$col]) && preg_match('/^\d{5}-\d{5}$/', trim($partNumber))) {
                    $partNumber = trim($partNumber);
                    $categoryMain = $partCategories[$col];
                    
                    // 중복 체크
                    $checkSql = "SELECT id FROM genuine_parts WHERE part_number = :part_number";
                    $checkStmt = $pdo->prepare($checkSql);
                    $checkStmt->execute([':part_number' => $partNumber]);
                    
                    if (!$checkStmt->fetch()) {
                        // 새 부품 추가
                        $sql = "INSERT INTO genuine_parts 
                                (category_main, category_sub, product_name, part_number, compatible_engines) 
                                VALUES (:category_main, :category_sub, :product_name, :part_number, :compatible_engines)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':category_main' => $categoryMain,
                            ':category_sub' => null,
                            ':product_name' => $categoryMain . ' - ' . $partNumber,
                            ':part_number' => $partNumber,
                            ':compatible_engines' => trim($engineType ?? '전체')
                        ]);
                        $insertedParts++;
                    }
                }
            }
        }
    }
    
    // 트랜잭션 커밋
    $pdo->commit();
    
    echo "<h2 class='success'>Import 완료!</h2>";
    echo "<p>차량 모델: {$insertedModels}개</p>";
    echo "<p>엔진 정보: {$insertedEngines}개</p>";
    echo "<p>부품 정보: {$insertedParts}개</p>";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "<h2 class='error'>오류 발생!</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
