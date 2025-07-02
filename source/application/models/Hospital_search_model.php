<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hospital_search_model extends CI_Model {

    private $table = 'medical_institution';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('Question_type_model');
    }

    /**
     * 자연어 검색 메인 함수 (질문 유형 시스템 통합)
     */
    public function natural_language_search($query)
    {
        $start_time = microtime(true);
        
        // 1. 질문 유형 감지
        $question_type = $this->Question_type_model->detect_question_type($query);
        
        // 2. 기존 검색어 분석 (호환성 유지)
        $analysis = $this->analyze_query($query);
        
        // 3. 질문 유형에 따른 검색 처리
        if ($question_type && $question_type['match_score'] > 10) {
            $result = $this->search_by_question_type($query, $question_type, $analysis);
        } else {
            // 4. 기존 검색 타입별 처리 (fallback)
            switch ($analysis['type']) {
                case 'hospital_detail':
                    $result = $this->search_hospital_detail($analysis);
                    break;
                case 'region_search':
                    $result = $this->search_by_region($analysis);
                    break;
                case 'category_search':
                    $result = $this->search_by_category($analysis);
                    break;
                case 'mixed_search':
                    $result = $this->search_mixed($analysis);
                    break;
                default:
                    $result = $this->search_general($analysis);
            }
        }
        
        // 5. 검색 결과 수 업데이트
        $result_count = isset($result['hospitals']) ? count($result['hospitals']) : 0;
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        if ($question_type) {
            $this->Question_type_model->log_question(
                $query, 
                $question_type['id'], 
                $analysis['hospital_name'] ?? '', 
                $response_time, 
                $result_count
            );
        }
        
        // 6. 질문 유형 정보 추가
        $result['question_type'] = $question_type;
        $result['analysis'] = $analysis;
        
        return $result;
    }

    /**
     * 질문 유형에 따른 검색 처리
     */
    private function search_by_question_type($query, $question_type, $analysis)
    {
        $type_code = $question_type['type_code'];
        
        switch ($type_code) {
            case 'BASIC_INFO':
            case 'LOCATION':
            case 'CONTACT':
            case 'HOMEPAGE':
            case 'SPECIALTY':
            case 'MEDICAL_STAFF':
            case 'HISTORY':
            case 'COORDINATES':
                return $this->search_hospital_detail_by_type($analysis, $question_type);
                
            case 'REGIONAL_SEARCH':
                return $this->search_by_region($analysis);
                
            case 'CATEGORY_SEARCH':
                return $this->search_by_category($analysis);
                
            case 'COMPLEX_SEARCH':
                return $this->search_mixed($analysis);
                
            default:
                return $this->search_general($analysis);
        }
    }

    /**
     * 질문 유형별 병원 상세 정보 검색
     */
    private function search_hospital_detail_by_type($analysis, $question_type)
    {
        if (!$analysis['hospital_name']) {
            return array(
                'hospitals' => array(),
                'total_count' => 0,
                'search_type' => 'hospital_detail',
                'message' => '병원명을 포함해서 질문해 주세요.',
                'question_type' => $question_type
            );
        }
        
        $this->db->where('state', 'Y');
        $this->db->like('institution_name', $analysis['hospital_name']);
        $this->db->limit(10);
        
        $hospitals = $this->db->get($this->table)->result();
        
        if (empty($hospitals)) {
            return array(
                'hospitals' => array(),
                'total_count' => 0,
                'search_type' => 'hospital_detail',
                'message' => "'{$analysis['hospital_name']}'에 해당하는 병원을 찾을 수 없습니다.",
                'question_type' => $question_type
            );
        }
        
        // 질문 유형에 따른 답변 생성
        $formatted_hospitals = array();
        foreach ($hospitals as $hospital) {
            $hospital_array = (array) $hospital;
            
            // 답변 템플릿 처리
            $answer = $this->Question_type_model->process_answer_template($question_type, $hospital_array);
            
            $hospital_array['formatted_answer'] = $answer;
            $formatted_hospitals[] = $hospital_array;
        }
        
        return array(
            'hospitals' => $formatted_hospitals,
            'total_count' => count($formatted_hospitals),
            'search_type' => 'hospital_detail',
            'message' => "'{$analysis['hospital_name']}'에 대한 {$question_type['type_name']} 정보입니다.",
            'question_type' => $question_type
        );
    }

    /**
     * 검색어 분석
     */
    private function analyze_query($query)
    {
        $query = trim(strtolower($query));
        
        // 키워드 정의
        $location_keywords = array('위치', '주소', '어디', '찾아가는', '길', '어디에');
        $contact_keywords = array('전화번호', '연락처', '번호', '전화', 'tel');
        $info_keywords = array('정보', '상세정보', '개요', '소개');
        $category_keywords = array('종류', '병원급수', '무슨', '분류');
        $opening_keywords = array('개원', '개설일', '언제', '설립');
        $homepage_keywords = array('홈페이지', '웹사이트', '인터넷', 'url', '사이트');
        
        // 지역명 패턴
        $regions = array(
            '서울' => '110000', '부산' => '210000', '대구' => '230000', '인천' => '240000',
            '광주' => '250000', '대전' => '260000', '울산' => '270000', '세종' => '290000',
            '경기' => '310000', '강원' => '320000', '충북' => '330000', '충남' => '340000',
            '전북' => '350000', '전남' => '360000', '경북' => '370000', '경남' => '380000',
            '제주' => '390000'
        );
        
        // 병원 분류 패턴  
        $categories = array(
            '종합병원' => '29', '병원' => '28', '의원' => '31', '치과' => '32',
            '한의원' => '33', '보건소' => '91', '약국' => '92'
        );

        $analysis = array(
            'original_query' => $query,
            'type' => 'general',
            'hospital_name' => null,
            'region' => null,
            'category' => null,
            'info_type' => 'basic',
            'keywords' => array()
        );

        // 병원명 + 정보 요청 패턴 분석
        foreach ($location_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $analysis['info_type'] = 'location';
                $analysis['keywords'][] = $keyword;
                break;
            }
        }

        foreach ($contact_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $analysis['info_type'] = 'contact';
                $analysis['keywords'][] = $keyword;
                break;
            }
        }

        foreach ($homepage_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $analysis['info_type'] = 'homepage';
                $analysis['keywords'][] = $keyword;
                break;
            }
        }

        // 지역 검색 패턴
        foreach ($regions as $region_name => $region_code) {
            if (strpos($query, $region_name) !== false) {
                $analysis['region'] = array(
                    'name' => $region_name,
                    'code' => $region_code
                );
                break;
            }
        }

        // 카테고리 검색 패턴
        foreach ($categories as $category_name => $category_code) {
            if (strpos($query, $category_name) !== false) {
                $analysis['category'] = array(
                    'name' => $category_name,
                    'code' => $category_code
                );
                break;
            }
        }

        // 병원명 추출 시도 (우선순위 1)
        $hospital_name = $this->extract_hospital_name($query);
        if ($hospital_name) {
            $analysis['hospital_name'] = $hospital_name;
            $analysis['type'] = 'hospital_detail';
        }
        // 병원명이 없을 때만 지역/카테고리 검색
        else {
            // 검색 타입 결정
            if ($analysis['region'] && $analysis['category']) {
                $analysis['type'] = 'mixed_search';
            } elseif ($analysis['region']) {
                $analysis['type'] = 'region_search';
            } elseif ($analysis['category']) {
                $analysis['type'] = 'category_search';
            }
        }

        return $analysis;
    }

    /**
     * 병원명 추출 (질문 키워드 앞의 문장만 병원명으로 인식)
     */
    private function extract_hospital_name($query)
    {
        // 질문 키워드들 정의
        $question_keywords = array(
            '위치', '주소', '어디', '찾아가는', '길', '어디에',
            '전화번호', '연락처', '번호', '전화', 'tel',
            '정보', '상세정보', '개요', '소개',
            '종류', '병원급수', '무슨', '분류',
            '개원', '개설일', '언제', '설립',
            '홈페이지', '웹사이트', '인터넷', 'url', '사이트'
        );
        
        $hospital_name = '';
        $found_keyword = false;
        
        // 각 질문 키워드에 대해 검사
        foreach ($question_keywords as $keyword) {
            $pos = strpos($query, $keyword);
            if ($pos !== false) {
                // 키워드 앞의 문장을 병원명으로 추출
                $hospital_name = trim(substr($query, 0, $pos));
                $found_keyword = true;
                break;
            }
        }
        
        // 질문 키워드가 없으면 전체를 병원명으로 간주하지 않음
        if (!$found_keyword) {
            return null;
        }
        
        // 병원명 정리
        $hospital_name = trim($hospital_name);
        
        // 최소 2글자 이상이어야 유효한 병원명
        if (strlen($hospital_name) >= 2) {
            return $hospital_name;
        }
        
        return null;
    }

    /**
     * 병원 상세 정보 검색
     */
    private function search_hospital_detail($analysis)
    {
        $this->db->where('state', 'Y');
        $this->db->like('institution_name', $analysis['hospital_name']);
        $this->db->limit(10);
        
        $hospitals = $this->db->get($this->table)->result();
        
        $message = '';
        switch ($analysis['info_type']) {
            case 'location':
                $message = "'{$analysis['hospital_name']}' 위치 정보입니다.";
                break;
            case 'contact':
                $message = "'{$analysis['hospital_name']}' 연락처 정보입니다.";
                break;
            case 'homepage':
                $message = "'{$analysis['hospital_name']}' 홈페이지 정보입니다.";
                break;
            default:
                $message = "'{$analysis['hospital_name']}' 검색 결과입니다.";
        }

        return array(
            'search_type' => 'hospital_detail',
            'search_params' => $analysis,
            'hospitals' => $hospitals,
            'total_count' => count($hospitals),
            'message' => $message
        );
    }

    /**
     * 지역별 검색
     */
    private function search_by_region($analysis)
    {
        $this->db->where('state', 'Y');
        $this->db->where('sido_code', $analysis['region']['code']);
        $this->db->limit(50);
        $this->db->order_by('institution_name', 'ASC');
        
        $hospitals = $this->db->get($this->table)->result();
        
        // 총 개수 조회
        $this->db->where('state', 'Y');
        $this->db->where('sido_code', $analysis['region']['code']);
        $total_count = $this->db->count_all_results($this->table);

        return array(
            'search_type' => 'region_search',
            'search_params' => $analysis,
            'hospitals' => $hospitals,
            'total_count' => $total_count,
            'message' => "'{$analysis['region']['name']}' 지역 의료기관 {$total_count}개를 찾았습니다."
        );
    }

    /**
     * 카테고리별 검색
     */
    private function search_by_category($analysis)
    {
        $this->db->where('state', 'Y');
        $this->db->like('category_name', $analysis['category']['name']);
        $this->db->limit(50);
        $this->db->order_by('institution_name', 'ASC');
        
        $hospitals = $this->db->get($this->table)->result();
        
        // 총 개수 조회
        $this->db->where('state', 'Y');
        $this->db->like('category_name', $analysis['category']['name']);
        $total_count = $this->db->count_all_results($this->table);

        return array(
            'search_type' => 'category_search',
            'search_params' => $analysis,
            'hospitals' => $hospitals,
            'total_count' => $total_count,
            'message' => "'{$analysis['category']['name']}' {$total_count}개를 찾았습니다."
        );
    }

    /**
     * 복합 검색 (지역 + 카테고리)
     */
    private function search_mixed($analysis)
    {
        $this->db->where('state', 'Y');
        $this->db->where('sido_code', $analysis['region']['code']);
        $this->db->like('category_name', $analysis['category']['name']);
        $this->db->limit(50);
        $this->db->order_by('institution_name', 'ASC');
        
        $hospitals = $this->db->get($this->table)->result();
        
        // 총 개수 조회
        $this->db->where('state', 'Y');
        $this->db->where('sido_code', $analysis['region']['code']);
        $this->db->like('category_name', $analysis['category']['name']);
        $total_count = $this->db->count_all_results($this->table);

        return array(
            'search_type' => 'mixed_search',
            'search_params' => $analysis,
            'hospitals' => $hospitals,
            'total_count' => $total_count,
            'message' => "'{$analysis['region']['name']} {$analysis['category']['name']}' {$total_count}개를 찾았습니다."
        );
    }

    /**
     * 일반 검색
     */
    private function search_general($analysis)
    {
        $this->db->where('state', 'Y');
        $this->db->group_start();
        $this->db->like('institution_name', $analysis['original_query']);
        $this->db->or_like('address', $analysis['original_query']);
        $this->db->or_like('category_name', $analysis['original_query']);
        $this->db->group_end();
        $this->db->limit(30);
        $this->db->order_by('institution_name', 'ASC');
        
        $hospitals = $this->db->get($this->table)->result();

        return array(
            'search_type' => 'general_search',
            'search_params' => $analysis,
            'hospitals' => $hospitals,
            'total_count' => count($hospitals),
            'message' => "'{$analysis['original_query']}' 검색 결과 " . count($hospitals) . "개를 찾았습니다."
        );
    }

    /**
     * 병원 상세 정보 조회
     */
    public function get_hospital_detail($id)
    {
        $this->db->where('id', $id);
        $this->db->where('state', 'Y');
        return $this->db->get($this->table)->row();
    }

    /**
     * 전체 병원 수 조회
     */
    public function get_total_hospital_count()
    {
        $this->db->where('state', 'Y');
        return $this->db->count_all_results($this->table);
    }

    /**
     * 지역별 통계
     */
    public function get_region_statistics()
    {
        $this->db->select('sido_name, COUNT(*) as count');
        $this->db->where('state', 'Y');
        $this->db->group_by('sido_name');
        $this->db->order_by('count', 'DESC');
        return $this->db->get($this->table)->result();
    }

    /**
     * 카테고리별 통계
     */
    public function get_category_statistics()
    {
        $this->db->select('category_name, COUNT(*) as count');
        $this->db->where('state', 'Y');
        $this->db->where('category_name IS NOT NULL');
        $this->db->group_by('category_name');
        $this->db->order_by('count', 'DESC');
        return $this->db->get($this->table)->result();
    }

    /**
     * 최근 통계
     */
    public function get_recent_statistics()
    {
        // 최근 1년간 개원한 병원
        $this->db->where('state', 'Y');
        $this->db->where('establishment_date >=', date('Y-m-d', strtotime('-1 year')));
        $recent_count = $this->db->count_all_results($this->table);

        return array(
            'recent_openings' => $recent_count,
            'total_count' => $this->get_total_hospital_count()
        );
    }

    /**
     * 자동완성 제안
     */
    public function get_autocomplete_suggestions($term)
    {
        $suggestions = array();
        
        // 병원명 기반 제안
        $this->db->select('institution_name');
        $this->db->where('state', 'Y');
        $this->db->like('institution_name', $term);
        $this->db->distinct();
        $this->db->limit(5);
        $hospitals = $this->db->get($this->table)->result();
        
        foreach ($hospitals as $hospital) {
            $suggestions[] = array(
                'label' => $hospital->institution_name,
                'value' => $hospital->institution_name,
                'type' => 'hospital'
            );
        }

        // 지역명 기반 제안
        $regions = array('서울', '부산', '대구', '인천', '광주', '대전', '울산', '경기', '강원');
        foreach ($regions as $region) {
            if (strpos($region, $term) !== false) {
                $suggestions[] = array(
                    'label' => $region . ' 병원',
                    'value' => $region . ' 병원',
                    'type' => 'region'
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
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'search_date' => date('Y-m-d H:i:s')
        );

        // 검색 로그 테이블이 없으면 생성하지 않고 로그만 남김
        log_message('info', '검색 로그: ' . json_encode($data));
    }

    /**
     * 최근 검색어 조회
     */
    public function get_recent_searches($limit = 5)
    {
        // 실제 구현시에는 검색 로그 테이블에서 가져오지만, 현재는 더미 데이터
        return array(
            '서울 종합병원',
            '부산 치과',
            '삼성서울병원 위치',
            '강남 의원',
            '대구 한의원'
        );
    }

    /**
     * 지역별 병원 조회
     */
    public function get_hospitals_by_region($sido_code = null, $sigungu_code = null, $limit = 20, $offset = 0)
    {
        $this->db->where('state', 'Y');
        
        if ($sido_code) {
            $this->db->where('sido_code', $sido_code);
        }
        
        if ($sigungu_code) {
            $this->db->where('sigungu_code', $sigungu_code);
        }
        
        // 총 개수 조회
        $total_count = $this->db->count_all_results($this->table);
        
        // 실제 데이터 조회
        $this->db->where('state', 'Y');
        
        if ($sido_code) {
            $this->db->where('sido_code', $sido_code);
        }
        
        if ($sigungu_code) {
            $this->db->where('sigungu_code', $sigungu_code);
        }
        
        $this->db->limit($limit, $offset);
        $this->db->order_by('institution_name', 'ASC');
        $hospitals = $this->db->get($this->table)->result();

        return array(
            'hospitals' => $hospitals,
            'total_count' => $total_count,
            'region_name' => $sido_code ? $this->get_region_name($sido_code) : null
        );
    }

    /**
     * 지역 코드로 지역명 조회
     */
    private function get_region_name($sido_code)
    {
        $this->db->select('sido_name');
        $this->db->where('sido_code', $sido_code);
        $this->db->limit(1);
        $result = $this->db->get($this->table)->row();
        
        return $result ? $result->sido_name : null;
    }
} 