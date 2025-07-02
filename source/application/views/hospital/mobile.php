<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>병원 검색 - 모바일</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .mobile-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 1rem;
            text-align: center;
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .app-title {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .search-section {
            padding: 1rem;
        }
        
        .search-box {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .search-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .search-input:focus {
            border-color: #667eea;
        }
        
        .search-btn {
            width: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 0.5rem;
            transition: transform 0.2s;
        }
        
        .search-btn:active {
            transform: scale(0.98);
        }
        
        .quick-search {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .quick-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: #333;
        }
        
        .quick-item {
            display: inline-block;
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            margin: 0.2rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #666;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .quick-item:hover {
            background: #667eea;
            color: white;
            text-decoration: none;
        }
        
        .results {
            padding: 0 1rem;
            display: none;
        }
        
        .result-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.8rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .hospital-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .hospital-info {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.4;
        }
        
        .hospital-category {
            background: #667eea;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .stats-mini {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1rem;
            margin: 1rem;
            color: white;
            text-align: center;
        }
        
        .stats-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .footer {
            text-align: center;
            padding: 2rem 1rem;
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }
        
        /* iOS 스타일 토글 */
        .ios-toggle {
            margin: 1rem;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1rem;
        }
        
        .toggle-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .toggle-item:last-child {
            margin-bottom: 0;
        }
        
        /* 애니메이션 */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .result-card {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* 다크모드 지원 */
        @media (prefers-color-scheme: dark) {
            .search-box, .quick-search, .result-card {
                background: #1a1a1a;
                color: #e9ecef;
            }
            
            .search-input {
                background: #2d2d2d;
                border-color: #444;
                color: #e9ecef;
            }
            
            .quick-item {
                background: #2d2d2d;
                color: #ccc;
            }
        }
    </style>
</head>
<body>
    <!-- 헤더 -->
    <div class="mobile-header">
        <div class="app-title">🏥 병원 검색</div>
    </div>

    <!-- 검색 섹션 -->
    <div class="search-section">
        <div class="search-box">
            <input type="text" id="mobileSearchInput" class="search-input" 
                   placeholder="병원명이나 지역을 입력하세요...">
            <button id="mobileSearchBtn" class="search-btn">검색</button>
        </div>

        <!-- 빠른 검색 -->
        <div class="quick-search">
            <div class="quick-title">🔥 인기 검색</div>
            <a href="#" class="quick-item" data-query="서울 종합병원">서울 종합병원</a>
            <a href="#" class="quick-item" data-query="부산 치과">부산 치과</a>
            <a href="#" class="quick-item" data-query="대구 의원">대구 의원</a>
            <a href="#" class="quick-item" data-query="강남 병원">강남 병원</a>
            <a href="#" class="quick-item" data-query="인천 한의원">인천 한의원</a>
            <a href="#" class="quick-item" data-query="광주 약국">광주 약국</a>
        </div>
    </div>

    <!-- 미니 통계 -->
    <div class="stats-mini">
        <div class="stats-number" id="totalCount">78,233</div>
        <div class="stats-label">전국 의료기관</div>
    </div>

    <!-- 설정 토글 -->
    <div class="ios-toggle">
        <div class="toggle-item">
            <span>📍 위치 기반 검색</span>
            <span>🔄</span>
        </div>
        <div class="toggle-item">
            <span>📊 상세 정보 표시</span>
            <span>✅</span>
        </div>
    </div>

    <!-- 검색 결과 -->
    <div class="results" id="mobileResults">
        <div class="loading" id="mobileLoading">
            🔍 검색 중...
        </div>
        <div id="mobileResultsContent"></div>
    </div>

    <!-- 푸터 -->
    <div class="footer">
        <div>실시간 의료기관 정보 검색</div>
        <div>📱 모바일 최적화 버전</div>
    </div>

    <script>
    class MobileHospitalSearch {
        constructor() {
            this.searchInput = document.getElementById('mobileSearchInput');
            this.searchBtn = document.getElementById('mobileSearchBtn');
            this.results = document.getElementById('mobileResults');
            this.loading = document.getElementById('mobileLoading');
            this.resultsContent = document.getElementById('mobileResultsContent');
            
            this.initEventListeners();
        }
        
        initEventListeners() {
            // 검색 버튼 클릭
            this.searchBtn.addEventListener('click', () => {
                this.performSearch(this.searchInput.value);
            });
            
            // 엔터키 검색
            this.searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.performSearch(this.searchInput.value);
                }
            });
            
            // 빠른 검색 클릭
            document.querySelectorAll('.quick-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const query = item.dataset.query;
                    this.searchInput.value = query;
                    this.performSearch(query);
                });
            });
            
            // 검색 입력시 자동 제안 (간단한 로컬 필터링)
            let timeout;
            this.searchInput.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const value = this.searchInput.value.trim();
                    if (value.length >= 2) {
                        this.showSuggestions(value);
                    }
                }, 300);
            });
        }
        
        async performSearch(query) {
            if (!query.trim()) {
                this.showToast('검색어를 입력해주세요');
                return;
            }
            
            // 검색 결과 영역 표시
            this.results.style.display = 'block';
            this.loading.style.display = 'block';
            this.resultsContent.innerHTML = '';
            
            // 페이지 부드럽게 스크롤
            this.results.scrollIntoView({ behavior: 'smooth' });
            
            try {
                const response = await fetch('<?php echo site_url("hospital_search/search"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `query=${encodeURIComponent(query)}`
                });
                
                const data = await response.json();
                
                this.loading.style.display = 'none';
                
                if (data.success) {
                    this.displayResults(data);
                } else {
                    this.showError(data.message || '검색 중 오류가 발생했습니다');
                }
                
            } catch (error) {
                this.loading.style.display = 'none';
                this.showError('네트워크 오류가 발생했습니다');
                console.error('검색 오류:', error);
            }
        }
        
        displayResults(data) {
            let html = '';
            
            if (data.hospitals && data.hospitals.length > 0) {
                html += `<div style="padding: 1rem; background: rgba(255,255,255,0.1); border-radius: 10px; margin-bottom: 1rem; color: white;">
                    <strong>💡 ${data.message}</strong>
                </div>`;
                
                data.hospitals.forEach(hospital => {
                    html += `
                        <div class="result-card" onclick="showHospitalDetail(${hospital.id})">
                            <div class="hospital-name">${hospital.institution_name}</div>
                            ${hospital.category_name ? `<span class="hospital-category">${hospital.category_name}</span>` : ''}
                            <div class="hospital-info">
                                ${hospital.address ? `📍 ${hospital.address}<br>` : ''}
                                ${hospital.phone_number ? `📞 ${hospital.phone_number}<br>` : ''}
                                ${hospital.sido_name && hospital.sigungu_name ? `🏢 ${hospital.sido_name} ${hospital.sigungu_name}` : ''}
                            </div>
                        </div>
                    `;
                });
            } else {
                html = `
                    <div class="result-card" style="text-align: center; color: #666;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                        <div><strong>검색 결과가 없습니다</strong></div>
                        <div style="margin-top: 0.5rem; font-size: 0.9rem;">다른 검색어로 시도해보세요</div>
                    </div>
                `;
            }
            
            this.resultsContent.innerHTML = html;
        }
        
        showError(message) {
            this.resultsContent.innerHTML = `
                <div class="result-card" style="text-align: center; color: #666;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                    <div><strong>오류가 발생했습니다</strong></div>
                    <div style="margin-top: 0.5rem; font-size: 0.9rem;">${message}</div>
                </div>
            `;
        }
        
        showSuggestions(term) {
            // 간단한 로컬 제안 (실제로는 서버에서 가져와야 함)
            const suggestions = [
                '서울대병원', '삼성서울병원', '세브란스병원', '아산병원', 
                '서울 종합병원', '부산 치과', '대구 의원', '강남 병원'
            ].filter(item => item.includes(term));
            
            // 제안 표시 로직 (간단히 구현)
            console.log('제안:', suggestions);
        }
        
        showToast(message) {
            // 간단한 토스트 메시지
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
                background: rgba(0,0,0,0.8); color: white; padding: 0.8rem 1.5rem;
                border-radius: 20px; z-index: 1000; font-size: 0.9rem;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 2000);
        }
    }
    
    // 병원 상세 정보 표시
    function showHospitalDetail(hospitalId) {
        // 진동 피드백 (모바일에서)
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
        
        // 새 창에서 상세 정보 열기
        window.open(`<?php echo site_url("hospital_search/detail"); ?>/${hospitalId}`, '_blank');
    }
    
    // 앱 초기화
    document.addEventListener('DOMContentLoaded', () => {
        new MobileHospitalSearch();
        
        // PWA 스타일 상태바 색상 (모바일)
        const meta = document.createElement('meta');
        meta.name = 'theme-color';
        meta.content = '#667eea';
        document.head.appendChild(meta);
        
        // 실시간 통계 업데이트
        updateStats();
        setInterval(updateStats, 30000); // 30초마다 업데이트
    });
    
    // 통계 업데이트
    async function updateStats() {
        try {
            // 실제로는 API에서 가져와야 함
            const totalElement = document.getElementById('totalCount');
            if (totalElement) {
                // 숫자 카운터 애니메이션 효과
                animateNumber(totalElement, 78233);
            }
        } catch (error) {
            console.log('통계 업데이트 실패:', error);
        }
    }
    
    // 숫자 애니메이션
    function animateNumber(element, target) {
        const start = parseInt(element.textContent.replace(/,/g, '')) || 0;
        const increment = (target - start) / 20;
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString();
        }, 50);
    }
    </script>
</body>
</html> 