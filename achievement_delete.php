<?php
require_once('iden.php');
require_once('db.php');

// 檢查登入
requireLogin();

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 先去資料庫查這筆資料
$sql = "SELECT file_path FROM achievements WHERE id = ? AND user_id = ?";
$row = fetchOne($sql, [$id, $user_id]);

if ($row) {
    // 如果硬碟有檔案，就用 unlink 刪除它
    if ($row['file_path'] && file_exists($row['file_path'])) {
        unlink($row['file_path']);
    }

    // 刪除資料庫裡的紀錄
    $del_sql = "DELETE FROM achievements WHERE id = ?";
    execute($del_sql, [$id]);
}

// 刪完跳回列表
header("Location: achievement.php");
exit();
?>