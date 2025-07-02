<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_search_model extends CI_Model {

    private $table = 'medical_products';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 자연어 검색 메인 함수
     */
    public function natural_language_search($query)
    {
        $start_time = microtime(true);
        
        // 검색어 분석
        $analysis = $this->analyze_query($query);
        
        // 검색 타입별 처리
        switch ($analysis['type']) {
            case 'product_detail':
                $result = $this->search_product_detail($analysis);
                break;
            case 'company_search':
                $result = $this->search_by_company($analysis);
                break;
            case 'category_search':
                $result = $this->search_by_category($analysis);
                break;
            case 'ingredient_search':
                $result = $this->search_by_ingredient($analysis);
                break;
            case 'price_search':
                $result = $this->search_by_price($analysis);
                break;
            case 'mixed_search':
                $result = $this->search_mixed($analysis);
                break;
            default:
                $result = $this->search_general($analysis);
        }
        
        // 검색 결과 수 업데이트
        $result_count = isset($result['products']) ? count($result['products']) : 0;
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        $result['analysis'] = $analysis;
        $result['response_time'] = $response_time;
        
        return $result;
    }

    /**
     * 검색어 분석
     */
    private function analyze_query($query)
    {
        $query = trim($query);
        $query_lower = strtolower($query);
        
        // 키워드 정의
        $company_keywords = array('업체', '제조사', '회사', '제약회사', '제약', '기업');
        $ingredient_keywords = array('성분', '원료', '주성분', '활성성분', '성분명');
        $price_keywords = array('가격', '약가', '비용', '급여', '비급여', '수가');
        $category_keywords = array('분류', '종류', '카테고리', '구분');
        $effect_keywords = array('효과', '효능', '치료', '증상', '질병');
        $formulation_keywords = array('제형', '정제', '캡슐', '시럽', '주사', '연고');
        
        $analysis = array(
            'original_query' => $query,
            'type' => 'general',
            'product_name' => null,
            'company_name' => null,
            'ingredient_name' => null,
            'category' => null,
            'price_range' => null,
            'formulation' => null,
            'keywords' => array(),
            'search_params' => array()
        );

        // 제품명 직접 검색 패턴
        if (preg_match('/(.+)(정보|상세|알려|찾아|검색)/u', $query, $matches)) {
            $potential_product = trim($matches[1]);
            if (strlen($potential_product) > 1) {
                $analysis['type'] = 'product_detail';
                $analysis['product_name'] = $potential_product;
            }
        }

        // 회사 검색 패턴
        foreach ($company_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                $analysis['type'] = 'company_search';
                $analysis['keywords'][] = $keyword;
                // 회사명 추출 시도
                $company_name = $this->extract_company_name($query);
                if ($company_name) {
                    $analysis['company_name'] = $company_name;
                }
                break;
            }
        }

        // 성분 검색 패턴
        foreach ($ingredient_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                $analysis['type'] = 'ingredient_search';
                $analysis['keywords'][] = $keyword;
                $ingredient_name = $this->extract_ingredient_name($query);
                if ($ingredient_name) {
                    $analysis['ingredient_name'] = $ingredient_name;
                }
                break;
            }
        }

        // 가격 검색 패턴
        foreach ($price_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                $analysis['type'] = 'price_search';
                $analysis['keywords'][] = $keyword;
                break;
            }
        }

        // 분류 검색 패턴
        foreach ($category_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                $analysis['type'] = 'category_search';
                $analysis['keywords'][] = $keyword;
                break;
            }
        }

        // 제형 검색 패턴
        foreach ($formulation_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                $analysis['formulation'] = $keyword;
                $analysis['keywords'][] = $keyword;
            }
        }

        // 복합 검색 패턴 감지
        if (count($analysis['keywords']) > 1) {
            $analysis['type'] = 'mixed_search';
        }

        return $analysis;
    }

    /**
     * 회사명 추출
     */
    private function extract_company_name($query)
    {
        // 일반적인 제약회사 패턴 매칭
        $patterns = array(
            '/(.+)(제약|팜|바이오|헬스케어|의료|메디|약품)/',
            '/(\w+)\s*(제품|의약품|약)/',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $query, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }

    /**
     * 성분명 추출
     */
    private function extract_ingredient_name($query)
    {
        // 성분명 키워드 앞의 단어 추출
        if (preg_match('/(\w+)\s*(성분|원료|주성분)/u', $query, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * 제품 상세 정보 검색
     */
    private function search_product_detail($analysis)
    {
        if (!$analysis['product_name']) {
            return array(
                'products' => array(),
                'total_count' => 0,
                'search_type' => 'product_detail',
                'message' => '제품명을 포함해서 질문해 주세요.',
                'search_params' => $analysis['search_params']
            );
        }
        
        $this->db->like('product_name', $analysis['product_name']);
        $this->db->limit(10);
        
        $products = $this->db->get($this->table)->result_array();
        
        if (empty($products)) {
            return array(
                'products' => array(),
                'total_count' => 0,
                'search_type' => 'product_detail',
                'message' => "'{$analysis['product_name']}'에 해당하는 제품을 찾을 수 없습니다.",
                'search_params' => $analysis['search_params']
            );
        }
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'product_detail',
            'message' => "'{$analysis['product_name']}'에 대한 제품 정보입니다.",
            'search_params' => $analysis['search_params']
        );
    }

    /**
     * 회사별 검색
     */
    private function search_by_company($analysis)
    {
        if ($analysis['company_name']) {
            $this->db->like('company_name', $analysis['company_name']);
        } else {
            // 일반적인 회사 검색
            $companies = $this->get_popular_companies();
            if (!empty($companies)) {
                $this->db->where_in('company_name', array_column($companies, 'company_name'));
            }
        }
        
        $this->db->limit(20);
        $products = $this->db->get($this->table)->result_array();
        
        $message = $analysis['company_name'] ? 
            "'{$analysis['company_name']}' 회사의 제품들입니다." :
            "주요 제약회사의 제품들입니다.";
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'company_search',
            'message' => $message,
            'search_params' => $analysis['search_params']
        );
    }

    /**
     * 분류별 검색
     */
    private function search_by_category($analysis)
    {
        $this->db->where('classification_name_1 !=', '');
        $this->db->order_by('classification_name_1, product_name');
        $this->db->limit(20);
        
        $products = $this->db->get($this->table)->result_array();
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'category_search',
            'message' => '분류별 제품 정보입니다.',
            'search_params' => $analysis['search_params']
        );
    }

    /**
     * 성분별 검색
     */
    private function search_by_ingredient($analysis)
    {
        if ($analysis['ingredient_name']) {
            $this->db->like('ingredient_name_en', $analysis['ingredient_name']);
        } else {
            $this->db->where('ingredient_name_en !=', '');
        }
        
        $this->db->limit(20);
        $products = $this->db->get($this->table)->result_array();
        
        $message = $analysis['ingredient_name'] ? 
            "'{$analysis['ingredient_name']}' 성분의 제품들입니다." :
            "성분 정보가 있는 제품들입니다.";
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'ingredient_search',
            'message' => $message,
            'search_params' => $analysis['search_params']
        );
    }

    /**
     * 가격별 검색
     */
    private function search_by_price($analysis)
    {
        $this->db->where('drug_price IS NOT NULL');
        $this->db->where('drug_price >', 0);
        $this->db->order_by('drug_price', 'ASC');
        $this->db->limit(20);
        
        $products = $this->db->get($this->table)->result_array();
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'price_search',
            'message' => '가격 정보가 있는 제품들입니다.',
            'search_params' => $analysis['search_params']
        );
    }

    /**
     * 복합 검색
     */
    private function search_mixed($analysis)
    {
        // 여러 조건을 조합하여 검색
        if ($analysis['company_name']) {
            $this->db->like('company_name', $analysis['company_name']);
        }
        
        if ($analysis['ingredient_name']) {
            $this->db->like('ingredient_name_en', $analysis['ingredient_name']);
        }
        
        if ($analysis['formulation']) {
            $this->db->like('formulation', $analysis['formulation']);
        }
        
        $this->db->limit(20);
        $products = $this->db->get($this->table)->result_array();
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'mixed_search',
            'message' => '조건에 맞는 제품들입니다.',
            'search_params' => $analysis['search_params']
        );
    }

    /**
     * 일반 검색
     */
    private function search_general($analysis)
    {
        $query = $analysis['original_query'];
        
        $this->db->group_start();
        $this->db->like('product_name', $query);
        $this->db->or_like('company_name', $query);
        $this->db->or_like('ingredient_name_en', $query);
        $this->db->or_like('classification_name_1', $query);
        $this->db->group_end();
        
        $this->db->limit(20);
        $products = $this->db->get($this->table)->result_array();
        
        return array(
            'products' => $products,
            'total_count' => count($products),
            'search_type' => 'general',
            'message' => "'{$query}' 검색 결과입니다.",
            'search_params' => $analysis['search_params']
        );
    }

    /**
     * 제품 상세 정보
     */
    public function get_product_detail($id)
    {
        $this->db->where('id', $id);
        return $this->db->get($this->table)->row();
    }

    /**
     * 전체 제품 수
     */
    public function get_total_product_count()
    {
        return $this->db->count_all($this->table);
    }

    /**
     * 회사별 통계
     */
    public function get_company_statistics()
    {
        $this->db->select('company_name, COUNT(*) as count');
        $this->db->where('company_name !=', '');
        $this->db->group_by('company_name');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        
        return $this->db->get($this->table)->result_array();
    }

    /**
     * 분류별 통계
     */
    public function get_category_statistics()
    {
        $this->db->select('classification_name_1, COUNT(*) as count');
        $this->db->where('classification_name_1 !=', '');
        $this->db->group_by('classification_name_1');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        
        return $this->db->get($this->table)->result_array();
    }

    /**
     * 최근 통계
     */
    public function get_recent_statistics()
    {
        // CSO 제품 통계
        $cso_count = $this->db->where('cso_product', 1)->count_all_results($this->table);
        
        // 급여/비급여 통계  
        $this->db->select('coverage, COUNT(*) as count');
        $this->db->group_by('coverage');
        $coverage_stats = $this->db->get($this->table)->result_array();
        
        return array(
            'cso_products' => $cso_count,
            'coverage_stats' => $coverage_stats
        );
    }

    /**
     * 자동완성 제안
     */
    public function get_autocomplete_suggestions($term)
    {
        $suggestions = array();
        
        // 제품명 제안
        $this->db->distinct();
        $this->db->select('product_name');
        $this->db->like('product_name', $term);
        $this->db->limit(5);
        $products = $this->db->get($this->table)->result_array();
        
        foreach ($products as $product) {
            $suggestions[] = array(
                'label' => $product['product_name'],
                'value' => $product['product_name'],
                'type' => 'product'
            );
        }
        
        // 회사명 제안
        $this->db->distinct();
        $this->db->select('company_name');
        $this->db->like('company_name', $term);
        $this->db->limit(5);
        $companies = $this->db->get($this->table)->result_array();
        
        foreach ($companies as $company) {
            $suggestions[] = array(
                'label' => $company['company_name'],
                'value' => $company['company_name'],
                'type' => 'company'
            );
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
            'search_time' => date('Y-m-d H:i:s'),
            'user_ip' => $this->input->ip_address()
        );
        
        // 검색 로그 테이블이 있다면 저장
        if ($this->db->table_exists('product_search_logs')) {
            $this->db->insert('product_search_logs', $data);
        }
    }

    /**
     * 최근 검색어
     */
    public function get_recent_searches($limit = 5)
    {
        if (!$this->db->table_exists('product_search_logs')) {
            return array();
        }
        
        $this->db->select('search_query, COUNT(*) as frequency');
        $this->db->group_by('search_query');
        $this->db->order_by('MAX(search_time)', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get('product_search_logs')->result_array();
    }

    /**
     * 회사별 제품 목록 (페이징)
     */
    public function get_products_by_company($company_name, $limit = 20, $offset = 0)
    {
        if ($company_name) {
            $this->db->like('company_name', $company_name);
        }
        
        $total_count = $this->db->count_all_results($this->table, false);
        
        $this->db->limit($limit, $offset);
        $products = $this->db->get()->result_array();
        
        return array(
            'products' => $products,
            'total_count' => $total_count
        );
    }

    /**
     * 인기 제약회사 목록
     */
    private function get_popular_companies()
    {
        $this->db->select('company_name');
        $this->db->where('company_name !=', '');
        $this->db->group_by('company_name');
        $this->db->order_by('COUNT(*)', 'DESC');
        $this->db->limit(10);
        
        return $this->db->get($this->table)->result_array();
    }
} 