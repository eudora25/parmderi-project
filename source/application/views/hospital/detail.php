<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : '병원 정보'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        
        .hospital-header {
            text-align: center;
        }
        
        .hospital-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .hospital-category {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
            font-size: 1rem;
        }
        
        .detail-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: -2rem;
            position: relative;
            z-index: 10;
        }
        
        .info-section {
            padding: 2rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            color: #667eea;
            margin-right: 0.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
            color: #333;
            word-break: break-all;
        }
        
        .map-container {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 1rem;
        }
        
        .back-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .back-btn i {
            margin-right: 0.5rem;
        }
        
        .contact-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .contact-btn {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        .btn-call {
            background: #28a745;
            color: white;
        }
        
        .btn-call:hover {
            background: #218838;
            color: white;
            text-decoration: none;
        }
        
        .btn-website {
            background: #17a2b8;
            color: white;
        }
        
        .btn-website:hover {
            background: #138496;
            color: white;
            text-decoration: none;
        }
        
        .no-data {
            color: #999;
            font-style: italic;
        }
        
        .highlight {
            background: linear-gradient(120deg, #a8edea 0%, #fed6e3 100%);
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- 헤더 섹션 -->
    <div class="header-section">
        <div class="container">
            <div class="hospital-header">
                <h1 class="hospital-name"><?php echo htmlspecialchars($hospital->institution_name); ?></h1>
                <?php if ($hospital->category_name): ?>
                    <span class="hospital-category"><?php echo htmlspecialchars($hospital->category_name); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- 뒤로가기 버튼 -->
        <a href="<?php echo site_url('hospital'); ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> 검색으로 돌아가기
        </a>

        <div class="detail-container">
            <!-- 기본 정보 섹션 -->
            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i> 기본 정보
                </h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">의료기관명</div>
                        <div class="info-value highlight"><?php echo htmlspecialchars($hospital->institution_name); ?></div>
                    </div>
                    
                    <?php if ($hospital->category_name): ?>
                    <div class="info-item">
                        <div class="info-label">기관 분류</div>
                        <div class="info-value"><?php echo htmlspecialchars($hospital->category_name); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($hospital->establishment_date): ?>
                    <div class="info-item">
                        <div class="info-label">개설일</div>
                        <div class="info-value"><?php echo date('Y년 m월 d일', strtotime($hospital->establishment_date)); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <div class="info-label">등록 상태</div>
                        <div class="info-value">
                            <span style="color: #28a745;">
                                <i class="fas fa-check-circle"></i> 정상 운영
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 위치 정보 섹션 -->
            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-map-marker-alt"></i> 위치 정보
                </h2>
                <div class="info-grid">
                    <?php if ($hospital->address): ?>
                    <div class="info-item">
                        <div class="info-label">주소</div>
                        <div class="info-value"><?php echo htmlspecialchars($hospital->address); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($hospital->postal_code): ?>
                    <div class="info-item">
                        <div class="info-label">우편번호</div>
                        <div class="info-value"><?php echo htmlspecialchars($hospital->postal_code); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($hospital->sido_name): ?>
                    <div class="info-item">
                        <div class="info-label">시도</div>
                        <div class="info-value"><?php echo htmlspecialchars($hospital->sido_name); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($hospital->sigungu_name): ?>
                    <div class="info-item">
                        <div class="info-label">시군구</div>
                        <div class="info-value"><?php echo htmlspecialchars($hospital->sigungu_name); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($hospital->eupmyeondong): ?>
                    <div class="info-item">
                        <div class="info-label">읍면동</div>
                        <div class="info-value"><?php echo htmlspecialchars($hospital->eupmyeondong); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- 지도 (추후 구현 가능) -->
                <?php if ($hospital->address): ?>
                <div class="map-container" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #666;">
                    <div style="text-align: center;">
                        <i class="fas fa-map fa-3x" style="color: #dee2e6; margin-bottom: 1rem;"></i><br>
                        <strong><?php echo htmlspecialchars($hospital->address); ?></strong><br>
                        <small>지도 API 연동으로 위치를 확인할 수 있습니다</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- 연락처 정보 섹션 -->
            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-phone"></i> 연락처 정보
                </h2>
                <div class="info-grid">
                    <?php if ($hospital->phone_number): ?>
                    <div class="info-item">
                        <div class="info-label">전화번호</div>
                        <div class="info-value highlight"><?php echo htmlspecialchars($hospital->phone_number); ?></div>
                        <div class="contact-actions">
                            <a href="tel:<?php echo htmlspecialchars($hospital->phone_number); ?>" class="contact-btn btn-call">
                                <i class="fas fa-phone"></i> 전화걸기
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="info-item">
                        <div class="info-label">전화번호</div>
                        <div class="info-value no-data">정보 없음</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($hospital->homepage_url): ?>
                    <div class="info-item">
                        <div class="info-label">홈페이지</div>
                        <div class="info-value"><?php echo htmlspecialchars($hospital->homepage_url); ?></div>
                        <div class="contact-actions">
                            <a href="<?php echo htmlspecialchars($hospital->homepage_url); ?>" target="_blank" class="contact-btn btn-website">
                                <i class="fas fa-globe"></i> 홈페이지 방문
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="info-item">
                        <div class="info-label">홈페이지</div>
                        <div class="info-value no-data">정보 없음</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 추가 정보 섹션 -->
            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-clipboard-list"></i> 추가 정보
                </h2>
                <div class="info-grid">
                    <?php if ($hospital->category_code): ?>
                    <div class="info-item">
                        <div class="info-label">종별코드</div>
                        <div class="info-value"><?php echo htmlspecialchars($hospital->category_code); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($hospital->sido_code): ?>
                    <div class="info-item">
                        <div class="info-label">시도코드</div>
                        <div class="info-value"><?php echo htmlspecialchars($hospital->sido_code); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($hospital->sigungu_code): ?>
                    <div class="info-item">
                        <div class="info-label">시군구코드</div>
                        <div class="info-value"><?php echo htmlspecialchars($hospital->sigungu_code); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <div class="info-label">정보 업데이트</div>
                        <div class="info-value"><?php echo date('Y년 m월 d일', strtotime($hospital->updated_at)); ?></div>
                    </div>
                </div>
            </div>

            <!-- 액션 버튼들 -->
            <div class="info-section" style="text-align: center; border-bottom: none;">
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="<?php echo site_url('hospital'); ?>" class="back-btn">
                        <i class="fas fa-search"></i> 다른 병원 검색
                    </a>
                    
                    <?php if ($hospital->sido_name): ?>
                    <a href="<?php echo site_url('hospital/region/' . $hospital->sido_code); ?>" class="back-btn" style="background: linear-gradient(45deg, #28a745, #20c997);">
                        <i class="fas fa-list"></i> <?php echo htmlspecialchars($hospital->sido_name); ?> 병원 목록
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo site_url('hospital/stats'); ?>" class="back-btn" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                        <i class="fas fa-chart-bar"></i> 통계 보기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
    
    <script>
    // 전화번호 포맷팅
    document.addEventListener('DOMContentLoaded', function() {
        // 클립보드 복사 기능 (전화번호, 주소 등)
        const copyButtons = document.querySelectorAll('.info-value');
        
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const text = this.textContent.trim();
                if (text && text !== '정보 없음') {
                    navigator.clipboard.writeText(text).then(() => {
                        // 간단한 피드백
                        const original = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check"></i> 복사됨!';
                        this.style.color = '#28a745';
                        
                        setTimeout(() => {
                            this.innerHTML = original;
                            this.style.color = '';
                        }, 1500);
                    }).catch(err => {
                        console.log('복사 실패:', err);
                    });
                }
            });
        });
    });
    </script>
</body>
</html> 