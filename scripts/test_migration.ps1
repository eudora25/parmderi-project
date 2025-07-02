# 테스트용 의료기관 데이터 마이그레이션 스크립트

# 설정 변수
$DockerContainer = "parmderi_mariadb"
$DbUser = "devh"
$DbPass = "A77ila@"
$DbName = "htest"

Write-Host "=== Medical Data Migration Test Started ===" -ForegroundColor Green

try {
    # 1. Docker 컨테이너 상태 확인
    Write-Host "Checking Docker container status..." -ForegroundColor Yellow
    $DockerPs = docker ps --format "table {{.Names}}" | Select-String $DockerContainer
    if (-not $DockerPs) {
        Write-Host "ERROR: Docker container $DockerContainer is not running" -ForegroundColor Red
        exit 1
    }
    Write-Host "✅ Docker container is running" -ForegroundColor Green

    # 2. 데이터베이스 연결 확인
    Write-Host "Testing database connection..." -ForegroundColor Yellow
    $TestQuery = docker exec $DockerContainer mariadb -u"$DbUser" -p"$DbPass" "$DbName" -e "SELECT 1;"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Database connection failed with exit code: $LASTEXITCODE" -ForegroundColor Red
        Write-Host "Query result: $TestQuery" -ForegroundColor Red
        exit 1
    }
    Write-Host "✅ Database connection successful" -ForegroundColor Green

    # 3. 원본 테이블 확인
    Write-Host "Checking source table..." -ForegroundColor Yellow
    $TableCheck = docker exec $DockerContainer mariadb -u"$DbUser" -p"$DbPass" "$DbName" -e "SHOW TABLES LIKE 'im_medical_institutions';"
    if (-not $TableCheck) {
        Write-Host "ERROR: Source table im_medical_institutions not found" -ForegroundColor Red
        exit 1
    }
    Write-Host "✅ Source table exists" -ForegroundColor Green

    # 4. 데이터 수 확인
    Write-Host "Counting source records..." -ForegroundColor Yellow
    $CountResult = docker exec $DockerContainer mariadb -u"$DbUser" -p"$DbPass" "$DbName" -e "SELECT COUNT(*) as count FROM im_medical_institutions;"
    $RecordCount = ($CountResult | Select-Object -Skip 1).Trim()
    Write-Host "Source table record count: $RecordCount" -ForegroundColor Cyan

    # 5. 정규화된 테이블 확인
    Write-Host "Checking normalized tables..." -ForegroundColor Yellow
    $NormalizedCheck = docker exec $DockerContainer mariadb -u"$DbUser" -p"$DbPass" "$DbName" -e "SHOW TABLES LIKE 'medical_institution';"
    if ($NormalizedCheck) {
        Write-Host "✅ Normalized tables exist" -ForegroundColor Green
        
        # 정규화된 테이블 레코드 수 확인
        $NormalizedCountResult = docker exec $DockerContainer mariadb -u"$DbUser" -p"$DbPass" "$DbName" -e "SELECT COUNT(*) as count FROM medical_institution;"
        $NormalizedCount = ($NormalizedCountResult | Select-Object -Skip 1).Trim()
        Write-Host "Normalized table record count: $NormalizedCount" -ForegroundColor Cyan
    } else {
        Write-Host "⚠️  Normalized tables not found - need to create" -ForegroundColor Yellow
    }

    Write-Host "=== Test Completed Successfully ===" -ForegroundColor Green

} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
} 