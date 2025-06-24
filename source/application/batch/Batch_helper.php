<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 배치 작업 헬퍼 클래스
 * 
 * @description 배치 작업에서 공통으로 사용되는 유틸리티 기능 제공
 * @author Parmderi Development Team
 * @version 1.0
 */
class Batch_helper {
    
    private $log_file;
    private $start_time;
    
    public function __construct($log_prefix = 'batch')
    {
        $this->log_file = APPPATH . 'logs/' . $log_prefix . '_' . date('Y-m-d_H-i-s') . '.log';
        $this->start_time = microtime(true);
        
        // 로그 디렉토리 확인 및 생성
        $log_dir = dirname($this->log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }
    
    /**
     * 로그 메시지 기록
     */
    public function log($message, $level = 'INFO')
    {
        $log_message = "[" . date('Y-m-d H:i:s') . "] [" . $level . "] " . $message . PHP_EOL;
        
        // 파일에 기록
        file_put_contents($this->log_file, $log_message, FILE_APPEND | LOCK_EX);
        
        // CLI 환경에서는 콘솔에도 출력
        if (function_exists('is_cli') && is_cli()) {
            echo $log_message;
        }
    }
    
    /**
     * 오류 로그 기록
     */
    public function log_error($message)
    {
        $this->log($message, 'ERROR');
    }
    
    /**
     * 경고 로그 기록
     */
    public function log_warning($message)
    {
        $this->log($message, 'WARNING');
    }
    
    /**
     * 실행 시간 측정
     */
    public function get_execution_time()
    {
        return round(microtime(true) - $this->start_time, 2);
    }
    
    /**
     * 메모리 사용량 조회
     */
    public function get_memory_usage()
    {
        return array(
            'current' => $this->format_bytes(memory_get_usage()),
            'peak' => $this->format_bytes(memory_get_peak_usage())
        );
    }
    
    /**
     * 파일 크기를 사람이 읽기 쉬운 형태로 변환
     */
    public function format_bytes($size, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    /**
     * 진행률 표시
     */
    public function show_progress($current, $total, $message = '')
    {
        $percent = round(($current / $total) * 100, 2);
        $progress_message = "진행률: " . $percent . "% (" . $current . "/" . $total . ")";
        
        if (!empty($message)) {
            $progress_message .= " - " . $message;
        }
        
        $this->log($progress_message);
    }
    
    /**
     * 데이터베이스 백업 실행
     */
    public function backup_database($db_config, $backup_dir)
    {
        // 백업 디렉토리 생성
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Docker 환경에서 mysqldump 실행
        $cmd = sprintf(
            'docker exec parmderi_mariadb mariadb-dump -u%s -p%s %s > %s 2>&1',
            escapeshellarg($db_config['username']),
            escapeshellarg($db_config['password']),
            escapeshellarg($db_config['database']),
            escapeshellarg($backup_file)
        );
        
        $this->log("데이터베이스 백업 시작: " . basename($backup_file));
        
        exec($cmd, $output, $return_code);
        
        if ($return_code === 0 && file_exists($backup_file) && filesize($backup_file) > 0) {
            $file_size = filesize($backup_file);
            $this->log("백업 성공: " . $this->format_bytes($file_size));
            return $backup_file;
        } else {
            $this->log_error("백업 실패: " . implode(PHP_EOL, $output));
            return false;
        }
    }
    
    /**
     * 오래된 파일 정리
     */
    public function cleanup_old_files($directory, $pattern, $days_old)
    {
        if (!is_dir($directory)) {
            $this->log_warning("디렉토리가 존재하지 않습니다: " . $directory);
            return 0;
        }
        
        $files = glob($directory . '/' . $pattern);
        $deleted_count = 0;
        $total_size = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $file_time = filemtime($file);
                $days_diff = (time() - $file_time) / (60 * 60 * 24);
                
                if ($days_diff > $days_old) {
                    $file_size = filesize($file);
                    if (unlink($file)) {
                        $deleted_count++;
                        $total_size += $file_size;
                        $this->log("삭제됨: " . basename($file) . " (" . round($days_diff, 1) . "일 전)");
                    }
                }
            }
        }
        
        if ($deleted_count > 0) {
            $this->log("파일 정리 완료: " . $deleted_count . "개 파일, " . $this->format_bytes($total_size) . " 절약");
        }
        
        return $deleted_count;
    }
    
    /**
     * 시스템 상태 확인
     */
    public function check_system_status()
    {
        $status = array();
        
        // 메모리 사용량
        $memory = $this->get_memory_usage();
        $status['memory'] = $memory;
        
        // 디스크 사용량
        $disk_free = disk_free_space(FCPATH);
        $disk_total = disk_total_space(FCPATH);
        $disk_usage_percent = round((($disk_total - $disk_free) / $disk_total) * 100, 2);
        
        $status['disk'] = array(
            'free' => $this->format_bytes($disk_free),
            'total' => $this->format_bytes($disk_total),
            'usage_percent' => $disk_usage_percent
        );
        
        // 로드 평균 (Linux/Unix만)
        if (function_exists('sys_getloadavg')) {
            $status['load_average'] = sys_getloadavg();
        }
        
        return $status;
    }
    
    /**
     * 배치 실행 결과 요약
     */
    public function write_summary($processed_count, $error_count, $additional_info = array())
    {
        $execution_time = $this->get_execution_time();
        $memory = $this->get_memory_usage();
        
        $this->log("=== 배치 실행 완료 ===");
        $this->log("처리된 레코드 수: " . number_format($processed_count));
        $this->log("오류 발생 수: " . number_format($error_count));
        $this->log("실행 시간: " . $execution_time . "초");
        
        if ($processed_count > 0) {
            $this->log("평균 처리 속도: " . round($processed_count / $execution_time, 2) . "개/초");
        }
        
        $this->log("메모리 사용량: " . $memory['current'] . " (최대: " . $memory['peak'] . ")");
        
        // 추가 정보 기록
        foreach ($additional_info as $key => $value) {
            $this->log($key . ": " . $value);
        }
        
        $this->log("로그 파일: " . $this->log_file);
    }
    
    /**
     * 배치 실행 환경 정보 기록
     */
    public function log_environment_info()
    {
        $this->log("=== 실행 환경 정보 ===");
        $this->log("PHP 버전: " . phpversion());
        $this->log("운영체제: " . php_uname());
        $this->log("메모리 제한: " . ini_get('memory_limit'));
        $this->log("최대 실행 시간: " . ini_get('max_execution_time'));
        $this->log("시작 시간: " . date('Y-m-d H:i:s', $this->start_time));
        
        // 시스템 상태
        $status = $this->check_system_status();
        $this->log("현재 메모리 사용량: " . $status['memory']['current']);
        $this->log("디스크 사용률: " . $status['disk']['usage_percent'] . "%");
    }
    
    /**
     * 이메일 알림 (선택사항)
     */
    public function send_notification($subject, $message, $email = null)
    {
        // 기본 이메일 설정이 있다면 알림 전송
        if (!empty($email) && function_exists('mail')) {
            $headers = "From: system@parmderi.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            $full_message = "배치 실행 결과 알림\n\n";
            $full_message .= "실행 시간: " . date('Y-m-d H:i:s') . "\n";
            $full_message .= "실행 시간: " . $this->get_execution_time() . "초\n\n";
            $full_message .= $message;
            
            if (mail($email, $subject, $full_message, $headers)) {
                $this->log("이메일 알림 전송 완료: " . $email);
            } else {
                $this->log_warning("이메일 알림 전송 실패: " . $email);
            }
        }
    }
} 