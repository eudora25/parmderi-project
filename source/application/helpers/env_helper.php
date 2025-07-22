<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Environment Helper
 * 
 * 환경 변수를 로드하고 관리하는 헬퍼
 */

if (!function_exists('load_env')) {
    /**
     * .env 파일에서 환경 변수를 로드합니다.
     * 
     * @param string $path .env 파일 경로
     * @return void
     */
    function load_env($path = null) {
        if ($path === null) {
            $path = FCPATH . '../.env';
        }
        
        if (!file_exists($path)) {
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // 주석 제거
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // KEY=VALUE 형식 파싱
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // 따옴표 제거
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // 환경 변수 설정
                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

if (!function_exists('env')) {
    /**
     * 환경 변수 값을 가져옵니다.
     * 
     * @param string $key 환경 변수 키
     * @param mixed $default 기본값
     * @return mixed
     */
    function env($key, $default = null) {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // boolean 값 변환
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
        }
        
        return $value;
    }
} 