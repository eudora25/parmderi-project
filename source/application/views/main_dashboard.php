<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #34a853;
            --accent-color: #ea4335;
            --background-color: #f8f9fa;
            --text-color: #202124;
            --gray-color: #5f6368;
        }

        body {
            font-family: 'Noto Sans KR', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color) !important;
        }

        .search-section {
            background: white;
            padding: 4rem 0;
            text-align: center;
        }

        .search-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 2rem;
        }

        .search-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--text-color);
        }

        .search-box {
            position: relative;
            margin-bottom: 2rem;
        }

        .search-input {
            width: 100%;
            padding: 1.5rem;
            padding-left: 4rem;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(26, 115, 232, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
            font-size: 1.5rem;
        }

        .quick-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .quick-link {
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            background: #e8f0fe;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .quick-link:hover {
            background: var(--primary-color);
            color: white;
        }

        .features-section {
            padding: 4rem 0;
            background: white;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .feature-description {
            color: var(--gray-color);
            font-size: 1rem;
            line-height: 1.6;
        }

        .stats-section {
            background: #f8f9fa;
            padding: 4rem 0;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-color);
            font-size: 1rem;
        }

        footer {
            background: white;
            padding: 2rem 0;
            border-top: 1px solid #e0e0e0;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-links a {
            color: var(--gray-color);
            margin-left: 1.5rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="#">의약품 정보 시스템</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">홈</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">의약품 검색</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">병원 찾기</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">도움말</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="search-section">
        <div class="container">
            <div class="search-container">
                <h1 class="search-title">무엇을 도와드릴까요?</h1>
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="의약품 이름, 증상, 또는 질문을 입력하세요">
                </div>
                <div class="quick-links">
                    <a href="#" class="quick-link">의약품 검색</a>
                    <a href="<?php echo base_url('hospital_search'); ?>" class="quick-link">병원 찾기</a>
                    <a href="#" class="quick-link">부작용 정보</a>
                    <a href="#" class="quick-link">복용 방법</a>
                </div>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fas fa-pills feature-icon"></i>
                        <h3 class="feature-title">의약품 정보 검색</h3>
                        <p class="feature-description">
                            의약품의 효능, 용법, 주의사항 등 상세한 정보를 쉽게 찾아보세요.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fas fa-hospital feature-icon"></i>
                        <h3 class="feature-title">병원 찾기</h3>
                        <p class="feature-description">전국의 병원 정보를 쉽게 검색하고 찾아보세요. 위치, 진료과목, 운영시간 등 상세 정보를 제공합니다.</p>
                        <a href="<?php echo base_url('hospital_search'); ?>" class="btn btn-primary mt-3">병원 검색하기</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fas fa-comments feature-icon"></i>
                        <h3 class="feature-title">Q&A 상담</h3>
                        <p class="feature-description">
                            의약품과 관련된 궁금증을 자연어로 질문하고 답변받으세요.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">50,000+</div>
                        <div class="stat-label">등록된 의약품</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">10,000+</div>
                        <div class="stat-label">등록된 병원</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">30,000+</div>
                        <div class="stat-label">일일 검색</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">95%</div>
                        <div class="stat-label">사용자 만족도</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-copyright">
                    © 2024 의약품 정보 시스템. All rights reserved.
                </div>
                <div class="footer-links">
                    <a href="#">이용약관</a>
                    <a href="#">개인정보처리방침</a>
                    <a href="#">고객센터</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
