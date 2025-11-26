<?php
// 关闭错误显示，避免影响JSON输出
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION)) { session_start(); }
require_once 'config.php';

// 设置JSON响应头
header('Content-Type: application/json; charset=utf-8');

// 确保二维码目录存在
if (!file_exists('uploads/qrcodes')) {
    @mkdir('uploads/qrcodes', 0755, true);
}

// 获取请求方法和操作
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 生成唯一的二维码标识
function generateQRCode() {
    return uniqid('video_', true) . '_' . time();
}

// 获取视频时长（需要ffmpeg支持）
function getVideoDuration($filePath) {
    // 简化版本，返回0，实际项目中可以使用ffmpeg获取真实时长
    return 0;
}

// 处理文件上传
function uploadVideo() {
    try {
        if (!isset($_SESSION)) { session_start(); }
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
            return ['success' => false, 'message' => '请先登录用户账户再上传'];
        }
        $ownerId = $_SESSION['user_id'] ?? null;
        if (!$ownerId) {
            return ['success' => false, 'message' => '用户身份无效'];
        }
        
        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['video']['error'] ?? -1;
            $map = [
                UPLOAD_ERR_INI_SIZE => '超出服务器 upload_max_filesize 限制',
                UPLOAD_ERR_FORM_SIZE => '超出表单 post_max_size 限制',
                UPLOAD_ERR_PARTIAL => '文件只上传了部分',
                UPLOAD_ERR_NO_FILE => '未选择文件',
                UPLOAD_ERR_NO_TMP_DIR => '服务器未配置临时目录（upload_tmp_dir）',
                UPLOAD_ERR_CANT_WRITE => '无法写入磁盘，请检查权限',
                UPLOAD_ERR_EXTENSION => '扩展阻止了上传',
            ];
            $detail = $map[$code] ?? '未知错误';
            return ['success' => false, 'message' => '视频上传失败: ' . $detail . '（错误代码: ' . $code . '）'];
        }
        
        $file = $_FILES['video'];
        $title = $_POST['title'] ?? '未命名视频';
        
        // 检查文件类型 - 支持更多视频格式和MIME类型
        $allowedTypes = [
            'video/mp4', 'video/avi', 'video/mov', 'video/wmv',
            'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv',
            'application/octet-stream' // 某些MOV文件可能被识别为此类型
        ];
        
        // 检查文件扩展名作为备用验证
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'flv', 'webm'];
        
        if (!in_array($file['type'], $allowedTypes) && !in_array($extension, $allowedExtensions)) {
            return ['success' => false, 'message' => '不支持的视频格式: ' . $file['type'] . ' (.' . $extension . ')'];
        }
        
        // 确保上传目录存在
        if (!file_exists(UPLOAD_PATH)) {
            if (!mkdir(UPLOAD_PATH, 0755, true)) {
                return ['success' => false, 'message' => '无法创建上传目录'];
            }
        }
        
        // 生成唯一文件名
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('video_') . '.' . $extension;
        $filepath = UPLOAD_PATH . $filename;
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'message' => '文件保存失败，请检查目录权限'];
        }
        
        // 生成二维码
        $qrCode = generateQRCode();
        
        // 保存到数据库
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO videos (user_id, title, filename, original_name, file_size, qr_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ownerId, $title, $filename, $file['name'], $file['size'], $qrCode]);
        
        $videoId = $pdo->lastInsertId();
        
        // 自动生成并保存二维码
        $qrResult = generateAndSaveQRCode($videoId, $qrCode);
        
        return [
            'success' => true, 
            'message' => '视频上传成功',
            'video_id' => $videoId,
            'qr_code' => $qrCode,
            'qr_generated' => $qrResult['success'] ?? false
        ];
    } catch(Exception $e) {
        return ['success' => false, 'message' => '上传失败: ' . $e->getMessage()];
    }
}

// 获取视频列表
function getVideoList() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT v.*, u.username AS uploader_username FROM videos v LEFT JOIN users u ON v.user_id = u.id WHERE v.status = 'active' ORDER BY v.upload_time DESC");
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return ['success' => true, 'videos' => $videos];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => '获取视频列表失败: ' . $e->getMessage()];
    }
}

function getMyVideos() {
    try {
        if (!isset($_SESSION)) { session_start(); }
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
            return ['success' => false, 'message' => '未登录'];
        }
        $ownerId = $_SESSION['user_id'] ?? 0;
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? ORDER BY upload_time DESC");
        $stmt->execute([$ownerId]);
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'videos' => $videos];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => '获取我的视频失败: ' . $e->getMessage()];
    }
}

// 根据二维码获取视频信息
function getVideoByQR($qrCode) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT v.*, u.username AS uploader_username FROM videos v LEFT JOIN users u ON v.user_id = u.id WHERE v.qr_code = ? AND v.status = 'active'");
        $stmt->execute([$qrCode]);
        $video = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($video) {
            // 增加观看次数
            $updateStmt = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
            $updateStmt->execute([$video['id']]);
            $video['views']++;
            
            return ['success' => true, 'video' => $video];
        } else {
            return ['success' => false, 'message' => '视频不存在'];
        }
    } catch(PDOException $e) {
        return ['success' => false, 'message' => '获取视频信息失败: ' . $e->getMessage()];
    }
}

// 更新视频封面
function updateCover() {
    $videoId = $_POST['video_id'] ?? 0;
    if (!isset($_SESSION)) { session_start(); }
    $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!isset($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '封面上传失败'];
    }
    
    $file = $_FILES['cover'];
    
    // 检查文件类型
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => '不支持的图片格式'];
    }
    
    // 获取旧封面信息，用于删除
    try {
        $pdo = getDB();
        if ($isAdmin) {
            $stmt = $pdo->prepare("SELECT id, cover_image FROM videos WHERE id = ?");
            $stmt->execute([$videoId]);
        } else {
            $stmt = $pdo->prepare("SELECT id, cover_image FROM videos WHERE id = ? AND user_id = ?");
            $stmt->execute([$videoId, $userId]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row && !$isAdmin) {
            return ['success' => false, 'message' => '无权限操作该视频'];
        }
        $oldCover = $row['cover_image'] ?? null;
    } catch(PDOException $e) {
        return ['success' => false, 'message' => '查询旧封面失败: ' . $e->getMessage()];
    }
    
    // 生成唯一文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'cover_' . $videoId . '_' . time() . '.' . $extension;
    $filepath = COVER_PATH . $filename;
    
    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => '封面保存失败'];
    }
    
    // 更新数据库
    try {
        $stmt = $pdo->prepare("UPDATE videos SET cover_image = ? WHERE id = ?");
        $stmt->execute([$filename, $videoId]);
        
        // 删除旧封面文件（如果存在且不为空）
        if ($oldCover && file_exists(COVER_PATH . $oldCover)) {
            unlink(COVER_PATH . $oldCover);
        }
        
        return ['success' => true, 'message' => '封面更新成功', 'cover' => $filename];
    } catch(PDOException $e) {
        // 如果数据库更新失败，删除新上传的文件
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        return ['success' => false, 'message' => '数据库更新失败: ' . $e->getMessage()];
    }
}

// 生成并保存二维码图片
function generateAndSaveQRCode($videoId, $qrCode) {
    try {
        $videoUrl = SITE_URL . '/play.php?qr=' . $qrCode;
        
        // 使用在线API获取二维码图片
        $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&data=' . urlencode($videoUrl);
        
        // 下载二维码图片
        $imageData = @file_get_contents($qrApiUrl);
        
        if ($imageData === false) {
            return ['success' => false, 'message' => '二维码生成失败'];
        }
        
        // 生成文件名
        $filename = 'qr_' . $videoId . '_' . time() . '.png';
        $filepath = 'uploads/qrcodes/' . $filename;
        
        // 保存图片文件
        if (file_put_contents($filepath, $imageData) === false) {
            return ['success' => false, 'message' => '二维码保存失败'];
        }
        
        // 更新数据库记录
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE videos SET qr_image_path = ? WHERE id = ?");
        $stmt->execute([$filename, $videoId]);
        
        return [
            'success' => true, 
            'message' => '二维码生成成功',
            'qr_image_path' => $filename,
            'qr_url' => $videoUrl
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '二维码生成失败: ' . $e->getMessage()];
    }
}

// 重新生成二维码
function regenerateQRCode() {
    $videoId = $_POST['video_id'] ?? 0;
    
    if (!$videoId) {
        return ['success' => false, 'message' => '视频ID不能为空'];
    }
    
    try {
        if (!isset($_SESSION)) { session_start(); }
        $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
        $userId = $_SESSION['user_id'] ?? 0;
        $pdo = getDB();
        if ($isAdmin) {
            $stmt = $pdo->prepare("SELECT qr_code FROM videos WHERE id = ?");
            $stmt->execute([$videoId]);
        } else {
            $stmt = $pdo->prepare("SELECT qr_code FROM videos WHERE id = ? AND user_id = ?");
            $stmt->execute([$videoId, $userId]);
        }
        $video = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$video) {
            return ['success' => false, 'message' => '视频不存在'];
        }
        
        return generateAndSaveQRCode($videoId, $video['qr_code']);
    } catch (Exception $e) {
        return ['success' => false, 'message' => '操作失败: ' . $e->getMessage()];
    }
}

// 删除视频
function deleteVideo() {
    $videoId = $_POST['video_id'] ?? 0;
    
    try {
        if (!isset($_SESSION)) { session_start(); }
        $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
        $userId = $_SESSION['user_id'] ?? 0;
        $pdo = getDB();
        
        // 先获取视频信息
        if ($isAdmin) {
            $stmt = $pdo->prepare("SELECT filename, cover_image, qr_image_path FROM videos WHERE id = ?");
            $stmt->execute([$videoId]);
        } else {
            $stmt = $pdo->prepare("SELECT filename, cover_image, qr_image_path FROM videos WHERE id = ? AND user_id = ?");
            $stmt->execute([$videoId, $userId]);
        }
        $video = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$video) {
            return ['success' => false, 'message' => '视频不存在'];
        }
        
        // 删除视频文件
        $videoFile = UPLOAD_PATH . $video['filename'];
        if (file_exists($videoFile)) {
            unlink($videoFile);
        }
        
        // 删除封面文件
        if ($video['cover_image']) {
            $coverFile = COVER_PATH . $video['cover_image'];
            if (file_exists($coverFile)) {
                unlink($coverFile);
            }
        }
        
        // 删除二维码文件
        if ($video['qr_image_path']) {
            $qrFile = 'uploads/qrcodes/' . $video['qr_image_path'];
            if (file_exists($qrFile)) {
                unlink($qrFile);
            }
        }
        
        // 从数据库中删除记录
        $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
        $stmt->execute([$videoId]);
        
        return ['success' => true, 'message' => '视频及相关文件删除成功'];
    } catch(Exception $e) {
        return ['success' => false, 'message' => '删除失败: ' . $e->getMessage()];
    }
}

// 路由处理
switch ($method) {
    case 'POST':
        switch ($action) {
            case 'upload':
                echo json_encode(uploadVideo());
                break;
            case 'update_cover':
                echo json_encode(updateCover());
                break;
            case 'delete':
                echo json_encode(deleteVideo());
                break;
            case 'generate_qr':
                echo json_encode(regenerateQRCode());
                break;
            default:
                echo json_encode(['success' => false, 'message' => '未知操作']);
        }
        break;
        
    case 'GET':
        switch ($action) {
            case 'list':
                echo json_encode(getVideoList());
                break;
            case 'list_mine':
                echo json_encode(getMyVideos());
                break;
            case 'get_by_qr':
                $qrCode = $_GET['qr'] ?? '';
                echo json_encode(getVideoByQR($qrCode));
                break;
            default:
                echo json_encode(['success' => false, 'message' => '未知操作']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
}
?>
