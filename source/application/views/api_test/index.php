<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API 테스트 페이지</title>
    <link href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>" rel="stylesheet">
    <style>
        .container { margin-top: 30px; }
        .test-section { 
            margin-bottom: 30px; 
            padding: 20px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            background-color: #f9f9f9;
        }
        .response-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            max-height: 400px;
            overflow-y: auto;
        }
        .loading {
            display: none;
            color: #007bff;
        }
        .btn-test {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .status-success { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .info-section {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mb-4">External API 테스트</h1>
                
                <!-- API 정보 섹션 -->
                <div class="info-section">
                    <h5>API 정보</h5>
                    <p><strong>URL:</strong> qa.legacy-erp.parmple.com/df/filtering/manufacturers/fetch</p>
                    <p><strong>Method:</strong> GET</p>
                    <p><strong>Auth:</strong> Bearer Token 인증</p>
                    <p><strong>토큰 타입:</strong> JWT (JSON Web Token)</p>
                    <p><strong>토큰 길이:</strong> <span id="token-length"><?php echo strlen('eyJhbGciOiJIUzUxMiJ9.eyJ0eXBlIjoiTUFOVUZBQ1RVUkVSIiwiY29tcGFueUlkIjoiNjMxIiwidXNlcklkIjoiMzIiLCJjYW5Vc2VGaWx0ZXJIaXN0b3J5Ijp0cnVlLCJzdWIiOiJoamtpbUBzYW1pay5jby5rciIsImlhdCI6MTc1MDI5ODk5MSwiZXhwIjoxNzgwODg1MzkxfQ.4t81Mow-S0_aokJ5NnFEib7z3QXSvsSngV6xA1znazd4l-fdss6p3wjqdXts6VEQxp8UuDJjIQEr8Zs3__PdYQ'); ?>자</span></p>
                    <p><strong>토큰 만료일:</strong> 2026-06-03 (예상)</p>
                    <div class="mt-2">
                        <small class="text-muted">
                            JWT 토큰은 Header.Payload.Signature 형태로 구성됩니다.<br>
                            현재 토큰은 제조업체(MANUFACTURER) 타입으로 설정되어 있습니다.
                        </small>
                    </div>
                </div>

                <!-- 연결 테스트 섹션 -->
                <div class="test-section">
                    <h4>1. 연결 테스트</h4>
                    <p>API 서버와의 기본 연결 상태를 확인합니다.</p>
                    <button type="button" class="btn btn-primary btn-test" onclick="testConnection()">
                        연결 테스트
                    </button>
                    <span class="loading" id="connection-loading">
                        <i class="spinner-border spinner-border-sm"></i> 테스트 중...
                    </span>
                    <div id="connection-response" class="response-box" style="display: none;">
                        <pre id="connection-result"></pre>
                    </div>
                </div>

                <!-- 데이터 조회 섹션 -->
                <div class="test-section">
                    <h4>2. 제조업체 데이터 조회</h4>
                    <p>API에서 제조업체 목록을 가져옵니다.</p>
                    <button type="button" class="btn btn-success btn-test" onclick="fetchManufacturers()">
                        데이터 조회
                    </button>
                    <span class="loading" id="fetch-loading">
                        <i class="spinner-border spinner-border-sm"></i> 데이터 조회 중...
                    </span>
                    <div id="fetch-response" class="response-box" style="display: none;">
                        <pre id="fetch-result"></pre>
                    </div>
                </div>

                <!-- 결과 요약 섹션 -->
                <div class="test-section">
                    <h4>3. 테스트 결과 요약</h4>
                    <div id="summary-section" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>연결 테스트 결과:</h6>
                                <span id="connection-status">-</span>
                            </div>
                            <div class="col-md-6">
                                <h6>데이터 조회 결과:</h6>
                                <span id="fetch-status">-</span>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h6>받은 데이터 개수:</h6>
                                <span id="data-count">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 네비게이션 -->
                <div class="mt-4">
                    <a href="<?php echo base_url('medical'); ?>" class="btn btn-secondary">
                        의료기관 업로드로 돌아가기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
    <script>
        function showLoading(elementId) {
            document.getElementById(elementId).style.display = 'inline';
        }

        function hideLoading(elementId) {
            document.getElementById(elementId).style.display = 'none';
        }

        function showResponse(boxId, resultId, data) {
            document.getElementById(boxId).style.display = 'block';
            document.getElementById(resultId).textContent = JSON.stringify(data, null, 2);
        }

        function updateSummary() {
            document.getElementById('summary-section').style.display = 'block';
        }

        function testConnection() {
            showLoading('connection-loading');
            
            fetch('<?php echo base_url("apiTest/test_connection"); ?>', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading('connection-loading');
                showResponse('connection-response', 'connection-result', data);
                
                // 상태 업데이트
                const statusElement = document.getElementById('connection-status');
                if (data.success) {
                    statusElement.innerHTML = '<span class="status-success">성공 (HTTP ' + data.http_code + ')</span>';
                } else {
                    statusElement.innerHTML = '<span class="status-error">실패: ' + (data.curl_error || data.message) + '</span>';
                }
                updateSummary();
            })
            .catch(error => {
                hideLoading('connection-loading');
                showResponse('connection-response', 'connection-result', {
                    success: false,
                    error: error.message
                });
                
                document.getElementById('connection-status').innerHTML = 
                    '<span class="status-error">네트워크 오류</span>';
                updateSummary();
            });
        }

        function fetchManufacturers() {
            showLoading('fetch-loading');
            
            fetch('<?php echo base_url("apiTest/fetch_manufacturers"); ?>', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading('fetch-loading');
                showResponse('fetch-response', 'fetch-result', data);
                
                // 상태 업데이트
                const statusElement = document.getElementById('fetch-status');
                const countElement = document.getElementById('data-count');
                
                if (data.success) {
                    statusElement.innerHTML = '<span class="status-success">성공</span>';
                    
                    // 데이터 개수 계산
                    let count = 0;
                    if (data.data && Array.isArray(data.data)) {
                        count = data.data.length;
                    } else if (data.data && typeof data.data === 'object') {
                        count = Object.keys(data.data).length;
                    }
                    countElement.textContent = count + '개';
                } else {
                    statusElement.innerHTML = '<span class="status-error">실패: ' + data.message + '</span>';
                    countElement.textContent = '0개';
                }
                updateSummary();
            })
            .catch(error => {
                hideLoading('fetch-loading');
                showResponse('fetch-response', 'fetch-result', {
                    success: false,
                    error: error.message
                });
                
                document.getElementById('fetch-status').innerHTML = 
                    '<span class="status-error">네트워크 오류</span>';
                document.getElementById('data-count').textContent = '0개';
                updateSummary();
            });
        }

        // 페이지 로드 시 안내 메시지
        document.addEventListener('DOMContentLoaded', function() {
            console.log('API 테스트 페이지가 로드되었습니다.');
            console.log('먼저 "연결 테스트"를 실행한 후 "데이터 조회"를 테스트해보세요.');
        });
    </script>
</body>
</html> 