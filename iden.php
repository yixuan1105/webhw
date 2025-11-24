<?php
// 啟動 Session 機制 儲存使用者資訊
session_start();

require_once('db.php');

/**
 * 處理使用者登入邏輯
 * 
 * @param string $username 使用者輸入的帳號
 * @param string $password 使用者輸入的密碼（明文）
 * @return string|void 若登入失敗返回錯誤訊息，成功則導向頁面
 */
function handleLogin($username, $password) {
    
    //從資料庫的 users 表格中，找到符合某個 username 的那一筆使用者資料
    $sql = "SELECT id, username, password, role, name FROM users WHERE username = ?";
    
    // 使用 fetchOne() 取得單筆使用者資料，參數 [$username] 會自動綁定到 SQL 的 ?
    $user = fetchOne($sql, [$username]);

    //查詢結果為空
    if (!$user) {
        return "帳號不存在。";
    }

    //驗證密碼是否正確    
    //password_verify()驗證明文密碼與加密後的 hash 是否匹配
    //$user['password']：資料庫中儲存的加密密碼

    if ($password === $user['password']) {
        //密碼正確，建立 Session
        
        // 防止 Session Fixation 攻擊：重新產生 Session ID
        // 這會使舊的 session ID 失效，防止攻擊者劫持 session
        session_regenerate_id(true);
        
        // 將使用者資訊存入 Session 中
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        
        // 記錄登入時間，可用於自動登出機制
        $_SESSION['login_time'] = time();

        header("Location: index.php");
            exit();
        
    } else {
        // 密碼錯誤：返回錯誤訊息
        return "密碼錯誤。";
    }
}

//登出清除所有 Session 資料並導向首頁

function logout() {
    
    // 清空所有 Session 變數（但 Session 本身仍存在）
    session_unset();
    
    // 導向首頁
    header("Location: index.php");

    exit();
}

// 權限檢查函式

//檢查使用者是否已登入
//@return bool 已登入返回 true，未登入返回 false

function isUserLoggedIn() {
    // 檢查 Session 中是否存在 user_id
    // isset() 檢查變數是否存在且不為 null
    return isset($_SESSION['user_id']);
}

/**
 * 強制要求使用者必須為管理員
 * 若未登入或非管理員，則強制導向登入頁面
 * 適用於管理員專屬頁面（例如：後台管理、會員管理等）
 */
function requireAdminLogin() {
    //使用者未登入
    //使用者角色不是 admin
    if (!isUserLoggedIn() || $_SESSION['user_role'] !== 'admin') {        
        // 導向登入頁面，並帶上錯誤參數 error=permission_denied
        // 登入頁面可以根據此參數顯示「權限不足」訊息
        header("Location: login.php?error=permission_denied");

        exit();
    }
}

/**
 * 強制要求使用者必須已登入
 * 適用於一般需要登入的頁面
 */
function requireLogin() {
    // 檢查使用者是否已登入
    if (!isUserLoggedIn()) {
        
        // 未登入：導向登入頁面，並帶上當前頁面網址
        // 登入成功後可以自動導回原本要訪問的頁面
        $current_page = urlencode($_SERVER['REQUEST_URI']);
        header("Location: login.php?redirect=$current_page");
        
        // 停止執行後續程式碼
        exit();
    }
}

// CSRF Token 相關函式（額外安全機制）

/**
 * 產生 CSRF Token 並存入 Session
 * 應在登入表單中呼叫此函數
 * 
 * @return string 返回產生的 CSRF Token
 */
function generateCSRFToken() {
    // 如果 Session 中尚未存在 csrf_token，則產生一個新的
    if (!isset($_SESSION['csrf_token'])) {
        // bin2hex(random_bytes(32)) 產生 64 字元的隨機字串
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // 返回 CSRF Token
    return $_SESSION['csrf_token'];
}

/**
 * 驗證 CSRF Token 是否正確
 * 應在處理登入請求時呼叫此函數
 * 
 * @param string $token 使用者提交的 CSRF Token
 * @return bool 驗證成功返回 true，失敗返回 false
 */
function verifyCSRFToken($token) {
    // 檢查 Session 中是否存在 csrf_token
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // 使用 hash_equals() 比較兩個字串是否相同
    // 此函數可以防止計時攻擊（Timing Attack）
    return hash_equals($_SESSION['csrf_token'], $token);
}