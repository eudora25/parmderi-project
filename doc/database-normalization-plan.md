# 📋 의료기관 데이터베이스 정규화 계획서

## 🎯 **목표**
`im_medical_institutions` 테이블의 78,236개 레코드를 정규화하여 다음 4개 테이블로 분리:
- `medical_institution` (기본 정보)
- `medical_institution_facility` (시설 정보)  
- `medical_institution_hospital` (병원 세부 정보)
- `medical_institution_specialty` (진료과목 정보)

---

## 📊 **현재 테이블 구조 분석**

### `im_medical_institutions` 테이블 (33개 컬럼)

| 컬럼명 | 타입 | 설명 | 분류 |
|--------|------|------|------|
| `id` | int(11) | 기본키 | 🔑 **ID** |
| `encrypted_code` | varchar(500) | 암호화된 기관 코드 | 🔑 **ID** |
| `institution_name` | varchar(200) | 기관명 | 🏥 **기본정보** |
| `category_code` | varchar(10) | 종별코드 | 🏥 **기본정보** |
| `category_name` | varchar(50) | 종별명 | 🏥 **기본정보** |
| `sido_code` | varchar(10) | 시도코드 | 🏥 **기본정보** |
| `sido_name` | varchar(50) | 시도명 | 🏥 **기본정보** |
| `sigungu_code` | varchar(10) | 시군구코드 | 🏥 **기본정보** |
| `sigungu_name` | varchar(50) | 시군구명 | 🏥 **기본정보** |
| `eupmyeondong` | varchar(100) | 읍면동 | 🏥 **기본정보** |
| `postal_code` | varchar(10) | 우편번호 | 🏥 **기본정보** |
| `address` | text | 주소 | 🏥 **기본정보** |
| `phone_number` | varchar(20) | 전화번호 | 🏥 **기본정보** |
| `homepage_url` | varchar(500) | 홈페이지 URL | 🏥 **기본정보** |
| `establishment_date` | date | 개설일 | 🏥 **기본정보** |
| `location_x` | decimal(15,10) | X 좌표 | 🏢 **시설정보** |
| `location_y` | decimal(15,10) | Y 좌표 | 🏢 **시설정보** |
| `total_doctors` | int(11) | 총 의사수 | 🏥 **병원정보** |
| `general_medicine_doctors` | int(11) | 의과일반의 | 👨‍⚕️ **진료과목** |
| `medicine_intern_doctors` | int(11) | 의과인턴 | 👨‍⚕️ **진료과목** |
| `medicine_resident_doctors` | int(11) | 의과레지던트 | 👨‍⚕️ **진료과목** |
| `medicine_specialist_doctors` | int(11) | 의과전문의 | 👨‍⚕️ **진료과목** |
| `dental_general_doctors` | int(11) | 치과일반의 | 👨‍⚕️ **진료과목** |
| `dental_intern_doctors` | int(11) | 치과인턴 | 👨‍⚕️ **진료과목** |
| `dental_resident_doctors` | int(11) | 치과레지던트 | 👨‍⚕️ **진료과목** |
| `dental_specialist_doctors` | int(11) | 치과전문의 | 👨‍⚕️ **진료과목** |
| `oriental_general_doctors` | int(11) | 한방일반의 | 👨‍⚕️ **진료과목** |
| `oriental_intern_doctors` | int(11) | 한방인턴 | 👨‍⚕️ **진료과목** |
| `oriental_resident_doctors` | int(11) | 한방레지던트 | 👨‍⚕️ **진료과목** |
| `oriental_specialist_doctors` | int(11) | 한방전문의 | 👨‍⚕️ **진료과목** |
| `midwives` | int(11) | 조산사 | 👨‍⚕️ **진료과목** |
| `created_at` | timestamp | 생성일시 | 🕐 **메타데이터** |
| `updated_at` | timestamp | 수정일시 | 🕐 **메타데이터** |

---

## 🎯 **정규화 설계**

### 1️⃣ `medical_institution` - 기본 정보 테이블
```sql
CREATE TABLE medical_institution (
    id INT PRIMARY KEY AUTO_INCREMENT,
    encrypted_code VARCHAR(500) UNIQUE NOT NULL,
    institution_name VARCHAR(200) NOT NULL,
    category_code VARCHAR(10),
    category_name VARCHAR(50),
    sido_code VARCHAR(10),
    sido_name VARCHAR(50),
    sigungu_code VARCHAR(10),
    sigungu_name VARCHAR(50),
    eupmyeondong VARCHAR(100),
    postal_code VARCHAR(10),
    address TEXT,
    phone_number VARCHAR(20),
    homepage_url VARCHAR(500),
    establishment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_institution_name (institution_name),
    INDEX idx_category_code (category_code),
    INDEX idx_sido_code (sido_code),
    INDEX idx_establishment_date (establishment_date)
);
```

### 2️⃣ `medical_institution_facility` - 시설 정보 테이블
```sql
CREATE TABLE medical_institution_facility (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT NOT NULL,
    location_x DECIMAL(15,10),
    location_y DECIMAL(15,10),
    facility_type ENUM('main', 'branch', 'clinic') DEFAULT 'main',
    building_info TEXT,
    parking_spaces INT DEFAULT 0,
    accessibility_features TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (institution_id) REFERENCES medical_institution(id) ON DELETE CASCADE,
    INDEX idx_location (location_x, location_y),
    INDEX idx_facility_type (facility_type)
);
```

### 3️⃣ `medical_institution_hospital` - 병원 세부 정보 테이블
```sql
CREATE TABLE medical_institution_hospital (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT NOT NULL,
    total_doctors INT DEFAULT 0,
    total_beds INT DEFAULT 0,
    emergency_room BOOLEAN DEFAULT FALSE,
    icu_beds INT DEFAULT 0,
    operating_rooms INT DEFAULT 0,
    hospital_grade ENUM('tertiary', 'secondary', 'primary', 'clinic') DEFAULT 'clinic',
    accreditation_status VARCHAR(50),
    specialties_offered TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (institution_id) REFERENCES medical_institution(id) ON DELETE CASCADE,
    INDEX idx_total_doctors (total_doctors),
    INDEX idx_hospital_grade (hospital_grade),
    INDEX idx_emergency_room (emergency_room)
);
```

### 4️⃣ `medical_institution_specialty` - 진료과목 정보 테이블
```sql
CREATE TABLE medical_institution_specialty (
    id INT PRIMARY KEY AUTO_INCREMENT,
    institution_id INT NOT NULL,
    specialty_type ENUM('general_medicine', 'medicine_intern', 'medicine_resident', 'medicine_specialist',
                        'dental_general', 'dental_intern', 'dental_resident', 'dental_specialist',
                        'oriental_general', 'oriental_intern', 'oriental_resident', 'oriental_specialist',
                        'midwives') NOT NULL,
    doctor_count INT DEFAULT 0,
    department_name VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (institution_id) REFERENCES medical_institution(id) ON DELETE CASCADE,
    UNIQUE KEY unique_institution_specialty (institution_id, specialty_type),
    INDEX idx_specialty_type (specialty_type),
    INDEX idx_doctor_count (doctor_count)
);
```

---

## 🔄 **데이터 마이그레이션 전략**

### **Phase 1: 테이블 생성**
1. 새로운 4개 테이블 생성
2. 외래키 관계 설정
3. 인덱스 생성

### **Phase 2: 데이터 이전**
1. `medical_institution` 테이블에 기본 정보 이전
2. `medical_institution_facility` 테이블에 시설 정보 이전
3. `medical_institution_hospital` 테이블에 병원 정보 이전
4. `medical_institution_specialty` 테이블에 진료과목 정보 이전

### **Phase 3: 검증 및 정리**
1. 데이터 무결성 검증
2. 성능 테스트
3. 기존 테이블 백업 후 제거

---

## 📈 **정규화 이점**

### **저장 공간 최적화**
- **중복 데이터 제거**: 반복되는 기관 정보 최소화
- **NULL 값 감소**: 각 테이블이 필요한 데이터만 저장

### **데이터 무결성 향상**
- **외래키 제약조건**: 데이터 일관성 보장
- **정규화된 구조**: 업데이트 이상 현상 방지

### **쿼리 성능 개선**
- **타겟팅된 인덱스**: 각 테이블별 최적화된 인덱스
- **조인 최적화**: 필요한 데이터만 조인

### **확장성 향상**
- **모듈화된 구조**: 각 영역별 독립적 확장 가능
- **유지보수 용이성**: 특정 기능별 테이블 관리

---

## ⚠️ **주의사항**

1. **대용량 데이터**: 78,236개 레코드의 마이그레이션 시간 고려
2. **외래키 제약조건**: 데이터 이전 순서 중요
3. **기존 애플리케이션**: CodeIgniter 모델 및 컨트롤러 수정 필요
4. **백업 필수**: 마이그레이션 전 전체 데이터베이스 백업

---

## 📅 **실행 계획**

1. **준비 단계** (30분)
   - 데이터베이스 백업
   - 새 테이블 생성 스크립트 실행

2. **마이그레이션 단계** (1-2시간)
   - 데이터 이전 스크립트 실행
   - 진행 상황 모니터링

3. **검증 단계** (30분)
   - 데이터 무결성 검증
   - 샘플 쿼리 테스트

4. **완료 단계** (15분)
   - 애플리케이션 연동 테스트
   - 성능 벤치마크 