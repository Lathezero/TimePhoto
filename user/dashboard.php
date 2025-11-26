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
    <!-- 图标库：Font Awesome（可回滚：移除此<link>） -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-9b4b8S7dCzWQ8Q6CkqzC0hRrj3mNf3kqj1xZpG7WQG9tHqFv9z5TVmQXQw3k4Xk9H6Yj6lqQWJmCwYbQbQ5k0w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5; }
        .header { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:16px; }
        .wrap { max-width:1200px; margin:0 auto; padding:0 20px; display:flex; justify-content:space-between; align-items:center; }
        .btn { padding:8px 14px; background:rgba(255,255,255,0.2); color:#fff; border:none; border-radius:5px; text-decoration:none; }
        .container { max-width:1200px; margin:20px auto; padding:20px; }
        .card { background:var(--surface,#f7f9ff); border-radius:var(--radius-md,12px); box-shadow:0 10px 30px rgba(0,0,0,0.12); margin-bottom:20px; }
        .card h2 { margin:0; padding:20px 30px; border-bottom:1px solid #e1e1e1; }
        .content { padding:20px 30px; }
        .form-group { margin-bottom:16px; }
        label { display:block; margin-bottom:6px; color:#333; transition: all 0.3s ease-in-out; }
        input { width:100%; padding:12px; border:2px solid #e1e1e1; border-radius:var(--radius-md,12px); transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out; }
        input:focus { outline:none; border-color:#667eea; box-shadow: 0 10px 30px rgba(0,0,0,0.12); }
        .upload-btn { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; border-radius:var(--radius-md,12px); padding:12px 24px; cursor:pointer; transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out; }
        .upload-btn:hover { transform: translateY(-2px) scale(1.02); box-shadow: 0 10px 30px rgba(0,0,0,0.12); }
        .upload-btn:active { transform: scale(0.98); }
        .video-item { padding:20px 30px; border-bottom:1px solid #e1e1e1; display:grid; grid-template-columns:120px 1fr auto; gap:20px; }
        .video-actions { display:flex; gap:10px; }
        .btn-mini { padding:6px 10px; border:none; border-radius:var(--radius-md,12px); color:#fff; cursor:pointer; transition: transform 0.3s ease-in-out, filter 0.3s ease-in-out; }
        .btn-mini:hover { transform: translateY(-1px) scale(1.02); filter: brightness(1.05); }
        .btn-mini:active { transform: scale(0.98); }
        .primary { background:#007bff; }
        .success { background:#28a745; }
        .danger { background:#dc3545; }
        .message { display:none; margin-bottom:16px; padding:12px; border-radius:6px; }
        .message.success { display:block; background:#e8f7ee; color:#1a7f37; border:1px solid #b7e4c7; }
        .message.error { display:block; background:#fee; color:#c33; border:1px solid #fcc; }
        /* 骨架屏与进场动画（动画时长 0.3s，ease-in-out） */
        .skeleton { background: linear-gradient(90deg, #f0f2f5 25%, #e2e6ea 37%, #f0f2f5 63%); background-size: 400% 100%; animation: shimmer 1.2s ease-in-out infinite; }
        @keyframes shimmer { 0% { background-position: 100% 0 } 100% { background-position: 0 0 } }
        .fade-in-up { opacity: 0; transform: translateY(12px); animation: fadeUp 0.3s ease-in-out forwards; }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0) } }
        /* 二维码弹窗淡入淡出与居中布局 */
        #qrModal { transition: opacity 0.3s ease-in-out; display: none; }
        #qrModal.open { display: block; opacity: 1; }
        #qrModal:not(.open) { opacity: 0; }
        @media (max-width: 768px) {
          .video-item { grid-template-columns: 1fr; gap: 16px; }
        }
    </style>
    <!-- 返回顶部样式（可回滚：移除此<style>） -->
    <style id="enhance-backtotop-styles">
      #backToTop { position: fixed; right: 20px; bottom: 24px; z-index: 999; opacity: 0; pointer-events: none; transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out; border: none; border-radius: 999px; padding: 10px 14px; font-weight: 600; color: #fff; background: linear-gradient(135deg, var(--brand-start, #667eea), var(--brand-end, #764ba2)); box-shadow: var(--shadow-soft, 0 10px 30px rgba(0,0,0,0.12)); }
      #backToTop.show { opacity: 1; pointer-events: auto; }
      #backToTop:hover { transform: translateY(-2px) scale(1.02); }
      #backToTop:active { transform: scale(0.98); }
    </style>
    <!-- 增强样式变量（可回滚：移除此<style>） -->
    <style id="enhance-styles-vars">
      :root { --brand-start:#667eea; --brand-end:#764ba2; --radius-md:12px; --surface:#f7f9ff; --shadow-soft:0 10px 30px rgba(0,0,0,0.12); --transition-fast:0.3s ease-in-out; }
      a:focus-visible, button:focus-visible { outline:3px solid rgba(102,126,234,0.6); outline-offset:2px; }
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
        <div class="modal-content" style="background:var(--surface,#f7f9ff); margin:10% auto; padding:20px; border-radius:10px; width:90%; max-width:500px; text-align:center; position:relative;">
            <span class="close" onclick="closeModal()" style="position:absolute; right:12px; top:8px; font-size:24px; cursor:pointer">&times;</span>
            <h3>视频二维码</h3>
            <div id="qrCodeContainer" style="margin-top:10px"></div>
            <p style="font-size:12px; color:#666">扫描二维码或点击链接访问</p>
        </div>
    </div>
    <script>
        // 输入反馈：焦点时 label 上移与高亮（仅样式，不改逻辑）
        document.querySelectorAll('.form-group input').forEach(function(input){
          const label = input.closest('.form-group').querySelector('label');
          input.addEventListener('focus', function(){ if(label){ label.style.color = '#667eea'; label.style.transform = 'translateY(-2px)'; } });
          input.addEventListener('blur', function(){ if(label){ label.style.color = '#333'; label.style.transform = 'translateY(0)'; } });
        });

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
            requestAnimationFrame(() => modal.classList.add('open')); // 弹窗淡入
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
        function closeModal() { const m = document.getElementById('qrModal'); m.classList.remove('open'); setTimeout(() => { m.style.display = 'none'; }, 200); }

        // 列表图片懒加载与骨架：不影响现有逻辑
        document.addEventListener('DOMContentLoaded', function(){
          document.querySelectorAll('.content img').forEach(function(img){
            if(!img.hasAttribute('loading')) img.setAttribute('loading','lazy');
            img.classList.add('skeleton'); img.addEventListener('load', function(){ img.classList.remove('skeleton'); });
          });
          // 列表项进场
          document.querySelectorAll('.video-item').forEach(function(item, idx){ item.classList.add('fade-in-up'); item.style.animationDelay = (idx * 0.1) + 's'; });
          // 返回顶部按钮
          const btn = document.createElement('button'); btn.id = 'backToTop'; btn.setAttribute('aria-label', '返回顶部'); btn.textContent = '↑ 顶部'; document.body.appendChild(btn);
          function toggleBtn(){ const y = window.scrollY || document.documentElement.scrollTop; if (y > 300) btn.classList.add('show'); else btn.classList.remove('show'); }
          window.addEventListener('scroll', toggleBtn, { passive: true }); toggleBtn(); btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        });
    </script>
</body>
</html>
