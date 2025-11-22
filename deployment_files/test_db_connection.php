<?php
/**
 * 데이터베이스 연결 테스트 스크립트
 * 
 * 사용법: http://59.19.231.47/hyundai-parts/test_db_connection.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DB 연결 테스트</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border: 1px solid green; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border: 1px solid red; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border: 1px solid blue; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🔍 데이터베이스 연결 테스트</h1>
    
    <?php
    // 1. config/db.php 파일 존재 여부 확인
    echo "<h2>1. config/db.php 파일 확인</h2>";
    $configPath = __DIR__ . '/config/db.php';
    
    if (file_exists($configPath)) {
        echo "<div class='success'>✅ config/db.php 파일이 존재합니다.</div>";
        echo "<div class='info'>경로: $configPath</div>";
    } else {
        echo "<div class='error'>❌ config/db.php 파일을 찾을 수 없습니다!</div>";
        echo "<div class='info'>찾은 경로: $configPath</div>";
        echo "<div class='info'>deployment_files/db.php를 /config/db.php로 업로드해야 합니다.</div>";
        exit;
    }
    
    // 2. config/db.php include 시도
    echo "<h2>2. config/db.php 로드 테스트</h2>";
    try {
        require_once $configPath;
        echo "<div class='success'>✅ config/db.php 로드 성공</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ config/db.php 로드 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
        exit;
    }
    
    // 3. DB 연결 확인
    echo "<h2>3. 데이터베이스 연결 확인</h2>";
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "<div class='success'>✅ PDO 객체가 생성되었습니다.</div>";
    } else {
        echo "<div class='error'>❌ PDO 객체가 생성되지 않았습니다!</div>";
        exit;
    }
    
    // 4. 데이터베이스 정보 확인
    echo "<h2>4. 데이터베이스 정보</h2>";
    try {
        $dbInfo = $pdo->query("SELECT DATABASE() as db_name")->fetch();
        echo "<div class='success'>✅ 연결된 데이터베이스: " . htmlspecialchars($dbInfo['db_name']) . "</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>❌ 데이터베이스 정보 조회 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // 5. 테이블 목록 확인
    echo "<h2>5. 테이블 목록</h2>";
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (count($tables) > 0) {
            echo "<div class='success'>✅ 테이블 " . count($tables) . "개 발견</div>";
            echo "<table>";
            echo "<tr><th>순번</th><th>테이블명</th><th>상태</th></tr>";
            $requiredTables = ['car_models', 'car_engines', 'genuine_parts', 'vehicle_parts_mapping'];
            foreach ($tables as $index => $table) {
                $required = in_array($table, $requiredTables) ? '✅ 필수' : '';
                echo "<tr><td>" . ($index + 1) . "</td><td>$table</td><td>$required</td></tr>";
            }
            echo "</table>";
            
            // 필수 테이블 체크
            echo "<h3>필수 테이블 확인:</h3>";
            foreach ($requiredTables as $reqTable) {
                if (in_array($reqTable, $tables)) {
                    echo "<div class='success'>✅ $reqTable 존재</div>";
                } else {
                    echo "<div class='error'>❌ $reqTable 없음</div>";
                }
            }
        } else {
            echo "<div class='error'>❌ 테이블이 없습니다!</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ 테이블 조회 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // 6. vehicle_parts_mapping 테이블 구조 확인
    echo "<h2>6. vehicle_parts_mapping 테이블 확인</h2>";
    try {
        $checkMapping = $pdo->query("SHOW TABLES LIKE 'vehicle_parts_mapping'")->fetchAll();
        if (count($checkMapping) > 0) {
            echo "<div class='success'>✅ vehicle_parts_mapping 테이블 존재</div>";
            
            // 컬럼 정보
            $columns = $pdo->query("SHOW COLUMNS FROM vehicle_parts_mapping")->fetchAll();
            echo "<table>";
            echo "<tr><th>컬럼명</th><th>타입</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            $hasNotes = false;
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
                echo "</tr>";
                if ($col['Field'] === 'notes') $hasNotes = true;
            }
            echo "</table>";
            
            if ($hasNotes) {
                echo "<div class='success'>✅ notes 컬럼이 존재합니다.</div>";
            } else {
                echo "<div class='error'>❌ notes 컬럼이 없습니다. add_notes_column.php를 실행하세요.</div>";
            }
            
            // 데이터 개수
            $count = $pdo->query("SELECT COUNT(*) FROM vehicle_parts_mapping")->fetchColumn();
            echo "<div class='info'>📊 vehicle_parts_mapping 레코드 수: $count 개</div>";
        } else {
            echo "<div class='error'>❌ vehicle_parts_mapping 테이블이 없습니다!</div>";
            echo "<div class='info'>이 테이블은 부품 검색에 필수입니다. 관리자 페이지에서 부품을 추가하세요.</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ vehicle_parts_mapping 확인 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // 7. 샘플 데이터 확인
    echo "<h2>7. 데이터 개수 확인</h2>";
    $dataCheck = [
        'car_models' => '차량 모델',
        'car_engines' => '엔진 정보',
        'genuine_parts' => '부품 정보',
        'vehicle_parts_mapping' => '차량-부품 매핑'
    ];
    
    echo "<table>";
    echo "<tr><th>테이블</th><th>설명</th><th>레코드 수</th></tr>";
    foreach ($dataCheck as $table => $desc) {
        try {
            if (in_array($table, $tables)) {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                $status = $count > 0 ? "✅ $count 개" : "⚠️ 0 개 (데이터 없음)";
                echo "<tr><td>$table</td><td>$desc</td><td>$status</td></tr>";
            } else {
                echo "<tr><td>$table</td><td>$desc</td><td>❌ 테이블 없음</td></tr>";
            }
        } catch (PDOException $e) {
            echo "<tr><td>$table</td><td>$desc</td><td>❌ 에러</td></tr>";
        }
    }
    echo "</table>";
    
    // 8. 결론
    echo "<h2>8. 진단 결과</h2>";
    echo "<div class='info'>";
    echo "<h3>✅ 성공한 항목:</h3>";
    echo "<ul>";
    echo "<li>config/db.php 파일 존재 및 로드</li>";
    echo "<li>PDO 데이터베이스 연결</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>📋 다음 단계:</h3>";
    echo "<ol>";
    if (!in_array('vehicle_parts_mapping', $tables)) {
        echo "<li><strong>관리자 페이지에서 차량과 부품을 매핑하세요.</strong></li>";
    }
    $mappingCount = 0;
    if (in_array('vehicle_parts_mapping', $tables)) {
        $mappingCount = $pdo->query("SELECT COUNT(*) FROM vehicle_parts_mapping")->fetchColumn();
    }
    if ($mappingCount == 0) {
        echo "<li><strong>vehicle_parts_mapping 테이블에 데이터가 없습니다. 관리자 페이지에서 부품을 추가하세요.</strong></li>";
    }
    if (!$hasNotes) {
        echo "<li><strong>add_notes_column.php를 실행하여 notes 컬럼을 추가하세요.</strong></li>";
    }
    echo "</ol>";
    echo "</div>";
    ?>
    
    <hr>
    <p><a href="index.php">← 메인 페이지로 돌아가기</a></p>
    <p><a href="admin_vehicle_parts.php">→ 관리자 페이지로 이동</a></p>
</body>
</html>
