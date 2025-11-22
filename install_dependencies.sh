#!/bin/bash
# PhpSpreadsheet 수동 설치 스크립트

echo "PhpSpreadsheet 다운로드 중..."

# vendor 디렉토리 생성
mkdir -p vendor

# PhpSpreadsheet 최신 버전 다운로드
cd vendor
wget https://github.com/PHPOffice/PhpSpreadsheet/archive/refs/tags/1.29.0.tar.gz -O phpspreadsheet.tar.gz

# 압축 해제
tar -xzf phpspreadsheet.tar.gz
mv PhpSpreadsheet-1.29.0 phpoffice

# 정리
rm phpspreadsheet.tar.gz

# autoload.php 생성
cat > autoload.php << 'EOF'
<?php
// 수동 autoload
spl_autoload_register(function ($class) {
    $prefix = 'PhpOffice\\PhpSpreadsheet\\';
    $base_dir = __DIR__ . '/phpoffice/src/PhpSpreadsheet/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
EOF

cd ..
echo "설치 완료!"
echo "vendor/ 폴더를 /volume1/web/hyundai-parts/로 업로드하세요."
