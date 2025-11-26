<?php
require_once 'config.php';

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'videos' AND COLUMN_NAME = 'qr_image_path'");
    $stmt->execute([DB_NAME]);
    $hasQrImagePath = (bool)$stmt->fetchColumn();
    if (!$hasQrImagePath) {
        $pdo->exec("ALTER TABLE videos ADD COLUMN qr_image_path VARCHAR(255) DEFAULT NULL");
        echo "<h2>数据库更新成功！</h2>";
        echo "<p>已添加 qr_image_path 字段到 videos 表</p>";
    } else {
        echo "<h2>数据库已是最新版本</h2>";
        echo "<p>qr_image_path 字段已存在</p>";
    }

    $stmt = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'videos' AND COLUMN_NAME = 'user_id'");
    $stmt->execute([DB_NAME]);
    $hasUserId = (bool)$stmt->fetchColumn();
    if (!$hasUserId) {
        $pdo->exec("ALTER TABLE videos ADD COLUMN user_id INT DEFAULT NULL AFTER id");
        echo "<p>已添加 user_id 字段到 videos 表</p>";
    } else {
        echo "<p>user_id 字段已存在</p>";
    }
    echo "<h3>当前 videos 表结构：</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>字段名</th><th>类型</th><th>默认值</th></tr>";
    $stmt = $pdo->query("DESCRIBE videos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><a href='admin/'>返回管理后台</a></p>";

    // 为 users 表添加 status 字段并初始化
    echo "<h3>检查 users 表结构：</h3>";
    $stmt = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'status'");
    $stmt->execute([DB_NAME]);
    $hasUserStatus = (bool)$stmt->fetchColumn();
    if (!$hasUserStatus) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('pending','active','disabled') DEFAULT 'pending' AFTER email");
        $pdo->exec("UPDATE users SET status = 'active' WHERE status IS NULL");
        echo "<p>已添加 users.status 字段，并将现有用户初始化为 active</p>";
    } else {
        echo "<p>users.status 字段已存在</p>";
    }
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;">";
    echo "<tr><th>字段名</th><th>类型</th><th>默认值</th></tr>";
    $stmt = $pdo->query("DESCRIBE users");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "<tr><td>" . htmlspecialchars($c['Field']) . "</td><td>" . htmlspecialchars($c['Type']) . "</td><td>" . htmlspecialchars($c['Default'] ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo '数据库更新失败: ' . $e->getMessage();
}
?>
