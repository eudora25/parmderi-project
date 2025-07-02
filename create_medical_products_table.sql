-- 의약품 데이터베이스 테이블 생성
-- 25,268개의 의약품 데이터를 저장

CREATE TABLE IF NOT EXISTS medical_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- 기본 정보
    product_no VARCHAR(20) COMMENT '전체 No',
    cso_product TINYINT DEFAULT 0 COMMENT 'CSO품목 (1: CSO품목, 0: 일반)',
    category CHAR(1) COMMENT '구분 (A/B/C)',
    company_name VARCHAR(100) COMMENT '업체명',
    
    -- 분류 정보
    classification_code_1 VARCHAR(10) COMMENT '분류번호 1',
    classification_name_1 VARCHAR(100) COMMENT '분류명 1',
    classification_code_2 VARCHAR(10) COMMENT '분류번호 2',
    classification_name_2 VARCHAR(100) COMMENT '분류명 2',
    
    -- 보험 코드
    insurance_code_a VARCHAR(20) COMMENT 'a 보험코드',
    insurance_code VARCHAR(20) COMMENT '보험코드',
    
    -- 제품 정보
    product_name VARCHAR(200) COMMENT '제품명',
    ingredient_code VARCHAR(20) COMMENT '주성분코드',
    ingredient_name_en VARCHAR(200) COMMENT '주성분명(영문)',
    
    -- 가격 정보
    drug_price DECIMAL(10,2) COMMENT '약가',
    decided_price DECIMAL(10,2) COMMENT '결정할 약가',
    june_decided_price DECIMAL(10,2) COMMENT '6월 결정할약가',
    
    -- 분류 및 급여
    professional_general VARCHAR(10) COMMENT '전문/일반',
    insurance_info VARCHAR(10) COMMENT '땡겨올 급여정보',
    coverage VARCHAR(10) COMMENT '급여',
    bioequivalence_clinical TEXT COMMENT '생동/임상',
    own_consignment VARCHAR(20) COMMENT '자사/위탁',
    
    -- 대조약 정보
    reference_drug VARCHAR(200) COMMENT '대조약',
    reference_drug_confirm VARCHAR(100) COMMENT '대조약 확인',
    reference_drug_code VARCHAR(20) COMMENT '대조약_코드',
    reference_drug_2 VARCHAR(200) COMMENT '대조약 2',
    pharmaceutical_company VARCHAR(100) COMMENT '제약사',
    reference_drug_note TEXT COMMENT '대조약 비고',
    
    -- 표준 코드
    item_standard_code VARCHAR(20) COMMENT '품목기준코드',
    representative_code VARCHAR(20) COMMENT '대표코드',
    standard_code VARCHAR(20) COMMENT '표준코드',
    atc_code VARCHAR(20) COMMENT 'ATC코드',
    
    -- 기타 정보
    image_filename VARCHAR(100) COMMENT '이미지 파일명',
    note TEXT COMMENT '비고',
    
    -- 성분 및 제형
    content DECIMAL(10,3) COMMENT '함량',
    unit VARCHAR(10) COMMENT '단위',
    drug_specification VARCHAR(100) COMMENT '약품규격',
    formulation_code VARCHAR(10) COMMENT '제형구분코드',
    formulation VARCHAR(50) COMMENT '제형',
    administration VARCHAR(20) COMMENT '투여',
    
    -- 허가 정보
    product_list_yn CHAR(1) DEFAULT 'N' COMMENT '의약품등제품정보목록유무',
    permit_info_yn CHAR(1) DEFAULT 'N' COMMENT '의약품제품허가정보',
    
    -- 작업 정보
    work_note TEXT COMMENT '(작업용)비고',
    commission_rate DECIMAL(5,4) COMMENT '수수료율',
    
    -- 추가 보험 정보
    index_code VARCHAR(20) COMMENT '인덱스용',
    insurance_code_2 VARCHAR(20) COMMENT '보험코드 2',
    mr_company VARCHAR(100) COMMENT '제약사 (MR 노출용)',
    insurance_code_3 VARCHAR(20) COMMENT '보험코드 3',
    product_name_2 VARCHAR(200) COMMENT '제품명 2',
    ingredient_code_2 VARCHAR(20) COMMENT '성분코드',
    drug_price_2 DECIMAL(10,2) COMMENT '약가 2',
    our_commission_rate DECIMAL(5,4) COMMENT '당사수수료율',
    coverage_yn VARCHAR(10) COMMENT '급여여부',
    reference_medicine VARCHAR(200) COMMENT '대조의약품',
    bioequivalence_yn VARCHAR(10) COMMENT '생동여부',
    note_category VARCHAR(20) COMMENT '비고구분',
    note_detail TEXT COMMENT 'note (비고)',
    sales_status VARCHAR(20) COMMENT '판매상태',
    
    -- 시스템 정보
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스
    INDEX idx_product_name (product_name),
    INDEX idx_company_name (company_name),
    INDEX idx_insurance_code (insurance_code),
    INDEX idx_classification (classification_code_1, classification_name_1),
    INDEX idx_ingredient_name (ingredient_name_en),
    INDEX idx_atc_code (atc_code),
    INDEX idx_coverage (coverage),
    INDEX idx_cso_product (cso_product),
    INDEX idx_formulation (formulation_code, formulation),
    
    -- 풀텍스트 검색 인덱스
    FULLTEXT idx_fulltext_search (product_name, company_name, classification_name_1, ingredient_name_en, note)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='의약품 정보 테이블';

-- 업로드 로그 테이블
CREATE TABLE IF NOT EXISTS medical_products_upload_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) COMMENT '업로드 파일명',
    total_rows INT COMMENT '총 행 수',
    success_rows INT COMMENT '성공 행 수',
    failed_rows INT COMMENT '실패 행 수',
    start_time TIMESTAMP COMMENT '시작 시간',
    end_time TIMESTAMP COMMENT '종료 시간',
    status ENUM('processing', 'completed', 'failed') DEFAULT 'processing' COMMENT '상태',
    error_message TEXT COMMENT '오류 메시지',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='의약품 업로드 로그'; 