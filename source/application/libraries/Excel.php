<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'third_party/PHPExcel/PHPExcel.php';

class Excel {
    
    public function __construct() {
        // PHPExcel 클래스가 이미 로드되어 있는지 확인
        if (!class_exists('PHPExcel')) {
            throw new Exception('PHPExcel library not found');
        }
    }
    
    public function load($file_path) {
        return PHPExcel_IOFactory::load($file_path);
    }
    
    public function create() {
        return new PHPExcel();
    }
} 