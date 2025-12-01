<?php

require_once('iden.php');

// 檢查登入
requireLogin();

// 接收 GET 參數中的 id
$id = $_GET['id'] ?? '';//接收GET參數傳遞過來的要刪除的成果 ID

if ($id) { //只有當成功接收到成果 ID 時，才執行大括號 {} 內的刪除邏輯。
    $user_id = $_SESSION['user_id'];//從 Session 中提取當前登入使用者的 ID。
    // 安全性檢查：確保這筆資料真的屬於目前登入的學生
    $check_sql = "SELECT id FROM achievements WHERE id = ? AND user_id = ?";
    $exists = fetchOne($check_sql, [$id, $user_id]);//執行檢查查詢。如果結果非空，則表示這筆資料存在且權限匹配。
    
    if ($exists) {
        // 執行刪除
        $sql = "DELETE FROM achievements WHERE id = ?"; // 確保資料屬於當前使用者 確認無誤後，才會執行 DELETE 語句

        execute($sql, [$id]);//執行資料庫刪除操作。
    }
}

// 刪除後導回列表頁  
header("Location: achievement.php");
exit();
?>