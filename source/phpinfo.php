<?php
// PHP 설정 확인용
echo "<h2>PHP 업로드 관련 설정</h2>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "max_input_time: " . ini_get('max_input_time') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";

echo "<hr>";
phpinfo();
?>