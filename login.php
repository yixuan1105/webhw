<?php
require_once('iden.php'); // 引入身份驗證模組

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 檢查表單是否提交
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 呼叫 auth.php 中的登入處理函式
    $error_message = handleLogin($username, $password);
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <title>登入系統</title>
</head>
<body>
    <h1>學生/管理員登入</h1>
    
    <?php if ($error_message): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="username">帳號:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">密碼:</label>
        <input type="password" id="password" name="password" required><br><br>

        <button type="submit">登入</button>
    </form>
</body>
</html>