<?php
/**
 * 의약품 데이터 직접 업로드 스크립트
 * 제품_raw_db_작업_20250624_후니.xlsx 파일을 처리
 */

ini_set('memory_limit', '1G');
set_time_limit(0);

require_once '/var/www/html/application/third_party/SimpleXLSX/SimpleXLSX.php';

// 데이터베이스 연결
$host = 'parmderi_mariadb';
$port = '3306';
$dbname = 'htest';
$username = 'root';
$password = 'root123';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 데이터베이스 연결 성공\n";
} catch (PDOException $e) {
    die("❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

$excel_file = '/var/www/html/제품_raw_db_작업_20250624_후니.xlsx';

if (!file_exists($excel_file)) {
    die("❌ 엑셀 파일을 찾을 수 없습니다: $excel_file\n");
}

echo "📂 엑셀 파일 분석 시작: $excel_file\n";

if (!$xlsx = Shuchkin\SimpleXLSX::parse($excel_file)) {
    die("❌ 엑셀 파일 파싱 실패: " . Shuchkin\SimpleXLSX::parseError() . "\n");
}

// raw_data 시트 (인덱스 2)
$rows = $xlsx->rows(2);

if (count($rows) < 6) {
    die("❌ raw_data 시트에 충분한 데이터가 없습니다.\n");
}

echo "📊 총 행 수: " . count($rows) . "\n";

// 헤더는 5번째 행 (인덱스 4)
$headers = $rows[4];
$data_rows = array_slice($rows, 5); // 6번째 행부터 데이터

echo "📝 헤더 수: " . count($headers) . "\n";
echo "📈 데이터 행 수: " . count($data_rows) . "\n";

// 필드 매핑 함수
function mapExcelRowToDbFields($headers, $row) {
    $mapped = [];
    
    // 헤더와 값 매핑
    for ($i = 0; $i < count($headers); $i++) {
        $header = trim($headers[$i]);
        $value = isset($row[$i]) ? trim($row[$i]) : '';
        
        // 헤더에 따른 DB 필드 매핑
        switch ($header) {
            case '전체 No':
                $mapped['product_no'] = $value;
                break;
            case 'CSO품목':
                $mapped['cso_product'] = ($value == '1') ? 1 : 0;
                break;
            case '구분':
                $mapped['category'] = $value;
                break;
            case '업체명':
                $mapped['company_name'] = $value;
                break;
            case '분류번호':
                if (!isset($mapped['classification_code_1'])) {
                    $mapped['classification_code_1'] = $value;
                } else {
                    $mapped['classification_code_2'] = $value;
                }
                break;
            case '분류명':
                if (!isset($mapped['classification_name_1'])) {
                    $mapped['classification_name_1'] = $value;
                } else {
                    $mapped['classification_name_2'] = $value;
                }
                break;
            case 'a 보험코드':
                $mapped['insurance_code_a'] = $value;
                break;
            case '보험코드':
                if (!isset($mapped['insurance_code'])) {
                    $mapped['insurance_code'] = $value;
                } elseif (!isset($mapped['insurance_code_2'])) {
                    $mapped['insurance_code_2'] = $value;
                } else {
                    $mapped['insurance_code_3'] = $value;
                }
                break;
            case '제품명':
                if (!isset($mapped['product_name'])) {
                    $mapped['product_name'] = $value;
                } else {
                    $mapped['product_name_2'] = $value;
                }
                break;
            case '주성분코드':
                $mapped['ingredient_code'] = $value;
                break;
            case '주성분명(영문)':
                $mapped['ingredient_name_en'] = $value;
                break;
            case '약가':
                if (!isset($mapped['drug_price'])) {
                    $mapped['drug_price'] = is_numeric($value) ? (float)$value : null;
                } else {
                    $mapped['drug_price_2'] = is_numeric($value) ? (float)$value : null;
                }
                break;
            case '결정할 약가':
                $mapped['decided_price'] = is_numeric($value) ? (float)$value : null;
                break;
            case '급여':
                $mapped['coverage'] = $value;
                break;
            case '제형':
                $mapped['formulation'] = $value;
                break;
            case '품목기준코드':
                $mapped['item_standard_code'] = $value;
                break;
            case 'ATC코드':
                $mapped['atc_code'] = $value;
                break;
            case '비고':
                $mapped['note'] = $value;
                break;
            case '전문/일반':
                $mapped['professional_general'] = $value;
                break;
            case '성분코드':
                $mapped['ingredient_code_2'] = $value;
                break;
            case '함량':
                $mapped['content'] = is_numeric($value) ? (float)$value : null;
                break;
            case '단위':
                $mapped['unit'] = $value;
                break;
        }
    }
    
    return $mapped;
}

echo "🗑️  기존 데이터 삭제 중...\n";
$pdo->exec("TRUNCATE TABLE medical_products");

echo "📥 데이터 처리 시작...\n";

$processed_data = [];
$failed_rows = 0;
$batch_size = 500; // 배치 크기

foreach ($data_rows as $row_index => $row) {
    try {
        $processed_row = mapExcelRowToDbFields($headers, $row);
        if (!empty($processed_row['product_name'])) { // 제품명이 있는 경우만
            $processed_data[] = $processed_row;
        }
        
        // 진행률 표시
        if (($row_index + 1) % 1000 == 0) {
            echo "📊 처리 중: " . number_format($row_index + 1) . " / " . number_format(count($data_rows)) . "\n";
        }
        
    } catch (Exception $e) {
        $failed_rows++;
        echo "⚠️  행 " . ($row_index + 6) . " 처리 실패: " . $e->getMessage() . "\n";
    }
}

echo "💾 데이터베이스 입력 시작...\n";
echo "📈 입력할 데이터: " . number_format(count($processed_data)) . "개\n";

// 배치 입력
$total_inserted = 0;
$batch_count = 0;

// SQL 준비
$sql = "INSERT INTO medical_products (
    product_no, cso_product, category, company_name,
    classification_code_1, classification_name_1, classification_code_2, classification_name_2,
    insurance_code_a, insurance_code, product_name, ingredient_code, ingredient_name_en,
    drug_price, decided_price, coverage, professional_general, formulation,
    item_standard_code, atc_code, note, ingredient_code_2, content, unit,
    insurance_code_2, insurance_code_3, product_name_2, drug_price_2
) VALUES (
    :product_no, :cso_product, :category, :company_name,
    :classification_code_1, :classification_name_1, :classification_code_2, :classification_name_2,
    :insurance_code_a, :insurance_code, :product_name, :ingredient_code, :ingredient_name_en,
    :drug_price, :decided_price, :coverage, :professional_general, :formulation,
    :item_standard_code, :atc_code, :note, :ingredient_code_2, :content, :unit,
    :insurance_code_2, :insurance_code_3, :product_name_2, :drug_price_2
)";

$stmt = $pdo->prepare($sql);

$pdo->beginTransaction();

try {
    foreach ($processed_data as $index => $data) {
        // 필드가 없으면 null로 설정
        $fields = [
            'product_no', 'cso_product', 'category', 'company_name',
            'classification_code_1', 'classification_name_1', 'classification_code_2', 'classification_name_2',
            'insurance_code_a', 'insurance_code', 'product_name', 'ingredient_code', 'ingredient_name_en',
            'drug_price', 'decided_price', 'coverage', 'professional_general', 'formulation',
            'item_standard_code', 'atc_code', 'note', 'ingredient_code_2', 'content', 'unit',
            'insurance_code_2', 'insurance_code_3', 'product_name_2', 'drug_price_2'
        ];
        
        $params = [];
        foreach ($fields as $field) {
            $params[$field] = isset($data[$field]) ? $data[$field] : null;
        }
        
        $stmt->execute($params);
        $total_inserted++;
        
        if (($index + 1) % $batch_size == 0) {
            $batch_count++;
            echo "💾 배치 " . $batch_count . " 완료: " . number_format($index + 1) . " / " . number_format(count($processed_data)) . "\n";
        }
    }
    
    $pdo->commit();
    echo "✅ 데이터 입력 완료!\n";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "❌ 데이터 입력 실패: " . $e->getMessage() . "\n";
    exit(1);
}

// 최종 결과
echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 업로드 완료!\n";
echo "📊 전체 처리 행: " . number_format(count($data_rows)) . "개\n";
echo "✅ 성공적으로 입력: " . number_format($total_inserted) . "개\n";
echo "❌ 실패: " . number_format($failed_rows) . "개\n";
echo str_repeat("=", 50) . "\n";

// 데이터 확인
echo "\n📋 입력된 데이터 샘플:\n";
$stmt = $pdo->query("SELECT product_name, company_name, coverage, drug_price FROM medical_products ORDER BY id LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['product_name'] . " (" . $row['company_name'] . ") " . $row['coverage'] . " " . number_format($row['drug_price']) . "원\n";
}

echo "\n📈 총 의약품 수: " . number_format($pdo->query("SELECT COUNT(*) FROM medical_products")->fetchColumn()) . "개\n";
?> 