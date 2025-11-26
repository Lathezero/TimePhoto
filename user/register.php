<?php
session_start();
require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $captcha = trim($_POST['captcha'] ?? '');
    if (!$username || !$password || !$confirm || !$captcha) {
        $error = '请完整填写信息';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少为6位';
    } elseif ($password !== $confirm) {
        $error = '两次输入的密码不一致';
    } elseif (!isset($_SESSION['captcha_code']) || strcasecmp($captcha, $_SESSION['captcha_code']) !== 0) {
        $error = '验证码错误';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetchColumn()) {
                $error = '用户名已存在';
            } else {
                $chk = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'status'");
                $chk->execute([DB_NAME]);
                $hasStatus = (bool)$chk->fetchColumn();
                if (!$hasStatus) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('pending','active','disabled') DEFAULT 'pending' AFTER email");
                    $pdo->exec("UPDATE users SET status = 'active' WHERE status IS NULL");
                }
                $stmt = $pdo->prepare('INSERT INTO users (username, password, email, status) VALUES (?, ?, ?, ?)');
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $email ?: null, 'pending']);
                $success = '注册已提交，需管理员审核通过后才能登录';
            }
        } catch (PDOException $e) {
            $error = '注册失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5; display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .card { background:#fff; padding:30px; border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.1); width:100%; max-width:420px; }
        h1 { font-size:24px; margin-bottom:20px; color:#333; text-align:center; }
        .form-group { margin-bottom:16px; }
        label { display:block; margin-bottom:6px; color:#333; }
        input { width:100%; padding:12px; border:2px solid #e1e1e1; border-radius:6px; font-size:16px; }
        input:focus { outline:none; border-color:#667eea; }
        .row { display:flex; gap:10px; align-items:center; }
        .error { background:#fee; color:#c33; padding:10px; border:1px solid #fcc; border-radius:6px; margin-bottom:16px; }
        .success { background:#e8f7ee; color:#1a7f37; padding:10px; border:1px solid #b7e4c7; border-radius:6px; margin-bottom:16px; }
        .btn { width:100%; padding:12px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; border-radius:6px; font-size:16px; cursor:pointer; }
        .links { text-align:center; margin-top:12px; }
        .links a { color:#667eea; text-decoration:none; }
    </style>
    </head>
<body>
    <div class="card">
        <h1>用户注册</h1>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">用户名</label>
                <input id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">邮箱</label>
                <input id="email" name="email" type="email">
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div class="form-group">
                <label for="confirm">确认密码</label>
                <input id="confirm" name="confirm" type="password" required>
            </div>
            <div class="form-group">
                <label>验证码</label>
                <div class="row">
                    <input name="captcha" required placeholder="输入图片中的字符">
                    <img src="captcha.php" alt="验证码" style="height:40px" onclick="this.src='captcha.php?'+Date.now()">
                </div>
            </div>
            <button class="btn" type="submit">注册</button>
        </form>
        <div class="links">
            <a href="login.php">已有账户？登录</a>
        </div>
    </div>
</body>
</html>
