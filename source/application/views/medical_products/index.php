<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? html_escape($title) : '의약품 관리'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .card {
            width: 100%;
            max-width: 500px;
        }
        .card-body a {
            text-decoration: none;
        }
        .card-body .d-grid {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="card shadow-sm">
        <div class="card-header text-center">
            <h1 class="h3 mb-0"><?php echo isset($title) ? html_escape($title) : '의약품 관리 시스템'; ?></h1>
        </div>
        <div class="card-body p-4">
            <div class="d-grid">
                <a href="<?php echo site_url('medical_products/search'); ?>" class="btn btn-primary btn-lg">
                    의약품 검색
                </a>
            </div>
            <div class="d-grid">
                <a href="<?php echo site_url('medical_products/upload'); ?>" class="btn btn-success btn-lg">
                    데이터 업로드
                </a>
            </div>
        </div>
    </div>
</body>
</html> 