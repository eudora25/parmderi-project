<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Exceptions extends CI_Exceptions {
    
    /**
     * JSON 형식의 오류 응답을 반환합니다.
     */
    private function _json_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        // AJAX 요청인 경우에만 JSON 응답
        if ($this->is_ajax_request()) {
            $response = [
                'success' => false,
                'message' => strip_tags($message),
                'status' => $status_code
            ];
            
            header('Content-Type: application/json; charset=utf-8');
            header('HTTP/1.1 ' . $status_code . ' ' . $this->get_status_message($status_code));
            echo json_encode($response);
            exit;
        }
        
        // AJAX가 아닌 경우 기본 오류 페이지 표시
        parent::show_error($heading, $message, $template, $status_code);
    }
    
    /**
     * 일반 PHP 오류 처리
     */
    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        $this->_json_error($heading, $message, $template, $status_code);
    }
    
    /**
     * PHP 예외 처리
     */
    public function show_exception($exception)
    {
        $this->_json_error('오류 발생', $exception->getMessage(), 'error_general', 500);
    }
    
    /**
     * 404 오류 처리
     */
    public function show_404($page = '', $log_error = TRUE)
    {
        $this->_json_error('404 Page Not Found', '요청하신 페이지를 찾을 수 없습니다.', 'error_404', 404);
    }
    
    /**
     * AJAX 요청 여부 확인
     */
    private function is_ajax_request()
    {
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) || (
            !empty($_SERVER['CONTENT_TYPE']) && 
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
        );
    }
    
    /**
     * HTTP 상태 코드에 대한 메시지 반환
     */
    private function get_status_message($status_code)
    {
        $status_messages = [
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable'
        ];
        
        return isset($status_messages[$status_code]) ? $status_messages[$status_code] : 'Unknown Status';
    }
} 
