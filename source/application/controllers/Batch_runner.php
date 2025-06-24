<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 배치 작업 실행 컨트롤러 (CLI 전용)
 * 
 * @description CLI 환경에서 배치 작업을 실행하는 컨트롤러
 * @author Parmderi Development Team
 * @version 1.0
 * 
 * 사용법:
 * php index.php batch_runner migrate_medical_data
 * php index.php batch_runner cleanup_logs
 */
class Batch_runner extends CI_Controller {
    
    public function __construct()
    {
        parent::__construct();
        
        // CLI 환경에서만 실행 허용
        if (!is_cli()) {
            show_error('이 스크립트는 CLI 환경에서만 실행할 수 있습니다.', 403);
        }
        
        // 필요한 라이브러리 로드
        $this->load->database();
        $this->load->helper('file');
    }
    
    /**
     * 의료기관 데이터 마이그레이션 배치 실행
     */
    public function migrate_medical_data()
    {
        echo "=== 의료기관 데이터 마이그레이션 배치 시작 ===" . PHP_EOL;
        echo "시작 시간: " . date('Y-m-d H:i:s') . PHP_EOL;
        
        try {
            // 배치 클래스 로드
            require_once(APPPATH . 'batch/Medical_data_batch.php');
            
            // 배치 실행
            $batch = new Medical_data_batch();
            $result = $batch->execute();
            
            if ($result) {
                echo "배치 실행 완료: 성공" . PHP_EOL;
                exit(0);
            } else {
                echo "배치 실행 완료: 실패" . PHP_EOL;
                exit(1);
            }
            
        } catch (Exception $e) {
            echo "배치 실행 중 오류 발생: " . $e->getMessage() . PHP_EOL;
            exit(1);
        }
    }
    
    /**
     * 엑셀 업로드 파일 정리 배치
     */
    public function cleanup_excel_files()
    {
        echo "=== 엑셀 파일 정리 배치 시작 ===" . PHP_EOL;
        
        $upload_path = FCPATH . 'uploads/excel/';
        $cleanup_days = 30; // 30일 이상 된 파일 삭제
        
        if (!is_dir($upload_path)) {
            echo "업로드 폴더가 존재하지 않습니다: " . $upload_path . PHP_EOL;
            return;
        }
        
        $files = glob($upload_path . '*');
        $deleted_count = 0;
        $total_size = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $file_time = filemtime($file);
                $days_old = (time() - $file_time) / (60 * 60 * 24);
                
                if ($days_old > $cleanup_days) {
                    $file_size = filesize($file);
                    if (unlink($file)) {
                        $deleted_count++;
                        $total_size += $file_size;
                        echo "삭제됨: " . basename($file) . " (" . round($days_old, 1) . "일 전)" . PHP_EOL;
                    }
                }
            }
        }
        
        echo "정리 완료: " . $deleted_count . "개 파일, " . $this->format_bytes($total_size) . " 절약" . PHP_EOL;
    }
    
    /**
     * 로그 파일 정리 배치
     */
    public function cleanup_logs()
    {
        echo "=== 로그 파일 정리 배치 시작 ===" . PHP_EOL;
        
        $log_paths = array(
            APPPATH . 'logs/',
            FCPATH . 'logs/migration/'
        );
        
        $cleanup_days = 30; // 30일 이상 된 로그 삭제
        $total_deleted = 0;
        $total_size = 0;
        
        foreach ($log_paths as $log_path) {
            if (is_dir($log_path)) {
                echo "정리 중: " . $log_path . PHP_EOL;
                
                $files = glob($log_path . '*.{log,php}', GLOB_BRACE);
                
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $file_time = filemtime($file);
                        $days_old = (time() - $file_time) / (60 * 60 * 24);
                        
                        if ($days_old > $cleanup_days) {
                            $file_size = filesize($file);
                            if (unlink($file)) {
                                $total_deleted++;
                                $total_size += $file_size;
                                echo "삭제됨: " . basename($file) . " (" . round($days_old, 1) . "일 전)" . PHP_EOL;
                            }
                        }
                    }
                }
            }
        }
        
        echo "로그 정리 완료: " . $total_deleted . "개 파일, " . $this->format_bytes($total_size) . " 절약" . PHP_EOL;
    }
    
    /**
     * 데이터베이스 백업 배치
     */
    public function backup_database()
    {
        echo "=== 데이터베이스 백업 배치 시작 ===" . PHP_EOL;
        
        $backup_dir = FCPATH . 'backups/';
        
        // 백업 디렉토리 생성
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $db_config = $this->db->database;
        $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        $cmd = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s 2>&1',
            $db_config['hostname'],
            $db_config['username'],
            $db_config['password'],
            $db_config['database'],
            $backup_file
        );
        
        echo "백업 실행 중..." . PHP_EOL;
        exec($cmd, $output, $return_code);
        
        if ($return_code === 0 && file_exists($backup_file)) {
            $file_size = filesize($backup_file);
            echo "백업 성공: " . basename($backup_file) . " (" . $this->format_bytes($file_size) . ")" . PHP_EOL;
            
            // 30일 이상 된 백업 파일 정리
            $this->cleanup_old_backups($backup_dir, 30);
            
        } else {
            echo "백업 실패" . PHP_EOL;
            if (!empty($output)) {
                echo "오류: " . implode(PHP_EOL, $output) . PHP_EOL;
            }
        }
    }
    
    /**
     * 오래된 백업 파일 정리
     */
    private function cleanup_old_backups($backup_dir, $days)
    {
        $files = glob($backup_dir . 'backup_*.sql');
        $deleted_count = 0;
        
        foreach ($files as $file) {
            $file_time = filemtime($file);
            $days_old = (time() - $file_time) / (60 * 60 * 24);
            
            if ($days_old > $days) {
                if (unlink($file)) {
                    $deleted_count++;
                    echo "오래된 백업 삭제: " . basename($file) . PHP_EOL;
                }
            }
        }
        
        if ($deleted_count > 0) {
            echo "오래된 백업 " . $deleted_count . "개 파일 정리 완료" . PHP_EOL;
        }
    }
    
    /**
     * 배치 작업 상태 확인
     */
    public function status()
    {
        echo "=== 배치 작업 상태 확인 ===" . PHP_EOL;
        echo "현재 시간: " . date('Y-m-d H:i:s') . PHP_EOL;
        echo PHP_EOL;
        
        // 데이터베이스 연결 확인
        echo "데이터베이스 연결: ";
        try {
            $this->db->query("SELECT 1");
            echo "정상" . PHP_EOL;
        } catch (Exception $e) {
            echo "오류 - " . $e->getMessage() . PHP_EOL;
        }
        
        // 테이블 존재 확인
        $required_tables = array(
            'im_medical_institutions',
            'medical_institution',
            'medical_institution_facility', 
            'medical_institution_hospital',
            'medical_institution_specialty'
        );
        
        echo PHP_EOL . "필수 테이블 확인:" . PHP_EOL;
        foreach ($required_tables as $table) {
            echo "  " . $table . ": ";
            if ($this->db->table_exists($table)) {
                $count = $this->db->count_all($table);
                echo "존재 (" . number_format($count) . "개 레코드)" . PHP_EOL;
            } else {
                echo "존재하지 않음" . PHP_EOL;
            }
        }
        
        // 디스크 사용량 확인
        echo PHP_EOL . "디스크 사용량:" . PHP_EOL;
        $paths = array(
            '업로드 폴더' => FCPATH . 'uploads/',
            '로그 폴더' => APPPATH . 'logs/',
            '백업 폴더' => FCPATH . 'backups/'
        );
        
        foreach ($paths as $name => $path) {
            if (is_dir($path)) {
                $size = $this->get_directory_size($path);
                echo "  " . $name . ": " . $this->format_bytes($size) . PHP_EOL;
            } else {
                echo "  " . $name . ": 존재하지 않음" . PHP_EOL;
            }
        }
    }
    
    /**
     * 도움말 표시
     */
    public function help()
    {
        echo "=== 배치 작업 도움말 ===" . PHP_EOL;
        echo PHP_EOL;
        echo "사용 가능한 명령어:" . PHP_EOL;
        echo "  migrate_medical_data  - 의료기관 데이터 마이그레이션" . PHP_EOL;
        echo "  cleanup_excel_files   - 엑셀 업로드 파일 정리 (30일 이상)" . PHP_EOL;
        echo "  cleanup_logs          - 로그 파일 정리 (30일 이상)" . PHP_EOL;
        echo "  backup_database       - 데이터베이스 백업" . PHP_EOL;
        echo "  status                - 시스템 상태 확인" . PHP_EOL;
        echo "  help                  - 이 도움말 표시" . PHP_EOL;
        echo PHP_EOL;
        echo "실행 예시:" . PHP_EOL;
        echo "  php index.php batch_runner migrate_medical_data" . PHP_EOL;
        echo "  php index.php batch_runner cleanup_logs" . PHP_EOL;
        echo "  php index.php batch_runner status" . PHP_EOL;
    }
    
    /**
     * 기본 메소드 (도움말 표시)
     */
    public function index()
    {
        $this->help();
    }
    
    /**
     * 파일 크기를 사람이 읽기 쉬운 형태로 변환
     */
    private function format_bytes($size, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    /**
     * 디렉토리 크기 계산
     */
    private function get_directory_size($directory)
    {
        $size = 0;
        
        if (is_dir($directory)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($files as $file) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
} 