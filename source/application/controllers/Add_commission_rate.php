<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Add_commission_rate extends CI_Controller {
    
    public function index() {
        $this->load->database();
        
        echo "<h2>medical_products 테이블에 commission_rate 컬럼 추가</h2>";
        echo "<hr>";
        
        try {
            // 현재 테이블 구조 확인
            $query = $this->db->query("DESCRIBE medical_products");
            $columns = $query->result_array();
            
            $commission_rate_exists = false;
            foreach ($columns as $column) {
                if ($column['Field'] === 'commission_rate') {
                    $commission_rate_exists = true;
                    break;
                }
            }
            
            if ($commission_rate_exists) {
                echo "<p style='color: green;'>✓ commission_rate 컬럼이 이미 존재합니다.</p>";
            } else {
                echo "<p>commission_rate 컬럼을 추가하는 중...</p>";
                
                $sql = "ALTER TABLE medical_products ADD COLUMN commission_rate DECIMAL(5,2) NULL COMMENT '수수료율(%)'";
                $this->db->query($sql);
                
                echo "<p style='color: green;'>✓ commission_rate 컬럼이 성공적으로 추가되었습니다.</p>";
            }
            
            echo "<h3>현재 테이블 구조</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Extra</th></tr>";
            
            $query = $this->db->query("DESCRIBE medical_products");
            $columns = $query->result_array();
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<br><p><a href='" . base_url('medical_products/upload') . "'>의약품 업로드 페이지로 돌아가기</a></p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ 오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?> 