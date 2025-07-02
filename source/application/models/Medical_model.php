<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Medical_model extends CI_Model {
    
    private $table = 'im_medical_institutions';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function insert_update($data) {
        // 기존 데이터가 있는지 확인
        $this->db->where('encrypted_code', $data['encrypted_code']);
        $query = $this->db->get($this->table);
        
        if($query->num_rows() > 0) {
            // 기존 데이터 업데이트
            $this->db->where('encrypted_code', $data['encrypted_code']);
            return $this->db->update($this->table, $data);
        } else {
            // 새 데이터 삽입
            return $this->db->insert($this->table, $data);
        }
    }
    
    public function get_all() {
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result();
    }
    
    public function get_by_id($id) {
        $this->db->where('id', $id);
        $query = $this->db->get($this->table);
        return $query->row();
    }
    
    public function get_by_encrypted_code($code) {
        $this->db->where('encrypted_code', $code);
        $query = $this->db->get($this->table);
        return $query->row();
    }
} 