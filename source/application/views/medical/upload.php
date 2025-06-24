<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap.min.css'); ?>">
    <style>
        .container { margin-top: 50px; }
        .alert { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">요양기관 정보 업로드</h4>
                    </div>
                    <div class="card-body">
                        <?php if($this->session->flashdata('success')): ?>
                            <div class="alert alert-success">
                                <?php echo $this->session->flashdata('success'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($this->session->flashdata('error')): ?>
                            <div class="alert alert-danger">
                                <?php echo $this->session->flashdata('error'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php echo form_open_multipart('medical/upload', ['class' => 'form']); ?>
                            <div class="form-group">
                                <label for="excel_file">엑셀 파일 선택</label>
                                <input type="file" name="excel_file" id="excel_file" class="form-control" accept=".xls,.xlsx" required>
                                <small class="form-text text-muted">
                                    지원 형식: .xls, .xlsx<br>
                                    첫 번째 행은 헤더로 처리됩니다.
                                </small>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">업로드</button>
                            </div>
                        <?php echo form_close(); ?>
                        
                        <hr>
                        
                        <h5>엑셀 파일 형식</h5>
                        <p>다음 컬럼을 순서대로 포함해야 합니다:</p>
                        <ol>
                            <li>암호화요양기호</li>
                            <li>요양기관명</li>
                            <li>종별코드</li>
                            <li>종별코드명</li>
                            <li>시도코드</li>
                            <li>시도코드명</li>
                            <li>시군구코드</li>
                            <li>시군구코드명</li>
                            <li>읍면동</li>
                            <li>우편번호</li>
                            <li>주소</li>
                            <li>전화번호</li>
                            <li>병원홈페이지</li>
                            <li>개설일자</li>
                            <li>총의사수</li>
                            <li>의과일반의 인원수</li>
                            <li>의과인턴 인원수</li>
                            <li>의과레지던트 인원수</li>
                            <li>의과전문의 인원수</li>
                            <li>치과일반의 인원수</li>
                            <li>치과인턴 인원수</li>
                            <li>치과레지던트 인원수</li>
                            <li>치과전문의 인원수</li>
                            <li>한방일반의 인원수</li>
                            <li>한방인턴 인원수</li>
                            <li>한방레지던트 인원수</li>
                            <li>한방전문의 인원수</li>
                            <li>조산사 인원수</li>
                            <li>좌표(X)</li>
                            <li>좌표(Y)</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo base_url('assets/vendor/bootstrap.min.js'); ?>"></script>
</body>
</html> 