# 환경 설정 가이드

## 데이터베이스 설정

### 1. 환경 변수 파일 생성

프로젝트 루트 디렉토리에 `.env` 파일을 생성하고 다음 내용을 추가하세요:

```bash
# Database Configuration
DB_HOST=db
DB_USERNAME=devh
DB_PASSWORD=A77ila@
DB_DATABASE=htest

# Application Configuration
APP_ENV=development
APP_DEBUG=true
```

### 2. 데이터베이스 설정 파일 복사

```bash
cp source/application/config/database.php.example source/application/config/database.php
```

### 3. 환경 변수 설정

- `DB_HOST`: 데이터베이스 호스트 (기본값: db)
- `DB_USERNAME`: 데이터베이스 사용자명 (기본값: devh)
- `DB_PASSWORD`: 데이터베이스 비밀번호 (기본값: A77ila@)
- `DB_DATABASE`: 데이터베이스명 (기본값: htest)

## 보안 주의사항

1. **`.env` 파일은 절대 Git에 커밋하지 마세요**
2. **실제 데이터베이스 접속 정보는 안전하게 보관하세요**
3. **프로덕션 환경에서는 더 강력한 비밀번호를 사용하세요**

## 환경별 설정

### 개발 환경
```bash
APP_ENV=development
APP_DEBUG=true
```

### 프로덕션 환경
```bash
APP_ENV=production
APP_DEBUG=false
```

## 문제 해결

### 환경 변수가 로드되지 않는 경우
1. `.env` 파일이 프로젝트 루트에 있는지 확인
2. 파일 권한이 올바른지 확인
3. 파일 형식이 UTF-8인지 확인

### 데이터베이스 연결 오류
1. 환경 변수가 올바르게 설정되었는지 확인
2. 데이터베이스 서버가 실행 중인지 확인
3. 방화벽 설정을 확인 