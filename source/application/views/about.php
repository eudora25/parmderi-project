<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : '서비스 소개'; ?></title>
    <meta name="description" content="<?php echo isset($meta_description) ? $meta_description : '의약품 정보 서비스에 대한 자세한 정보를 확인하세요.'; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .content-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            margin: 5rem auto;
            max-width: 1000px;
        }

        .back-btn {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.3);
        }

        .page-title {
            color: #333;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }

        .section-title {
            color: #4facfe;
            font-size: 1.8rem;
            font-weight: 600;
            margin: 2rem 0 1rem 0;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .feature-list li:before {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #28a745;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content-section">
            <a href="<?php echo base_url(); ?>" class="back-btn">
                <i class="fas fa-arrow-left me-2"></i>홈으로 돌아가기
            </a>
            
            <h1 class="page-title">
                <i class="fas fa-pills me-3"></i>서비스 소개
            </h1>

            <div class="row">
                <div class="col-12">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle me-2"></i>서비스 개요
                    </h2>
                    <p class="lead">
                        의약품 정보 서비스는 전국의 의약품 정보를 체계적으로 관리하고 검색할 수 있는 통합 플랫폼입니다. 
                        자연어 기반 검색을 통해 누구나 쉽게 의약품 정보를 찾을 수 있으며, 
                        데이터 업로드 기능을 통해 효율적인 데이터 관리가 가능합니다.
                    </p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-search me-2"></i>검색 서비스
                    </h2>
                    <ul class="feature-list">
                        <li>자연어 기반 지능형 검색</li>
                        <li>실시간 자동완성 기능</li>
                        <li>제품명, 회사명, 분류별 검색</li>
                        <li>상세한 의약품 정보 제공</li>
                        <li>빠른 검색 속도</li>
                        <li>모바일 친화적 인터페이스</li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-upload me-2"></i>업로드 서비스
                    </h2>
                    <ul class="feature-list">
                        <li>엑셀 파일 일괄 업로드</li>
                        <li>다양한 파일 형식 지원</li>
                        <li>업로드 결과 상세 분석</li>
                        <li>오류 데이터 검증 및 수정</li>
                        <li>안전한 데이터 저장</li>
                        <li>업로드 히스토리 관리</li>
                    </ul>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <h2 class="section-title">
                        <i class="fas fa-cogs me-2"></i>기술적 특징
                    </h2>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <i class="fas fa-database fa-3x text-primary mb-2"></i>
                                <h5>고성능 데이터베이스</h5>
                                <p>최적화된 MariaDB를 사용하여 빠른 검색 성능을 제공합니다.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <i class="fas fa-brain fa-3x text-success mb-2"></i>
                                <h5>AI 기반 분석</h5>
                                <p>자연어 처리 기술로 사용자 의도를 정확히 파악합니다.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <i class="fas fa-shield-alt fa-3x text-warning mb-2"></i>
                                <h5>보안 시스템</h5>
                                <p>안전한 데이터 처리와 백업 시스템을 운영합니다.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line me-2"></i>서비스 통계
                    </h2>
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <h3 class="text-primary">25,000+</h3>
                            <p>등록 의약품</p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h3 class="text-success">100+</h3>
                            <p>제약회사</p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h3 class="text-info">1,000+</h3>
                            <p>일일 검색</p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h3 class="text-warning">99%</h3>
                            <p>정확도</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">
                        <i class="fas fa-rocket me-2"></i>지금 시작하세요
                    </h2>
                    <p class="lead mb-4">의약품 정보 서비스의 강력한 기능을 바로 체험해보세요.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?php echo site_url('medical_products/search'); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i>검색 시작하기
                        </a>
                        <a href="<?php echo site_url('medical_products/upload'); ?>" class="btn btn-success btn-lg">
                            <i class="fas fa-upload me-2"></i>업로드 시작하기
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
</body>
</html> 