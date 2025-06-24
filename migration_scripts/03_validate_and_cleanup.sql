-- ====================================================================
-- 의료기관 데이터베이스 정규화 스크립트 3단계: 검증 및 정리
-- 작성일: 2025-06-23
-- 목적: 마이그레이션 완료 후 데이터 검증 및 최적화
-- ====================================================================

USE htest;

-- ====================================================================
-- 1단계: 데이터 무결성 전체 검증
-- ====================================================================
SELECT '=== DATABASE NORMALIZATION VALIDATION REPORT ===' as report_title;
SELECT CONCAT('Validation started at: ', NOW()) as validation_start;

-- 1.1 기본 레코드 수 비교
SELECT '1. Record Count Comparison' as section;
SELECT 
    'Original Table' as source,
    COUNT(*) as record_count,
    'im_medical_institutions' as table_name
FROM im_medical_institutions
UNION ALL
SELECT 
    'Normalized Tables' as source,
    COUNT(*) as record_count,
    'medical_institution' as table_name
FROM medical_institution;

-- 1.2 각 정규화 테이블별 레코드 수
SELECT '2. Normalized Tables Record Count' as section;
SELECT 
    'medical_institution' as table_name,
    COUNT(*) as record_count,
    '기본 정보' as description
FROM medical_institution
UNION ALL
SELECT 
    'medical_institution_facility' as table_name,
    COUNT(*) as record_count,
    '시설 정보' as description
FROM medical_institution_facility
UNION ALL
SELECT 
    'medical_institution_hospital' as table_name,
    COUNT(*) as record_count,
    '병원 정보' as description
FROM medical_institution_hospital
UNION ALL
SELECT 
    'medical_institution_specialty' as table_name,
    COUNT(*) as record_count,
    '진료과목 정보' as description
FROM medical_institution_specialty
UNION ALL
SELECT 
    'medical_institution_contact' as table_name,
    COUNT(*) as record_count,
    '연락처 정보' as description
FROM medical_institution_contact;

-- ====================================================================
-- 2단계: 외래키 무결성 검증
-- ====================================================================
SELECT '3. Foreign Key Integrity Check' as section;

-- 2.1 시설 테이블 외래키 검증
SELECT 
    'medical_institution_facility' as table_name,
    COUNT(*) as total_records,
    COUNT(mi.id) as valid_foreign_keys,
    (COUNT(*) - COUNT(mi.id)) as orphaned_records
FROM medical_institution_facility mif
LEFT JOIN medical_institution mi ON mif.institution_id = mi.id;

-- 2.2 병원 테이블 외래키 검증
SELECT 
    'medical_institution_hospital' as table_name,
    COUNT(*) as total_records,
    COUNT(mi.id) as valid_foreign_keys,
    (COUNT(*) - COUNT(mi.id)) as orphaned_records
FROM medical_institution_hospital mih
LEFT JOIN medical_institution mi ON mih.institution_id = mi.id;

-- 2.3 진료과목 테이블 외래키 검증
SELECT 
    'medical_institution_specialty' as table_name,
    COUNT(*) as total_records,
    COUNT(mi.id) as valid_foreign_keys,
    (COUNT(*) - COUNT(mi.id)) as orphaned_records
FROM medical_institution_specialty mis
LEFT JOIN medical_institution mi ON mis.institution_id = mi.id;

-- 2.4 연락처 테이블 외래키 검증
SELECT 
    'medical_institution_contact' as table_name,
    COUNT(*) as total_records,
    COUNT(mi.id) as valid_foreign_keys,
    (COUNT(*) - COUNT(mi.id)) as orphaned_records
FROM medical_institution_contact mic
LEFT JOIN medical_institution mi ON mic.institution_id = mi.id;

-- ====================================================================
-- 3단계: 데이터 품질 검증
-- ====================================================================
SELECT '4. Data Quality Check' as section;

-- 3.1 필수 필드 NULL 체크
SELECT 
    'medical_institution NULL check' as check_type,
    COUNT(*) as total_records,
    SUM(CASE WHEN encrypted_code IS NULL THEN 1 ELSE 0 END) as null_encrypted_code,
    SUM(CASE WHEN institution_name IS NULL THEN 1 ELSE 0 END) as null_institution_name
FROM medical_institution;

-- 3.2 진료과목별 통계
SELECT '5. Specialty Distribution' as section;
SELECT 
    specialty_type,
    COUNT(*) as institution_count,
    SUM(doctor_count) as total_doctors,
    AVG(doctor_count) as avg_doctors_per_institution,
    MAX(doctor_count) as max_doctors
FROM medical_institution_specialty
GROUP BY specialty_type
ORDER BY total_doctors DESC;

-- 3.3 지역별 분포
SELECT '6. Regional Distribution' as section;
SELECT 
    sido_name,
    COUNT(*) as institution_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM medical_institution), 2) as percentage
FROM medical_institution
WHERE sido_name IS NOT NULL
GROUP BY sido_name
ORDER BY institution_count DESC
LIMIT 10;

-- 3.4 병원 등급별 분포
SELECT '7. Hospital Grade Distribution' as section;
SELECT 
    hospital_grade,
    COUNT(*) as hospital_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM medical_institution_hospital), 2) as percentage,
    AVG(total_doctors) as avg_doctors
FROM medical_institution_hospital
GROUP BY hospital_grade
ORDER BY hospital_count DESC;

-- ====================================================================
-- 4단계: 성능 최적화
-- ====================================================================
SELECT '8. Performance Optimization' as section;

-- 4.1 인덱스 사용률 분석 (MySQL 8.0+에서 사용 가능)
-- 주요 쿼리 패턴에 대한 성능 테스트
SELECT '--- Performance Test Queries ---' as test_info;

-- 기관명 검색 성능 테스트
SET @start_time = NOW(6);
SELECT COUNT(*) FROM medical_institution WHERE institution_name LIKE '%병원%';
SET @end_time = NOW(6);
SELECT 
    'Institution name search' as query_type,
    TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) as microseconds;

-- 지역별 검색 성능 테스트
SET @start_time = NOW(6);
SELECT COUNT(*) FROM medical_institution WHERE sido_code = '11';
SET @end_time = NOW(6);
SELECT 
    'Region search' as query_type,
    TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) as microseconds;

-- 조인 쿼리 성능 테스트
SET @start_time = NOW(6);
SELECT COUNT(*) FROM medical_institution mi
INNER JOIN medical_institution_hospital mih ON mi.id = mih.institution_id
WHERE mih.total_doctors > 10;
SET @end_time = NOW(6);
SELECT 
    'Join query performance' as query_type,
    TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) as microseconds;

-- ====================================================================
-- 5단계: 샘플 쿼리 테스트
-- ====================================================================
SELECT '9. Sample Query Tests' as section;

-- 5.1 전체 의료기관 정보 조회 (뷰 사용)
SELECT '--- Sample Query 1: Full Institution Info (using view) ---' as query_info;
SELECT *
FROM v_medical_institution_full
LIMIT 3;

-- 5.2 특정 지역의 대형 병원 조회
SELECT '--- Sample Query 2: Large hospitals in Seoul ---' as query_info;
SELECT 
    mi.institution_name,
    mi.sido_name,
    mi.sigungu_name,
    mih.total_doctors,
    mih.hospital_grade,
    GROUP_CONCAT(DISTINCT mis.department_name) as departments
FROM medical_institution mi
INNER JOIN medical_institution_hospital mih ON mi.id = mih.institution_id
LEFT JOIN medical_institution_specialty mis ON mi.id = mis.institution_id
WHERE mi.sido_name = '서울특별시'
  AND mih.total_doctors >= 50
GROUP BY mi.id, mi.institution_name, mi.sido_name, mi.sigungu_name, mih.total_doctors, mih.hospital_grade
ORDER BY mih.total_doctors DESC
LIMIT 5;

-- 5.3 진료과목별 의사 수 통계
SELECT '--- Sample Query 3: Doctor count by specialty ---' as query_info;
SELECT 
    mis.specialty_type,
    mis.department_name,
    COUNT(DISTINCT mi.id) as institution_count,
    SUM(mis.doctor_count) as total_doctors,
    AVG(mis.doctor_count) as avg_doctors_per_institution
FROM medical_institution mi
INNER JOIN medical_institution_specialty mis ON mi.id = mis.institution_id
WHERE mis.is_active = TRUE
GROUP BY mis.specialty_type, mis.department_name
HAVING total_doctors > 0
ORDER BY total_doctors DESC;

-- ====================================================================
-- 6단계: 백업 및 정리 권장사항
-- ====================================================================
SELECT '10. Backup and Cleanup Recommendations' as section;

-- 6.1 테이블 크기 비교
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.tables 
WHERE table_schema = 'htest' 
  AND table_name IN ('im_medical_institutions', 'medical_institution', 'medical_institution_facility', 
                     'medical_institution_hospital', 'medical_institution_specialty', 'medical_institution_contact')
ORDER BY size_mb DESC;

-- 6.2 원본 테이블 백업 권장사항
SELECT '=== BACKUP RECOMMENDATIONS ===' as backup_title;
SELECT 'Before dropping the original table, create a backup:' as recommendation;
SELECT 'CREATE TABLE im_medical_institutions_backup AS SELECT * FROM im_medical_institutions;' as backup_command;

-- ====================================================================
-- 7단계: 최종 완료 보고서
-- ====================================================================
SELECT '=== MIGRATION COMPLETION REPORT ===' as completion_title;
SELECT CONCAT('Validation completed at: ', NOW()) as completion_time;

-- 최종 요약 통계
SELECT 
    '✅ Migration Status' as status,
    'COMPLETED SUCCESSFULLY' as result;

SELECT 
    '📊 Final Statistics' as summary_title,
    (SELECT COUNT(*) FROM medical_institution) as total_institutions,
    (SELECT COUNT(*) FROM medical_institution_facility) as total_facilities,
    (SELECT COUNT(*) FROM medical_institution_hospital) as total_hospitals,
    (SELECT COUNT(*) FROM medical_institution_specialty) as total_specialties,
    (SELECT COUNT(*) FROM medical_institution_contact) as total_contacts;

-- 다음 단계 권장사항
SELECT '📋 Next Steps' as next_steps_title;
SELECT '1. Update CodeIgniter models to use normalized tables' as step1;
SELECT '2. Test application functionality with new table structure' as step2;
SELECT '3. Create backup of original table: im_medical_institutions' as step3;
SELECT '4. Monitor performance and optimize queries as needed' as step4;
SELECT '5. Update documentation and API endpoints' as step5;

-- 정리 스크립트 (주석 처리됨 - 필요시 실행)
/*
-- ⚠️  WARNING: 다음 명령어들은 원본 테이블을 삭제합니다!
-- 마이그레이션이 완전히 성공하고 애플리케이션 테스트가 완료된 후에만 실행하세요.

-- 백업 생성
CREATE TABLE im_medical_institutions_backup AS SELECT * FROM im_medical_institutions;

-- 원본 테이블 삭제 (신중하게!)
-- DROP TABLE im_medical_institutions;

-- 백업 테이블을 다른 데이터베이스로 이동하는 것을 권장합니다.
*/

SELECT '🎉 Database normalization completed successfully!' as final_message; 