<?php
session_start();
require_once '../config.php';

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: profile.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                $status = $user['status'] ?? 'active';
                if ($status === 'active' || $status === null) {
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    header('Location: dashboard.php');
                    exit;
                } elseif ($status === 'pending') {
                    $error = '账户待审核，通过后即可登录';
                } else {
                    $error = '账户已被禁用，请联系管理员';
                }
            } else {
                $error = '用户名或密码错误';
            }
        } catch (PDOException $e) {
            $error = '登录失败，请稍后重试';
        }
    } else {
        $error = '请输入用户名和密码';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户登录</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .card { background:#fff; padding:30px; border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.1); width:100%; max-width:420px; }
        h1 { font-size:24px; margin-bottom:20px; color:#333; text-align:center; }
        .form-group { margin-bottom:16px; }
        label { display:block; margin-bottom:6px; color:#333; }
        input { width:100%; padding:12px; border:2px solid #e1e1e1; border-radius:6px; font-size:16px; }
        input:focus { outline:none; border-color:#667eea; }
        .error { background:#fee; color:#c33; padding:10px; border:1px solid #fcc; border-radius:6px; margin-bottom:16px; }
        .btn { width:100%; padding:12px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; border-radius:6px; font-size:16px; cursor:pointer; }
        .links { text-align:center; margin-top:12px; }
        .links a { color:#667eea; text-decoration:none; }
    </style>
    </head>
<body>
    <div class="card">
        <h1>用户登录</h1>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">用户名</label>
                <input id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input id="password" type="password" name="password" required>
            </div>
            <button class="btn" type="submit">登录</button>
        </form>
        <div class="links">
            <a href="register.php">没有账户？注册</a>
            &nbsp;|&nbsp;
            <a href="../admin/login.php">管理员登录</a>
            &nbsp;|&nbsp;
            <a href="../index.html">首页</a>
        </div>
    </div>
</body>
</html>
