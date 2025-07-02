<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'third_party/SimpleXLSX/SimpleXLSX.php';

class Medical_products extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // 모델 로드
        $this->load->model('Medical_products_model');
        
        // 라이브러리 로드
        $this->load->library('upload');
        $this->load->library('form_validation');
        $this->load->library('session');
        $this->load->dbforge();
        
        
        // 헬퍼 로드
        $this->load->helper(['url', 'form', 'file']);
        

        $this->load->database();
    }

    /**
     * 의약품 관리 메인 페이지 (검색/업로드 선택)
     */
    public function index() {
        $data['title'] = '의약품 관리';
        $this->load->view('medical_products/index', $data);
    }

    /**
     * 자연어 기반 검색 페이지
     */
    public function search()
    {
        $data = array(
            'page_title' => '의약품 정보 검색',
            'meta_description' => '전국 의약품 정보를 자연어로 쉽게 검색해보세요',
            'total_products' => $this->Medical_products_model->get_total_product_count(),
            'recent_searches' => $this->Medical_products_model->get_recent_searches(5)
        );
        
        $this->load->view('medical_products/search_main', $data);
    }

    /**
     * 자연어 검색 처리 (AJAX)
     */
    public function natural_search()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $query = trim($this->input->post('query'));
        
        if (empty($query)) {
            echo json_encode(array(
                'success' => false,
                'message' => '검색어를 입력해주세요.'
            ));
            return;
        }

        try {
            // 자연어 분석 및 검색
            $search_result = $this->Medical_products_model->natural_language_search($query);
            
            // 검색 기록 저장
            $this->Medical_products_model->save_search_log($query, count($search_result['products']));
            
            echo json_encode(array(
                'success' => true,
                'query' => $query,
                'search_type' => $search_result['search_type'],
                'search_params' => $search_result['search_params'],
                'products' => $search_result['products'],
                'total_count' => $search_result['total_count'],
                'message' => $search_result['message']
            ));
            
        } catch (Exception $e) {
            log_message('error', '검색 오류: ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => '검색 중 오류가 발생했습니다.'
            ));
        }
    }

    /**
     * 자동완성 (AJAX)
     */
    public function autocomplete()
    {
        $term = trim($this->input->get('term'));
        
        if (strlen($term) < 2) {
            echo json_encode(array());
            return;
        }

        $suggestions = $this->Medical_products_model->get_autocomplete_suggestions($term);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($suggestions);
    }

    /**
     * 상품 상세 정보 (AJAX)
     */
    public function detail($id = null)
    {
        if (!$id) {
            show_404();
            return;
        }

        $product = $this->Medical_products_model->get_product_detail($id);
        
        if (!$product) {
            show_404();
            return;
        }

        if ($this->input->is_ajax_request()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'success' => true,
                'product' => $product
            ));
        } else {
            $data = array(
                'product' => $product,
                'page_title' => $product->product_name . ' - 의약품 정보'
            );
            $this->load->view('medical_products/detail', $data);
        }
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
            
            // im_product 테이블에도 데이터 저장
            if ($result['success']) {
                $this->db->trans_start();
                
                // 기존 데이터 삭제
                $this->db->truncate('im_product');
                
                // 새 데이터 삽입
                foreach ($result['data'] as $row) {
                    $im_product_data = array(
                        'cso_item' => $row['cso_item'],
                        'category' => $row['category'],
                        'company_name' => $row['company_name'],
                        'classification_code' => $row['classification_code'],
                        'insurance_code' => $row['insurance_code'],
                        'product_name' => $row['product_name'],
                        'price' => $row['price'],
                        'ingredient_eng' => $row['ingredient_eng'],
                        'formulation' => $row['formulation'],
                        'ingredient_code' => $row['ingredient_code'],
                        'content' => $row['content'],
                        'unit' => $row['unit'],
                        'atc_code' => $row['atc_code'],
                        'commission_rate' => $row['commission_rate']
                    );
                    
                    $this->db->insert('im_product', $im_product_data);
                }
                
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    throw new Exception('im_product 테이블 데이터 저장 중 오류가 발생했습니다.');
                }
            }
            
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
            @unlink($file_path);
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['message'],
                'total_rows' => $result['total_rows'],
                'success_rows' => $result['success_rows'],
                'failed_rows' => $result['failed_rows']
            ]);
            
        } catch (Exception $e) {
            log_message('error', '엑셀 처리 오류: ' . $e->getMessage());
            
            $this->Medical_products_model->update_upload_log($log_id, [
                'status' => 'failed',
                'end_time' => date('Y-m-d H:i:s'),
                'error_message' => $e->getMessage()
            ]);
            
            @unlink($file_path);
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
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
        try {
            log_message('debug', '엑셀 파싱 시작: ' . $file_path);
            $xlsx = Shuchkin\SimpleXLSX::parse($file_path);
            if (!$xlsx) {
                log_message('error', '엑셀 파싱 실패: ' . Shuchkin\SimpleXLSX::parseError());
                throw new Exception('엑셀 파싱 실패: ' . Shuchkin\SimpleXLSX::parseError());
            }
            log_message('debug', '엑셀 파싱 성공');
            $rows = $xlsx->rows(0);
            if (count($rows) < 2) {
                return [
                    'success' => false,
                    'message' => '엑셀 데이터가 충분하지 않습니다.',
                    'total_rows' => 0,
                    'success_rows' => 0,
                    'failed_rows' => 0
                ];
            }
            // 첫 번째 줄이 헤더, 두 번째 줄부터 데이터
            $headers = $rows[0];
            $data_rows = array_slice($rows, 1);
            // 엑셀 헤더와 DB 컬럼 매핑 테이블
            $header_to_db = [
                'ingredient_eng' => 'ingredient_name_en',
                // 필요시 추가 매핑
            ];
            $processed_data = [];
            $failed_rows = 0;
            $skipped_rows = 0;
            foreach ($data_rows as $row_index => $row) {
                $item = [];
                foreach ($headers as $i => $header) {
                    $db_col = isset($header_to_db[$header]) ? $header_to_db[$header] : $header;
                    $item[$db_col] = isset($row[$i]) ? $row[$i] : null;
                }
                // product_name이 비어있으면 insert 대상에서 제외, 로그로 남김
                if (empty($item['product_name'])) {
                    $skipped_rows++;
                    log_message('debug', '엑셀 업로드: product_name 비어있어 제외된 행: ' . json_encode($item));
                    continue;
                }
                $processed_data[] = $item;
            }
            if (count($processed_data) === 0) {
                log_message('error', '엑셀 업로드: insert 대상 데이터가 0건. 전체 데이터: ' . json_encode($data_rows));
                return [
                    'success' => false,
                    'message' => '유효한 제품 데이터가 없습니다.',
                    'total_rows' => count($data_rows),
                    'success_rows' => 0,
                    'failed_rows' => count($data_rows)
                ];
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
                'failed_rows' => $failed_rows,
                'data' => $processed_data
            ];
        } catch (Exception $e) {
            log_message('error', '엑셀 처리 중 예외: ' . $e->getMessage() . ' / 파일: ' . $file_path);
            return [
                'success' => false,
                'message' => '엑셀 처리 중 오류: ' . $e->getMessage(),
                'total_rows' => 0,
                'success_rows' => 0,
                'failed_rows' => 0
            ];
        }
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
                    $mapped['cso_item'] = $value;
                    break;
                case '구분':
                    $mapped['category'] = $value;
                    break;
                case '업체명':
                    $mapped['company_name'] = $value;
                    break;
                case '분류번호':
                    $mapped['classification_code'] = $value;
                    break;
                case '분류명':
                    $mapped['classification_name'] = $value;
                    break;
                case 'a 보험코드':
                    $mapped['insurance_code'] = $value;
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
                    $mapped['ingredient_eng'] = $value;
                    break;
                case '약가':
                    if (!isset($mapped['price'])) {
                        $mapped['price'] = is_numeric($value) ? (float)$value : null;
                    } else {
                        $mapped['price_2'] = is_numeric($value) ? (float)$value : null;
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
                case '전문/일반':
                    $mapped['product_type'] = $value;
                    break;
                case '생동/임상':
                    $mapped['bioequivalence'] = $value;
                    break;
                case '자사/위탁':
                    $mapped['source_type'] = $value;
                    break;
                case '대조약':
                    $mapped['reference_drug'] = $value;
                    break;
                case '수수료율':
                    $mapped['commission_rate'] = is_numeric($value) ? (float)$value : null;
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
        
        // 필터 값 가져오기
        $filters = [
            'company' => $this->input->get('company', TRUE),
            'classification' => $this->input->get('classification', TRUE),
            'cso_only' => $this->input->get('cso_only', TRUE) === '1' ? true : false
        ];
        
        $products = $this->Medical_products_model->search_products($keyword, $limit, 0, $filters);
        
        $result = [
            'success' => true,
            'query' => $keyword,
            'count' => count($products),
            'data' => $products
        ];
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 테이블 생성 (초기 설정)
     */
    public function setup() {
        $this->load->dbforge();
        
        // medical_products 테이블 필드 정의
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'product_no' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'cso_product' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ),
            'category' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'company_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => TRUE
            ),
            'classification_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'classification_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => TRUE
            ),
            'insurance_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'insurance_code_2' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'insurance_code_3' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'product_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => FALSE
            ),
            'product_name_2' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => TRUE
            ),
            'ingredient_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'ingredient_name_en' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => TRUE
            ),
            'drug_price' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => TRUE
            ),
            'drug_price_2' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => TRUE
            ),
            'decided_price' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => TRUE
            ),
            'coverage' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'formulation' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => TRUE
            ),
            'item_standard_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'atc_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'note' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'product_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'bioequivalence' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'source_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'reference_drug' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => TRUE
            ),
            'commission_rate' => array(
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => TRUE
            ),
            'created_at' => array(
                'type' => 'TIMESTAMP',
                'null' => FALSE
            ),
            'updated_at' => array(
                'type' => 'TIMESTAMP',
                'null' => TRUE
            )
        );

        // 테이블 생성
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('product_name');
        $this->dbforge->add_key('company_name');
        $this->dbforge->add_key('classification_code');
        
        if($this->dbforge->create_table('medical_products', TRUE)) {
            echo "medical_products 테이블이 성공적으로 생성되었습니다.";
        } else {
            echo "테이블 생성 중 오류가 발생했습니다.";
        }
    }

    /**
     * 업로드 로그 테이블 생성
     */
    public function setup_upload_log() {
        $this->load->dbforge();
        
        // medical_products_upload_log 테이블 필드 정의
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'filename' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => FALSE
            ),
            'status' => array(
                'type' => 'ENUM',
                'constraint' => ['processing','completed','failed'],
                'default' => 'processing'
            ),
            'total_rows' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0
            ),
            'success_rows' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0
            ),
            'failed_rows' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0
            ),
            'error_message' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'start_time' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            ),
            'end_time' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            ),
            'created_at' => array(
                'type' => 'TIMESTAMP',
                'null' => FALSE
            ),
            'updated_at' => array(
                'type' => 'TIMESTAMP',
                'null' => TRUE
            )
        );

        // 테이블 생성
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('created_at');
        
        if($this->dbforge->create_table('medical_products_upload_log', TRUE)) {
            // 테이블 생성 후 CURRENT_TIMESTAMP 기본값 설정
            $this->db->query('ALTER TABLE medical_products_upload_log MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
            $this->db->query('ALTER TABLE medical_products_upload_log MODIFY updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
            
            echo "medical_products_upload_log 테이블이 성공적으로 생성되었습니다.";
        } else {
            echo "테이블 생성 중 오류가 발생했습니다.";
        }
    }

    /**
     * 실패한 레코드 테이블 생성
     */
    public function setup_failed_records() {
        $this->load->dbforge();
        
        // medical_products_failed_records 테이블 필드 정의
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'upload_log_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE
            ),
            'row_number' => array(
                'type' => 'INT',
                'constraint' => 11
            ),
            'row_data' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'error_message' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'error_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE
            ),
            'created_at' => array(
                'type' => 'TIMESTAMP',
                'null' => FALSE
            )
        );

        // 테이블 생성
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('upload_log_id');
        
        if($this->dbforge->create_table('medical_products_failed_records', TRUE)) {
            echo "<br>medical_products_failed_records 테이블이 성공적으로 생성되었습니다.";
        } else {
            echo "<br>테이블 생성 중 오류가 발생했습니다.";
        }
    }

    /**
     * im_product 테이블 생성
     */
    public function setup_im_product() {
        $this->load->dbforge();
        
        // im_product 테이블 필드 정의
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'cso_item' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE,
                'comment' => 'CSO품목'
            ),
            'category' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE,
                'comment' => '구분'
            ),
            'company_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => TRUE,
                'comment' => '업체명'
            ),
            'classification_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE,
                'comment' => '분류번호'
            ),
            'insurance_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE,
                'comment' => '보험코드'
            ),
            'product_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => FALSE,
                'comment' => '제품명'
            ),
            'price' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => TRUE,
                'comment' => '약가'
            ),
            'ingredient_eng' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => TRUE,
                'comment' => '성분명(영문)'
            ),
            'formulation' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => TRUE,
                'comment' => '제형'
            ),
            'ingredient_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE,
                'comment' => '성분코드'
            ),
            'content' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => TRUE,
                'comment' => '함량'
            ),
            'unit' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE,
                'comment' => '단위'
            ),
            'atc_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => TRUE,
                'comment' => 'ATC코드'
            ),
            'commission_rate' => array(
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => TRUE,
                'comment' => '수수료율'
            ),
            'created_at' => array(
                'type' => 'TIMESTAMP',
                'null' => FALSE,
                'default' => 'CURRENT_TIMESTAMP'
            ),
            'updated_at' => array(
                'type' => 'TIMESTAMP',
                'null' => TRUE,
                'default' => NULL,
                'on update' => 'CURRENT_TIMESTAMP'
            )
        );

        // 테이블 생성
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('product_name');
        $this->dbforge->add_key('company_name');
        $this->dbforge->add_key('classification_code');
        
        if($this->dbforge->create_table('im_product', TRUE)) {
            echo "im_product 테이블이 성공적으로 생성되었습니다.";
        } else {
            echo "테이블 생성 중 오류가 발생했습니다.";
        }
    }

    /**
     * 모든 필요한 테이블 생성
     */
    public function setup_all() {
        $this->setup();
        echo "<br><br>";
        $this->setup_upload_log();
        echo "<br><br>";
        $this->setup_failed_records();
        echo "<br><br>";
        $this->setup_im_product();
    }

    /**
     * 간단한 엑셀 파일 업로드 처리
     */
    public function upload_simple()
    {
        // AJAX 요청이 아닌 경우 업로드 폼 표시
        if (!$this->input->is_ajax_request()) {
            $this->load->view('medical_products/upload_simple');
            return;
        }

        // 파일 업로드 처리
        try {
            if (empty($_FILES['excel_file'])) {
                throw new Exception('업로드된 파일이 없습니다.');
            }

            $file = $_FILES['excel_file'];
            
            // 파일 업로드 검증
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception($this->get_upload_error_message($file['error']));
            }

            // 파일 확장자 검사
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'xls'])) {
                throw new Exception('엑셀 파일(.xlsx, .xls)만 업로드 가능합니다.');
            }

            // 업로드 디렉토리 생성
            $upload_dir = FCPATH . 'uploads';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // 파일명 생성 (현재 시간 + 원본 파일명)
            $new_filename = 'medical_products_simple_' . date('YmdHis') . '.' . $ext;
            $upload_path = $upload_dir . '/' . $new_filename;

            // 파일 이동
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                throw new Exception('파일 업로드에 실패했습니다.');
            }

            // 엑셀 파일 읽기
            $excel = new SimpleXLSX($upload_path);
            
            if (!$excel) {
                throw new Exception('엑셀 파일을 읽을 수 없습니다: ' . SimpleXLSX::parseError());
            }

            // 데이터베이스 트랜잭션 시작
            $this->db->trans_start();

            $rows = $excel->rows();
            $header = array_shift($rows); // 첫 번째 행은 헤더

            $success_count = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    // 데이터 검증
                    if (count($row) !== count($header)) {
                        throw new Exception('데이터 형식이 올바르지 않습니다.');
                    }

                    // 데이터 매핑
                    $data = array_combine($header, $row);
                    
                    // 필수 필드 검증
                    $required_fields = ['cso_item', 'product_name', 'price'];
                    foreach ($required_fields as $field) {
                        if (empty($data[$field])) {
                            throw new Exception($field . ' 필드는 필수입니다.');
                        }
                    }

                    // 데이터 정제
                    $insert_data = [
                        'cso_item' => $data['cso_item'],
                        'category' => $data['category'] ?? '',
                        'company_name' => $data['company_name'] ?? '',
                        'classification_code' => $data['classification_code'] ?? '',
                        'insurance_code' => $data['insurance_code'] ?? '',
                        'product_name' => $data['product_name'],
                        'price' => (float)$data['price'],
                        'ingredient_eng' => $data['ingredient_eng'] ?? '',
                        'formulation' => $data['formulation'] ?? '',
                        'ingredient_code' => $data['ingredient_code'] ?? '',
                        'content' => $data['content'] ?? '',
                        'unit' => $data['unit'] ?? '',
                        'atc_code' => $data['atc_code'] ?? '',
                        'commission_rate' => isset($data['commission_rate']) ? (float)$data['commission_rate'] : 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    // 데이터 삽입
                    $this->db->insert('im_product', $insert_data);
                    $success_count++;

                } catch (Exception $e) {
                    $errors[] = [
                        'row' => $index + 2, // Excel 행 번호 (헤더 제외)
                        'error' => $e->getMessage(),
                        'data' => $row
                    ];
                }
            }

            // 트랜잭션 완료
            $this->db->trans_complete();

            // 트랜잭션 결과 확인
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('데이터베이스 오류가 발생했습니다.');
            }

            // 성공 응답
            $response = [
                'success' => true,
                'message' => sprintf(
                    '파일 업로드 완료. 총 %d개 중 %d개 처리 성공, %d개 실패',
                    count($rows),
                    $success_count,
                    count($errors)
                ),
                'errors' => $errors
            ];

        } catch (Exception $e) {
            // 실패 응답
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => isset($errors) ? $errors : []
            ];

            // 트랜잭션이 시작된 경우 롤백
            if ($this->db->trans_status() !== NULL) {
                $this->db->trans_rollback();
            }

            // 로그 기록
            log_message('error', 'File upload error: ' . $e->getMessage());
        }

        // JSON 응답 전송
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 파일 업로드 오류 메시지 반환
     */
    private function get_upload_error_message($error_code)
    {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return '업로드된 파일이 PHP의 upload_max_filesize 설정값을 초과했습니다.';
            case UPLOAD_ERR_FORM_SIZE:
                return '업로드된 파일이 HTML 폼에서 지정한 MAX_FILE_SIZE를 초과했습니다.';
            case UPLOAD_ERR_PARTIAL:
                return '파일이 일부분만 업로드되었습니다.';
            case UPLOAD_ERR_NO_FILE:
                return '파일이 업로드되지 않았습니다.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return '임시 폴더가 없습니다.';
            case UPLOAD_ERR_CANT_WRITE:
                return '디스크에 파일을 쓸 수 없습니다.';
            case UPLOAD_ERR_EXTENSION:
                return 'PHP 확장에 의해 파일 업로드가 중지되었습니다.';
            default:
                return '알 수 없는 업로드 오류가 발생했습니다.';
        }
    }

    /**
     * process_upload: process_excel과 동일하게 동작하도록 구현 (ajax 업로드 대응)
     */
    public function process_upload() {
        return $this->process_excel();
    }
}
?> 
