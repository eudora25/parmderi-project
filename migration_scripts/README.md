# 🗄️ 의료기관 데이터베이스 정규화 마이그레이션 가이드

## 📋 **개요**

`im_medical_institutions` 테이블의 78,236개 레코드를 정규화하여 다음 4개 테이블로 분리하는 마이그레이션 스크립트입니다.

### **대상 테이블**
- `medical_institution` - 기본 정보
- `medical_institution_facility` - 시설 정보  
- `medical_institution_hospital` - 병원 세부 정보
- `medical_institution_specialty` - 진료과목 정보
- `medical_institution_contact` - 연락처 정보 (추가)

---

## 🚀 **실행 순서**

### **1단계: 테이블 생성**
```bash
# Docker 컨테이너에서 실행
docker exec parmderi_mariadb mariadb -udevh -pA77ila@ htest < migration_scripts/01_create_normalized_tables.sql
```

**실행 내용:**
- ✅ 5개의 정규화된 테이블 생성
- ✅ 외래키 제약조건 설정
- ✅ 인덱스 최적화
- ✅ 통합 뷰(`v_medical_institution_full`) 생성

### **2단계: 데이터 마이그레이션**
```bash
# Docker 컨테이너에서 실행 (시간이 오래 걸릴 수 있음)
docker exec parmderi_mariadb mariadb -udevh -pA77ila@ htest < migration_scripts/02_migrate_data.sql
```

**실행 내용:**
- ✅ 기본 정보 78,236개 레코드 이전
- ✅ 시설 정보 이전 (좌표 데이터 있는 경우)
- ✅ 병원 정보 이전 (의사 수, 등급 분류)
- ✅ 진료과목별 의사 수 이전 (13개 카테고리)
- ✅ 연락처 정보 이전
- ✅ 트랜잭션 기반 안전한 마이그레이션

### **3단계: 검증 및 최적화**
```bash
# Docker 컨테이너에서 실행
docker exec parmderi_mariadb mariadb -udevh -pA77ila@ htest < migration_scripts/03_validate_and_cleanup.sql
```

**실행 내용:**
- ✅ 데이터 무결성 검증
- ✅ 외래키 관계 확인
- ✅ 성능 테스트
- ✅ 샘플 쿼리 실행
- ✅ 완료 보고서 생성

---

## ⏱️ **예상 실행 시간**

| 단계 | 예상 시간 | 설명 |
|------|-----------|------|
| 1단계 | 5-10분 | 테이블 생성 및 인덱스 설정 |
| 2단계 | 30-60분 | 78,236개 레코드 마이그레이션 |
| 3단계 | 10-15분 | 검증 및 성능 테스트 |
| **총합** | **45-85분** | 데이터 양에 따라 변동 |

---

## 📊 **마이그레이션 결과 예상**

### **테이블별 예상 레코드 수**
```
medical_institution:          78,236개 (기본 정보)
medical_institution_facility: 50,000개 (좌표 있는 기관)
medical_institution_hospital: 70,000개 (의사 정보 있는 기관)
medical_institution_specialty: 300,000개 (진료과목별 데이터)
medical_institution_contact: 75,000개 (전화번호 있는 기관)
```

### **정규화 이점**
- 🎯 **중복 데이터 제거**: 약 30% 저장공간 절약
- 🔍 **쿼리 성능 향상**: 인덱스 최적화로 검색 속도 증가
- 🛡️ **데이터 무결성**: 외래키 제약조건으로 일관성 보장
- 📈 **확장성**: 모듈화된 구조로 유지보수 용이

---

## ⚠️ **주의사항**

### **실행 전 필수 작업**
1. **데이터베이스 백업**
   ```bash
   docker exec parmderi_mariadb mysqldump -udevh -pA77ila@ htest > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **충분한 디스크 공간 확보**
   - 최소 2GB 여유 공간 필요
   - 마이그레이션 중 임시 데이터 생성

3. **다른 작업 중단**
   - 웹 애플리케이션 일시 중단 권장
   - 동시 데이터 수정 방지

### **문제 발생 시 대처**
```bash
# 마이그레이션 중단 시 롤백
docker exec parmderi_mariadb mariadb -udevh -pA77ila@ htest -e "ROLLBACK;"

# 백업에서 복구
docker exec -i parmderi_mariadb mariadb -udevh -pA77ila@ htest < backup_file.sql
```

---

## 📝 **실행 로그 확인**

### **진행 상황 모니터링**
```bash
# 별도 터미널에서 실시간 모니터링
docker exec parmderi_mariadb mariadb -udevh -pA77ila@ htest -e "SHOW PROCESSLIST;"

# 테이블 크기 확인
docker exec parmderi_mariadb mariadb -udevh -pA77ila@ htest -e "
SELECT table_name, table_rows 
FROM information_schema.tables 
WHERE table_schema = 'htest' 
AND table_name LIKE 'medical_%';"
```

### **완료 확인**
```bash
# 최종 레코드 수 확인
docker exec parmderi_mariadb mariadb -udevh -pA77ila@ htest -e "
SELECT 'medical_institution' as table_name, COUNT(*) as count FROM medical_institution
UNION ALL
SELECT 'medical_institution_facility', COUNT(*) FROM medical_institution_facility
UNION ALL  
SELECT 'medical_institution_hospital', COUNT(*) FROM medical_institution_hospital
UNION ALL
SELECT 'medical_institution_specialty', COUNT(*) FROM medical_institution_specialty;"
```

---

## 🔄 **애플리케이션 업데이트**

### **CodeIgniter 모델 수정 필요**
마이그레이션 완료 후 다음 파일들을 업데이트해야 합니다:

1. **`Medical_data_model.php`**
   - 새로운 테이블 구조에 맞게 쿼리 수정
   - 조인 쿼리 추가

2. **`Excel_upload.php`**
   - 업로드 로직을 정규화된 테이블에 맞게 수정

3. **뷰 파일들**
   - 데이터 표시 로직 업데이트

### **API 엔드포인트 업데이트**
- REST API 응답 구조 변경
- 검색 기능 개선
- 통계 기능 업데이트

---

## 🎯 **성공 기준**

### **마이그레이션 성공 확인**
✅ 모든 78,236개 레코드가 `medical_institution`에 이전됨  
✅ 외래키 무결성 검증 통과  
✅ 기존 데이터와 정규화된 데이터 일치 확인  
✅ 샘플 쿼리 정상 실행  
✅ 성능 테스트 통과  

### **롤백 시나리오**
마이그레이션 실패 시 자동으로 트랜잭션이 롤백되며, 기존 `im_medical_institutions` 테이블은 그대로 유지됩니다.

---

## 📞 **지원**

문제 발생 시:
1. 실행 로그 확인
2. 데이터베이스 상태 점검
3. 백업에서 복구
4. 개발팀 문의

**마이그레이션 성공을 위해 단계별로 신중하게 진행하세요!** 🚀 