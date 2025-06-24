<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 배치 작업 설정 파일
 * 
 * @description 배치 작업에서 사용되는 각종 설정값들을 정의
 * @author Parmderi Development Team
 * @version 1.0
 */

// ================================================
// 일반 배치 설정
// ================================================

// 로그 보관 기간 (일)
define('BATCH_LOG_RETENTION_DAYS', 30);

// 백업 보관 기간 (일)
define('BATCH_BACKUP_RETENTION_DAYS', 30);

// 업로드 파일 보관 기간 (일)
define('BATCH_UPLOAD_RETENTION_DAYS', 30);

// 배치 실행 시간 제한 (초, 0 = 무제한)
define('BATCH_TIME_LIMIT', 0);

// 메모리 제한 (MB, 0 = 시스템 기본값 사용)
define('BATCH_MEMORY_LIMIT', 512);

// ================================================
// 의료기관 데이터 마이그레이션 설정
// ================================================

// 한 번에 처리할 레코드 수 (배치 크기)
define('MEDICAL_MIGRATION_BATCH_SIZE', 100);

// 진행률 표시 간격 (레코드 수)
define('MEDICAL_MIGRATION_PROGRESS_INTERVAL', 100);

// 마이그레이션 전 백업 실행 여부
define('MEDICAL_MIGRATION_AUTO_BACKUP', true);

// 마이그레이션 완료 후 알림 이메일 (빈 문자열이면 전송 안 함)
define('MEDICAL_MIGRATION_NOTIFICATION_EMAIL', '');

// ================================================
// 데이터베이스 백업 설정
// ================================================

// 백업 파일 압축 여부
define('DB_BACKUP_COMPRESS', true);

// 백업 파일 접두사
define('DB_BACKUP_PREFIX', 'parmderi_backup_');

// 백업 디렉토리 (절대 경로 또는 FCPATH 기준 상대 경로)
define('DB_BACKUP_DIR', FCPATH . 'backups/');

// Docker 컨테이너 이름
define('DB_DOCKER_CONTAINER', 'parmderi_mariadb');

// ================================================
// 파일 정리 설정
// ================================================

// 엑셀 업로드 파일 정리 설정
$config['cleanup_excel'] = array(
    'directory' => FCPATH . 'uploads/excel/',
    'retention_days' => BATCH_UPLOAD_RETENTION_DAYS,
    'file_pattern' => '*',
    'enabled' => true
);

// 로그 파일 정리 설정
$config['cleanup_logs'] = array(
    'directories' => array(
        APPPATH . 'logs/',
        FCPATH . 'logs/migration/'
    ),
    'retention_days' => BATCH_LOG_RETENTION_DAYS,
    'file_patterns' => array('*.log', '*.php'),
    'enabled' => true
);

// 백업 파일 정리 설정
$config['cleanup_backups'] = array(
    'directory' => DB_BACKUP_DIR,
    'retention_days' => BATCH_BACKUP_RETENTION_DAYS,
    'file_pattern' => DB_BACKUP_PREFIX . '*.sql*',
    'enabled' => true
);

// ================================================
// 알림 설정
// ================================================

// 이메일 알림 설정
$config['email_notification'] = array(
    'enabled' => false,
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_user' => '',
    'smtp_pass' => '',
    'from_email' => 'system@parmderi.com',
    'from_name' => 'Parmderi 배치 시스템',
    'admin_email' => '', // 관리자 이메일
    'error_notification' => true, // 오류 발생 시 알림
    'success_notification' => false // 성공 시 알림
);

// Slack 알림 설정 (향후 확장용)
$config['slack_notification'] = array(
    'enabled' => false,
    'webhook_url' => '',
    'channel' => '#batch-alerts',
    'username' => 'Parmderi Bot'
);

// ================================================
// 성능 모니터링 설정
// ================================================

// 성능 모니터링 설정
$config['performance_monitoring'] = array(
    'enabled' => true,
    'memory_threshold' => 400, // MB
    'execution_time_threshold' => 1800, // 초 (30분)
    'disk_usage_threshold' => 90 // 퍼센트
);

// ================================================
// 보안 설정
// ================================================

// 배치 실행 권한 설정
$config['security'] = array(
    'allowed_ips' => array(), // 빈 배열이면 모든 IP 허용
    'require_authentication' => false,
    'log_access_attempts' => true
);

// ================================================
// 헬퍼 함수들
// ================================================

/**
 * 배치 설정값 조회
 */
function get_batch_config($key, $default = null)
{
    global $config;
    
    if (isset($config[$key])) {
        return $config[$key];
    }
    
    return $default;
}

/**
 * 배치 실행 환경 초기화
 */
function init_batch_environment()
{
    // 시간 제한 설정
    if (BATCH_TIME_LIMIT > 0) {
        set_time_limit(BATCH_TIME_LIMIT);
    } else {
        set_time_limit(0); // 무제한
    }
    
    // 메모리 제한 설정
    if (BATCH_MEMORY_LIMIT > 0) {
        ini_set('memory_limit', BATCH_MEMORY_LIMIT . 'M');
    }
    
    // 오류 리포팅 설정
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    
    // 타임존 설정
    if (function_exists('date_default_timezone_set')) {
        date_default_timezone_set('Asia/Seoul');
    }
}

/**
 * 배치 디렉토리 확인 및 생성
 */
function ensure_batch_directories()
{
    $directories = array(
        APPPATH . 'logs/',
        FCPATH . 'logs/migration/',
        DB_BACKUP_DIR,
        FCPATH . 'uploads/excel/'
    );
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/**
 * 배치 실행 권한 확인
 */
function check_batch_permission()
{
    $security_config = get_batch_config('security', array());
    
    // IP 제한 확인
    if (!empty($security_config['allowed_ips'])) {
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!in_array($client_ip, $security_config['allowed_ips'])) {
            throw new Exception('접근이 거부되었습니다. IP: ' . $client_ip);
        }
    }
    
    // CLI 환경에서만 실행 가능
    if (!function_exists('is_cli') || !is_cli()) {
        throw new Exception('배치 작업은 CLI 환경에서만 실행할 수 있습니다.');
    }
    
    return true;
}

// ================================================
// 배치 작업별 상세 설정
// ================================================

// 의료기관 마이그레이션 테이블 매핑
$config['medical_migration_mapping'] = array(
    'source_table' => 'im_medical_institutions',
    'target_tables' => array(
        'medical_institution' => array(
            'fields' => array(
                'encrypted_code', 'institution_name', 'category_code', 'category_name',
                'sido_code', 'sido_name', 'sigungu_code', 'sigungu_name',
                'eupmyeondong', 'postal_code', 'address', 'phone_number',
                'homepage_url', 'establishment_date', 'created_at', 'updated_at'
            )
        ),
        'medical_institution_facility' => array(
            'fields' => array('location_x', 'location_y'),
            'condition' => 'location_x IS NOT NULL OR location_y IS NOT NULL'
        ),
        'medical_institution_hospital' => array(
            'fields' => array('total_doctors'),
            'condition' => 'total_doctors > 0'
        ),
        'medical_institution_specialty' => array(
            'fields' => array(
                'general_medicine_doctors', 'medicine_intern_doctors',
                'medicine_resident_doctors', 'medicine_specialist_doctors',
                'dental_general_doctors', 'dental_intern_doctors',
                'dental_resident_doctors', 'dental_specialist_doctors',
                'oriental_general_doctors', 'oriental_intern_doctors',
                'oriental_resident_doctors', 'oriental_specialist_doctors',
                'midwives'
            ),
            'condition' => 'any_field > 0'
        )
    )
);

// 배치 작업 스케줄 정의 (cron 형식)
$config['batch_schedules'] = array(
    'migrate_medical_data' => '0 2 * * *',        // 매일 오전 2시
    'cleanup_excel_files' => '0 3 * * 0',         // 매주 일요일 오전 3시
    'cleanup_logs' => '0 4 * * 0',                // 매주 일요일 오전 4시
    'backup_database' => '0 1 * * *'              // 매일 오전 1시
); 