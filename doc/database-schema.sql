-- ====================================================================
-- Parmderi Q&A 검색 시스템 데이터베이스 스키마
-- 작성일: 2024년
-- 목적: 자연어 처리 기반 Q&A 검색 시스템
-- 버전: 1.0 (Phase 1: 규칙 기반)
-- ====================================================================

-- 데이터베이스 생성 (이미 존재하는 경우 주석 처리)
-- CREATE DATABASE IF NOT EXISTS dev DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE dev;

-- ====================================================================
-- 1. 카테고리 테이블 (categories)
-- 용도: Q&A 분류 체계, 계층 구조 지원
-- ====================================================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '카테고리 고유 ID',
    name VARCHAR(100) NOT NULL COMMENT '카테고리 이름',
    description TEXT COMMENT '카테고리 상세 설명',
    parent_id INT NULL COMMENT '상위 카테고리 ID (계층 구조)',
    sort_order INT DEFAULT 0 COMMENT '정렬 순서 (낮은 수가 먼저)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성 상태 (1:활성, 0:비활성)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 일시',
    INDEX idx_parent (parent_id),
    INDEX idx_sort (sort_order),
    INDEX idx_active (is_active),
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Q&A 카테고리 분류 테이블';

-- ====================================================================
-- 2. 질문-답변 테이블 (qa_pairs)
-- 용도: 핵심 Q&A 데이터 저장
-- ====================================================================
CREATE TABLE qa_pairs (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '질문-답변 고유 ID',
    question TEXT NOT NULL COMMENT '질문 내용 (원문)',
    answer TEXT NOT NULL COMMENT '답변 내용 (HTML 태그 포함 가능)',
    category_id INT COMMENT '카테고리 ID (categories 테이블 참조)',
    keywords VARCHAR(500) COMMENT '검색용 키워드 (쉼표로 구분)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 일시',
    view_count INT DEFAULT 0 COMMENT '조회수 (검색 결과 클릭 횟수)',
    rating DECIMAL(3,2) DEFAULT 0.00 COMMENT '사용자 평가 점수 (0.00-5.00)',
    INDEX idx_category (category_id),
    INDEX idx_created (created_at),
    INDEX idx_rating (rating),
    FULLTEXT KEY ft_question (question),
    FULLTEXT KEY ft_keywords (keywords),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Q&A 질문-답변 데이터 저장 테이블';

-- ====================================================================
-- 3. 키워드 테이블 (keywords)
-- 용도: 검색 최적화를 위한 키워드 사전
-- ====================================================================
CREATE TABLE keywords (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '키워드 고유 ID',
    keyword VARCHAR(100) NOT NULL COMMENT '핵심 키워드',
    weight DECIMAL(3,2) DEFAULT 1.00 COMMENT '키워드 가중치 (0.01-9.99)',
    category_id INT COMMENT '연관 카테고리 ID',
    synonyms TEXT COMMENT '동의어 목록 (쉼표로 구분)',
    frequency INT DEFAULT 0 COMMENT '검색 빈도수',
    is_stopword TINYINT(1) DEFAULT 0 COMMENT '불용어 여부 (1:불용어, 0:일반어)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 일시',
    UNIQUE KEY uk_keyword (keyword),
    INDEX idx_category (category_id),
    INDEX idx_weight (weight),
    INDEX idx_frequency (frequency),
    INDEX idx_stopword (is_stopword),
    FULLTEXT KEY ft_synonyms (synonyms),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='검색 최적화를 위한 키워드 사전';

-- ====================================================================
-- 4. 검색 로그 테이블 (search_logs)
-- 용도: 사용자 검색 이력 및 성능 분석
-- ====================================================================
CREATE TABLE search_logs (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '검색 로그 고유 ID',
    user_query TEXT NOT NULL COMMENT '사용자가 입력한 검색 질문',
    matched_qa_id INT COMMENT '매칭된 Q&A ID (qa_pairs 테이블 참조)',
    similarity_score DECIMAL(5,4) COMMENT '유사도 점수 (0.0000-1.0000)',
    search_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '검색 실행 시간',
    user_feedback ENUM('helpful', 'not_helpful', 'partially_helpful') COMMENT '사용자 피드백',
    response_time_ms INT COMMENT '응답 시간 (밀리초)',
    user_ip VARCHAR(45) COMMENT '사용자 IP 주소 (IPv4/IPv6)',
    user_agent TEXT COMMENT '사용자 브라우저 정보',
    session_id VARCHAR(128) COMMENT '세션 ID (동일 사용자 추적)',
    INDEX idx_search_time (search_time),
    INDEX idx_qa_id (matched_qa_id),
    INDEX idx_similarity (similarity_score),
    INDEX idx_feedback (user_feedback),
    INDEX idx_session (session_id),
    FULLTEXT KEY ft_user_query (user_query),
    FOREIGN KEY (matched_qa_id) REFERENCES qa_pairs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 검색 이력 및 분석 데이터 저장';

-- ====================================================================
-- 5. 사용자 피드백 테이블 (user_feedback)
-- 용도: 사용자 만족도 및 시스템 개선 데이터
-- ====================================================================
CREATE TABLE user_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '피드백 고유 ID',
    search_log_id INT NOT NULL COMMENT '검색 로그 ID (search_logs 테이블 참조)',
    qa_id INT NOT NULL COMMENT 'Q&A ID (qa_pairs 테이블 참조)', 
    rating TINYINT(1) NOT NULL COMMENT '평가 점수 (1-5점)',
    feedback_type ENUM('rating', 'report', 'suggestion') DEFAULT 'rating' COMMENT '피드백 유형',
    comment TEXT COMMENT '사용자 의견 및 제안사항',
    user_ip VARCHAR(45) COMMENT '사용자 IP 주소',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '피드백 생성 시간',
    INDEX idx_search_log (search_log_id),
    INDEX idx_qa (qa_id),
    INDEX idx_rating (rating),
    INDEX idx_type (feedback_type),
    INDEX idx_created (created_at),
    FOREIGN KEY (search_log_id) REFERENCES search_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (qa_id) REFERENCES qa_pairs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 피드백 및 평가 데이터';

-- ====================================================================
-- 6. 관리자 사용자 테이블 (admin_users)
-- 용도: Q&A 시스템 관리자 계정
-- ====================================================================
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '관리자 ID',
    username VARCHAR(50) NOT NULL COMMENT '로그인 아이디',
    password_hash VARCHAR(255) NOT NULL COMMENT '암호화된 비밀번호',
    email VARCHAR(100) NOT NULL COMMENT '이메일 주소',
    full_name VARCHAR(100) COMMENT '전체 이름',
    role ENUM('super_admin', 'admin', 'editor') DEFAULT 'editor' COMMENT '권한 레벨',
    is_active TINYINT(1) DEFAULT 1 COMMENT '계정 활성 상태',
    last_login TIMESTAMP NULL COMMENT '마지막 로그인 시간',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '계정 생성 시간',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '정보 수정 시간',
    UNIQUE KEY uk_username (username),
    UNIQUE KEY uk_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active),
    INDEX idx_last_login (last_login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Q&A 시스템 관리자 계정';

-- ====================================================================
-- 초기 데이터 삽입
-- ====================================================================

-- 기본 카테고리 생성
INSERT INTO categories (name, description, sort_order) VALUES
('일반', '일반적인 질문과 답변', 1),
('기술지원', '기술적인 문제 해결', 2),
('계정관리', '회원가입, 로그인 관련', 3),
('결제/환불', '결제 및 환불 관련', 4),
('기타', '기타 문의사항', 99);

-- 샘플 Q&A 데이터
INSERT INTO qa_pairs (question, answer, category_id, keywords) VALUES
('회원가입은 어떻게 하나요?', '홈페이지 상단의 회원가입 버튼을 클릭하시고, 필요한 정보를 입력해주세요.', 3, '회원가입,계정생성,가입'),
('비밀번호를 잊어버렸어요', '로그인 페이지에서 비밀번호 찾기를 이용하시거나 고객센터로 문의해주세요.', 3, '비밀번호,찾기,분실'),
('결제는 어떤 방법이 있나요?', '신용카드, 계좌이체, 휴대폰 결제를 지원합니다.', 4, '결제,카드,계좌이체'),
('환불은 언제까지 가능한가요?', '구매일로부터 7일 이내에 환불 신청이 가능합니다.', 4, '환불,취소,반품'),
('시스템 오류가 발생했어요', '브라우저 캐시를 삭제하거나 다른 브라우저로 시도해보세요.', 2, '오류,에러,문제');

-- 기본 키워드 등록
INSERT INTO keywords (keyword, weight, category_id) VALUES
('회원가입', 1.5, 3),
('로그인', 1.5, 3),
('비밀번호', 1.3, 3),
('결제', 1.8, 4),
('환불', 1.7, 4),
('오류', 1.2, 2),
('에러', 1.2, 2);

-- 기본 관리자 계정 (비밀번호: admin123)
INSERT INTO admin_users (username, password_hash, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@parmderi.com', '시스템 관리자', 'super_admin');

-- ====================================================================
-- 추가 인덱스 및 최적화
-- ====================================================================

-- 검색 성능 최적화를 위한 복합 인덱스
ALTER TABLE qa_pairs ADD INDEX idx_category_rating (category_id, rating);
ALTER TABLE search_logs ADD INDEX idx_time_similarity (search_time, similarity_score);

-- 테이블 상태 확인 쿼리 (선택사항)
-- SHOW TABLE STATUS;
-- SHOW INDEX FROM qa_pairs;

-- ====================================================================
-- 완료
-- ====================================================================
-- 스키마 생성이 완료되었습니다.
-- 다음 단계: CodeIgniter에서 database.php 설정 확인
-- ==================================================================== 