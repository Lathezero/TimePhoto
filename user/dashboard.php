<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user_username'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? ORDER BY upload_time DESC");
    $stmt->execute([$userId]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $videos = []; }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的视频 - iCloud</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5; }
        .header { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:16px; }
        .wrap { max-width:1200px; margin:0 auto; padding:0 20px; display:flex; justify-content:space-between; align-items:center; }
        .btn { padding:8px 14px; background:rgba(255,255,255,0.2); color:#fff; border:none; border-radius:5px; text-decoration:none; }
        .container { max-width:1200px; margin:20px auto; padding:20px; }
        .card { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-bottom:20px; }
        .card h2 { margin:0; padding:20px 30px; border-bottom:1px solid #e1e1e1; }
        .content { padding:20px 30px; }
        .form-group { margin-bottom:16px; }
        label { display:block; margin-bottom:6px; color:#333; }
        input { width:100%; padding:12px; border:2px solid #e1e1e1; border-radius:6px; }
        input:focus { outline:none; border-color:#667eea; }
        .upload-btn { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; border-radius:6px; padding:12px 24px; cursor:pointer; }
        .video-item { padding:20px 30px; border-bottom:1px solid #e1e1e1; display:grid; grid-template-columns:120px 1fr auto; gap:20px; }
        .video-actions { display:flex; gap:10px; }
        .btn-mini { padding:6px 10px; border:none; border-radius:4px; color:#fff; cursor:pointer; }
        .primary { background:#007bff; }
        .success { background:#28a745; }
        .danger { background:#dc3545; }
        .message { display:none; margin-bottom:16px; padding:12px; border-radius:6px; }
        .message.success { display:block; background:#e8f7ee; color:#1a7f37; border:1px solid #b7e4c7; }
        .message.error { display:block; background:#fee; color:#c33; border:1px solid #fcc; }
    </style>
    </head>
<body>
    <div class="header">
        <div class="wrap">
            <div>欢迎，<?php echo htmlspecialchars($username); ?></div>
            <div>
                <a class="btn" href="dashboard.php">上传视频</a>
                <a class="btn" href="profile.php">资料</a>
                <a class="btn" href="logout.php">退出</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div id="message" class="message"></div>
        <div class="card">
            <h2>上传视频</h2>
            <div class="content">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">视频标题</label>
                        <input id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="video">选择视频文件</label>
                        <input id="video" name="video" type="file" accept="video/*" required>
                    </div>
                    <button class="upload-btn" type="submit" id="uploadBtn">上传视频</button>
                </form>
            </div>
        </div>
        <div class="card">
            <h2>我的视频</h2>
            <div class="content">
                <?php if (empty($videos)): ?>
                    <div style="color:#666">暂无视频</div>
                <?php else: ?>
                    <?php foreach ($videos as $video): ?>
                        <div class="video-item">
                            <div>
                                <?php if ($video['cover_image']): ?>
                                    <img src="../<?php echo COVER_PATH . $video['cover_image']; ?>" alt="封面" style="width:120px; height:90px; object-fit:contain;"/>
                                <?php else: ?>
                                    <div style="width:120px; height:90px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;">无封面</div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight:600; margin-bottom:6px;"><?php echo htmlspecialchars($video['title']); ?></div>
                                <div style="color:#666; font-size:14px;">上传时间：<?php echo $video['upload_time']; ?> | 观看：<?php echo $video['views']; ?></div>
                            </div>
                            <div class="video-actions">
                                <button class="btn-mini primary" onclick="showQR('<?php echo $video['qr_code']; ?>', <?php echo $video['id']; ?>, '<?php echo $video['qr_image_path']; ?>')">二维码</button>
                                <button class="btn-mini success" onclick="updateCover(<?php echo $video['id']; ?>)">封面</button>
                                <button class="btn-mini danger" onclick="deleteVideo(<?php echo $video['id']; ?>)">删除</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <form id="coverForm" style="display:none" enctype="multipart/form-data">
        <input type="file" id="coverFile" accept="image/*">
        <input type="hidden" id="coverVideoId">
    </form>
    <div id="qrModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5)">
        <div class="modal-content" style="background:#fff; margin:10% auto; padding:20px; border-radius:10px; width:90%; max-width:500px; text-align:center; position:relative;">
            <span class="close" onclick="closeModal()" style="position:absolute; right:12px; top:8px; font-size:24px; cursor:pointer">&times;</span>
            <h3>视频二维码</h3>
            <div id="qrCodeContainer" style="margin-top:10px"></div>
            <p style="font-size:12px; color:#666">扫描二维码或点击链接访问</p>
        </div>
    </div>
    <script>
        function showMessage(msg, type) {
            const el = document.getElementById('message');
            el.textContent = msg; el.className = 'message ' + type; el.style.display = 'block';
            setTimeout(() => { el.style.display = 'none'; }, 4000);
        }
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault(); const btn = document.getElementById('uploadBtn'); btn.disabled = true; btn.textContent = '上传中...';
            try {
                const resp = await fetch('../api.php?action=upload', { method: 'POST', body: new FormData(this) });
                const ct = resp.headers.get('content-type') || '';
                if (ct.includes('application/json')) {
                    const res = await resp.json();
                    if (res.success) { showMessage('上传成功', 'success'); setTimeout(() => location.reload(), 1500); }
                    else { showMessage('上传失败: ' + (res.message || '未知错误'), 'error'); }
                } else {
                    const text = await resp.text();
                    showMessage('上传失败: 服务器返回异常页面，请检查上传限制或错误日志', 'error');
                    console.error('非JSON响应:', text);
                }
            } catch (e) { showMessage('上传失败: ' + e.message, 'error'); }
            btn.disabled = false; btn.textContent = '上传视频';
        });
        function updateCover(videoId) { document.getElementById('coverVideoId').value = videoId; document.getElementById('coverFile').click(); }
        document.getElementById('coverFile').addEventListener('change', async function() {
            const videoId = document.getElementById('coverVideoId').value; const file = this.files[0]; if (!file) return;
            const fd = new FormData(); fd.append('cover', file); fd.append('video_id', videoId);
            try { const resp = await fetch('../api.php?action=update_cover', { method: 'POST', body: fd }); const res = await resp.json(); if (res.success) { showMessage('封面更新成功', 'success'); setTimeout(() => location.reload(), 1200); } else { showMessage('失败: ' + res.message, 'error'); } } catch (e) { showMessage('失败: ' + e.message, 'error'); }
        });
        async function deleteVideo(id) {
            if (!confirm('确定删除该视频？')) return; const fd = new FormData(); fd.append('video_id', id);
            try { const resp = await fetch('../api.php?action=delete', { method: 'POST', body: fd }); const res = await resp.json(); if (res.success) { showMessage('删除成功', 'success'); setTimeout(() => location.reload(), 1200); } else { showMessage('失败: ' + res.message, 'error'); } } catch (e) { showMessage('失败: ' + e.message, 'error'); }
        }
        function showQR(qrCode, videoId, qrImagePath) {
            const modal = document.getElementById('qrModal');
            const container = document.getElementById('qrCodeContainer');
            container.innerHTML = '';
            const videoUrl = window.location.origin + '/play.php?qr=' + qrCode;
            if (qrImagePath && qrImagePath !== 'null' && qrImagePath !== '') {
                const img = document.createElement('img');
                img.src = '../uploads/qrcodes/' + qrImagePath;
                img.style.width = '200px'; img.style.height = '200px'; img.alt = '二维码';
                img.onerror = function() { showOnlineQR(container, videoUrl); };
                container.appendChild(img);
            } else {
                showOnlineQR(container, videoUrl);
            }
            const linkDiv = document.createElement('div');
            linkDiv.style.marginTop = '10px';
            linkDiv.style.fontSize = '12px';
            linkDiv.style.wordBreak = 'break-all';
            linkDiv.style.color = '#666';
            linkDiv.style.textAlign = 'center';
            linkDiv.innerHTML = '链接：<a href="' + videoUrl + '" target="_blank">' + videoUrl + '</a>';
            container.appendChild(linkDiv);
            modal.style.display = 'block';
        }
        function showOnlineQR(container, videoUrl) {
            const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(videoUrl);
            const img = document.createElement('img');
            img.src = qrApiUrl; img.style.width = '200px'; img.style.height = '200px'; img.alt = '二维码';
            img.onerror = function() {
                container.innerHTML = '<div style="padding: 20px; text-align: center;"><p>二维码生成失败</p><p style="font-size: 12px; word-break: break-all; margin-top: 10px;">直接访问链接：<br>' + videoUrl + '</p></div>';
            };
            container.appendChild(img);
        }
        function closeModal() { document.getElementById('qrModal').style.display = 'none'; }
    </script>
</body>
</html>
