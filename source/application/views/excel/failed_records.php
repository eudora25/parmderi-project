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
        .failed-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        .error-type-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .error-missing { background: #ff6b6b; color: white; }
        .error-validation { background: #feca57; color: #333; }
        .error-database { background: #ff9ff3; color: #333; }
        .error-duplicate { background: #54a0ff; color: white; }
        .raw-data {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            max-height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="failed-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-exclamation-triangle text-danger"></i> 실패한 레코드</h2>
                        <div>
                            <a href="<?php echo base_url('excel/stats'); ?>" class="btn btn-outline-primary">
                                <i class="fas fa-chart-bar"></i> 통계로 돌아가기
                            </a>
                            <?php if ($upload_log_id): ?>
                                <button onclick="reprocessFailedRecords(<?php echo $upload_log_id; ?>)" class="btn btn-success">
                                    <i class="fas fa-redo"></i> 전체 재처리
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($upload_log): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> 업로드 정보</h5>
                            <div class="row">
                                <div class="col-md-3"><strong>파일명:</strong> <?php echo htmlspecialchars($upload_log->file_name); ?></div>
                                <div class="col-md-3"><strong>총 행수:</strong> <?php echo number_format($upload_log->total_rows); ?></div>
                                <div class="col-md-3"><strong>성공:</strong> <?php echo number_format($upload_log->success_count); ?></div>
                                <div class="col-md-3"><strong>실패:</strong> <?php echo number_format($upload_log->error_count); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($failed_records)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">행번호</th>
                                        <th width="10%">오류유형</th>
                                        <th width="15%">암호화코드</th>
                                        <th width="20%">기관명</th>
                                        <th width="25%">오류메시지</th>
                                        <th width="20%">원본 데이터</th>
                                        <th width="5%">처리여부</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($failed_records as $record): ?>
                                        <tr>
                                            <td><?php echo $record->row_num; ?></td>
                                            <td>
                                                <span class="error-type-badge error-<?php echo $record->error_type; ?>">
                                                    <?php 
                                                    switch($record->error_type) {
                                                        case 'missing_required': echo '필수필드 누락'; break;
                                                        case 'validation_error': echo '검증 오류'; break;
                                                        case 'database_error': echo 'DB 오류'; break;
                                                        case 'duplicate_key': echo '중복 키'; break;
                                                        default: echo $record->error_type;
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($record->encrypted_code): ?>
                                                    <small class="text-muted"><?php echo substr($record->encrypted_code, 0, 20) . '...'; ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($record->institution_name ?: '-'); ?></td>
                                            <td>
                                                <small class="text-danger"><?php echo htmlspecialchars($record->error_message); ?></small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary" onclick="showRawData(<?php echo $record->id; ?>)">
                                                    <i class="fas fa-eye"></i> 보기
                                                </button>
                                                <div id="raw-data-<?php echo $record->id; ?>" style="display:none;">
                                                    <div class="raw-data mt-2">
                                                        <?php echo htmlspecialchars($record->raw_data); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($record->processed): ?>
                                                    <span class="badge bg-success">처리완료</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">대기중</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-lightbulb"></i> 실패 원인 및 해결 방법</h6>
                                <ul class="mb-0">
                                    <li><strong>필수필드 누락:</strong> 기관명, 주소, 전화번호 중 하나 이상이 비어있음</li>
                                    <li><strong>검증 오류:</strong> 데이터 형식이나 값이 올바르지 않음</li>
                                    <li><strong>DB 오류:</strong> 데이터베이스 저장 중 오류 발생</li>
                                    <li><strong>중복 키:</strong> 이미 존재하는 암호화요양기호</li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success text-center">
                            <h4><i class="fas fa-check-circle"></i> 실패한 레코드가 없습니다!</h4>
                            <p>모든 데이터가 성공적으로 처리되었습니다.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap JS -->
    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
    
    <script>
    function showRawData(recordId) {
        const element = document.getElementById('raw-data-' + recordId);
        if (element.style.display === 'none') {
            element.style.display = 'block';
        } else {
            element.style.display = 'none';
        }
    }

    function reprocessFailedRecords(uploadLogId) {
        if (!confirm('실패한 레코드를 재처리하시겠습니까?')) {
            return;
        }
        
        const button = event.target;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 처리중...';
        
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
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-redo"></i> 전체 재처리';
            });
    }
    </script>
</body>
</html> 