<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();

// Simple authentication
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => '파일이 업로드되지 않았습니다.']);
    exit;
}

$file = $_FILES['file'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => '파일 업로드 중 오류가 발생했습니다.']);
    exit;
}

// Check file extension
if (!str_ends_with(strtolower($file['name']), '.xlsx')) {
    echo json_encode(['success' => false, 'error' => 'Excel 파일(.xlsx)만 업로드할 수 있습니다.']);
    exit;
}

// Create temp directory if not exists
$tempDir = __DIR__ . '/temp_uploads';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Create output directory if not exists
$outputDir = __DIR__ . '/converted_files';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Generate unique filenames
$timestamp = date('YmdHis');
$inputFileName = 'input_' . $timestamp . '.xlsx';
$outputFileName = '차종별시트_' . $timestamp . '.xlsx';

$inputPath = $tempDir . '/' . $inputFileName;
$outputPath = $outputDir . '/' . $outputFileName;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $inputPath)) {
    echo json_encode(['success' => false, 'error' => '파일 저장 중 오류가 발생했습니다.']);
    exit;
}

// Run Python conversion script
$scriptPath = __DIR__ . '/excel_converter_method2.py';
$command = sprintf(
    'cd %s && python3 %s %s %s 2>&1',
    escapeshellarg(__DIR__),
    escapeshellarg($scriptPath),
    escapeshellarg($inputPath),
    escapeshellarg($outputPath)
);

exec($command, $output, $returnCode);

// Clean up input file
@unlink($inputPath);

if ($returnCode !== 0) {
    // Conversion failed
    $errorMsg = implode("\n", $output);
    
    // Check if openpyxl is not installed
    if (strpos($errorMsg, 'No module named') !== false || strpos($errorMsg, 'openpyxl') !== false) {
        echo json_encode([
            'success' => false, 
            'error' => 'Python openpyxl 모듈이 설치되지 않았습니다. 관리자에게 문의하세요.',
            'details' => $errorMsg
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => '변환 중 오류가 발생했습니다.',
            'details' => $errorMsg
        ]);
    }
    exit;
}

// Check if output file exists
if (!file_exists($outputPath)) {
    echo json_encode([
        'success' => false, 
        'error' => '출력 파일이 생성되지 않았습니다.',
        'details' => implode("\n", $output)
    ]);
    exit;
}

// Parse statistics from output
$stats = [
    'total_vehicles' => 0,
    'total_parts' => 0,
    'sheets' => []
];

foreach ($output as $line) {
    // Parse total vehicles
    if (preg_match('/총 처리 차량:\s*(\d+)/', $line, $matches)) {
        $stats['total_vehicles'] = intval($matches[1]);
    }
    
    // Parse total parts
    if (preg_match('/총 부품 레코드:\s*(\d+)/', $line, $matches)) {
        $stats['total_parts'] = intval($matches[1]);
    }
    
    // Parse sheet stats
    if (preg_match('/^\s+(제너시스_\S+|현대_\S+):\s*(\d+)/', $line, $matches)) {
        $sheetName = $matches[1];
        $count = intval($matches[2]);
        $stats['sheets'][$sheetName] = $count;
    }
}

// Generate download URL
$downloadUrl = 'converted_files/' . $outputFileName;

echo json_encode([
    'success' => true,
    'message' => '변환이 완료되었습니다.',
    'download_url' => $downloadUrl,
    'stats' => $stats,
    'output' => $output
]);
