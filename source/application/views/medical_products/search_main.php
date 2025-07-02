<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>의약품 검색</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>">
    <style>
        .search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .search-header {
            margin-bottom: 30px;
        }
        .search-filters {
            margin-bottom: 20px;
        }
        .product-card {
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .pagination {
            justify-content: center;
            margin-top: 30px;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="search-container">
        <div class="search-header">
            <h2>의약품 검색</h2>
            <p class="text-muted">제품명, CSO품목, 업체명으로 검색할 수 있습니다.</p>
        </div>

        <div class="search-filters card mb-4">
            <div class="card-body">
                <form id="searchForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="keyword" class="form-label">검색어</label>
                            <input type="text" class="form-control" id="keyword" name="keyword" placeholder="검색어를 입력하세요">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="category" class="form-label">카테고리</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">전체</option>
                                <!-- 카테고리는 JavaScript에서 동적으로 추가됩니다 -->
                            </select>
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">검색</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="searchResults" class="row">
            <!-- 검색 결과가 여기에 동적으로 추가됩니다 -->
        </div>

        <nav id="pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- 페이지네이션이 여기에 동적으로 추가됩니다 -->
            </ul>
        </nav>

        <!-- 로딩 오버레이 -->
        <div id="loadingOverlay" class="loading-overlay">
            <div class="spinner"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('searchForm');
            const searchResults = document.getElementById('searchResults');
            const pagination = document.getElementById('pagination');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const categorySelect = document.getElementById('category');
            
            let currentPage = 1;

            // 초기 검색 실행
            performSearch();

            // 검색 폼 제출 이벤트
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                currentPage = 1;
                performSearch();
            });

            // 검색 실행 함수
            async function performSearch() {
                try {
                    showLoading();

                    const keyword = document.getElementById('keyword').value;
                    const category = document.getElementById('category').value;
                    
                    const response = await fetch(`<?php echo site_url('medical_products/search_ajax'); ?>?keyword=${encodeURIComponent(keyword)}&category=${encodeURIComponent(category)}&page=${currentPage}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('서버 오류가 발생했습니다.');
                    }

                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.message || '검색 중 오류가 발생했습니다.');
                    }

                    // 검색 결과 표시
                    displayResults(data.data.items);
                    
                    // 페이지네이션 생성
                    createPagination(data.data.page, data.data.total_pages);
                    
                    // 카테고리 목록 업데이트
                    updateCategories(data.data.categories);

                } catch (error) {
                    console.error('Search error:', error);
                    showError(error.message);
                } finally {
                    hideLoading();
                }
            }

            // 검색 결과 표시 함수
            function displayResults(items) {
                searchResults.innerHTML = '';
                
                if (items.length === 0) {
                    searchResults.innerHTML = '<div class="col-12"><div class="alert alert-info">검색 결과가 없습니다.</div></div>';
                    return;
                }

                items.forEach(item => {
                    const card = document.createElement('div');
                    card.className = 'col-md-6 col-lg-4';
                    card.innerHTML = `
                        <div class="card product-card">
                            <div class="card-body">
                                <h5 class="card-title">${escapeHtml(item.product_name)}</h5>
                                <h6 class="card-subtitle mb-2 text-muted">${escapeHtml(item.company_name || '')}</h6>
                                <p class="card-text">
                                    <small class="text-muted">CSO품목: ${escapeHtml(item.cso_item || '')}</small><br>
                                    <small class="text-muted">카테고리: ${escapeHtml(item.category || '')}</small>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-primary fw-bold">${numberFormat(item.price)}원</span>
                                    <span class="badge bg-info">${item.commission_rate}%</span>
                                </div>
                            </div>
                        </div>
                    `;
                    searchResults.appendChild(card);
                });
            }

            // 페이지네이션 생성 함수
            function createPagination(currentPage, totalPages) {
                const ul = pagination.querySelector('ul');
                ul.innerHTML = '';

                // 이전 페이지 버튼
                ul.appendChild(createPageItem('이전', currentPage > 1, currentPage - 1));

                // 페이지 번호
                for (let i = 1; i <= totalPages; i++) {
                    ul.appendChild(createPageItem(i, true, i, i === currentPage));
                }

                // 다음 페이지 버튼
                ul.appendChild(createPageItem('다음', currentPage < totalPages, currentPage + 1));
            }

            // 페이지네이션 아이템 생성 함수
            function createPageItem(text, enabled, page, active = false) {
                const li = document.createElement('li');
                li.className = `page-item ${active ? 'active' : ''} ${enabled ? '' : 'disabled'}`;
                
                const a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.textContent = text;

                if (enabled) {
                    a.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (page !== currentPage) {
                            currentPage = page;
                            performSearch();
                        }
                    });
                }

                li.appendChild(a);
                return li;
            }

            // 카테고리 목록 업데이트 함수
            function updateCategories(categories) {
                const currentValue = categorySelect.value;
                categorySelect.innerHTML = '<option value="">전체</option>';
                
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category;
                    option.textContent = category;
                    if (category === currentValue) {
                        option.selected = true;
                    }
                    categorySelect.appendChild(option);
                });
            }

            // 로딩 표시/숨김 함수
            function showLoading() {
                loadingOverlay.style.display = 'flex';
            }

            function hideLoading() {
                loadingOverlay.style.display = 'none';
            }

            // 오류 표시 함수
            function showError(message) {
                searchResults.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger">
                            ${escapeHtml(message)}
                        </div>
                    </div>
                `;
            }

            // HTML 이스케이프 함수
            function escapeHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            // 숫자 포맷 함수
            function numberFormat(num) {
                return new Intl.NumberFormat('ko-KR').format(num);
            }
        });
    </script>
</body>
</html> 
