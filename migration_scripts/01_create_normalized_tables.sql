-- ====================================================================
-- 의료기관 데이터베이스 정규화 스크립트 1단계: 테이블 생성
-- 작성일: 2025-06-23
-- 목적: im_medical_institutions 테이블을 정규화된 4개 테이블로 분리
-- ====================================================================

USE htest;

-- 기존 외래키 제약조건 확인 및 제거 (필요시)
SET FOREIGN_KEY_CHECKS = 0;

-- ====================================================================
-- 1. medical_institution - 기본 정보 테이블
-- ====================================================================
DROP TABLE IF EXISTS medical_institution;

CREATE TABLE medical_institution (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '의료기관 고유 ID',
    encrypted_code VARCHAR(500) UNIQUE NOT NULL COMMENT '암호화된 기관 코드',
    institution_name VARCHAR(200) NOT NULL COMMENT '기관명',
    category_code VARCHAR(10) COMMENT '종별코드',
    category_name VARCHAR(50) COMMENT '종별명',
    sido_code VARCHAR(10) COMMENT '시도코드',
    sido_name VARCHAR(50) COMMENT '시도명',
    sigungu_code VARCHAR(10) COMMENT '시군구코드',
    sigungu_name VARCHAR(50) COMMENT '시군구명',
    eupmyeondong VARCHAR(100) COMMENT '읍면동',
    postal_code VARCHAR(10) COMMENT '우편번호',
    address TEXT COMMENT '주소',
    phone_number VARCHAR(20) COMMENT '전화번호',
    homepage_url VARCHAR(500) COMMENT '홈페이지 URL',
    establishment_date DATE COMMENT '개설일',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 생성
    INDEX idx_institution_name (institution_name),
    INDEX idx_category_code (category_code),
    INDEX idx_sido_code (sido_code),
    INDEX idx_sigungu_code (sigungu_code),
    INDEX idx_establishment_date (establishment_date),
    INDEX idx_encrypted_code (encrypted_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='의료기관 기본정보';

-- ====================================================================
-- 2. medical_institution_facility - 시설 정보 테이블
-- ====================================================================
DROP TABLE IF EXISTS medical_institution_facility;

CREATE TABLE medical_institution_facility (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '시설 고유 ID',
    institution_id INT NOT NULL COMMENT '의료기관 ID (외래키)',
    location_x DECIMAL(15,10) COMMENT 'X 좌표 (경도)',
    location_y DECIMAL(15,10) COMMENT 'Y 좌표 (위도)',
    facility_type ENUM('main', 'branch', 'clinic', 'other') DEFAULT 'main' COMMENT '시설 유형',
    building_info TEXT COMMENT '건물 정보',
    parking_spaces INT DEFAULT 0 COMMENT '주차 공간 수',
    accessibility_features TEXT COMMENT '접근성 시설 정보',
    floor_count INT COMMENT '층수',
    total_area DECIMAL(10,2) COMMENT '총 면적 (㎡)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 외래키 제약조건
    FOREIGN KEY (institution_id) REFERENCES medical_institution(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- 인덱스 생성
    INDEX idx_location (location_x, location_y),
    INDEX idx_facility_type (facility_type),
    INDEX idx_institution_id (institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='의료기관 시설정보';

-- ====================================================================
-- 3. medical_institution_hospital - 병원 세부 정보 테이블
-- ====================================================================
DROP TABLE IF EXISTS medical_institution_hospital;

CREATE TABLE medical_institution_hospital (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '병원정보 고유 ID',
    institution_id INT NOT NULL COMMENT '의료기관 ID (외래키)',
    total_doctors INT DEFAULT 0 COMMENT '총 의사 수',
    total_beds INT DEFAULT 0 COMMENT '총 병상 수',
    emergency_room BOOLEAN DEFAULT FALSE COMMENT '응급실 운영 여부',
    icu_beds INT DEFAULT 0 COMMENT 'ICU 병상 수',
    operating_rooms INT DEFAULT 0 COMMENT '수술실 수',
    hospital_grade ENUM('tertiary', 'secondary', 'primary', 'clinic', 'unknown') DEFAULT 'clinic' COMMENT '병원 등급',
    accreditation_status VARCHAR(50) COMMENT '인증 상태',
    specialties_offered TEXT COMMENT '제공 진료과목',
    is_teaching_hospital BOOLEAN DEFAULT FALSE COMMENT '교육병원 여부',
    research_facilities BOOLEAN DEFAULT FALSE COMMENT '연구시설 보유 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 외래키 제약조건
    FOREIGN KEY (institution_id) REFERENCES medical_institution(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- 인덱스 생성
    INDEX idx_total_doctors (total_doctors),
    INDEX idx_total_beds (total_beds),
    INDEX idx_hospital_grade (hospital_grade),
    INDEX idx_emergency_room (emergency_room),
    INDEX idx_institution_id (institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='의료기관 병원상세정보';

-- ====================================================================
-- 4. medical_institution_specialty - 진료과목 정보 테이블
-- ====================================================================
DROP TABLE IF EXISTS medical_institution_specialty;

CREATE TABLE medical_institution_specialty (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '진료과목 고유 ID',
    institution_id INT NOT NULL COMMENT '의료기관 ID (외래키)',
    specialty_type ENUM(
        'general_medicine', 'medicine_intern', 'medicine_resident', 'medicine_specialist',
        'dental_general', 'dental_intern', 'dental_resident', 'dental_specialist',
        'oriental_general', 'oriental_intern', 'oriental_resident', 'oriental_specialist',
        'midwives', 'nursing', 'pharmacy', 'other'
    ) NOT NULL COMMENT '진료과목 유형',
    doctor_count INT DEFAULT 0 COMMENT '해당 과목 의사 수',
    department_name VARCHAR(100) COMMENT '진료과명',
    is_active BOOLEAN DEFAULT TRUE COMMENT '운영 중 여부',
    service_hours VARCHAR(100) COMMENT '진료 시간',
    notes TEXT COMMENT '비고',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 외래키 제약조건
    FOREIGN KEY (institution_id) REFERENCES medical_institution(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- 유니크 제약조건 (한 기관에서 같은 진료과목은 하나만)
    UNIQUE KEY unique_institution_specialty (institution_id, specialty_type),
    
    -- 인덱스 생성
    INDEX idx_specialty_type (specialty_type),
    INDEX idx_doctor_count (doctor_count),
    INDEX idx_is_active (is_active),
    INDEX idx_institution_id (institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='의료기관 진료과목정보';

-- ====================================================================
-- 5. 추가 유용한 테이블: medical_institution_contact - 연락처 정보
-- ====================================================================
DROP TABLE IF EXISTS medical_institution_contact;

CREATE TABLE medical_institution_contact (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '연락처 고유 ID',
    institution_id INT NOT NULL COMMENT '의료기관 ID (외래키)',
    contact_type ENUM('main', 'emergency', 'fax', 'appointment', 'other') DEFAULT 'main' COMMENT '연락처 유형',
    contact_value VARCHAR(100) NOT NULL COMMENT '연락처 값',
    description VARCHAR(200) COMMENT '설명',
    is_primary BOOLEAN DEFAULT FALSE COMMENT '대표 연락처 여부',
    is_active BOOLEAN DEFAULT TRUE COMMENT '사용 중 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 외래키 제약조건
    FOREIGN KEY (institution_id) REFERENCES medical_institution(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- 인덱스 생성
    INDEX idx_contact_type (contact_type),
    INDEX idx_is_primary (is_primary),
    INDEX idx_institution_id (institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='의료기관 연락처정보';

-- 외래키 제약조건 다시 활성화
SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- 6. 뷰 생성: 전체 의료기관 정보 조회용
-- ====================================================================
DROP VIEW IF EXISTS v_medical_institution_full;

CREATE VIEW v_medical_institution_full AS
SELECT 
    mi.id,
    mi.encrypted_code,
    mi.institution_name,
    mi.category_code,
    mi.category_name,
    mi.sido_code,
    mi.sido_name,
    mi.sigungu_code,
    mi.sigungu_name,
    mi.eupmyeondong,
    mi.postal_code,
    mi.address,
    mi.phone_number,
    mi.homepage_url,
    mi.establishment_date,
    
    -- 시설 정보
    mif.location_x,
    mif.location_y,
    mif.facility_type,
    mif.parking_spaces,
    
    -- 병원 정보
    mih.total_doctors,
    mih.total_beds,
    mih.emergency_room,
    mih.hospital_grade,
    
    -- 진료과목 수 집계
    (SELECT COUNT(*) FROM medical_institution_specialty mis 
     WHERE mis.institution_id = mi.id AND mis.is_active = TRUE) as active_specialties_count,
    
    -- 총 의사 수 집계 (진료과목별 합산)
    (SELECT IFNULL(SUM(mis.doctor_count), 0) FROM medical_institution_specialty mis 
     WHERE mis.institution_id = mi.id AND mis.is_active = TRUE) as total_specialty_doctors,
    
    mi.created_at,
    mi.updated_at
FROM medical_institution mi
LEFT JOIN medical_institution_facility mif ON mi.id = mif.institution_id
LEFT JOIN medical_institution_hospital mih ON mi.id = mih.institution_id;

-- ====================================================================
-- 7. 완료 메시지
-- ====================================================================
SELECT 'Database normalization tables created successfully!' as message;
SELECT 'Tables created:' as info, 
       'medical_institution, medical_institution_facility, medical_institution_hospital, medical_institution_specialty, medical_institution_contact' as tables;
SELECT 'Next step: Run 02_migrate_data.sql to migrate existing data' as next_step; 