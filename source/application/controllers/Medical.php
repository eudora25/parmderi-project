<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Medical extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('medical_model');
        
        // PHPExcel 라이브러리 로드
        require_once FCPATH . 'vendor/autoload.php';
    }

    public function index() {
        $data['title'] = '요양기관 관리';
        $this->load->view('medical/upload', $data);
    }

    public function upload() {
        // 시간 제한 해제
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
        if (!empty($_FILES['excel_file']['name'])) {
            $path = $_FILES['excel_file']['tmp_name'];
            
            try {
                $object = PHPExcel_IOFactory::load($path);
                
                foreach($object->getWorksheetIterator() as $worksheet) {
                    $highestRow = $worksheet->getHighestRow();
                    $highestColumn = $worksheet->getHighestColumn();
                    
                    for($row=2; $row<=$highestRow; $row++) {
                        $encrypted_code = $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                        $name = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                        $type_code = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                        $type_name = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                        $sido_code = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
                        $sido_name = $worksheet->getCellByColumnAndRow(5, $row)->getValue();
                        $sigungu_code = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
                        $sigungu_name = $worksheet->getCellByColumnAndRow(7, $row)->getValue();
                        $eupmyeondong = $worksheet->getCellByColumnAndRow(8, $row)->getValue();
                        $postal_code = $worksheet->getCellByColumnAndRow(9, $row)->getValue();
                        $address = $worksheet->getCellByColumnAndRow(10, $row)->getValue();
                        $phone = $worksheet->getCellByColumnAndRow(11, $row)->getValue();
                        $website = $worksheet->getCellByColumnAndRow(12, $row)->getValue();
                        $open_date = $worksheet->getCellByColumnAndRow(13, $row)->getValue();
                        $total_doctors = $worksheet->getCellByColumnAndRow(14, $row)->getValue();
                        $med_general = $worksheet->getCellByColumnAndRow(15, $row)->getValue();
                        $med_intern = $worksheet->getCellByColumnAndRow(16, $row)->getValue();
                        $med_resident = $worksheet->getCellByColumnAndRow(17, $row)->getValue();
                        $med_specialist = $worksheet->getCellByColumnAndRow(18, $row)->getValue();
                        $dent_general = $worksheet->getCellByColumnAndRow(19, $row)->getValue();
                        $dent_intern = $worksheet->getCellByColumnAndRow(20, $row)->getValue();
                        $dent_resident = $worksheet->getCellByColumnAndRow(21, $row)->getValue();
                        $dent_specialist = $worksheet->getCellByColumnAndRow(22, $row)->getValue();
                        $oriental_general = $worksheet->getCellByColumnAndRow(23, $row)->getValue();
                        $oriental_intern = $worksheet->getCellByColumnAndRow(24, $row)->getValue();
                        $oriental_resident = $worksheet->getCellByColumnAndRow(25, $row)->getValue();
                        $oriental_specialist = $worksheet->getCellByColumnAndRow(26, $row)->getValue();
                        $midwife_count = $worksheet->getCellByColumnAndRow(27, $row)->getValue();
                        $coord_x = $worksheet->getCellByColumnAndRow(28, $row)->getValue();
                        $coord_y = $worksheet->getCellByColumnAndRow(29, $row)->getValue();
                        
                        if(!empty($encrypted_code)) {
                            $data = array(
                                'encrypted_code' => $encrypted_code,
                                'name' => $name,
                                'type_code' => $type_code,
                                'type_name' => $type_name,
                                'sido_code' => $sido_code,
                                'sido_name' => $sido_name,
                                'sigungu_code' => $sigungu_code,
                                'sigungu_name' => $sigungu_name,
                                'eupmyeondong' => $eupmyeondong,
                                'postal_code' => $postal_code,
                                'address' => $address,
                                'phone' => $phone,
                                'website' => $website,
                                'open_date' => date('Y-m-d', strtotime($open_date)),
                                'total_doctors' => (int)$total_doctors,
                                'med_general' => (int)$med_general,
                                'med_intern' => (int)$med_intern,
                                'med_resident' => (int)$med_resident,
                                'med_specialist' => (int)$med_specialist,
                                'dent_general' => (int)$dent_general,
                                'dent_intern' => (int)$dent_intern,
                                'dent_resident' => (int)$dent_resident,
                                'dent_specialist' => (int)$dent_specialist,
                                'oriental_general' => (int)$oriental_general,
                                'oriental_intern' => (int)$oriental_intern,
                                'oriental_resident' => (int)$oriental_resident,
                                'oriental_specialist' => (int)$oriental_specialist,
                                'midwife_count' => (int)$midwife_count,
                                'coord_x' => (float)$coord_x,
                                'coord_y' => (float)$coord_y
                            );
                            
                            $this->medical_model->insert_update($data);
                        }
                    }
                }
                
                $this->session->set_flashdata('success', '엑셀 파일이 성공적으로 업로드되었습니다.');
                
            } catch (Exception $e) {
                $this->session->set_flashdata('error', '엑셀 파일 처리 중 오류가 발생했습니다: ' . $e->getMessage());
            }
        } else {
            $this->session->set_flashdata('error', '업로드할 엑셀 파일을 선택해주세요.');
        }
        
        redirect('medical');
    }
} 