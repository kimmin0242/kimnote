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

    echo "<h1>매핑된 부품 상세 확인</h1>";
    
    // 매핑된 부품의 실제 정보 확인
    $sql = "SELECT 
                vpm.id as mapping_id,
                vpm.car_engine_id,
                vpm.part_id,
                vpm.part_type,
                vpm.quantity,
                gp.part_number,
                gp.product_name,
                gp.category_main,
                gp.capacity,
                ce.engine_type,
                cm.model_name,
                cm.generation
            FROM vehicle_parts_mapping vpm
            JOIN genuine_parts gp ON vpm.part_id = gp.id
            JOIN car_engines ce ON vpm.car_engine_id = ce.id
            JOIN car_models cm ON ce.car_model_id = cm.id
            WHERE cm.model_name = 'G80' AND cm.generation LIKE '%RG3%'
            ORDER BY vpm.id";
    
    $stmt = $pdo->query($sql);
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>총 " . count($parts) . "개 부품 매핑됨</h2>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>
            <th>매핑ID</th>
            <th>부품타입</th>
            <th>부품번호</th>
            <th>부품명</th>
            <th>카테고리</th>
            <th>용량</th>
            <th>수량</th>
            <th>엔진타입</th>
          </tr>";
    
    foreach ($parts as $part) {
        echo "<tr>";
        echo "<td>" . $part['mapping_id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($part['part_type']) . "</strong></td>";
        echo "<td><strong style='color: blue;'>" . htmlspecialchars($part['part_number']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($part['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($part['category_main']) . "</td>";
        echo "<td>" . htmlspecialchars($part['capacity']) . "</td>";
        echo "<td>" . htmlspecialchars($part['quantity']) . "</td>";
        echo "<td style='font-size: 11px;'>" . htmlspecialchars($part['engine_type']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // 우리가 원하는 부품 번호 확인
    echo "<h2>원하는 부품들이 DB에 존재하는지 확인</h2>";
    
    $targetParts = [
        '05100-2S400' => '엔진오일(대) 4L',
        '05100-2S100' => '엔진오일(소) 1L',
        '26350 2T000' => '오일필터',
        '28113 T1210' => '에어필터'
    ];
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>찾는 부품번호</th><th>설명</th><th>DB에 존재?</th><th>실제 ID</th></tr>";
    
    foreach ($targetParts as $partNum => $desc) {
        $stmt = $pdo->prepare("SELECT id, part_number, product_name, category_main FROM genuine_parts WHERE part_number = ?");
        $stmt->execute([$partNum]);
        $found = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($found) {
            echo "<tr style='background: #d4edda;'>";
            echo "<td><strong>" . htmlspecialchars($partNum) . "</strong></td>";
            echo "<td>" . htmlspecialchars($desc) . "</td>";
            echo "<td style='color: green;'>✅ 존재함</td>";
            echo "<td>ID: " . $found['id'] . " / " . htmlspecialchars($found['product_name']) . "</td>";
            echo "</tr>";
        } else {
            echo "<tr style='background: #f8d7da;'>";
            echo "<td><strong>" . htmlspecialchars($partNum) . "</strong></td>";
            echo "<td>" . htmlspecialchars($desc) . "</td>";
            echo "<td style='color: red;'>❌ 없음</td>";
            echo "<td>-</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<h1 style='color:red'>에러 발생</h1>";
    echo "<p><strong>에러 메시지:</strong> " . $e->getMessage() . "</p>";
}
?>
