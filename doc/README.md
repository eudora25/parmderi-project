# Parmderi Q&A 검색 시스템 문서

이 폴더에는 **의료/의약품 전문 자연어 처리 기반 Q&A 검색 시스템** Parmderi 프로젝트의 개발 환경 설정과 관련된 문서들이 포함되어 있습니다.

> **🎯 프로젝트 비전**: 의료 데이터 기반 규칙에서 시작하여 AI 기반으로 발전하는 의료 전문 질의응답 시스템
> **💊 특화 영역**: 의약품 정보, 의료기관 정보, 의료 제품 및 수가 정보

## 📋 문서 목록

### 🔧 [개발 환경 설정 가이드](./development-environment.md)
프로젝트의 전체 개발 환경 구성에 대한 상세한 설명
- 시스템 아키텍처
- Docker 컨테이너 구성
- 설정 파일 설명
- 문제 해결 방법

### ⚡ [빠른 설정 가이드](./quick-setup-guide.md)
개발 환경을 빠르게 시작하기 위한 간단한 가이드
- 5분 만에 환경 구축
- 핵심 명령어
- 접속 정보
- 주의사항

### 🎯 [CodeIgniter 개발 가이드](./codeigniter-guide.md)
CodeIgniter 3.x 프레임워크 기반 개발 가이드
- MVC 패턴 개발 방법
- 데이터베이스 연동
- URL 라우팅
- 실제 개발 예제

### 📋 [프로젝트 개요](./project-overview.md)
Q&A 검색 시스템 프로젝트의 상세 계획 및 로드맵
- 프로젝트 목표 및 비전
- 단계별 개발 로드맵
- 기술 스택 계획
- 데이터베이스 설계

### 🗄️ [데이터베이스 스키마](./database-schema.sql)
실행 가능한 데이터베이스 생성 스크립트
- 전체 테이블 구조 (상세 comment 포함)
- 인덱스 및 외래키 설정
- 초기 데이터 삽입
- 성능 최적화 설정

## 🏗️ 현재 개발 환경 구성

### 웹 서버 (Q&A 시스템)
- **OS**: Ubuntu 20.04
- **웹서버**: Nginx
- **언어**: PHP 8.1
- **프레임워크**: CodeIgniter 3.x
- **컨테이너**: parmderi-workspace
- **개발 목표**: 규칙 기반 → AI 기반 Q&A 시스템

### 데이터베이스 (의료 통합 데이터)
- **DBMS**: MariaDB (latest)
- **컨테이너**: parmderi-mariadb  
- **기본 DB**: dev
- **총 테이블**: 21개 (의료 특화)
- **용도**: 
  - 의약품 정보 (drug, active_ingredient, manufacturer)
  - 의료기관 정보 (medical_institution, specialty, facility)
  - 제품/수가 정보 (product, commission, rate_sheet)
  - Q&A 데이터, 키워드 사전, 검색 로그

### 접속 정보
- **웹**: http://localhost
- **DB 포트**: 3306
- **DB 사용자**: dev / dev2000

## 📁 프로젝트 구조
```
parmderi_project/
├── docker-compose.yml     # Docker 설정
├── source/               # 웹 소스 코드 (의료 특화 CodeIgniter)
│   ├── application/     # CodeIgniter 앱 코드
│   │   ├── models/      # 의료 데이터 모델 (21개 테이블)
│   │   ├── controllers/ # 의료 Q&A 컨트롤러
│   │   └── views/       # 의료 전문 UI
│   ├── system/          # CodeIgniter 시스템
│   └── assets/          # 정적 파일
├── images/ubuntu/        # 컨테이너 설정
├── mariadb_data/         # 의료 통합 DB 데이터 (21 테이블)
│   ├── drug*            # 의약품 관련 데이터
│   ├── medical_*        # 의료기관 관련 데이터
│   └── product*         # 제품/수가 관련 데이터
└── doc/                  # 이 문서들
```

---
**도움이 필요하시면 해당 문서를 참조하시거나 개발팀에 문의하세요.** 