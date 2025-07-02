<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>업로드 결과 - 의료제품 엑셀 업로드</title>
    <link href="<?= base_url('assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .result-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        .success-icon {
            color: #28a745;
            font-size: 4rem;
        }
        .error-icon {
            color: #dc3545;
            font-size: 4rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        .btn-custom {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background: linear-gradient(45deg, #0056b3, #004085);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="result-container">
                    <div class="text-center">
                        <?php if (isset($success) && $success): ?>
                            <i class="fas fa-check-circle success-icon"></i>
                            <h2 class="text-success mt-3">의료제품 업로드 완료!</h2>
                        <?php else: ?>
                            <i class="fas fa-exclamation-triangle error-icon"></i>
                            <h2 class="text-danger mt-3">의료제품 업로드 실패</h2>
                        <?php endif; ?>
                    </div>

                    <!-- 성공 메시지 -->
                    <?php if (isset($message) && !empty($message)): ?>
                        <div class="success-message">
                            <i class="fas fa-info-circle"></i> <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <!-- 오류 메시지 -->
                    <?php if (isset($error_message) && !empty($error_message)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>

                    <!-- 통계 정보 -->
                    <?php if (isset($success) && $success): ?>
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="stats-number"><?= isset($total_rows) ? number_format($total_rows) : 0 ?></div>
                                    <div class="mt-2">총 데이터 행</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                    <div class="stats-number"><?= isset($success_rows) ? number_format($success_rows) : 0 ?></div>
                                    <div class="mt-2">성공</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                                    <div class="stats-number"><?= isset($skipped_rows) ? number_format($skipped_rows) : 0 ?></div>
                                    <div class="mt-2">건너뜀</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);">
                                    <div class="stats-number"><?= isset($failed_rows) ? number_format($failed_rows) : 0 ?></div>
                                    <div class="mt-2">실패</div>
                                </div>
                            </div>
                        </div>

                        <!-- 성공률 표시 -->
                        <?php 
                        $total = isset($total_rows) ? $total_rows : 0;
                        $success_count = isset($success_rows) ? $success_rows : 0;
                        $success_rate = ($total - ($skipped_rows ?? 0)) > 0 ? round($success_count / ($total - ($skipped_rows ?? 0)) * 100, 1) : 0;
                        ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>처리 성공률 (건너뛴 행 제외): <?= $success_rate ?>%</h5>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?= $success_rate ?>%" 
                                         aria-valuenow="<?= $success_rate ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?= $success_rate ?>%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 처리 요약 -->
                        <div class="mt-4 p-3" style="background: #f8f9fa; border-radius: 5px;">
                            <h6><i class="fas fa-chart-pie"></i> 처리 요약</h6>
                            <ul class="mb-0">
                                <li><strong>성공적으로 처리:</strong> <?= number_format($success_count) ?>개 제품</li>
                                <?php if (isset($skipped_rows) && $skipped_rows > 0): ?>
                                    <li class="text-warning"><strong>건너뛴 행:</strong> <?= number_format($skipped_rows) ?>개 행 (필수 데이터 누락)</li>
                                <?php endif; ?>
                                <?php if (isset($failed_rows) && $failed_rows > 0): ?>
                                    <li class="text-danger"><strong>처리 실패:</strong> <?= number_format($failed_rows) ?>개 행 (데이터베이스 오류)</li>
                                <?php endif; ?>
                                <?php if (isset($filename)): ?>
                                    <li><strong>처리된 파일:</strong> <?= htmlspecialchars($filename) ?></li>
                                <?php endif; ?>
                                <?php if (isset($processing_time)): ?>
                                    <li><strong>처리 시간:</strong> <?= $processing_time ?>초</li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <!-- 처리된 컬럼 정보 -->
                        <div class="mt-4 p-3" style="background: #e3f2fd; border-radius: 5px; border-left: 4px solid #2196f3;">
                            <h6><i class="fas fa-table"></i> 처리된 컬럼 정보</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small>
                                        <ul class="mb-0">
                                            <li><strong>B열:</strong> CSO품목</li>
                                            <li><strong>D열:</strong> 구분</li>
                                            <li><strong>E열:</strong> 업체명</li>
                                            <li><strong>F열:</strong> 분류번호</li>
                                            <li><strong>J열:</strong> 보험코드</li>
                                            <li><strong>K열:</strong> 제품명</li>
                                            <li><strong>L열:</strong> 약가</li>
                                        </ul>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small>
                                        <ul class="mb-0">
                                            <li><strong>M열:</strong> 성분명(영문)</li>
                                            <li><strong>P열:</strong> 제형</li>
                                            <li><strong>Q열:</strong> 성분코드</li>
                                            <li><strong>T열:</strong> 함량</li>
                                            <li><strong>U열:</strong> 단위</li>
                                            <li><strong>V열:</strong> ATC코드</li>
                                            <li><strong>AQ열:</strong> 수수료율</li>
                                        </ul>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- 처리된 컬럼 정보 -->
                        <div class="mt-4 p-3" style="background: #e3f2fd; border-radius: 5px; border-left: 4px solid #2196f3;">
                            <h6><i class="fas fa-info-circle"></i> 처리 상세 정보</h6>
                             <?php if (isset($message_detail) && !empty($message_detail)): ?>
                                <p class="mb-0"><?= htmlspecialchars($message_detail) ?></p>
                            <?php endif; ?>
                            <p class="mb-0">처리된 컬럼: 제품명, 보험코드, 제조사, 분류 등</p>
                        </div>
                    <?php endif; ?>

                    <!-- 액션 버튼 -->
                    <div class="text-center mt-4">
                        <a href="<?= base_url('medical_products/upload') ?>" class="btn btn-custom me-3">
                            <i class="fas fa-upload"></i> 다시 업로드
                        </a>
                        <a href="<?= base_url('medical_products') ?>" class="btn btn-outline-info me-3">
                            <i class="fas fa-search"></i> 제품 검색
                        </a>
                        <a href="<?= base_url('upload') ?>" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left"></i> 업로드 메인
                        </a>
                        <a href="<?= base_url() ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-home"></i> 메인으로
                        </a>
                    </div>

                    <!-- 도움말 -->
                    <div class="mt-4 p-3" style="background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                        <h6><i class="fas fa-lightbulb"></i> 도움말</h6>
                        <small class="text-muted">
                            <ul class="mb-0">
                                <li>업로드 시 기존 의료제품 데이터는 모두 삭제되고 새로운 데이터로 교체됩니다.</li>
                                <li>raw_data 시트의 B,D,E,F,J,K,L,M,P,Q,T,U,V,AQ열만 처리됩니다.</li>
                                <li>처리된 데이터는 데이터베이스에 안전하게 저장되었습니다.</li>
                                <li>수수료율(AQ열) 데이터도 함께 저장되었습니다.</li>
                            </ul>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap JS -->
    <script src="<?= base_url('assets/vendor/bootstrap.min.js') ?>"></script>
</body>
</html> 