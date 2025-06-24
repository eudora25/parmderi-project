# 🏥 Parmderi Q&A 검색 시스템

의료/의약품 전문 자연어 처리 기반 Q&A 검색 시스템

## 📋 프로젝트 개요

**Parmderi**는 의료진과 환자들이 의료기관, 의약품, 치료 정보에 대해 자연어로 질문하고 정확한 답변을 받을 수 있는 전문 검색 시스템입니다.

### 🎯 주요 기능

- **🔍 자연어 질의응답**: "서울대병원 위치는?" 같은 자연스러운 질문 처리
- **🏥 병원 검색**: 전국 의료기관 정보 검색 및 상세 정보 제공
- **💊 의약품 정보**: EDI 코드, 성분, 제조사별 의약품 검색
- **📊 엑셀 업로드**: 대용량 의료 데이터 일괄 업로드 및 검증
- **🤖 질문 유형 분석**: AI 기반 질문 의도 파악 및 최적 답변 제공

### 🏗️ 시스템 아키텍처

```
Frontend (Bootstrap + jQuery)
       ↓
CodeIgniter 3.x Framework
       ↓
MariaDB (21개 전문 테이블)
       ↓
Docker Container Environment
```

## 🚀 빠른 시작

### 전제 조건
- Docker & Docker Compose
- Git

### 설치 및 실행

```bash
# 1. 저장소 클론
git clone [REPOSITORY_URL]
cd parmderi_project

# 2. Docker 환경 시작
docker-compose up -d

# 3. 브라우저에서 접속
http://localhost
```

### 주요 엔드포인트

- **메인 페이지**: `http://localhost`
- **병원 검색**: `http://localhost/hospital_search`
- **엑셀 업로드**: `http://localhost/excel`
- **API 테스트**: `http://localhost/api_test`

## 📁 프로젝트 구조

```
parmderi_project/
├── doc/                     # 📚 프로젝트 문서
├── source/                  # 🔧 CodeIgniter 소스코드
│   ├── application/
│   │   ├── controllers/     # 🎮 컨트롤러
│   │   ├── models/         # 📊 데이터 모델
│   │   └── views/          # 🖼️ 뷰 템플릿
│   └── system/             # CodeIgniter 프레임워크
├── docker-compose.yml       # 🐳 Docker 설정
└── README.md               # 📖 이 파일
```

## 🛠️ 기술 스택

### Backend
- **Framework**: CodeIgniter 3.x
- **Language**: PHP 8.1
- **Database**: MariaDB 10.6
- **Web Server**: Nginx

### Frontend
- **CSS**: Bootstrap 5
- **JavaScript**: jQuery
- **UI Components**: 반응형 모바일 지원

### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Environment**: Ubuntu 20.04

## 📊 데이터베이스 스키마

### 핵심 테이블 (21개)
- `medical_institution`: 의료기관 정보
- `drug`: 의약품 마스터 데이터
- `product`: 제품 정보
- `question_types`: 질문 유형 분류
- `upload_logs`: 업로드 이력 관리

상세한 스키마는 [`doc/database-schema.sql`](doc/database-schema.sql) 참조

## 🔄 개발 로드맵

### Phase 1 (현재) - 규칙 기반 시스템 ✅
- [x] 키워드 매칭 검색 엔진
- [x] 의료기관 검색 시스템
- [x] 엑셀 데이터 업로드
- [x] 기본 질의응답 처리

### Phase 2 (계획) - 하이브리드 시스템
- [ ] 머신러닝 기반 질문 분류
- [ ] 텍스트 유사도 개선
- [ ] 검색 결과 랭킹 알고리즘

### Phase 3 (미래) - AI 기반 시스템
- [ ] 자연어 이해 (NLU) 엔진
- [ ] 대화형 챗봇 인터페이스
- [ ] 개인화 추천 시스템

## 🧪 테스트

### API 테스트
```bash
# 병원 검색 테스트
curl -X POST "http://localhost/hospital_search/search" \
     -d "query=서울대병원" \
     -H "Content-Type: application/x-www-form-urlencoded"
```

### 샘플 데이터
- [`sample_hospital_data.csv`](sample_hospital_data.csv): 병원 정보 샘플
- 테스트용 엑셀 파일들 포함

## 📖 문서

상세한 문서는 [`doc/`](doc/) 폴더에서 확인하세요:

- [📋 프로젝트 개요](doc/project-overview.md)
- [⚡ 빠른 설정 가이드](doc/quick-setup-guide.md)
- [🔧 개발 환경 설정](doc/development-environment.md)
- [📚 CodeIgniter 가이드](doc/codeigniter-guide.md)
- [🗄️ 데이터베이스 정규화 계획](doc/database-normalization-plan.md)

## 🤝 기여하기

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 라이선스

이 프로젝트는 MIT 라이선스 하에 배포됩니다. 자세한 내용은 `LICENSE` 파일을 참조하세요.

## 📞 연락처

프로젝트 관련 문의: [GitHub Issues](../../issues)

---

<div align="center">

**🏥 건강한 정보 검색의 새로운 패러다임 🏥**

Made with ❤️ for better healthcare information access

</div> 