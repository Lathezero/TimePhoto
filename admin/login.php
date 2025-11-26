<?php
session_start();
require_once '../config.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                header('Location: index.php');
                exit;
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
    <title>管理员登录 - 视频管理系统</title>
    <!-- 图标库：Font Awesome（可回滚：移除此<link>） -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-9b4b8S7dCzWQ8Q6CkqzC0hRrj3mNf3kqj1xZpG7WQG9tHqFv9z5TVmQXQw3k4Xk9H6Yj6lqQWJmCwYbQbQ5k0w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12); width: 100%; max-width: 420px; opacity: 0; transform: translateY(12px); animation: fadeUp 0.3s ease-in-out forwards; }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h1 { color: #333; font-size: 28px; margin-bottom: 10px; }
        .login-header p { color: #666; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e1e1e1; border-radius: 5px; font-size: 16px; transition: border-color 0.3s; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .error-message { background: #fee; color: #c33; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #fcc; }
        .login-btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 16px; cursor: pointer; transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out; }
        .login-btn:hover { transform: translateY(-2px) scale(1.02); box-shadow: 0 10px 30px rgba(0,0,0,0.12); }
        .login-btn:active { transform: scale(0.98); }
        .login-btn:focus-visible { outline: 3px solid rgba(102,126,234,0.6); outline-offset: 2px; }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0) } }
        :root { --brand-start:#667eea; --brand-end:#764ba2; }
        a:focus-visible, button:focus-visible { outline:3px solid rgba(102,126,234,0.6); outline-offset:2px; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #667eea; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
    </head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>管理员登录</h1>
            <p>视频管理系统后台</p>
        </div>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">登录</button>
        </form>
        <div class="back-link">
            <a href="../">返回首页</a>
            &nbsp;|&nbsp;
            <a href="../user/login.php">用户登录</a>
        </div>
    </div>
</body>
</html>
