<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : '병원 정보 검색'; ?></title>
    <meta name="description" content="<?php echo isset($meta_description) ? $meta_description : '전국 의료기관 정보를 자연어로 쉽게 검색해보세요'; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #3b82f6;
            --accent-color: #60a5fa;
            --background-color: #f3f4f6;
            --text-color: #1f2937;
            --gray-color: #6b7280;
        }

        body {
            font-family: 'Noto Sans KR', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            min-height: 100vh;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: 700;
        }
        
        .search-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: 2rem;
            position: relative;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .search-input {
            width: 100%;
            padding: 1rem 8rem 1rem 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .search-buttons {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 0.5rem;
        }

        .search-btn {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .search-btn:hover {
            background: var(--secondary-color);
        }

        .search-btn-secondary {
            background: #6b7280;
            border: none;
            border-radius: 8px;
            padding: 0.5rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .search-btn-secondary:hover {
            background: #4b5563;
        }

        .filter-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: var(--gray-color);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .hospital-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .hospital-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .hospital-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .hospital-tags {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .hospital-tag {
            background: #f3f4f6;
            color: var(--gray-color);
            padding: 0.2rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
        }

        .hospital-info {
            display: flex;
            align-items: center;
            gap: 2rem;
            color: var(--gray-color);
            font-size: 0.9rem;
        }

        .hospital-info i {
            color: var(--primary-color);
            width: 16px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid #e5e7eb;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-color);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .search-container {
                margin-top: 1rem;
                padding: 1.5rem;
            }

            .hospital-info {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }
        }

        .analysis-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .analysis-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .analysis-header h5 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .btn-close-analysis {
            background: none;
            border: none;
            color: var(--gray-color);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .btn-close-analysis:hover {
            background: #e2e8f0;
            color: var(--text-color);
        }

        .analysis-content {
            font-size: 0.9rem;
        }

        .analysis-row {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .analysis-item {
            flex: 1;
            min-width: 200px;
        }

        .analysis-item strong {
            color: var(--text-color);
            display: block;
            margin-bottom: 0.25rem;
        }

        .confidence-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .confidence-high {
            background-color: #10b981;
            color: white;
        }

        .confidence-medium {
            background-color: #f59e0b;
            color: white;
        }

        .confidence-low {
            background-color: #ef4444;
            color: white;
        }

        .elements-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.25rem;
        }

        .element-tag {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .suggestion-section, .related-section, .action-section {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .suggestion-section h6, .related-section h6, .action-section h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        .suggestion-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .suggestion-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
        }

        .suggestion-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .suggestion-title {
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9rem;
        }

        .suggestion-description {
            color: var(--gray-color);
            font-size: 0.8rem;
            margin-bottom: 0.75rem;
        }

        .suggestion-query {
            background: #f3f4f6;
            padding: 0.5rem;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            border-left: 3px solid var(--primary-color);
        }

        .suggestion-options {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .option-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .option-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .related-queries {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .related-query {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .related-query:hover {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }

        .quick-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <!-- 네비게이션 바 -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="<?php echo base_url(); ?>">
                <i class="fas fa-hospital me-2"></i>병원 검색
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url(); ?>">홈</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('hospital_search/stats'); ?>">통계</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- 검색 섹션 -->
        <div class="search-container">
            <h1 class="h3 mb-4 text-center">전국 병원 찾기</h1>
            <div class="search-box">
                <input type="text" class="search-input" placeholder="병원 이름, 지역, 진료과목으로 검색" id="searchInput">
                <div class="search-buttons">
                    <button class="search-btn-secondary" onclick="getLocation()" title="내 위치로 검색">
                        <i class="fas fa-location-crosshairs"></i>
                    </button>
                    <button class="search-btn-secondary" onclick="analyzeQuestion()" title="질문 분석">
                        <i class="fas fa-brain"></i>
                    </button>
                    <button class="search-btn" onclick="performSearch()">
                        <i class="fas fa-search"></i> 검색
                    </button>
                </div>
            </div>

            <!-- 필터 섹션 -->
            <div class="filter-section">
                <button class="filter-btn active">전체</button>
                <button class="filter-btn">내과</button>
                <button class="filter-btn">외과</button>
                <button class="filter-btn">소아과</button>
                <button class="filter-btn">치과</button>
                <button class="filter-btn">안과</button>
                <button class="filter-btn">이비인후과</button>
            </div>

            <!-- 질문 분석 결과 표시 섹션 -->
            <div id="analysisResults" class="analysis-section" style="display: none;">
                <div class="analysis-header">
                    <h5><i class="fas fa-brain"></i> 질문 분석 결과</h5>
                    <button class="btn-close-analysis" onclick="hideAnalysis()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="analysis-content">
                    <div class="analysis-row">
                        <div class="analysis-item">
                            <strong>검색 의도:</strong>
                            <span id="searchIntent">-</span>
                        </div>
                        <div class="analysis-item">
                            <strong>신뢰도:</strong>
                            <span id="confidenceScore" class="confidence-badge">-</span>
                        </div>
                    </div>
                    <div class="analysis-row">
                        <div class="analysis-item">
                            <strong>추출된 요소:</strong>
                            <div id="extractedElements" class="elements-list">-</div>
                        </div>
                    </div>
                    <div class="analysis-row">
                        <div class="analysis-item">
                            <strong>검색 전략:</strong>
                            <span id="searchStrategy">-</span>
                        </div>
                    </div>
                    
                    <!-- 개선 제안 섹션 -->
                    <div id="suggestionSection" class="suggestion-section" style="display: none;">
                        <h6><i class="fas fa-lightbulb"></i> 검색 개선 제안</h6>
                        <div id="suggestions" class="suggestions-list"></div>
                    </div>
                    
                    <!-- 관련 검색어 섹션 -->
                    <div id="relatedSection" class="related-section" style="display: none;">
                        <h6><i class="fas fa-tags"></i> 관련 검색어</h6>
                        <div id="relatedQueries" class="related-queries"></div>
                    </div>
                    
                    <!-- 빠른 액션 섹션 -->
                    <div id="actionSection" class="action-section" style="display: none;">
                        <h6><i class="fas fa-bolt"></i> 빠른 작업</h6>
                        <div id="quickActions" class="quick-actions"></div>
                    </div>
                </div>
            </div>

            <!-- 검색 결과 -->
            <div id="searchResults">
                <!-- 결과는 JavaScript로 동적 로드 -->
            </div>
        </div>

        <!-- 통계 섹션 -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_hospitals); ?></div>
                <div class="stat-label">등록된 병원</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">24/7</div>
                <div class="stat-label">응급실 운영</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">98%</div>
                <div class="stat-label">사용자 만족도</div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function performSearch() {
            const searchInput = document.getElementById('searchInput');
            const query = searchInput.value.trim();
            
            if (!query) return;

            // 검색 로직 구현
            fetch(`<?php echo base_url('hospital_search/search'); ?>`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `query=${encodeURIComponent(query)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.hospitals);
                    // 질문 분석 결과 표시
                    if (data.analysis) {
                        displayAnalysis(data.analysis, data.question_type);
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('검색 중 오류 발생:', error);
                alert('검색 중 오류가 발생했습니다.');
            });
        }

        function analyzeQuestion() {
            const searchInput = document.getElementById('searchInput');
            const query = searchInput.value.trim();
            
            if (!query) {
                alert('분석할 질문을 입력해주세요.');
                return;
            }

            // 질문 분석만 수행
            fetch(`<?php echo base_url('hospital_search/analyze'); ?>`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `query=${encodeURIComponent(query)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAnalysis(data.analysis);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('분석 중 오류 발생:', error);
                alert('분석 중 오류가 발생했습니다.');
            });
        }

        function displayAnalysis(analysis, questionType) {
            const analysisSection = document.getElementById('analysisResults');
            
            // 검색 의도 표시
            document.getElementById('searchIntent').textContent = 
                analysis.interpretation?.intent || '알 수 없음';
            
            // 신뢰도 점수 표시
            const confidenceElement = document.getElementById('confidenceScore');
            const score = analysis.confidence_score || 0;
            confidenceElement.textContent = `${score}점`;
            
            // 신뢰도에 따른 클래스 적용
            confidenceElement.className = 'confidence-badge';
            if (score >= 70) {
                confidenceElement.classList.add('confidence-high');
            } else if (score >= 40) {
                confidenceElement.classList.add('confidence-medium');
            } else {
                confidenceElement.classList.add('confidence-low');
            }
            
            // 추출된 요소들 표시
            const elementsContainer = document.getElementById('extractedElements');
            const elements = analysis.interpretation?.extracted_elements || [];
            
            if (elements.length > 0) {
                elementsContainer.innerHTML = elements.map(element => 
                    `<span class="element-tag">${element}</span>`
                ).join('');
            } else {
                elementsContainer.innerHTML = '<span class="text-muted">추출된 요소가 없습니다</span>';
            }
            
            // 검색 전략 표시
            const strategy = analysis.search_strategy;
            if (strategy) {
                document.getElementById('searchStrategy').innerHTML = 
                    `<strong>${strategy.method}</strong>: ${strategy.description}`;
            } else {
                document.getElementById('searchStrategy').textContent = '정보 없음';
            }
            
            // 개선 제안 표시
            displaySuggestions(analysis.suggestions || []);
            
            // 관련 검색어 표시
            displayRelatedQueries(analysis.related_queries || []);
            
            // 빠른 액션 표시
            displayQuickActions(analysis.quick_actions || []);
            
            // 분석 결과 섹션 표시
            analysisSection.style.display = 'block';
            
            // 부드러운 스크롤 효과
            analysisSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function displaySuggestions(suggestions) {
            const suggestionSection = document.getElementById('suggestionSection');
            const suggestionsContainer = document.getElementById('suggestions');
            
            if (suggestions.length === 0) {
                suggestionSection.style.display = 'none';
                return;
            }
            
            suggestionSection.style.display = 'block';
            
            suggestionsContainer.innerHTML = suggestions.map(suggestion => {
                let content = `
                    <div class="suggestion-card" onclick="applySuggestion('${suggestion.type}', '${suggestion.suggested_query || ''}')">
                        <div class="suggestion-header">
                            <i class="${suggestion.icon}"></i>
                            <span class="suggestion-title">${suggestion.title}</span>
                        </div>
                        <div class="suggestion-description">${suggestion.description}</div>
                `;
                
                if (suggestion.suggested_query) {
                    content += `<div class="suggestion-query">${suggestion.suggested_query}</div>`;
                }
                
                if (suggestion.options) {
                    content += `<div class="suggestion-options">`;
                    suggestion.options.forEach(option => {
                        content += `<span class="option-btn" onclick="event.stopPropagation(); searchWithQuery('${option.query}')">${option.label}</span>`;
                    });
                    content += `</div>`;
                }
                
                content += `</div>`;
                return content;
            }).join('');
        }

        function displayRelatedQueries(queries) {
            const relatedSection = document.getElementById('relatedSection');
            const queriesContainer = document.getElementById('relatedQueries');
            
            if (queries.length === 0) {
                relatedSection.style.display = 'none';
                return;
            }
            
            relatedSection.style.display = 'block';
            
            queriesContainer.innerHTML = queries.map(query => 
                `<span class="related-query" onclick="searchWithQuery('${query}')">${query}</span>`
            ).join('');
        }

        function displayQuickActions(actions) {
            const actionSection = document.getElementById('actionSection');
            const actionsContainer = document.getElementById('quickActions');
            
            if (actions.length === 0) {
                actionSection.style.display = 'none';
                return;
            }
            
            actionSection.style.display = 'block';
            
            actionsContainer.innerHTML = actions.map(action => 
                `<button class="action-btn" onclick="${action.action}()">
                    <i class="${action.icon}"></i>
                    ${action.label}
                </button>`
            ).join('');
        }

        function applySuggestion(type, query) {
            if (query) {
                searchWithQuery(query);
            }
        }

        function searchWithQuery(query) {
            document.getElementById('searchInput').value = query;
            performSearch();
        }

        // 빠른 액션 함수들
        function openMap() {
            const query = document.getElementById('searchInput').value;
            const mapUrl = `https://map.naver.com/v5/search/${encodeURIComponent(query)}`;
            window.open(mapUrl, '_blank');
        }

        function findSimilar() {
            const currentQuery = document.getElementById('searchInput').value;
            const similarQuery = currentQuery.replace(/특정 병원명/g, '') + ' 병원';
            searchWithQuery(similarQuery.trim());
        }

        function bookmarkQuery() {
            const query = document.getElementById('searchInput').value;
            if (!query) {
                alert('저장할 검색어가 없습니다.');
                return;
            }
            
            let bookmarks = JSON.parse(localStorage.getItem('hospitalSearchBookmarks') || '[]');
            if (!bookmarks.includes(query)) {
                bookmarks.unshift(query);
                bookmarks = bookmarks.slice(0, 10);
                localStorage.setItem('hospitalSearchBookmarks', JSON.stringify(bookmarks));
                alert('검색어가 저장되었습니다.');
            } else {
                alert('이미 저장된 검색어입니다.');
            }
        }

        function showHistory() {
            const bookmarks = JSON.parse(localStorage.getItem('hospitalSearchBookmarks') || '[]');
            if (bookmarks.length === 0) {
                alert('저장된 검색 기록이 없습니다.');
                return;
            }
            
            const historyHtml = bookmarks.map(query => 
                `<div class="related-query" onclick="searchWithQuery('${query}')">${query}</div>`
            ).join('');
            
            const historySection = document.getElementById('relatedSection');
            document.getElementById('relatedQueries').innerHTML = historyHtml;
            historySection.style.display = 'block';
            
            historySection.querySelector('h6').innerHTML = '<i class="fas fa-history"></i> 저장된 검색어';
        }

        function hideAnalysis() {
            document.getElementById('analysisResults').style.display = 'none';
        }

        function displayResults(hospitals) {
            const resultsContainer = document.getElementById('searchResults');
            resultsContainer.innerHTML = '';

            if (hospitals.length === 0) {
                resultsContainer.innerHTML = '<p class="text-center text-muted my-5">검색 결과가 없습니다.</p>';
                return;
            }

            hospitals.forEach(hospital => {
                const card = document.createElement('div');
                card.className = 'hospital-card';
                card.innerHTML = `
                    <h3 class="hospital-name">${hospital.institution_name || '이름 없음'}</h3>
                    <div class="hospital-tags">
                        <span class="hospital-tag">${hospital.category_name || '분류 없음'}</span>
                        <span class="hospital-tag">${hospital.specialty_name || '진료과목 없음'}</span>
                    </div>
                    <div class="hospital-info">
                        <span><i class="fas fa-map-marker-alt"></i> ${hospital.address || '주소 없음'}</span>
                        <span><i class="fas fa-phone"></i> ${hospital.phone_number || '전화번호 없음'}</span>
                        <span><i class="fas fa-clock"></i> ${hospital.business_hours || '운영시간 정보 없음'}</span>
                    </div>
                `;
                resultsContainer.appendChild(card);
            });
        }

        // 필터 버튼 이벤트
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelector('.filter-btn.active').classList.remove('active');
                this.classList.add('active');
            });
        });

        // 검색창 엔터 이벤트
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    performNearbySearch(lat, lng);
                }, function(error) {
                    alert('위치 정보 사용이 거부되었거나 오류가 발생했습니다.');
                });
            } else {
                alert('이 브라우저에서는 위치 정보가 지원되지 않습니다.');
            }
        }

        function performNearbySearch(lat, lng) {
            fetch(`<?php echo base_url('hospital_search/search'); ?>`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `lat=${lat}&lng=${lng}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.hospitals);
                    if (data.message) {
                        alert(data.message);
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('근처 병원 검색 중 오류 발생:', error);
                alert('근처 병원 검색 중 오류가 발생했습니다.');
            });
        }
    </script>
</body>
</html>
