-- 병원 질문 유형 초기 데이터 삽입

-- 1. 카테고리 삽입
INSERT INTO `question_categories` (`category_code`, `category_name`, `description`) VALUES
('HOSPITAL', '병원', '의료기관 관련 질문들'),
('PHARMACY', '약국', '약국 관련 질문들 (향후 확장)'),
('GENERAL', '일반', '기타 의료 관련 일반 질문들');

-- 2. 병원 카테고리의 질문 유형들 삽입
INSERT INTO `question_types` (
    `category_id`, 
    `type_code`, 
    `type_name`, 
    `description`, 
    `keywords`, 
    `sample_questions`, 
    `answer_template`, 
    `db_fields`, 
    `priority`
) VALUES

-- 기본 정보 질문들
(1, 'BASIC_INFO', '기본 정보', '병원의 기본적인 정보 조회',
 JSON_ARRAY('정보', '소개', '개요', '기본', '어떤', '무엇'),
 JSON_ARRAY('삼성서울병원 정보', '아산병원 소개', '세브란스병원 개요'),
 '{{hospital_name}}은(는) {{category_name}} 입니다. 주소는 {{address}}이고, 전화번호는 {{phone_number}}입니다.',
 JSON_ARRAY('institution_name', 'category_name', 'address', 'phone_number', 'homepage_url'),
 100),

-- 위치/주소 질문들  
(1, 'LOCATION', '위치/주소', '병원의 위치나 주소 문의',
 JSON_ARRAY('위치', '주소', '어디', '찾아가는길', '길', '어디에', '어디있', '소재지'),
 JSON_ARRAY('삼성서울병원 위치', '아산병원 주소', '세브란스병원 어디에 있어'),
 '{{hospital_name}}의 주소는 {{address}}입니다. (우편번호: {{postal_code}})',
 JSON_ARRAY('institution_name', 'address', 'postal_code', 'location_x', 'location_y'),
 95),

-- 연락처 질문들
(1, 'CONTACT', '연락처', '병원의 전화번호나 연락처 문의',
 JSON_ARRAY('전화번호', '연락처', '전화', '번호', '콜센터', '문의'),
 JSON_ARRAY('삼성서울병원 전화번호', '아산병원 연락처', '세브란스병원 전화'),
 '{{hospital_name}}의 전화번호는 {{phone_number}}입니다.',
 JSON_ARRAY('institution_name', 'phone_number'),
 90),

-- 홈페이지 질문들
(1, 'HOMEPAGE', '홈페이지', '병원 홈페이지 URL 문의',
 JSON_ARRAY('홈페이지', '웹사이트', '사이트', '인터넷', '온라인', 'URL'),
 JSON_ARRAY('삼성서울병원 홈페이지', '아산병원 웹사이트', '세브란스병원 사이트'),
 '{{hospital_name}}의 홈페이지는 {{homepage_url}}입니다.',
 JSON_ARRAY('institution_name', 'homepage_url'),
 85),

-- 진료과/종별 질문들
(1, 'SPECIALTY', '진료과/종별', '병원의 진료과목이나 종별 문의',
 JSON_ARRAY('진료과', '과목', '종별', '전문', '분야', '무엇을', '어떤과'),
 JSON_ARRAY('삼성서울병원 진료과', '아산병원 전문분야', '세브란스병원 종별'),
 '{{hospital_name}}은(는) {{category_name}}입니다.',
 JSON_ARRAY('institution_name', 'category_name', 'category_code'),
 80),

-- 의료진 정보 질문들
(1, 'MEDICAL_STAFF', '의료진 정보', '병원의 의료진 수나 구성 문의',
 JSON_ARRAY('의사', '의료진', '선생님', '전문의', '인원', '몇명', '구성'),
 JSON_ARRAY('삼성서울병원 의사 수', '아산병원 의료진', '세브란스병원 전문의'),
 '{{hospital_name}}의 총 의사 수는 {{total_doctors}}명입니다. (전문의: {{medicine_specialist_doctors}}명, 일반의: {{general_medicine_doctors}}명)',
 JSON_ARRAY('institution_name', 'total_doctors', 'medicine_specialist_doctors', 'general_medicine_doctors', 'dental_specialist_doctors', 'oriental_specialist_doctors'),
 75),

-- 개설일/연혁 질문들
(1, 'HISTORY', '개설일/연혁', '병원의 개설일이나 연혁 문의',
 JSON_ARRAY('개설일', '설립', '언제', '연혁', '역사', '창립', '개원'),
 JSON_ARRAY('삼성서울병원 개설일', '아산병원 언제 설립', '세브란스병원 연혁'),
 '{{hospital_name}}의 개설일은 {{establishment_date}}입니다.',
 JSON_ARRAY('institution_name', 'establishment_date'),
 70),

-- 지역별 검색 질문들
(1, 'REGIONAL_SEARCH', '지역별 검색', '특정 지역의 병원들 검색',
 JSON_ARRAY('지역', '근처', '주변', '동네', '시', '구', '동'),
 JSON_ARRAY('강남구 병원', '서울 종합병원', '부산 치과'),
 '{{region}}에 있는 {{category_name}} 목록입니다.',
 JSON_ARRAY('sido_name', 'sigungu_name', 'eupmyeondong', 'category_name'),
 65),

-- 종별 검색 질문들
(1, 'CATEGORY_SEARCH', '종별 검색', '특정 종별의 병원들 검색',
 JSON_ARRAY('종합병원', '병원', '의원', '치과', '한의원', '요양병원'),
 JSON_ARRAY('종합병원 찾기', '치과 검색', '한의원 목록'),
 '{{category_name}} 목록입니다.',
 JSON_ARRAY('category_name', 'category_code'),
 60),

-- 복합 검색 질문들
(1, 'COMPLEX_SEARCH', '복합 검색', '지역 + 종별 등 복합 조건 검색',
 JSON_ARRAY('에서', '에 있는', '근처', '주변'),
 JSON_ARRAY('강남 치과', '서울 한의원', '부산 종합병원'),
 '{{region}} {{category_name}} 검색 결과입니다.',
 JSON_ARRAY('sido_name', 'sigungu_name', 'category_name'),
 55),

-- 좌표/거리 질문들
(1, 'COORDINATES', '좌표/거리', '병원의 좌표나 거리 관련 문의',
 JSON_ARRAY('좌표', '위도', '경도', '거리', '얼마나', '멀리'),
 JSON_ARRAY('삼성서울병원 좌표', '아산병원 위치 좌표'),
 '{{hospital_name}}의 좌표는 위도 {{location_y}}, 경도 {{location_x}}입니다.',
 JSON_ARRAY('institution_name', 'location_x', 'location_y'),
 50),

-- 기타 질문들
(1, 'OTHER', '기타', '위 분류에 속하지 않는 기타 질문들',
 JSON_ARRAY('기타', '다른', '또', '그외'),
 JSON_ARRAY('기타 문의사항'),
 '죄송합니다. 해당 질문에 대한 정보를 찾을 수 없습니다. 다른 방식으로 질문해 주세요.',
 JSON_ARRAY(),
 10);

-- 3. 통계용 뷰 생성
CREATE OR REPLACE VIEW `question_type_stats` AS
SELECT 
    qc.category_name,
    qt.type_name,
    qt.type_code,
    COUNT(ql.id) as question_count,
    AVG(ql.response_time_ms) as avg_response_time,
    MAX(ql.created_at) as last_used
FROM question_categories qc
LEFT JOIN question_types qt ON qc.id = qt.category_id
LEFT JOIN question_logs ql ON qt.id = ql.detected_type_id
WHERE qt.is_active = 1
GROUP BY qc.id, qt.id
ORDER BY question_count DESC, qt.priority DESC; 