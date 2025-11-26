<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$userId = $_SESSION['user_id'];
$username = $_SESSION['user_username'];
$email = '';

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = $row ? ($row['email'] ?? '') : '';
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newEmail = trim($_POST['email'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    try {
        $pdo = getDB();
        if ($newEmail !== $email) {
            $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
            $stmt->execute([$newEmail ?: null, $userId]);
            $email = $newEmail;
            $success = '资料已更新';
        }
        if ($newPassword) {
            if (strlen($newPassword) < 6) {
                $error = '密码长度至少为6位';
            } elseif ($newPassword !== $confirm) {
                $error = '两次输入的密码不一致';
            } else {
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
                $success = $success ? $success : '密码已更新';
            }
        }
    } catch (PDOException $e) {
        $error = '更新失败，请稍后重试';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人资料</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5; min-height:100vh; }
        .header { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:16px; }
        .container { max-width:800px; margin:20px auto; background:#fff; padding:24px; border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
        h1 { font-size:22px; margin-bottom:16px; color:#333; }
        .form-group { margin-bottom:16px; }
        label { display:block; margin-bottom:6px; color:#333; }
        input { width:100%; padding:12px; border:2px solid #e1e1e1; border-radius:6px; font-size:16px; }
        input:focus { outline:none; border-color:#667eea; }
        .row { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        .error { background:#fee; color:#c33; padding:10px; border:1px solid #fcc; border-radius:6px; margin-bottom:16px; }
        .success { background:#e8f7ee; color:#1a7f37; padding:10px; border:1px solid #b7e4c7; border-radius:6px; margin-bottom:16px; }
        .btn { padding:12px 18px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; border-radius:6px; font-size:16px; cursor:pointer; }
        .actions { display:flex; justify-content:space-between; align-items:center; margin-top:12px; }
        a { color:#667eea; text-decoration:none; }
    </style>
    </head>
<body>
    <div class="header">
        <div style="max-width:800px; margin:0 auto; display:flex; justify-content:space-between; align-items:center;">
            <div>欢迎，<?php echo htmlspecialchars($username); ?></div>
            <div>
                <a href="dashboard.php" style="color:#fff; margin-right:12px;">上传视频</a>
                <a href="dashboard.php" style="color:#fff; margin-right:12px;">我的视频</a>
                <a href="logout.php" style="color:#fff;">退出登录</a>
            </div>
        </div>
    </div>
    <div class="container">
        <h1>编辑个人资料</h1>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>用户名</label>
                <input value="<?php echo htmlspecialchars($username); ?>" disabled>
            </div>
            <div class="form-group">
                <label for="email">邮箱</label>
                <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div class="row">
                <div class="form-group">
                    <label for="password">新密码</label>
                    <input id="password" name="password" type="password" placeholder="不修改请留空">
                </div>
                <div class="form-group">
                    <label for="confirm">确认新密码</label>
                    <input id="confirm" name="confirm" type="password">
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">保存</button>
                <a href="../">返回首页</a>
            </div>
        </form>
    </div>
</body>
</html>
