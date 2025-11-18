<?php
// ==========================================
// admin/dashboard.php - 管理員儀表板
// ==========================================

// 引入身份驗證模組
require_once('../iden.php'); // 注意路徑: 從 admin/ 跳回上層目錄

// 執行權限檢查：確保只有管理員可以進入此頁面
requireAdminLogin(); // iden.php 中的函式

// 取得登入者的名稱 (用於歡迎訊息)
$admin_name = htmlspecialchars($_SESSION['user_name'] ?? '管理員');

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>管理員儀表板</title>
    <link rel="stylesheet" href="../css/style.css"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>👋 歡迎，<?php echo $admin_name; ?></h1>
        <p class="lead">這是管理員的專屬頁面，您擁有最高的系統權限。</p>

        <div class="mt-4">
            <a href="pending_list.php" class="btn btn-primary me-2">審核待處理成果</a>
            <a href="user_management.php" class="btn btn-secondary">使用者管理</a>
            
            <a href="../logout.php" class="btn btn-danger float-end">
                登出系統
            </a>
        </div>

        <div class="row mt-5">
           
        </div>
    </div>
</body>
</html>