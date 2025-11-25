<?php

// 引入身份驗證模組
require_once('iden.php');

// 初始化錯誤訊息變數（顯示登入失敗訊息）
$error_message = '';

// 使用者登入，導向對應頁面
if (isUserLoggedIn()) {
    //導向到 index.php
    header("Location: index.php");
    exit(); // 確保導向後立即停止腳本執行
}

//呼叫 iden.php 中的 generateCSRFToken() 函數
//Token 會存在 Session 中，並在表單提交時進行驗證
$csrf_token = generateCSRFToken();

// 處理表單提交（POST 請求）
// 檢查是否為 POST 請求（即使用者按下「登入」按鈕）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //驗證 CSRF Token
    // 取得使用者提交的 CSRF Token（從隱藏欄位）
    $submitted_token = $_POST['csrf_token'] ?? '';
    
    // 驗證 Token 是否正確
    if (!verifyCSRFToken($submitted_token)) {
        // Token 驗證失敗：顯示錯誤訊息並停止處理
        $error_message = "無效的請求，請重新整理頁面後再試。";
    } else {
        
        // 取得並清理使用者輸入
        
        // 取得使用者名稱，並使用 trim() 去除前後空白
        // ?? '' 是 null 合併運算子，如果 $_POST['username'] 不存在則回傳空字串
        $username = trim($_POST['username'] ?? '');
        
        // 取得密碼（密碼不需要 trim，因為可能有前後空白）
        $password = $_POST['password'] ?? '';
        
        // 基本驗證
        
        // 檢查帳號和密碼是否為空
        if (empty($username) || empty($password)) {
            $error_message = "帳號和密碼不能為空。";
        } else {
            
            // 呼叫登入處理函式
            
            // handleLogin() 會驗證帳號密碼，成功則導向對應頁面
            // 若失敗則返回錯誤訊息字串
            $error_message = handleLogin($username, $password);
            
        }
    }
}

// 處理 URL 參數中的錯誤訊息

// 檢查 URL 是否包含 error 參數（例如：login.php?error=permission_denied）
if (isset($_GET['error'])) {
    // 根據不同的錯誤代碼顯示對應訊息
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
    <title>登入系統 - 學生成果管理平台</title>
    <link rel="stylesheet" href="login.css">
    
</head>
<body>
    <div class="login-container">
        <h1>🎓 學生/管理員登入</h1>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
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
                    value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>"
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
        
        <div class="footer-text">2025 學生成果管理平台</div>
    </div>
</body>
</html>