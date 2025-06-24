<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Medical_products extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Medical_products_model');
        $this->load->library(['upload', 'pagination']);
        $this->load->helper(['url', 'form']);
    }

    /**
     * 의약품 검색 메인 페이지
     */
    public function index() {
        $data['title'] = '의약품 검색';
        $data['stats'] = $this->Medical_products_model->get_statistics();
        $data['classifications'] = $this->Medical_products_model->get_classifications();
        $data['companies'] = $this->Medical_products_model->get_companies();
        
        $this->load->view('medical_products/search_main', $data);
    }

    /**
     * AJAX 의약품 검색
     */
    public function search() {
        header('Content-Type: application/json; charset=utf-8');
        
        $keyword = $this->input->get('q', TRUE);
        $page = (int) $this->input->get('page', TRUE) ?: 1;
        $limit = (int) $this->input->get('limit', TRUE) ?: 20;
        $offset = ($page - 1) * $limit;
        
        $products = $this->Medical_products_model->search_products($keyword, $limit, $offset);
        $total_count = $this->Medical_products_model->count_search_results($keyword);
        
        $result = [
            'success' => true,
            'data' => $products,
            'pagination' => [
                'current_page' => $page,
                'total_count' => $total_count,
                'total_pages' => ceil($total_count / $limit),
                'limit' => $limit
            ]
        ];
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 의약품 상세 정보
     */
    public function detail($id) {
        $product = $this->Medical_products_model->get_product_detail($id);
        
        if (!$product) {
            show_404();
        }
        
        $data['product'] = $product;
        $data['title'] = $product['product_name'] . ' - 의약품 상세 정보';
        
        $this->load->view('medical_products/detail', $data);
    }

    /**
     * 자동완성 API
     */
    public function autocomplete() {
        header('Content-Type: application/json; charset=utf-8');
        
        $keyword = $this->input->get('q', TRUE);
        
        if (strlen($keyword) < 2) {
            echo json_encode([]);
            return;
        }
        
        $suggestions = $this->Medical_products_model->get_product_suggestions($keyword);
        echo json_encode($suggestions, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 통계 페이지
     */
    public function statistics() {
        $data['title'] = '의약품 통계';
        $data['stats'] = $this->Medical_products_model->get_statistics();
        
        $this->load->view('medical_products/statistics', $data);
    }

    /**
     * 엑셀 업로드 페이지
     */
    public function upload() {
        $data['title'] = '의약품 데이터 업로드';
        $data['upload_logs'] = $this->Medical_products_model->get_recent_upload_logs();
        
        $this->load->view('medical_products/upload', $data);
    }

    /**
     * 엑셀 데이터 처리 (raw_data 시트)
     */
    public function process_excel() {
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
            return;
        }
        
        // 업로드 설정
        $config['upload_path'] = './uploads/';
        $config['allowed_types'] = 'xlsx|xls';
        $config['max_size'] = 10240; // 10MB
        $config['file_name'] = 'medical_products_' . date('YmdHis');
        
        $this->upload->initialize($config);
        
        if (!$this->upload->do_upload('excel_file')) {
            echo json_encode([
                'success' => false, 
                'message' => $this->upload->display_errors('', '')
            ]);
            return;
        }
        
        $upload_data = $this->upload->data();
        $file_path = $upload_data['full_path'];
        
        // 업로드 로그 시작
        $log_id = $this->Medical_products_model->log_upload([
            'filename' => $upload_data['file_name'],
            'status' => 'processing',
            'start_time' => date('Y-m-d H:i:s')
        ]);
        
        try {
            // 엑셀 파일 처리
            $result = $this->process_raw_data_excel($file_path);
            
            // 로그 업데이트
            $this->Medical_products_model->update_upload_log($log_id, [
                'total_rows' => $result['total_rows'],
                'success_rows' => $result['success_rows'],
                'failed_rows' => $result['failed_rows'],
                'end_time' => date('Y-m-d H:i:s'),
                'status' => $result['success'] ? 'completed' : 'failed',
                'error_message' => $result['message'] ?? null
            ]);
            
            // 업로드 파일 삭제
            unlink($file_path);
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            // 오류 로그 업데이트
            $this->Medical_products_model->update_upload_log($log_id, [
                'end_time' => date('Y-m-d H:i:s'),
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => '데이터 처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * raw_data 시트 엑셀 파일 처리
     */
    private function process_raw_data_excel($file_path) {
        ini_set('memory_limit', '1G');
        set_time_limit(600);
        
        require_once APPPATH . 'third_party/SimpleXLSX/SimpleXLSX.php';
        
        if (!$xlsx = Shuchkin\SimpleXLSX::parse($file_path)) {
            return [
                'success' => false,
                'message' => 'Excel 파일을 읽을 수 없습니다: ' . Shuchkin\SimpleXLSX::parseError()
            ];
        }
        
        // raw_data 시트 (인덱스 2)
        $rows = $xlsx->rows(2);
        
        if (count($rows) < 6) {
            return [
                'success' => false,
                'message' => 'raw_data 시트에 충분한 데이터가 없습니다.'
            ];
        }
        
        // 헤더는 5번째 행 (인덱스 4)
        $headers = $rows[4];
        $data_rows = array_slice($rows, 5); // 6번째 행부터 데이터
        
        $processed_data = [];
        $failed_rows = 0;
        
        foreach ($data_rows as $row_index => $row) {
            try {
                $processed_row = $this->map_excel_row_to_db_fields($headers, $row);
                if (!empty($processed_row['product_name'])) { // 제품명이 있는 경우만
                    $processed_data[] = $processed_row;
                }
            } catch (Exception $e) {
                $failed_rows++;
            }
        }
        
        // 데이터베이스에 입력
        $insert_result = $this->Medical_products_model->insert_batch_data($processed_data);
        
        return [
            'success' => $insert_result['success'],
            'message' => $insert_result['success'] ? 
                '데이터가 성공적으로 입력되었습니다.' : 
                $insert_result['message'],
            'total_rows' => count($data_rows),
            'success_rows' => $insert_result['inserted_count'] ?? 0,
            'failed_rows' => $failed_rows
        ];
    }

    /**
     * 엑셀 행을 DB 필드에 매핑
     */
    private function map_excel_row_to_db_fields($headers, $row) {
        $mapped = [];
        
        // 헤더와 값 매핑
        for ($i = 0; $i < count($headers); $i++) {
            $header = trim($headers[$i]);
            $value = isset($row[$i]) ? trim($row[$i]) : '';
            
            // 헤더에 따른 DB 필드 매핑
            switch ($header) {
                case '전체 No':
                    $mapped['product_no'] = $value;
                    break;
                case 'CSO품목':
                    $mapped['cso_product'] = ($value == '1') ? 1 : 0;
                    break;
                case '구분':
                    $mapped['category'] = $value;
                    break;
                case '업체명':
                    $mapped['company_name'] = $value;
                    break;
                case '분류번호':
                    if (!isset($mapped['classification_code_1'])) {
                        $mapped['classification_code_1'] = $value;
                    } else {
                        $mapped['classification_code_2'] = $value;
                    }
                    break;
                case '분류명':
                    if (!isset($mapped['classification_name_1'])) {
                        $mapped['classification_name_1'] = $value;
                    } else {
                        $mapped['classification_name_2'] = $value;
                    }
                    break;
                case 'a 보험코드':
                    $mapped['insurance_code_a'] = $value;
                    break;
                case '보험코드':
                    if (!isset($mapped['insurance_code'])) {
                        $mapped['insurance_code'] = $value;
                    } elseif (!isset($mapped['insurance_code_2'])) {
                        $mapped['insurance_code_2'] = $value;
                    } else {
                        $mapped['insurance_code_3'] = $value;
                    }
                    break;
                case '제품명':
                    if (!isset($mapped['product_name'])) {
                        $mapped['product_name'] = $value;
                    } else {
                        $mapped['product_name_2'] = $value;
                    }
                    break;
                case '주성분코드':
                    $mapped['ingredient_code'] = $value;
                    break;
                case '주성분명(영문)':
                    $mapped['ingredient_name_en'] = $value;
                    break;
                case '약가':
                    if (!isset($mapped['drug_price'])) {
                        $mapped['drug_price'] = is_numeric($value) ? (float)$value : null;
                    } else {
                        $mapped['drug_price_2'] = is_numeric($value) ? (float)$value : null;
                    }
                    break;
                case '결정할 약가':
                    $mapped['decided_price'] = is_numeric($value) ? (float)$value : null;
                    break;
                case '급여':
                    $mapped['coverage'] = $value;
                    break;
                case '제형':
                    $mapped['formulation'] = $value;
                    break;
                case '품목기준코드':
                    $mapped['item_standard_code'] = $value;
                    break;
                case 'ATC코드':
                    $mapped['atc_code'] = $value;
                    break;
                case '비고':
                    $mapped['note'] = $value;
                    break;
            }
        }
        
        return $mapped;
    }

    /**
     * API 엔드포인트
     */
    public function api() {
        header('Content-Type: application/json; charset=utf-8');
        
        $keyword = $this->input->get('q', TRUE);
        $limit = min((int) $this->input->get('limit', TRUE) ?: 10, 50);
        
        $products = $this->Medical_products_model->search_products($keyword, $limit);
        
        $result = [
            'success' => true,
            'query' => $keyword,
            'count' => count($products),
            'data' => $products
        ];
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
?> 