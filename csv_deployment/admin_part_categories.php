<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// 로그인 체크
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$isLoggedIn) {
    header('Location: admin/index.php');
    exit;
}

// 데이터베이스 연결
require_once 'config/db.php';

// 카테고리 테이블이 없으면 생성
$pdo->exec("CREATE TABLE IF NOT EXISTS part_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    part_type VARCHAR(100) NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_part_type (category_name, part_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// 기본 데이터가 없으면 초기 데이터 삽입
$count = $pdo->query("SELECT COUNT(*) FROM part_categories")->fetchColumn();

if ($count == 0) {
    $defaultCategories = [
        ['오일 및 액체류', '엔진오일(대)', 1],
        ['오일 및 액체류', '엔진오일(소)', 2],
        ['오일 및 액체류', '미션오일', 3],
        ['오일 및 액체류', '브레이크오일', 4],
        ['오일 및 액체류', '냉각수/부동액', 5],
        ['오일 및 액체류', '파워스티어링오일', 6],
        ['오일 및 액체류', '워셔액', 7],
        ['오일 및 액체류', '디퍼런셜오일', 8],
        ['필터류', '에어필터', 1],
        ['필터류', '오일필터', 2],
        ['필터류', '에어컨필터(실내)', 3],
        ['필터류', '에어컨필터(외기)', 4],
        ['필터류', '연료필터', 5],
        ['제동류', '브레이크 패드(앞축)', 1],
        ['제동류', '브레이크 패드(뒤축)', 2],
        ['제동류', '브레이크 디스크(앞)', 3],
        ['제동류', '브레이크 디스크(뒤)', 4],
        ['제동류', '타이어(앞)', 5],
        ['제동류', '타이어(뒤)', 6],
        ['전장 및 기타 부품류', '배터리', 1],
        ['전장 및 기타 부품류', '점화플러그', 2],
        ['전장 및 기타 부품류', '점화코일', 3],
        ['전장 및 기타 부품류', '구동벨트 (V벨트)', 4],
        ['전장 및 기타 부품류', '타이밍벨트', 5],
        ['전장 및 기타 부품류', '와이퍼 블레이드(좌)', 6],
        ['전장 및 기타 부품류', '와이퍼 블레이드(우)', 7],
        ['전장 및 기타 부품류', '와이퍼 블레이드(뒤)', 8]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO part_categories (category_name, part_type, display_order) VALUES (?, ?, ?)");
    foreach ($defaultCategories as $cat) {
        $stmt->execute($cat);
    }
}

// 카테고리 목록 가져오기
$categories = $pdo->query("
    SELECT DISTINCT category_name 
    FROM part_categories 
    WHERE is_active = 1 
    ORDER BY category_name
")->fetchAll(PDO::FETCH_COLUMN);

// 전체 부품 타입 가져오기
$allParts = $pdo->query("
    SELECT id, category_name, part_type, display_order, is_active
    FROM part_categories
    ORDER BY category_name, display_order, part_type
")->fetchAll(PDO::FETCH_ASSOC);

// 카테고리별로 그룹화
$groupedParts = [];
foreach ($allParts as $part) {
    $groupedParts[$part['category_name']][] = $part;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>부품 카테고리 관리</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; }
        .category-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
        }
        .part-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .part-item:hover {
            background: #f8f9fa;
        }
        .part-item.inactive {
            opacity: 0.5;
            text-decoration: line-through;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .drag-handle {
            cursor: move;
            color: #999;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin/index.php">
                <i class="fas fa-arrow-left"></i> 관리자 대시보드로 돌아가기
            </a>
            <span class="navbar-text text-white">
                <i class="fas fa-user"></i> 관리자
            </span>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tags"></i> 부품 카테고리 관리</h2>
                    <div>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus"></i> 새 카테고리 추가
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPartTypeModal">
                            <i class="fas fa-plus-circle"></i> 부품 타입 추가
                        </button>
                    </div>
                </div>

                <!-- 카테고리별 부품 목록 -->
                <?php foreach ($groupedParts as $categoryName => $parts): ?>
                <div class="card category-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($categoryName); ?>
                        </h5>
                        <span class="badge bg-light text-dark"><?php echo count($parts); ?>개</span>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($parts as $part): ?>
                        <div class="part-item <?php echo $part['is_active'] ? '' : 'inactive'; ?>" data-id="<?php echo $part['id']; ?>">
                            <div class="d-flex align-items-center">
                                <span class="drag-handle me-2">
                                    <i class="fas fa-grip-vertical"></i>
                                </span>
                                <span class="part-name">
                                    <strong><?php echo htmlspecialchars($part['part_type']); ?></strong>
                                    <?php if (!$part['is_active']): ?>
                                        <span class="badge bg-secondary ms-2">비활성</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary edit-part" 
                                        data-id="<?php echo $part['id']; ?>"
                                        data-category="<?php echo htmlspecialchars($part['category_name']); ?>"
                                        data-type="<?php echo htmlspecialchars($part['part_type']); ?>"
                                        data-active="<?php echo $part['is_active']; ?>">
                                    <i class="fas fa-edit"></i> 수정
                                </button>
                                <?php if ($part['is_active']): ?>
                                <button class="btn btn-sm btn-outline-warning toggle-active" 
                                        data-id="<?php echo $part['id']; ?>"
                                        data-active="0">
                                    <i class="fas fa-eye-slash"></i> 비활성화
                                </button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-success toggle-active" 
                                        data-id="<?php echo $part['id']; ?>"
                                        data-active="1">
                                    <i class="fas fa-eye"></i> 활성화
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-danger delete-part" 
                                        data-id="<?php echo $part['id']; ?>"
                                        data-type="<?php echo htmlspecialchars($part['part_type']); ?>">
                                    <i class="fas fa-trash"></i> 삭제
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 새 카테고리 추가 모달 -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">새 카테고리 추가</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">카테고리 이름</label>
                        <input type="text" class="form-control" id="newCategoryName" placeholder="예: 엔진 부품류">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">첫 부품 타입 (선택사항)</label>
                        <input type="text" class="form-control" id="newCategoryFirstType" placeholder="예: 엔진 마운트">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-success" id="saveCategoryBtn">추가</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 부품 타입 추가 모달 -->
    <div class="modal fade" id="addPartTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">부품 타입 추가</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">카테고리 선택</label>
                        <select class="form-select" id="addPartCategory">
                            <option value="">선택하세요</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">부품 타입 이름</label>
                        <input type="text" class="form-control" id="addPartType" placeholder="예: CVT 오일">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" id="savePartTypeBtn">추가</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 부품 타입 수정 모달 -->
    <div class="modal fade" id="editPartTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">부품 타입 수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editPartId">
                    <div class="mb-3">
                        <label class="form-label">카테고리</label>
                        <select class="form-select" id="editPartCategory">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">부품 타입 이름</label>
                        <input type="text" class="form-control" id="editPartType">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" id="updatePartTypeBtn">수정</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // 새 카테고리 추가
    document.getElementById('saveCategoryBtn').addEventListener('click', async function() {
        const categoryName = document.getElementById('newCategoryName').value.trim();
        const firstType = document.getElementById('newCategoryFirstType').value.trim();
        
        if (!categoryName) {
            alert('카테고리 이름을 입력하세요.');
            return;
        }
        
        const response = await fetch('api/part_categories_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_category',
                category_name: categoryName,
                first_type: firstType
            })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('카테고리가 추가되었습니다.');
            location.reload();
        } else {
            alert('오류: ' + result.message);
        }
    });

    // 부품 타입 추가
    document.getElementById('savePartTypeBtn').addEventListener('click', async function() {
        const category = document.getElementById('addPartCategory').value;
        const partType = document.getElementById('addPartType').value.trim();
        
        if (!category || !partType) {
            alert('모든 필드를 입력하세요.');
            return;
        }
        
        const response = await fetch('api/part_categories_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_part_type',
                category_name: category,
                part_type: partType
            })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('부품 타입이 추가되었습니다.');
            location.reload();
        } else {
            alert('오류: ' + result.message);
        }
    });

    // 수정 버튼
    document.querySelectorAll('.edit-part').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editPartId').value = this.dataset.id;
            document.getElementById('editPartCategory').value = this.dataset.category;
            document.getElementById('editPartType').value = this.dataset.type;
            
            new bootstrap.Modal(document.getElementById('editPartTypeModal')).show();
        });
    });

    // 부품 타입 수정
    document.getElementById('updatePartTypeBtn').addEventListener('click', async function() {
        const id = document.getElementById('editPartId').value;
        const category = document.getElementById('editPartCategory').value;
        const partType = document.getElementById('editPartType').value.trim();
        
        if (!partType) {
            alert('부품 타입을 입력하세요.');
            return;
        }
        
        const response = await fetch('api/part_categories_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_part_type',
                id: id,
                category_name: category,
                part_type: partType
            })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('수정되었습니다.');
            location.reload();
        } else {
            alert('오류: ' + result.message);
        }
    });

    // 활성화/비활성화 토글
    document.querySelectorAll('.toggle-active').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const active = this.dataset.active;
            
            const response = await fetch('api/part_categories_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'toggle_active',
                    id: id,
                    is_active: active
                })
            });
            
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('오류: ' + result.message);
            }
        });
    });

    // 삭제
    document.querySelectorAll('.delete-part').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const type = this.dataset.type;
            
            if (!confirm(`"${type}"을(를) 정말 삭제하시겠습니까?\n\n⚠️ 주의: 이 부품 타입을 사용하는 기존 매핑 데이터가 영향을 받을 수 있습니다.`)) {
                return;
            }
            
            const response = await fetch('api/part_categories_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_part_type',
                    id: id
                })
            });
            
            const result = await response.json();
            if (result.success) {
                alert('삭제되었습니다.');
                location.reload();
            } else {
                alert('오류: ' + result.message);
            }
        });
    });
    </script>
</body>
</html>
