<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : '병원 정보 검색'; ?></title>
    <meta name="description" content="<?php echo isset($meta_description) ? $meta_description : '전국 의료기관 정보를 자연어로 쉽게 검색해보세요'; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
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
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(45deg, #667eea, #764ba2);
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
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
            background: #667eea;
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
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .results-container {
            margin-top: 2rem;
            display: none;
        }
        
        .hospital-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .hospital-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .hospital-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .hospital-category {
            background: #667eea;
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .hospital-info {
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
            border: 1px solid #e9ecef;
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .autocomplete-item {
            padding: 0.8rem 1.5rem;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .autocomplete-item:hover,
        .autocomplete-item.active {
            background: #f8f9fa;
        }
        
        .recent-searches {
            margin-top: 2rem;
        }
        
        .recent-search-item {
            display: inline-block;
            background: #e9ecef;
            padding: 0.4rem 0.8rem;
            margin: 0.2rem;
            border-radius: 15px;
            font-size: 0.85rem;
            color: #666;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .recent-search-item:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="search-container">
                    <!-- 메인 타이틀 -->
                    <h1 class="main-title">
                        <i class="fas fa-hospital-alt"></i> 병원 정보 검색
                    </h1>
                    <p class="subtitle">
                        "삼성서울병원 위치", "강남 치과", "부산 종합병원" 처럼 자연스럽게 물어보세요
                    </p>

                    <!-- 검색 박스 -->
                    <div class="search-box">
                        <input type="text" id="searchInput" class="search-input" 
                               placeholder="예: 서울아산병원 전화번호, 강남 치과, 부산 종합병원 리스트"
                               autocomplete="off">
                        <button type="button" id="searchBtn" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                        <div class="autocomplete-suggestions" id="autocompleteSuggestions"></div>
                    </div>

                    <!-- 예시 검색어 -->
                    <div class="example-queries">
                        <h6 class="mb-3"><i class="fas fa-lightbulb"></i> 이렇게 검색해보세요</h6>
                        <div class="example-item" data-query="서울대병원 위치">서울대병원 위치</div>
                        <div class="example-item" data-query="강남 치과">강남 치과</div>
                        <div class="example-item" data-query="부산 종합병원">부산 종합병원</div>
                        <div class="example-item" data-query="삼성서울병원 전화번호">삼성서울병원 전화번호</div>
                        <div class="example-item" data-query="대구 의원">대구 의원</div>
                        <div class="example-item" data-query="세브란스병원 홈페이지">세브란스병원 홈페이지</div>
                        <div class="example-item" data-query="경기도 한의원">경기도 한의원</div>
                        <div class="example-item" data-query="아산병원 개원일">아산병원 개원일</div>
                    </div>

                    <!-- 통계 정보 -->
                    <div class="stats-section">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($total_hospitals ?? 0); ?></div>
                            <div class="stat-label">전국 의료기관</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">17</div>
                            <div class="stat-label">시도</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">검색 서비스</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">실시간</div>
                            <div class="stat-label">정보 업데이트</div>
                        </div>
                    </div>

                    <!-- 최근 검색어 -->
                    <div class="recent-searches">
                        <h6 class="mb-2"><i class="fas fa-clock"></i> 인기 검색어</h6>
                        <?php if (isset($recent_searches) && !empty($recent_searches)): ?>
                            <?php foreach ($recent_searches as $search): ?>
                                <span class="recent-search-item" data-query="<?php echo htmlspecialchars($search); ?>">
                                    <?php echo htmlspecialchars($search); ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- 검색 결과 -->
                    <div class="results-container" id="resultsContainer">
                        <div class="loading" id="loadingIndicator">
                            <i class="fas fa-spinner fa-spin"></i> 검색 중...
                        </div>
                        <div class="results-content" id="resultsContent"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const resultsContainer = document.getElementById('resultsContainer');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const resultsContent = document.getElementById('resultsContent');
        const autocompleteSuggestions = document.getElementById('autocompleteSuggestions');
        
        let autocompleteTimeout;
        let currentSearch = '';

        // 검색 실행
        function performSearch(query) {
            if (!query.trim()) {
                alert('검색어를 입력해주세요.');
                return;
            }

            currentSearch = query;
            resultsContainer.style.display = 'block';
            loadingIndicator.style.display = 'block';
            resultsContent.innerHTML = '';
            hideAutocomplete();

            fetch('<?php echo site_url("hospital_search/search"); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'query=' + encodeURIComponent(query)
            })
            .then(response => response.json())
            .then(data => {
                loadingIndicator.style.display = 'none';
                
                if (data.success) {
                    displayResults(data);
                } else {
                    resultsContent.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-exclamation-circle"></i><br>
                            ${data.message || '검색 결과가 없습니다.'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                loadingIndicator.style.display = 'none';
                resultsContent.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-triangle"></i><br>
                        검색 중 오류가 발생했습니다.
                    </div>
                `;
                console.error('검색 오류:', error);
            });
        }

        // 검색 결과 표시
        function displayResults(data) {
            let html = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> ${data.message}
                </div>
            `;

            if (data.hospitals && data.hospitals.length > 0) {
                data.hospitals.forEach(hospital => {
                    html += `
                        <div class="hospital-card" data-id="${hospital.id}">
                            <div class="hospital-name">${hospital.institution_name}</div>
                            ${hospital.category_name ? `<span class="hospital-category">${hospital.category_name}</span>` : ''}
                            <div class="hospital-info">
                                ${hospital.address ? `<div><i class="fas fa-map-marker-alt"></i> ${hospital.address}</div>` : ''}
                                ${hospital.phone_number ? `<div><i class="fas fa-phone"></i> ${hospital.phone_number}</div>` : ''}
                                ${hospital.sido_name && hospital.sigungu_name ? `<div><i class="fas fa-location-dot"></i> ${hospital.sido_name} ${hospital.sigungu_name}</div>` : ''}
                                ${hospital.establishment_date ? `<div><i class="fas fa-calendar"></i> 개원일: ${hospital.establishment_date}</div>` : ''}
                                ${hospital.homepage_url ? `<div><i class="fas fa-globe"></i> <a href="${hospital.homepage_url}" target="_blank">홈페이지</a></div>` : ''}
                            </div>
                        </div>
                    `;
                });
            } else {
                html += `
                    <div class="no-results">
                        <i class="fas fa-search"></i><br>
                        검색 결과가 없습니다.<br>
                        <small>다른 검색어로 시도해보세요.</small>
                    </div>
                `;
            }

            resultsContent.innerHTML = html;
            
            // 병원 카드 클릭 이벤트
            document.querySelectorAll('.hospital-card').forEach(card => {
                card.addEventListener('click', function() {
                    const hospitalId = this.dataset.id;
                    showHospitalDetail(hospitalId);
                });
            });
        }

        // 병원 상세 정보 표시
        function showHospitalDetail(hospitalId) {
            // 상세 정보 모달 또는 새 페이지로 이동
            window.open(`<?php echo site_url("hospital_search/detail"); ?>/${hospitalId}`, '_blank');
        }

        // 자동완성
        function showAutocomplete(suggestions) {
            if (suggestions.length === 0) {
                hideAutocomplete();
                return;
            }

            let html = '';
            suggestions.forEach(suggestion => {
                html += `
                    <div class="autocomplete-item" data-value="${suggestion.value}">
                        <i class="fas fa-${suggestion.type === 'hospital' ? 'hospital' : 'map-marker-alt'}"></i>
                        ${suggestion.label}
                    </div>
                `;
            });

            autocompleteSuggestions.innerHTML = html;
            autocompleteSuggestions.style.display = 'block';

            // 자동완성 항목 클릭
            document.querySelectorAll('.autocomplete-item').forEach(item => {
                item.addEventListener('click', function() {
                    const value = this.dataset.value;
                    searchInput.value = value;
                    hideAutocomplete();
                    performSearch(value);
                });
            });
        }

        function hideAutocomplete() {
            autocompleteSuggestions.style.display = 'none';
        }

        // 이벤트 리스너
        searchBtn.addEventListener('click', function() {
            performSearch(searchInput.value);
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch(this.value);
            }
        });

        // 자동완성 처리
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (query.length < 2) {
                hideAutocomplete();
                return;
            }

            clearTimeout(autocompleteTimeout);
            autocompleteTimeout = setTimeout(() => {
                fetch(`<?php echo site_url("hospital_search/autocomplete"); ?>?term=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(suggestions => {
                    showAutocomplete(suggestions);
                })
                .catch(error => {
                    console.error('자동완성 오류:', error);
                });
            }, 300);
        });

        // 예시 검색어 클릭
        document.querySelectorAll('.example-item, .recent-search-item').forEach(item => {
            item.addEventListener('click', function() {
                const query = this.dataset.query;
                searchInput.value = query;
                performSearch(query);
            });
        });

        // 외부 클릭시 자동완성 숨기기
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !autocompleteSuggestions.contains(e.target)) {
                hideAutocomplete();
            }
        });

        // 초기 포커스
        searchInput.focus();
    });
    </script>
</body>
</html> 