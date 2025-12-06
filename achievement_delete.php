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
    $row = fetchOne($check_sql, [$id, $user_id]);
    if ($row) {
        
        //刪除實體檔案 (新增的邏輯)
        $file_path = $row['file_path'];
        
        // 檢查路徑是否不為空，且檔案真的存在於硬碟上
        if (!empty($file_path) && file_exists($file_path)) {
            // unlink() 是 PHP 用來刪除檔案的函式
            unlink($file_path); 
        }

        //執行資料庫刪除
        $sql = "DELETE FROM achievements WHERE id = ?";
        execute($sql, [$id]);
    }
}

// 刪除後導回列表頁  
header("Location: achievement.php");
exit();
?>