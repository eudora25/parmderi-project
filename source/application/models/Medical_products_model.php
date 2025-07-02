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
            $this->db->or_like('classification_name', $keyword);
            $this->db->or_like('ingredient_name_en', $keyword);
            $this->db->group_end();
        }
        
        // 필터 적용
        if (!empty($filters['company'])) {
            $this->db->where('company_name', $filters['company']);
        }
        
        if (!empty($filters['classification'])) {
            $this->db->where('classification_code', $filters['classification']);
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
            $this->db->or_like('classification_name', $keyword);
            $this->db->or_like('ingredient_name_en', $keyword);
            $this->db->group_end();
        }
        
        // 필터 적용
        if (!empty($filters['company'])) {
            $this->db->where('company_name', $filters['company']);
        }
        
        if (!empty($filters['classification'])) {
            $this->db->where('classification_code', $filters['classification']);
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
        $this->db->where('id', $id);
        return $this->db->get('medical_products')->row_array();
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
        $this->db->where('classification_code', $classification_code);
        $this->db->order_by('product_name', 'ASC');
        $this->db->limit($limit);
        
        return $this->db->get('medical_products')->result_array();
    }

    /**
     * 자동완성을 위한 제품명 검색
     */
    public function get_product_suggestions($keyword, $limit = 10) {
        $this->db->distinct();
        $this->db->select('product_name');
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
        
        // 상위 제약회사
        $this->db->select('company_name, COUNT(id) as count');
        $this->db->from('medical_products');
        $this->db->where('company_name !=', '');
        $this->db->group_by('company_name');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        $stats['top_companies'] = $this->db->get()->result_array();
        
        // 상위 분류
        $this->db->select('classification_name, COUNT(id) as count');
        $this->db->from('medical_products');
        $this->db->where('classification_name !=', '');
        $this->db->group_by('classification_name');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        $stats['top_classifications'] = $this->db->get()->result_array();
        
        return $stats;
    }

    /**
     * 엑셀 데이터 일괄 입력
     */
    public function insert_batch_data($data_array) {
        if (empty($data_array)) {
            return ['success' => false, 'message' => '입력할 데이터가 없습니다.'];
        }
        
        $this->db->trans_start();
        
        try {
            // 기존 데이터 삭제 (전체 재입력)
            $this->db->empty_table('medical_products');
            
            // 데이터 정리 및 검증
            $cleaned_data = [];
            foreach ($data_array as $row) {
                $cleaned_row = [];
                foreach ($row as $key => $value) {
                    // 배열이 포함된 경우 처리
                    if (is_array($value)) {
                        $cleaned_row[$key] = !empty($value) ? (string)$value[0] : null;
                    } else {
                        $cleaned_row[$key] = $value;
                    }
                }
                
                // 제품명이 있는 경우만 추가
                if (!empty($cleaned_row['product_name'])) {
                    $cleaned_data[] = $cleaned_row;
                }
            }
            
            if (empty($cleaned_data)) {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => '유효한 제품 데이터가 없습니다.'];
            }
            
            // 배치 입력 (1000개씩 나누어서)
            $batch_size = 1000;
            $total_inserted = 0;
            
            for ($i = 0; $i < count($cleaned_data); $i += $batch_size) {
                $batch = array_slice($cleaned_data, $i, $batch_size);
                if (!empty($batch)) {
                    $this->db->insert_batch('medical_products', $batch);
                    $total_inserted += count($batch);
                }
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return ['success' => false, 'message' => '데이터 입력 중 오류가 발생했습니다.'];
            }
            
            return ['success' => true, 'inserted_count' => $total_inserted];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => '데이터 처리 중 오류: ' . $e->getMessage()];
        }
    }

    /**
     * 업로드 로그 기록
     */
    public function log_upload($data) {
        $this->db->insert('medical_products_upload_log', $data);
        return $this->db->insert_id();
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
        $this->db->distinct();
        $this->db->select('classification_code, classification_name');
        $this->db->from('medical_products');
        $this->db->where('classification_name IS NOT NULL');
        $this->db->where('classification_name !=', '');
        $this->db->order_by('classification_name', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * 회사 목록
     */
    public function get_companies() {
        $this->db->distinct();
        $this->db->select('company_name');
        $this->db->from('medical_products');
        $this->db->where('company_name IS NOT NULL');
        $this->db->where('company_name !=', '');
        $this->db->order_by('company_name', 'ASC');
        $results = $this->db->get()->result_array();
        return array_column($results, 'company_name');
    }

    /**
     * 자연어 검색 메인 함수
     */
    public function natural_language_search($query)
    {
        $start_time = microtime(true);
        
        // 검색어 분석
        $analysis = $this->analyze_query($query);
        
        // 검색 타입에 따른 처리
        switch ($analysis['type']) {
            case 'product_detail':
                $result = $this->search_product_detail($analysis);
                break;
            case 'company_search':
                $result = $this->search_by_company($analysis);
                break;
            case 'classification_search':
                $result = $this->search_by_classification($analysis);
                break;
            case 'mixed_search':
                $result = $this->search_mixed($analysis);
                break;
            default:
                $result = $this->search_general($analysis);
        }
        
        $result['analysis'] = $analysis;
        
        return $result;
    }

    /**
     * 검색어 분석
     */
    private function analyze_query($query)
    {
        $query = trim(strtolower($query));
        
        // 키워드 정의
        $info_keywords = array('정보', '상세정보', '개요', '소개', '무엇');
        $price_keywords = array('가격', '금액', '비용', '수가', '가', '얼마');
        $company_keywords = array('회사', '제약회사', '업체', '제조사', '제조업체');
        $classification_keywords = array('분류', '종류', '구분', '카테고리');
        $effect_keywords = array('효과', '효능', '작용', '치료');
        $usage_keywords = array('용법', '용량', '복용법', '사용법');
        
        // 회사명 패턴
        $companies = array(
            '한국얀센', '노바티스', '한국릴리', '화이자', '로슈', '머크', '사노피',
            '아스텔라스', '다케다', '바이엘', '글락소스미스클라인', 'gsk', 'msd'
        );
        
        // 분류명 패턴
        $classifications = array(
            '전문의약품', '일반의약품', '항생제', '진통제', '해열제', '소염제',
            '당뇨병약', '고혈압약', '항암제', '백신', '비타민'
        );

        $analysis = array(
            'original_query' => $query,
            'type' => 'general',
            'product_name' => null,
            'company' => null,
            'classification' => null,
            'info_type' => 'basic',
            'keywords' => array()
        );

        // 정보 유형 분석
        foreach ($price_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $analysis['info_type'] = 'price';
                $analysis['keywords'][] = $keyword;
                break;
            }
        }

        foreach ($company_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $analysis['info_type'] = 'company';
                $analysis['keywords'][] = $keyword;
                break;
            }
        }

        foreach ($effect_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $analysis['info_type'] = 'effect';
                $analysis['keywords'][] = $keyword;
                break;
            }
        }

        // 회사 검색 패턴
        foreach ($companies as $company_name) {
            if (strpos($query, $company_name) !== false) {
                $analysis['company'] = $company_name;
                break;
            }
        }

        // 분류 검색 패턴
        foreach ($classifications as $classification_name) {
            if (strpos($query, $classification_name) !== false) {
                $analysis['classification'] = $classification_name;
                break;
            }
        }

        // 제품명 추출 시도
        $product_name = $this->extract_product_name($query);
        if ($product_name) {
            $analysis['product_name'] = $product_name;
            $analysis['type'] = 'product_detail';
        }
        // 제품명이 없을 때만 회사/분류 검색
        else {
            if ($analysis['company']) {
                $analysis['type'] = 'company_search';
            } elseif ($analysis['classification']) {
                $analysis['type'] = 'classification_search';
            } elseif ($analysis['company'] && $analysis['classification']) {
                $analysis['type'] = 'mixed_search';
            }
        }

        return $analysis;
    }

    /**
     * 제품명 추출
     */
    private function extract_product_name($query)
    {
        // 일반적인 약품명 패턴들을 제거하고 실제 제품명 추출
        $stop_words = array('의', '정보', '가격', '효과', '회사', '제조사', '어디서', '언제', '얼마', '무엇');
        
        $words = explode(' ', $query);
        $product_candidates = array();
        
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 1 && !in_array($word, $stop_words)) {
                $product_candidates[] = $word;
            }
        }
        
        if (!empty($product_candidates)) {
            return implode(' ', $product_candidates);
        }
        
        return null;
    }

    /**
     * 제품 상세 검색
     */
    private function search_product_detail($analysis)
    {
        if (!$analysis['product_name']) {
            return array(
                'products' => array(),
                'total_count' => 0,
                'search_type' => 'product_detail',
                'message' => '제품명을 포함해서 검색해 주세요.',
                'search_params' => $analysis
            );
        }
        
        $this->db->like('product_name', $analysis['product_name']);
        $this->db->limit(10);
        
        $products = $this->db->get('medical_products')->result_array();
        
        if (empty($products)) {
            return array(
                'products' => array(),
                'total_count' => 0,
                'search_type' => 'product_detail',
                'message' => "'{$analysis['product_name']}'에 해당하는 제품을 찾을 수 없습니다.",
                'search_params' => $analysis
            );
        }
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'product_detail',
            'message' => "'{$analysis['product_name']}'에 대한 검색 결과입니다.",
            'search_params' => $analysis
        );
    }

    /**
     * 회사별 검색
     */
    private function search_by_company($analysis)
    {
        if (!$analysis['company']) {
            return array(
                'products' => array(),
                'total_count' => 0,
                'search_type' => 'company_search',
                'message' => '회사명을 포함해서 검색해 주세요.',
                'search_params' => $analysis
            );
        }
        
        $this->db->like('company_name', $analysis['company']);
        $this->db->limit(20);
        
        $products = $this->db->get('medical_products')->result_array();
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'company_search',
            'message' => "'{$analysis['company']}' 제품 목록입니다.",
            'search_params' => $analysis
        );
    }

    /**
     * 분류별 검색
     */
    private function search_by_classification($analysis)
    {
        if (!$analysis['classification']) {
            return array(
                'products' => array(),
                'total_count' => 0,
                'search_type' => 'classification_search',
                'message' => '분류명을 포함해서 검색해 주세요.',
                'search_params' => $analysis
            );
        }
        
        $this->db->like('classification_name', $analysis['classification']);
        $this->db->limit(20);
        
        $products = $this->db->get('medical_products')->result_array();
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'classification_search',
            'message' => "'{$analysis['classification']}' 분류 제품 목록입니다.",
            'search_params' => $analysis
        );
    }

    /**
     * 복합 검색
     */
    private function search_mixed($analysis)
    {
        $this->db->select('*');
        $this->db->from('medical_products');
        
        if ($analysis['company']) {
            $this->db->like('company_name', $analysis['company']);
        }
        
        if ($analysis['classification']) {
            $this->db->like('classification_name', $analysis['classification']);
        }
        
        $this->db->limit(20);
        $products = $this->db->get()->result_array();
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'mixed_search',
            'message' => '복합 검색 결과입니다.',
            'search_params' => $analysis
        );
    }

    /**
     * 일반 검색
     */
    private function search_general($analysis)
    {
        $this->db->select('*');
        $this->db->from('medical_products');
        
        $this->db->group_start();
        $this->db->like('product_name', $analysis['original_query']);
        $this->db->or_like('company_name', $analysis['original_query']);
        $this->db->or_like('classification_name', $analysis['original_query']);
        $this->db->group_end();
        
        $this->db->limit(20);
        $products = $this->db->get()->result_array();
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'general',
            'message' => "'{$analysis['original_query']}'에 대한 통합 검색 결과입니다.",
            'search_params' => $analysis
        );
    }

    /**
     * 자동완성 제안
     */
    public function get_autocomplete_suggestions($term)
    {
        $suggestions = array();
        
        // 제품명 검색
        $this->db->select('product_name as value, "product" as type');
        $this->db->like('product_name', $term);
        $this->db->limit(5);
        $products = $this->db->get('medical_products')->result_array();
        
        foreach ($products as $product) {
            $suggestions[] = array(
                'value' => $product['value'],
                'label' => $product['value'],
                'type' => 'product'
            );
        }
        
        // 회사명 검색
        $this->db->select('DISTINCT company_name as value');
        $this->db->like('company_name', $term);
        $this->db->limit(3);
        $companies = $this->db->get('medical_products')->result_array();
        
        foreach ($companies as $company) {
            if (!empty($company['value'])) {
                $suggestions[] = array(
                    'value' => $company['value'],
                    'label' => $company['value'] . ' (제조사)',
                    'type' => 'company'
                );
            }
        }
        
        return $suggestions;
    }

    /**
     * 검색 로그 저장
     */
    public function save_search_log($query, $result_count)
    {
        $data = array(
            'search_query' => $query,
            'result_count' => $result_count,
            'search_date' => date('Y-m-d H:i:s'),
            'ip_address' => $this->input->ip_address()
        );
        
        // search_logs 테이블이 있다면 저장
        if ($this->db->table_exists('search_logs')) {
            $this->db->insert('search_logs', $data);
        }
    }

    /**
     * 최근 검색어 가져오기
     */
    public function get_recent_searches($limit = 5)
    {
        if (!$this->db->table_exists('search_logs')) {
            return array('타이레놀', '아스피린', '한국얀센', '전문의약품', '진통제');
        }
        
        $this->db->select('search_query');
        $this->db->group_by('search_query');
        $this->db->order_by('search_date', 'DESC');
        $this->db->limit($limit);
        
        $results = $this->db->get('search_logs')->result_array();
        
        $searches = array();
        foreach ($results as $result) {
            $searches[] = $result['search_query'];
        }
        
        return $searches;
    }

    /**
     * 전체 제품 수 조회
     */
    public function get_total_product_count()
    {
        return $this->db->count_all('medical_products');
    }

    // 엑셀 헤더와 데이터 배열을 받아 medical_products 테이블에 일괄 입력
    public function insert_batch_data_from_excel($headers, $data_rows) {
        $insert_data = [];
        foreach ($data_rows as $row) {
            $item = [];
            foreach ($headers as $i => $col) {
                $item[trim($col)] = isset($row[$i]) ? trim($row[$i]) : null;
            }
            if (!empty($item['product_name'])) {
                $insert_data[] = $item;
            }
        }
        if (empty($insert_data)) {
            return ['success' => false, 'message' => '입력할 데이터가 없습니다.', 'count' => 0];
        }
        $this->db->empty_table('medical_products');
        $this->db->insert_batch('medical_products', $insert_data);
        return ['success' => true, 'message' => '데이터가 성공적으로 입력되었습니다.', 'count' => count($insert_data)];
    }
}
?> 
