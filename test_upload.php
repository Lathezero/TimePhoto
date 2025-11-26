<?php
// 调试上传功能
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>上传调试信息</h2>";

echo "<h3>PHP配置:</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'enabled' : 'disabled') . "<br>";

echo "<h3>目录权限:</h3>";
$uploadDir = 'uploads/videos/';
echo "上传目录: " . $uploadDir . "<br>";
echo "目录存在: " . (file_exists($uploadDir) ? 'yes' : 'no') . "<br>";
echo "目录可写: " . (is_writable($uploadDir) ? 'yes' : 'no') . "<br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST数据:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>FILES数据:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    if (isset($_FILES['video'])) {
        $file = $_FILES['video'];
        echo "<h3>文件信息:</h3>";
        echo "文件名: " . $file['name'] . "<br>";
        echo "文件类型: " . $file['type'] . "<br>";
        echo "文件大小: " . $file['size'] . " bytes<br>";
        echo "临时文件: " . $file['tmp_name'] . "<br>";
        echo "错误代码: " . $file['error'] . "<br>";
        
        // 错误代码说明
        $errors = [
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File too large (upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temp directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
            UPLOAD_ERR_EXTENSION => 'Extension stopped upload'
        ];
        
        echo "错误说明: " . ($errors[$file['error']] ?? 'Unknown error') . "<br>";
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            echo "<h3>尝试移动文件:</h3>";
            $targetFile = $uploadDir . 'test_' . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                echo "文件移动成功: " . $targetFile . "<br>";
            } else {
                echo "文件移动失败<br>";
            }
        }
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <h3>测试上传:</h3>
    <input type="text" name="title" placeholder="视频标题" value="测试视频"><br><br>
    <input type="file" name="video" accept="video/*"><br><br>
    <input type="submit" value="上传测试">
</form>