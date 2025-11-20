<?php
header('Content-Type: text/html; charset=utf-8');

$adminDir = '/volume1/web/hyundai-parts/admin/';

echo "<h1>관리자 폴더 파일 목록</h1>";

if (is_dir($adminDir)) {
    echo "<h2>폴더: $adminDir</h2>";
    
    $files = scandir($adminDir);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>파일명</th><th>크기</th><th>수정일</th></tr>";
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $filePath = $adminDir . $file;
        $size = filesize($filePath);
        $modified = date("Y-m-d H:i:s", filemtime($filePath));
        
        echo "<tr>";
        echo "<td><a href='/hyundai-parts/admin/$file' target='_blank'>$file</a></td>";
        echo "<td>" . number_format($size) . " bytes</td>";
        echo "<td>$modified</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color:red;'>admin 폴더가 존재하지 않습니다.</p>";
}

echo "<br><h2>메인 폴더 파일 목록</h2>";

$mainDir = '/volume1/web/hyundai-parts/';
$mainFiles = scandir($mainDir);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>파일명</th><th>크기</th><th>수정일</th></tr>";

foreach ($mainFiles as $file) {
    if ($file == '.' || $file == '..') continue;
    
    $filePath = $mainDir . $file;
    
    if (is_dir($filePath)) {
        echo "<tr style='background:#f0f0f0;'>";
        echo "<td><strong>[폴더] $file</strong></td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
        echo "</tr>";
    } else {
        $size = filesize($filePath);
        $modified = date("Y-m-d H:i:s", filemtime($filePath));
        
        echo "<tr>";
        echo "<td>$file</td>";
        echo "<td>" . number_format($size) . " bytes</td>";
        echo "<td>$modified</td>";
        echo "</tr>";
    }
}

echo "</table>";
?>
