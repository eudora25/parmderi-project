# medical_products 엑셀 업로드(process_upload) 에러 가이드

이 문서는 http://localhost/medical_products/process_upload 경로에서 발생할 수 있는 대표적인 에러와 원인, 해결 방법, 실제 콘솔/로그 예시를 정리한 자료입니다.

---

## 1. 대표 에러 유형 및 원인

### 1) DB 컬럼 불일치 (Unknown column ... in 'INSERT INTO')
- **원인:** 엑셀 데이터의 컬럼명과 DB 테이블 컬럼명이 다를 때 발생
- **예시 로그:**
  ```
  ERROR - ... --> Query error: Unknown column 'cso_item' in 'INSERT INTO' - Invalid query: INSERT INTO `medical_products` ...
  ```
- **해결:**
  - DB에 해당 컬럼을 추가하거나, 코드/엑셀에서 컬럼을 제외

### 2) 필수 데이터 부족/엑셀 구조 불일치
- **원인:** 엑셀 시트명, 헤더, 데이터 행 수 등이 코드에서 기대하는 구조와 다를 때
- **예시 메시지:**
  - "엑셀 데이터가 충분하지 않습니다."
  - "raw_data 시트에 충분한 데이터가 없습니다."
- **해결:**
  - 엑셀 시트명/헤더/데이터 구조를 업로드 양식에 맞게 수정

### 3) 파일 파싱 실패/포맷 오류
- **원인:** 손상된 파일, 지원하지 않는 확장자(xls 등), SimpleXLSX 파싱 실패
- **예시 로그:**
  ```
  ERROR - ... --> 엑셀 파싱 실패: ...
  ```
- **해결:**
  - .xlsx 파일로 저장, 파일 손상 여부 확인

### 4) 서버 환경/설정 문제
- **원인:** 업로드 용량 초과, 메모리 부족, 권한 문제 등
- **예시:**
  - "Allowed memory size exhausted ..."
  - "The uploaded file exceeds the upload_max_filesize directive ..."
- **해결:**
  - php.ini에서 upload_max_filesize, post_max_size, memory_limit 등 확인/증설

---

## 2. 실제 콘솔/로그 예시

- **DB 컬럼 불일치:**
  ```
  ERROR - 2025-07-01 08:22:20 --> Query error: Unknown column 'cso_item' in 'INSERT INTO' - Invalid query: INSERT INTO `medical_products` ...
  ```
- **엑셀 구조 오류:**
  ```
  {"success":false,"message":"엑셀 데이터가 충분하지 않습니다.", ...}
  ```
- **파싱 실패:**
  ```
  ERROR - ... --> 엑셀 파싱 실패: SimpleXLSX::parseError()
  ```
- **서버 환경 오류:**
  ```
  Fatal error: Allowed memory size of ... bytes exhausted ...
  ```

---

## 3. 에러 발생 시 점검/조치 체크리스트

- [ ] 엑셀 헤더/컬럼명/순서가 업로드 양식과 일치하는지 확인
- [ ] DB 테이블 구조와 컬럼명이 일치하는지 확인
- [ ] 파일이 .xlsx 형식이고 손상되지 않았는지 확인
- [ ] 업로드 용량/메모리 등 서버 환경 설정 확인
- [ ] 에러 메시지/로그를 복사해 개발팀에 문의

---

## 4. 문의/지원

- 에러 메시지, 업로드 파일, 로그 등과 함께 개발팀에 문의하시면 신속히 지원해드립니다. 