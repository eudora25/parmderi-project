# ====================================================================
# 의료기관 데이터 자동 마이그레이션 배치 스크립트 (Windows PowerShell)
# 작성일: 2025-06-23
# 목적: im_medical_institutions 테이블 데이터를 정규화된 테이블로 자동 이전
# 실행: Windows 작업 스케줄러에서 주기적 실행
# ====================================================================

param(
    [string]$LogLevel = "INFO"
)

# 설정 변수
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$LogDir = Join-Path $ProjectRoot "logs\migration"
$BackupDir = Join-Path $ProjectRoot "backups"
$DockerContainer = "parmderi_mariadb"
$DbUser = "devh"
$DbPass = "A77ila@"
$DbName = "htest"
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$LogFile = Join-Path $LogDir "migration_$Timestamp.log"

# 로그 디렉토리 생성
if (!(Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir -Force | Out-Null
}
if (!(Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
}

# 로그 함수
function Write-Log {
    param(
        [string]$Message,
        [string]$Level = "INFO"
    )
    $Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $LogMessage = "[$Timestamp] [$Level] $Message"
    Write-Host $LogMessage
    Add-Content -Path $LogFile -Value $LogMessage -Encoding UTF8
}

function Write-LogError {
    param([string]$Message)
    Write-Log -Message $Message -Level "ERROR"
}

# 에러 처리 함수
function Handle-Error {
    param([string]$ErrorMessage)
    Write-LogError "스크립트 실행 중 오류가 발생했습니다: $ErrorMessage"
    Write-LogError "백업 파일 위치: $BackupFile"
    exit 1
}

try {
    # ====================================================================
    # 1. 사전 검사
    # ====================================================================
    Write-Log "=== 의료기관 데이터 마이그레이션 시작 ==="
    Write-Log "로그 파일: $LogFile"

    # Docker 상태 확인
    Write-Log "Docker 컨테이너 상태 확인..."
    $DockerPs = docker ps --format "table {{.Names}}" | Select-String $DockerContainer
    if (-not $DockerPs) {
        throw "$DockerContainer 컨테이너가 실행되지 않고 있습니다."
    }
    Write-Log "✅ Docker 컨테이너 정상 실행 중"

    # 데이터베이스 연결 확인
    Write-Log "데이터베이스 연결 확인..."
    $TestQuery = docker exec $DockerContainer mariadb -u$DbUser -p$DbPass $DbName -e "SELECT 1;" 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "Database connection failed."
    }
    Write-Log "✅ 데이터베이스 연결 성공"

    # 원본 테이블 존재 확인
    Write-Log "원본 테이블 존재 확인..."
    $TableCheck = docker exec $DockerContainer mariadb -u$DbUser -p$DbPass $DbName -e "SHOW TABLES LIKE 'im_medical_institutions';" 2>$null
    if (-not $TableCheck -or $TableCheck.Count -eq 0) {
        throw "im_medical_institutions 테이블이 존재하지 않습니다."
    }
    Write-Log "✅ 원본 테이블 존재 확인"

    # 원본 데이터 수 확인
    $OriginalCountResult = docker exec $DockerContainer mariadb -u$DbUser -p$DbPass $DbName -e "SELECT COUNT(*) FROM im_medical_institutions;" 2>$null
    $OriginalCount = ($OriginalCountResult | Select-Object -Last 1).Trim()
    Write-Log "원본 테이블 레코드 수: $OriginalCount"

    if ([int]$OriginalCount -eq 0) {
        Write-Log "원본 테이블에 데이터가 없습니다. 마이그레이션을 건너뜁니다."
        exit 0
    }

    # ====================================================================
    # 2. 백업 생성
    # ====================================================================
    Write-Log "=== 데이터베이스 백업 생성 ==="
    $BackupFile = Join-Path $BackupDir "backup_before_migration_$Timestamp.sql"

    Write-Log "백업 파일 생성: $BackupFile"
    docker exec $DockerContainer mysqldump -u$DbUser -p$DbPass $DbName > $BackupFile 2>$null

    if ($LASTEXITCODE -eq 0) {
        $BackupSize = (Get-Item $BackupFile).Length / 1MB
        Write-Log "✅ 백업 완료: $([math]::Round($BackupSize, 2)) MB"
    } else {
        throw "백업 생성에 실패했습니다."
    }

    # ====================================================================
    # 3. 정규화된 테이블 존재 확인 및 생성
    # ====================================================================
    Write-Log "=== 정규화된 테이블 확인 ==="

    # medical_institution 테이블 존재 확인
    $NormalizedTableCheck = docker exec $DockerContainer mariadb -u$DbUser -p$DbPass $DbName -e "SHOW TABLES LIKE 'medical_institution';" 2>$null

    if (-not $NormalizedTableCheck -or $NormalizedTableCheck.Count -eq 0) {
        Write-Log "정규화된 테이블이 존재하지 않습니다. 테이블을 생성합니다."
        
        # SQL 스크립트 복사 및 실행
        $SqlScript = Join-Path $ProjectRoot "migration_scripts\01_create_normalized_tables.sql"
        docker cp $SqlScript "${DockerContainer}:/tmp/"
        
        Write-Log "정규화된 테이블 생성 중..."
        docker exec $DockerContainer mariadb -u$DbUser -p$DbPass $DbName -e "source /tmp/01_create_normalized_tables.sql" 2>>$LogFile
        
        if ($LASTEXITCODE -eq 0) {
            Write-Log "✅ 정규화된 테이블 생성 완료"
        } else {
            throw "정규화된 테이블 생성에 실패했습니다."
        }
    } else {
        Write-Log "✅ 정규화된 테이블이 이미 존재합니다."
    }

    # ====================================================================
    # 4. 증분 데이터 확인 및 마이그레이션
    # ====================================================================
    Write-Log "=== 증분 데이터 확인 ==="

    # 마지막 마이그레이션 이후 변경사항 확인
    $LastMigrationResult = docker exec $DockerContainer mariadb -u$DbUser -p$DbPass $DbName -e "SELECT IFNULL(MAX(updated_at), '1900-01-01') FROM medical_institution;" 2>$null
    $LastMigrationTime = ($LastMigrationResult | Select-Object -Last 1).Trim()
    
    $NewCountResult = docker exec $DockerContainer mariadb -u$DbUser -p$DbPass $DbName -e "SELECT COUNT(*) FROM im_medical_institutions WHERE updated_at > '$LastMigrationTime';" 2>$null
    $NewOrUpdatedCount = ($NewCountResult | Select-Object -Last 1).Trim()

    Write-Log "마지막 마이그레이션: $LastMigrationTime"
    Write-Log "신규/수정된 레코드 수: $NewOrUpdatedCount"

    if ([int]$NewOrUpdatedCount -eq 0) {
        Write-Log "새로운 데이터가 없습니다. 마이그레이션을 건너뜁니다."
        Write-Log "=== 마이그레이션 완료 (변경사항 없음) ==="
        exit 0
    }

    Write-Log "=== 증분 데이터 마이그레이션 실행 ==="
    
    # 증분 마이그레이션 SQL 스크립트 실행
    $IncrementalScript = Join-Path $ProjectRoot "migration_scripts\02_migrate_data.sql"
    if (Test-Path $IncrementalScript) {
        docker cp $IncrementalScript "${DockerContainer}:/tmp/"
        Write-Log "증분 마이그레이션 실행 중..."
        docker exec $DockerContainer mariadb -u$DbUser -p$DbPass $DbName -e "source /tmp/02_migrate_data.sql" 2>>$LogFile
        
        if ($LASTEXITCODE -eq 0) {
            Write-Log "✅ 증분 마이그레이션 완료"
        } else {
            throw "증분 마이그레이션에 실패했습니다."
        }
    }

    # ====================================================================
    # 5. 검증 및 정리
    # ====================================================================
    Write-Log "=== 마이그레이션 결과 검증 ==="

    # 최종 레코드 수 확인
    $FinalInstitutionResult = docker exec $DockerContainer mariadb -u$DbUser -p$DbPass $DbName -e "SELECT COUNT(*) FROM medical_institution;" 2>$null
    $FinalInstitutionCount = ($FinalInstitutionResult | Select-Object -Last 1).Trim()
    
    $FinalSpecialtyResult = docker exec $DockerContainer mariadb -u$DbUser -p$DbPass $DbName -e "SELECT COUNT(*) FROM medical_institution_specialty;" 2>$null
    $FinalSpecialtyCount = ($FinalSpecialtyResult | Select-Object -Last 1).Trim()

    Write-Log "최종 의료기관 수: $FinalInstitutionCount"
    Write-Log "최종 진료과목 레코드 수: $FinalSpecialtyCount"

    # 오래된 백업 파일 정리 (30일 이상)
    $OldBackups = Get-ChildItem $BackupDir -Filter "backup_*.sql" | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-30) }
    foreach ($OldBackup in $OldBackups) {
        Remove-Item $OldBackup.FullName -Force
        Write-Log "오래된 백업 파일 삭제: $($OldBackup.Name)"
    }

    $OldLogs = Get-ChildItem $LogDir -Filter "migration_*.log" | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-30) }
    foreach ($OldLog in $OldLogs) {
        Remove-Item $OldLog.FullName -Force
        Write-Log "오래된 로그 파일 삭제: $($OldLog.Name)"
    }

    Write-Log "=== 마이그레이션 완료 ==="
    Write-Log "처리된 신규/수정 레코드: $NewOrUpdatedCount"
    Write-Log "백업 파일: $BackupFile"
    Write-Log "로그 파일: $LogFile"

} catch {
    Handle-Error $_.Exception.Message
} 