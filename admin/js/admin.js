/**
 * 관리자 페이지 JavaScript
 */

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    
    // 폼 이벤트 리스너
    document.getElementById('modelDataForm')?.addEventListener('submit', saveModel);
    document.getElementById('engineDataForm')?.addEventListener('submit', saveEngine);
    document.getElementById('partDataForm')?.addEventListener('submit', savePart);
    document.getElementById('importForm')?.addEventListener('submit', importExcel);
});

// 섹션 전환
function showSection(sectionId) {
    // 모든 섹션 숨기기
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.add('d-none');
    });
    
    // 모든 네비게이션 링크에서 active 제거
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // 선택된 섹션 표시
    const selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.classList.remove('d-none');
    }
    
    // 네비게이션 활성화
    event.target.closest('.nav-link')?.classList.add('active');
    
    // 섹션별 데이터 로드
    switch(sectionId) {
        case 'models':
            loadModels();
            break;
        case 'engines':
            loadEngines();
            loadModelsForSelect();
            break;
        case 'parts':
            loadParts();
            break;
        case 'dashboard':
            loadDashboardStats();
            break;
    }
}

// ============= 대시보드 =============
async function loadDashboardStats() {
    try {
        const response = await fetch('api/get_stats.php');
        const stats = await response.json();
        
        document.getElementById('totalModels').textContent = stats.models || 0;
        document.getElementById('totalEngines').textContent = stats.engines || 0;
        document.getElementById('totalParts').textContent = stats.parts || 0;
    } catch (error) {
        console.error('통계 로드 실패:', error);
    }
}

// ============= 차량 모델 관리 =============
async function loadModels() {
    try {
        const response = await fetch('api/models.php');
        const models = await response.json();
        
        const tbody = document.getElementById('modelsTableBody');
        tbody.innerHTML = '';
        
        if (models.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">등록된 모델이 없습니다.</td></tr>';
            return;
        }
        
        models.forEach(model => {
            const row = `
                <tr>
                    <td>${model.id}</td>
                    <td>${model.manufacturer}</td>
                    <td>${model.category}</td>
                    <td>${model.brand_name}</td>
                    <td>${model.model_name}</td>
                    <td>${model.generation || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editModel(${model.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteModel(${model.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    } catch (error) {
        console.error('모델 로드 실패:', error);
    }
}

function showAddModelForm() {
    document.getElementById('modelForm').classList.remove('d-none');
    document.getElementById('modelFormTitle').textContent = '새 차량 모델 추가';
    document.getElementById('modelDataForm').reset();
    document.getElementById('model_id').value = '';
}

function hideModelForm() {
    document.getElementById('modelForm').classList.add('d-none');
}

async function saveModel(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('api/models.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('저장되었습니다.');
            hideModelForm();
            loadModels();
        } else {
            alert('오류: ' + result.message);
        }
    } catch (error) {
        console.error('저장 실패:', error);
        alert('저장 중 오류가 발생했습니다.');
    }
}

async function editModel(id) {
    try {
        const response = await fetch(`api/models.php?id=${id}`);
        const model = await response.json();
        
        document.getElementById('model_id').value = model.id;
        document.getElementById('manufacturer').value = model.manufacturer;
        document.getElementById('category').value = model.category;
        document.getElementById('brand_name').value = model.brand_name;
        document.getElementById('model_name').value = model.model_name;
        document.getElementById('generation').value = model.generation || '';
        
        document.getElementById('modelFormTitle').textContent = '차량 모델 수정';
        document.getElementById('modelForm').classList.remove('d-none');
    } catch (error) {
        console.error('모델 조회 실패:', error);
    }
}

async function deleteModel(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;
    
    try {
        const response = await fetch(`api/models.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('삭제되었습니다.');
            loadModels();
        } else {
            alert('오류: ' + result.message);
        }
    } catch (error) {
        console.error('삭제 실패:', error);
    }
}

// ============= 엔진 관리 =============
async function loadEngines() {
    try {
        const response = await fetch('api/engines.php');
        const engines = await response.json();
        
        const tbody = document.getElementById('enginesTableBody');
        tbody.innerHTML = '';
        
        if (engines.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">등록된 엔진이 없습니다.</td></tr>';
            return;
        }
        
        engines.forEach(engine => {
            const row = `
                <tr>
                    <td>${engine.id}</td>
                    <td>${engine.model_name || '-'}</td>
                    <td>${engine.engine_type}</td>
                    <td>${engine.engine_name || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editEngine(${engine.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteEngine(${engine.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    } catch (error) {
        console.error('엔진 로드 실패:', error);
    }
}

async function loadModelsForSelect() {
    try {
        const response = await fetch('api/models.php');
        const models = await response.json();
        
        const select = document.getElementById('car_model_id');
        select.innerHTML = '<option value="">선택하세요</option>';
        
        models.forEach(model => {
            const option = document.createElement('option');
            option.value = model.id;
            option.textContent = `${model.brand_name} ${model.model_name}`;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('모델 목록 로드 실패:', error);
    }
}

function showAddEngineForm() {
    document.getElementById('engineForm').classList.remove('d-none');
    document.getElementById('engineFormTitle').textContent = '새 엔진 추가';
    document.getElementById('engineDataForm').reset();
    document.getElementById('engine_id').value = '';
}

function hideEngineForm() {
    document.getElementById('engineForm').classList.add('d-none');
}

async function saveEngine(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('api/engines.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('저장되었습니다.');
            hideEngineForm();
            loadEngines();
        } else {
            alert('오류: ' + result.message);
        }
    } catch (error) {
        console.error('저장 실패:', error);
        alert('저장 중 오류가 발생했습니다.');
    }
}

async function editEngine(id) {
    try {
        const response = await fetch(`api/engines.php?id=${id}`);
        const engine = await response.json();
        
        document.getElementById('engine_id').value = engine.id;
        document.getElementById('car_model_id').value = engine.car_model_id;
        document.getElementById('engine_type').value = engine.engine_type;
        document.getElementById('engine_name').value = engine.engine_name || '';
        
        document.getElementById('engineFormTitle').textContent = '엔진 수정';
        document.getElementById('engineForm').classList.remove('d-none');
    } catch (error) {
        console.error('엔진 조회 실패:', error);
    }
}

async function deleteEngine(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;
    
    try {
        const response = await fetch(`api/engines.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('삭제되었습니다.');
            loadEngines();
        } else {
            alert('오류: ' + result.message);
        }
    } catch (error) {
        console.error('삭제 실패:', error);
    }
}

// ============= 부품 관리 =============
async function loadParts() {
    const searchTerm = document.getElementById('partSearchInput')?.value || '';
    
    try {
        const url = searchTerm ? `api/parts.php?search=${encodeURIComponent(searchTerm)}` : 'api/parts.php';
        const response = await fetch(url);
        const parts = await response.json();
        
        const tbody = document.getElementById('partsTableBody');
        tbody.innerHTML = '';
        
        if (parts.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">등록된 부품이 없습니다.</td></tr>';
            return;
        }
        
        parts.forEach(part => {
            const row = `
                <tr>
                    <td>${part.id}</td>
                    <td>${part.category_main}</td>
                    <td>${part.category_sub || '-'}</td>
                    <td>${part.product_name}</td>
                    <td>${part.capacity || '-'}</td>
                    <td><code>${part.part_number}</code></td>
                    <td><small>${part.compatible_engines || '-'}</small></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editPart(${part.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deletePart(${part.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    } catch (error) {
        console.error('부품 로드 실패:', error);
    }
}

function showAddPartForm() {
    document.getElementById('partForm').classList.remove('d-none');
    document.getElementById('partFormTitle').textContent = '새 부품 추가';
    document.getElementById('partDataForm').reset();
    document.getElementById('part_id').value = '';
}

function hidePartForm() {
    document.getElementById('partForm').classList.add('d-none');
}

async function savePart(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('api/parts.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('저장되었습니다.');
            hidePartForm();
            loadParts();
        } else {
            alert('오류: ' + result.message);
        }
    } catch (error) {
        console.error('저장 실패:', error);
        alert('저장 중 오류가 발생했습니다.');
    }
}

async function editPart(id) {
    try {
        const response = await fetch(`api/parts.php?id=${id}`);
        const part = await response.json();
        
        document.getElementById('part_id').value = part.id;
        document.getElementById('category_main').value = part.category_main;
        document.getElementById('category_sub').value = part.category_sub || '';
        document.getElementById('product_name').value = part.product_name;
        document.getElementById('capacity').value = part.capacity || '';
        document.getElementById('part_number').value = part.part_number;
        document.getElementById('compatible_engines').value = part.compatible_engines || '';
        document.getElementById('notes').value = part.notes || '';
        
        document.getElementById('partFormTitle').textContent = '부품 수정';
        document.getElementById('partForm').classList.remove('d-none');
    } catch (error) {
        console.error('부품 조회 실패:', error);
    }
}

async function deletePart(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;
    
    try {
        const response = await fetch(`api/parts.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('삭제되었습니다.');
            loadParts();
        } else {
            alert('오류: ' + result.message);
        }
    } catch (error) {
        console.error('삭제 실패:', error);
    }
}

// ============= 엑셀 가져오기 =============
async function importExcel(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('excelFile');
    const clearExisting = document.getElementById('clearExisting').checked;
    
    if (!fileInput.files[0]) {
        alert('파일을 선택하세요.');
        return;
    }
    
    const formData = new FormData();
    formData.append('excelFile', fileInput.files[0]);
    formData.append('clearExisting', clearExisting ? '1' : '0');
    
    document.getElementById('importProgress').classList.remove('d-none');
    document.getElementById('importResult').innerHTML = '';
    
    try {
        const response = await fetch('api/import_excel.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        document.getElementById('importProgress').classList.add('d-none');
        
        if (result.success) {
            document.getElementById('importResult').innerHTML = `
                <div class="alert alert-success">
                    <h5>가져오기 완료!</h5>
                    <p>차량 모델: ${result.models}개</p>
                    <p>엔진: ${result.engines}개</p>
                    <p>부품: ${result.parts}개</p>
                </div>
            `;
            loadDashboardStats();
        } else {
            document.getElementById('importResult').innerHTML = `
                <div class="alert alert-danger">
                    <h5>오류 발생</h5>
                    <p>${result.message}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('가져오기 실패:', error);
        document.getElementById('importProgress').classList.add('d-none');
        document.getElementById('importResult').innerHTML = `
            <div class="alert alert-danger">
                <h5>오류 발생</h5>
                <p>파일 업로드 중 오류가 발생했습니다.</p>
            </div>
        `;
    }
}
