<?php
// 디버그용 에러 표시
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    // 데이터베이스 연결 정보
    $host = 'localhost';
    $dbname = 'hyundai_parts';
    $username = 'root';
    $password = 'Kdmdtt1225**';
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // 파라미터 가져오기
    $modelName = $_GET['model_name'] ?? '';
    $generation = $_GET['generation'] ?? '';
    $fuelType = $_GET['fuel_type'] ?? '';
    $engineType = $_GET['engine_type'] ?? '';
    $partName = $_GET['part_name'] ?? '';
    
    echo json_encode([
        'debug' => true,
        'params' => [
            'model_name' => $modelName,
            'generation' => $generation,
            'fuel_type' => $fuelType,
            'engine_type' => $engineType,
            'part_name' => $partName
        ],
        'pdo_connected' => true
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
