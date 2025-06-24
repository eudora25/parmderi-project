<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : '병원정보 엑셀 업로드'; ?></title>
    <link href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .upload-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        .drag-drop-zone {
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 3rem 2rem;
            text-align: center;
            background: #f8f9ff;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .drag-drop-zone:hover {
            border-color: #0056b3;
            background: #e6f3ff;
        }
        .drag-drop-zone.dragover {
            border-color: #28a745;
            background: #f0fff4;
        }
        .file-info {
            background: #e9ecef;
            border-radius: 5px;
            padding: 1rem;
            margin-top: 1rem;
            display: none;
        }
        .preview-table {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 1rem;
        }
        .error-message {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 0.75rem;
            margin-top: 1rem;
        }
        .success-message {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 0.75rem;
            margin-top: 1rem;
        }
        .progress-container {
            display: none;
            margin-top: 1rem;
        }
        .btn-upload {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: bold;
        }
        .btn-upload:hover {
            background: linear-gradient(45deg, #0056b3, #004085);
        }
        .format-guide {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="upload-container">
                    <div class="text-center mb-4">
                        <h2 class="text-primary">
                            <i class="fas fa-upload"></i> 병원정보 엑셀 업로드
                        </h2>
                        <p class="text-muted">엑셀 파일을 업로드하여 병원정보를 일괄 등록할 수 있습니다.</p>
                    </div>

                    <!-- 파일 형식 안내 -->
                    <div class="format-guide">
                        <h5><i class="fas fa-info-circle"></i> 엑셀 파일 형식 안내</h5>
                        <p class="mb-2"><strong>필수 컬럼:</strong> 기관명, 주소, 전화번호</p>
                        <p class="mb-2"><strong>선택 컬럼:</strong> 기관종류, 대표자명, 사업자번호, 우편번호, 팩스, 홈페이지, 개원일</p>
                        <p class="mb-0"><strong>지원 형식:</strong> .xlsx, .xls, .csv (최대 10MB)</p>
                    </div>

                    <!-- 오류 메시지 표시 -->
                    <?php if (isset($error)): ?>
                        <div class="error-message">
                            <strong>업로드 오류:</strong> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- 업로드 폼 -->
                    <?php echo form_open_multipart('excel_upload/upload', array('id' => 'uploadForm')); ?>
                        <div class="drag-drop-zone" id="dragDropZone">
                            <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                            <h4>파일을 이곳에 드래그하거나 클릭하여 선택하세요</h4>
                            <p class="text-muted">엑셀 파일 (.xlsx, .xls, .csv)</p>
                            <input type="file" name="excel_file" id="excel_file" class="d-none" accept=".xlsx,.xls,.csv">
                        </div>

                        <!-- 파일 정보 표시 -->
                        <div class="file-info" id="fileInfo">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>파일명:</strong> <span id="fileName"></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>크기:</strong> <span id="fileSize"></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>형식:</strong> <span id="fileType"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 진행률 표시 -->
                        <div class="progress-container" id="progressContainer">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%" id="uploadProgress"></div>
                            </div>
                            <div class="text-center mt-2">
                                <small class="text-muted" id="progressText">업로드 중...</small>
                            </div>
                        </div>

                        <!-- 미리보기 버튼 -->
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-outline-primary me-2" id="previewBtn" disabled>
                                <i class="fas fa-eye"></i> 미리보기
                            </button>
                            <button type="submit" class="btn btn-upload text-white" id="uploadBtn" disabled>
                                <i class="fas fa-upload"></i> 업로드 및 저장
                            </button>
                        </div>
                    <?php echo form_close(); ?>

                    <!-- 미리보기 테이블 -->
                    <div id="previewContainer" style="display: none;">
                        <hr>
                        <h5><i class="fas fa-table"></i> 데이터 미리보기</h5>
                        <div class="preview-table">
                            <table class="table table-bordered table-striped table-sm" id="previewTable">
                                <thead class="table-dark">
                                    <!-- 헤더는 JavaScript로 동적 생성 -->
                                </thead>
                                <tbody>
                                    <!-- 데이터는 JavaScript로 동적 생성 -->
                                </tbody>
                            </table>
                        </div>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary" id="totalRows">0</h5>
                                        <p class="card-text">총 데이터 행</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title text-info" id="totalColumns">0</h5>
                                        <p class="card-text">총 컬럼 수</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title text-success">Ready</h5>
                                        <p class="card-text">업로드 준비</p>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const dragDropZone = document.getElementById('dragDropZone');
        const fileInput = document.getElementById('excel_file');
        const fileInfo = document.getElementById('fileInfo');
        const previewBtn = document.getElementById('previewBtn');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');
        const progressContainer = document.getElementById('progressContainer');
        const previewContainer = document.getElementById('previewContainer');

        // 드래그 앤 드롭 이벤트
        dragDropZone.addEventListener('click', () => fileInput.click());
        
        dragDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dragDropZone.classList.add('dragover');
        });

        dragDropZone.addEventListener('dragleave', () => {
            dragDropZone.classList.remove('dragover');
        });

        dragDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dragDropZone.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files[0]);
            }
        });

        // 파일 선택 이벤트
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });

        // 파일 선택 처리
        function handleFileSelect(file) {
            const fileName = file.name;
            const fileSize = formatFileSize(file.size);
            const fileType = file.type || fileName.split('.').pop().toUpperCase();

            document.getElementById('fileName').textContent = fileName;
            document.getElementById('fileSize').textContent = fileSize;
            document.getElementById('fileType').textContent = fileType;

            fileInfo.style.display = 'block';
            previewBtn.disabled = false;
            uploadBtn.disabled = false;

            // 미리보기 컨테이너 숨기기
            previewContainer.style.display = 'none';
        }

        // 파일 크기 포맷팅
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // 미리보기 버튼 클릭
        previewBtn.addEventListener('click', function() {
            if (fileInput.files.length === 0) {
                alert('파일을 먼저 선택해주세요.');
                return;
            }

            const formData = new FormData();
            formData.append('excel_file', fileInput.files[0]);

            fetch('<?php echo site_url("excel_upload/preview"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPreview(data);
                } else {
                    alert('미리보기 오류: ' + (data.error_message || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('미리보기 중 오류가 발생했습니다.');
            });
        });

        // 미리보기 표시
        function displayPreview(data) {
            const table = document.getElementById('previewTable');
            const thead = table.querySelector('thead');
            const tbody = table.querySelector('tbody');

            // 헤더 생성
            thead.innerHTML = '';
            if (data.data.length > 0) {
                const headerRow = document.createElement('tr');
                data.data[0].forEach(header => {
                    const th = document.createElement('th');
                    th.textContent = header;
                    headerRow.appendChild(th);
                });
                thead.appendChild(headerRow);
            }

            // 데이터 행 생성 (헤더 제외)
            tbody.innerHTML = '';
            for (let i = 1; i < data.data.length && i <= 10; i++) {
                const dataRow = document.createElement('tr');
                data.data[i].forEach(cell => {
                    const td = document.createElement('td');
                    td.textContent = cell || '';
                    dataRow.appendChild(td);
                });
                tbody.appendChild(dataRow);
            }

            // 통계 업데이트
            document.getElementById('totalRows').textContent = data.total_rows;
            document.getElementById('totalColumns').textContent = data.total_columns;

            // 미리보기 컨테이너 표시
            previewContainer.style.display = 'block';
        }

        // 폼 제출 처리
        uploadForm.addEventListener('submit', function(e) {
            if (fileInput.files.length === 0) {
                e.preventDefault();
                alert('파일을 먼저 선택해주세요.');
                return;
            }

            // 진행률 표시
            progressContainer.style.display = 'block';
            uploadBtn.disabled = true;
            
            // 간단한 진행률 애니메이션
            let progress = 0;
            const progressBar = document.getElementById('uploadProgress');
            const progressText = document.getElementById('progressText');
            
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
                progressText.textContent = `업로드 중... ${Math.round(progress)}%`;
            }, 200);

            // 실제 제출 시 정리
            setTimeout(() => {
                clearInterval(interval);
                progressBar.style.width = '100%';
                progressText.textContent = '처리 중...';
            }, 2000);
        });
    });
    </script>
</body>
</html> 