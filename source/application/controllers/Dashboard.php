<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
    }
    
    public function index() {
        $data = array(
            'title' => '의약품 정보 시스템',
            'description' => '의약품 정보를 검색하고 관리할 수 있는 시스템입니다.'
        );
        
        $this->load->view('main_dashboard', $data);
    }
} 
