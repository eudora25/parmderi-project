#!/bin/bash

# ====================================================================
# 의료기관 데이터 자동 마이그레이션 배치 스크립트
# 작성일: 2025-06-23
# 목적: im_medical_institutions 테이블 데이터를 정규화된 테이블로 자동 이전
# 실행: crontab에서 주기적 실행
# ====================================================================

# 설정 변수
SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$PROJECT_ROOT/logs/migration"
BACKUP_DIR="$PROJECT_ROOT/backups"
DOCKER_CONTAINER=""parmderi_mariadb"
DB_USER=""devh"
DB_PASS=""A77ila@"
DB_NAME=""htest"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="$LOG_DIR/migration_$TIMESTAMP.log"

# 로그 디렉토리 생성
mkdir -p "$LOG_DIR"
mkdir -p "$BACKUP_DIR"

# 로그 함수
log() {
    echo ""[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo ""[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1" | tee -a "$LOG_FILE" >&2
}

# 에러 처리 함수
handle_error() {
    log_error ""스크립트 실행 중 오류가 발생했습니다. 라인: $1"
    log_error ""백업 파일 위치: $BACKUP_FILE"
    exit 1
}

# 에러 트랩 설정
trap 'handle_error $LINENO' ERR

log ""=== 의료기관 데이터 마이그레이션 시작 ==="
log ""로그 파일: $LOG_FILE"

# Docker 컨테이너 상태 확인
log ""Docker 컨테이너 상태 확인..."
if ! docker ps | grep -q "$DOCKER_CONTAINER"; then
    log_error ""$DOCKER_CONTAINER 컨테이너가 실행되지 않고 있습니다."
    exit 1
fi
log ""✅ Docker 컨테이너 정상 실행 중"

# 데이터베이스 연결 확인
log ""데이터베이스 연결 확인..."
if ! docker exec "$DOCKER_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e ""SELECT 1;" > /dev/null 2>&1; then
    log_error ""데이터베이스 연결에 실패했습니다."
    exit 1
fi
log ""✅ 데이터베이스 연결 성공"

# 원본 테이블 존재 확인
log ""원본 테이블 존재 확인..."
TABLE_EXISTS=$(docker exec "$DOCKER_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e ""SHOW TABLES LIKE 'im_medical_institutions';" | wc -l)
if [ "$TABLE_EXISTS" -eq 0 ]; then
    log_error ""im_medical_institutions 테이블이 존재하지 않습니다."
    exit 1
fi
log ""✅ 원본 테이블 존재 확인"

# 원본 데이터 수 확인
ORIGINAL_COUNT=$(docker exec "$DOCKER_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e ""SELECT COUNT(*) FROM im_medical_institutions;" | tail -n 1)
log ""원본 테이블 레코드 수: $ORIGINAL_COUNT"

if [ "$ORIGINAL_COUNT" -eq 0 ]; then
    log ""원본 테이블에 데이터가 없습니다. 마이그레이션을 건너뜁니다."
    exit 0
fi

# 백업 생성
log ""=== 데이터베이스 백업 생성 ==="
BACKUP_FILE="$BACKUP_DIR/backup_before_migration_$TIMESTAMP.sql"

log ""백업 파일 생성: $BACKUP_FILE"
docker exec "$DOCKER_CONTAINER" mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null

if [ $? -eq 0 ]; then
    log ""✅ 백업 완료: $(du -h "$BACKUP_FILE" | cut -f1)"
else
    log_error ""백업 생성에 실패했습니다."
    exit 1
fi

# 정규화된 테이블 확인
log ""=== 정규화된 테이블 확인 ==="

NORMALIZED_TABLE_EXISTS=$(docker exec "$DOCKER_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e ""SHOW TABLES LIKE 'medical_institution';" | wc -l)

if [ "$NORMALIZED_TABLE_EXISTS" -eq 0 ]; then
    log ""정규화된 테이블이 존재하지 않습니다. 테이블을 생성합니다."
    docker cp "$PROJECT_ROOT/migration_scripts/01_create_normalized_tables.sql" "$DOCKER_CONTAINER:/tmp/"
    log ""정규화된 테이블 생성 중..."
    docker exec "$DOCKER_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /tmp/01_create_normalized_tables.sql >> "$LOG_FILE" 2>&1
    
    if [ $? -eq 0 ]; then
        log ""✅ 정규화된 테이블 생성 완료"
    else
        log_error ""정규화된 테이블 생성에 실패했습니다."
        exit 1
    fi
else
    log ""✅ 정규화된 테이블이 이미 존재합니다."
fi

log ""=== 마이그레이션 완료 ==="
exit 0
