<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Excel_upload extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'file'));
        $this->load->library('upload');
        $this->load->database();
    }

    // 업로드 페이지 표시
    public function index()
    {
        $data['title'] = '병원정보 엑셀 업로드';
        $this->load->view('excel/upload_form', $data);
    }

    // 엑셀 파일 업로드 및 처리
    public function upload()
    {
        echo "Excel Upload Controller is working!";
    }

    // 엑셀 파일 미리보기
    public function preview()
    {
        echo json_encode(array('success' => true, 'message' => 'Preview function working'));
    }
} 