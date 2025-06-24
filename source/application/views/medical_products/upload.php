<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            background-color: #e9ecef;
            border-color: #0056b3;
        }
        .upload-area.dragging {
            background-color: #e3f2fd;
            border-color: #2196f3;
        }
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        .log-item {
            border-left: 4px solid #007bff;
            padding: 10px 15px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
        }
        .log-item.success {
            border-left-color: #28a745;
        }
        .log-item.failed {
            border-left-color: #dc3545;
        }
        .log-item.processing {
            border-left-color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-upload me-2"></i><?= $title ?></h1>
                    <a href="<?= base_url('medical_products') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-search me-1"></i>의약품 검색
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-excel me-2"></i>엑셀 파일 업로드</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>업로드 안내</h6>
                            <ul class="mb-0">
                                <li><strong>파일 형식:</strong> .xlsx, .xls 파일만 업로드 가능</li>
                                <li><strong>최대 크기:</strong> 10MB 이하</li>
                                <li><strong>시트:</strong> "raw_data" 시트의 데이터를 처리합니다</li>
                                <li><strong>데이터:</strong> 기존 데이터를 모두 삭제하고 새로 입력됩니다</li>
                            </ul>
                        </div>

                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-content">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                    <h5>엑셀 파일을 여기에 드래그하거나 클릭해서 선택하세요</h5>
                                    <p class="text-muted">제품_raw_db_작업_YYYYMMDD_후니.xlsx 형태의 파일</p>
                                    <input type="file" id="excelFile" name="excel_file" accept=".xlsx,.xls" style="display: none;">
                                    <button type="button" class="btn btn-primary" onclick="document.getElementById('excelFile').click();">
                                        <i class="fas fa-folder-open me-1"></i>파일 선택
                                    </button>
                                </div>
                            </div>

                            <div class="selected-file mt-3" id="selectedFile" style="display: none;">
                                <div class="alert alert-success">
                                    <i class="fas fa-file-excel me-2"></i>
                                    <span id="fileName"></span>
                                    <span class="badge bg-secondary ms-2" id="fileSize"></span>
                                </div>
                            </div>

                            <div class="progress-container" id="progressContainer">
                                <div class="progress mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 0%"></div>
                                </div>
                                <div class="text-center">
                                    <small class="text-muted" id="progressText">업로드 중...</small>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-success btn-lg" id="uploadBtn" disabled>
                                    <i class="fas fa-upload me-1"></i>데이터 업로드 시작
                                </button>
                                <button type="button" class="btn btn-secondary ms-2" onclick="location.reload()">
                                    <i class="fas fa-refresh me-1"></i>새로고침
                                </button>
                            </div>
                        </form>

                        <div class="result-container mt-4" id="resultContainer" style="display: none;">
                            <div class="alert" id="resultAlert">
                                <div id="resultContent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>최근 업로드 이력</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upload_logs)): ?>
                            <?php foreach ($upload_logs as $log): ?>
                                <div class="log-item <?= $log['status'] ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($log['filename']) ?></h6>
                                            <small class="text-muted"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></small>
                                        </div>
                                        <span class="badge bg-<?= $log['status'] === 'completed' ? 'success' : ($log['status'] === 'failed' ? 'danger' : 'warning') ?>">
                                            <?= $log['status'] ?>
                                        </span>
                                    </div>
                                    <?php if ($log['status'] === 'completed'): ?>
                                        <div class="mt-2">
                                            <small>
                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                성공: <?= number_format($log['success_rows']) ?>개 |
                                                <i class="fas fa-times-circle text-danger me-1"></i>
                                                실패: <?= number_format($log['failed_rows']) ?>개
                                            </small>
                                        </div>
                                    <?php elseif ($log['status'] === 'failed'): ?>
                                        <div class="mt-2">
                                            <small class="text-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <?= htmlspecialchars($log['error_message']) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-history fa-2x mb-2"></i>
                                <p>업로드 이력이 없습니다.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>주의사항</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="fas fa-minus text-warning me-2"></i>업로드 시 기존 데이터가 모두 삭제됩니다</li>
                            <li><i class="fas fa-minus text-warning me-2"></i>대용량 파일은 처리에 시간이 걸립니다</li>
                            <li><i class="fas fa-minus text-warning me-2"></i>업로드 중에는 브라우저를 닫지 마세요</li>
                            <li><i class="fas fa-minus text-warning me-2"></i>네트워크 연결을 확인해주세요</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('excelFile');
        const selectedFile = document.getElementById('selectedFile');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');
        const progressContainer = document.getElementById('progressContainer');
        const resultContainer = document.getElementById('resultContainer');

        // 드래그 앤 드롭 이벤트
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragging');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragging');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragging');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect();
            }
        });

        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (!file) return;

            // 파일 형식 체크
            if (!file.name.match(/\.(xlsx|xls)$/i)) {
                alert('Excel 파일(.xlsx, .xls)만 업로드 가능합니다.');
                fileInput.value = '';
                return;
            }

            // 파일 크기 체크 (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('파일 크기가 10MB를 초과합니다.');
                fileInput.value = '';
                return;
            }

            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            selectedFile.style.display = 'block';
            uploadBtn.disabled = false;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!fileInput.files[0]) {
                alert('파일을 선택해주세요.');
                return;
            }

            const formData = new FormData();
            formData.append('excel_file', fileInput.files[0]);

            uploadBtn.disabled = true;
            progressContainer.style.display = 'block';
            resultContainer.style.display = 'none';

            try {
                const response = await fetch('<?= base_url('medical_products/process_excel') ?>', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                progressContainer.style.display = 'none';
                resultContainer.style.display = 'block';

                const resultAlert = document.getElementById('resultAlert');
                const resultContent = document.getElementById('resultContent');

                if (result.success) {
                    resultAlert.className = 'alert alert-success';
                    resultContent.innerHTML = `
                        <h5><i class="fas fa-check-circle me-2"></i>업로드 완료!</h5>
                        <p>${result.message}</p>
                        <ul class="mb-0">
                            <li>전체 행 수: ${result.total_rows.toLocaleString()}개</li>
                            <li>성공 행 수: ${result.success_rows.toLocaleString()}개</li>
                            <li>실패 행 수: ${result.failed_rows.toLocaleString()}개</li>
                        </ul>
                        <div class="mt-3">
                            <a href="<?= base_url('medical_products') ?>" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>의약품 검색 페이지로 이동
                            </a>
                        </div>
                    `;
                } else {
                    resultAlert.className = 'alert alert-danger';
                    resultContent.innerHTML = `
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>업로드 실패</h5>
                        <p>${result.message}</p>
                    `;
                }

            } catch (error) {
                progressContainer.style.display = 'none';
                resultContainer.style.display = 'block';
                
                const resultAlert = document.getElementById('resultAlert');
                const resultContent = document.getElementById('resultContent');
                
                resultAlert.className = 'alert alert-danger';
                resultContent.innerHTML = `
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>오류 발생</h5>
                    <p>서버와의 통신 중 오류가 발생했습니다: ${error.message}</p>
                `;
            }

            uploadBtn.disabled = false;
        });
    </script>
</body>
</html> 