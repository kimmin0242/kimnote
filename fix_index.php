<?php
/**
 * index.php 자동 수정 스크립트
 * - product_name → part_name 변경
 * - 부품번호 크게 표시
 * - 호환 엔진 제거
 * - 수량/위치 정보 추가
 */

$indexFile = '/volume1/web/hyundai-parts/index.php';

// 파일 읽기
$content = file_get_contents($indexFile);

if ($content === false) {
    die("❌ index.php 파일을 읽을 수 없습니다.");
}

// 백업
$backupFile = $indexFile . '.backup_' . date('Ymd_His');
file_put_contents($backupFile, $content);
echo "✅ 백업 완료: $backupFile<br><br>";

// 수정 1: productName 변수명 변경
$content = str_replace(
    'const productName = part.product_name ? part.product_name.toLowerCase() : \'\';',
    'const partName = part.part_name ? part.part_name.toLowerCase() : \'\';',
    $content,
    $count1
);
echo "✅ 수정 1: productName 변수 변경 ($count1 개소)<br>";

// 수정 2: 카드 렌더링 부분 변경
$oldCard = <<<'EOD'
                        <div class="card-body">
                            <h5 class="card-title">${part.product_name}</h5>
                            <p class="card-text mb-2">
                                <strong>부품번호:</strong> <code>${part.part_number}</code><br>
                                ${part.capacity ? `<strong>용량:</strong> ${part.capacity}<br>` : ''}
                                ${part.compatible_engines ? `<strong>호환 엔진:</strong> ${part.compatible_engines}<br>` : ''}
                            </p>
EOD;

$newCard = <<<'EOD'
                        <div class="card-body">
                            <h3 class="card-title text-primary mb-3" style="font-size: 1.8rem; font-weight: bold;">
                                ${part.part_number}
                            </h3>
                            <h6 class="card-subtitle mb-3 text-muted">${part.part_name}</h6>
                            <p class="card-text mb-2">
                                ${part.capacity ? `<strong>용량:</strong> <span class="badge bg-info">${part.capacity}</span><br>` : ''}
                                ${part.quantity ? `<strong>수량:</strong> <span class="badge bg-success">${part.quantity}</span><br>` : ''}
                                ${part.position ? `<strong>위치:</strong> <span class="badge bg-warning text-dark">${part.position}</span><br>` : ''}
                            </p>
EOD;

$content = str_replace($oldCard, $newCard, $content, $count2);
echo "✅ 수정 2: 카드 렌더링 변경 ($count2 개소)<br>";

// 파일 저장
file_put_contents($indexFile, $content);

echo "<br><h2 style='color: green;'>✅ 모든 수정이 완료되었습니다!</h2>";
echo "<p><a href='/hyundai-parts/'>메인 페이지로 이동하여 테스트하기</a></p>";
echo "<p><a href='javascript:history.back()'>뒤로 가기</a></p>";
?>
