<?php

// auth.php - 身份驗證核心邏輯模組
// 注意：此檔案應在所有需要身份驗證的頁面最上方引用

// 啟動 Session 機制，讓伺服器可以追蹤使用者狀態
// Session 會在伺服器端儲存使用者資訊，並透過 Cookie 傳遞 Session ID
session_start();

// 引用資料庫連線模組，讓此檔案可以使用 fetchOne() 等資料庫函數
require_once('db.php');

// ==========================================
// 登入處理函式
// ==========================================

/**
 * 處理使用者登入邏輯
 * 
 * @param string $username 使用者輸入的帳號
 * @param string $password 使用者輸入的密碼（明文）
 * @return string|void 若登入失敗返回錯誤訊息，成功則導向頁面
 */
function handleLogin($username, $password) {
    
    // ----------------------------------------
    // 步驟 1：從資料庫查詢使用者資料
    // ----------------------------------------
    
    // SQL 查詢語句：根據帳號查詢使用者的 id, username, password, role, name
    // ⚠️ 注意：必須包含 username 欄位，否則後續 Session 設定會出錯
    $sql = "SELECT id, username, password, role, name FROM users WHERE username = ?";
    
    // 使用 fetchOne() 取得單筆使用者資料，參數 [$username] 會自動綁定到 SQL 的 ?
    $user = fetchOne($sql, [$username]);

    // 如果查詢結果為空（帳號不存在），返回錯誤訊息
    if (!$user) {
        return "帳號不存在。";
    }

    // ----------------------------------------
    // 步驟 2：驗證密碼是否正確
    // ----------------------------------------
    
    // password_verify() 是 PHP 內建函數，用來驗證明文密碼與加密後的 hash 是否匹配
    // $password：使用者輸入的明文密碼
    // $user['password']：資料庫中儲存的加密密碼（應該是用 password_hash() 產生的）
    if ($password === $user['password']) {
        
        // ----------------------------------------
        // 步驟 3：密碼正確，建立 Session
        // ----------------------------------------
        
        // 防止 Session Fixation 攻擊：重新產生 Session ID
        // 這會使舊的 Session ID 失效，防止攻擊者劫持 Session
        session_regenerate_id(true);
        
        // 將使用者資訊存入 Session 中，讓後續頁面可以識別使用者身份
        $_SESSION['user_id'] = $user['id'];           // 儲存使用者 ID
        $_SESSION['username'] = $user['username'];     // 儲存使用者帳號
        $_SESSION['user_role'] = $user['role'];        // 儲存使用者角色（admin 或 student）
        $_SESSION['user_name'] = $user['name'];        // 儲存使用者真實姓名
        
        // 記錄登入時間，可用於自動登出機制
        $_SESSION['login_time'] = time();

        // ----------------------------------------
        // 步驟 4：根據使用者角色導向不同頁面
        // ----------------------------------------
        
        // 檢查使用者角色是否為管理員
        header("Location: index.php");
            exit();
        
    } else {
        // 密碼錯誤：返回錯誤訊息
        return "密碼錯誤。";
    }
}

// ==========================================
// 登出函式
// ==========================================

/**
 * 處理使用者登出邏輯
 * 清除所有 Session 資料並導向首頁
 */
function logout() {
    // 注意：不需要再次呼叫 session_start()，因為此檔案開頭已經啟動
    // 如果重複呼叫會產生 PHP Warning
    
    // 清空所有 Session 變數（但 Session 本身仍存在）
    session_unset();
    
    // 完全銷毀 Session（包含伺服器端的 Session 檔案）
    session_destroy();
    
    // 導向首頁或登入頁面
    header("Location: index.php");
    
    // 停止執行後續程式碼
    exit();
}

// ==========================================
// 權限檢查函式
// ==========================================

/**
 * 檢查使用者是否已登入
 * 
 * @return bool 已登入返回 true，未登入返回 false
 */
function isUserLoggedIn() {
    // 檢查 Session 中是否存在 user_id
    // isset() 會檢查變數是否存在且不為 null
    return isset($_SESSION['user_id']);
}

/**
 * 強制要求使用者必須為管理員
 * 若未登入或非管理員，則強制導向登入頁面
 * 適用於管理員專屬頁面（例如：後台管理、會員管理等）
 */
function requireAdminLogin() {
    // 檢查條件 1：使用者未登入
    // 檢查條件 2：使用者角色不是 admin
    if (!isUserLoggedIn() || $_SESSION['user_role'] !== 'admin') {
        
        // 導向登入頁面，並帶上錯誤參數 error=permission_denied
        // 登入頁面可以根據此參數顯示「權限不足」訊息
        header("Location: login.php?error=permission_denied");
        
        // 停止執行後續程式碼，防止未授權存取
        exit();
    }
}

/**
 * 強制要求使用者必須已登入（不限角色）
 * 適用於一般需要登入的頁面（例如：個人資料、成果管理等）
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

// ==========================================
// CSRF Token 相關函式（額外安全機制）
// ==========================================

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

// ==========================================
// Session 逾時檢查（選用功能）
// ==========================================

/**
 * 檢查 Session 是否已逾時（超過 30 分鐘無操作）
 * 可在每個頁面開頭呼叫此函數
 * 
 * @param int $timeout 逾時秒數（預設 1800 秒 = 30 分鐘）
 */
function checkSessionTimeout($timeout = 1800) {
    // 檢查 Session 中是否記錄了最後活動時間
    if (isset($_SESSION['last_activity'])) {
        
        // 計算距離上次活動已經過了多少秒
        $elapsed = time() - $_SESSION['last_activity'];
        
        // 如果超過逾時時間，則強制登出
        if ($elapsed > $timeout) {
            logout();
        }
    }
    
    // 更新最後活動時間為當前時間
    $_SESSION['last_activity'] = time();
}

?>