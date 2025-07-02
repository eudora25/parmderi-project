<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Question_manager extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Question_type_model');
        $this->load->library('form_validation');
        $this->load->helper('url');
    }

    /**
     * 질문 유형 관리 메인 페이지
     */
    public function index() {
        $data['question_types'] = $this->Question_type_model->get_all_question_types();
        $data['stats'] = $this->Question_type_model->get_question_stats();
        $data['popular_keywords'] = $this->Question_type_model->get_popular_keywords();
        
        $this->load->view('question/manager', $data);
    }

    /**
     * 질문 유형 상세 정보
     */
    public function detail($type_id) {
        $data['question_type'] = $this->Question_type_model->get_question_type($type_id);
        
        if (!$data['question_type']) {
            show_404();
        }
        
        $this->load->view('question/detail', $data);
    }

    /**
     * 샘플 질문 조회 API
     */
    public function samples() {
        $type_code = $this->input->get('type');
        $samples = $this->Question_type_model->get_sample_questions($type_code);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($samples, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 질문 유형 테스트
     */
    public function test() {
        $query = $this->input->post('query') ?: $this->input->get('q');
        
        if (!$query) {
            $data['result'] = null;
        } else {
            $data['result'] = $this->Question_type_model->detect_question_type($query);
        }
        
        $data['sample_questions'] = $this->Question_type_model->get_sample_questions();
        
        $this->load->view('question/test', $data);
    }

    /**
     * 질문 통계 조회
     */
    public function stats() {
        $days = $this->input->get('days') ?: 30;
        
        $data['stats'] = $this->Question_type_model->get_question_stats($days);
        $data['popular_keywords'] = $this->Question_type_model->get_popular_keywords();
        $data['days'] = $days;
        
        $this->load->view('question/stats', $data);
    }

    /**
     * 질문 로그 조회
     */
    public function logs() {
        $page = $this->input->get('page') ?: 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $this->db->select('ql.*, qt.type_name, qc.category_name');
        $this->db->from('question_logs ql');
        $this->db->join('question_types qt', 'ql.detected_type_id = qt.id', 'left');
        $this->db->join('question_categories qc', 'qt.category_id = qc.id', 'left');
        $this->db->order_by('ql.created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        $data['logs'] = $this->db->get()->result_array();
        
        // 총 개수
        $this->db->from('question_logs');
        $data['total_count'] = $this->db->count_all_results();
        $data['page'] = $page;
        $data['total_pages'] = ceil($data['total_count'] / $limit);
        
        $this->load->view('question/logs', $data);
    }

    /**
     * 질문 유형 추가 폼
     */
    public function add() {
        if ($this->input->post()) {
            $this->form_validation->set_rules('type_code', '질문 유형 코드', 'required|alpha_dash');
            $this->form_validation->set_rules('type_name', '질문 유형명', 'required');
            $this->form_validation->set_rules('category_id', '카테고리', 'required|integer');
            
            if ($this->form_validation->run()) {
                $data = array(
                    'category_id' => $this->input->post('category_id'),
                    'type_code' => $this->input->post('type_code'),
                    'type_name' => $this->input->post('type_name'),
                    'description' => $this->input->post('description'),
                    'keywords' => explode(',', $this->input->post('keywords')),
                    'sample_questions' => explode("\n", $this->input->post('sample_questions')),
                    'answer_template' => $this->input->post('answer_template'),
                    'db_fields' => explode(',', $this->input->post('db_fields')),
                    'priority' => $this->input->post('priority') ?: 0
                );
                
                if ($this->Question_type_model->add_question_type($data)) {
                    redirect('question_manager?success=added');
                } else {
                    $data['error'] = '질문 유형 추가에 실패했습니다.';
                }
            }
        }
        
        // 카테고리 목록
        $this->db->where('is_active', 1);
        $data['categories'] = $this->db->get('question_categories')->result_array();
        
        $this->load->view('question/add', $data);
    }

    /**
     * 질문 유형 수정
     */
    public function edit($type_id) {
        $data['question_type'] = $this->Question_type_model->get_question_type($type_id);
        
        if (!$data['question_type']) {
            show_404();
        }
        
        if ($this->input->post()) {
            $this->form_validation->set_rules('type_name', '질문 유형명', 'required');
            
            if ($this->form_validation->run()) {
                $update_data = array(
                    'type_name' => $this->input->post('type_name'),
                    'description' => $this->input->post('description'),
                    'keywords' => explode(',', $this->input->post('keywords')),
                    'sample_questions' => explode("\n", $this->input->post('sample_questions')),
                    'answer_template' => $this->input->post('answer_template'),
                    'db_fields' => explode(',', $this->input->post('db_fields')),
                    'priority' => $this->input->post('priority') ?: 0,
                    'is_active' => $this->input->post('is_active') ? 1 : 0
                );
                
                if ($this->Question_type_model->update_question_type($type_id, $update_data)) {
                    redirect('question_manager?success=updated');
                } else {
                    $data['error'] = '질문 유형 수정에 실패했습니다.';
                }
            }
        }
        
        $this->load->view('question/edit', $data);
    }

    /**
     * 질문 분석 API
     */
    public function analyze() {
        $query = $this->input->post('query') ?: $this->input->get('q');
        
        if (!$query) {
            $result = array('error' => '질문을 입력해주세요.');
        } else {
            $result = $this->Question_type_model->detect_question_type($query);
            
            if (!$result) {
                $result = array('message' => '매칭되는 질문 유형을 찾을 수 없습니다.');
            }
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 질문 유형 통계 API
     */
    public function api_stats() {
        $days = $this->input->get('days') ?: 7;
        
        $stats = $this->Question_type_model->get_question_stats($days);
        $popular = $this->Question_type_model->get_popular_keywords(10);
        
        $result = array(
            'stats' => $stats,
            'popular_keywords' => $popular,
            'period' => $days . '일간'
        );
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
} 