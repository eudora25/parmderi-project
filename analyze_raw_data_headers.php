<?php
ini_set('memory_limit', '512M');
set_time_limit(300);

require_once 'source/application/third_party/SimpleXLSX/SimpleXLSX.php';
use Shuchkin\SimpleXLSX;

$excel_file = '제품_raw_db_작업_20250624_후니.xlsx';

echo "<h1>📋 raw_data 시트 헤더 분석</h1>\n";

if ($xlsx = SimpleXLSX::parse($excel_file)) {
    // raw_data 시트 (인덱스 2)
    $rows = $xlsx->rows(2);
    
    if (count($rows) > 4) {
        // 실제 헤더는 5번째 행에 있는 것으로 보임
        $headers = $rows[4]; // 인덱스 4 = 5번째 행
        
        echo "<h2>🔍 실제 헤더 정보 (5번째 행)</h2>\n";
        echo "<p>총 컬럼 수: " . count($headers) . "</p>\n";
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr style='background-color: #f0f0f0;'><th>순번</th><th>컬럼명</th><th>샘플 데이터 (6번째 행)</th></tr>\n";
        
        $sample_row = isset($rows[5]) ? $rows[5] : [];
        
        for ($i = 0; $i < count($headers); $i++) {
            $header = trim($headers[$i]);
            $sample = isset($sample_row[$i]) ? trim($sample_row[$i]) : '';
            
            if (mb_strlen($sample) > 30) {
                $sample = mb_substr($sample, 0, 30) . '...';
            }
            
            echo "<tr>";
            echo "<td>" . ($i + 1) . "</td>";
            echo "<td><strong>" . htmlspecialchars($header) . "</strong></td>";
            echo "<td>" . htmlspecialchars($sample) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // 데이터 행 수 계산 (헤더 제외)
        $data_rows_count = count($rows) - 5;
        echo "<h2>📊 데이터 통계</h2>\n";
        echo "<ul>\n";
        echo "<li>전체 행 수: " . count($rows) . "</li>\n";
        echo "<li>헤더 행: 5개</li>\n";
        echo "<li>실제 데이터 행: " . $data_rows_count . "</li>\n";
        echo "</ul>\n";
        
        // 중요 컬럼 식별
        echo "<h2>🎯 주요 컬럼 식별</h2>\n";
        $important_columns = [];
        
        foreach ($headers as $index => $header) {
            $header = trim($header);
            if (!empty($header)) {
                $important_columns[] = [
                    'index' => $index,
                    'name' => $header,
                    'sample' => isset($sample_row[$index]) ? trim($sample_row[$index]) : ''
                ];
            }
        }
        
        echo "<p>비어있지 않은 컬럼: " . count($important_columns) . "개</p>\n";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr style='background-color: #e8f5e8;'><th>인덱스</th><th>컬럼명</th><th>샘플 데이터</th></tr>\n";
        
        foreach ($important_columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['index'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($col['name']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($col['sample']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
    } else {
        echo "<p>❌ raw_data 시트 데이터가 부족합니다.</p>\n";
    }
    
} else {
    echo "<p>❌ 엑셀 파일을 읽을 수 없습니다: " . SimpleXLSX::parseError() . "</p>\n";
}
?> 