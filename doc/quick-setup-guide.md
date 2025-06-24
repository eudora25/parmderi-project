# Q&A 검색 시스템 빠른 설정 가이드

## 🚀 5분 만에 Q&A 시스템 개발 환경 시작하기

> **프로젝트 목표**: 자연어 처리 기반 Q&A 검색 시스템 (규칙 기반 → AI 기반)

### 1. 사전 준비
```bash
# Docker와 Docker Compose가 설치되어 있는지 확인
docker --version
docker-compose --version
```

### 2. 개발 환경 시작
```bash
# 프로젝트 디렉토리로 이동
cd parmderi_project

# 백그라운드에서 모든 서비스 시작
docker-compose up -d

# 상태 확인
docker-compose ps
```

### 3. 접속 정보

#### 🌐 웹 브라우저
- **URL**: http://localhost
- **소스 코드 위치**: `./source/` 폴더

#### 🗄️ 데이터베이스 접속 (Q&A 데이터)
- **호스트**: localhost
- **포트**: 3306
- **DB명**: dev (Q&A 시스템용)
- **사용자**: dev
- **비밀번호**: dev2000
- **용도**: Q&A 데이터, 키워드, 검색 로그 저장

### 4. 자주 사용하는 명령어

```bash
# 로그 실시간 확인
docker-compose logs -f

# 컨테이너 재시작
docker-compose restart

# 개발 환경 종료
docker-compose down

# 웹서버 컨테이너 접속
docker exec -it parmderi-workspace bash

# DB 컨테이너 접속
docker exec -it parmderi-mariadb bash
```

### 5. 개발 시작하기

1. `./source/application/` 폴더에서 **CodeIgniter** 기반 개발
   - `controllers/`: 컨트롤러 파일 작성
   - `models/`: 데이터베이스 모델 작성  
   - `views/`: HTML 템플릿 작성
   - `config/`: 설정 파일 수정
2. 웹 브라우저에서 http://localhost 접속
3. 코드 변경 사항이 실시간으로 반영됨

#### CodeIgniter 개발 참고
- **환경**: development 모드로 설정됨
- **URL 구조**: `http://localhost/컨트롤러/메소드`
- **데이터베이스 설정**: `application/config/database.php`에서 설정

### ⚠️ 주의사항
- 데이터베이스 데이터는 `./mariadb_data/`에 영구 저장됨
- `docker-compose down -v` 명령어는 Q&A 데이터를 삭제하므로 주의
- 개발용 설정이므로 프로덕션 사용 금지
- **Phase 1**: 규칙 기반 개발 → **Phase 3**: AI 기반으로 확장 예정

### 📖 추가 문서
- 📋 [프로젝트 전체 개요](./project-overview.md) - 개발 로드맵 및 기술 계획
- 🔧 [상세 환경 설정](./development-environment.md) - 전체 시스템 구성
- 🎯 [CodeIgniter 가이드](./codeigniter-guide.md) - 프레임워크 개발 방법 