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
                case 'regional_hospital_search':
                    $result = $this->search_regional_hospital($analysis);
                    break;
                case 'emergency_regional_search':
                    $result = $this->search_emergency_regional($analysis);
                    break;
                case 'emergency_search':
                    $result = $this->search_emergency($analysis);
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
                
            case 'EMERGENCY':
                if ($analysis['region']) {
                    return $this->search_emergency_regional($analysis);
                } else {
                    return $this->search_emergency($analysis);
                }
                
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
        $emergency_keywords = array('응급실', '응급', '급한', '응급의료', '24시간', '밤', '야간', '응급센터');
        
        // 확장된 지역명 패턴 (시/도 + 구/군/시)
        $regions = array(
            // 서울특별시
            '서울' => '110000', '서울시' => '110000', '서울특별시' => '110000',
            '강남' => '11230', '강남구' => '11230',
            '강동' => '11250', '강동구' => '11250',
            '강북' => '11090', '강북구' => '11090',
            '강서' => '11220', '강서구' => '11220',
            '관악' => '11210', '관악구' => '11210',
            '광진' => '11050', '광진구' => '11050',
            '구로' => '11170', '구로구' => '11170',
            '금천' => '11200', '금천구' => '11200',
            '노원' => '11350', '노원구' => '11350',
            '도봉' => '11320', '도봉구' => '11320',
            '동대문' => '11230', '동대문구' => '11230',
            '동작' => '11200', '동작구' => '11200',
            '마포' => '11140', '마포구' => '11140',
            '서대문' => '11130', '서대문구' => '11130',
            '서초' => '11240', '서초구' => '11240',
            '성동' => '11040', '성동구' => '11040',
            '성북' => '11080', '성북구' => '11080',
            '송파' => '11240', '송파구' => '11240',
            '양천' => '11180', '양천구' => '11180',
            '영등포' => '11190', '영등포구' => '11190',
            '용산' => '11030', '용산구' => '11030',
            '은평' => '11120', '은평구' => '11120',
            '종로' => '11010', '종로구' => '11010',
            '중' => '11020', '중구' => '11020',
            '중랑' => '11260', '중랑구' => '11260',
            
            // 기타 광역시
            '부산' => '210000', '대구' => '230000', '인천' => '240000',
            '광주' => '250000', '대전' => '260000', '울산' => '270000', '세종' => '290000',
            '경기' => '310000', '강원' => '320000', '충북' => '330000', '충남' => '340000',
            '전북' => '350000', '전남' => '360000', '경북' => '370000', '경남' => '380000',
            '제주' => '390000'
        );
        
        // 병원 분류 패턴  
        $categories = array(
            '종합병원' => '29', '상급종합' => '11', '병원' => '28', '의원' => '31', '치과' => '32',
            '한의원' => '33', '보건소' => '91', '약국' => '92'
        );

        $analysis = array(
            'original_query' => $query,
            'type' => 'general',
            'hospital_name' => null,
            'region' => null,
            'detailed_region' => null,
            'category' => null,
            'info_type' => 'basic',
            'keywords' => array(),
            'search_intent' => 'general'
        );

        // 병원명 + 정보 요청 패턴 분석
        foreach ($location_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $analysis['info_type'] = 'location';
                $analysis['keywords'][] = $keyword;
                $analysis['search_intent'] = 'specific_info';
                break;
            }
        }

        foreach ($contact_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $analysis['info_type'] = 'contact';
                $analysis['keywords'][] = $keyword;
                $analysis['search_intent'] = 'specific_info';
                break;
            }
        }

        foreach ($homepage_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $analysis['info_type'] = 'homepage';
                $analysis['keywords'][] = $keyword;
                $analysis['search_intent'] = 'specific_info';
                break;
            }
        }

        // 응급실 검색 패턴 분석
        foreach ($emergency_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $analysis['info_type'] = 'emergency';
                $analysis['keywords'][] = $keyword;
                $analysis['search_intent'] = 'emergency_search';
                // 응급실 검색인 경우 종합병원으로 카테고리 설정
                if (!$analysis['category']) {
                    $analysis['category'] = array(
                        'name' => '종합병원',
                        'code' => '29'
                    );
                }
                break;
            }
        }

        // 지역 검색 패턴 (구체적인 지역 우선 매칭)
        $matched_regions = array();
        foreach ($regions as $region_name => $region_code) {
            if (strpos($query, $region_name) !== false) {
                $matched_regions[$region_name] = array(
                    'name' => $region_name,
                    'code' => $region_code,
                    'length' => mb_strlen($region_name, 'UTF-8')
                );
            }
        }
        
        // 가장 긴 매칭(구체적인 지역) 선택
        if (!empty($matched_regions)) {
            usort($matched_regions, function($a, $b) {
                return $b['length'] - $a['length'];
            });
            $analysis['region'] = $matched_regions[0];
            
            // 구/군 단위 지역인지 확인
            if (strlen($matched_regions[0]['code']) > 5) {
                $analysis['detailed_region'] = $matched_regions[0];
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

        // 병원명 추출 시도 (개선된 로직)
        $hospital_name = $this->extract_hospital_name_advanced($query, $analysis);
        if ($hospital_name) {
            $analysis['hospital_name'] = $hospital_name;
            
            // 지역과 병원명이 모두 있는 경우
            if ($analysis['region']) {
                $analysis['type'] = 'regional_hospital_search';
                $analysis['search_intent'] = 'specific_hospital_in_region';
            } else {
            $analysis['type'] = 'hospital_detail';
                $analysis['search_intent'] = 'specific_hospital';
            }
        }
        // 병원명이 없을 때만 지역/카테고리 검색
        else {
            // 응급실 검색 우선 처리
            if ($analysis['info_type'] === 'emergency') {
                if ($analysis['region']) {
                    $analysis['type'] = 'emergency_regional_search';
                    $analysis['search_intent'] = 'emergency_in_region';
                } else {
                    $analysis['type'] = 'emergency_search';
                    $analysis['search_intent'] = 'emergency_general';
                }
            }
            // 검색 타입 결정
            elseif ($analysis['region'] && $analysis['category']) {
                $analysis['type'] = 'mixed_search';
                $analysis['search_intent'] = 'category_in_region';
            } elseif ($analysis['region']) {
                $analysis['type'] = 'region_search';
                $analysis['search_intent'] = 'hospitals_in_region';
            } elseif ($analysis['category']) {
                $analysis['type'] = 'category_search';
                $analysis['search_intent'] = 'hospitals_by_category';
            }
        }

        return $analysis;
    }

    /**
     * 개선된 병원명 추출 로직
     */
    private function extract_hospital_name_advanced($query, $analysis)
    {
        // 병원 관련 키워드
        $hospital_keywords = array('병원', '의원', '보건소', '의료원', '센터', '클리닉');
        
        // 질문 키워드들
        $question_keywords = array(
            '위치', '주소', '어디', '찾아가는', '길', '어디에',
            '전화번호', '연락처', '번호', '전화', 'tel',
            '정보', '상세정보', '개요', '소개',
            '종류', '병원급수', '무슨', '분류',
            '개원', '개설일', '언제', '설립',
            '홈페이지', '웹사이트', '인터넷', 'url', '사이트'
        );
        
        $hospital_name = $query;
        
        // 지역명 제거 (분석된 지역이 있는 경우)
        if (isset($analysis['region'])) {
            $hospital_name = str_replace($analysis['region']['name'], '', $hospital_name);
        }
        
        // 질문 키워드 제거
        foreach ($question_keywords as $keyword) {
            $hospital_name = str_replace($keyword, '', $hospital_name);
        }
        
        // 병원 키워드가 포함된 경우만 처리
        $has_hospital_keyword = false;
        foreach ($hospital_keywords as $keyword) {
            if (strpos($query, $keyword) !== false) {
                $has_hospital_keyword = true;
                break;
            }
        }
        
        if (!$has_hospital_keyword) {
            return null;
        }
        
        // 병원명 정리 (공백 제거 및 정규화)
        $hospital_name = preg_replace('/\s+/', '', $hospital_name);
        $hospital_name = trim($hospital_name);
        
        // 최소 2글자 이상이어야 유효한 병원명
        if (mb_strlen($hospital_name, 'UTF-8') >= 2) {
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
     * 응급실 지역별 검색
     */
    private function search_emergency_regional($analysis)
    {
        $this->db->select('medical_institution.*, medical_institution_hospital.emergency_room');
        $this->db->from('medical_institution');
        $this->db->join('medical_institution_hospital', 'medical_institution.id = medical_institution_hospital.institution_id', 'inner');
        $this->db->where('medical_institution.state', 'Y');
        $this->db->where('medical_institution_hospital.emergency_room', 1);
        
        // 지역 조건 추가 (코드와 주소 모두 검색)
        if ($analysis['region']) {
            $region_name = $analysis['region']['name'];
            $region_code = $analysis['region']['code'];
            $this->db->group_start();
            $this->db->like('medical_institution.address', $region_name);
            if (strlen($region_code) > 5) {
                $this->db->or_like('medical_institution.sigungu_code', substr($region_code, 0, 5));
            } else {
                $this->db->or_like('medical_institution.sido_code', substr($region_code, 0, 3));
            }
            $this->db->group_end();
        }
        $this->db->where_in('medical_institution.category_code', array('29', '11'));
        $this->db->order_by('medical_institution.category_code', 'ASC');
        $this->db->limit(20);
        $hospitals = $this->db->get()->result();
        $region_name = $analysis['region']['name'] ?? '해당 지역';
        $message = "{$region_name} 응급실 운영 종합병원입니다.";
        return array(
            'hospitals' => $hospitals,
            'total_count' => count($hospitals),
            'search_type' => 'emergency_regional',
            'message' => $message,
            'region' => $analysis['region']
        );
    }

    /**
     * 응급실 일반 검색
     */
    private function search_emergency($analysis)
    {
        $this->db->select('medical_institution.*, medical_institution_hospital.emergency_room');
        $this->db->from('medical_institution');
        $this->db->join('medical_institution_hospital', 'medical_institution.id = medical_institution_hospital.institution_id', 'inner');
        $this->db->where('medical_institution.state', 'Y');
        $this->db->where('medical_institution_hospital.emergency_room', 1);
        $this->db->where_in('medical_institution.category_code', array('29', '11'));
        $this->db->order_by('medical_institution.category_code', 'ASC');
        $this->db->limit(20);
        $hospitals = $this->db->get()->result();
        return array(
            'hospitals' => $hospitals,
            'total_count' => count($hospitals),
            'search_type' => 'emergency',
            'message' => '응급실 운영 종합병원입니다.'
        );
    }

    /**
     * 지역별 검색
     */
    private function search_by_region($analysis)
    {
        $this->db->where('state', 'Y');
        $region_name = $analysis['region']['name'];
        $region_code = $analysis['region']['code'];
        $this->db->group_start();
        $this->db->like('address', $region_name);
        $this->db->or_like('sigungu_code', $region_code);
        $this->db->group_end();
        $this->db->limit(50);
        $this->db->order_by('institution_name', 'ASC');
        $hospitals = $this->db->get($this->table)->result();
        // 총 개수 조회
        $this->db->where('state', 'Y');
        $this->db->group_start();
        $this->db->like('address', $region_name);
        $this->db->or_like('sigungu_code', $region_code);
        $this->db->group_end();
        $total_count = $this->db->count_all_results($this->table);
        return array(
            'search_type' => 'region_search',
            'search_params' => $analysis,
            'hospitals' => $hospitals,
            'total_count' => $total_count,
            'message' => "'{$region_name}' 지역 의료기관 {$total_count}개를 찾았습니다."
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
        
        // 검색어를 공백으로 분리하여 각각 검색
        $keywords = explode(' ', $analysis['original_query']);
        foreach ($keywords as $keyword) {
            if (trim($keyword) !== '') {
                $this->db->group_start();
                $this->db->like('institution_name', $keyword);
                $this->db->or_like('address', $keyword);
                $this->db->or_like('category_name', $keyword);
                $this->db->group_end();
            }
        }
        
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

    /**
     * 지역별 병원 검색 (지역명 + 병원명)
     */
    private function search_regional_hospital($analysis)
    {
        $this->db->where('state', 'Y');
        $this->db->like('institution_name', $analysis['hospital_name']);
        
        // 구체적인 지역(구/군)이 있는 경우
        if (isset($analysis['detailed_region']) && $analysis['detailed_region']) {
            $this->db->like('address', $analysis['detailed_region']['name']);
        } 
        // 시/도 단위 지역 검색
        elseif (isset($analysis['region'])) {
            // 시/도 코드로 검색
            if (strlen($analysis['region']['code']) == 6) {
                $this->db->where('sido_code', $analysis['region']['code']);
            } else {
                // 주소에서 지역명 검색
                $this->db->like('address', $analysis['region']['name']);
            }
        }
        
        $this->db->limit(10);
        $this->db->order_by('institution_name', 'ASC');
        
        $hospitals = $this->db->get($this->table)->result();
        
        $region_text = $analysis['region']['name'];
        if (isset($analysis['detailed_region'])) {
            $region_text = $analysis['detailed_region']['name'];
        }
        
        $message = '';
        switch ($analysis['info_type']) {
            case 'location':
                $message = "'{$region_text} {$analysis['hospital_name']}' 위치 정보입니다.";
                break;
            case 'contact':
                $message = "'{$region_text} {$analysis['hospital_name']}' 연락처 정보입니다.";
                break;
            case 'homepage':
                $message = "'{$region_text} {$analysis['hospital_name']}' 홈페이지 정보입니다.";
                break;
            default:
                $message = "'{$region_text} {$analysis['hospital_name']}' 검색 결과입니다.";
        }

        return array(
            'search_type' => 'regional_hospital_search',
            'search_params' => $analysis,
            'hospitals' => $hospitals,
            'total_count' => count($hospitals),
            'message' => $message
        );
    }

    /**
     * 질문 분석만 수행 (검색 실행 없이)
     */
    public function analyze_query_only($query)
    {
        // 1. 질문 유형 감지
        $question_type = $this->Question_type_model->detect_question_type($query);
        
        // 2. 기본 검색어 분석
        $analysis = $this->analyze_query($query);
        
        // 3. 분석 결과 종합
        $result = array(
            'original_query' => $query,
            'cleaned_query' => trim(strtolower($query)),
            'analysis' => $analysis,
            'question_type' => $question_type,
            'interpretation' => $this->generate_interpretation($analysis, $question_type),
            'search_strategy' => $this->get_search_strategy($analysis),
            'confidence_score' => $this->calculate_confidence_score($analysis, $question_type),
            'suggestions' => $this->generate_search_suggestions($analysis, $query),
            'related_queries' => $this->get_related_queries($analysis),
            'quick_actions' => $this->generate_quick_actions($analysis)
        );
        
        return $result;
    }

    /**
     * 분석 결과 해석 생성
     */
    private function generate_interpretation($analysis, $question_type)
    {
        $interpretation = array();
        
        // 검색 의도 해석
        switch ($analysis['search_intent']) {
            case 'specific_hospital_in_region':
                $interpretation['intent'] = '특정 지역의 특정 병원을 찾고 있습니다';
                break;
            case 'specific_hospital':
                $interpretation['intent'] = '특정 병원을 찾고 있습니다';
                break;
            case 'hospitals_in_region':
                $interpretation['intent'] = '특정 지역의 병원들을 찾고 있습니다';
                break;
            case 'category_in_region':
                $interpretation['intent'] = '특정 지역의 특정 종류 병원을 찾고 있습니다';
                break;
            case 'hospitals_by_category':
                $interpretation['intent'] = '특정 종류의 병원을 찾고 있습니다';
                break;
            case 'emergency_search':
                $interpretation['intent'] = '🚨 응급실 운영 병원을 찾고 있습니다';
                break;
            case 'emergency_in_region':
                $interpretation['intent'] = '🚨 특정 지역의 응급실 운영 병원을 찾고 있습니다';
                break;
            case 'specific_info':
                $interpretation['intent'] = '특정 정보를 요청하고 있습니다';
                break;
            default:
                $interpretation['intent'] = '일반적인 검색을 하고 있습니다';
        }
        
        // 추출된 요소들
        $elements = array();
        if ($analysis['hospital_name']) {
            $elements[] = "병원명: '{$analysis['hospital_name']}'";
        }
        if ($analysis['region']) {
            $elements[] = "지역: '{$analysis['region']['name']}'";
        }
        if ($analysis['detailed_region']) {
            $elements[] = "상세지역: '{$analysis['detailed_region']['name']}'";
        }
        if ($analysis['category']) {
            $elements[] = "분류: '{$analysis['category']['name']}'";
        }
        if ($analysis['info_type'] !== 'basic') {
            $info_types = array(
                'location' => '위치/주소 정보',
                'contact' => '연락처 정보',
                'homepage' => '홈페이지 정보',
                'emergency' => '🚨 응급실 정보'
            );
            $elements[] = "요청정보: " . ($info_types[$analysis['info_type']] ?? $analysis['info_type']);
        }
        
        $interpretation['extracted_elements'] = $elements;
        
        // 질문 유형 정보
        if ($question_type && $question_type['match_score'] > 10) {
            $interpretation['question_category'] = $question_type['type_name'];
            $interpretation['question_confidence'] = $question_type['match_score'];
        }
        
        return $interpretation;
    }

    /**
     * 검색 전략 설명
     */
    private function get_search_strategy($analysis)
    {
        $strategy = array();
        
        switch ($analysis['type']) {
            case 'regional_hospital_search':
                $strategy['method'] = '지역별 병원 검색';
                $strategy['description'] = '병원명과 지역 정보를 조합하여 정확한 매칭을 시도합니다';
                break;
            case 'emergency_regional_search':
                $strategy['method'] = '🚨 지역별 응급실 검색';
                $strategy['description'] = '해당 지역의 응급실 운영 종합병원 및 상급종합병원을 우선 검색합니다';
                break;
            case 'emergency_search':
                $strategy['method'] = '🚨 응급실 검색';
                $strategy['description'] = '응급실 운영이 가능한 종합병원 및 상급종합병원을 우선 검색합니다';
                break;
            case 'hospital_detail':
                $strategy['method'] = '병원명 검색';
                $strategy['description'] = '병원명을 기준으로 유사한 이름의 병원을 찾습니다';
                break;
            case 'region_search':
                $strategy['method'] = '지역별 검색';
                $strategy['description'] = '지정된 지역의 모든 의료기관을 검색합니다';
                break;
            case 'category_search':
                $strategy['method'] = '분류별 검색';
                $strategy['description'] = '특정 종류의 의료기관을 검색합니다';
                break;
            case 'mixed_search':
                $strategy['method'] = '복합 검색';
                $strategy['description'] = '지역과 분류를 모두 고려하여 검색합니다';
                break;
            default:
                $strategy['method'] = '통합 검색';
                $strategy['description'] = '병원명, 주소, 분류를 모두 고려한 광범위한 검색을 수행합니다';
        }
        
        return $strategy;
    }

    /**
     * 신뢰도 점수 계산
     */
    private function calculate_confidence_score($analysis, $question_type)
    {
        $score = 0;
        
        // 병원명 추출 성공 시 점수 증가
        if ($analysis['hospital_name']) {
            $score += 30;
        }
        
        // 지역 정보 추출 성공 시 점수 증가
        if ($analysis['region']) {
            $score += 20;
        }
        
        // 상세 지역 정보가 있으면 추가 점수
        if ($analysis['detailed_region']) {
            $score += 10;
        }
        
        // 카테고리 정보 추출 성공 시 점수 증가
        if ($analysis['category']) {
            $score += 15;
        }
        
        // 정보 요청 유형 인식 시 점수 증가
        if ($analysis['info_type'] !== 'basic') {
            $score += 10;
        }
        
        // 질문 유형 인식 성공 시 점수 증가
        if ($question_type && $question_type['match_score'] > 10) {
            $score += min($question_type['match_score'], 25);
        }
        
        // 최대 100점으로 제한
        return min($score, 100);
    }

    /**
     * 검색 개선 제안 생성
     */
    private function generate_search_suggestions($analysis, $original_query)
    {
        $suggestions = array();
        
        // 1. 더 구체적인 검색어 제안
        if ($analysis['hospital_name'] && !$analysis['region']) {
            $suggestions[] = array(
                'type' => 'add_location',
                'title' => '지역을 추가해보세요',
                'description' => '더 정확한 결과를 위해 지역명을 추가하는 것이 좋습니다',
                'suggested_query' => "서울 {$analysis['hospital_name']}",
                'icon' => 'fas fa-map-marker-alt'
            );
        }
        
        // 2. 정보 요청 방식 개선 제안
        if ($analysis['hospital_name'] && $analysis['info_type'] === 'basic') {
            $info_suggestions = array(
                array('query' => "{$analysis['hospital_name']} 주소", 'label' => '주소'),
                array('query' => "{$analysis['hospital_name']} 전화번호", 'label' => '전화번호'),
                array('query' => "{$analysis['hospital_name']} 진료과목", 'label' => '진료과목')
            );
            
            $suggestions[] = array(
                'type' => 'specific_info',
                'title' => '구체적인 정보를 요청해보세요',
                'description' => '원하는 정보를 명시하면 더 정확한 답변을 받을 수 있습니다',
                'options' => $info_suggestions,
                'icon' => 'fas fa-info-circle'
            );
        }
        
        // 3. 검색 범위 확장 제안
        if ($analysis['confidence_score'] < 50) {
            $suggestions[] = array(
                'type' => 'broaden_search',
                'title' => '검색 범위를 넓혀보세요',
                'description' => '비슷한 키워드나 다른 표현을 시도해보세요',
                'suggested_query' => $this->generate_broader_query($analysis),
                'icon' => 'fas fa-expand-arrows-alt'
            );
        }
        
        // 4. 철자 수정 제안
        $spell_check = $this->check_spelling($original_query);
        if ($spell_check['has_suggestions']) {
            $suggestions[] = array(
                'type' => 'spelling',
                'title' => '철자를 확인해보세요',
                'description' => '다음과 같이 입력하신 건 아닌가요?',
                'suggested_query' => $spell_check['suggestion'],
                'icon' => 'fas fa-spell-check'
            );
        }
        
        return $suggestions;
    }

    /**
     * 관련 검색어 제안
     */
    private function get_related_queries($analysis)
    {
        $related = array();
        
        if ($analysis['hospital_name']) {
            // 같은 병원의 다른 정보
            $base_name = $analysis['hospital_name'];
            $related[] = "{$base_name} 진료시간";
            $related[] = "{$base_name} 주차장";
            $related[] = "{$base_name} 예약";
        }
        
        if ($analysis['region']) {
            // 같은 지역의 다른 병원들
            $region_name = $analysis['region']['name'];
            $related[] = "{$region_name} 응급실";
            $related[] = "{$region_name} 24시간 병원";
            $related[] = "{$region_name} 종합병원";
        }
        
        if ($analysis['category']) {
            // 같은 분류의 다른 정보
            $category = $analysis['category']['name'];
            $related[] = "가까운 {$category}";
            $related[] = "평점 좋은 {$category}";
        }
        
        return array_slice($related, 0, 5); // 최대 5개
    }

    /**
     * 빠른 액션 버튼 생성
     */
    private function generate_quick_actions($analysis)
    {
        $actions = array();
        
        // 1. 지도에서 보기
        if ($analysis['hospital_name'] || $analysis['region']) {
            $actions[] = array(
                'type' => 'map_search',
                'label' => '지도에서 보기',
                'icon' => 'fas fa-map',
                'action' => 'openMap'
            );
        }
        
        // 2. 비슷한 병원 찾기
        if ($analysis['category']) {
            $actions[] = array(
                'type' => 'similar_search',
                'label' => '비슷한 병원 찾기',
                'icon' => 'fas fa-search-plus',
                'action' => 'findSimilar'
            );
        }
        
        // 3. 즐겨찾기 추가
        $actions[] = array(
            'type' => 'bookmark',
            'label' => '검색어 저장',
            'icon' => 'fas fa-bookmark',
            'action' => 'bookmarkQuery'
        );
        
        // 4. 검색 기록 보기
        $actions[] = array(
            'type' => 'history',
            'label' => '최근 검색',
            'icon' => 'fas fa-history',
            'action' => 'showHistory'
        );
        
        return $actions;
    }

    /**
     * 더 넓은 범위의 검색어 생성
     */
    private function generate_broader_query($analysis)
    {
        if ($analysis['hospital_name']) {
            // 병원명에서 핵심 키워드만 추출
            $core_name = preg_replace('/병원|의원|센터|클리닉/', '', $analysis['hospital_name']);
            return trim($core_name);
        }
        
        if ($analysis['region']) {
            return $analysis['region']['name'] . " 병원";
        }
        
        return "병원 검색";
    }

    /**
     * 기본적인 철자 검사
     */
    private function check_spelling($query)
    {
        // 자주 틀리는 병원명들
        $common_typos = array(
            '삼성병원' => array('삼송병원', '삼섬병원'),
            '세브란스' => array('세브란스', '세브란츠'),
            '서울대병원' => array('서울데병원', '서울대학교병원'),
            '연세대' => array('연새대', '연세데'),
            '고려대' => array('고려데', '고려대학교')
        );
        
        foreach ($common_typos as $correct => $typos) {
            foreach ($typos as $typo) {
                if (strpos($query, $typo) !== false) {
                    return array(
                        'has_suggestions' => true,
                        'suggestion' => str_replace($typo, $correct, $query)
                    );
                }
            }
        }
        
        return array('has_suggestions' => false);
    }

    /**
     * 내 위치 기준 근처 병원 검색 (반경 km)
     */
    public function search_nearby($lat, $lng, $radius_km = 3)
    {
        $sql = "SELECT mi.*, mif.location_x, mif.location_y, (
            6371 * acos(
                cos(radians(?)) * cos(radians(mif.location_y)) *
                cos(radians(mif.location_x) - radians(?)) +
                sin(radians(?)) * sin(radians(mif.location_y))
            )
        ) AS distance
        FROM medical_institution mi
        INNER JOIN medical_institution_facility mif ON mi.id = mif.institution_id
        WHERE mi.state = 'Y'
        HAVING distance < ?
        ORDER BY distance ASC
        LIMIT 30";
        $query = $this->db->query($sql, array($lat, $lng, $lat, $radius_km));
        $hospitals = $query->result();
        return array(
            'hospitals' => $hospitals,
            'total_count' => count($hospitals),
            'message' => "내 위치 기준 반경 {$radius_km}km 이내 병원 {$query->num_rows()}개를 찾았습니다."
        );
    }
} 
