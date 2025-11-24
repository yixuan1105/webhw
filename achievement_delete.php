<?php
// achievement_delete.php - 刪除成果
require_once('iden.php');

// 檢查登入
requireLogin();

// 接收 GET 參數中的 id
$id = $_GET['id'] ?? ''; //它通過 $_GET['id'] 接收要刪除的 ID

if ($id) {
    $user_id = $_SESSION['user_id'];
    
    // 安全性檢查：確保這筆資料真的屬於目前登入的學生
    // 如果不檢查，學生 A 可能會偷刪學生 B 的資料
    $check_sql = "SELECT id FROM achievements WHERE id = ? AND user_id = ?";
    $exists = fetchOne($check_sql, [$id, $user_id]); //fetchOne 執行越權檢查
    
    if ($exists) {
        // 執行刪除
        $sql = "DELETE FROM achievements WHERE id = ?"; // 確保資料屬於當前使用者 確認無誤後，才會執行 DELETE 語句

        execute($sql, [$id]);
    }
}

// 刪除後（或 id 無效(if ($exists) 判斷為 False)）都導回列表頁  
header("Location: achievement.php");
exit();
?>