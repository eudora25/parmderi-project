<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>질문 유형 테스트 - 병원 검색 시스템</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .result-card { 
            margin-top: 20px; 
            border-left: 4px solid #007bff;
        }
        .sample-question {
            cursor: pointer;
            padding: 8px 12px;
            margin: 4px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            display: inline-block;
        }
        .sample-question:hover {
            background: #e9ecef;
        }
        .type-badge {
            font-size: 0.8em;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h2>🔍 질문 유형 분석 테스트</h2>
                <p class="text-muted">병원 관련 질문을 입력하면 자동으로 질문 유형을 분석합니다.</p>
                
                <!-- 질문 입력 폼 -->
                <form method="post" id="testForm">
                    <div class="mb-3">
                        <label for="query" class="form-label">질문 입력</label>
                        <input type="text" class="form-control" id="query" name="query" 
                               placeholder="예: 삼성서울병원 위치, 아산병원 전화번호" 
                               value="<?= isset($_POST['query']) ? htmlspecialchars($_POST['query']) : '' ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">분석하기</button>
                    <button type="button" class="btn btn-secondary" onclick="clearForm()">초기화</button>
                </form>

                <!-- 분석 결과 -->
                <?php if (isset($result) && $result): ?>
                <div class="card result-card">
                    <div class="card-header">
                        <h5>분석 결과</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>질문 유형:</strong> 
                                <span class="badge bg-primary"><?= htmlspecialchars($result['type_name']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>매칭 점수:</strong> 
                                <span class="badge bg-info"><?= number_format($result['match_score'], 1) ?></span>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>카테고리:</strong> <?= htmlspecialchars($result['category_name']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>우선순위:</strong> <?= $result['priority'] ?>
                            </div>
                        </div>
                        <hr>
                        <div>
                            <strong>설명:</strong><br>
                            <?= htmlspecialchars($result['description']) ?>
                        </div>
                        <hr>
                        <div>
                            <strong>관련 키워드:</strong><br>
                            <?php 
                            $keywords = json_decode($result['keywords'], true);
                            if ($keywords) {
                                foreach ($keywords as $keyword) {
                                    echo '<span class="badge bg-light text-dark me-1">' . htmlspecialchars($keyword) . '</span>';
                                }
                            }
                            ?>
                        </div>
                        <hr>
                        <div>
                            <strong>답변 템플릿:</strong><br>
                            <code><?= htmlspecialchars($result['answer_template']) ?></code>
                        </div>
                    </div>
                </div>
                <?php elseif (isset($result)): ?>
                <div class="alert alert-warning mt-3">
                    질문을 분석할 수 없습니다. 다른 방식으로 질문해 보세요.
                </div>
                <?php endif; ?>
            </div>

            <!-- 샘플 질문들 -->
            <div class="col-md-4">
                <h5>샘플 질문들</h5>
                <p class="text-muted small">클릭하면 자동으로 입력됩니다.</p>
                
                <?php if (isset($sample_questions) && $sample_questions): ?>
                    <?php foreach ($sample_questions as $type_code => $type_data): ?>
                        <div class="mb-3">
                            <h6><?= htmlspecialchars($type_data['type_name']) ?></h6>
                            <?php foreach ($type_data['questions'] as $question): ?>
                                <div class="sample-question" onclick="setQuery('<?= htmlspecialchars($question) ?>')">
                                    <?= htmlspecialchars($question) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 통계 정보 -->
        <div class="row mt-5">
            <div class="col-12">
                <h5>시스템 정보</h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h4 class="text-primary">12</h4>
                                <small>질문 유형</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h4 class="text-success">78,233</h4>
                                <small>의료기관</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h4 class="text-info">실시간</h4>
                                <small>분석 속도</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h4 class="text-warning">AI</h4>
                                <small>자연어 처리</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setQuery(question) {
            document.getElementById('query').value = question;
        }
        
        function clearForm() {
            document.getElementById('query').value = '';
        }
        
        // Enter 키 처리
        document.getElementById('query').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('testForm').submit();
            }
        });
    </script>
</body>
</html> 