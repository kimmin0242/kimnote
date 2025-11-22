<?php
/**
 * 데이터베이스 연결 설정
 * 시놀로지 NAS - MariaDB 10 환경
 */

// 데이터베이스 설정 (실제 환경에 맞게 수정하세요)
define('DB_HOST', 'localhost');
define('DB_NAME', 'hyundai_parts');  // 데이터베이스명을 실제 DB명으로 수정하세요
define('DB_USER', 'root');            // 데이터베이스 사용자명
define('DB_PASS', 'Kdmdtt1225**');    // 데이터베이스 비밀번호
define('DB_CHARSET', 'utf8mb4');

// PDO 연결
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // 에러 로깅 (프로덕션 환경에서는 로그 파일에 기록)
    error_log("데이터베이스 연결 실패: " . $e->getMessage());
    
    // 사용자에게 친화적인 메시지 표시
    die(json_encode([
        'error' => true,
        'message' => '데이터베이스 연결에 실패했습니다.'
    ]));
}

// 타임존 설정
date_default_timezone_set('Asia/Seoul');

// 에러 리포팅 (개발 환경)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON 응답 헤더 함수
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 보안: SQL Injection 방지를 위한 파라미터 정제
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>
