<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>의약품 데이터 업로드</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>">
    <style>
        .hidden {
            display: none;
        }
        .error-details {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">의약품 데이터 업로드</h2>
        
        <div class="card">
            <div class="card-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="excel_file">엑셀 파일 선택 (.xlsx, .xls)</label>
                        <input type="file" class="form-control-file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                    </div>
                    <button type="submit" class="btn btn-primary">업로드</button>
                </form>
            </div>
        </div>

        <!-- 결과 영역 -->
        <div id="resultArea" class="mt-4 hidden">
            <div class="alert" role="alert">
                <h4 id="resultTitle" class="alert-heading"></h4>
                <p id="resultMessage"></p>
            </div>
        </div>

        <!-- 오류 목록 -->
        <div id="errorList" class="mt-4 hidden">
            <h5>오류 상세</h5>
            <div class="list-group"></div>
        </div>

        <!-- 로딩 오버레이 -->
        <div id="loadingOverlay" class="loading-overlay hidden">
            <div class="spinner"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadForm = document.getElementById('uploadForm');
            const resultArea = document.getElementById('resultArea');
            const resultTitle = document.getElementById('resultTitle');
            const resultMessage = document.getElementById('resultMessage');
            const errorList = document.getElementById('errorList');
            const loadingOverlay = document.getElementById('loadingOverlay');

            uploadForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    // 입력 검증
                    const fileInput = document.getElementById('excel_file');
                    if (!fileInput.files.length) {
                        throw new Error('파일을 선택해주세요.');
                    }

                    const file = fileInput.files[0];
                    if (!file.name.match(/\.(xlsx|xls)$/i)) {
                        throw new Error('엑셀 파일(.xlsx, .xls)만 업로드 가능합니다.');
                    }

                    // 로딩 표시
                    loadingOverlay.classList.remove('hidden');
                    
                    // 폼 데이터 준비
                    const formData = new FormData(uploadForm);

                    // 파일 업로드 요청
                    const response = await fetch('<?php echo site_url('medical_products/upload_simple'); ?>', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    // 응답 확인
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // 응답 타입 확인
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Server response:', text);
                        throw new Error('서버에서 잘못된 응답을 반환했습니다.');
                    }

                    // JSON 파싱
                    const data = await response.json();
                    
                    // 결과 표시
                    showResult(data);

                } catch (error) {
                    console.error('Upload error:', error);
                    showError(error.message);
                } finally {
                    loadingOverlay.classList.add('hidden');
                }
            });

            function showResult(data) {
                resultArea.classList.remove('hidden');
                
                if (data.success) {
                    resultArea.querySelector('.alert').className = 'alert alert-success';
                    resultTitle.textContent = '업로드 성공';
                } else {
                    resultArea.querySelector('.alert').className = 'alert alert-danger';
                    resultTitle.textContent = '업로드 실패';
                }
                
                resultMessage.textContent = data.message;
                
                // 오류 목록 표시
                if (data.errors && data.errors.length > 0) {
                    errorList.classList.remove('hidden');
                    const errorListGroup = errorList.querySelector('.list-group');
                    errorListGroup.innerHTML = ''; // 기존 오류 목록 초기화
                    
                    data.errors.forEach(error => {
                        const item = document.createElement('div');
                        item.className = 'list-group-item list-group-item-danger';
                        item.innerHTML = `
                            <h6 class="mb-1">행 ${error.row}</h6>
                            <p class="mb-1">${error.error}</p>
                            <small>데이터: ${JSON.stringify(error.data)}</small>
                        `;
                        errorListGroup.appendChild(item);
                    });
                } else {
                    errorList.classList.add('hidden');
                }
            }

            function showError(message) {
                resultArea.classList.remove('hidden');
                resultArea.querySelector('.alert').className = 'alert alert-danger';
                resultTitle.textContent = '오류 발생';
                resultMessage.textContent = message;
                errorList.classList.add('hidden');
            }
        });
    </script>
</body>
</html> 
