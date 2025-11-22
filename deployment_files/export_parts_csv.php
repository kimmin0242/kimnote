<?php
/**
 * 차량-부품 매핑 데이터 CSV 내보내기
 */
session_start();

// 관리자 권한 체크
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('권한이 없습니다.');
}

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
    die("DB 연결 실패: " . $e->getMessage());
}

// 데이터 조회
$sql = "SELECT 
            cm.model_name AS '차명',
            cm.generation AS '세대',
            ce.fuel_type AS '연료',
            ce.engine_type AS '엔진',
            gp.part_number AS '부품번호',
            gp.product_name AS '부품명',
            gp.category_main AS '주카테고리',
            gp.category_sub AS '부카테고리',
            gp.capacity AS '용량',
            vpm.part_type AS '부품타입',
            vpm.quantity AS '수량',
            vpm.position AS '위치',
            vpm.notes AS '비고',
            vpm.replacement_cycle AS '교체주기'
        FROM vehicle_parts_mapping vpm
        JOIN car_engines ce ON vpm.car_engine_id = ce.id
        JOIN car_models cm ON ce.car_model_id = cm.id
        JOIN genuine_parts gp ON vpm.part_id = gp.id
        ORDER BY cm.model_name, cm.generation, ce.fuel_type, ce.engine_type, gp.category_main";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CSV 헤더 설정
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="vehicle_parts_mapping_' . date('Y-m-d') . '.csv"');

// UTF-8 BOM 추가 (Excel에서 한글 깨짐 방지)
echo "\xEF\xBB\xBF";

// CSV 출력
$output = fopen('php://output', 'w');

// 헤더 작성
if (count($data) > 0) {
    fputcsv($output, array_keys($data[0]));
}

// 데이터 작성
foreach ($data as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
