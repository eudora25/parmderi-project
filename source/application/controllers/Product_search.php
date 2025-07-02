<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_search extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Product_search_model');
        $this->load->helper(array('url', 'html'));
        $this->load->library('form_validation');
    }

    /**
     * 메인 검색 페이지
     */
    public function index()
    {
        $data = array(
            'page_title' => '의료제품 정보 검색',
            'meta_description' => '전국 의료제품 정보를 자연어로 쉽게 검색해보세요',
            'total_products' => $this->Product_search_model->get_total_product_count(),
            'recent_searches' => $this->Product_search_model->get_recent_searches(5)
        );
        
        $this->load->view('product/search_main', $data);
    }

    /**
     * 자연어 검색 처리 (AJAX)
     */
    public function search()
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
            $search_result = $this->Product_search_model->natural_language_search($query);
            
            // 검색 기록 저장
            $this->Product_search_model->save_search_log($query, count($search_result['products']));
            
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
     * 제품 상세 정보 (AJAX)
     */
    public function detail($id = null)
    {
        if (!$id) {
            show_404();
            return;
        }

        $product = $this->Product_search_model->get_product_detail($id);
        
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
                'page_title' => $product->product_name . ' - 제품 정보'
            );
            $this->load->view('product/detail', $data);
        }
    }

    /**
     * 회사별 제품 리스트
     */
    public function company($company_name = null)
    {
        $page = (int)$this->input->get('page') ?: 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $result = $this->Product_search_model->get_products_by_company($company_name, $limit, $offset);
        
        $data = array(
            'products' => $result['products'],
            'total_count' => $result['total_count'],
            'company_name' => $company_name,
            'current_page' => $page,
            'total_pages' => ceil($result['total_count'] / $limit),
            'page_title' => ($company_name ?? '회사별') . ' 제품 목록'
        );
        
        $this->load->view('product/company_list', $data);
    }

    /**
     * 통계 페이지
     */
    public function stats()
    {
        $data = array(
            'company_stats' => $this->Product_search_model->get_company_statistics(),
            'category_stats' => $this->Product_search_model->get_category_statistics(),
            'recent_stats' => $this->Product_search_model->get_recent_statistics(),
            'page_title' => '전국 의료제품 통계'
        );
        
        $this->load->view('product/statistics', $data);
    }

    /**
     * 모바일 페이지
     */
    public function mobile()
    {
        $data = array(
            'page_title' => '제품 검색 - 모바일',
            'total_products' => $this->Product_search_model->get_total_product_count()
        );
        
        $this->load->view('product/mobile', $data);
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

        $suggestions = $this->Product_search_model->get_autocomplete_suggestions($term);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($suggestions);
    }

    /**
     * API 엔드포인트 (JSON 응답)
     */
    public function api()
    {
        $query = trim($this->input->get('q'));
        $format = $this->input->get('format') ?: 'json';
        
        if (empty($query)) {
            $response = array(
                'error' => 'Missing query parameter',
                'message' => 'Please provide a search query using ?q=keyword'
            );
        } else {
            try {
                $search_result = $this->Product_search_model->natural_language_search($query);
                $response = array(
                    'success' => true,
                    'query' => $query,
                    'results' => $search_result['products'],
                    'total_count' => $search_result['total_count'],
                    'search_type' => $search_result['search_type']
                );
            } catch (Exception $e) {
                $response = array(
                    'error' => 'Search failed',
                    'message' => $e->getMessage()
                );
            }
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} 