<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT v.*, u.username AS uploader_username FROM videos v LEFT JOIN users u ON v.user_id = u.id WHERE v.status = 'active' ORDER BY v.upload_time DESC");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $videos = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iCloud 视频管理系统 - 管理后台</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; transition: background 0.3s; }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .upload-section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .upload-section h2 { margin-bottom: 20px; color: #333; }
        .upload-form { display: grid; gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-weight: 500; color: #333; }
        .form-group input { padding: 12px; border: 2px solid #e1e1e1; border-radius: 5px; font-size: 16px; transition: border-color 0.3s; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .upload-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: transform 0.2s; }
        .upload-btn:hover { transform: translateY(-2px); }
        .upload-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .video-list { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .video-list h2 { padding: 20px 30px; background: #f8f9fa; margin: 0; color: #333; border-bottom: 1px solid #e1e1e1; }
        .video-item { padding: 20px 30px; border-bottom: 1px solid #e1e1e1; display: grid; grid-template-columns: 120px 1fr auto; gap: 20px; align-items: flex-start; }
        .video-item:last-child { border-bottom: none; }
        .video-cover { width: 120px; min-height: 80px; max-height: 120px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #666; font-size: 12px; overflow: hidden; position: relative; }
        .video-cover img { width: 100%; height: auto; min-height: 80px; max-height: 120px; object-fit: contain; border-radius: 8px; }
        .video-cover.no-cover { height: 80px; background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%); border: 2px dashed #ccc; }
        .video-info h3 { margin-bottom: 5px; color: #333; }
        .video-meta { color: #666; font-size: 14px; }
        .video-actions { display: flex; gap: 10px; }
        .btn { padding: 6px 12px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; transition: opacity 0.3s; }
        .btn:hover { opacity: 0.8; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .qr-code { width: 100px; height: 100px; margin: 10px 0; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 15% auto; padding: 20px; border-radius: 10px; width: 90%; max-width: 500px; text-align: center; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: black; }
        .message { padding: 15px; margin: 20px 0; border-radius: 5px; display: none; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        @media (max-width: 768px) { .video-item { grid-template-columns: 1fr; gap: 15px; text-align: center; } .video-cover { width: 100%; max-width: 200px; margin: 0 auto; } .header-content { flex-direction: column; gap: 10px; } }
    </style>
    </head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>iCloud</h1>
            <div class="user-info">
                <span>欢迎，<?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="users.php" class="logout-btn">用户管理</a>
                <a href="../" class="logout-btn">返回首页</a>
                <a href="logout.php" class="logout-btn">退出登录</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div id="message" class="message"></div>
        <div class="upload-section" style="display:none">
            <h2>上传视频（已限制为普通用户）</h2>
        </div>
        <div class="video-list">
            <h2>视频列表</h2>
            <?php if (empty($videos)): ?>
                <div style="padding: 40px; text-align: center; color: #666;">暂无视频，请先上传视频</div>
            <?php else: ?>
                <?php foreach ($videos as $video): ?>
                    <div class="video-item">
                        <div class="video-cover <?php echo $video['cover_image'] ? '' : 'no-cover'; ?>">
                            <?php if ($video['cover_image']): ?>
                                <img src="../<?php echo COVER_PATH . $video['cover_image']; ?>" alt="<?php echo htmlspecialchars($video['title']); ?>封面" loading="lazy">
                            <?php else: ?>
                                <div style="text-align: center;"><div style="font-size: 24px; margin-bottom: 5px;">🎬</div><div>无封面</div></div>
                            <?php endif; ?>
                        </div>
                        <div class="video-info">
                            <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                            <div class="video-meta">
                                <div>文件名: <?php echo htmlspecialchars($video['original_name']); ?></div>
                                <div>大小: <?php echo round($video['file_size'] / 1024 / 1024, 2); ?> MB</div>
                                <div>上传者: <?php echo htmlspecialchars($video['uploader_username'] ?? '未知'); ?></div>
                                <div>上传时间: <?php echo $video['upload_time']; ?></div>
                                <div>观看次数: <?php echo $video['views']; ?></div>
                                <div>状态: <?php echo $video['status'] === 'active' ? '正常' : '已删除'; ?></div>
                            </div>
                        </div>
                        <div class="video-actions">
                            <button class="btn btn-primary" onclick="showQR('<?php echo $video['qr_code']; ?>', <?php echo $video['id']; ?>, '<?php echo $video['qr_image_path']; ?>')">查看二维码</button>
                            <?php if ($video['qr_image_path']): ?>
                                <a href="../uploads/qrcodes/<?php echo $video['qr_image_path']; ?>" download class="btn btn-success">下载二维码</a>
                            <?php else: ?>
                                <button class="btn btn-success" onclick="generateQR(<?php echo $video['id']; ?>)">生成二维码</button>
                            <?php endif; ?>
                            <button class="btn btn-success" onclick="updateCover(<?php echo $video['id']; ?>)">更换封面</button>
                            <?php if ($video['status'] === 'active'): ?>
                                <button class="btn btn-danger" onclick="deleteVideo(<?php echo $video['id']; ?>)">删除</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>视频二维码</h3>
            <div id="qrCodeContainer"></div>
            <p>扫描二维码观看视频</p>
        </div>
    </div>
    <form id="coverForm" style="display: none;" enctype="multipart/form-data">
        <input type="file" id="coverFile" accept="image/*">
        <input type="hidden" id="coverVideoId">
    </form>
    <script src="../qrcode.min.js"></script>
    <script>
        function showMessage(message, type) {
            const messageEl = document.getElementById('message');
            messageEl.textContent = message;
            messageEl.className = 'message ' + type;
            messageEl.style.display = 'block';
            setTimeout(() => { messageEl.style.display = 'none'; }, 5000);
        }
        // 管理员不支持上传，上传入口已隐藏
        function showQR(qrCode, videoId, qrImagePath) {
            const modal = document.getElementById('qrModal');
            const container = document.getElementById('qrCodeContainer');
            container.innerHTML = '';
            const videoUrl = window.location.origin + '/play.php?qr=' + qrCode;
            if (qrImagePath && qrImagePath !== 'null' && qrImagePath !== '') {
                const img = document.createElement('img');
                img.src = '../uploads/qrcodes/' + qrImagePath; img.style.width = '200px'; img.style.height = '200px'; img.alt = '二维码';
                img.onerror = function() { showOnlineQR(container, videoUrl); };
                container.appendChild(img);
            } else { showOnlineQR(container, videoUrl); }
            const linkDiv = document.createElement('div');
            linkDiv.style.marginTop = '10px'; linkDiv.style.fontSize = '12px'; linkDiv.style.wordBreak = 'break-all'; linkDiv.style.color = '#666'; linkDiv.style.textAlign = 'center';
            linkDiv.innerHTML = '扫描二维码或点击链接：<br><a href="' + videoUrl + '" target="_blank">' + videoUrl + '</a>';
            container.appendChild(linkDiv);
            modal.style.display = 'block';
        }
        function showOnlineQR(container, videoUrl) {
            const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(videoUrl);
            const img = document.createElement('img');
            img.src = qrApiUrl; img.style.width = '200px'; img.style.height = '200px'; img.alt = '二维码';
            img.onerror = function() { container.innerHTML = '<div style="padding: 20px; text-align: center;"><p>二维码生成失败</p><p style="font-size: 12px; word-break: break-all; margin-top: 10px;">直接访问链接：<br>' + videoUrl + '</p></div>'; };
            container.appendChild(img);
        }
        function closeModal() { document.getElementById('qrModal').style.display = 'none'; }
        window.onclick = function(event) { const modal = document.getElementById('qrModal'); if (event.target === modal) { modal.style.display = 'none'; } };
        function updateCover(videoId) { document.getElementById('coverVideoId').value = videoId; document.getElementById('coverFile').click(); }
        document.getElementById('coverFile').addEventListener('change', async function() {
            const videoId = document.getElementById('coverVideoId').value; const file = this.files[0]; if (!file) return;
            const formData = new FormData(); formData.append('cover', file); formData.append('video_id', videoId);
            try { const response = await fetch('../api.php?action=update_cover', { method: 'POST', body: formData }); const result = await response.json(); if (result.success) { showMessage('封面更新成功！', 'success'); setTimeout(() => location.reload(), 2000); } else { showMessage('封面更新失败: ' + result.message, 'error'); } } catch (error) { showMessage('封面更新失败: ' + error.message, 'error'); }
        });
        async function deleteVideo(videoId) {
            if (!confirm('确定要删除这个视频吗？')) return;
            const formData = new FormData(); formData.append('video_id', videoId);
            try { const response = await fetch('../api.php?action=delete', { method: 'POST', body: formData }); const result = await response.json(); if (result.success) { showMessage('删除成功！', 'success'); setTimeout(() => location.reload(), 2000); } else { showMessage('删除失败: ' + result.message, 'error'); } } catch (error) { showMessage('删除失败: ' + error.message, 'error'); }
        }
    </script>
</body>
</html>
