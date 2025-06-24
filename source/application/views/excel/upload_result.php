<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>업로드 결과 - 병원정보 엑셀 업로드</title>
    <link href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>" rel="stylesheet">
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
                            <h2 class="text-success mt-3">업로드 완료!</h2>
                        <?php else: ?>
                            <i class="fas fa-exclamation-triangle error-icon"></i>
                            <h2 class="text-danger mt-3">업로드 실패</h2>
                        <?php endif; ?>
                    </div>

                    <!-- 성공 메시지 -->
                    <?php if (isset($message) && !empty($message)): ?>
                        <div class="success-message">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- 오류 메시지 -->
                    <?php if (isset($error_message) && !empty($error_message)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- 통계 정보 -->
                    <?php if (isset($success) && $success): ?>
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="stats-number"><?php echo isset($total_rows) ? $total_rows : 0; ?></div>
                                    <div class="mt-2">총 데이터 행</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                    <div class="stats-number"><?php echo isset($inserted_count) ? $inserted_count : 0; ?></div>
                                    <div class="mt-2">성공</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                                    <div class="stats-number"><?php echo isset($updated_count) ? $updated_count : 0; ?></div>
                                    <div class="mt-2">업데이트</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);">
                                    <div class="stats-number"><?php echo isset($error_count) ? $error_count : 0; ?></div>
                                    <div class="mt-2">오류</div>
                                </div>
                            </div>
                        </div>

                        <!-- 성공률 표시 -->
                        <?php 
                        $total = isset($total_rows) ? $total_rows : 0;
                        $success_rate = $total > 0 ? round(($inserted_count + $updated_count) / $total * 100, 1) : 0;
                        ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>처리 성공률: <?php echo $success_rate; ?>%</h5>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $success_rate; ?>%" 
                                         aria-valuenow="<?php echo $success_rate; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $success_rate; ?>%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 처리 요약 -->
                        <div class="mt-4 p-3" style="background: #f8f9fa; border-radius: 5px;">
                            <h6><i class="fas fa-chart-pie"></i> 처리 요약</h6>
                            <ul class="mb-0">
                                <li><strong>새로 등록:</strong> <?php echo $inserted_count; ?>개 기관</li>
                                <li><strong>정보 업데이트:</strong> <?php echo $updated_count; ?>개 기관</li>
                                <?php if ($error_count > 0): ?>
                                    <li class="text-danger"><strong>처리 실패:</strong> <?php echo $error_count; ?>개 행 (필수 정보 누락 또는 형식 오류)</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- 액션 버튼 -->
                    <div class="text-center mt-4">
                        <a href="<?php echo base_url('excel'); ?>" class="btn btn-custom me-3">
                            <i class="fas fa-upload"></i> 다시 업로드
                        </a>
                        <a href="<?php echo base_url('excel/stats'); ?>" class="btn btn-outline-info me-3">
                            <i class="fas fa-chart-bar"></i> 업로드 통계
                        </a>
                        <?php if (isset($result['upload_log_id']) && isset($error_count) && $error_count > 0): ?>
                            <a href="<?php echo base_url('excel/failed_records/' . $result['upload_log_id']); ?>" class="btn btn-outline-danger me-3">
                                <i class="fas fa-exclamation-triangle"></i> 실패한 레코드
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo base_url(); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-home"></i> 메인으로
                        </a>
                    </div>

                    <!-- 도움말 -->
                    <div class="mt-4 p-3" style="background: #e3f2fd; border-radius: 5px; border-left: 4px solid #2196f3;">
                        <h6><i class="fas fa-lightbulb"></i> 도움말</h6>
                        <small class="text-muted">
                            <ul class="mb-0">
                                <li>중복된 기관 정보(기관명 + 전화번호 기준)는 자동으로 업데이트됩니다.</li>
                                <li>필수 정보(기관명, 주소, 전화번호)가 누락된 행은 처리되지 않습니다.</li>
                                <li>처리된 데이터는 데이터베이스에 안전하게 저장되었습니다.</li>
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
    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
</body>
</html> 