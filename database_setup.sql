SET FOREIGN_KEY_CHECKS=0;

-- 차량 모델 테이블
CREATE TABLE IF NOT EXISTS car_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manufacturer VARCHAR(50) NOT NULL,
    category VARCHAR(100) NOT NULL,
    brand_name VARCHAR(100) NOT NULL,
    model_name VARCHAR(100) NOT NULL,
    generation VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 엔진 정보 테이블  
CREATE TABLE IF NOT EXISTS car_engines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_model_id INT,
    engine_type VARCHAR(100) NOT NULL,
    engine_name VARCHAR(100),
    FOREIGN KEY (car_model_id) REFERENCES car_models(id) ON DELETE CASCADE
);

-- 순정부품 테이블
CREATE TABLE IF NOT EXISTS genuine_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_main VARCHAR(100) NOT NULL,
    category_sub VARCHAR(100),
    product_name TEXT NOT NULL,
    capacity VARCHAR(50),
    part_number VARCHAR(100) NOT NULL UNIQUE,
    compatible_engines TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 샘플 데이터 삽입 (엑셀 데이터 기반)
INSERT INTO car_models (manufacturer, category, brand_name, model_name) VALUES
('현대', '제너시스', '제너시스 DH', 'EQ900'),
('현대', '제너시스', '제너시스 DH', 'G70 IK'),
('현대', '준중형차/소형차', '아반떼', '아반떼CN7'),
('현대', '중형차', '소나타', 'DN8소나타'),
('현대', 'SUV', '투싼', '투싼NX4'),
('현대', '수소/전기자동차', '아이오닉', '아이오닉5');

INSERT INTO car_engines (car_model_id, engine_type) VALUES
(1, '3.3가솔린엔진'),
(1, '3.8가솔린엔진'),
(2, '2.0가솔린엔진'),
(2, '2.5가솔린엔진'),
(3, '1.6가솔린엔진'),
(3, '1.6LPG엔진'),
(4, '1.6T가솔린엔진'),
(4, '2.0가솔린엔진'),
(5, '1.6가솔린엔진'),
(5, '2.0디젤엔진'),
(6, '전기차');

INSERT INTO genuine_parts (category_main, category_sub, product_name, capacity, part_number, compatible_engines) VALUES
('엔진오일', '가솔린', 'SAE 5W-20 프리미엄 저마찰형 엔진오일 API SM/GF-IV급', '4L', '05100-00451', '2.0가솔린엔진,2.5가솔린엔진'),
('엔진오일', '가솔린', 'SAE 0W-20 저점도 차세대+ 가솔린엔진오일 API SN+급', '4L', '05100-00481', '1.6가솔린엔진,2.0가솔린엔진'),
('엔진오일', '디젤', 'SAE 5W-30 저마찰 승용디젤엔진오일 ACEA C2급 유로5', '6L', '05200-00630', '2.0디젤엔진,2.2디젤엔진'),
('미션오일& 후륜기어오일', NULL, 'SAE 75W 자동트랜스미션오일 ATF SP4M-1', '1L', '04500-00125', '전체'),
('브레이크 오일', NULL, '브레이크오일 DOT-4', '1L', '01100-00130', '전체'),
('부동액&기타오일', NULL, '현대모비스순정 4계절용 부동액 일반형', '4L', '07100-00401', '전체');

SET FOREIGN_KEY_CHECKS=1;