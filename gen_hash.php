<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pwd = $_POST['pwd'] ?? '';
    if ($pwd === '') { echo '请输入密码'; exit; }
    echo password_hash($pwd, PASSWORD_DEFAULT);
    exit;
}
?>
<!doctype html>
<html><body>
<form method="post">
    <input name="pwd" placeholder="输入要生成的密码" />
    <button type="submit">生成散列</button>
</form>
</body></html>