<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Medical_data_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // 의료기관 정보 삽입
    public function insert_medical_institution($data)
    {
        try {
            // 중복 체크 (기관명 + 전화번호)
            $this->db->where('name', $data['name']);
            $this->db->where('phone', $data['phone']);
            $existing = $this->db->get('medical_institution')->row();

            if ($existing) {
                // 기존 데이터 업데이트
                $this->db->where('id', $existing->id);
                return $this->db->update('medical_institution', $data);
            } else {
                // 새 데이터 삽입
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                return $this->db->insert('medical_institution', $data);
            }
        } catch (Exception $e) {
            log_message('error', '의료기관 데이터 삽입 실패: ' . $e->getMessage());
            return false;
        }
    }

    // 의료기관 목록 조회
    public function get_medical_institutions($limit = 100, $offset = 0, $search = null)
    {
        if ($search) {
            $this->db->like('name', $search);
            $this->db->or_like('address', $search);
        }

        $this->db->limit($limit, $offset);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('medical_institution')->result();
    }

    // 의료기관 총 개수 조회
    public function count_medical_institutions($search = null)
    {
        if ($search) {
            $this->db->like('name', $search);
            $this->db->or_like('address', $search);
        }

        return $this->db->count_all_results('medical_institution');
    }

    // 특정 의료기관 조회
    public function get_medical_institution($id)
    {
        $this->db->where('id', $id);
        return $this->db->get('medical_institution')->row();
    }

    // 의료기관 업데이트
    public function update_medical_institution($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('medical_institution', $data);
    }

    // 의료기관 삭제
    public function delete_medical_institution($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('medical_institution');
    }

    // 일괄 삭제 (업로드 실패 시 롤백용)
    public function delete_batch_medical_institutions($ids)
    {
        if (!empty($ids)) {
            $this->db->where_in('id', $ids);
            return $this->db->delete('medical_institution');
        }
        return true;
    }

    // 기관 종류별 통계
    public function get_institution_type_stats()
    {
        $this->db->select('institution_type_cd, COUNT(*) as count');
        $this->db->group_by('institution_type_cd');
        return $this->db->get('medical_institution')->result();
    }

    // 지역별 통계
    public function get_province_stats()
    {
        $this->db->select('province_cd, COUNT(*) as count');
        $this->db->group_by('province_cd');
        return $this->db->get('medical_institution')->result();
    }

    // 업로드 로그 기록
    public function log_upload($data)
    {
        $log_data = array(
            'file_name' => $data['file_name'],
            'total_rows' => $data['total_rows'],
            'success_count' => $data['success_count'],
            'error_count' => $data['error_count'],
            'upload_date' => date('Y-m-d H:i:s'),
            'user_agent' => $this->input->user_agent(),
            'ip_address' => $this->input->ip_address()
        );

        return $this->db->insert('upload_logs', $log_data);
    }

    // im_medical_institutions 테이블 컬럼 확인을 위한 테스트 메서드
    public function test_im_medical_institutions_structure()
    {
        try {
            // LIMIT 0으로 데이터 없이 테이블 구조만 확인
            $this->db->limit(0);
            $query = $this->db->get('im_medical_institutions');
            $fields = $query->list_fields();
            
            log_message('debug', 'im_medical_institutions 컬럼: ' . implode(', ', $fields));
            return $fields;
        } catch (Exception $e) {
            // 만약 위 방법이 안되면 다른 방법으로 시도
            try {
                $this->db->limit(1);
                $query = $this->db->get('im_medical_institutions');
                
                if ($query->num_rows() > 0) {
                    $columns = array_keys($query->row_array());
                    log_message('debug', 'im_medical_institutions 컬럼: ' . implode(', ', $columns));
                    return $columns;
                } else {
                    log_message('debug', 'im_medical_institutions 테이블이 비어있습니다.');
                    return array();
                }
            } catch (Exception $e2) {
                log_message('error', 'im_medical_institutions 구조 확인 실패: ' . $e2->getMessage());
                return false;
            }
        }
    }

    // 의료기관 정보 삽입 또는 업데이트 (엑셀 업로드용) - im_medical_institutions 테이블
    public function insert_or_update_medical_institution($data)
    {
        try {
            // 디버깅을 위한 로그 (넘어온 데이터 확인)
            log_message('debug', '모델로 넘어온 데이터: ' . json_encode($data));
            
            // 특정 필드들의 값 확인
            $debug_fields = array('category_code', 'sido_code', 'sido_name', 'location_x', 'location_y');
            foreach ($debug_fields as $field) {
                if (isset($data[$field])) {
                    $value = $data[$field];
                    $is_empty = empty($value);
                    $is_null = ($value === null);
                    $is_empty_string = ($value === '');
                    log_message('debug', "필드 {$field}: 값='{$value}', empty={$is_empty}, null={$is_null}, empty_string={$is_empty_string}");
                }
            }
            
            // im_medical_institutions 테이블에 맞는 데이터 매핑
            $mapped_data = array();
            
            // 1. 기본 정보
            if (isset($data['encrypted_code']) && !empty($data['encrypted_code'])) {
                $mapped_data['encrypted_code'] = $data['encrypted_code'];
            }
            if (isset($data['institution_name'])) {
                $mapped_data['institution_name'] = $data['institution_name'];
            }
            
            // 2. 분류 정보
            if (isset($data['category_code']) && $data['category_code'] !== '' && $data['category_code'] !== null) {
                $mapped_data['category_code'] = trim($data['category_code']);
            }
            if (isset($data['category_name']) && $data['category_name'] !== '' && $data['category_name'] !== null) {
                $mapped_data['category_name'] = trim($data['category_name']);
            }
            
            // 3. 지역 정보
            if (isset($data['sido_code']) && $data['sido_code'] !== '' && $data['sido_code'] !== null) {
                $mapped_data['sido_code'] = trim($data['sido_code']);
            }
            if (isset($data['sido_name']) && $data['sido_name'] !== '' && $data['sido_name'] !== null) {
                $mapped_data['sido_name'] = trim($data['sido_name']);
            }
            if (isset($data['sigungu_code']) && $data['sigungu_code'] !== '' && $data['sigungu_code'] !== null) {
                $mapped_data['sigungu_code'] = trim($data['sigungu_code']);
            }
            if (isset($data['sigungu_name']) && $data['sigungu_name'] !== '' && $data['sigungu_name'] !== null) {
                $mapped_data['sigungu_name'] = trim($data['sigungu_name']);
            }
            if (isset($data['eupmyeondong']) && $data['eupmyeondong'] !== '' && $data['eupmyeondong'] !== null) {
                $mapped_data['eupmyeondong'] = trim($data['eupmyeondong']);
            }
            
            // 4. 주소 및 연락처
            if (isset($data['postal_code']) && !empty($data['postal_code'])) {
                $mapped_data['postal_code'] = $data['postal_code'];
            }
            if (isset($data['address'])) {
                $mapped_data['address'] = $data['address'];
            }
            if (isset($data['phone'])) {
                $mapped_data['phone_number'] = $data['phone'];
            }
            
            // 5. 웹사이트 및 날짜
            if (isset($data['website']) && !empty($data['website'])) {
                $mapped_data['homepage_url'] = $data['website'];
            }
            if (isset($data['opening_date']) && !empty($data['opening_date'])) {
                // 날짜 형식 변환 (YYYY-MM-DD)
                $date = $data['opening_date'];
                if (strpos($date, '/') !== false) {
                    $date = str_replace('/', '-', $date);
                }
                $mapped_data['establishment_date'] = $date;
            }
            
            // 6. 의료진 정보 (숫자 값은 0 이상인 경우만)
            $doctor_fields = array(
                'total_doctors', 'general_medicine_doctors', 'medicine_intern_doctors',
                'medicine_resident_doctors', 'medicine_specialist_doctors',
                'dental_general_doctors', 'dental_intern_doctors', 'dental_resident_doctors',
                'dental_specialist_doctors', 'oriental_general_doctors', 'oriental_intern_doctors',
                'oriental_resident_doctors', 'oriental_specialist_doctors', 'midwives'
            );
            
            foreach ($doctor_fields as $field) {
                if (isset($data[$field]) && is_numeric($data[$field]) && $data[$field] >= 0) {
                    $mapped_data[$field] = (int)$data[$field];
                }
            }
            
            // 7. 좌표 정보 (숫자 값인 경우만)
            if (isset($data['location_x']) && is_numeric($data['location_x'])) {
                $mapped_data['location_x'] = (float)$data['location_x'];
            }
            if (isset($data['location_y']) && is_numeric($data['location_y'])) {
                $mapped_data['location_y'] = (float)$data['location_y'];
            }
            
            // 최종 매핑된 데이터 로그
            log_message('debug', '최종 매핑된 데이터: ' . json_encode($mapped_data));

            // encrypted_code 처리
            $has_encrypted_code = isset($mapped_data['encrypted_code']) && !empty($mapped_data['encrypted_code']);
            if (!$has_encrypted_code) {
                // encrypted_code가 없는 경우 고유한 코드 생성 (중복 체크 없이 항상 새로 삽입)
                $mapped_data['encrypted_code'] = md5($mapped_data['institution_name'] . time() . rand());
            }

            // 필수 필드 검증
            if (empty($mapped_data['institution_name']) || empty($mapped_data['address']) || empty($mapped_data['phone_number'])) {
                log_message('error', '필수 필드 누락: ' . json_encode($mapped_data));
                return false;
            }

            // 중복 체크 (encrypted_code만으로 체크)
            $existing = null;
            
            // encrypted_code가 엑셀에 있는 경우에만 중복 체크
            if ($has_encrypted_code) {
                $this->db->where('encrypted_code', $data['encrypted_code']);
                $existing = $this->db->get('im_medical_institutions')->row();
            }

            if ($existing) {
                // 기존 데이터 업데이트
                $update_data = $mapped_data;
                // encrypted_code가 이미 있고 같다면 제거 (UNIQUE 충돌 방지)
                if ($existing->encrypted_code == $mapped_data['encrypted_code']) {
                    unset($update_data['encrypted_code']);
                }
                $this->db->where('id', $existing->id);
                if ($this->db->update('im_medical_institutions', $update_data)) {
                    return 'updated';
                } else {
                    return false;
                }
            } else {
                // 새 데이터 삽입
                if ($this->db->insert('im_medical_institutions', $mapped_data)) {
                    return 'inserted';
                } else {
                    return false;
                }
            }
            
        } catch (Exception $e) {
            log_message('error', '의료기관 데이터 처리 실패: ' . $e->getMessage());
            return false;
        }
    }

    // 업로드 로그 테이블 생성 (필요시)
    public function create_upload_logs_table()
    {
        $sql = "CREATE TABLE IF NOT EXISTS upload_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_name VARCHAR(255) NOT NULL,
            total_rows INT DEFAULT 0,
            success_count INT DEFAULT 0,
            error_count INT DEFAULT 0,
            upload_date DATETIME NOT NULL,
            user_agent TEXT,
            ip_address VARCHAR(45)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return $this->db->query($sql);
    }

    // 업로드 로그 시작
    public function start_upload_log($file_name, $file_size, $total_rows)
    {
        $log_data = array(
            'file_name' => $file_name,
            'file_size' => $file_size,
            'total_rows' => $total_rows,
            'upload_date' => date('Y-m-d H:i:s'),
            'status' => 'processing',
            'user_agent' => $this->input->user_agent(),
            'ip_address' => $this->input->ip_address()
        );

        $this->db->insert('upload_logs', $log_data);
        return $this->db->insert_id();
    }

    // 업로드 로그 완료
    public function complete_upload_log($upload_log_id, $success_count, $update_count, $error_count, $skipped_count, $error_message = null)
    {
        $update_data = array(
            'success_count' => $success_count,
            'update_count' => $update_count,  
            'error_count' => $error_count,
            'skipped_count' => $skipped_count,
            'completion_date' => date('Y-m-d H:i:s'),
            'status' => $error_count > 0 ? 'completed' : 'completed'
        );

        if ($error_message) {
            $update_data['error_message'] = $error_message;
        }

        $this->db->where('id', $upload_log_id);
        return $this->db->update('upload_logs', $update_data);
    }

    // 실패 레코드 저장
    public function save_failed_record($upload_log_id, $row_num, $data, $error_message, $error_type = 'validation_error')
    {
        $failed_data = array(
            'upload_log_id' => $upload_log_id,
            'row_num' => $row_num,
            'encrypted_code' => isset($data['encrypted_code']) ? $data['encrypted_code'] : null,
            'institution_name' => isset($data['institution_name']) ? $data['institution_name'] : null,
            'raw_data' => json_encode($data),
            'error_message' => $error_message,
            'error_type' => $error_type
        );

        return $this->db->insert('failed_records', $failed_data);
    }

    // 실패 레코드 조회
    public function get_failed_records($upload_log_id = null, $processed = false)
    {
        if ($upload_log_id) {
            $this->db->where('upload_log_id', $upload_log_id);
        }
        $this->db->where('processed', $processed);
        $this->db->order_by('row_num', 'ASC');
        return $this->db->get('failed_records')->result();
    }

    // 실패 레코드 재처리
    public function reprocess_failed_records($upload_log_id)
    {
        $failed_records = $this->get_failed_records($upload_log_id, false);
        $success_count = 0;
        $still_failed = 0;

        foreach ($failed_records as $record) {
            try {
                $data = json_decode($record->raw_data, true);
                
                if ($this->insert_or_update_medical_institution($data)) {
                    // 성공 시 processed = true로 마킹
                    $this->db->where('id', $record->id);
                    $this->db->update('failed_records', array('processed' => true));
                    $success_count++;
                } else {
                    $still_failed++;
                }
            } catch (Exception $e) {
                // 여전히 실패한 경우 에러 메시지 업데이트
                $this->db->where('id', $record->id);
                $this->db->update('failed_records', array(
                    'error_message' => $e->getMessage(),
                    'error_type' => 'database_error'
                ));
                $still_failed++;
            }
        }

        return array(
            'success' => $success_count,
            'failed' => $still_failed,
            'total' => count($failed_records)
        );
    }
} 