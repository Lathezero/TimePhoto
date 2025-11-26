<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([intval($_POST['delete_id'])]);
        $message = '删除成功';
    } catch (PDOException $e) { $message = '删除失败'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([intval($_POST['approve_id'])]);
        $message = '已通过审核';
    } catch (PDOException $e) { $message = '操作失败'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disable_id'])) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET status = 'disabled' WHERE id = ?");
        $stmt->execute([intval($_POST['disable_id'])]);
        $message = '已禁用用户';
    } catch (PDOException $e) { $message = '操作失败'; }
}

try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, username, email, status, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $users = []; }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 管理后台</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5; }
        .header { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; padding:16px; }
        .wrap { max-width:1200px; margin:0 auto; padding:0 20px; display:flex; justify-content:space-between; align-items:center; }
        .container { max-width:1200px; margin:20px auto; background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
        .title { padding:20px 30px; border-bottom:1px solid #e1e1e1; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px 16px; border-bottom:1px solid #eee; text-align:left; }
        .msg { margin:16px 30px; padding:12px; border-radius:6px; }
        .success { background:#e8f7ee; color:#1a7f37; border:1px solid #b7e4c7; }
        .btn-del { background:#dc3545; color:#fff; border:none; border-radius:4px; padding:6px 10px; cursor:pointer; }
        a { color:#fff; text-decoration:none; }
    </style>
    </head>
<body>
    <div class="header">
        <div class="wrap">
            <div>用户管理</div>
            <div><a href="index.php">返回视频管理</a></div>
        </div>
    </div>
    <div class="container">
        <div class="title">用户列表</div>
        <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <table>
            <thead>
                <tr><th>ID</th><th>用户名</th><th>邮箱</th><th>状态</th><th>注册时间</th><th>操作</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['status'] ?? 'active'); ?></td>
                        <td><?php echo $u['created_at']; ?></td>
                        <td>
                            <form method="POST" style="display:inline-block">
                                <input type="hidden" name="approve_id" value="<?php echo $u['id']; ?>">
                                <button class="btn-del" type="submit" style="background:#28a745">通过</button>
                            </form>
                            <form method="POST" style="display:inline-block; margin-left:6px">
                                <input type="hidden" name="disable_id" value="<?php echo $u['id']; ?>">
                                <button class="btn-del" type="submit" style="background:#ff9800">禁用</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('确定删除该用户？');" style="display:inline-block">
                                <input type="hidden" name="delete_id" value="<?php echo $u['id']; ?>">
                                <button class="btn-del" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
