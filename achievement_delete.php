<?php

require_once('iden.php');

// 檢查登入
requireLogin();

// 接收 GET 參數中的 id
$id = $_GET['id'] ?? '';

if ($id) {
    $user_id = $_SESSION['user_id'];
    // 安全性檢查：確保這筆資料真的屬於目前登入的學生
    $check_sql = "SELECT id FROM achievements WHERE id = ? AND user_id = ?";
    $exists = fetchOne($check_sql, [$id, $user_id]);
    
    if ($exists) {
        // 執行刪除
        $sql = "DELETE FROM achievements WHERE id = ?"; // 確保資料屬於當前使用者 確認無誤後，才會執行 DELETE 語句

        execute($sql, [$id]);
    }
}

// 刪除後導回列表頁  
header("Location: achievement.php");
exit();
?>