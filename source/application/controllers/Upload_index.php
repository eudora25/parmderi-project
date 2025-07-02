<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Upload_index extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
    }

    public function index() {
        $data['title'] = '데이터 업로드 메인';
        $this->load->view('upload_index/main', $data);
    }
} 