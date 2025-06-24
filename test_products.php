<?php
// 의약품 데이터 테스트 페이지

// CodeIgniter 없이 직접 데이터베이스 연결
$host = 'parmderi_mariadb';
$port = '3306';
$dbname = 'htest';
$username = 'root';
$password = 'root123';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🧪 의약품 데이터 테스트</h1>";
    
    // 총 개수 확인
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM medical_products");
    $total = $stmt->fetchColumn();
    echo "<h3>📊 총 의약품 수: " . number_format($total) . "개</h3>";
    
    if ($total > 0) {
        // 샘플 데이터 조회
        echo "<h3>📋 샘플 데이터 (상위 10개)</h3>";
        $stmt = $pdo->query("
            SELECT product_name, company_name, coverage, drug_price, classification_name_1, cso_product
            FROM medical_products 
            WHERE product_name IS NOT NULL AND product_name != ''
            ORDER BY id LIMIT 10
        ");
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>
                <th style='padding: 8px;'>제품명</th>
                <th style='padding: 8px;'>제약회사</th>
                <th style='padding: 8px;'>분류</th>
                <th style='padding: 8px;'>급여</th>
                <th style='padding: 8px;'>가격</th>
                <th style='padding: 8px;'>CSO</th>
              </tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['product_name']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['company_name']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['classification_name_1']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['coverage']) . "</td>";
            echo "<td style='padding: 8px;'>" . number_format($row['drug_price']) . "원</td>";
            echo "<td style='padding: 8px;'>" . ($row['cso_product'] ? 'CSO' : '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 통계 정보
        echo "<h3>📈 통계 정보</h3>";
        
        // CSO 품목 수
        $stmt = $pdo->query("SELECT COUNT(*) FROM medical_products WHERE cso_product = 1");
        $cso_count = $stmt->fetchColumn();
        echo "<p>🟡 CSO 품목: " . number_format($cso_count) . "개</p>";
        
        // 급여/비급여 통계
        $stmt = $pdo->query("SELECT coverage, COUNT(*) as count FROM medical_products WHERE coverage != '' GROUP BY coverage ORDER BY count DESC");
        echo "<p>💊 급여 구분:</p><ul>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>" . htmlspecialchars($row['coverage']) . ": " . number_format($row['count']) . "개</li>";
        }
        echo "</ul>";
        
        // 상위 제약회사
        $stmt = $pdo->query("SELECT company_name, COUNT(*) as count FROM medical_products WHERE company_name != '' GROUP BY company_name ORDER BY count DESC LIMIT 10");
        echo "<p>🏭 상위 제약회사:</p><ul>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>" . htmlspecialchars($row['company_name']) . ": " . number_format($row['count']) . "개</li>";
        }
        echo "</ul>";
        
        // 검색 테스트
        echo "<h3>🔍 검색 테스트</h3>";
        
        $search_keyword = '아스피린';
        $stmt = $pdo->prepare("
            SELECT product_name, company_name, drug_price 
            FROM medical_products 
            WHERE product_name LIKE ? 
            ORDER BY product_name 
            LIMIT 5
        ");
        $stmt->execute(["%$search_keyword%"]);
        
        echo "<p>검색어: '$search_keyword'</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'><th style='padding: 8px;'>제품명</th><th style='padding: 8px;'>제약회사</th><th style='padding: 8px;'>가격</th></tr>";
        
        $found = false;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $found = true;
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['product_name']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($row['company_name']) . "</td>";
            echo "<td style='padding: 8px;'>" . number_format($row['drug_price']) . "원</td>";
            echo "</tr>";
        }
        
        if (!$found) {
            echo "<tr><td colspan='3' style='padding: 8px; text-align: center;'>검색 결과가 없습니다.</td></tr>";
        }
        echo "</table>";
        
        echo "<hr>";
        echo "<h3>✅ 데이터 업로드 성공!</h3>";
        echo "<p>🔗 <a href='/products'>의약품 검색 페이지로 이동</a></p>";
        echo "<p>🔗 <a href='/products/upload'>데이터 업로드 페이지로 이동</a></p>";
        
    } else {
        echo "<h3>❌ 데이터가 없습니다.</h3>";
        echo "<p>엑셀 파일을 업로드해주세요.</p>";
        echo "<p>🔗 <a href='/products/upload'>데이터 업로드 페이지로 이동</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<h3>❌ 데이터베이스 연결 오류</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 