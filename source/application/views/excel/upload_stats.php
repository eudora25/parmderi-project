<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - 병원정보 엑셀 업로드</title>
    <link href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .stats-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .error-card {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .success-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
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
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="stats-container">
                    <h2><i class="fas fa-chart-bar"></i> 업로드 통계</h2>
                    
                    <!-- 액션 버튼 -->
                    <div class="mb-4">
                        <a href="<?php echo base_url('excel'); ?>" class="btn btn-custom me-3">
                            <i class="fas fa-upload"></i> 새 업로드
                        </a>
                        <a href="<?php echo base_url('excel/failed_records'); ?>" class="btn btn-outline-danger">
                            <i class="fas fa-exclamation-triangle"></i> 실패한 레코드
                        </a>
                    </div>

                    <!-- 업로드 로그 테이블 -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>파일명</th>
                                    <th>총 행수</th>
                                    <th>성공</th>
                                    <th>업데이트</th>
                                    <th>오류</th>
                                    <th>건너뜀</th>
                                    <th>성공률</th>
                                    <th>업로드 일시</th>
                                    <th>상태</th>
                                    <th>액션</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($upload_logs)): ?>
                                    <?php foreach ($upload_logs as $log): ?>
                                        <?php 
                                        $success_rate = $log->total_rows > 0 ? round(($log->success_count + $log->update_count) / $log->total_rows * 100, 1) : 0;
                                        $has_errors = $log->error_count > 0;
                                        ?>
                                        <tr class="<?php echo $has_errors ? 'table-warning' : 'table-success'; ?>">
                                            <td><?php echo $log->id; ?></td>
                                            <td><?php echo htmlspecialchars($log->file_name); ?></td>
                                            <td><?php echo number_format($log->total_rows); ?></td>
                                            <td><?php echo number_format($log->success_count); ?></td>
                                            <td><?php echo number_format($log->update_count); ?></td>
                                            <td>
                                                <?php if ($log->error_count > 0): ?>
                                                    <span class="badge bg-danger"><?php echo number_format($log->error_count); ?></span>
                                                <?php else: ?>
                                                    <?php echo number_format($log->error_count); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format($log->skipped_count); ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-<?php echo $success_rate >= 95 ? 'success' : ($success_rate >= 80 ? 'warning' : 'danger'); ?>" 
                                                         style="width: <?php echo $success_rate; ?>%">
                                                        <?php echo $success_rate; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($log->upload_date)); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $log->status == 'completed' ? 'success' : ($log->status == 'failed' ? 'danger' : 'warning'); ?>">
                                                    <?php echo $log->status; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log->error_count > 0): ?>
                                                    <a href="<?php echo base_url('excel/failed_records/' . $log->id); ?>" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-eye"></i> 실패 보기
                                                    </a>
                                                    <button onclick="reprocessFailedRecords(<?php echo $log->id; ?>)" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-redo"></i> 재처리
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11" class="text-center">업로드 기록이 없습니다.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap JS -->
    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
    
    <script>
    function reprocessFailedRecords(uploadLogId) {
        if (!confirm('실패한 레코드를 재처리하시겠습니까?')) {
            return;
        }
        
        fetch(`<?php echo base_url('excel/reprocess/'); ?>${uploadLogId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('오류: ' + data.message);
                }
            })
            .catch(error => {
                alert('네트워크 오류: ' + error.message);
            });
    }
    </script>
</body>
</html> 