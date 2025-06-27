# Parmderi Q&A 검색 시스템 개발 환경 설정

## 개요
이 문서는 **자연어 처리 기반 Q&A 검색 시스템** Parmderi 프로젝트의 개발 환경 설정과 구성 요소에 대해 설명합니다.

> **프로젝트 목표**: 규칙 기반에서 시작하여 AI 기반으로 발전하는 지능형 질의응답 시스템 개발  
> 📖 **상세 정보**: [프로젝트 개요 문서](./project-overview.md) 참조

## 시스템 구성

### 아키텍처
- **웹 서버**: Ubuntu 20.04 + Nginx + PHP 8.1
- **PHP 프레임워크**: CodeIgniter 3.x (Q&A 시스템 개발용)
- **데이터베이스**: MariaDB (Q&A 데이터, 검색 로그 저장)
- **컨테이너 관리**: Docker Compose
- **프로세스 관리**: Supervisord
- **개발 단계**: Phase 1 (규칙 기반) → Phase 3 (AI 기반)

## 컨테이너 구성

### 1. 웹 서버 컨테이너 (parmderi-workspace)

#### 기본 설정
- **Base Image**: Ubuntu 20.04
- **Container Name**: parmderi-workspace
- **포트 매핑**: 80:80, 443:443
- **볼륨 마운트**: 
  - `./source` → `/var/www/html`
  - `./images/ubuntu/conf/nginx.conf` → `/etc/nginx/conf.d/default.conf`

#### 설치된 소프트웨어
- **웹 서버**: Nginx
- **PHP**: 8.1 (PHP-FPM 포함)
- **PHP 확장모듈**:
  - php8.1-mysql (MariaDB 연결)
  - php8.1-gd (이미지 처리)
  - php8.1-curl (HTTP 클라이언트)
  - php8.1-mbstring (멀티바이트 문자열)
  - php8.1-xml (XML 처리)
  - php8.1-zip (압축 파일 처리)
  - php8.1-redis (Redis 캐시)
  - php8.1-memcache (Memcache 캐시)
  - 기타 개발 필수 모듈들
- **패키지 관리**: Composer
- **프로세스 관리**: Supervisord
- **유틸리티**: curl, vim

#### Nginx 설정
- **Document Root**: `/var/www/html`
- **기본 인덱스**: index.html, index.htm, index.php
- **PHP 처리**: PHP-FPM 8.1 (Unix 소켓 사용)
- **로그**: 
  - Error Log: `/var/log/nginx/error.log`
  - Access Log: `/var/log/nginx/access.log`

#### Supervisord 설정
- **Nginx**: 데몬 모드 비활성화로 실행
- **PHP-FPM**: 8.1 버전 사용, 노데몬 모드
- **자동 재시작**: 활성화
- **로그**: stdout/stderr로 출력

### 2. 데이터베이스 컨테이너 (parmderi-mariadb)

#### 기본 설정
- **Image**: mariadb:latest
- **Container Name**: parmderi-mariadb
- **포트 매핑**: 3306:3306
- **재시작 정책**: always

#### 데이터베이스 설정
- **기본 데이터베이스**: dev (Q&A 시스템용)
- **사용자**: dev
- **비밀번호**: dev2000
- **Root 비밀번호**: dev2000
- **데이터 저장소**: `./mariadb_data:/var/lib/mysql`
- **용도**: Q&A 데이터, 검색 로그, 키워드 사전 저장

## 개발 환경 시작하기

### 1. 전제 조건
- Docker 및 Docker Compose 설치
- Git (소스 코드 관리용)

### 2. 환경 시작
```bash
# 프로젝트 디렉토리로 이동
cd parmderi_project

# 컨테이너 빌드 및 시작
docker-compose up -d

# 로그 확인
docker-compose logs -f
```

### 3. 환경 중지
```bash
# 컨테이너 중지
docker-compose down

# 볼륨까지 함께 제거 (주의: 데이터 손실)
docker-compose down -v
```

## 개발 워크플로우

### 1. Q&A 시스템 개발
- **목표**: 규칙 기반 → AI 기반 자연어 처리 Q&A 시스템
- 모든 웹 소스 코드는 `./source` 디렉토리에 작성
- **CodeIgniter 3.x 프레임워크** 기반 개발
- 컨테이너 내부의 `/var/www/html`로 자동 마운트
- 실시간 코드 변경 반영

#### Phase 1 개발 목표 (규칙 기반)
- 키워드 매칭 엔진 개발
- 텍스트 유사도 계산
- 카테고리 분류 시스템
- Q&A 관리 패널

#### CodeIgniter 디렉토리 구조
```
source/
├── application/          # 메인 애플리케이션
│   ├── controllers/     # 컨트롤러 파일들
│   ├── models/          # 모델 파일들
│   ├── views/           # 뷰 파일들
│   ├── config/          # 설정 파일들
│   ├── libraries/       # 사용자 정의 라이브러리
│   └── helpers/         # 헬퍼 함수들
├── system/              # CodeIgniter 시스템 파일들
├── assets/              # CSS, JS, 이미지 등 정적 파일들
├── index.php            # 메인 진입점
└── composer.json        # Composer 의존성 관리
```

### 2. 데이터베이스 접근
- **호스트**: localhost (또는 127.0.0.1)
- **포트**: 3306
- **데이터베이스**: dev
- **사용자명**: dev
- **비밀번호**: dev2000

### 3. 웹 브라우저 접근
- **URL**: http://localhost
- **HTTPS**: https://localhost (설정에 따라)

## 디렉토리 구조

```
parmderi_project/
├── docker-compose.yml          # Docker Compose 설정
├── source/                     # 웹 소스 코드 (CodeIgniter 기반)
│   ├── application/           # CodeIgniter 애플리케이션
│   ├── system/                # CodeIgniter 시스템 파일
│   ├── assets/                # 정적 파일 (CSS, JS, 이미지)
│   ├── index.php              # 메인 진입점
│   ├── composer.json          # Composer 설정
│   └── readme.rst             # CodeIgniter 문서
├── images/ubuntu/              # Ubuntu 컨테이너 설정
│   ├── Dockerfile             # 컨테이너 빌드 설정
│   └── conf/                  # 설정 파일들
│       ├── nginx.conf         # Nginx 웹서버 설정
│       └── supervisord.conf   # 프로세스 관리 설정
├── mariadb_data/              # MariaDB 데이터 저장소
└── doc/                       # 프로젝트 문서
```

## 문제 해결

### 1. 컨테이너 접속
```bash
# 웹서버 컨테이너 접속
docker exec -it parmderi-workspace bash

# 데이터베이스 컨테이너 접속
docker exec -it parmderi-mariadb bash
```

### 2. 서비스 재시작
```bash
# 특정 서비스만 재시작
docker-compose restart ubuntu
docker-compose restart db

# 전체 재시작
docker-compose restart
```

### 3. 로그 확인
```bash
# 전체 로그
docker-compose logs

# 특정 서비스 로그
docker-compose logs ubuntu
docker-compose logs db
```

## 추가 정보

### PHP 설정
- PHP 8.1 사용
- **CodeIgniter 3.x 프레임워크** 설치됨
- Composer를 통한 패키지 관리 가능
- 주요 확장 모듈 사전 설치됨

### CodeIgniter 환경 설정
- **환경 모드**: development (기본값)
- **시스템 디렉토리**: `system/`
- **애플리케이션 디렉토리**: `application/`
- **PHP 최소 요구 버전**: 5.3.7 이상 (현재 8.1 사용)
- **라이센스**: MIT License

### 보안 고려사항
- 개발 환경용 설정이므로 프로덕션 사용 시 보안 강화 필요
- 기본 비밀번호 변경 권장
- HTTPS 인증서 설정 (필요시)

### 성능 최적화
- PHP-FPM 설정 튜닝 가능
- Nginx 설정 최적화 가능
- MariaDB 설정 튜닝 가능

---
*문서 최종 업데이트: 2024년* 