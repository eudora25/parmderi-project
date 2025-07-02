# 질문 시스템 분석 및 개선 방안

## 📊 현재 테이블 구조 분석

### 1. question_categories (질문 카테고리)
```sql
+---------------+-------------+------+-----+---------------------+-------------------------------+
| Field         | Type        | Null | Key | Default             | Extra                         |
+---------------+-------------+------+-----+---------------------+-------------------------------+
| id            | int(11)     | NO   | PRI | NULL                | auto_increment                |
| category_code | varchar(20) | NO   | UNI | NULL                |                               |
| category_name | varchar(50) | NO   |     | NULL                |                               |
| description   | text        | YES  |     | NULL                |                               |
| is_active     | tinyint(1)  | YES  |     | 1                   |                               |
| created_at    | timestamp   | YES  |     | current_timestamp() |                               |
| updated_at    | timestamp   | YES  |     | current_timestamp() | on update current_timestamp() |
+---------------+-------------+------+-----+---------------------+-------------------------------+
```

**현재 데이터:** 5개 카테고리
- HOSPITAL (병원)
- PHARMACY (약국) 
- GENERAL (일반)
- EMERGENCY (응급의료)
- LOCATION (지역검색)

### 2. question_types (질문 유형)
```sql
+------------------+--------------+------+-----+---------------------+-------------------------------+
| Field            | Type         | Null | Key | Default             | Extra                         |
+------------------+--------------+------+-----+---------------------+-------------------------------+
| id               | int(11)      | NO   | PRI | NULL                | auto_increment                |
| category_id      | int(11)      | NO   | MUL | NULL                |                               |
| type_code        | varchar(30)  | NO   |     | NULL                |                               |
| type_name        | varchar(100) | NO   |     | NULL                |                               |
| description      | text         | YES  |     | NULL                |                               |
| keywords         | longtext     | YES  |     | NULL                |                               |
| sample_questions | longtext     | YES  |     | NULL                |                               |
| answer_template  | text         | YES  |     | NULL                |                               |
| db_fields        | longtext     | YES  |     | NULL                |                               |
| priority         | int(11)      | YES  | MUL | 0                   |                               |
| is_active        | tinyint(1)   | YES  |     | 1                   |                               |
| created_at       | timestamp    | YES  |     | current_timestamp() |                               |
| updated_at       | timestamp    | YES  |     | current_timestamp() | on update current_timestamp() |
+------------------+--------------+------+-----+---------------------+-------------------------------+
```

**현재 데이터:** 16개 질문 유형 (우선순위 순)
1. BASIC_INFO (기본 정보) - 100
2. EMERGENCY (응급실) - 95
3. LOCATION (위치/주소) - 95
4. CONTACT (연락처) - 90
5. HOMEPAGE (홈페이지) - 85
6. BOOKING (예약) - 85
7. SPECIALTY (진료과/종별) - 80
8. HOURS (운영시간) - 75
9. MEDICAL_STAFF (의료진 정보) - 75
10. HISTORY (개설일/연혁) - 70
11. PARKING (주차장) - 70
12. REGIONAL_SEARCH (지역별 검색) - 65
13. CATEGORY_SEARCH (종별 검색) - 60
14. COMPLEX_SEARCH (복합 검색) - 55
15. COORDINATES (좌표/거리) - 50
16. OTHER (기타) - 10

### 3. question_logs (질문 로그) - 개선됨!
```sql
+----------------------+--------------+------+-----+---------------------+----------------+
| Field                | Type         | Null | Key | Default             | Extra          |
+----------------------+--------------+------+-----+---------------------+----------------+
| id                   | int(11)      | NO   | PRI | NULL                | auto_increment |
| user_question        | text         | NO   |     | NULL                |                |
| detected_type_id     | int(11)      | YES  | MUL | NULL                |                |
| hospital_name        | varchar(200) | YES  | MUL | NULL                |                |
| search_results_count | int(11)      | YES  |     | 0                   |                |
| response_time_ms     | int(11)      | YES  |     | 0                   |                |
| ip_address           | varchar(45)  | YES  |     | NULL                |                |
| user_agent           | text         | YES  |     | NULL                |                |
| created_at           | timestamp    | YES  | MUL | current_timestamp() |                |
| confidence_score     | int(11)      | YES  |     | 0                   |   ✅ 새로 추가  |
| search_intent        | varchar(100) | YES  |     | NULL                |   ✅ 새로 추가  |
| extracted_elements   | longtext     | YES  |     | NULL                |   ✅ 새로 추가  |
| session_id           | varchar(100) | YES  |     | NULL                |   ✅ 새로 추가  |
+----------------------+--------------+------+-----+---------------------+----------------+
```

## 🚀 개선된 활용 방안

### 1. 질문 유형별 활용법

#### **🏥 병원 기본 정보 (BASIC_INFO)**
```php
// keywords: ["정보", "소개", "개요", "기본", "어떤", "무엇"]
// 예시 질문: "삼성서울병원 정보", "아산병원 소개"
$this->Question_type_model->detect_question_type("삼성병원 정보");
```

#### **📍 위치/주소 정보 (LOCATION)**  
```php
// keywords: ["위치", "주소", "어디", "찾아가는길", "길", "어디에", "어디있", "소재지"]
// 예시 질문: "삼성서울병원 위치", "아산병원 주소"
$this->Question_type_model->detect_question_type("삼성병원 주소");
```

#### **🚨 응급실 정보 (EMERGENCY)**
```php
// keywords: ["응급실", "응급", "급한", "응급의료", "24시간", "밤"]
// 예시 질문: "근처 응급실", "24시간 응급실"
$this->Question_type_model->detect_question_type("강남구 응급실");
```

#### **🗺️ 지역별 검색 (REGIONAL_SEARCH)**
```php
// keywords: ["강남", "강북", "서초", "마포", "근처", "주변", "가까운"]
// 예시 질문: "강남구 병원", "강북구 종합병원"
$this->Question_type_model->detect_question_type("강북 삼성병원");
```

### 2. 로그 데이터 활용

#### **신뢰도 점수 기반 분석**
```sql
-- 신뢰도가 낮은 질문 패턴 분석
SELECT user_question, confidence_score, search_intent 
FROM question_logs 
WHERE confidence_score < 50 
ORDER BY created_at DESC;
```

#### **인기 질문 유형 분석**
```sql
-- 가장 많이 검색되는 질문 유형
SELECT qt.type_name, COUNT(*) as count
FROM question_logs ql
JOIN question_types qt ON ql.detected_type_id = qt.id
GROUP BY qt.type_name
ORDER BY count DESC;
```

#### **세션별 사용자 행동 분석**
```sql
-- 세션별 질문 패턴 분석
SELECT session_id, COUNT(*) as question_count, 
       AVG(confidence_score) as avg_confidence
FROM question_logs 
WHERE session_id IS NOT NULL
GROUP BY session_id
ORDER BY question_count DESC;
```

### 3. 실시간 분석 뷰 활용

```sql
-- 일별 질문 분석 통계
SELECT * FROM question_analytics 
WHERE date = CURDATE()
ORDER BY question_count DESC;
```

## 🔧 개선된 기능들

### 1. **향상된 질문 분석**
- ✅ 16개 세부 질문 유형으로 확장
- ✅ 우선순위 기반 매칭 시스템
- ✅ 키워드 기반 정확도 향상

### 2. **로그 시스템 강화**
- ✅ `confidence_score`: 분석 신뢰도 점수
- ✅ `search_intent`: 검색 의도 저장
- ✅ `extracted_elements`: 추출된 요소들
- ✅ `session_id`: 사용자 세션 추적

### 3. **성능 최적화**
- ✅ 인덱스 추가로 검색 성능 향상
- ✅ 분석 뷰를 통한 빠른 통계 조회

### 4. **확장성 확보**
- ✅ 응급의료, 지역검색 카테고리 추가
- ✅ 약국 시스템 확장을 위한 기반 마련

## 📈 권장 활용 시나리오

### 1. **실시간 사용자 지원**
```php
// 사용자 질문 입력 시
$analysis = $this->Hospital_search_model->analyze_query_only($query);

// 신뢰도가 낮으면 추가 제안 제공
if ($analysis['confidence_score'] < 60) {
    // 개선 제안 표시
    $suggestions = $analysis['suggestions'];
}
```

### 2. **관리자 대시보드**
```php
// 일일 통계 확인
$daily_stats = $this->db->query("
    SELECT * FROM question_analytics 
    WHERE date = CURDATE()
")->result_array();

// 문제가 있는 질문 패턴 확인
$low_confidence = $this->db->query("
    SELECT user_question, confidence_score 
    FROM question_logs 
    WHERE confidence_score < 30 
    AND created_at >= CURDATE()
")->result_array();
```

### 3. **시스템 개선**
```php
// 새로운 질문 유형 발견 시 추가
$this->db->insert('question_types', [
    'category_id' => 1,
    'type_code' => 'NEW_TYPE',
    'type_name' => '새로운 유형',
    'keywords' => json_encode(['키워드1', '키워드2']),
    'priority' => 65
]);
```

## 🎯 향후 개선 방향

### 1. **AI/ML 통합**
- 사용자 질문 패턴 학습을 통한 자동 분류 개선
- 자연어 처리(NLP) 도입

### 2. **개인화**
- 사용자별 선호 질문 유형 분석
- 맞춤형 검색 결과 제공

### 3. **확장**
- 약국 검색 시스템 통합
- 의료진 정보 시스템 연동

이제 질문 시스템이 단순한 키워드 매칭을 넘어서 **지능적인 의도 분석 및 학습 시스템**으로 발전했습니다! 🎉 