<?php

require_once('iden.php');

$error_message = '';

// 檢查是否已登入
if (isUserLoggedIn()) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: profile.php");
    }
    exit();
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 步驟 1：直接取得使用者輸入 (移除了 CSRF 驗證)
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 步驟 2：基本驗證
    if (empty($username) || empty($password)) {
        $error_message = "帳號和密碼不能為空。";
    } else {
        // 步驟 3：呼叫登入處理函式
        $error_message = handleLogin($username, $password);
    }
}

// 處理 URL 參數中的錯誤訊息
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'permission_denied':
            $error_message = "權限不足，請使用管理員帳號登入。";
            break;
        case 'session_expired':
            $error_message = "您的登入已逾時，請重新登入。";
            break;
        default:
            $error_message = "發生錯誤，請重新登入。";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>登入系統 - 學生成果管理平台</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h1>🎓 學生/管理員登入</h1>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            
            <div class="form-group">
                <label for="username">帳號</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="請輸入帳號"
                    required 
                    maxlength="50"
                    autocomplete="username"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">密碼</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="請輸入密碼"
                    required
                    maxlength="100"
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="login-button">登入</button>
        </form>
        
        <div class="footer-text">
            © 2025 學生成果管理平台
        </div>
    </div>
</body>
</html>