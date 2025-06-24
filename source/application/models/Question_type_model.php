<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Question_type_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 사용자 질문을 분석하여 질문 유형을 감지
     */
    public function detect_question_type($user_question, $hospital_name = '') {
        $start_time = microtime(true);
        
        // 1. 모든 활성화된 질문 유형 가져오기
        $this->db->select('qt.*, qc.category_name, qc.category_code');
        $this->db->from('question_types qt');
        $this->db->join('question_categories qc', 'qt.category_id = qc.id');
        $this->db->where('qt.is_active', 1);
        $this->db->where('qc.is_active', 1);
        $this->db->order_by('qt.priority', 'DESC');
        $query = $this->db->get();
        
        if ($query->num_rows() == 0) {
            return null;
        }
        
        $question_types = $query->result_array();
        $user_question_lower = strtolower($user_question);
        
        $best_match = null;
        $best_score = 0;
        
        foreach ($question_types as $type) {
            $score = $this->calculate_match_score($user_question_lower, $type);
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_match = $type;
                $best_match['match_score'] = $score;
            }
        }
        
        // 2. 질문 로그 저장
        $response_time = round((microtime(true) - $start_time) * 1000);
        $this->log_question($user_question, $best_match ? $best_match['id'] : null, $hospital_name, $response_time);
        
        return $best_match;
    }

    /**
     * 키워드 매칭 점수 계산
     */
    private function calculate_match_score($user_question, $question_type) {
        $score = 0;
        
        // JSON 키워드 디코딩
        $keywords = json_decode($question_type['keywords'], true);
        if (!$keywords) return 0;
        
        foreach ($keywords as $keyword) {
            $keyword_lower = strtolower($keyword);
            
            // 정확한 매칭 (높은 점수)
            if (strpos($user_question, $keyword_lower) !== false) {
                $score += 10;
                
                // 단어 경계에서 매칭되면 추가 점수
                if (preg_match('/\b' . preg_quote($keyword_lower, '/') . '\b/', $user_question)) {
                    $score += 5;
                }
            }
            
            // 부분 매칭 (낮은 점수)
            if (similar_text($user_question, $keyword_lower) > strlen($keyword_lower) * 0.7) {
                $score += 3;
            }
        }
        
        // 우선순위 보너스
        $score += $question_type['priority'] * 0.1;
        
        return $score;
    }

    /**
     * 질문 로그 저장
     */
    public function log_question($user_question, $detected_type_id = null, $hospital_name = '', $response_time = 0, $search_results_count = 0) {
        $data = array(
            'user_question' => $user_question,
            'detected_type_id' => $detected_type_id,
            'hospital_name' => $hospital_name,
            'search_results_count' => $search_results_count,
            'response_time_ms' => $response_time,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        );
        
        return $this->db->insert('question_logs', $data);
    }

    /**
     * 모든 질문 유형 조회
     */
    public function get_all_question_types($category_code = 'HOSPITAL') {
        $this->db->select('qt.*, qc.category_name, qc.category_code');
        $this->db->from('question_types qt');
        $this->db->join('question_categories qc', 'qt.category_id = qc.id');
        
        if ($category_code) {
            $this->db->where('qc.category_code', $category_code);
        }
        
        $this->db->where('qt.is_active', 1);
        $this->db->order_by('qt.priority', 'DESC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * 특정 질문 유형 조회
     */
    public function get_question_type($type_id) {
        $this->db->select('qt.*, qc.category_name, qc.category_code');
        $this->db->from('question_types qt');
        $this->db->join('question_categories qc', 'qt.category_id = qc.id');
        $this->db->where('qt.id', $type_id);
        
        $query = $this->db->get();
        return $query->row_array();
    }

    /**
     * 답변 템플릿 처리
     */
    public function process_answer_template($question_type, $hospital_data) {
        if (!isset($question_type['answer_template'])) {
            return '정보를 찾을 수 없습니다.';
        }
        
        $template = $question_type['answer_template'];
        
        // 템플릿 변수 치환
        foreach ($hospital_data as $key => $value) {
            if ($value !== null && $value !== '') {
                $template = str_replace('{{' . $key . '}}', $value, $template);
            }
        }
        
        // hospital_name 매핑 처리 (institution_name을 hospital_name으로)
        if (isset($hospital_data['institution_name'])) {
            $template = str_replace('{{hospital_name}}', $hospital_data['institution_name'], $template);
        }
        
        // 빈 값 처리
        $template = preg_replace('/\{\{[^}]+\}\}/', '정보 없음', $template);
        
        return $template;
    }

    /**
     * 질문 통계 조회
     */
    public function get_question_stats($days = 30) {
        $this->db->select('qts.*');
        $this->db->from('question_type_stats qts');
        $this->db->where('qts.last_used >=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
        $this->db->order_by('qts.question_count', 'DESC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * 인기 질문 키워드 분석
     */
    public function get_popular_keywords($limit = 20) {
        $this->db->select('user_question, COUNT(*) as count');
        $this->db->from('question_logs');
        $this->db->where('created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        $this->db->group_by('user_question');
        $this->db->order_by('count', 'DESC');
        $this->db->limit($limit);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * 질문 유형별 샘플 질문 조회
     */
    public function get_sample_questions($type_code = null) {
        $this->db->select('qt.type_code, qt.type_name, qt.sample_questions');
        $this->db->from('question_types qt');
        $this->db->join('question_categories qc', 'qt.category_id = qc.id');
        $this->db->where('qc.category_code', 'HOSPITAL');
        $this->db->where('qt.is_active', 1);
        
        if ($type_code) {
            $this->db->where('qt.type_code', $type_code);
        }
        
        $this->db->order_by('qt.priority', 'DESC');
        
        $query = $this->db->get();
        $results = $query->result_array();
        
        $samples = array();
        foreach ($results as $result) {
            $questions = json_decode($result['sample_questions'], true);
            if ($questions) {
                $samples[$result['type_code']] = array(
                    'type_name' => $result['type_name'],
                    'questions' => $questions
                );
            }
        }
        
        return $samples;
    }

    /**
     * 새로운 질문 유형 추가
     */
    public function add_question_type($data) {
        // JSON 필드 처리
        if (isset($data['keywords']) && is_array($data['keywords'])) {
            $data['keywords'] = json_encode($data['keywords']);
        }
        if (isset($data['sample_questions']) && is_array($data['sample_questions'])) {
            $data['sample_questions'] = json_encode($data['sample_questions']);
        }
        if (isset($data['db_fields']) && is_array($data['db_fields'])) {
            $data['db_fields'] = json_encode($data['db_fields']);
        }
        
        return $this->db->insert('question_types', $data);
    }

    /**
     * 질문 유형 업데이트
     */
    public function update_question_type($id, $data) {
        // JSON 필드 처리
        if (isset($data['keywords']) && is_array($data['keywords'])) {
            $data['keywords'] = json_encode($data['keywords']);
        }
        if (isset($data['sample_questions']) && is_array($data['sample_questions'])) {
            $data['sample_questions'] = json_encode($data['sample_questions']);
        }
        if (isset($data['db_fields']) && is_array($data['db_fields'])) {
            $data['db_fields'] = json_encode($data['db_fields']);
        }
        
        $this->db->where('id', $id);
        return $this->db->update('question_types', $data);
    }
} 