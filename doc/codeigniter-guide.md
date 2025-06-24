# Q&A 검색 시스템 CodeIgniter 개발 가이드

## 🚀 CodeIgniter 3.x 기반 Q&A 시스템 개발

이 프로젝트는 **의료/의약품 전문 자연어 처리 기반 Q&A 검색 시스템**을 **CodeIgniter 3.x** 프레임워크로 개발합니다.

> **개발 목표**: 의료 데이터 기반 규칙에서 시작하여 AI 기반으로 확장하는 의료 전문 질의응답 플랫폼  
> **데이터 기반**: 의약품, 의료기관, 제품 정보 등 21개 테이블 활용

## 📁 디렉토리 구조

### 핵심 디렉토리
```
source/
├── application/              # 메인 애플리케이션 코드
│   ├── controllers/         # 컨트롤러 (MVC의 C)
│   ├── models/              # 모델 (MVC의 M)
│   ├── views/               # 뷰 (MVC의 V)
│   ├── config/              # 설정 파일들
│   │   ├── config.php      # 기본 설정
│   │   ├── database.php    # DB 연결 설정
│   │   ├── routes.php      # URL 라우팅
│   │   └── autoload.php    # 자동 로드 설정
│   ├── libraries/           # 사용자 정의 라이브러리
│   ├── helpers/             # 헬퍼 함수들
│   ├── hooks/               # 훅 함수들
│   └── cache/               # 캐시 파일들
├── system/                   # CodeIgniter 시스템 파일 (수정 금지)
├── assets/                   # 정적 파일들
│   ├── css/                 # 스타일시트
│   ├── js/                  # JavaScript
│   └── images/              # 이미지 파일
├── index.php                 # 메인 진입점
└── composer.json             # Composer 설정
```

## 🔧 개발 환경 설정

### 1. 데이터베이스 설정
`application/config/database.php` 파일 수정:

```php
$db['default'] = array(
    'dsn'      => '',
    'hostname' => 'db',              // Docker 컨테이너명
    'username' => 'dev',
    'password' => 'dev2000',
    'database' => 'dev',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);
```

### 2. 기본 URL 설정
`application/config/config.php` 파일에서:

```php
$config['base_url'] = 'http://localhost/';
```

### 3. 자동 로드 설정
`application/config/autoload.php` 파일에서:

```php
$autoload['libraries'] = array('database', 'session');
$autoload['helper'] = array('url', 'form');
```

## 🎯 개발 워크플로우

### 1. Q&A 컨트롤러 생성
`application/controllers/` 폴더에 Q&A 시스템용 컨트롤러 생성:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Qa extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Qa_model');
        $this->load->helper('text');
    }

    // Q&A 검색 페이지
    public function index()
    {
        $this->load->view('qa/search');
    }
    
    // 질문 검색 처리 (AJAX)
    public function search()
    {
        $query = $this->input->post('query');
        
        // Phase 1: 규칙 기반 검색
        $results = $this->Qa_model->search_qa($query);
        
        // 검색 로그 저장
        $this->Qa_model->log_search($query, $results);
        
        echo json_encode($results);
    }
    
    // Q&A 관리 (관리자용)
    public function admin()
    {
        $data['qa_list'] = $this->Qa_model->get_all_qa();
        $this->load->view('qa/admin', $data);
    }
}
```

### 2. Q&A 모델 생성
`application/models/` 폴더에 Q&A 시스템용 모델 생성:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Qa_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    // Phase 1: 규칙 기반 Q&A 검색
    public function search_qa($query)
    {
        // 1. 키워드 추출
        $keywords = $this->extract_keywords($query);
        
        // 2. 유사도 기반 검색
        $this->db->select('*');
        $this->db->from('qa_pairs');
        
        // 키워드 매칭
        foreach ($keywords as $keyword) {
            $this->db->or_like('question', $keyword);
            $this->db->or_like('keywords', $keyword);
        }
        
        $this->db->limit(10);
        $results = $this->db->get()->result();
        
        // 3. 유사도 점수 계산
        foreach ($results as &$result) {
            $result->similarity = $this->calculate_similarity($query, $result->question);
        }
        
        // 유사도 순으로 정렬
        usort($results, function($a, $b) {
            return $b->similarity <=> $a->similarity;
        });
        
        return $results;
    }
    
    // 키워드 추출 (Phase 1: 단순 분리)
    private function extract_keywords($text)
    {
        // 불용어 제거 및 키워드 추출
        $stopwords = ['은', '는', '이', '가', '을', '를', '의', '에', '와', '과'];
        $words = explode(' ', $text);
        
        return array_filter($words, function($word) use ($stopwords) {
            return !in_array($word, $stopwords) && strlen($word) > 1;
        });
    }
    
    // 텍스트 유사도 계산 (코사인 유사도 단순화)
    private function calculate_similarity($text1, $text2)
    {
        $words1 = $this->extract_keywords($text1);
        $words2 = $this->extract_keywords($text2);
        
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));
        
        return $union > 0 ? $intersection / $union : 0;
    }
    
    // 검색 로그 저장
    public function log_search($query, $results)
    {
        $data = [
            'user_query' => $query,
            'matched_qa_id' => !empty($results) ? $results[0]->id : null,
            'similarity_score' => !empty($results) ? $results[0]->similarity : 0,
            'response_time_ms' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000)
        ];
        
        $this->db->insert('search_logs', $data);
    }
    
    // Q&A 관리 기능
    public function get_all_qa()
    {
        return $this->db->get('qa_pairs')->result();
    }
    
    public function insert_qa($data)
    {
        return $this->db->insert('qa_pairs', $data);
    }
}
```

### 3. 의료 데이터 모델 생성
`application/models/` 폴더에 의료 데이터 전용 모델 생성:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Drug_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    // 의약품 검색 (EDI 코드 기반)
    public function search_by_edi($edi_code)
    {
        $this->db->where('edi_code', $edi_code);
        return $this->db->get('drug')->row();
    }
    
    // 의약품명으로 검색
    public function search_by_name($name)
    {
        $this->db->like('name', $name);
        return $this->db->get('drug')->result();
    }
    
    // 활성 성분으로 유사 의약품 검색
    public function find_similar_by_ingredient($ingredient_code)
    {
        $this->db->where('active_ingredient_code', $ingredient_code);
        return $this->db->get('drug')->result();
    }
    
    // 제조사별 의약품 조회
    public function get_by_manufacturer($manufacturer_name)
    {
        $this->db->where('manufacturer_name', $manufacturer_name);
        return $this->db->get('drug')->result();
    }
}

class Medical_institution_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    // 지역별 의료기관 검색
    public function search_by_region($province_cd, $city_district_cd = null)
    {
        $this->db->where('province_cd', $province_cd);
        if ($city_district_cd) {
            $this->db->where('city_district_cd', $city_district_cd);
        }
        return $this->db->get('medical_institution')->result();
    }
    
    // 의료기관명으로 검색
    public function search_by_name($name)
    {
        $this->db->like('name', $name);
        return $this->db->get('medical_institution')->result();
    }
    
    // GPS 좌표 기반 주변 의료기관 검색
    public function find_nearby($lat, $lng, $radius_km = 5)
    {
        $sql = "SELECT *, 
                (6371 * acos(cos(radians(?)) * cos(radians(location_lat)) * 
                cos(radians(location_lng) - radians(?)) + 
                sin(radians(?)) * sin(radians(location_lat)))) AS distance 
                FROM medical_institution 
                HAVING distance < ? 
                ORDER BY distance";
        
        return $this->db->query($sql, [$lat, $lng, $lat, $radius_km])->result();
    }
}

class Product_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    // 제품 검색
    public function search_products($keyword)
    {
        $this->db->like('name', $keyword);
        $this->db->or_like('description', $keyword);
        return $this->db->get('product')->result();
    }
    
    // EDI 코드로 제품 조회
    public function get_by_edi($edi_code)
    {
        $this->db->where('edi_code', $edi_code);
        return $this->db->get('product')->row();
    }
}
```

### 4. Q&A 검색 뷰 생성
`application/views/qa/` 폴더에 Q&A 시스템용 뷰 생성:

**검색 페이지** (`application/views/qa/search.php`):
```html
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Q&A 검색 시스템</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .search-container { max-width: 800px; margin: 50px auto; }
        .search-box { width: 100%; padding: 15px; font-size: 16px; }
        .results { margin-top: 20px; }
        .qa-item { border: 1px solid #ddd; margin: 10px 0; padding: 15px; }
        .question { font-weight: bold; color: #333; }
        .answer { margin-top: 10px; color: #666; }
        .similarity { float: right; color: #999; }
    </style>
</head>
<body>
    <div class="search-container">
        <h1>🤖 Q&A 검색 시스템</h1>
        <p>자연어로 질문해보세요!</p>
        
        <form id="searchForm">
            <input type="text" id="queryInput" class="search-box" 
                   placeholder="예: 회원가입은 어떻게 하나요?" required>
            <button type="submit">검색</button>
        </form>
        
        <div id="results" class="results"></div>
    </div>

    <script>
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        
        var query = $('#queryInput').val();
        
        $.ajax({
            url: '<?= base_url('qa/search') ?>',
            method: 'POST',
            data: { query: query },
            dataType: 'json',
            success: function(results) {
                displayResults(results);
            }
        });
    });
    
    function displayResults(results) {
        var html = '';
        
        if (results.length > 0) {
            results.forEach(function(item) {
                html += '<div class="qa-item">';
                html += '<div class="similarity">유사도: ' + 
                        (item.similarity * 100).toFixed(1) + '%</div>';
                html += '<div class="question">Q: ' + item.question + '</div>';
                html += '<div class="answer">A: ' + item.answer + '</div>';
                html += '</div>';
            });
        } else {
            html = '<p>검색 결과가 없습니다.</p>';
        }
        
        $('#results').html(html);
    }
    </script>
</body>
</html>
```

## 🌐 URL 라우팅

### 기본 URL 구조
```
http://localhost/컨트롤러/메소드/매개변수1/매개변수2
```

예시:
- `http://localhost/welcome/index` → Welcome 컨트롤러의 index 메소드
- `http://localhost/welcome/hello/John` → Welcome 컨트롤러의 hello 메소드에 'John' 전달

### 커스텀 라우팅
`application/config/routes.php`에서 설정:

```php
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// 커스텀 라우트
$route['about'] = 'pages/about';
$route['user/(:num)'] = 'users/profile/$1';
```

## 📊 데이터베이스 사용법

### Active Record 패턴
```php
// 데이터 조회
$this->db->select('*');
$this->db->from('users');
$this->db->where('id', $id);
$query = $this->db->get();

// 또는 간단하게
$query = $this->db->get_where('users', array('id' => $id));

// 데이터 삽입
$data = array(
    'name' => 'John Doe',
    'email' => 'john@example.com'
);
$this->db->insert('users', $data);

// 데이터 업데이트
$this->db->where('id', $id);
$this->db->update('users', $data);

// 데이터 삭제
$this->db->where('id', $id);
$this->db->delete('users');
```

## 🔍 디버깅

### 환경 설정
현재 **development** 모드로 설정되어 있어 오류가 화면에 표시됩니다.

### 로그 확인
- 에러 로그: `application/logs/` 폴더
- CodeIgniter 로그 레벨 설정: `application/config/config.php`

```php
$config['log_threshold'] = 1; // 0=Disabled, 1=Error, 2=Debug, 3=Info, 4=All
```

## 📚 유용한 헬퍼와 라이브러리

### 자주 사용하는 헬퍼
```php
// URL 헬퍼
$this->load->helper('url');
echo base_url('assets/css/style.css');

// Form 헬퍼
$this->load->helper('form');
echo form_open('user/submit');

// Date 헬퍼
$this->load->helper('date');
echo unix_to_human(time());
```

### 자주 사용하는 라이브러리
```php
// Session 라이브러리
$this->load->library('session');
$this->session->set_userdata('key', 'value');

// Form Validation 라이브러리
$this->load->library('form_validation');
$this->form_validation->set_rules('email', 'Email', 'required|valid_email');

// Upload 라이브러리
$this->load->library('upload');
```

## 🚨 개발 시 주의사항

1. **시스템 폴더 수정 금지**: `system/` 폴더는 절대 수정하지 마세요
2. **보안**: 사용자 입력은 항상 검증하고 이스케이프 처리
3. **캐싱**: 프로덕션에서는 캐싱 활용 고려
4. **버전 관리**: `.gitignore`에 `application/logs/`와 `application/cache/` 추가

## 📖 참고 자료

- [CodeIgniter 공식 문서](https://codeigniter.com/userguide3/)
- [CodeIgniter 한글 가이드](https://codeigniter-kr.org/)
- [GitHub 저장소](https://github.com/bcit-ci/CodeIgniter)

## 🚀 다음 단계: AI 기반으로 확장

### Phase 2 계획 (하이브리드)
- **Python 연동**: REST API로 머신러닝 모델 호출
- **의도 분석**: 질문의 의도 자동 파악
- **개체명 인식**: 중요 키워드 자동 추출
- **학습 기능**: 사용자 피드백 기반 개선

### Phase 3 계획 (AI 기반)
- **딥러닝 모델**: Transformer 기반 언어 모델 통합
- **문맥 이해**: 대화 흐름 및 문맥 파악
- **동적 답변 생성**: 기존 지식 기반 새로운 답변 생성

---
**🎯 목표: 규칙 기반에서 시작하여 최첨단 AI Q&A 시스템으로 발전! 🚀** 