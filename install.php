<?php
require_once 'config.php';

try {
    $pdo = getDB();
    $pdo->exec("CREATE TABLE IF NOT EXISTS videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_size BIGINT NOT NULL,
        duration INT DEFAULT 0,
        cover_image VARCHAR(255) DEFAULT NULL,
        qr_code VARCHAR(255) NOT NULL UNIQUE,
        qr_image_path VARCHAR(255) DEFAULT NULL,
        upload_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active','inactive') DEFAULT 'active',
        views INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        status ENUM('pending','active','disabled') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = ?');
    $stmt->execute(['admin']);
    $adminExists = $stmt->fetchColumn();
    if (!$adminExists) {
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password) VALUES (?, ?)');
        $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
    }

    if (!file_exists('uploads/qrcodes')) { mkdir('uploads/qrcodes', 0755, true); }

    echo "<h2>数据库安装成功！</h2>";
    echo "<p>数据库类型：MySQL</p>";
    echo "<p>默认管理员账户：</p>";
    echo "<ul>";
    echo "<li>用户名: admin</li>";
    echo "<li>密码: admin123</li>";
    echo "</ul>";
    echo "<p><a href='admin/'>进入管理后台</a></p>";
    echo "<p><a href='index.html'>访问客户端</a></p>";
} catch (PDOException $e) {
    echo '安装失败: ' . $e->getMessage();
}
?>
