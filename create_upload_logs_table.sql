-- ====================================================================
-- upload_logs 테이블 생성 스크립트
-- 목적: Excel 파일 업로드 이력 및 상태 추적
-- 작성일: 2025-06-23
-- ====================================================================

CREATE TABLE upload_logs (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '업로드 로그 고유 ID',
    file_name VARCHAR(255) NOT NULL COMMENT '업로드된 파일명 (원본 파일명)',
    file_size BIGINT NOT NULL COMMENT '파일 크기 (바이트 단위)',
    total_rows INT DEFAULT 0 COMMENT '총 행 수 (헤더 제외)',
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '업로드 실행 일시',
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending' COMMENT '업로드 상태',
    user_agent TEXT COMMENT '사용자 브라우저 정보',
    ip_address VARCHAR(45) COMMENT '업로드 요청 IP 주소 (IPv4/IPv6)',
    
    -- Excel_upload 컨트롤러에서 사용하는 컬럼들
    success_count INT DEFAULT 0 COMMENT '성공적으로 처리된 행 수',
    update_count INT DEFAULT 0 COMMENT '업데이트된 행 수',
    error_count INT DEFAULT 0 COMMENT '처리 실패한 행 수',
    skipped_count INT DEFAULT 0 COMMENT '건너뛴 행 수',
    completion_date TIMESTAMP NULL COMMENT '처리 완료 일시',
    
    -- 추가 유용한 컬럼들
    processing_time_ms INT COMMENT '처리 시간 (밀리초)',
    error_message TEXT COMMENT '오류 메시지 (실패 시)',
    file_path VARCHAR(500) COMMENT '서버 내 파일 저장 경로',
    
    -- 시간 추적
    started_at TIMESTAMP NULL COMMENT '처리 시작 시간',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '마지막 수정 시간',
    
    -- 인덱스 설정
    INDEX idx_upload_date (upload_date),
    INDEX idx_status (status),
    INDEX idx_ip_address (ip_address),
    INDEX idx_file_name (file_name),
    INDEX idx_status_date (status, upload_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Excel 파일 업로드 이력 및 상태 관리 테이블';

-- ====================================================================
-- 초기 데이터 (선택사항)
-- ====================================================================

-- 샘플 업로드 로그 데이터
INSERT INTO upload_logs (
    file_name, 
    file_size, 
    total_rows, 
    status, 
    user_agent, 
    ip_address,
    processed_rows,
    failed_rows,
    processing_time_ms
) VALUES 
(
    'sample_medical_data.xlsx', 
    1024000, 
    500, 
    'completed', 
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 
    '127.0.0.1',
    495,
    5,
    15000
),
(
    'hospital_list.xlsx', 
    2048000, 
    1200, 
    'processing', 
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 
    '192.168.1.100',
    800,
    0,
    NULL
);

-- ====================================================================
-- 테이블 정보 조회 쿼리 (확인용)
-- ====================================================================

-- 테이블 구조 확인
-- DESCRIBE upload_logs;

-- 업로드 통계 조회
-- SELECT 
--     status,
--     COUNT(*) as count,
--     AVG(file_size) as avg_file_size,
--     AVG(total_rows) as avg_rows,
--     AVG(processing_time_ms) as avg_processing_time
-- FROM upload_logs 
-- GROUP BY status;

-- 최근 업로드 목록
-- SELECT 
--     id,
--     file_name,
--     ROUND(file_size/1024/1024, 2) as file_size_mb,
--     total_rows,
--     status,
--     upload_date
-- FROM upload_logs 
-- ORDER BY upload_date DESC 
-- LIMIT 10; 