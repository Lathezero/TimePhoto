<?php
require_once 'config.php';

$qrCode = $_GET['qr'] ?? '';
$video = null;
$error = '';

if ($qrCode) {
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
        } else {
            $error = '视频不存在或已被删除';
        }
    } catch(PDOException $e) {
        $error = '获取视频信息失败';
    }
} else {
    $error = '无效的访问链接';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $video ? htmlspecialchars($video['title']) : '视频播放'; ?> - iCloud</title>
    <!-- 图标库：Font Awesome（可回滚：移除此<link>） -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-9b4b8S7dCzWQ8Q6CkqzC0hRrj3mNf3kqj1xZpG7WQG9tHqFv9z5TVmQXQw3k4Xk9H6Yj6lqQWJmCwYbQbQ5k0w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000;
            color: white;
            line-height: 1.6;
        }
        
        .header {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: transform 0.3s;
        }
        
        .header.hidden {
            transform: translateY(-100%);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 8px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .video-container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
        }
        
        .video-player {
            width: 100%;
            height: 100%;
            max-width: 100vw;
            max-height: 100vh;
            object-fit: contain;
        }
        
        .video-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 60px 20px 20px;
            transform: translateY(100%);
            transition: transform 0.3s;
        }
        
        .video-info.show {
            transform: translateY(0);
        }
        
        .video-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .video-meta {
            display: flex;
            gap: 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
            padding: 20px;
        }
        
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .error-title {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .error-message {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 30px;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .controls.show {
            opacity: 1;
        }
        
        .control-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 18px;
        }
        
        .control-btn:hover {
            background: rgba(0, 0, 0, 0.9);
            border-color: rgba(255, 255, 255, 0.6);
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .video-title {
                font-size: 20px;
            }
            
            .video-meta {
                font-size: 12px;
                gap: 15px;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 200px;
                text-align: center;
            }
        }
        
        /* 全屏样式 */
        .video-container:-webkit-full-screen {
            width: 100vw;
            height: 100vh;
        }
        
        .video-container:-moz-full-screen {
            width: 100vw;
            height: 100vh;
        }
        
        .video-container:fullscreen {
            width: 100vw;
            height: 100vh;
        }
    </style>
</head>
<body>
    <?php if ($video): ?>
        <div class="header" id="header">
            <div class="header-content">
                <div class="logo">☁ iCloud</div>
                <a href="index.html" class="back-btn">← 返回首页</a>
            </div>
        </div>
        
        <div class="video-container" id="videoContainer">
            <video 
                class="video-player" 
                id="videoPlayer"
                controls
                preload="auto"
                poster="<?php echo $video['cover_image'] ? COVER_PATH . $video['cover_image'] : ''; ?>"
                muted
            >
                <source src="<?php echo UPLOAD_PATH . $video['filename']; ?>" type="video/mp4">
                您的浏览器不支持视频播放。
            </video>
            
            <div class="video-info" id="videoInfo">
                <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                <div class="video-meta">
                    <div class="meta-item">
                        <span class="fa-solid fa-eye" aria-hidden="true"></span>
                        <span><?php echo $video['views']; ?> 次观看</span>
                    </div>
                    <div class="meta-item">
                        <span class="fa-solid fa-calendar" aria-hidden="true"></span>
                        <span><?php echo date('Y-m-d', strtotime($video['upload_time'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="fa-solid fa-user" aria-hidden="true"></span>
                        <span><?php echo htmlspecialchars($video['uploader_username'] ?? '未知'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="fa-solid fa-file" aria-hidden="true"></span>
                        <span><?php echo round($video['file_size'] / 1024 / 1024, 1); ?> MB</span>
                    </div>
                </div>
            </div>
            
            <div class="controls" id="controls">
                <button class="control-btn" id="playPauseBtn" title="播放/暂停">
                    <span id="playPauseIcon">▶</span>
                </button>
                <button class="control-btn" id="fullscreenBtn" title="全屏">
                    <span>⛶</span>
                </button>
                <button class="control-btn" id="infoBtn" title="显示/隐藏信息">
                    <span>ℹ</span>
                </button>
            </div>
        </div>
        
        <!-- 增强样式：主题变量、信息面板与控件动效（可回滚：移除本<style>块） -->
        <style id="enhance-styles">
          :root {
            --brand-start: #667eea; --brand-end: #764ba2;
            --text-primary: #ffffff; --text-secondary: rgba(255,255,255,0.78);
            --radius-md: 12px; --shadow-soft: 0 10px 30px rgba(0,0,0,0.12);
            --transition-fast: 0.3s ease-in-out; --transition-mid: 0.4s ease-in-out;
          }

          .video-info { transition: transform var(--transition-mid), opacity var(--transition-mid); opacity: 0; }
          .video-info.show { opacity: 1; }
          .video-title { letter-spacing: 0.2px; }
          .video-meta { border-top: 1px solid rgba(255,255,255,0.12); padding-top: 12px; }

          .control-btn { transition: transform var(--transition-fast), background var(--transition-fast), border-color var(--transition-fast); }
          .control-btn:hover { transform: scale(1.06); }
          .control-btn:active { transform: scale(0.98); }
          .control-btn:focus-visible { outline: 3px solid rgba(102,126,234,0.6); outline-offset: 2px; }

          /* 顶部导航滚动增强（若出现滚动） */
          #header { transition: background var(--transition-fast), box-shadow var(--transition-fast); }
          #header.scrolled { background: rgba(0,0,0,0.9); box-shadow: 0 12px 30px rgba(0,0,0,0.35); }
        </style>

        <!-- 增强脚本：信息面板淡入、导航滚动状态（可回滚：移除本<script>块） -->
        <script id="enhance-scripts">
          (function() {
            const headerEl = document.getElementById('header');
            function onScroll() { const y = window.scrollY || document.documentElement.scrollTop; if (!headerEl) return; if (y > 50) headerEl.classList.add('scrolled'); else headerEl.classList.remove('scrolled'); }
            window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
          })();
        </script>
        <script>
            const video = document.getElementById('videoPlayer');
            const header = document.getElementById('header');
            const videoInfo = document.getElementById('videoInfo');
            const controls = document.getElementById('controls');
            const playPauseBtn = document.getElementById('playPauseBtn');
            const playPauseIcon = document.getElementById('playPauseIcon');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const infoBtn = document.getElementById('infoBtn');
            const videoContainer = document.getElementById('videoContainer');
            
            let hideTimeout;
            let isInfoVisible = false;
            
            // 显示/隐藏控制元素
            function showControls() {
                header.classList.remove('hidden');
                controls.classList.add('show');
                clearTimeout(hideTimeout);
                hideTimeout = setTimeout(hideControls, 3000);
            }
            
            function hideControls() {
                if (!video.paused) {
                    header.classList.add('hidden');
                    controls.classList.remove('show');
                    if (isInfoVisible) {
                        videoInfo.classList.remove('show');
                        isInfoVisible = false;
                    }
                }
            }
            
            // 鼠标移动显示控制
            document.addEventListener('mousemove', showControls);
            document.addEventListener('touchstart', showControls);
            
            // 播放/暂停控制
            playPauseBtn.addEventListener('click', function() {
                if (video.paused) {
                    video.play();
                } else {
                    video.pause();
                }
            });
            
            // 更新播放按钮图标
            video.addEventListener('play', function() {
                playPauseIcon.textContent = '⏸';
            });
            
            video.addEventListener('pause', function() {
                playPauseIcon.textContent = '▶';
                showControls();
            });
            
            // 全屏控制
            fullscreenBtn.addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    videoContainer.requestFullscreen().catch(err => {
                        console.log('无法进入全屏模式:', err);
                    });
                } else {
                    document.exitFullscreen();
                }
            });
            
            // 信息显示控制
            infoBtn.addEventListener('click', function() {
                if (isInfoVisible) {
                    videoInfo.classList.remove('show');
                    isInfoVisible = false;
                } else {
                    videoInfo.classList.add('show');
                    isInfoVisible = true;
                }
            });
            
            // 点击视频播放/暂停
            video.addEventListener('click', function() {
                if (video.paused) {
                    video.play();
                } else {
                    video.pause();
                }
            });
            
            // 空格键播放/暂停
            document.addEventListener('keydown', function(e) {
                if (e.code === 'Space') {
                    e.preventDefault();
                    if (video.paused) {
                        video.play();
                    } else {
                        video.pause();
                    }
                }
                
                // ESC键退出全屏
                if (e.code === 'Escape' && document.fullscreenElement) {
                    document.exitFullscreen();
                }
                
                // F键全屏
                if (e.code === 'KeyF') {
                    if (!document.fullscreenElement) {
                        videoContainer.requestFullscreen();
                    } else {
                        document.exitFullscreen();
                    }
                }
            });
            
            // 初始显示控制
            showControls();
            
            // 视频加载完成后自动播放
            video.addEventListener('loadedmetadata', function() {
                // 尝试自动播放视频
                video.play().catch(error => {
                    console.log('自动播放被浏览器阻止:', error);
                    // 如果自动播放被阻止，显示播放提示
                    showAutoplayPrompt();
                });
            });
            
            // 显示自动播放提示
            function showAutoplayPrompt() {
                const prompt = document.createElement('div');
                prompt.style.cssText = `
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: rgba(0, 0, 0, 0.8);
                    color: white;
                    padding: 20px;
                    border-radius: 10px;
                    text-align: center;
                    z-index: 1001;
                    backdrop-filter: blur(10px);
                `;
                prompt.innerHTML = `
                    <div style="font-size: 48px; margin-bottom: 15px;">▶️</div>
                    <div style="font-size: 18px; margin-bottom: 10px;">点击播放视频</div>
                    <div style="font-size: 14px; color: rgba(255,255,255,0.7);">浏览器需要用户交互才能播放</div>
                `;
                
                // 点击提示框播放视频
                prompt.addEventListener('click', function() {
                    video.play();
                    prompt.remove();
                });
                
                videoContainer.appendChild(prompt);
                
                // 3秒后自动隐藏提示
                setTimeout(() => {
                    if (prompt.parentNode) {
                        prompt.remove();
                    }
                }, 3000);
            }
            
            // 页面加载完成后尝试自动播放
            document.addEventListener('DOMContentLoaded', function() {
                // 延迟一点时间确保视频元素完全加载
                setTimeout(() => {
                    if (video.readyState >= 1) { // HAVE_METADATA
                        video.play().catch(error => {
                            console.log('页面加载时自动播放被阻止:', error);
                            showAutoplayPrompt();
                        });
                    }
                }, 500);
            });
            
            // 尝试在视频可以播放时自动播放
            video.addEventListener('canplay', function() {
                if (video.paused) {
                    video.play().catch(error => {
                        console.log('canplay事件自动播放被阻止:', error);
                        showAutoplayPrompt();
                    });
                }
            });
            
            // 如果用户与页面有任何交互，取消静音并重新播放
            let hasInteracted = false;
            function handleUserInteraction() {
                if (!hasInteracted) {
                    hasInteracted = true;
                    video.muted = false;
                    if (video.paused) {
                        video.play();
                    }
                    // 移除事件监听器
                    document.removeEventListener('click', handleUserInteraction);
                    document.removeEventListener('touchstart', handleUserInteraction);
                    document.removeEventListener('keydown', handleUserInteraction);
                }
            }
            
            document.addEventListener('click', handleUserInteraction);
            document.addEventListener('touchstart', handleUserInteraction);
            document.addEventListener('keydown', handleUserInteraction);
        </script>
        
    <?php else: ?>
        <div class="error-container">
            <div class="error-icon">🎬</div>
            <div class="error-title">视频不存在</div>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <div class="error-actions">
                <a href="index.html" class="btn btn-primary">返回首页</a>
                <a href="javascript:history.back()" class="btn btn-secondary">返回上页</a>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
