<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>의약품 엑셀 업로드</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-3 text-center">의약품 엑셀 업로드</h3>
                    <div class="alert alert-info p-2" role="alert">
                        <ul class="mb-0 small">
                            <li>업로드 가능 파일: <b>.xlsx, .xls</b></li>
                            <li>최대 5MB까지 업로드 가능합니다.</li>
                            <li>양식에 맞지 않는 파일은 등록되지 않을 수 있습니다.</li>
                        </ul>
                    </div>
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" class="form-control" name="excel_file" accept=".xlsx,.xls" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">업로드</button>
                    </form>
                    <div id="spinnerArea" class="text-center my-3" style="display:none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">업로드 중...</span>
                        </div>
                        <div class="small mt-2">업로드 중입니다...</div>
                    </div>
                    <div id="resultArea" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    var resultArea = document.getElementById('resultArea');
    var spinnerArea = document.getElementById('spinnerArea');
    resultArea.innerHTML = '';
    spinnerArea.style.display = 'block';
    fetch('process_upload', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        spinnerArea.style.display = 'none';
        if (data.success) {
            resultArea.innerHTML = '<div class="alert alert-success">' + data.message + ' (입력: ' + data.count + '건)</div>';
        } else {
            resultArea.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    })
    .catch(function() {
        spinnerArea.style.display = 'none';
        resultArea.innerHTML = '<div class="alert alert-danger">업로드 중 오류가 발생했습니다.</div>';
    });
});
</script>
</body>
</html> 
