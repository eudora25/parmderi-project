-- ====================================================================
-- 의료기관 데이터베이스 정규화 스크립트 2단계: 데이터 마이그레이션
-- 작성일: 2025-06-23
-- 목적: im_medical_institutions 테이블의 데이터를 정규화된 테이블들로 이전
-- 대상 레코드: 78,236개
-- ====================================================================

USE htest;

-- 마이그레이션 시작 시간 기록
SET @migration_start = NOW();
SELECT CONCAT('Data migration started at: ', @migration_start) as migration_info;

-- 트랜잭션 시작 (안전한 마이그레이션을 위해)
START TRANSACTION;

-- ====================================================================
-- 1단계: medical_institution 테이블로 기본 정보 이전
-- ====================================================================
SELECT 'Step 1: Migrating basic institution information...' as current_step;

INSERT INTO medical_institution (
    encrypted_code,
    institution_name,
    category_code,
    category_name,
    sido_code,
    sido_name,
    sigungu_code,
    sigungu_name,
    eupmyeondong,
    postal_code,
    address,
    phone_number,
    homepage_url,
    establishment_date,
    created_at,
    updated_at
)
SELECT 
    encrypted_code,
    institution_name,
    category_code,
    category_name,
    sido_code,
    sido_name,
    sigungu_code,
    sigungu_name,
    eupmyeondong,
    postal_code,
    address,
    phone_number,
    homepage_url,
    establishment_date,
    created_at,
    updated_at
FROM im_medical_institutions
WHERE encrypted_code IS NOT NULL 
  AND institution_name IS NOT NULL
ORDER BY id;

-- 1단계 완료 확인
SET @step1_count = ROW_COUNT();
SELECT CONCAT('Step 1 completed: ', @step1_count, ' institutions migrated') as step1_result;

-- ====================================================================
-- 2단계: medical_institution_facility 테이블로 시설 정보 이전
-- ====================================================================
SELECT 'Step 2: Migrating facility information...' as current_step;

INSERT INTO medical_institution_facility (
    institution_id,
    location_x,
    location_y,
    facility_type,
    created_at,
    updated_at
)
SELECT 
    mi.id,
    imi.location_x,
    imi.location_y,
    'main' as facility_type,  -- 기본값으로 main 설정
    imi.created_at,
    imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE (imi.location_x IS NOT NULL OR imi.location_y IS NOT NULL);

-- 2단계 완료 확인
SET @step2_count = ROW_COUNT();
SELECT CONCAT('Step 2 completed: ', @step2_count, ' facilities migrated') as step2_result;

-- ====================================================================
-- 3단계: medical_institution_hospital 테이블로 병원 정보 이전
-- ====================================================================
SELECT 'Step 3: Migrating hospital information...' as current_step;

INSERT INTO medical_institution_hospital (
    institution_id,
    total_doctors,
    hospital_grade,
    created_at,
    updated_at
)
SELECT 
    mi.id,
    IFNULL(imi.total_doctors, 0) as total_doctors,
    CASE 
        WHEN imi.category_name LIKE '%상급종합%' THEN 'tertiary'
        WHEN imi.category_name LIKE '%종합병원%' THEN 'secondary'
        WHEN imi.category_name LIKE '%병원%' THEN 'primary'
        WHEN imi.category_name LIKE '%의원%' OR imi.category_name LIKE '%클리닉%' THEN 'clinic'
        ELSE 'unknown'
    END as hospital_grade,
    imi.created_at,
    imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.total_doctors > 0 
   OR imi.category_name IS NOT NULL;

-- 3단계 완료 확인
SET @step3_count = ROW_COUNT();
SELECT CONCAT('Step 3 completed: ', @step3_count, ' hospital records migrated') as step3_result;

-- ====================================================================
-- 4단계: medical_institution_specialty 테이블로 진료과목 정보 이전
-- ====================================================================
SELECT 'Step 4: Migrating specialty information...' as current_step;

-- 의과 일반의
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'general_medicine', imi.general_medicine_doctors, '일반의(의과)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.general_medicine_doctors > 0;

-- 의과 인턴
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'medicine_intern', imi.medicine_intern_doctors, '인턴(의과)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.medicine_intern_doctors > 0;

-- 의과 레지던트
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'medicine_resident', imi.medicine_resident_doctors, '레지던트(의과)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.medicine_resident_doctors > 0;

-- 의과 전문의
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'medicine_specialist', imi.medicine_specialist_doctors, '전문의(의과)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.medicine_specialist_doctors > 0;

-- 치과 일반의
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'dental_general', imi.dental_general_doctors, '일반의(치과)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.dental_general_doctors > 0;

-- 치과 인턴
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'dental_intern', imi.dental_intern_doctors, '인턴(치과)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.dental_intern_doctors > 0;

-- 치과 레지던트
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'dental_resident', imi.dental_resident_doctors, '레지던트(치과)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.dental_resident_doctors > 0;

-- 치과 전문의
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'dental_specialist', imi.dental_specialist_doctors, '전문의(치과)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.dental_specialist_doctors > 0;

-- 한방 일반의
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'oriental_general', imi.oriental_general_doctors, '일반의(한방)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.oriental_general_doctors > 0;

-- 한방 인턴
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'oriental_intern', imi.oriental_intern_doctors, '인턴(한방)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.oriental_intern_doctors > 0;

-- 한방 레지던트
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'oriental_resident', imi.oriental_resident_doctors, '레지던트(한방)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.oriental_resident_doctors > 0;

-- 한방 전문의
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'oriental_specialist', imi.oriental_specialist_doctors, '전문의(한방)', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.oriental_specialist_doctors > 0;

-- 조산사
INSERT INTO medical_institution_specialty (institution_id, specialty_type, doctor_count, department_name, created_at, updated_at)
SELECT mi.id, 'midwives', imi.midwives, '조산사', imi.created_at, imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.midwives > 0;

-- 4단계 완료 확인
SELECT COUNT(*) as total_specialties_migrated FROM medical_institution_specialty;

-- ====================================================================
-- 5단계: medical_institution_contact 테이블로 연락처 정보 이전
-- ====================================================================
SELECT 'Step 5: Migrating contact information...' as current_step;

INSERT INTO medical_institution_contact (
    institution_id,
    contact_type,
    contact_value,
    description,
    is_primary,
    created_at,
    updated_at
)
SELECT 
    mi.id,
    'main' as contact_type,
    imi.phone_number,
    '대표 전화번호' as description,
    TRUE as is_primary,
    imi.created_at,
    imi.updated_at
FROM im_medical_institutions imi
INNER JOIN medical_institution mi ON imi.encrypted_code = mi.encrypted_code
WHERE imi.phone_number IS NOT NULL 
  AND imi.phone_number != '';

-- 5단계 완료 확인
SET @step5_count = ROW_COUNT();
SELECT CONCAT('Step 5 completed: ', @step5_count, ' contact records migrated') as step5_result;

-- ====================================================================
-- 6단계: 데이터 무결성 검증
-- ====================================================================
SELECT 'Step 6: Validating data integrity...' as current_step;

-- 기본 테이블 레코드 수 확인
SELECT 
    (SELECT COUNT(*) FROM im_medical_institutions) as original_count,
    (SELECT COUNT(*) FROM medical_institution) as migrated_institutions,
    (SELECT COUNT(*) FROM medical_institution_facility) as migrated_facilities,
    (SELECT COUNT(*) FROM medical_institution_hospital) as migrated_hospitals,
    (SELECT COUNT(*) FROM medical_institution_specialty) as migrated_specialties,
    (SELECT COUNT(*) FROM medical_institution_contact) as migrated_contacts;

-- 외래키 무결성 검증
SELECT 'Checking foreign key integrity...' as integrity_check;

SELECT 
    'facility_foreign_keys' as check_type,
    COUNT(*) as valid_references
FROM medical_institution_facility mif
INNER JOIN medical_institution mi ON mif.institution_id = mi.id;

SELECT 
    'hospital_foreign_keys' as check_type,
    COUNT(*) as valid_references
FROM medical_institution_hospital mih
INNER JOIN medical_institution mi ON mih.institution_id = mi.id;

SELECT 
    'specialty_foreign_keys' as check_type,
    COUNT(*) as valid_references
FROM medical_institution_specialty mis
INNER JOIN medical_institution mi ON mis.institution_id = mi.id;

-- ====================================================================
-- 7단계: 마이그레이션 완료 및 통계
-- ====================================================================
SET @migration_end = NOW();
SELECT 
    CONCAT('Data migration completed at: ', @migration_end) as completion_time,
    CONCAT('Total duration: ', TIMESTAMPDIFF(SECOND, @migration_start, @migration_end), ' seconds') as duration;

-- 최종 통계
SELECT 'Migration Summary:' as summary;
SELECT 
    'medical_institution' as table_name,
    COUNT(*) as record_count,
    'Basic institution information' as description
FROM medical_institution
UNION ALL
SELECT 
    'medical_institution_facility' as table_name,
    COUNT(*) as record_count,
    'Facility and location data' as description
FROM medical_institution_facility
UNION ALL
SELECT 
    'medical_institution_hospital' as table_name,
    COUNT(*) as record_count,
    'Hospital specific information' as description
FROM medical_institution_hospital
UNION ALL
SELECT 
    'medical_institution_specialty' as table_name,
    COUNT(*) as record_count,
    'Medical specialties and doctor counts' as description
FROM medical_institution_specialty
UNION ALL
SELECT 
    'medical_institution_contact' as table_name,
    COUNT(*) as record_count,
    'Contact information' as description
FROM medical_institution_contact;

-- 트랜잭션 커밋
COMMIT;

SELECT 'Data migration completed successfully! All changes have been committed.' as final_message;
SELECT 'Next step: Run 03_validate_and_cleanup.sql to perform final validation and cleanup' as next_step; 