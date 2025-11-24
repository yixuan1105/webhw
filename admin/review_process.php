<?php
// ==========================================
// admin/review_process.php - 處理審核動作
// ==========================================

// 引入必要的模組
require_once('../iden.php');
require_once('../db.php');

// 權限檢查：強制管理員登入
requireAdminLogin(); 

// 確保腳本只接受 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // 如果不是 POST 請求，導回待審列表並顯示錯誤
    header("Location: pending_list.php?error=" . urlencode("請勿直接訪問審核處理頁面。"));
    exit();
}

// 取得資料庫連線
$pdo = connectDB(); 

$achievement_id = $_POST['achievement_id'] ?? null;
$action = $_POST['action'] ?? ''; // 預期值: 'approve' 或 'reject'
$comment = trim($_POST['reviewer_comment'] ?? '');
$reviewer_id = $_SESSION['user_id'] ?? null; // 從 Session 取得當前管理員 ID

// 預設導向目標 (如果處理成功)
$redirect_url = 'pending_list.php';

// ----------------------------------------
// 步驟 1: 輸入驗證與狀態設定
// ----------------------------------------

if (empty($achievement_id) || !is_numeric($achievement_id) || empty($reviewer_id)) {
    $error_msg = "審核失敗：成果 ID 或審核者 ID 遺失。";
} elseif (!in_array($action, ['approve', 'reject'])) {
    $error_msg = "審核失敗：無效的動作指令。";
} else {
    // 設置更新的狀態和成功訊息
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $success_msg = "成果 ID: {$achievement_id} 已成功設定為 [{$new_status}]。";

    // ----------------------------------------
    // 步驟 2: 資料庫更新
    // ----------------------------------------
    try {
        // 記錄審核時間 (CURRENT_TIMESTAMP)
        $sql = "UPDATE achievements 
                SET status = ?, 
                    reviewer_comment = ?, 
                    reviewer_id = ?,
                    review_date = CURRENT_TIMESTAMP
                WHERE id = ?";

        executeSQL($sql, [$new_status, $comment, $reviewer_id, $achievement_id]);
        
        // 成功處理後，導向回列表頁面並帶上成功訊息
        header("Location: {$redirect_url}?success=" . urlencode($success_msg));
        exit();

    } catch (Exception $e) {
        $error_msg = "資料庫更新失敗: " . $e->getMessage();
    }
}

// ----------------------------------------
// 步驟 3: 處理失敗導向
// ----------------------------------------

// 如果程式碼執行到這裡，表示有錯誤發生，導向列表頁並顯示錯誤訊息
header("Location: {$redirect_url}?error=" . urlencode($error_msg));
exit();

?>