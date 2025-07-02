<?php
ini_set('memory_limit', '512M');
set_time_limit(300);

require_once 'source/application/third_party/SimpleXLSX/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

if (!class_exists('Shuchkin\SimpleXLSX')) {
    die('SimpleXLSX 클래스를 로드할 수 없습니다.');
}

// 엑셀 파일 경로
$excel_file = '제품_raw_db_작업_20250624_후니.xlsx';

echo "<h1>📊 엑셀 파일 분석: {$excel_file}</h1>\n";
echo "<p>파일 크기: " . number_format(filesize($excel_file)) . " bytes</p>\n";

if ($xlsx = SimpleXLSX::parse($excel_file)) {
    echo "<h2>🔍 시트 정보</h2>\n";
    $sheets = $xlsx->sheetNames();
    echo "<p>총 시트 수: " . count($sheets) . "</p>\n";
    
    foreach ($sheets as $index => $sheet_name) {
        echo "<h3>📋 시트 {$index}: {$sheet_name}</h3>\n";
        
        // 각 시트의 데이터 가져오기
        $rows = $xlsx->rows($index);
        $total_rows = count($rows);
        
        if ($total_rows > 0) {
            echo "<p>총 행 수: " . number_format($total_rows) . "</p>\n";
            
            // 헤더 분석 (첫 번째 행)
            $headers = $rows[0];
            echo "<h4>📌 컬럼 정보 (총 " . count($headers) . "개)</h4>\n";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
            echo "<tr style='background-color: #f0f0f0;'><th>순번</th><th>컬럼명</th><th>샘플 데이터</th></tr>\n";
            
            for ($i = 0; $i < count($headers); $i++) {
                $sample_data = isset($rows[1][$i]) ? $rows[1][$i] : '';
                if (mb_strlen($sample_data) > 50) {
                    $sample_data = mb_substr($sample_data, 0, 50) . '...';
                }
                echo "<tr>";
                echo "<td>" . ($i + 1) . "</td>";
                echo "<td><strong>" . htmlspecialchars($headers[$i]) . "</strong></td>";
                echo "<td>" . htmlspecialchars($sample_data) . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
            
            // 데이터 샘플 (처음 5행)
            echo "<h4>📝 데이터 샘플 (최대 5행)</h4>\n";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>\n";
            
            $sample_rows = array_slice($rows, 0, min(6, $total_rows)); // 헤더 + 5행
            foreach ($sample_rows as $row_index => $row) {
                echo "<tr" . ($row_index == 0 ? " style='background-color: #f0f0f0; font-weight: bold;'" : "") . ">";
                foreach ($row as $col_index => $cell) {
                    if ($col_index < 10) { // 처음 10개 컬럼만 표시
                        $cell_text = htmlspecialchars($cell);
                        if (mb_strlen($cell_text) > 30) {
                            $cell_text = mb_substr($cell_text, 0, 30) . '...';
                        }
                        echo "<td>" . $cell_text . "</td>";
                    }
                }
                if (count($row) > 10) {
                    echo "<td>... +" . (count($row) - 10) . "개 컬럼</td>";
                }
                echo "</tr>\n";
            }
            echo "</table>\n";
            
            // 데이터 타입 분석
            echo "<h4>🔬 데이터 타입 분석</h4>\n";
            $column_analysis = [];
            
            // 데이터 행들만 분석 (헤더 제외) - 메모리 절약을 위해 100행만 분석
            $data_rows = array_slice($rows, 1, min(100, $total_rows - 1)); // 최대 100행 분석
            
            foreach ($headers as $col_index => $header) {
                $column_analysis[$header] = [
                    'numeric_count' => 0,
                    'text_count' => 0,
                    'empty_count' => 0,
                    'unique_values' => [],
                    'max_length' => 0
                ];
                
                foreach ($data_rows as $row) {
                    $value = isset($row[$col_index]) ? $row[$col_index] : '';
                    
                    if (empty($value)) {
                        $column_analysis[$header]['empty_count']++;
                    } elseif (is_numeric($value)) {
                        $column_analysis[$header]['numeric_count']++;
                    } else {
                        $column_analysis[$header]['text_count']++;
                    }
                    
                    $column_analysis[$header]['max_length'] = max(
                        $column_analysis[$header]['max_length'], 
                        mb_strlen($value)
                    );
                    
                    // 고유값 저장 (최대 10개)
                    if (count($column_analysis[$header]['unique_values']) < 10) {
                        if (!in_array($value, $column_analysis[$header]['unique_values']) && !empty($value)) {
                            $column_analysis[$header]['unique_values'][] = $value;
                        }
                    }
                }
            }
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>컬럼명</th><th>숫자형</th><th>텍스트</th><th>빈값</th><th>최대길이</th><th>샘플값</th>";
            echo "</tr>\n";
            
            foreach ($column_analysis as $col_name => $analysis) {
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($col_name) . "</strong></td>";
                echo "<td>" . $analysis['numeric_count'] . "</td>";
                echo "<td>" . $analysis['text_count'] . "</td>";
                echo "<td>" . $analysis['empty_count'] . "</td>";
                echo "<td>" . $analysis['max_length'] . "</td>";
                echo "<td>" . implode(', ', array_slice($analysis['unique_values'], 0, 3)) . 
                     (count($analysis['unique_values']) > 3 ? '...' : '') . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
            
        } else {
            echo "<p>❌ 빈 시트입니다.</p>\n";
        }
        
        echo "<hr>\n";
    }
    
    echo "<h2>📈 전체 요약</h2>\n";
    echo "<ul>\n";
    echo "<li>파일명: {$excel_file}</li>\n";
    echo "<li>총 시트 수: " . count($sheets) . "</li>\n";
    echo "<li>분석 완료 시간: " . date('Y-m-d H:i:s') . "</li>\n";
    echo "</ul>\n";
    
} else {
    echo "<p>❌ 엑셀 파일을 읽을 수 없습니다: " . SimpleXLSX::parseError() . "</p>\n";
}
?> 