<?php
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$dbname = 'hyundai_parts';
$username = 'root';
$password = 'Hyundai@2025';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h1>누락된 부품 확인</h1>";
    
    // G80 RG3 2.5 터보에 필요한 부품 목록
    $requiredParts = [
        '05100-2S400' => '엔진오일(대)',
        '05100-2S100' => '엔진오일(소)',
        '26350 2T000' => '오일필터',
        '28113 T1210' => '에어필터',
        '97133 T6500' => '에어컨필터(실내)',
        '97133 T6700' => '에어컨필터(외기)',
        '98350 0100' => '와이퍼(좌)',
        '98360 0100' => '와이퍼(우)',
        '58101 T1A00' => '브레이크 패드(앞축)',
    ];
    
    echo "<h2>G80 RG3 2.5 터보 필요 부품 점검</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>
            <th>부품번호</th>
            <th>부품타입</th>
            <th>DB 존재 여부</th>
            <th>매핑 여부</th>
          </tr>";
    
    $missingParts = [];
    
    foreach ($requiredParts as $partNumber => $partType) {
        // genuine_parts에서 찾기
        $stmt = $pdo->prepare("SELECT id, product_name, category_main FROM genuine_parts WHERE part_number = ?");
        $stmt->execute([$partNumber]);
        $part = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<tr>";
        echo "<td><strong>$partNumber</strong></td>";
        echo "<td>$partType</td>";
        
        if ($part) {
            echo "<td style='background: #d4edda; color: #155724;'>✅ 존재 (ID: {$part['id']}, {$part['product_name']})</td>";
            
            // 매핑 확인 (G80 RG3 2.5 터보 = engine_id 377)
            $stmt = $pdo->prepare("SELECT id FROM vehicle_parts_mapping WHERE part_id = ? AND car_engine_id = 377");
            $stmt->execute([$part['id']]);
            $mapped = $stmt->fetch();
            
            if ($mapped) {
                echo "<td style='background: #d4edda; color: #155724;'>✅ 매핑됨</td>";
            } else {
                echo "<td style='background: #fff3cd; color: #856404;'>⚠️ 매핑 안됨</td>";
            }
        } else {
            echo "<td style='background: #f8d7da; color: #721c24;'>❌ 없음</td>";
            echo "<td style='background: #f8d7da; color: #721c24;'>❌ 불가능</td>";
            $missingParts[] = ['number' => $partNumber, 'type' => $partType];
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    if (count($missingParts) > 0) {
        echo "<div style='background: #fff3cd; padding: 20px; margin-top: 20px; border: 1px solid #ffc107; border-radius: 5px;'>";
        echo "<h2>⚠️ genuine_parts 테이블에 추가해야 할 부품</h2>";
        echo "<p>다음 부품들을 <code>genuine_parts</code> 테이블에 먼저 추가해야 합니다:</p>";
        echo "<ol>";
        foreach ($missingParts as $missing) {
            echo "<li><strong>{$missing['number']}</strong> ({$missing['type']})</li>";
        }
        echo "</ol>";
        echo "<p>관리자 페이지에서 부품을 추가하거나, Excel 부품 마스터 데이터를 다시 임포트하세요.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 20px; margin-top: 20px; border: 1px solid #28a745; border-radius: 5px;'>";
        echo "<h2>✅ 모든 부품이 준비되었습니다!</h2>";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "<h1 style='color:red'>에러 발생</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
