<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ApiTest extends CI_Controller {
    
    private $api_url = 'https://qa.legacy-erp.parmple.com/df/filtering/manufacturers/fetch';
    private $auth_token = 'eyJhbGciOiJIUzUxMiJ9.eyJ0eXBlIjoiTUFOVUZBQ1RVUkVSIiwiY29tcGFueUlkIjoiNjMxIiwidXNlcklkIjoiMzIiLCJjYW5Vc2VGaWx0ZXJIaXN0b3J5Ijp0cnVlLCJzdWIiOiJoamtpbUBzYW1pay5jby5rciIsImlhdCI6MTc1MDI5ODk5MSwiZXhwIjoxNzgwODg1MzkxfQ.4t81Mow-S0_aokJ5NnFEib7z3QXSvsSngV6xA1znazd4l-fdss6p3wjqdXts6VEQxp8UuDJjIQEr8Zs3__PdYQ';
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('session');
    }
    
    public function index() {
        $this->load->view('api_test/index');
    }
    
    public function fetch_manufacturers() {
        // JSON 응답 헤더 설정
        $this->output->set_content_type('application/json');
        
        if (!$this->input->is_ajax_request()) {
            $this->output->set_output(json_encode([
                'success' => false, 
                'message' => '잘못된 요청입니다.'
            ]));
            return;
        }
        
        try {
            // cURL 초기화
            $ch = curl_init();
            
            // cURL 옵션 설정
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->auth_token,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);
            
            // API 요청 실행
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            
            curl_close($ch);
            
            // cURL 오류 확인
            if ($curl_error) {
                throw new Exception('API 요청 실패: ' . $curl_error);
            }
            
            // HTTP 상태 코드 확인
            if ($http_code !== 200) {
                throw new Exception('API 응답 오류: HTTP ' . $http_code);
            }
            
            // JSON 응답 파싱
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON 파싱 오류: ' . json_last_error_msg());
            }
            
            // 성공 응답
            $this->output->set_output(json_encode([
                'success' => true,
                'data' => $data,
                'http_code' => $http_code,
                'response_size' => strlen($response)
            ]));
            
        } catch (Exception $e) {
            // 오류 응답
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        }
    }
    
    public function test_connection() {
        // JSON 응답 헤더 설정
        $this->output->set_content_type('application/json');
        
        try {
            // 기본 연결 테스트
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_NOBODY => true, // HEAD 요청
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->auth_token
                ]
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            
            curl_close($ch);
            
            $this->output->set_output(json_encode([
                'success' => empty($curl_error),
                'http_code' => $http_code,
                'curl_error' => $curl_error,
                'url' => $this->api_url,
                'auth_token_length' => strlen($this->auth_token)
            ]));
            
        } catch (Exception $e) {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        }
    }
} 