<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : '데이터 업로드 메인'; ?></title>
    <link href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            padding: 50px 0;
        }
        
        .upload-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .upload-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px rgba(0,0,0,0.15);
        }
        
        .card-header {
            padding: 30px;
            text-align: center;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
        }
        
        .card-header.product {
            background: linear-gradient(45deg, #2196F3, #1976D2);
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 40px 30px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            color: #666;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li i {
            color: #4CAF50;
            margin-right: 10px;
        }
        
        .upload-btn {
            width: 100%;
            padding: 15px;
            font-size: 1.2rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-hospital {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
        }
        
        .btn-hospital:hover {
            background: linear-gradient(45deg, #45a049, #3d8b40);
            color: white;
            text-decoration: none;
        }
        
        .btn-product {
            background: linear-gradient(45deg, #2196F3, #1976D2);
            color: white;
        }
        
        .btn-product:hover {
            background: linear-gradient(45deg, #1976D2, #1565C0);
            color: white;
            text-decoration: none;
        }
        
        .main-title {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }
        
        .main-title h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .main-title p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .stats-section {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 30px;
            margin-top: 50px;
            text-align: center;
            color: white;
        }
        
        .stats-item {
            margin: 20px 0;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            display: block;
        }
        
        .stats-label {
            font-size: 1rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <!-- 메인 타이틀 -->
        <div class="main-title">
            <h1>📊 데이터 업로드 센터</h1>
            <p>의료기관 정보와 의료제품 데이터를 쉽게 업로드하세요</p>
        </div>
        
        <div class="row">
            <!-- 의료기관 업로드 카드 -->
            <div class="col-md-6">
                <div class="upload-card">
                    <div class="card-header">
                        <div class="icon">🏥</div>
                        <h3>의료기관 데이터 업로드</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">병원, 의원, 클리닉 등 의료기관 정보를 업로드합니다.</p>
                        
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> 기관명, 주소, 전화번호 필수</li>
                            <li><i class="fas fa-check"></i> 엑셀(.xlsx) 파일 지원</li>
                            <li><i class="fas fa-check"></i> 자동 중복 검사</li>
                            <li><i class="fas fa-check"></i> 일괄 업로드 가능</li>
                            <li><i class="fas fa-check"></i> 업로드 결과 통계 제공</li>
                        </ul>
                        
                        <div class="mt-4">
                            <strong>지원 형식:</strong><br>
                            <small class="text-muted">
                                • 요양기관명, 주소, 전화번호<br>
                                • 기관명, 소재지, 연락처<br>
                                • 병원명, 주소, TEL
                            </small>
                        </div>
                        
                        <div class="mt-4">
                            <a href="<?php echo base_url('excel_upload'); ?>" class="upload-btn btn-hospital">
                                🏥 의료기관 업로드 시작
                            </a>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small>
                                <a href="<?php echo base_url('create_sample_medical_file.php'); ?>" class="text-muted">
                                    📋 샘플 파일 다운로드
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 의료제품 업로드 카드 -->
            <div class="col-md-6">
                <div class="upload-card">
                    <div class="card-header product">
                        <div class="icon">💊</div>
                        <h3>의료제품 데이터 업로드</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">의약품, 의료기기 등 의료제품 정보를 업로드합니다.</p>
                        
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> 14개 핵심 컬럼만 처리</li>
                            <li><i class="fas fa-check"></i> 자동 데이터 검증</li>
                            <li><i class="fas fa-check"></i> 수수료율 자동 계산</li>
                            <li><i class="fas fa-check"></i> 제품 분류 자동화</li>
                            <li><i class="fas fa-check"></i> 상세 업로드 로그</li>
                        </ul>
                        
                        <div class="mt-4">
                            <strong>사용 컬럼:</strong><br>
                            <small class="text-muted">
                                B(CSO품목), D(구분), E(업체명), F(분류번호)<br>
                                J(보험코드), K(제품명), L(약가), M(성분명)<br>
                                P(제형), Q(성분코드), T(함량), U(단위)<br>
                                V(ATC코드), AQ(수수료율)
                            </small>
                        </div>
                        
                        <div class="mt-4">
                            <a href="<?php echo base_url('medical_products/upload'); ?>" class="upload-btn btn-product">
                                💊 의료제품 업로드 시작
                            </a>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small>
                                <a href="<?php echo base_url('medical_products'); ?>" class="text-muted">
                                    📊 제품 검색/관리
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 통계 섹션 -->
        <div class="stats-section">
            <h4>📈 시스템 현황</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="stats-item">
                        <span class="stats-number">2</span>
                        <div class="stats-label">업로드 타입</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-item">
                        <span class="stats-number">14</span>
                        <div class="stats-label">제품 처리 컬럼</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-item">
                        <span class="stats-number">100%</span>
                        <div class="stats-label">자동화 처리</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 추가 도구들 -->
        <div class="text-center mt-5">
            <h5 style="color: white; margin-bottom: 20px;">🔧 추가 도구</h5>
            <div class="row">
                <div class="col-md-4">
                    <a href="<?php echo base_url('excel_debug'); ?>" class="btn btn-outline-light btn-sm">
                        🔍 파일 구조 분석
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo base_url('hospital_search'); ?>" class="btn btn-outline-light btn-sm">
                        🏥 의료기관 검색
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo base_url('product_search'); ?>" class="btn btn-outline-light btn-sm">
                        💊 제품 자연어 검색
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
</body>
</html> 