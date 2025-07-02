# 의료기관 데이터 자동 마이그레이션 배치 시스템

## 개요

`im_medical_institutions` 테이블의 데이터를 정규화된 5개 테이블로 자동 마이그레이션하는 배치 시스템입니다. crontab 또는 Windows 작업 스케줄러를 통해 주기적으로 실행되어 증분 데이터를 처리합니다.

## 파일 구조

```
scripts/
├── medical_data_migration.sh      # Linux/Unix용 배치 스크립트
├── medical_data_migration.ps1     # Windows PowerShell용 배치 스크립트
├── crontab_config.txt             # crontab 설정 파일
├── windows_task_scheduler.xml     # Windows 작업 스케줄러 XML
└── README.md                      # 이 문서
```

## 주요 기능

### 1. 스마트 증분 처리
- 마지막 마이그레이션 이후 변경된 데이터만 처리
- 전체 데이터 재처리 방지로 성능 최적화
- `updated_at` 컬럼 기반 변경 감지

### 2. 자동 백업
- 마이그레이션 전 자동 데이터베이스 백업
- 백업 파일명: `backup_before_migration_YYYYMMDD_HHMMSS.sql`
- 30일 이상 된 백업 파일 자동 정리

### 3. 포괄적 로깅
- 상세한 실행 로그 기록
- 오류 발생 시 상세한 에러 정보 제공
- 로그 파일명: `migration_YYYYMMDD_HHMMSS.log`

### 4. 데이터 무결성 보장
- 트랜잭션 기반 안전한 데이터 처리
- 에러 발생 시 자동 롤백
- 데이터 검증 및 통계 제공

## Linux/Unix 환경 설정

### 1. 스크립트 권한 설정

```bash
# 실행 권한 부여
chmod +x /path/to/parmderi_project/scripts/medical_data_migration.sh

# 소유자 확인 (Docker 실행 권한 필요)
ls -la /path/to/parmderi_project/scripts/medical_data_migration.sh
```

### 2. crontab 설정

```bash
# crontab 편집기 열기
crontab -e

# 매일 오전 2시 실행 (추천)
0 2 * * * /path/to/parmderi_project/scripts/medical_data_migration.sh >> /path/to/parmderi_project/logs/cron.log 2>&1

# 설정 확인
crontab -l
```

### 3. 수동 테스트 실행

```bash
# 직접 실행 테스트
/path/to/parmderi_project/scripts/medical_data_migration.sh

# 로그 확인
tail -f /path/to/parmderi_project/logs/migration/migration_*.log
```

## Windows 환경 설정

### 1. PowerShell 실행 정책 설정

```powershell
# 관리자 권한으로 PowerShell 실행
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope LocalMachine

# 현재 정책 확인
Get-ExecutionPolicy
```

### 2. 작업 스케줄러 설정

#### 방법 1: XML 파일 가져오기
```cmd
# 관리자 권한으로 명령 프롬프트 실행
schtasks /create /xml "D:\Work_Project\parmderi_project\scripts\windows_task_scheduler.xml" /tn "MedicalDataMigration"
```

#### 방법 2: GUI를 통한 설정
1. `Windows + R` → `taskschd.msc` 실행
2. 작업 스케줄러 라이브러리 → 작업 만들기
3. 일반 탭:
   - 이름: `MedicalDataMigration`
   - 가장 높은 수준의 권한으로 실행 체크
4. 트리거 탭:
   - 새로 만들기 → 매일 → 시작 시간: 오전 2:00
5. 동작 탭:
   - 프로그램 시작 → `powershell.exe`
   - 인수: `-ExecutionPolicy Bypass -File "D:\Work_Project\parmderi_project\scripts\medical_data_migration.ps1"`

### 3. 수동 테스트 실행

```powershell
# PowerShell에서 직접 실행
D:\Work_Project\parmderi_project\scripts\medical_data_migration.ps1

# 로그 확인
Get-Content D:\Work_Project\parmderi_project\logs\migration\migration_*.log -Tail 20
```

## 실행 스케줄 옵션

### 추천 스케줄

| 빈도 | crontab | 설명 |
|------|---------|------|
| 매일 | `0 2 * * *` | 매일 오전 2시 (추천) |
| 평일만 | `0 2 * * 1-5` | 평일 오전 2시 |
| 주 1회 | `0 3 * * 0` | 매주 일요일 오전 3시 |
| 월 1회 | `0 3 1 * *` | 매월 1일 오전 3시 |

### 고려사항
- **서버 부하**: 새벽 시간대 실행 권장
- **데이터 업데이트 빈도**: 원본 데이터 업데이트 주기에 맞춰 설정
- **백업 용량**: 일일 백업 시 디스크 용량 모니터링 필요

## 모니터링 및 문제 해결

### 1. 로그 모니터링

```bash
# 최신 로그 실시간 확인
tail -f /path/to/parmderi_project/logs/migration/migration_*.log

# 에러 로그 검색
grep -i error /path/to/parmderi_project/logs/migration/*.log

# 마이그레이션 완료 확인
grep "마이그레이션 완료" /path/to/parmderi_project/logs/migration/*.log
```

### 2. 일반적인 문제 해결

#### Docker 컨테이너 미실행
```bash
# 컨테이너 상태 확인
docker ps

# 컨테이너 시작
docker-compose up -d
```

#### 데이터베이스 연결 실패
```bash
# 데이터베이스 연결 테스트
docker exec parmderi_mariadb mariadb -udevh -pA77ila@ htest -e "SELECT 1;"
```

#### 권한 문제
```bash
# 스크립트 권한 확인
ls -la scripts/medical_data_migration.sh

# 실행 권한 부여
chmod +x scripts/medical_data_migration.sh
```

### 3. 성능 최적화

#### 대용량 데이터 처리
- 배치 크기 조정: SQL 스크립트에서 `LIMIT` 사용
- 인덱스 최적화: 자주 조회되는 컬럼에 인덱스 추가
- 파티셔닝: 대용량 테이블 월별/연도별 파티션

#### 백업 최적화
```bash
# 압축 백업 (선택사항)
docker exec parmderi_mariadb mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > backup_$TIMESTAMP.sql.gz
```

## 알림 설정 (선택사항)

### 1. 이메일 알림

```bash
# 스크립트 수정하여 이메일 전송 추가
# sendmail 또는 mail 명령 사용
echo "마이그레이션 완료: $(date)" | mail -s "Migration Status" admin@example.com
```

### 2. Slack 알림

```bash
# Webhook을 통한 Slack 알림
curl -X POST -H 'Content-type: application/json' \
  --data '{"text":"의료기관 데이터 마이그레이션 완료"}' \
  YOUR_SLACK_WEBHOOK_URL
```

## 보안 고려사항

### 1. 데이터베이스 자격증명
- 환경변수 또는 설정 파일 사용 권장
- 스크립트 파일 권한 제한 (600 또는 700)

### 2. 백업 파일 보안
- 백업 파일 암호화 고려
- 백업 저장소 접근 권한 제한

### 3. 로그 보안
- 민감한 정보 로그에 기록하지 않기
- 로그 파일 권한 제한

## 문의 및 지원

- 기술 지원: 시스템 관리자
- 문서 업데이트: 2025-06-23
- 버전: 1.0.0 