<?php
/**
 * 차량-부품 매핑 데이터 CSV 가져오기
 */
session_start();

// 관리자 권한 체크
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die(json_encode(['success' => false, 'message' => '권한이 없습니다.']));
}

header('Content-Type: application/json; charset=UTF-8');

// 데이터베이스 연결
$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Kdmdtt1225**';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'DB 연결 실패: ' . $e->getMessage()]));
}

// 파일 업로드 확인
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    die(json_encode(['success' => false, 'message' => '파일 업로드 실패']));
}

$file = $_FILES['csv_file']['tmp_name'];

// CSV 파일 읽기
$handle = fopen($file, 'r');
if ($handle === false) {
    die(json_encode(['success' => false, 'message' => 'CSV 파일을 열 수 없습니다.']));
}

// UTF-8 BOM 제거
fseek($handle, 0);
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    fseek($handle, 0);
}

// 헤더 읽기
$headers = fgetcsv($handle);
if ($headers === false) {
    fclose($handle);
    die(json_encode(['success' => false, 'message' => 'CSV 헤더를 읽을 수 없습니다.']));
}

$successCount = 0;
$errorCount = 0;
$errors = [];

try {
    $pdo->beginTransaction();
    
    // 기존 매핑 데이터 삭제 (선택적)
    // $pdo->exec("TRUNCATE TABLE vehicle_parts_mapping");
    
    while (($row = fgetcsv($handle)) !== false) {
        try {
            // 행 데이터와 헤더 매핑
            $data = array_combine($headers, $row);
            
            // 차량 모델 ID 찾기
            $stmt = $pdo->prepare("SELECT id FROM car_models WHERE model_name = ? AND generation = ?");
            $stmt->execute([$data['차명'], $data['세대']]);
            $model = $stmt->fetch();
            
            if (!$model) {
                $errors[] = "차량 모델 없음: {$data['차명']} {$data['세대']}";
                $errorCount++;
                continue;
            }
            
            // 엔진 ID 찾기
            $stmt = $pdo->prepare("SELECT id FROM car_engines WHERE car_model_id = ? AND fuel_type = ? AND engine_type = ?");
            $stmt->execute([$model['id'], $data['연료'], $data['엔진']]);
            $engine = $stmt->fetch();
            
            if (!$engine) {
                $errors[] = "엔진 정보 없음: {$data['차명']} {$data['엔진']}";
                $errorCount++;
                continue;
            }
            
            // 부품 ID 찾기 또는 생성
            $stmt = $pdo->prepare("SELECT id FROM genuine_parts WHERE part_number = ?");
            $stmt->execute([$data['부품번호']]);
            $part = $stmt->fetch();
            
            if (!$part) {
                // 부품이 없으면 생성
                $stmt = $pdo->prepare("INSERT INTO genuine_parts (
                    part_number, product_name, category_main, category_sub, capacity, compatible_engines
                ) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $data['부품번호'],
                    $data['부품명'] ?: '',
                    $data['주카테고리'] ?: '',
                    $data['부카테고리'] ?: '',
                    $data['용량'] ?: '',
                    $data['엔진'] ?: ''
                ]);
                $partId = $pdo->lastInsertId();
            } else {
                $partId = $part['id'];
            }
            
            // 매핑 데이터 삽입 (중복 체크)
            $stmt = $pdo->prepare("SELECT id FROM vehicle_parts_mapping WHERE car_engine_id = ? AND part_id = ? AND part_type = ?");
            $stmt->execute([$engine['id'], $partId, $data['부품타입'] ?: '']);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // 업데이트
                $stmt = $pdo->prepare("UPDATE vehicle_parts_mapping SET 
                    quantity = ?, position = ?, notes = ?, replacement_cycle = ?
                    WHERE id = ?");
                $stmt->execute([
                    $data['수량'] ?: '1개',
                    $data['위치'] ?: '',
                    $data['비고'] ?: '',
                    $data['교체주기'] ?: '',
                    $existing['id']
                ]);
            } else {
                // 삽입
                $stmt = $pdo->prepare("INSERT INTO vehicle_parts_mapping (
                    car_engine_id, part_id, part_type, quantity, position, notes, replacement_cycle
                ) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $engine['id'],
                    $partId,
                    $data['부품타입'] ?: '',
                    $data['수량'] ?: '1개',
                    $data['위치'] ?: '',
                    $data['비고'] ?: '',
                    $data['교체주기'] ?: ''
                ]);
            }
            
            $successCount++;
            
        } catch (Exception $e) {
            $errors[] = "행 처리 실패: " . $e->getMessage();
            $errorCount++;
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "업로드 완료: 성공 {$successCount}건, 실패 {$errorCount}건",
        'success_count' => $successCount,
        'error_count' => $errorCount,
        'errors' => array_slice($errors, 0, 10) // 최대 10개 에러만 표시
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => '업로드 실패: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

fclose($handle);
?>
