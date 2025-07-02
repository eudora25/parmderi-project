<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Excel_debug extends CI_Controller {
    
    public function index() {
        echo "<h2>엑셀 파일 디버깅 도구</h2>";
        echo "<hr>";
        
        echo '<form method="post" enctype="multipart/form-data">';
        echo '<p><input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required></p>';
        echo '<p><button type="submit">파일 분석</button></p>';
        echo '</form>';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['excel_file']['name'])) {
            $this->analyze_file();
        }
    }
    
    private function analyze_file() {
        try {
            $file = $_FILES['excel_file'];
            $temp_path = $file['tmp_name'];
            
            echo "<h3>파일 정보</h3>";
            echo "<ul>";
            echo "<li>파일명: " . htmlspecialchars($file['name']) . "</li>";
            echo "<li>크기: " . number_format($file['size']) . " bytes</li>";
            echo "<li>타입: " . htmlspecialchars($file['type']) . "</li>";
            echo "</ul>";
            
            // 파일 확장자 확인
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            echo "<p>확장자: " . $extension . "</p>";
            
            if ($extension === 'csv') {
                $this->analyze_csv($temp_path);
            } else {
                $this->analyze_excel($temp_path);
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    private function analyze_csv($file_path) {
        echo "<h3>CSV 파일 분석</h3>";
        
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            $row_count = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && $row_count < 5) {
                echo "<h4>행 " . ($row_count + 1) . " (컬럼 수: " . count($data) . ")</h4>";
                echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
                $row_count++;
            }
            fclose($handle);
        }
    }
    
    private function analyze_excel($file_path) {
        echo "<h3>엑셀 파일 분석</h3>";
        
        require_once APPPATH . 'third_party/SimpleXLSX/SimpleXLSX.php';
        
        if ($xlsx = \Shuchkin\SimpleXLSX::parse($file_path)) {
            $sheets = $xlsx->sheetNames();
            echo "<p>시트 목록: " . implode(', ', $sheets) . "</p>";
            
            $rows = $xlsx->rows();
            echo "<p>총 행 수: " . count($rows) . "</p>";
            
            // 처음 5행 표시
            for ($i = 0; $i < min(5, count($rows)); $i++) {
                echo "<h4>행 " . ($i + 1) . " (컬럼 수: " . count($rows[$i]) . ")</h4>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr>";
                foreach ($rows[$i] as $j => $cell) {
                    $cell_value = trim($cell);
                    $display_value = empty($cell_value) ? '<em>[빈값]</em>' : htmlspecialchars($cell_value);
                    echo "<td style='padding: 5px;'>컬럼 " . ($j + 1) . ": " . $display_value . "</td>";
                }
                echo "</tr>";
                echo "</table>";
                echo "<pre>Raw data: " . htmlspecialchars(print_r($rows[$i], true)) . "</pre>";
            }
            
            // 헤더 자동 감지 테스트
            echo "<h4>헤더 자동 감지 테스트</h4>";
            for ($i = 0; $i < min(5, count($rows)); $i++) {
                $potential_headers = array_map('trim', $rows[$i]);
                $non_empty_count = count(array_filter($potential_headers, function($h) { return !empty($h); }));
                
                echo "<p>행 " . ($i + 1) . ": 비어있지 않은 컬럼 " . $non_empty_count . "개";
                if ($non_empty_count >= 3) {
                    echo " <strong>(헤더 후보)</strong>";
                }
                echo "</p>";
            }
            
        } else {
            echo "<p style='color: red;'>SimpleXLSX 파싱 오류: " . \Shuchkin\SimpleXLSX::parseError() . "</p>";
        }
    }
}
?> 