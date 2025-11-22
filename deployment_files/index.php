<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>현대차 순정부품 검색 시스템</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            background-color: #f8f9fa;
        }
        .search-section {
            background: linear-gradient(135deg, #002c5f 0%, #0046a8 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .part-card {
            transition: transform 0.2s;
            border: 1px solid #e0e0e0;
            margin-bottom: 1rem;
        }
        .part-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .required-label::after {
            content: " *";
            color: #ff6b6b;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
        .badge-count {
            margin-left: 0.5rem;
        }
        @media (max-width: 768px) {
            .search-section {
                padding: 1.5rem 0;
            }
            .part-card {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- 헤더 -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand mb-0 h1">
                <i class="fas fa-car"></i> 현대차 순정부품 검색 시스템
            </span>
            <a href="admin/" class="btn btn-outline-light btn-sm">
                <i class="fas fa-cog"></i> 관리자
            </a>
        </div>
    </nav>

    <!-- 검색 섹션 -->
    <section class="search-section">
        <div class="container">
            <h2 class="text-center mb-4">
                <i class="fas fa-search"></i> 부품 검색
            </h2>
            
            <!-- 5-Level Search Form -->
            <form id="searchForm" class="row g-3">
                <!-- 1. 차명 (Required) -->
                <div class="col-md-6 col-lg-3">
                    <label class="form-label required-label">차명</label>
                    <select class="form-select" id="modelSelect" required>
                        <option value="">차량 선택</option>
                    </select>
                </div>
                
                <!-- 2. 상세트림/세대 (Required) -->
                <div class="col-md-6 col-lg-3">
                    <label class="form-label required-label">상세트림/세대</label>
                    <select class="form-select" id="trimSelect" required disabled>
                        <option value="">먼저 차량을 선택하세요</option>
                    </select>
                </div>
                
                <!-- 3. 연료형식 (Optional) -->
                <div class="col-md-6 col-lg-2">
                    <label class="form-label">연료형식</label>
                    <select class="form-select" id="fuelSelect" disabled>
                        <option value="">전체</option>
                    </select>
                </div>
                
                <!-- 4. 엔진형식 (Optional) -->
                <div class="col-md-6 col-lg-2">
                    <label class="form-label">엔진형식</label>
                    <select class="form-select" id="engineSelect" disabled>
                        <option value="">전체</option>
                    </select>
                </div>
                
                <!-- 5. 부품명 (Optional) -->
                <div class="col-md-12 col-lg-2">
                    <label class="form-label">부품명</label>
                    <input type="text" class="form-control" id="partSearch" placeholder="부품명 검색">
                </div>
                
                <div class="col-12 text-center mt-3">
                    <button type="submit" class="btn btn-light btn-lg me-2">
                        <i class="fas fa-search"></i> 검색하기
                    </button>
                    <button type="button" class="btn btn-outline-light btn-lg" onclick="clearSearch()">
                        <i class="fas fa-undo"></i> 초기화
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- 결과 섹션 -->
    <section class="container mb-5">
        <div id="loading" class="text-center d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">검색 중...</p>
        </div>
        
        <!-- Tabbed Results -->
        <div id="resultsContainer" class="d-none">
            <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                        전체 <span class="badge bg-primary badge-count" id="count-all">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="engine-tab" data-bs-toggle="tab" data-bs-target="#engine" type="button">
                        엔진류 <span class="badge bg-secondary badge-count" id="count-engine">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="filter-tab" data-bs-toggle="tab" data-bs-target="#filter" type="button">
                        필터류 <span class="badge bg-secondary badge-count" id="count-filter">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="wiper-tab" data-bs-toggle="tab" data-bs-target="#wiper" type="button">
                        와이퍼 <span class="badge bg-secondary badge-count" id="count-wiper">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="brake-tab" data-bs-toggle="tab" data-bs-target="#brake" type="button">
                        브레이크 <span class="badge bg-secondary badge-count" id="count-brake">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="other-tab" data-bs-toggle="tab" data-bs-target="#other" type="button">
                        기타 부품 <span class="badge bg-secondary badge-count" id="count-other">0</span>
                    </button>
                </li>
            </ul>
            <div class="tab-content pt-3" id="categoryTabContent">
                <div class="tab-pane fade show active" id="all" role="tabpanel"></div>
                <div class="tab-pane fade" id="engine" role="tabpanel"></div>
                <div class="tab-pane fade" id="filter" role="tabpanel"></div>
                <div class="tab-pane fade" id="wiper" role="tabpanel"></div>
                <div class="tab-pane fade" id="brake" role="tabpanel"></div>
                <div class="tab-pane fade" id="other" role="tabpanel"></div>
            </div>
        </div>
        
        <div id="noResults" class="text-center d-none">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 검색 결과가 없습니다.
            </div>
        </div>
    </section>

    <!-- 푸터 -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 현대차 순정부품 관리 시스템. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 우클릭 방지
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        // 페이지 로드 시 모델 목록 불러오기
        window.onload = function() {
            loadCarModels();
        };

        // 1. 차명 선택 -> 상세트림/세대 로드
        document.getElementById('modelSelect').addEventListener('change', function() {
            const modelName = this.value;
            resetDependentFields(['trim', 'fuel', 'engine']);
            
            if (modelName) {
                loadTrims(modelName);
            }
        });

        // 2. 상세트림/세대 선택 -> 연료형식 로드
        document.getElementById('trimSelect').addEventListener('change', function() {
            const modelName = document.getElementById('modelSelect').value;
            const generation = this.value;
            resetDependentFields(['fuel', 'engine']);
            
            if (modelName && generation) {
                loadFuelTypes(modelName, generation);
            }
        });

        // 3. 연료형식 선택 -> 엔진형식 로드
        document.getElementById('fuelSelect').addEventListener('change', function() {
            const modelName = document.getElementById('modelSelect').value;
            const generation = document.getElementById('trimSelect').value;
            const fuelType = this.value;
            resetDependentFields(['engine']);
            
            if (modelName && generation) {
                loadEngines(modelName, generation, fuelType);
            }
        });

        // 검색 폼 제출
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            searchParts();
        });

        // Reset dependent fields
        function resetDependentFields(fields) {
            if (fields.includes('trim')) {
                const trimSelect = document.getElementById('trimSelect');
                trimSelect.innerHTML = '<option value="">먼저 차량을 선택하세요</option>';
                trimSelect.disabled = true;
            }
            if (fields.includes('fuel')) {
                const fuelSelect = document.getElementById('fuelSelect');
                fuelSelect.innerHTML = '<option value="">전체</option>';
                fuelSelect.disabled = true;
            }
            if (fields.includes('engine')) {
                const engineSelect = document.getElementById('engineSelect');
                engineSelect.innerHTML = '<option value="">전체</option>';
                engineSelect.disabled = true;
            }
        }

        // 모델 목록 불러오기
        async function loadCarModels() {
            try {
                const response = await fetch('api/get_models.php');
                const data = await response.json();
                
                if (data.success && data.models) {
                    const modelSelect = document.getElementById('modelSelect');
                    data.models.forEach(modelName => {
                        const option = document.createElement('option');
                        option.value = modelName;
                        option.textContent = modelName;
                        modelSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('모델 목록 불러오기 실패:', error);
                alert('모델 목록을 불러오는데 실패했습니다.');
            }
        }

        // 상세트림/세대 목록 불러오기
        async function loadTrims(modelName) {
            try {
                const response = await fetch(`api/get_trims.php?model_name=${encodeURIComponent(modelName)}`);
                const data = await response.json();
                
                const trimSelect = document.getElementById('trimSelect');
                trimSelect.innerHTML = '<option value="">세대 선택</option>';
                
                if (data.success && data.trims && data.trims.length > 0) {
                    data.trims.forEach(trim => {
                        const option = document.createElement('option');
                        option.value = trim.generation;
                        option.textContent = trim.generation;
                        trimSelect.appendChild(option);
                    });
                    trimSelect.disabled = false;
                } else {
                    trimSelect.innerHTML = '<option value="">해당 차량의 세대 정보가 없습니다</option>';
                }
            } catch (error) {
                console.error('트림 목록 불러오기 실패:', error);
            }
        }

        // 연료형식 목록 불러오기
        async function loadFuelTypes(modelName, generation) {
            try {
                const response = await fetch(`api/get_fuel_types.php?model_name=${encodeURIComponent(modelName)}&generation=${encodeURIComponent(generation)}`);
                const data = await response.json();
                
                const fuelSelect = document.getElementById('fuelSelect');
                fuelSelect.innerHTML = '<option value="">전체</option>';
                
                if (data.success && data.fuel_types && data.fuel_types.length > 0) {
                    data.fuel_types.forEach(fuelType => {
                        const option = document.createElement('option');
                        option.value = fuelType;
                        option.textContent = fuelType;
                        fuelSelect.appendChild(option);
                    });
                    fuelSelect.disabled = false;
                    
                    // Also load engines for this selection
                    loadEngines(modelName, generation, '');
                }
            } catch (error) {
                console.error('연료 타입 불러오기 실패:', error);
            }
        }

        // 엔진형식 목록 불러오기
        async function loadEngines(modelName, generation, fuelType) {
            try {
                let url = `api/get_engines.php?model_name=${encodeURIComponent(modelName)}&generation=${encodeURIComponent(generation)}`;
                if (fuelType) {
                    url += `&fuel_type=${encodeURIComponent(fuelType)}`;
                }
                
                const response = await fetch(url);
                const engines = await response.json();
                
                const engineSelect = document.getElementById('engineSelect');
                engineSelect.innerHTML = '<option value="">전체</option>';
                
                if (engines && engines.length > 0) {
                    engines.forEach(engine => {
                        const option = document.createElement('option');
                        option.value = engine.engine_type;
                        option.textContent = engine.engine_type;
                        engineSelect.appendChild(option);
                    });
                    engineSelect.disabled = false;
                }
            } catch (error) {
                console.error('엔진 목록 불러오기 실패:', error);
            }
        }

        // 부품 검색
        async function searchParts() {
            const modelName = document.getElementById('modelSelect').value;
            const generation = document.getElementById('trimSelect').value;
            const fuelType = document.getElementById('fuelSelect').value;
            const engineType = document.getElementById('engineSelect').value;
            const partName = document.getElementById('partSearch').value;
            
            if (!modelName || !generation) {
                alert('차명과 세대는 필수 선택 항목입니다.');
                return;
            }
            
            document.getElementById('loading').classList.remove('d-none');
            document.getElementById('resultsContainer').classList.add('d-none');
            document.getElementById('noResults').classList.add('d-none');
            
            try {
                const params = new URLSearchParams({
                    model_name: modelName,
                    generation: generation
                });
                if (fuelType) params.append('fuel_type', fuelType);
                if (engineType) params.append('engine_type', engineType);
                if (partName) params.append('part_name', partName);
                
                // Use absolute path from document root
                const apiUrl = './api/search_parts_with_mapping.php?' + params.toString();
                console.log('API 호출:', apiUrl);
                
                const response = await fetch(apiUrl);
                
                // Check if response is OK
                if (!response.ok) {
                    console.error('HTTP 에러:', response.status, response.statusText);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                console.log('API 응답:', data);
                
                document.getElementById('loading').classList.add('d-none');
                
                if (!data.success) {
                    alert(data.message || '검색 중 오류가 발생했습니다.');
                    if (data.debug) {
                        console.error('서버 에러:', data.debug);
                    }
                    return;
                }
                
                if (!data.parts || data.parts.length === 0) {
                    document.getElementById('noResults').classList.remove('d-none');
                    return;
                }
                
                displayResults(data.parts);
            } catch (error) {
                console.error('검색 실패:', error);
                document.getElementById('loading').classList.add('d-none');
                alert('검색 중 오류가 발생했습니다.\n\n개발자 도구(F12)의 Console 탭에서 자세한 정보를 확인하세요.\n\n에러: ' + error.message);
            }
        }

        // 결과 표시 (카테고리별 탭)
        function displayResults(parts) {
            // Categorize parts
            const categories = {
                all: [],
                engine: [],
                filter: [],
                wiper: [],
                brake: [],
                other: []
            };
            
            parts.forEach(part => {
                categories.all.push(part);
                
                const mainCat = part.category_main ? part.category_main.toLowerCase() : '';
                const subCat = part.category_sub ? part.category_sub.toLowerCase() : '';
                const partName = part.part_name ? part.part_name.toLowerCase() : '';
                
                // category_main 기준으로 정확하게 분류
                
                // 1. 엔진류: 엔진오일, 오일필터, 오일량 (미션오일, 부동액 제외)
                if (mainCat.startsWith('엔진오일') || 
                    mainCat === '오일필터' || 
                    mainCat === '오일량' ||
                    (mainCat.includes('오일') && !mainCat.includes('미션') && !mainCat.includes('변속') && 
                     !mainCat.includes('부동액') && !mainCat.includes('atf') && !mainCat.includes('dct'))) {
                    categories.engine.push(part);
                } 
                // 2. 필터류: 에어필터, 에어컨필터, 연료필터 등 (오일필터 제외 - 이미 엔진류에 포함)
                else if ((mainCat.includes('필터') || mainCat.includes('filter') || mainCat.includes('엘리먼트')) && 
                         !mainCat.includes('오일')) {
                    categories.filter.push(part);
                } 
                // 3. 와이퍼
                else if (mainCat.includes('와이퍼') || mainCat.includes('wiper') || mainCat.includes('블레이드')) {
                    categories.wiper.push(part);
                } 
                // 4. 브레이크
                else if (mainCat.includes('브레이크') || mainCat.includes('brake') || mainCat.includes('패드')) {
                    categories.brake.push(part);
                } 
                // 5. 기타 (미션오일, ATF, 조향유, DCT오일, 부동액 등)
                else {
                    categories.other.push(part);
                }
            });
            
            // Update counts
            document.getElementById('count-all').textContent = categories.all.length;
            document.getElementById('count-engine').textContent = categories.engine.length;
            document.getElementById('count-filter').textContent = categories.filter.length;
            document.getElementById('count-wiper').textContent = categories.wiper.length;
            document.getElementById('count-brake').textContent = categories.brake.length;
            document.getElementById('count-other').textContent = categories.other.length;
            
            // Render each category
            renderCategoryParts('all', categories.all);
            renderCategoryParts('engine', categories.engine);
            renderCategoryParts('filter', categories.filter);
            renderCategoryParts('wiper', categories.wiper);
            renderCategoryParts('brake', categories.brake);
            renderCategoryParts('other', categories.other);
            
            document.getElementById('resultsContainer').classList.remove('d-none');
        }

        // Render parts for a specific category
        function renderCategoryParts(categoryId, parts) {
            const container = document.getElementById(categoryId);
            container.innerHTML = '';
            
            if (parts.length === 0) {
                container.innerHTML = '<div class="alert alert-info">해당 카테고리에 부품이 없습니다.</div>';
                return;
            }
            
            const row = document.createElement('div');
            row.className = 'row';
            
            parts.forEach(part => {
                const card = document.createElement('div');
                card.className = 'col-md-6 col-lg-4';
                card.innerHTML = `
                    <div class="card part-card h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">${part.category_main}${part.category_sub ? ' - ' + part.category_sub : ''}</h6>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title text-primary mb-3" style="font-size: 1.8rem; font-weight: bold;">
                                ${part.part_number}
                            </h3>
                            <h6 class="card-subtitle mb-3 text-muted">${part.product_name || '부품명 없음'}</h6>
                            <p class="card-text mb-2">
                                ${part.capacity ? `<strong>용량:</strong> <span class="badge bg-info">${part.capacity}</span><br>` : ''}
                                ${part.quantity ? `<strong>수량:</strong> <span class="badge bg-success">${part.quantity}</span><br>` : ''}
                                ${part.position ? `<strong>위치:</strong> <span class="badge bg-warning text-dark">${part.position}</span><br>` : ''}
                            </p>
                            ${part.mapping_notes ? `
                                <div class="alert alert-info py-2 px-3 mb-0" style="font-size: 0.9rem;">
                                    <i class="fas fa-info-circle"></i> <strong>비고:</strong> ${part.mapping_notes}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
                row.appendChild(card);
            });
            
            container.appendChild(row);
        }

        // 검색 초기화
        function clearSearch() {
            document.getElementById('searchForm').reset();
            resetDependentFields(['trim', 'fuel', 'engine']);
            document.getElementById('resultsContainer').classList.add('d-none');
            document.getElementById('noResults').classList.add('d-none');
        }
    </script>
</body>
</html>
