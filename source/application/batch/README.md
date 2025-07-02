# 📊 Parmderi 배치 시스템

CodeIgniter 기반의 의료기관 데이터 처리를 위한 배치 작업 시스템입니다.

## 📁 구조

```
source/application/batch/
├── Medical_data_batch.php    # 의료기관 데이터 마이그레이션 배치 클래스
├── Batch_helper.php          # 배치 작업 공통 유틸리티
├── batch_config.php          # 배치 시스템 설정
└── README.md                 # 이 문서

source/application/controllers/
└── Batch_runner.php          # CLI 배치 실행 컨트롤러
```

## 🚀 사용법

### 1. CLI를 통한 배치 실행

```bash
# Docker 컨테이너 내부에서 실행
docker exec -it parmderi_nginx_php81 bash
cd /var/www/html

# 각종 배치 작업 실행
php index.php batch_runner migrate_medical_data    # 의료기관 데이터 마이그레이션
php index.php batch_runner cleanup_excel_files     # 엑셀 파일 정리
php index.php batch_runner cleanup_logs            # 로그 파일 정리
php index.php batch_runner backup_database         # 데이터베이스 백업
php index.php batch_runner status                  # 시스템 상태 확인
php index.php batch_runner help                    # 도움말 표시
```

### 2. Windows 호스트에서 직접 실행

```powershell
# 프로젝트 디렉토리로 이동
cd D:\Work_Project\parmderi_project\source

# Docker를 통한 배치 실행
docker exec parmderi_nginx_php81 php /var/www/html/index.php batch_runner migrate_medical_data
```

## 🔧 주요 기능

### 1. 의료기관 데이터 마이그레이션
- **기능**: `im_medical_institutions` 테이블의 데이터를 정규화된 4개 테이블로 분리
- **대상 테이블**:
  - `medical_institution` (기본 정보)
  - `medical_institution_facility` (시설 정보)
  - `medical_institution_hospital` (병원 정보)
  - `medical_institution_specialty` (진료과목 정보)
- **특징**: 증분 처리, 트랜잭션 보장, 상세 로깅

### 2. 파일 정리
- **엑셀 파일**: 30일 이상 된 업로드 파일 자동 삭제
- **로그 파일**: 30일 이상 된 로그 파일 자동 삭제
- **백업 파일**: 30일 이상 된 백업 파일 자동 삭제

### 3. 데이터베이스 백업
- **백업 방식**: Docker 컨테이너 내부 mariadb-dump 사용
- **백업 위치**: `/backups/` 폴더
- **파일명 형식**: `parmderi_backup_YYYY-MM-DD_HH-MM-SS.sql`

### 4. 시스템 모니터링
- 데이터베이스 연결 상태 확인
- 필수 테이블 존재 및 레코드 수 확인
- 디스크 사용량 모니터링
- 메모리 사용량 모니터링

## ⚙️ 설정

### 배치 설정 (batch_config.php)

```php
// 기본 설정
define('BATCH_LOG_RETENTION_DAYS', 30);     // 로그 보관 기간
define('BATCH_BACKUP_RETENTION_DAYS', 30);  // 백업 보관 기간
define('BATCH_MEMORY_LIMIT', 512);          // 메모리 제한 (MB)

// 의료기관 마이그레이션 설정
define('MEDICAL_MIGRATION_BATCH_SIZE', 100);           // 배치 크기
define('MEDICAL_MIGRATION_PROGRESS_INTERVAL', 100);    // 진행률 표시 간격
define('MEDICAL_MIGRATION_AUTO_BACKUP', true);         // 자동 백업
```

## 📋 로그 시스템

### 로그 파일 위치
- **배치 로그**: `source/application/logs/batch_YYYY-MM-DD_HH-MM-SS.log`
- **마이그레이션 로그**: `logs/migration/migration_YYYY-MM-DD_HH-MM-SS.log`

### 로그 레벨
- **INFO**: 일반 정보
- **WARNING**: 경고 메시지
- **ERROR**: 오류 메시지

### 로그 예시
```
[2025-06-23 15:30:15] [INFO] === 의료기관 데이터 마이그레이션 배치 시작 ===
[2025-06-23 15:30:16] [INFO] 마지막 마이그레이션 시점: 2025-06-20 14:25:30
[2025-06-23 15:30:16] [INFO] 처리할 레코드 수: 1,250
[2025-06-23 15:30:18] [INFO] 진행률: 8.0% (100/1250)
[2025-06-23 15:30:45] [INFO] === 마이그레이션 완료 ===
[2025-06-23 15:30:45] [INFO] 처리된 레코드 수: 1,250
[2025-06-23 15:30:45] [INFO] 오류 발생 수: 0
[2025-06-23 15:30:45] [INFO] 실행 시간: 29.34초
[2025-06-23 15:30:45] [INFO] 평균 처리 속도: 42.58개/초
```

## 🔄 cron 설정 (자동 실행)

### Linux/Unix 환경
```bash
# crontab 편집
crontab -e

# 스케줄 추가
0 2 * * * docker exec parmderi_nginx_php81 php /var/www/html/index.php batch_runner migrate_medical_data
0 3 * * 0 docker exec parmderi_nginx_php81 php /var/www/html/index.php batch_runner cleanup_excel_files
0 4 * * 0 docker exec parmderi_nginx_php81 php /var/www/html/index.php batch_runner cleanup_logs
0 1 * * * docker exec parmderi_nginx_php81 php /var/www/html/index.php batch_runner backup_database
```

### Windows 작업 스케줄러
```powershell
# PowerShell 스크립트 생성: batch_medical_migration.ps1
Set-Location "D:\Work_Project\parmderi_project"
docker exec parmderi_nginx_php81 php /var/www/html/index.php batch_runner migrate_medical_data

# 작업 스케줄러에 등록
schtasks /create /tn "Medical Data Migration" /tr "powershell.exe -File D:\path\to\batch_medical_migration.ps1" /sc daily /st 02:00
```

## 🛠️ 개발 및 확장

### 새로운 배치 작업 추가

1. **배치 클래스 생성** (`application/batch/Your_batch.php`)
```php
<?php
class Your_batch {
    public function execute() {
        // 배치 로직 구현
    }
}
```

2. **컨트롤러에 메소드 추가** (`controllers/Batch_runner.php`)
```php
public function your_batch_method() {
    require_once(APPPATH . 'batch/Your_batch.php');
    $batch = new Your_batch();
    return $batch->execute();
}
```

3. **설정 추가** (`batch/batch_config.php`)
```php
$config['your_batch_config'] = array(
    'setting1' => 'value1',
    'setting2' => 'value2'
);
```

### 유틸리티 사용

```php
// 배치 헬퍼 사용
require_once(APPPATH . 'batch/Batch_helper.php');
$helper = new Batch_helper('your_batch');

$helper->log('작업 시작');
$helper->show_progress(50, 100, '처리 중...');
$helper->log_error('오류 발생');
$helper->write_summary(100, 2);
```

## 🚨 문제 해결

### 일반적인 문제

1. **권한 오류**
```bash
# 로그 디렉토리 권한 설정
chmod 755 source/application/logs/
chmod 755 logs/migration/
```

2. **메모리 부족**
```php
// batch_config.php에서 메모리 제한 증가
define('BATCH_MEMORY_LIMIT', 1024); // 1GB
```

3. **데이터베이스 연결 오류**
```bash
# Docker 컨테이너 상태 확인
docker ps
docker logs parmderi_mariadb
```

### 디버깅

```bash
# 상세 로그 확인
tail -f source/application/logs/batch_*.log

# 시스템 상태 확인
php index.php batch_runner status

# 테스트 실행 (소량 데이터)
# batch_config.php에서 MEDICAL_MIGRATION_BATCH_SIZE를 10으로 설정 후 실행
```

## 📞 지원

문제가 발생하거나 기능 요청이 있으시면 개발팀에 문의하세요.

- **로그 파일**: 오류 발생 시 로그 파일을 첨부해 주세요
- **시스템 상태**: `php index.php batch_runner status` 결과를 포함해 주세요
- **환경 정보**: Docker 버전, PHP 버전 등을 알려주세요 