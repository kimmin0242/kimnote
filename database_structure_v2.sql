-- ============================================
-- 현대차 순정부품 관리 시스템 - 새로운 DB 구조
-- ============================================

-- 1. 차량 모델 정보 테이블 (기존 유지 또는 개선)
CREATE TABLE IF NOT EXISTS car_models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    manufacturer VARCHAR(50) NOT NULL DEFAULT '현대',      -- 제조사
    brand VARCHAR(50),                                     -- 브랜드 (제너시스, N, 아이오닉 등)
    category VARCHAR(50),                                  -- 대분류 (승용차, SUV, 전기차 등)
    model_name VARCHAR(50) NOT NULL,                       -- 모델명 (G80, 소나타 등)
    generation VARCHAR(50) NOT NULL,                       -- 상세트림/세대 (RG3, DN8 등)
    description TEXT,                                      -- 설명
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_model_generation (model_name, generation),
    INDEX idx_brand (brand),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 엔진 정보 테이블 (기존 유지 또는 개선)
CREATE TABLE IF NOT EXISTS car_engines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    car_model_id INT NOT NULL,
    fuel_type VARCHAR(50) NOT NULL,                        -- 동력원 유형 (가솔린, 디젤, 전기차 등)
    engine_type VARCHAR(150) NOT NULL,                     -- 세부 엔진/동력계
    displacement VARCHAR(50),                              -- 배기량
    power_output VARCHAR(50),                              -- 출력
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (car_model_id) REFERENCES car_models(id) ON DELETE CASCADE,
    UNIQUE KEY idx_engine (car_model_id, fuel_type, engine_type),
    INDEX idx_fuel_type (fuel_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 부품 마스터 테이블 (기존 genuine_parts)
CREATE TABLE IF NOT EXISTS genuine_parts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_main VARCHAR(100) NOT NULL,                   -- 대분류 (엔진오일(대), 에어필터 등)
    category_sub VARCHAR(100),                             -- 소분류
    product_name VARCHAR(200) NOT NULL,                    -- 제품명
    part_number VARCHAR(50) NOT NULL,                      -- 부품번호 (OEM 번호)
    capacity VARCHAR(50),                                  -- 용량 (4L, 1L 등)
    manufacturer VARCHAR(100),                             -- 제조사
    unit_price DECIMAL(10,2),                             -- 단가
    description TEXT,                                      -- 설명
    compatible_engines TEXT,                               -- 호환 엔진 (레거시, 향후 사용 안 함)
    notes TEXT,                                           -- 비고
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_part_number (part_number),
    INDEX idx_category (category_main),
    INDEX idx_product_name (product_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ★★★ 핵심 - 차량별 부품 매핑 테이블 ★★★
CREATE TABLE IF NOT EXISTS vehicle_parts_mapping (
    id INT PRIMARY KEY AUTO_INCREMENT,
    car_engine_id INT NOT NULL,                           -- 차량 엔진 ID (car_engines 테이블 참조)
    part_id INT NOT NULL,                                 -- 부품 ID (genuine_parts 테이블 참조)
    part_type VARCHAR(100) NOT NULL,                      -- 부품 타입 (엔진오일(대), 오일필터 등)
    quantity VARCHAR(50),                                 -- 수량/용량 (4L+1L 2개, 1개 등)
    position VARCHAR(50),                                 -- 위치 (좌, 우, 앞축, 뒤축 등)
    is_required BOOLEAN DEFAULT TRUE,                     -- 필수 여부
    replacement_cycle VARCHAR(100),                       -- 교체 주기
    notes TEXT,                                           -- 비고
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (car_engine_id) REFERENCES car_engines(id) ON DELETE CASCADE,
    FOREIGN KEY (part_id) REFERENCES genuine_parts(id) ON DELETE CASCADE,
    UNIQUE KEY idx_vehicle_part (car_engine_id, part_type, position),
    INDEX idx_part_type (part_type),
    INDEX idx_part_id (part_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 샘플 데이터: G80 RG3 가솔린 2.5 터보
-- ============================================

-- Step 1: 차량 모델 입력
INSERT INTO car_models (manufacturer, brand, category, model_name, generation, description) 
VALUES ('현대', '제너시스', '제너시스 세단', 'G80', 'RG3 (3세대)', 'G80 3세대 모델')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

SET @g80_rg3_id = LAST_INSERT_ID();

-- Step 2: 엔진 정보 입력
INSERT INTO car_engines (car_model_id, fuel_type, engine_type, displacement) 
VALUES (@g80_rg3_id, '가솔린', '직렬 4기통 2.5 가솔린 터보 (I4 2.5 T-GDi)', '2.5L')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

SET @engine_25t_id = LAST_INSERT_ID();

-- Step 3: 부품 마스터 입력 (genuine_parts)
-- 엔진오일(대)
INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
VALUES ('엔진오일(대)', '엔진오일 4L', '05100-2S400', '4L')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
SET @oil_large_id = LAST_INSERT_ID();

-- 엔진오일(소)
INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
VALUES ('엔진오일(소)', '엔진오일 1L', '05100-2S100', '1L')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
SET @oil_small_id = LAST_INSERT_ID();

-- 오일필터
INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
VALUES ('오일필터', '오일필터', '26350 2T000', '1개')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
SET @oil_filter_id = LAST_INSERT_ID();

-- 에어필터
INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
VALUES ('에어필터', '에어필터', '28113 T1210', '1개')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
SET @air_filter_id = LAST_INSERT_ID();

-- 에어컨필터(실내)
INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
VALUES ('에어컨필터', '에어컨필터(실내)', '97133 T6500', '1개')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
SET @cabin_filter_inner_id = LAST_INSERT_ID();

-- 에어컨필터(외기)
INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
VALUES ('에어컨필터', '에어컨필터(외기)', '97133 T6700', '1개')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
SET @cabin_filter_outer_id = LAST_INSERT_ID();

-- 와이퍼(좌)
INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
VALUES ('와이퍼', '와이퍼 블레이드(좌)', '98350 0100', '1개')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
SET @wiper_left_id = LAST_INSERT_ID();

-- 와이퍼(우)
INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
VALUES ('와이퍼', '와이퍼 블레이드(우)', '98360 0100', '1개')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
SET @wiper_right_id = LAST_INSERT_ID();

-- 브레이크 패드(앞축)
INSERT INTO genuine_parts (category_main, product_name, part_number, capacity) 
VALUES ('브레이크 패드', '브레이크 패드(앞축)', '58101 T1A00', '1세트')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
SET @brake_pad_front_id = LAST_INSERT_ID();

-- Step 4: ★★★ 차량-부품 매핑 ★★★
-- 엔진오일(대)
INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity, is_required)
VALUES (@engine_25t_id, @oil_large_id, '엔진오일(대)', '1개', TRUE);

-- 엔진오일(소)
INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity, is_required)
VALUES (@engine_25t_id, @oil_small_id, '엔진오일(소)', '2개', TRUE);

-- 오일량 (가상 부품 - 정보 표시용)
-- 별도 부품 없이 정보만 표시하려면 notes에 기록
INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity, notes)
VALUES (@engine_25t_id, @oil_large_id, '오일량', '4L+1L 2개', '총 오일량: 6L (대 1개 + 소 2개)');

-- 오일필터
INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity, is_required)
VALUES (@engine_25t_id, @oil_filter_id, '오일필터', '1개', TRUE);

-- 에어필터
INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity, is_required)
VALUES (@engine_25t_id, @air_filter_id, '에어필터', '1개', TRUE);

-- 에어컨필터(실내)
INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity, position)
VALUES (@engine_25t_id, @cabin_filter_inner_id, '에어컨필터', '1개', '실내');

-- 에어컨필터(외기)
INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity, position)
VALUES (@engine_25t_id, @cabin_filter_outer_id, '에어컨필터', '1개', '외기');

-- 와이퍼(좌)
INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity, position)
VALUES (@engine_25t_id, @wiper_left_id, '와이퍼', '1개', '좌');

-- 와이퍼(우)
INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity, position)
VALUES (@engine_25t_id, @wiper_right_id, '와이퍼', '1개', '우');

-- 브레이크 패드(앞축)
INSERT INTO vehicle_parts_mapping (car_engine_id, part_id, part_type, quantity, position)
VALUES (@engine_25t_id, @brake_pad_front_id, '브레이크 패드', '1세트', '앞축');

-- ============================================
-- 조회 쿼리 예시
-- ============================================

-- G80 RG3 가솔린 2.5 터보의 모든 부품 조회
SELECT 
    cm.model_name,
    cm.generation,
    ce.fuel_type,
    ce.engine_type,
    gp.category_main,
    gp.product_name,
    gp.part_number,
    gp.capacity,
    vpm.part_type,
    vpm.quantity,
    vpm.position,
    vpm.notes
FROM vehicle_parts_mapping vpm
JOIN car_engines ce ON vpm.car_engine_id = ce.id
JOIN car_models cm ON ce.car_model_id = cm.id
JOIN genuine_parts gp ON vpm.part_id = gp.id
WHERE cm.model_name = 'G80' 
  AND cm.generation = 'RG3 (3세대)'
  AND ce.fuel_type = '가솔린'
  AND ce.engine_type LIKE '%2.5%'
ORDER BY 
    CASE gp.category_main
        WHEN '엔진오일(대)' THEN 1
        WHEN '엔진오일(소)' THEN 2
        WHEN '오일필터' THEN 3
        WHEN '에어필터' THEN 4
        WHEN '에어컨필터' THEN 5
        WHEN '와이퍼' THEN 6
        WHEN '브레이크 패드' THEN 7
        ELSE 99
    END,
    vpm.position;

-- ============================================
-- 인덱스 최적화 확인
-- ============================================
SHOW INDEX FROM car_models;
SHOW INDEX FROM car_engines;
SHOW INDEX FROM genuine_parts;
SHOW INDEX FROM vehicle_parts_mapping;
