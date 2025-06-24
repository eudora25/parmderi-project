<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 의료기관 데이터 마이그레이션 배치 클래스
 * 
 * @description 의료기관 데이터를 정규화된 테이블로 마이그레이션하는 배치 작업 (증분 처리)
 * @author Parmderi Development Team
 * @version 2.0 - encrypted_code 기준 증분 처리
 */
class Medical_data_batch {
    
    private $CI;
    private $db;
    private $log_file;
    private $start_time;
    private $processed_count = 0;
    private $updated_count = 0;
    private $inserted_count = 0;
    private $deleted_count = 0;
    private $error_count = 0;
    
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper('file');
        $this->db = $this->CI->db;
        
        // 로그 파일 설정
        $this->log_file = APPPATH . 'logs/batch_' . date('Y-m-d_H-i-s') . '.log';
        $this->start_time = microtime(true);
        
        $this->write_log("=== 의료기관 데이터 증분 마이그레이션 배치 시작 ===");
    }
    
    /**
     * 메인 마이그레이션 실행
     */
    public function execute()
    {
        try {
            $this->write_log("증분 마이그레이션 프로세스 시작");
            
            // 1. 테이블 존재 확인
            if (!$this->check_tables_exist()) {
                throw new Exception("필요한 테이블이 존재하지 않습니다.");
            }
            
            // 2. 마지막 마이그레이션 시점 확인
            $last_migration_time = $this->get_last_migration_time();
            $this->write_log("마지막 마이그레이션 시점: " . ($last_migration_time ?: '없음 (전체 마이그레이션)'));
            
            // 3. 원본 데이터와 대상 데이터 비교 분석
            $comparison_result = $this->analyze_data_changes($last_migration_time);
            
            if (empty($comparison_result['to_process']) && empty($comparison_result['to_delete'])) {
                $this->write_log("마이그레이션할 변경 사항이 없습니다.");
                return true;
            }
            
            $this->write_log("변경 분석 결과:");
            $this->write_log("- 처리할 레코드 수: " . count($comparison_result['to_process']));
            $this->write_log("- 삭제할 레코드 수: " . count($comparison_result['to_delete']));
            
            // 4. 트랜잭션 시작
            $this->db->trans_start();
            
            // 5. 변경/신규 데이터 마이그레이션
            $this->process_changed_data($comparison_result['to_process']);
            
            // 6. 삭제된 데이터 처리
            $this->process_deleted_data($comparison_result['to_delete']);
            
            // 7. 트랜잭션 완료
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception("트랜잭션이 실패했습니다.");
            }
            
            // 8. 마이그레이션 완료 기록
            $this->record_migration_completion();
            
            // 9. 결과 요약
            $this->write_summary();
            
            return true;
            
        } catch (Exception $e) {
            $this->write_log("배치 실행 중 오류 발생: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 데이터 변경 사항 분석
     */
    private function analyze_data_changes($last_migration_time)
    {
        $this->write_log("데이터 변경 사항 분석 시작...");
        
        // 원본 데이터에서 변경된/신규 데이터 조회
        $source_data = $this->get_source_data($last_migration_time);
        $source_codes = array_column($source_data, 'encrypted_code');
        
        // 기존 마이그레이션된 데이터의 encrypted_code 조회
        $existing_codes = $this->get_existing_encrypted_codes();
        
        // 신규 및 변경된 데이터 식별
        $to_process = [];
        foreach ($source_data as $record) {
            // updated_at이 마지막 마이그레이션 이후이거나 새로운 encrypted_code인 경우
            if (!$last_migration_time || 
                strtotime($record['updated_at']) > strtotime($last_migration_time) ||
                !in_array($record['encrypted_code'], $existing_codes)) {
                $to_process[] = $record;
            }
        }
        
        // 삭제된 데이터 식별 (원본에 없지만 대상에 있는 데이터)
        $to_delete = array_diff($existing_codes, $source_codes);
        
        return [
            'to_process' => $to_process,
            'to_delete' => $to_delete
        ];
    }
    
    /**
     * 원본 데이터 조회
     */
    private function get_source_data($last_migration_time)
    {
        // 논리적 삭제를 위해 항상 전체 데이터를 조회
        $this->db->select('*')
                 ->from('im_medical_institutions')
                 ->order_by('updated_at', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * 기존 encrypted_code 목록 조회 (정상 상태만)
     */
    private function get_existing_encrypted_codes()
    {
        $query = $this->db->select('encrypted_code')
                          ->from('medical_institution')
                          ->where('state', 'Y')  // 정상 상태만 조회
                          ->get();
        
        $result = $query->result_array();
        return array_column($result, 'encrypted_code');
    }
    
    /**
     * 변경/신규 데이터 처리
     */
    private function process_changed_data($data_to_process)
    {
        $total_count = count($data_to_process);
        
        if ($total_count == 0) {
            return;
        }
        
        $this->write_log("변경/신규 데이터 처리 시작: {$total_count}개");
        
        foreach ($data_to_process as $index => $institution_data) {
            try {
                $institution = (object) $institution_data;
                $is_update = $this->migrate_single_institution($institution);
                
                if ($is_update) {
                    $this->updated_count++;
                } else {
                    $this->inserted_count++;
                }
                
                $this->processed_count++;
                
                // 진행률 로깅 (100개마다)
                if (($index + 1) % 100 == 0) {
                    $progress = round((($index + 1) / $total_count) * 100, 2);
                    $this->write_log("처리 진행률: " . $progress . "% (" . ($index + 1) . "/" . $total_count . ")");
                }
                
            } catch (Exception $e) {
                $this->error_count++;
                $this->write_log("레코드 처리 실패 (encrypted_code: " . $institution_data['encrypted_code'] . "): " . $e->getMessage());
            }
        }
    }
    
    /**
     * 삭제된 데이터 처리 (논리적 삭제)
     */
    private function process_deleted_data($codes_to_delete)
    {
        if (empty($codes_to_delete)) {
            return;
        }
        
        $this->write_log("삭제된 데이터 처리 시작 (논리적 삭제): " . count($codes_to_delete) . "개");
        
        foreach ($codes_to_delete as $encrypted_code) {
            try {
                // medical_institution에서 상태를 'N'으로 변경 (논리적 삭제)
                $affected_rows = $this->db->where('encrypted_code', $encrypted_code)
                                         ->where('state', 'Y')  // 정상 상태인 것만
                                         ->update('medical_institution', array('state' => 'N'));
                
                if ($affected_rows > 0) {
                    $this->deleted_count++;
                    $this->write_log("논리적 삭제 완료: encrypted_code = {$encrypted_code}");
                }
                
            } catch (Exception $e) {
                $this->error_count++;
                $this->write_log("논리적 삭제 실패 (encrypted_code: {$encrypted_code}): " . $e->getMessage());
            }
        }
    }
    
    /**
     * 필요한 테이블들이 존재하는지 확인
     */
    private function check_tables_exist()
    {
        $required_tables = [
            'im_medical_institutions',
            'medical_institution',
            'medical_institution_facility',
            'medical_institution_hospital',
            'medical_institution_specialty'
        ];
        
        foreach ($required_tables as $table) {
            if (!$this->db->table_exists($table)) {
                $this->write_log("테이블이 존재하지 않음: " . $table);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 마지막 마이그레이션 시점 조회
     */
    private function get_last_migration_time()
    {
        $query = $this->db->select('MAX(updated_at) as last_time')
                          ->from('medical_institution')
                          ->get();
        
        $result = $query->row();
        return $result ? $result->last_time : null;
    }
    
    /**
     * 단일 의료기관 데이터 마이그레이션
     * @return bool true if updated, false if inserted
     */
    private function migrate_single_institution($institution)
    {
        // 1. 기본 정보 테이블 처리
        $result = $this->migrate_basic_info($institution);
        $institution_id = $result['id'];
        $is_update = $result['is_update'];
        
        // 2. 시설 정보 테이블 처리
        $this->migrate_facility_info($institution_id, $institution);
        
        // 3. 병원 정보 테이블 처리
        $this->migrate_hospital_info($institution_id, $institution);
        
        // 4. 진료과목 정보 테이블 처리
        $this->migrate_specialty_info($institution_id, $institution);
        
        return $is_update;
    }
    
    /**
     * 기본 정보 마이그레이션
     */
    private function migrate_basic_info($institution)
    {
        $data = array(
            'encrypted_code' => $institution->encrypted_code,
            'institution_name' => $institution->institution_name,
            'category_code' => $institution->category_code,
            'category_name' => $institution->category_name,
            'sido_code' => $institution->sido_code,
            'sido_name' => $institution->sido_name,
            'sigungu_code' => $institution->sigungu_code,
            'sigungu_name' => $institution->sigungu_name,
            'eupmyeondong' => $institution->eupmyeondong,
            'postal_code' => $institution->postal_code,
            'address' => $institution->address,
            'phone_number' => $institution->phone_number,
            'homepage_url' => $institution->homepage_url,
            'establishment_date' => $institution->establishment_date,
            'updated_at' => $institution->updated_at,
            'state' => 'Y'  // 정상 상태로 설정
        );
        
        // 기존 레코드 확인 (삭제된 것도 포함하여 확인)
        $existing = $this->db->where('encrypted_code', $institution->encrypted_code)
                            ->get('medical_institution')
                            ->row();
        
        if ($existing) {
            // 데이터 변경 여부 확인
            $has_changes = false;
            foreach ($data as $key => $value) {
                if ($existing->$key !== $value) {
                    $has_changes = true;
                    break;
                }
            }
            
            if ($has_changes) {
                // 업데이트 (삭제된 상태에서도 복구 가능)
                $this->db->where('id', $existing->id)
                        ->update('medical_institution', $data);
                
                if ($existing->state === 'N') {
                    $this->write_log("복구 및 업데이트: encrypted_code = {$institution->encrypted_code}");
                } else {
                    $this->write_log("업데이트: encrypted_code = {$institution->encrypted_code}");
                }
            } else if ($existing->state === 'N') {
                // 데이터는 같지만 삭제 상태에서 정상 상태로 복구
                $this->db->where('id', $existing->id)
                        ->update('medical_institution', array('state' => 'Y'));
                $this->write_log("복구: encrypted_code = {$institution->encrypted_code}");
            }
            
            return ['id' => $existing->id, 'is_update' => true];
        } else {
            // 신규 삽입
            $this->db->insert('medical_institution', $data);
            $new_id = $this->db->insert_id();
            $this->write_log("신규 등록: encrypted_code = {$institution->encrypted_code}");
            
            return ['id' => $new_id, 'is_update' => false];
        }
    }
    
    /**
     * 시설 정보 마이그레이션
     */
    private function migrate_facility_info($institution_id, $institution)
    {
        if (!empty($institution->location_x) || !empty($institution->location_y)) {
            $data = array(
                'institution_id' => $institution_id,
                'location_x' => $institution->location_x,
                'location_y' => $institution->location_y,
                'facility_type' => 'main'
            );
            
            // 기존 레코드 확인
            $existing = $this->db->where('institution_id', $institution_id)
                                ->get('medical_institution_facility')
                                ->row();
            
            if ($existing) {
                // 데이터 변경 여부 확인
                $has_changes = false;
                foreach ($data as $key => $value) {
                    if ($existing->$key !== $value) {
                        $has_changes = true;
                        break;
                    }
                }
                
                if ($has_changes) {
                    // 업데이트
                    $this->db->where('id', $existing->id)
                            ->update('medical_institution_facility', $data);
                }
            } else {
                // 신규 삽입
                $this->db->insert('medical_institution_facility', $data);
            }
        } else {
            // 좌표 정보가 없으면 기존 레코드 삭제
            $this->db->where('institution_id', $institution_id)
                     ->delete('medical_institution_facility');
        }
    }
    
    /**
     * 병원 정보 마이그레이션
     */
    private function migrate_hospital_info($institution_id, $institution)
    {
        if (!empty($institution->total_doctors)) {
            $data = array(
                'institution_id' => $institution_id,
                'total_doctors' => $institution->total_doctors,
                'hospital_grade' => $this->determine_hospital_grade($institution->category_name)
            );
            
            // 기존 레코드 확인
            $existing = $this->db->where('institution_id', $institution_id)
                                ->get('medical_institution_hospital')
                                ->row();
            
            if ($existing) {
                // 데이터 변경 여부 확인
                $has_changes = false;
                foreach ($data as $key => $value) {
                    if ($existing->$key !== $value) {
                        $has_changes = true;
                        break;
                    }
                }
                
                if ($has_changes) {
                    // 업데이트
                    $this->db->where('id', $existing->id)
                            ->update('medical_institution_hospital', $data);
                }
            } else {
                // 신규 삽입
                $this->db->insert('medical_institution_hospital', $data);
            }
        } else {
            // 의사 수 정보가 없으면 기존 레코드 삭제
            $this->db->where('institution_id', $institution_id)
                     ->delete('medical_institution_hospital');
        }
    }
    
    /**
     * 진료과목 정보 마이그레이션
     */
    private function migrate_specialty_info($institution_id, $institution)
    {
        $specialty_fields = array(
            'general_medicine_doctors' => 'general_medicine',
            'medicine_intern_doctors' => 'medicine_intern',
            'medicine_resident_doctors' => 'medicine_resident',
            'medicine_specialist_doctors' => 'medicine_specialist',
            'dental_general_doctors' => 'dental_general',
            'dental_intern_doctors' => 'dental_intern',
            'dental_resident_doctors' => 'dental_resident',
            'dental_specialist_doctors' => 'dental_specialist',
            'oriental_general_doctors' => 'oriental_general',
            'oriental_intern_doctors' => 'oriental_intern',
            'oriental_resident_doctors' => 'oriental_resident',
            'oriental_specialist_doctors' => 'oriental_specialist',
            'midwives' => 'midwives'
        );
        
        // 현재 원본 데이터에서 유효한 진료과목 목록
        $current_specialties = array();
        foreach ($specialty_fields as $field => $specialty_type) {
            $doctor_count = $institution->$field;
            if ($doctor_count > 0) {
                $current_specialties[$specialty_type] = $doctor_count;
            }
        }
        
        // 기존 진료과목 데이터 조회
        $existing_specialties = $this->db->where('institution_id', $institution_id)
                                        ->get('medical_institution_specialty')
                                        ->result();
        
        $existing_map = array();
        foreach ($existing_specialties as $existing) {
            $existing_map[$existing->specialty_type] = $existing;
        }
        
        // 현재 진료과목 처리 (업데이트 또는 신규 삽입)
        foreach ($current_specialties as $specialty_type => $doctor_count) {
            $data = array(
                'institution_id' => $institution_id,
                'specialty_type' => $specialty_type,
                'doctor_count' => $doctor_count,
                'is_active' => 1
            );
            
            if (isset($existing_map[$specialty_type])) {
                // 기존 레코드 업데이트
                $existing = $existing_map[$specialty_type];
                
                // 데이터 변경 여부 확인
                if ($existing->doctor_count != $doctor_count || $existing->is_active != 1) {
                    $this->db->where('id', $existing->id)
                            ->update('medical_institution_specialty', $data);
                }
                
                // 처리된 항목 제거 (나중에 삭제 대상에서 제외)
                unset($existing_map[$specialty_type]);
            } else {
                // 신규 삽입
                $this->db->insert('medical_institution_specialty', $data);
            }
        }
        
        // 원본에 없는 기존 레코드들 삭제
        foreach ($existing_map as $specialty_type => $existing) {
            $this->db->where('id', $existing->id)
                     ->delete('medical_institution_specialty');
        }
    }
    
    /**
     * 병원 등급 결정
     */
    private function determine_hospital_grade($category_name)
    {
        if (strpos($category_name, '상급종합병원') !== false) {
            return 'tertiary';
        } elseif (strpos($category_name, '종합병원') !== false) {
            return 'secondary';
        } elseif (strpos($category_name, '병원') !== false) {
            return 'primary';
        } else {
            return 'clinic';
        }
    }
    
    /**
     * 마이그레이션 완료 기록
     */
    private function record_migration_completion()
    {
        $migration_log = array(
            'migration_date' => date('Y-m-d H:i:s'),
            'processed_count' => $this->processed_count,
            'inserted_count' => $this->inserted_count,
            'updated_count' => $this->updated_count,
            'deleted_count' => $this->deleted_count,
            'error_count' => $this->error_count,
            'execution_time' => round(microtime(true) - $this->start_time, 2)
        );
        
        // 마이그레이션 로그 테이블이 있다면 기록
        if ($this->db->table_exists('migration_logs')) {
            $this->db->insert('migration_logs', $migration_log);
        }
    }
    
    /**
     * 실행 결과 요약
     */
    private function write_summary()
    {
        $execution_time = round(microtime(true) - $this->start_time, 2);
        
        $this->write_log("=== 증분 마이그레이션 완료 ===");
        $this->write_log("전체 처리된 레코드 수: " . $this->processed_count);
        $this->write_log("- 신규 등록: " . $this->inserted_count);
        $this->write_log("- 업데이트: " . $this->updated_count);
        $this->write_log("- 삭제: " . $this->deleted_count);
        $this->write_log("오류 발생 수: " . $this->error_count);
        $this->write_log("실행 시간: " . $execution_time . "초");
        
        if ($this->processed_count > 0) {
            $this->write_log("평균 처리 속도: " . round($this->processed_count / $execution_time, 2) . "개/초");
        }
        
        // 성공률 계산
        $total_operations = $this->processed_count + $this->deleted_count;
        if ($total_operations > 0) {
            $success_rate = round((($total_operations - $this->error_count) / $total_operations) * 100, 2);
            $this->write_log("성공률: " . $success_rate . "%");
        }
    }
    
    /**
     * 로그 기록
     */
    private function write_log($message)
    {
        $log_message = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
        
        // 파일에 로그 기록
        file_put_contents($this->log_file, $log_message, FILE_APPEND | LOCK_EX);
        
        // 콘솔 출력 (CLI 환경에서)
        if (php_sapi_name() === 'cli') {
            echo $log_message;
        }
    }
} 