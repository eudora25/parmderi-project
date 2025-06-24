<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .search-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }
        .search-box {
            max-width: 600px;
            margin: 0 auto;
        }
        .stat-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .product-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- 검색 헤더 -->
    <div class="search-hero">
        <div class="container">
            <div class="text-center mb-4">
                <h1><i class="fas fa-pills me-2"></i>의약품 검색 시스템</h1>
                <p class="lead">25,000+ 의약품 정보를 빠르게 검색하세요</p>
            </div>
            
            <div class="search-box">
                <form id="searchForm">
                    <div class="input-group input-group-lg">
                        <input type="text" id="searchInput" class="form-control" 
                               placeholder="의약품명, 회사명, 성분명을 입력하세요..." 
                               autocomplete="off">
                        <button class="btn btn-warning" type="submit">
                            <i class="fas fa-search"></i> 검색
                        </button>
                    </div>
                </form>
                
                <!-- 자동완성 -->
                <div id="searchSuggestions" class="list-group position-absolute w-100" style="z-index: 1000; display: none;">
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="<?= base_url('products/upload') ?>" class="btn btn-outline-light me-2">
                    <i class="fas fa-upload me-1"></i>데이터 업로드
                </a>
                <a href="<?= base_url('products/statistics') ?>" class="btn btn-outline-light">
                    <i class="fas fa-chart-bar me-1"></i>통계 보기
                </a>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <!-- 통계 카드 -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-pills fa-2x text-primary mb-2"></i>
                        <h5><?= isset($stats['total_products']) ? number_format($stats['total_products']) : '0' ?></h5>
                        <small class="text-muted">전체 의약품</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-star fa-2x text-warning mb-2"></i>
                        <h5><?= isset($stats['cso_products']) ? number_format($stats['cso_products']) : '0' ?></h5>
                        <small class="text-muted">CSO 품목</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h5><?= isset($stats['coverage']['급여']) ? number_format($stats['coverage']['급여']) : '0' ?></h5>
                        <small class="text-muted">급여 의약품</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <i class="fas fa-building fa-2x text-info mb-2"></i>
                        <h5><?= isset($stats['top_companies']) ? count($stats['top_companies']) : '0' ?></h5>
                        <small class="text-muted">제약회사</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 필터 -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>검색 필터</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">제약회사</label>
                                <select id="companyFilter" class="form-select">
                                    <option value="">전체</option>
                                    <?php if (isset($companies)): ?>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?= htmlspecialchars($company) ?>"><?= htmlspecialchars($company) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">분류</label>
                                <select id="classificationFilter" class="form-select">
                                    <option value="">전체</option>
                                    <?php if (isset($classifications)): ?>
                                        <?php foreach ($classifications as $classification): ?>
                                            <option value="<?= htmlspecialchars($classification['classification_code_1']) ?>">
                                                <?= htmlspecialchars($classification['classification_name_1']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">급여구분</label>
                                <select id="coverageFilter" class="form-select">
                                    <option value="">전체</option>
                                    <option value="급여">급여</option>
                                    <option value="비급여">비급여</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">기타</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="csoOnlyFilter">
                                    <label class="form-check-label" for="csoOnlyFilter">
                                        CSO 품목만 보기
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 검색 결과 -->
        <div id="searchResults">
            <div class="text-center text-muted py-5">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h5>검색어를 입력해서 의약품을 찾아보세요</h5>
                <p>제품명, 회사명, 성분명으로 검색할 수 있습니다.</p>
            </div>
        </div>

        <!-- 로딩 -->
        <div id="loading" class="loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">검색 중...</span>
            </div>
            <p class="mt-2">검색 중...</p>
        </div>

        <!-- 페이지네이션 -->
        <div id="pagination" class="d-flex justify-content-center mt-4" style="display: none !important;">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentPage = 1;
        let searchTimeout;

        // 검색 폼 이벤트
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch(1);
        });

        // 실시간 검색
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    loadAutoComplete(query);
                    performSearch(1);
                }, 300);
            } else {
                hideSuggestions();
                if (query.length === 0) {
                    showDefaultContent();
                }
            }
        });

        // 필터 변경 이벤트
        ['companyFilter', 'classificationFilter', 'coverageFilter', 'csoOnlyFilter'].forEach(id => {
            document.getElementById(id).addEventListener('change', () => {
                if (document.getElementById('searchInput').value.trim()) {
                    performSearch(1);
                }
            });
        });

        // 검색 수행
        async function performSearch(page = 1) {
            const query = document.getElementById('searchInput').value.trim();
            
            if (!query) {
                showDefaultContent();
                return;
            }

            showLoading();
            currentPage = page;

            const params = new URLSearchParams({
                q: query,
                page: page,
                limit: 20,
                company: document.getElementById('companyFilter').value,
                classification: document.getElementById('classificationFilter').value,
                coverage: document.getElementById('coverageFilter').value,
                cso_only: document.getElementById('csoOnlyFilter').checked ? '1' : '0'
            });

            try {
                const response = await fetch(`<?= base_url('products/search') ?>?${params}`);
                const result = await response.json();

                hideLoading();

                if (result.success) {
                    displayResults(result.data, result.pagination);
                } else {
                    showError('검색 중 오류가 발생했습니다.');
                }
            } catch (error) {
                hideLoading();
                showError('서버 연결 오류가 발생했습니다.');
            }
        }

        // 자동완성 로드
        async function loadAutoComplete(query) {
            try {
                const response = await fetch(`<?= base_url('products/autocomplete') ?>?q=${encodeURIComponent(query)}`);
                const suggestions = await response.json();
                showSuggestions(suggestions);
            } catch (error) {
                console.error('자동완성 오류:', error);
            }
        }

        // 자동완성 표시
        function showSuggestions(suggestions) {
            const container = document.getElementById('searchSuggestions');
            
            if (suggestions.length === 0) {
                hideSuggestions();
                return;
            }

            container.innerHTML = suggestions.map(suggestion => 
                `<button type="button" class="list-group-item list-group-item-action" 
                         onclick="selectSuggestion('${suggestion}')">${suggestion}</button>`
            ).join('');
            
            container.style.display = 'block';
        }

        // 자동완성 숨기기
        function hideSuggestions() {
            document.getElementById('searchSuggestions').style.display = 'none';
        }

        // 자동완성 선택
        function selectSuggestion(suggestion) {
            document.getElementById('searchInput').value = suggestion;
            hideSuggestions();
            performSearch(1);
        }

        // 결과 표시
        function displayResults(products, pagination) {
            const container = document.getElementById('searchResults');
            
            if (products.length === 0) {
                container.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h5>검색 결과가 없습니다</h5>
                        <p>다른 검색어로 시도해보세요.</p>
                    </div>
                `;
                hidePagination();
                return;
            }

            let html = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>검색 결과 (${pagination.total_count.toLocaleString()}개)</h5>
                    <span class="text-muted">${pagination.current_page} / ${pagination.total_pages} 페이지</span>
                </div>
            `;

            products.forEach(product => {
                html += `
                    <div class="product-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-1">${product.product_name || ''}</h6>
                                <div class="text-muted mb-2">
                                    <i class="fas fa-building me-1"></i>${product.company_name || ''}
                                    ${product.classification_name_1 ? `<span class="badge bg-light text-dark ms-2">${product.classification_name_1}</span>` : ''}
                                </div>
                                <div class="small text-muted">
                                    ${product.ingredient_name_en ? `<div><i class="fas fa-flask me-1"></i>${product.ingredient_name_en}</div>` : ''}
                                    ${product.insurance_code ? `<div><i class="fas fa-barcode me-1"></i>보험코드: ${product.insurance_code}</div>` : ''}
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="mb-2">
                                    ${product.drug_price ? `<h6 class="text-primary">${Number(product.drug_price).toLocaleString()}원</h6>` : ''}
                                    ${getCoverageBadge(product.coverage)}
                                    ${product.cso_product == 1 ? '<span class="badge bg-warning ms-1">CSO</span>' : ''}
                                </div>
                                <div>
                                    ${product.formulation ? `<small class="text-muted">${product.formulation}</small>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
            updatePagination(pagination);
        }

        // 급여 구분 뱃지
        function getCoverageBadge(coverage) {
            switch (coverage) {
                case '급여':
                    return '<span class="badge bg-success">급여</span>';
                case '비급여':
                    return '<span class="badge bg-danger">비급여</span>';
                default:
                    return coverage ? `<span class="badge bg-secondary">${coverage}</span>` : '';
            }
        }

        // 페이지네이션 업데이트
        function updatePagination(pagination) {
            const container = document.getElementById('pagination');
            
            if (pagination.total_pages <= 1) {
                hidePagination();
                return;
            }

            let html = '<ul class="pagination">';
            
            // 이전 페이지
            if (pagination.current_page > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="performSearch(${pagination.current_page - 1})">이전</a></li>`;
            }
            
            // 페이지 번호
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="performSearch(${i})">${i}</a>
                </li>`;
            }
            
            // 다음 페이지
            if (pagination.current_page < pagination.total_pages) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="performSearch(${pagination.current_page + 1})">다음</a></li>`;
            }
            
            html += '</ul>';
            container.innerHTML = html;
            container.style.display = 'flex';
        }

        // 기본 콘텐츠 표시
        function showDefaultContent() {
            document.getElementById('searchResults').innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h5>검색어를 입력해서 의약품을 찾아보세요</h5>
                    <p>제품명, 회사명, 성분명으로 검색할 수 있습니다.</p>
                </div>
            `;
            hidePagination();
        }

        // 로딩 표시/숨기기
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('searchResults').style.display = 'none';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('searchResults').style.display = 'block';
        }

        // 페이지네이션 숨기기
        function hidePagination() {
            document.getElementById('pagination').style.display = 'none';
        }

        // 오류 표시
        function showError(message) {
            document.getElementById('searchResults').innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>${message}
                </div>
            `;
            hidePagination();
        }

        // 클릭 외부 영역 처리
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-box')) {
                hideSuggestions();
            }
        });
    </script>
</body>
</html> 