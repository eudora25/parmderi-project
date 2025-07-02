<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : '전국 의료기관 통계'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .stats-header {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 3rem 0;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .stats-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .stats-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            border: none;
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            padding: 1.5rem;
        }
        
        .top-regions {
            list-group-flush;
        }
        
        .region-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .region-name {
            font-weight: 500;
            color: #333;
        }
        
        .region-count {
            background: #667eea;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        
        .progress-bar-custom {
            background: linear-gradient(45deg, #667eea, #764ba2);
            height: 8px;
            border-radius: 4px;
            margin: 0.5rem 0;
        }
        
        .category-list {
            padding: 1.5rem;
        }
        
        .category-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            margin: 1rem;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        .back-btn i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- 헤더 -->
    <div class="stats-header">
        <div class="container">
            <h1 class="stats-title">
                <i class="fas fa-chart-bar"></i> 전국 의료기관 통계
            </h1>
            <p class="stats-subtitle">
                대한민국 의료기관 현황을 한눈에 확인하세요
            </p>
            <a href="<?php echo site_url('hospital'); ?>" class="back-btn">
                <i class="fas fa-search"></i> 병원 검색하기
            </a>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- 전체 통계 -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-globe-asia"></i> 전국 현황
                        </h2>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($recent_stats['total_count'] ?? 0); ?></div>
                            <div class="stat-label">전체 의료기관</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">17</div>
                            <div class="stat-label">광역시도</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($region_stats ?? []); ?></div>
                            <div class="stat-label">운영 지역</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($category_stats ?? []); ?></div>
                            <div class="stat-label">의료기관 종류</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($recent_stats['recent_openings'] ?? 0); ?></div>
                            <div class="stat-label">최근 1년 개원</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">검색 서비스</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 지역별 통계 차트 -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-map-marked-alt"></i> 지역별 의료기관 분포
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="regionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 상위 지역 목록 -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-trophy"></i> 상위 지역 TOP 10
                        </h3>
                    </div>
                    <div class="top-regions">
                        <?php if (isset($region_stats) && !empty($region_stats)): ?>
                            <?php 
                            $max_count = $region_stats[0]->count ?? 1;
                            $rank = 1;
                            ?>
                            <?php foreach (array_slice($region_stats, 0, 10) as $region): ?>
                                <div class="region-item">
                                    <div>
                                        <div class="region-name">
                                            <?php echo $rank; ?>. <?php echo htmlspecialchars($region->sido_name); ?>
                                        </div>
                                        <div class="progress-bar-custom" 
                                             style="width: <?php echo ($region->count / $max_count) * 100; ?>%"></div>
                                    </div>
                                    <span class="region-count"><?php echo number_format($region->count); ?>개</span>
                                </div>
                                <?php $rank++; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="region-item">
                                <div class="region-name">데이터를 불러오는 중...</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 의료기관 종류별 통계 -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-hospital"></i> 의료기관 종류별 분포
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 종류별 상세 목록 -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i> 종류별 상세 현황
                        </h3>
                    </div>
                    <div class="category-list">
                        <?php if (isset($category_stats) && !empty($category_stats)): ?>
                            <?php foreach ($category_stats as $category): ?>
                                <div class="category-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($category->category_name); ?></strong>
                                        <div style="font-size: 0.9rem; color: #666;">
                                            전체의 <?php echo round(($category->count / ($recent_stats['total_count'] ?? 1)) * 100, 1); ?>%
                                        </div>
                                    </div>
                                    <span class="region-count"><?php echo number_format($category->count); ?>개</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="category-item">
                                <div>데이터를 불러오는 중...</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- API 정보 -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-code"></i> API 정보
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-link"></i> API 엔드포인트</h5>
                                <p><strong>검색 API:</strong></p>
                                <code><?php echo site_url('hospital/api'); ?>?q=검색어</code>
                                
                                <p class="mt-3"><strong>예시:</strong></p>
                                <code><?php echo site_url('hospital/api'); ?>?q=서울 종합병원</code>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-info-circle"></i> 사용법</h5>
                                <ul>
                                    <li>GET 방식으로 요청</li>
                                    <li>q 파라미터에 검색어 입력</li>
                                    <li>JSON 형태로 응답</li>
                                    <li>CORS 지원으로 외부 사이트에서 사용 가능</li>
                                </ul>
                                <a href="<?php echo site_url('hospital/api'); ?>?q=서울병원" 
                                   class="btn btn-primary" target="_blank">
                                    <i class="fas fa-play"></i> API 테스트
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
    
    <script>
    // 지역별 차트
    const regionCtx = document.getElementById('regionChart').getContext('2d');
    const regionChart = new Chart(regionCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php if (isset($region_stats) && !empty($region_stats)): ?>
                    <?php foreach (array_slice($region_stats, 0, 10) as $region): ?>
                        '<?php echo addslashes($region->sido_name); ?>',
                    <?php endforeach; ?>
                <?php endif; ?>
            ],
            datasets: [{
                label: '의료기관 수',
                data: [
                    <?php if (isset($region_stats) && !empty($region_stats)): ?>
                        <?php foreach (array_slice($region_stats, 0, 10) as $region): ?>
                            <?php echo $region->count; ?>,
                        <?php endforeach; ?>
                    <?php endif; ?>
                ],
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + '개';
                        }
                    }
                }
            }
        }
    });

    // 종류별 차트 (도넛형)
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php if (isset($category_stats) && !empty($category_stats)): ?>
                    <?php foreach (array_slice($category_stats, 0, 8) as $category): ?>
                        '<?php echo addslashes($category->category_name); ?>',
                    <?php endforeach; ?>
                <?php endif; ?>
            ],
            datasets: [{
                data: [
                    <?php if (isset($category_stats) && !empty($category_stats)): ?>
                        <?php foreach (array_slice($category_stats, 0, 8) as $category): ?>
                            <?php echo $category->count; ?>,
                        <?php endforeach; ?>
                    <?php endif; ?>
                ],
                backgroundColor: [
                    '#667eea', '#764ba2', '#f093fb', '#f5576c',
                    '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });

    // 실시간 업데이트 표시
    setInterval(function() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('ko-KR');
        const elements = document.querySelectorAll('.last-update');
        elements.forEach(el => {
            if (el) el.textContent = '마지막 업데이트: ' + timeString;
        });
    }, 60000);
    </script>
</body>
</html> 