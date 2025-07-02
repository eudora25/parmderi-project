<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Excel_upload extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'file'));
        $this->load->library('upload');
        $this->load->database();
        $this->load->model('Medical_data_model');
    }

    // 업로드 페이지 표시
    public function index()
    {
        $data['title'] = '병원정보 엑셀 업로드';
        $this->load->view('excel/upload_form', $data);
    }

    // 엑셀 파일 미리보기
    public function preview()
    {
        // AJAX 체크 제거 (테스트를 위해)
        // if (!$this->input->is_ajax_request()) {
        //     show_404();
        // }

        $response = array('success' => false, 'error_message' => '');

        try {
            // 파일 업로드 설정
            $config['upload_path'] = './uploads/excel/';
            $config['allowed_types'] = 'xlsx|xls|csv';
            $config['max_size'] = 102400; // 100MB (KB 단위)
            $config['encrypt_name'] = TRUE;

            // 업로드 디렉토리가 없으면 생성
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('excel_file')) {
                $response['error_message'] = $this->upload->display_errors('', '');
                echo json_encode($response);
                return;
            }

            $file_data = $this->upload->data();
            $file_path = $file_data['full_path'];

            // 엑셀 파일 읽기
            $excel_data = $this->read_excel_file($file_path);
            
            if ($excel_data === false) {
                $response['error_message'] = '엑셀 파일을 읽을 수 없습니다.';
                unlink($file_path); // 임시 파일 삭제
                echo json_encode($response);
                return;
            }

            // 성공 응답
            $response['success'] = true;
            $response['data'] = array_slice($excel_data, 0, 11); // 헤더 + 최대 10행
            $response['total_rows'] = count($excel_data) - 1; // 헤더 제외
            $response['total_columns'] = count($excel_data[0]);
            $response['file_path'] = $file_path;

            // 임시 파일 삭제 (미리보기용이므로)
            unlink($file_path);

        } catch (Exception $e) {
            $response['error_message'] = '파일 처리 중 오류가 발생했습니다: ' . $e->getMessage();
        }

        echo json_encode($response);
    }

    // 엑셀 파일 업로드 및 처리
    public function upload()
    {
        // 시간 제한 해제 및 메모리 최적화 (큰 파일 처리를 위해)
        set_time_limit(0);
        ini_set('memory_limit', '2G');
        
        $response = array(
            'success' => false,
            'message' => '',
            'error_message' => '',
            'inserted_count' => 0,
            'updated_count' => 0,
            'error_count' => 0
        );

        try {
            // 파일 업로드 설정
            $config['upload_path'] = './uploads/excel/';
            $config['allowed_types'] = 'xlsx|xls|csv';
            $config['max_size'] = 102400; // 100MB (KB 단위)
            $config['encrypt_name'] = TRUE;

            // 업로드 디렉토리가 없으면 생성
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('excel_file')) {
                $response['error_message'] = $this->upload->display_errors('', '');
                $this->load->view('excel/upload_result', $response);
                return;
            }

            $file_data = $this->upload->data();
            $file_path = $file_data['full_path'];

            // 엑셀 파일 읽기
            $excel_data = $this->read_excel_file($file_path);
            
            if ($excel_data === false) {
                $response['error_message'] = '엑셀 파일을 읽을 수 없습니다.';
                unlink($file_path);
                $this->load->view('excel/upload_result', $response);
                return;
            }

            // 데이터 처리 - im_medical_institutions 테이블에 저장
            $result = $this->process_excel_data($excel_data);
            
            $response['success'] = true;
            $response['message'] = 'im_medical_institutions 테이블에 성공적으로 저장되었습니다.';
            $response['inserted_count'] = $result['inserted'];
            $response['updated_count'] = $result['updated'];
            $response['error_count'] = $result['errors'];
            $response['total_rows'] = count($excel_data) - 1; // 헤더 제외
            $response['result'] = $result; // upload_log_id 포함

            // 임시 파일 삭제
            unlink($file_path);

        } catch (Exception $e) {
            $response['error_message'] = '파일 처리 중 오류가 발생했습니다: ' . $e->getMessage();
            if (isset($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }
        }

        $this->load->view('excel/upload_result', $response);
    }

    // 엑셀 파일 읽기 (메모리 효율적 방법)
    private function read_excel_file($file_path)
    {
        try {
            $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

            if ($file_extension === 'csv') {
                return $this->read_csv_file($file_path);
            } else {
                // 메모리 사용량 최적화를 위한 설정
                ini_set('memory_limit', '2G'); // 임시로 더 많은 메모리 할당
                
                // SimpleXLSX 라이브러리 사용
                require_once APPPATH . 'third_party/SimpleXLSX/SimpleXLSX.php';
                
                // 네임스페이스를 포함한 클래스 사용
                if ($xlsx = \Shuchkin\SimpleXLSX::parse($file_path)) {
                    $rows = $xlsx->rows();
                    
                    // 메모리 정리
                    unset($xlsx);
                    
                    return $rows;
                } else {
                    log_message('error', 'SimpleXLSX 파싱 오류: ' . \Shuchkin\SimpleXLSX::parseError());
                    return false;
                }
            }
        } catch (Exception $e) {
            log_message('error', '엑셀 파일 읽기 오류: ' . $e->getMessage());
            
            // 메모리 부족 에러인 경우 특별한 처리
            if (strpos($e->getMessage(), 'memory') !== false) {
                log_message('error', '메모리 부족 에러 발생. 파일 크기를 줄이거나 분할해서 업로드해주세요.');
                throw new Exception('파일이 너무 큽니다. 파일 크기를 줄이거나 분할해서 업로드해주세요.');
            }
            
            return false;
        }
    }

    // CSV 파일 읽기
    private function read_csv_file($file_path)
    {
        $data = array();
        
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data[] = $row;
            }
            fclose($handle);
        }
        
        return $data;
    }

    // 엑셀 데이터 미리보기 처리 (DB 저장 없이)
    private function process_excel_data_preview($excel_data)
    {
        $processed_rows = array();
        
        if (empty($excel_data) || count($excel_data) < 2) {
            return $processed_rows;
        }

        // 헤더 행 가져오기
        $headers = array_map('trim', $excel_data[0]);
        
        // 컬럼 매핑 (다양한 헤더명 지원)
        $column_mapping = array(
            // 기관명 관련
            '기관명' => 'institution_name',
            '병원명' => 'institution_name',
            '의료기관명' => 'institution_name',
            '기관이름' => 'institution_name',
            '요양기관명' => 'institution_name',
            '의료기관 이름' => 'institution_name',
            '기관 이름' => 'institution_name',
            'institution_name' => 'institution_name',
            'name' => 'institution_name',
            'hospital_name' => 'institution_name',
            // 주소 관련
            '주소' => 'address',
            '소재지' => 'address',
            '위치' => 'address',
            '의료기관 주소' => 'address',
            '기관주소' => 'address',
            '소재지 주소' => 'address',
            'address' => 'address',
            'location' => 'address',
            // 전화번호 관련
            '전화번호' => 'phone',
            '전화' => 'phone',
            '연락처' => 'phone',
            '의료기관 전화번호' => 'phone',
            'TEL' => 'phone',
            'Tel' => 'phone',
            'phone' => 'phone',
            'telephone' => 'phone',
            'phone_number' => 'phone',
            // 기타 필드들
            '기관종류' => 'institution_type',
            '기관구분' => 'institution_type',
            '종별' => 'institution_type',
            '의료기관종별' => 'institution_type',
            '구분' => 'institution_type',
            '분류' => 'institution_type',
            '대표자명' => 'representative_name',
            '대표자' => 'representative_name',
            '원장' => 'representative_name',
            '병원장' => 'representative_name',
            '사업자번호' => 'business_number',
            '사업자등록번호' => 'business_number',
            '등록번호' => 'business_number',
            '우편번호' => 'postal_code',
            '우편' => 'postal_code',
            'ZIP' => 'postal_code',
            '팩스' => 'fax',
            'FAX' => 'fax',
            'Fax' => 'fax',
            '홈페이지' => 'website',
            '병원홈페이지' => 'website',
            '웹사이트' => 'website',
            'URL' => 'website',
            'Website' => 'website',
            '개원일' => 'opening_date',
            '개설일' => 'opening_date',
            '개설일자' => 'opening_date',
            '설립일' => 'opening_date'
        );

        // 헤더 인덱스 매핑
        $header_indices = array();
        foreach ($headers as $index => $header) {
            if (isset($column_mapping[$header])) {
                $header_indices[$column_mapping[$header]] = $index;
            }
        }

        // 데이터 행 처리 (최대 5개 행만 표시)
        for ($i = 1; $i < count($excel_data) && $i <= 5; $i++) {
            $row = $excel_data[$i];
            
            // 빈 행 건너뛰기
            if (empty(array_filter($row))) {
                continue;
            }

            $data = array();
            
            // 데이터 매핑
            foreach ($header_indices as $field => $index) {
                $value = isset($row[$index]) ? trim($row[$index]) : '';
                $data[$field] = $value;
            }

            $processed_rows[] = $data;
        }

        return $processed_rows;
    }

    // 엑셀 데이터 처리 및 데이터베이스 저장
    private function process_excel_data($excel_data)
    {
        // 대용량 데이터 처리를 위한 추가 최적화
        set_time_limit(0);
        ini_set('memory_limit', '2G');
        
        $result = array('inserted' => 0, 'updated' => 0, 'errors' => 0, 'skipped' => 0);
        
        if (empty($excel_data) || count($excel_data) < 2) {
            return $result;
        }

        // 업로드 로그 시작
        $file_name = isset($_FILES['excel_file']['name']) ? $_FILES['excel_file']['name'] : 'unknown_file';
        $file_size = isset($_FILES['excel_file']['size']) ? $_FILES['excel_file']['size'] : 0;
        $total_rows = count($excel_data) - 1; // 헤더 제외
        $upload_log_id = $this->Medical_data_model->start_upload_log($file_name, $file_size, $total_rows);

        // 헤더 행 가져오기
        $headers = array_map('trim', $excel_data[0]);
        
        // 컬럼 매핑 (다양한 헤더명 지원) - 실제 엑셀 파일의 모든 컬럼 매핑
        $column_mapping = array(
            // 기본 정보
            '암호화요양기호' => 'encrypted_code',
            '요양기관명' => 'institution_name',
            '기관명' => 'institution_name',
            '병원명' => 'institution_name',
            '의료기관명' => 'institution_name',
            'institution_name' => 'institution_name',
            
            // 분류 정보
            '종별코드' => 'category_code',
            '종별코드명' => 'category_name',
            '기관종류' => 'category_name',
            '기관구분' => 'category_name',
            '종별' => 'category_name',
            
            // 지역 정보
            '시도코드' => 'sido_code',
            '시도코드명' => 'sido_name',
            '시군구코드' => 'sigungu_code', 
            '시군구코드명' => 'sigungu_name',
            '읍면동' => 'eupmyeondong',
            
            // 주소 및 연락처
            '우편번호' => 'postal_code',
            '주소' => 'address',
            '소재지' => 'address',
            '전화번호' => 'phone',
            '연락처' => 'phone',
            'TEL' => 'phone',
            
            // 웹사이트 및 날짜
            '병원홈페이지' => 'website',
            '홈페이지' => 'website',
            '웹사이트' => 'website',
            '개설일자' => 'opening_date',
            '개원일' => 'opening_date',
            '설립일' => 'opening_date',
            
            // 의료진 정보
            '총의사수' => 'total_doctors',
            '의과일반의 인원수' => 'general_medicine_doctors',
            '의과인턴 인원수' => 'medicine_intern_doctors',
            '의과레지던트 인원수' => 'medicine_resident_doctors',
            '의과전문의 인원수' => 'medicine_specialist_doctors',
            '치과일반의 인원수' => 'dental_general_doctors',
            '치과인턴 인원수' => 'dental_intern_doctors',
            '치과레지던트 인원수' => 'dental_resident_doctors',
            '치과전문의 인원수' => 'dental_specialist_doctors',
            '한방일반의 인원수' => 'oriental_general_doctors',
            '한방인턴 인원수' => 'oriental_intern_doctors',
            '한방레지던트 인원수' => 'oriental_resident_doctors',
            '한방전문의 인원수' => 'oriental_specialist_doctors',
            '조산사 인원수' => 'midwives',
            
            // 좌표 정보
            '좌표(X)' => 'location_x',
            '좌표(Y)' => 'location_y',
            'X좌표' => 'location_x',
            'Y좌표' => 'location_y',
            
            // 기타 호환성을 위한 매핑
            'phone_number' => 'phone',
            'address' => 'address',
            'name' => 'institution_name'
        );

        // 헤더 인덱스 매핑
        $header_indices = array();
        foreach ($headers as $index => $header) {
            if (isset($column_mapping[$header])) {
                $header_indices[$column_mapping[$header]] = $index;
            }
        }

        // 디버깅을 위한 헤더 정보 로그
        log_message('debug', '엑셀 파일 헤더: ' . implode(', ', $headers));
        log_message('debug', '매핑된 헤더: ' . json_encode($header_indices));

        // 필수 필드 확인
        $required_fields = array('institution_name', 'address', 'phone');
        $missing_fields = array();
        foreach ($required_fields as $field) {
            if (!isset($header_indices[$field])) {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            $error_msg = "필수 컬럼이 누락되었습니다: " . implode(', ', $missing_fields) . 
                        "\n업로드된 파일의 헤더: " . implode(', ', $headers) . 
                        "\n지원되는 헤더 예시: 기관명(또는 병원명), 주소(또는 소재지), 전화번호(또는 연락처)";
            throw new Exception($error_msg);
        }

        // 데이터 행 처리
        for ($i = 1; $i < count($excel_data); $i++) {
            try {
                $row = $excel_data[$i];
                
                // 빈 행 건너뛰기
                if (empty(array_filter($row))) {
                    $result['skipped']++;
                    continue;
                }

                $data = array();
                
                // 데이터 매핑
                foreach ($header_indices as $field => $index) {
                    $value = isset($row[$index]) ? trim($row[$index]) : '';
                    $data[$field] = $value;
                }

                // 필수 필드 검증
                $missing_fields = array();
                foreach ($required_fields as $field) {
                    if (empty($data[$field])) {
                        $missing_fields[] = $field;
                    }
                }

                if (!empty($missing_fields)) {
                    $error_msg = "필수 필드 누락: " . implode(', ', $missing_fields);
                    $this->Medical_data_model->save_failed_record($upload_log_id, $i, $data, $error_msg, 'missing_required');
                    $result['errors']++;
                    continue;
                }

                // 데이터베이스에 저장
                $save_result = $this->Medical_data_model->insert_or_update_medical_institution($data);
                if ($save_result === 'inserted') {
                    $result['inserted']++;
                } elseif ($save_result === 'updated') {
                    $result['updated']++;
                } else {
                    // 저장 실패
                    $this->Medical_data_model->save_failed_record($upload_log_id, $i, $data, "데이터베이스 저장 실패", 'database_error');
                    $result['errors']++;
                }

            } catch (Exception $e) {
                $this->Medical_data_model->save_failed_record($upload_log_id, $i, isset($data) ? $data : array(), $e->getMessage(), 'validation_error');
                $result['errors']++;
                log_message('error', "행 {$i} 처리 오류: " . $e->getMessage());
            }
        }

        // 업로드 로그 완료
        $this->Medical_data_model->complete_upload_log(
            $upload_log_id, 
            $result['inserted'], 
            $result['updated'], 
            $result['errors'], 
            $result['skipped']
        );

        // 결과에 upload_log_id 추가
        $result['upload_log_id'] = $upload_log_id;

        return $result;
    }

    // 업로드 통계 조회
    public function stats()
    {
        $this->load->model('Medical_data_model');
        
        // 최근 업로드 로그들 조회
        $this->db->order_by('upload_date', 'DESC');
        $this->db->limit(10);
        $upload_logs = $this->db->get('upload_logs')->result();
        
        $data = array(
            'upload_logs' => $upload_logs,
            'page_title' => '업로드 통계'
        );
        
        $this->load->view('excel/upload_stats', $data);
    }
    
    // 실패한 레코드 조회
    public function failed_records($upload_log_id = null)
    {
        $this->load->model('Medical_data_model');
        
        if (!$upload_log_id) {
            // 가장 최근 업로드 로그 ID 가져오기
            $this->db->order_by('id', 'DESC');
            $this->db->limit(1);
            $latest_log = $this->db->get('upload_logs')->row();
            $upload_log_id = $latest_log ? $latest_log->id : null;
        }
        
        if ($upload_log_id) {
            $failed_records = $this->Medical_data_model->get_failed_records($upload_log_id);
            $upload_log = $this->db->where('id', $upload_log_id)->get('upload_logs')->row();
        } else {
            $failed_records = array();
            $upload_log = null;
        }
        
        $data = array(
            'failed_records' => $failed_records,
            'upload_log' => $upload_log,
            'upload_log_id' => $upload_log_id,
            'page_title' => '실패한 레코드'
        );
        
        $this->load->view('excel/failed_records', $data);
    }
    
    // 실패한 레코드 재처리
    public function reprocess($upload_log_id = null)
    {
        if (!$upload_log_id) {
            show_404();
            return;
        }
        
        $this->load->model('Medical_data_model');
        
        try {
            $result = $this->Medical_data_model->reprocess_failed_records($upload_log_id);
            
            $response = array(
                'success' => true,
                'message' => "재처리 완료: 성공 {$result['success']}건, 실패 {$result['failed']}건",
                'data' => $result
            );
        } catch (Exception $e) {
            $response = array(
                'success' => false,
                'message' => '재처리 중 오류: ' . $e->getMessage()
            );
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
    }
} 