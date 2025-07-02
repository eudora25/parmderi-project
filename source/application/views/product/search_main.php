<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : '의료제품 정보 검색'; ?></title>
    <meta name="description" content="<?php echo isset($meta_description) ? $meta_description : '전국 의료제품 정보를 자연어로 쉽게 검색해보세요'; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #48c6ef 0%, #6f86d6 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .search-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            margin-top: 5rem;
            position: relative;
            overflow: hidden;
        }
        
        .search-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #48c6ef, #6f86d6, #a8edea);
        }
        
        .main-title {
            color: #333;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 3rem;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .search-input {
            width: 100%;
            padding: 1.2rem 4rem 1.2rem 1.5rem;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .search-input:focus {
            outline: none;
            border-color: #48c6ef;
            box-shadow: 0 0 0 0.2rem rgba(72, 198, 239, 0.25);
        }
        
        .search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(45deg, #48c6ef, #6f86d6);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 5px 15px rgba(72, 198, 239, 0.4);
        }
        
        .example-queries {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .example-item {
            display: inline-block;
            background: white;
            padding: 0.5rem 1rem;
            margin: 0.3rem;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }
        
        .example-item:hover {
            background: #48c6ef;
            color: white;
            transform: translateY(-2px);
        }
        
        .stats-section {
            display: flex;
            justify-content: space-around;
            margin-top: 2rem;
            text-align: center;
        }
        
        .stat-item {
            flex: 1;
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #48c6ef;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .results-container {
            margin-top: 2rem;
            display: none;
        }
        
        .product-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .product-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .product-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .product-company {
            background: #48c6ef;
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .product-info {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .no-results {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .autocomplete-item {
            padding: 0.8rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .autocomplete-item:hover {
            background: #f8f9fa;
        }
        
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        
        .price-info {
            color: #28a745;
            font-weight: 600;
        }
        
        .classification-info {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .ingredient-info {
            color: #dc3545;
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .search-container {
                margin: 1rem;
                padding: 2rem 1.5rem;
            }
            
            .main-title {
                font-size: 2rem;
            }
            
            .stats-section {
                flex-direction: column;
            }
            
            .stat-item {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="search-container">
                    <h1 class="main-title">
                        <i class="fas fa-pills"></i> 의료제품 검색
                    </h1>
                    <p class="subtitle">자연어로 쉽게 의료제품 정보를 찾아보세요</p>
                    
                    <div class="search-box">
                        <input type="text" 
                               id="searchInput" 
                               class="search-input" 
                               placeholder="예: 타이레놀 500mg 정보 알려줘, 한국유나이티드제약 제품 찾아줘"
                               autocomplete="off">
                        <button id="searchBtn" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                        <div id="autocompleteSuggestions" class="autocomplete-suggestions"></div>
                    </div>
                    
                    <div class="example-queries">
                        <h6><i class="fas fa-lightbulb"></i> 검색 예시:</h6>
                        <span class="example-item" data-query="타이레놀 500mg 정보">타이레놀 500mg 정보</span>
                        <span class="example-item" data-query="한국유나이티드제약 제품">한국유나이티드제약 제품</span>
                        <span class="example-item" data-query="해열제 추천">해열제 추천</span>
                        <span class="example-item" data-query="아세트아미노펜 성분 의약품">아세트아미노펜 성분 의약품</span>
                        <span class="example-item" data-query="급여 적용 항생제">급여 적용 항생제</span>
                        <span class="example-item" data-query="정제 형태 소화제">정제 형태 소화제</span>
                    </div>
                    
                    <div class="stats-section">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($total_products ?? 0); ?></div>
                            <div class="stat-label">등록 제품</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">500+</div>
                            <div class="stat-label">제약회사</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24시간</div>
                            <div class="stat-label">실시간 업데이트</div>
                        </div>
                    </div>
                </div>
                
                <div id="resultsContainer" class="results-container">
                    <div class="search-container">
                        <div id="searchResults"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
    
    <script>
        $(document).ready(function() {
            let searchTimeout;
            
            // 검색 실행
            function performSearch() {
                const query = $('#searchInput').val().trim();
                
                if (!query) {
                    alert('검색어를 입력해주세요.');
                    return;
                }
                
                $('#resultsContainer').show();
                $('#searchResults').html('<div class="loading"><i class="fas fa-spinner fa-spin"></i> 검색 중...</div>');
                
                $.ajax({
                    url: '<?php echo base_url("product_search/search"); ?>',
                    type: 'POST',
                    data: { query: query },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            displayResults(response);
                        } else {
                            $('#searchResults').html('<div class="no-results"><i class="fas fa-exclamation-circle"></i> ' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#searchResults').html('<div class="no-results"><i class="fas fa-exclamation-triangle"></i> 검색 중 오류가 발생했습니다.</div>');
                    }
                });
            }
            
            // 검색 결과 표시
            function displayResults(response) {
                let html = '<h5><i class="fas fa-list"></i> 검색 결과 (' + response.total_count + '개)</h5>';
                html += '<p class="text-muted">' + response.message + '</p>';
                
                if (response.products && response.products.length > 0) {
                    response.products.forEach(function(product) {
                        html += '<div class="product-card" onclick="showProductDetail(' + product.id + ')">';
                        html += '<div class="product-name">' + (product.product_name || '제품명 없음') + '</div>';
                        
                        if (product.company_name) {
                            html += '<span class="product-company">' + product.company_name + '</span>';
                        }
                        
                        html += '<div class="product-info">';
                        
                        if (product.classification_name_1) {
                            html += '<div class="classification-info"><i class="fas fa-tag"></i> ' + product.classification_name_1 + '</div>';
                        }
                        
                        if (product.ingredient_name_en) {
                            html += '<div class="ingredient-info"><i class="fas fa-flask"></i> ' + product.ingredient_name_en + '</div>';
                        }
                        
                        if (product.drug_price) {
                            html += '<div class="price-info"><i class="fas fa-won-sign"></i> ' + parseFloat(product.drug_price).toLocaleString() + '원</div>';
                        }
                        
                        if (product.formulation) {
                            html += '<div><i class="fas fa-pills"></i> ' + product.formulation + '</div>';
                        }
                        
                        html += '</div></div>';
                    });
                } else {
                    html += '<div class="no-results"><i class="fas fa-search"></i> 검색 결과가 없습니다.</div>';
                }
                
                $('#searchResults').html(html);
            }
            
            // 제품 상세 정보 표시
            window.showProductDetail = function(productId) {
                $.ajax({
                    url: '<?php echo base_url("product_search/detail/"); ?>' + productId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showProductModal(response.product);
                        }
                    }
                });
            };
            
            // 제품 상세 모달 표시
            function showProductModal(product) {
                let modalHtml = '<div class="modal fade" id="productModal" tabindex="-1">';
                modalHtml += '<div class="modal-dialog modal-lg">';
                modalHtml += '<div class="modal-content">';
                modalHtml += '<div class="modal-header">';
                modalHtml += '<h5 class="modal-title">' + (product.product_name || '제품 상세 정보') + '</h5>';
                modalHtml += '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
                modalHtml += '</div>';
                modalHtml += '<div class="modal-body">';
                
                if (product.company_name) {
                    modalHtml += '<p><strong>제조사:</strong> ' + product.company_name + '</p>';
                }
                
                if (product.classification_name_1) {
                    modalHtml += '<p><strong>분류:</strong> ' + product.classification_name_1 + '</p>';
                }
                
                if (product.ingredient_name_en) {
                    modalHtml += '<p><strong>성분:</strong> ' + product.ingredient_name_en + '</p>';
                }
                
                if (product.formulation) {
                    modalHtml += '<p><strong>제형:</strong> ' + product.formulation + '</p>';
                }
                
                if (product.drug_price) {
                    modalHtml += '<p><strong>약가:</strong> ' + parseFloat(product.drug_price).toLocaleString() + '원</p>';
                }
                
                if (product.insurance_code) {
                    modalHtml += '<p><strong>보험코드:</strong> ' + product.insurance_code + '</p>';
                }
                
                modalHtml += '</div>';
                modalHtml += '<div class="modal-footer">';
                modalHtml += '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>';
                modalHtml += '</div>';
                modalHtml += '</div></div></div>';
                
                $('body').append(modalHtml);
                $('#productModal').modal('show');
                $('#productModal').on('hidden.bs.modal', function() {
                    $(this).remove();
                });
            }
            
            // 검색 버튼 클릭
            $('#searchBtn').click(performSearch);
            
            // 엔터키 검색
            $('#searchInput').keypress(function(e) {
                if (e.which === 13) {
                    performSearch();
                }
            });
            
            // 예시 쿼리 클릭
            $('.example-item').click(function() {
                const query = $(this).data('query');
                $('#searchInput').val(query);
                performSearch();
            });
            
            // 자동완성
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                const term = $(this).val().trim();
                
                if (term.length < 2) {
                    $('#autocompleteSuggestions').hide();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: '<?php echo base_url("product_search/autocomplete"); ?>',
                        data: { term: term },
                        dataType: 'json',
                        success: function(suggestions) {
                            if (suggestions.length > 0) {
                                let html = '';
                                suggestions.forEach(function(item) {
                                    html += '<div class="autocomplete-item" data-value="' + item.value + '">';
                                    html += '<i class="fas fa-' + (item.type === 'product' ? 'pills' : 'building') + '"></i> ';
                                    html += item.label + '</div>';
                                });
                                $('#autocompleteSuggestions').html(html).show();
                            } else {
                                $('#autocompleteSuggestions').hide();
                            }
                        }
                    });
                }, 300);
            });
            
            // 자동완성 아이템 클릭
            $(document).on('click', '.autocomplete-item', function() {
                $('#searchInput').val($(this).data('value'));
                $('#autocompleteSuggestions').hide();
                performSearch();
            });
            
            // 자동완성 숨기기
            $(document).click(function(e) {
                if (!$(e.target).closest('.search-box').length) {
                    $('#autocompleteSuggestions').hide();
                }
            });
        });
    </script>
</body>
</html> 