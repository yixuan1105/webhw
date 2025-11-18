<?php
// ==========================================
// login.php - 使用者登入頁面
// ==========================================

// 引入身份驗證模組（包含登入處理函式和 CSRF Token 函數）
require_once('iden.php');

// 初始化錯誤訊息變數（用來顯示登入失敗訊息）
$error_message = '';

// ==========================================
// 檢查是否已登入（避免重複登入）
// ==========================================

// 如果使用者已經登入，直接導向對應頁面
if (isUserLoggedIn()) {
    // 不論使用者角色為何，一律導向到 index.php
    header("Location: index.php");
    exit(); // 確保導向後立即停止腳本執行
}

// ==========================================
// 產生 CSRF Token（用於防止跨站請求偽造攻擊）
// ==========================================

// 呼叫 auth.php 中的 generateCSRFToken() 函數
// 此 Token 會存在 Session 中，並在表單提交時進行驗證
$csrf_token = generateCSRFToken();

// ==========================================
// 處理表單提交（POST 請求）
// ==========================================

// 檢查是否為 POST 請求（即使用者按下「登入」按鈕）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ----------------------------------------
    // 步驟 1：驗證 CSRF Token
    // ----------------------------------------
    
    // 取得使用者提交的 CSRF Token（從隱藏欄位）
    $submitted_token = $_POST['csrf_token'] ?? '';
    
    // 驗證 Token 是否正確
    if (!verifyCSRFToken($submitted_token)) {
        // Token 驗證失敗：顯示錯誤訊息並停止處理
        $error_message = "無效的請求，請重新整理頁面後再試。";
    } else {
        
        // ----------------------------------------
        // 步驟 2：取得並清理使用者輸入
        // ----------------------------------------
        
        // 取得使用者名稱，並使用 trim() 去除前後空白
        // ?? '' 是 null 合併運算子，如果 $_POST['username'] 不存在則回傳空字串
        $username = trim($_POST['username'] ?? '');
        
        // 取得密碼（密碼不需要 trim，因為可能有前後空白）
        $password = $_POST['password'] ?? '';
        
        // ----------------------------------------
        // 步驟 3：基本驗證
        // ----------------------------------------
        
        // 檢查帳號和密碼是否為空
        if (empty($username) || empty($password)) {
            $error_message = "帳號和密碼不能為空。";
        } else {
            
            // ----------------------------------------
            // 步驟 4：呼叫登入處理函式
            // ----------------------------------------
            
            // handleLogin() 會驗證帳號密碼，成功則導向對應頁面
            // 若失敗則返回錯誤訊息字串
            $error_message = handleLogin($username, $password);
            
            // 注意：如果登入成功，handleLogin() 內部會執行 exit()
            // 所以程式執行到這裡表示登入失敗
        }
    }
}

// ==========================================
// 處理 URL 參數中的錯誤訊息
// ==========================================

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
    <!-- 設定網頁編碼為 UTF-8，支援中文顯示 -->
    <meta charset="UTF-8">
    
    <!-- 設定 viewport，讓網頁在手機上正常顯示（響應式設計） -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
    <!-- 網頁標題（顯示在瀏覽器分頁） -->
    <title>登入系統 - 學生成果管理平台</title>
    <link rel="stylesheet" href="login.css">
   
</head>
<body>
    <!-- 登入表單容器 -->
    <div class="login-container">
        <!-- 頁面標題 -->
        <h1>🎓 學生/管理員登入</h1>
        
        <!-- 錯誤訊息區域（只在有錯誤時顯示） -->
        <?php if ($error_message): ?>
            <div class="error-message">
                <!-- 使用 htmlspecialchars() 防止 XSS 攻擊 -->
                <!-- ENT_QUOTES 會轉義單引號和雙引號 -->
                <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- 登入表單 -->
        <!-- action="login.php" 表示提交到當前頁面 -->
        <!-- method="POST" 使用 POST 方法提交（較安全） -->
        <form method="POST" action="login.php">
            
            <!-- CSRF Token 隱藏欄位（防止 CSRF 攻擊） -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            
            <!-- 帳號輸入欄位 -->
            <div class="form-group">
                <label for="username">帳號</label>
                <!-- 
                    id: 用於 label 的 for 屬性連結
                    name: 提交表單時的參數名稱
                    required: HTML5 必填驗證
                    maxlength: 限制最大長度（防止惡意輸入）
                    autocomplete: 允許瀏覽器自動填入
                -->
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

            <!-- 密碼輸入欄位 -->
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

            <!-- 登入按鈕 -->
            <button type="submit" class="login-button">登入</button>
        </form>
        
        <!-- 底部提示文字 -->
        <div class="footer-text">
            © 2025 學生成果管理平台
        </div>
    </div>
</body>
</html>