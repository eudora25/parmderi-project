<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hospital_search extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Hospital_search_model');
        $this->load->helper(array('url', 'html'));
        $this->load->library('form_validation');
    }

    /**
     * 메인 검색 페이지
     */
    public function index()
    {
        $data = array(
            'page_title' => '병원 정보 검색',
            'meta_description' => '전국 의료기관 정보를 자연어로 쉽게 검색해보세요',
            'total_hospitals' => $this->Hospital_search_model->get_total_hospital_count(),
            'recent_searches' => $this->Hospital_search_model->get_recent_searches(5)
        );
        
        $this->load->view('hospital/search_main', $data);
    }

    /**
     * 자연어 검색 처리 (AJAX)
     */
    public function search()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $lat = $this->input->post('lat');
        $lng = $this->input->post('lng');
        if (!empty($lat) && !empty($lng)) {
            // 근처 병원 검색
            try {
                $search_result = $this->Hospital_search_model->search_nearby(floatval($lat), floatval($lng), 3); // 반경 3km
                echo json_encode(array(
                    'success' => true,
                    'search_type' => 'nearby',
                    'hospitals' => $search_result['hospitals'],
                    'total_count' => $search_result['total_count'],
                    'message' => $search_result['message'],
                ));
            } catch (Exception $e) {
                log_message('error', '근처 병원 검색 오류: ' . $e->getMessage());
                echo json_encode(array(
                    'success' => false,
                    'message' => '근처 병원 검색 중 오류가 발생했습니다.'
                ));
            }
            return;
        }
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
            $search_result = $this->Hospital_search_model->natural_language_search($query);
            // 검색 기록 저장
            $this->Hospital_search_model->save_search_log($query, count($search_result['hospitals']));
            echo json_encode(array(
                'success' => true,
                'query' => $query,
                'search_type' => $search_result['search_type'],
                'search_params' => $search_result['search_params'],
                'hospitals' => $search_result['hospitals'],
                'total_count' => $search_result['total_count'],
                'message' => $search_result['message'],
                'analysis' => isset($search_result['analysis']) ? $search_result['analysis'] : null,
                'question_type' => isset($search_result['question_type']) ? $search_result['question_type'] : null
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
     * 질문 분석만 수행 (AJAX)
     */
    public function analyze()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $query = trim($this->input->post('query'));
        
        if (empty($query)) {
            echo json_encode(array(
                'success' => false,
                'message' => '분석할 질문을 입력해주세요.'
            ));
            return;
        }

        try {
            // 질문 분석만 수행
            $analysis_result = $this->Hospital_search_model->analyze_query_only($query);
            
            echo json_encode(array(
                'success' => true,
                'query' => $query,
                'analysis' => $analysis_result
            ));
            
        } catch (Exception $e) {
            log_message('error', '질문 분석 오류: ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => '질문 분석 중 오류가 발생했습니다.'
            ));
        }
    }

    /**
     * 병원 상세 정보 (AJAX)
     */
    public function detail($id = null)
    {
        if (!$id) {
            show_404();
            return;
        }

        $hospital = $this->Hospital_search_model->get_hospital_detail($id);
        
        if (!$hospital) {
            show_404();
            return;
        }

        if ($this->input->is_ajax_request()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'success' => true,
                'hospital' => $hospital
            ));
        } else {
            $data = array(
                'hospital' => $hospital,
                'page_title' => $hospital->institution_name . ' - 병원 정보'
            );
            $this->load->view('hospital/detail', $data);
        }
    }

    /**
     * 지역별 병원 리스트
     */
    public function region($sido_code = null, $sigungu_code = null)
    {
        $page = (int)$this->input->get('page') ?: 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $result = $this->Hospital_search_model->get_hospitals_by_region($sido_code, $sigungu_code, $limit, $offset);
        
        $data = array(
            'hospitals' => $result['hospitals'],
            'total_count' => $result['total_count'],
            'sido_code' => $sido_code,
            'sigungu_code' => $sigungu_code,
            'current_page' => $page,
            'total_pages' => ceil($result['total_count'] / $limit),
            'page_title' => ($result['region_name'] ?? '지역별') . ' 병원 목록'
        );
        
        $this->load->view('hospital/region_list', $data);
    }

    /**
     * 통계 페이지
     */
    public function stats()
    {
        $data = array(
            'region_stats' => $this->Hospital_search_model->get_region_statistics(),
            'category_stats' => $this->Hospital_search_model->get_category_statistics(),
            'recent_stats' => $this->Hospital_search_model->get_recent_statistics(),
            'page_title' => '전국 의료기관 통계'
        );
        
        $this->load->view('hospital/statistics', $data);
    }

    /**
     * 모바일 페이지
     */
    public function mobile()
    {
        $data = array(
            'page_title' => '병원 검색 - 모바일',
            'total_hospitals' => $this->Hospital_search_model->get_total_hospital_count()
        );
        
        $this->load->view('hospital/mobile', $data);
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

        $suggestions = $this->Hospital_search_model->get_autocomplete_suggestions($term);
        
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
                $search_result = $this->Hospital_search_model->natural_language_search($query);
                $response = array(
                    'success' => true,
                    'query' => $query,
                    'results' => $search_result['hospitals'],
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
