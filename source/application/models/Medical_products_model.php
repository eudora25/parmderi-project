<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Medical_products_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 의약품 검색
     */
    public function search_products($keyword = '', $limit = 20, $offset = 0, $filters = []) {
        $this->db->select('*');
        $this->db->from('medical_products');
        
        if (!empty($keyword)) {
            $this->db->group_start();
            $this->db->like('product_name', $keyword);
            $this->db->or_like('company_name', $keyword);
            $this->db->or_like('classification_name_1', $keyword);
            $this->db->or_like('ingredient_name_en', $keyword);
            $this->db->group_end();
        }
        
        // 필터 적용
        if (!empty($filters['company'])) {
            $this->db->like('company_name', $filters['company']);
        }
        
        if (!empty($filters['classification'])) {
            $this->db->where('classification_code_1', $filters['classification']);
        }
        
        if (!empty($filters['coverage'])) {
            $this->db->where('coverage', $filters['coverage']);
        }
        
        if (!empty($filters['cso_only'])) {
            $this->db->where('cso_product', 1);
        }
        
        $this->db->order_by('product_name', 'ASC');
        $this->db->limit($limit, $offset);
        
        return $this->db->get()->result_array();
    }

    /**
     * 검색 결과 총 개수
     */
    public function count_search_results($keyword = '', $filters = []) {
        $this->db->from('medical_products');
        
        if (!empty($keyword)) {
            $this->db->group_start();
            $this->db->like('product_name', $keyword);
            $this->db->or_like('company_name', $keyword);
            $this->db->or_like('classification_name_1', $keyword);
            $this->db->or_like('ingredient_name_en', $keyword);
            $this->db->group_end();
        }
        
        // 필터 적용
        if (!empty($filters['company'])) {
            $this->db->like('company_name', $filters['company']);
        }
        
        if (!empty($filters['classification'])) {
            $this->db->where('classification_code_1', $filters['classification']);
        }
        
        if (!empty($filters['coverage'])) {
            $this->db->where('coverage', $filters['coverage']);
        }
        
        if (!empty($filters['cso_only'])) {
            $this->db->where('cso_product', 1);
        }
        
        return $this->db->count_all_results();
    }

    /**
     * 의약품 상세 정보
     */
    public function get_product_detail($id) {
        return $this->db->get_where('medical_products', ['id' => $id])->row_array();
    }

    /**
     * 보험코드로 의약품 조회
     */
    public function get_product_by_insurance_code($insurance_code) {
        $this->db->group_start();
        $this->db->where('insurance_code', $insurance_code);
        $this->db->or_where('insurance_code_2', $insurance_code);
        $this->db->or_where('insurance_code_3', $insurance_code);
        $this->db->group_end();
        
        return $this->db->get('medical_products')->result_array();
    }

    /**
     * 회사별 의약품 목록
     */
    public function get_products_by_company($company_name, $limit = 50) {
        $this->db->like('company_name', $company_name);
        $this->db->order_by('product_name', 'ASC');
        $this->db->limit($limit);
        
        return $this->db->get('medical_products')->result_array();
    }

    /**
     * 분류별 의약품 목록
     */
    public function get_products_by_classification($classification_code, $limit = 50) {
        $this->db->where('classification_code_1', $classification_code);
        $this->db->order_by('product_name', 'ASC');
        $this->db->limit($limit);
        
        return $this->db->get('medical_products')->result_array();
    }

    /**
     * 자동완성을 위한 제품명 검색
     */
    public function get_product_suggestions($keyword, $limit = 10) {
        $this->db->select('DISTINCT product_name');
        $this->db->like('product_name', $keyword);
        $this->db->limit($limit);
        
        $results = $this->db->get('medical_products')->result_array();
        return array_column($results, 'product_name');
    }

    /**
     * 통계 정보
     */
    public function get_statistics() {
        $stats = [];
        
        // 전체 의약품 수
        $stats['total_products'] = $this->db->count_all('medical_products');
        
        // CSO 품목 수
        $stats['cso_products'] = $this->db->where('cso_product', 1)->count_all_results('medical_products');
        
        // 급여/비급여 통계
        $this->db->select('coverage, COUNT(*) as count');
        $this->db->group_by('coverage');
        $coverage_stats = $this->db->get('medical_products')->result_array();
        $stats['coverage'] = [];
        foreach ($coverage_stats as $stat) {
            $stats['coverage'][$stat['coverage']] = $stat['count'];
        }
        
        // 상위 제약회사
        $this->db->select('company_name, COUNT(*) as count');
        $this->db->where('company_name !=', '');
        $this->db->group_by('company_name');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        $stats['top_companies'] = $this->db->get('medical_products')->result_array();
        
        // 상위 분류
        $this->db->select('classification_name_1, COUNT(*) as count');
        $this->db->where('classification_name_1 !=', '');
        $this->db->group_by('classification_name_1');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        $stats['top_classifications'] = $this->db->get('medical_products')->result_array();
        
        return $stats;
    }

    /**
     * 엑셀 데이터 일괄 입력
     */
    public function insert_batch_data($data_array) {
        $this->db->trans_start();
        
        // 기존 데이터 삭제 (전체 재입력)
        $this->db->truncate('medical_products');
        
        // 배치 입력 (1000개씩 나누어서)
        $batch_size = 1000;
        $total_inserted = 0;
        
        for ($i = 0; $i < count($data_array); $i += $batch_size) {
            $batch = array_slice($data_array, $i, $batch_size);
            $this->db->insert_batch('medical_products', $batch);
            $total_inserted += count($batch);
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => '데이터 입력 중 오류가 발생했습니다.'];
        }
        
        return ['success' => true, 'inserted_count' => $total_inserted];
    }

    /**
     * 업로드 로그 기록
     */
    public function log_upload($data) {
        return $this->db->insert('medical_products_upload_log', $data);
    }

    /**
     * 업로드 로그 업데이트
     */
    public function update_upload_log($id, $data) {
        return $this->db->update('medical_products_upload_log', $data, ['id' => $id]);
    }

    /**
     * 최근 업로드 로그
     */
    public function get_recent_upload_logs($limit = 10) {
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get('medical_products_upload_log')->result_array();
    }

    /**
     * 분류 목록 조회
     */
    public function get_classifications() {
        $this->db->select('DISTINCT classification_code_1, classification_name_1');
        $this->db->where('classification_code_1 !=', '');
        $this->db->where('classification_name_1 !=', '');
        $this->db->order_by('classification_code_1', 'ASC');
        
        return $this->db->get('medical_products')->result_array();
    }

    /**
     * 제약회사 목록 조회
     */
    public function get_companies() {
        $this->db->select('DISTINCT company_name');
        $this->db->where('company_name !=', '');
        $this->db->order_by('company_name', 'ASC');
        
        $results = $this->db->get('medical_products')->result_array();
        return array_column($results, 'company_name');
    }
}
?> 