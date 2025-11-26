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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-9b4b8S7dCzWQ8Q6CkqzC0hRrj3mNf3kqj1xZpG7WQG9tHqFv9z5TVmQXQw3k4Xk9H6Yj6lqQWJmCwYbQbQ5k0w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style id="enhance-styles-vars">
      :root { --brand-start:#667eea; --brand-end:#764ba2; --bg-neutral:#f6f7fb; --text-primary:#1f2937; --text-secondary:#4b5563; --radius-md:12px; --shadow-soft:0 10px 30px rgba(0,0,0,0.12); --transition-fast:0.3s ease-in-out; }
      a:focus-visible, button:focus-visible { outline:3px solid rgba(102,126,234,0.6); outline-offset:2px; }
    </style>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:var(--bg-neutral); color:var(--text-primary); }
        .header { background:linear-gradient(135deg,var(--brand-start) 0%,var(--brand-end) 100%); color:#fff; padding:16px; }
        .wrap { max-width:1200px; margin:0 auto; padding:0 20px; display:flex; justify-content:space-between; align-items:center; }
        .container { max-width:1200px; margin:20px auto; background:#fff; border-radius:var(--radius-md); box-shadow:var(--shadow-soft); overflow:hidden; }
        .title { padding:20px 30px; border-bottom:1px solid #e1e1e1; font-weight:600; }
        .table-wrap { width:100%; overflow:auto; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#f8f9fa; color:var(--text-secondary); font-weight:600; }
        th, td { padding:14px 18px; border-bottom:1px solid #eee; text-align:left; }
        tbody tr:nth-child(odd) { background:#fcfcfd; }
        tbody tr:hover { background:#f5f7ff; }
        .msg { margin:16px 30px; padding:12px; border-radius:var(--radius-md); }
        .success { background:#e8f7ee; color:#1a7f37; border:1px solid #b7e4c7; }
        .status-badge { display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; }
        .status-active { background:#e6f4ea; color:#1a7f37; }
        .status-disabled { background:#fdecea; color:#c33; }
        .status-pending { background:#fff4e5; color:#b45309; }
        .btn { padding:8px 12px; border:none; border-radius:var(--radius-md); cursor:pointer; font-size:12px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition: transform var(--transition-fast), filter var(--transition-fast), box-shadow var(--transition-fast); }
        .btn:hover { transform: translateY(-1px) scale(1.02); filter: brightness(1.06); }
        .btn:active { transform: scale(0.98); }
        .btn-success { background:#28a745; color:#fff; }
        .btn-warning { background:#ff9800; color:#fff; }
        .btn-danger { background:#dc3545; color:#fff; }
        a { color:#fff; text-decoration:none; }
        @media (max-width:768px) { .wrap { padding:0 16px; } .title { padding:16px 20px; } th, td { padding:12px 14px; } }
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
        <div class="table-wrap">
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
                          <td><?php $status = htmlspecialchars($u['status'] ?? 'active'); $cls = ($status==='active')?'status-active':(($status==='disabled')?'status-disabled':'status-pending'); echo '<span class="status-badge ' . $cls . '">' . $status . '</span>'; ?></td>
                          <td><?php echo $u['created_at']; ?></td>
                          <td>
                              <form method="POST" style="display:inline-block">
                                  <input type="hidden" name="approve_id" value="<?php echo $u['id']; ?>">
                                  <button class="btn btn-success" type="submit"><i class="fa-solid fa-check"></i>通过</button>
                              </form>
                              <form method="POST" style="display:inline-block; margin-left:6px">
                                  <input type="hidden" name="disable_id" value="<?php echo $u['id']; ?>">
                                  <button class="btn btn-warning" type="submit"><i class="fa-solid fa-ban"></i>禁用</button>
                              </form>
                              <form method="POST" onsubmit="return confirm('确定删除该用户？');" style="display:inline-block; margin-left:6px">
                                  <input type="hidden" name="delete_id" value="<?php echo $u['id']; ?>">
                                  <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i>删除</button>
                              </form>
                          </td>
                      </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
        </div>
    </div>
</body>
</html>
