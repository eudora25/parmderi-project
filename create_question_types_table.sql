-- 질문 유형 관리 테이블들 생성

-- 1. 카테고리 테이블 (병원, 약국, 기타 등)
CREATE TABLE IF NOT EXISTS `question_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_code` VARCHAR(20) NOT NULL UNIQUE COMMENT '카테고리 코드',
    `category_name` VARCHAR(50) NOT NULL COMMENT '카테고리명',
    `description` TEXT COMMENT '카테고리 설명',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_category_code` (`category_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='질문 카테고리';

-- 2. 질문 유형 테이블
CREATE TABLE IF NOT EXISTS `question_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL COMMENT '카테고리 ID',
    `type_code` VARCHAR(30) NOT NULL COMMENT '질문 유형 코드',
    `type_name` VARCHAR(100) NOT NULL COMMENT '질문 유형명',
    `description` TEXT COMMENT '질문 유형 설명',
    `keywords` JSON COMMENT '관련 키워드 배열',
    `sample_questions` JSON COMMENT '샘플 질문들',
    `answer_template` TEXT COMMENT '답변 템플릿',
    `db_fields` JSON COMMENT '사용되는 DB 필드들',
    `priority` INT DEFAULT 0 COMMENT '우선순위 (높을수록 우선)',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`category_id`) REFERENCES `question_categories`(`id`) ON DELETE CASCADE,
    INDEX `idx_category_type` (`category_id`, `type_code`),
    INDEX `idx_priority` (`priority` DESC),
    UNIQUE KEY `uk_category_type` (`category_id`, `type_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='질문 유형';

-- 3. 질문 로그 테이블 (사용자 질문 분석용)
CREATE TABLE IF NOT EXISTS `question_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_question` TEXT NOT NULL COMMENT '사용자 질문',
    `detected_type_id` INT COMMENT '감지된 질문 유형 ID',
    `hospital_name` VARCHAR(200) COMMENT '추출된 병원명',
    `search_results_count` INT DEFAULT 0 COMMENT '검색 결과 수',
    `response_time_ms` INT DEFAULT 0 COMMENT '응답 시간(밀리초)',
    `ip_address` VARCHAR(45) COMMENT '사용자 IP',
    `user_agent` TEXT COMMENT '사용자 에이전트',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`detected_type_id`) REFERENCES `question_types`(`id`) ON DELETE SET NULL,
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_type_id` (`detected_type_id`),
    INDEX `idx_hospital_name` (`hospital_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='질문 로그'; 