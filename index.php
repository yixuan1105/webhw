<?php
// ==========================================
// index.php - 網站首頁
// ==========================================
// 功能：提供登入和搜尋的入口

// 啟動 Session（檢查是否已登入）
session_start();

// 如果使用者已經登入，直接導向對應頁面
if (isset($_SESSION['user_id'])) {
    // 檢查使用者角色
    if ($_SESSION['user_role'] === 'admin') {
        // 管理員：導向後台
        header("Location: admin/dashboard.php");
    } else {
        // 學生：導向個人頁面
        header("Location: profile.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <!-- 設定網頁編碼 -->
    <meta charset="UTF-8">
    
    <!-- 響應式設計 viewport 設定 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
    <!-- 網頁標題 -->
    <title>學生學習成果認證系統</title>
    <link rel="stylesheet" href="index.css">
    
</head>
<body>
    <!-- 主要容器 -->
    <div class="main-container">
        <!-- 標題區域 -->
        <h1>🎓 學生學習成果認證系統</h1>
        <p class="subtitle">
            展示您的專業技能與學習成果<br>
            讓才華被看見
        </p>
        
        <!-- 按鈕區域 -->
        <div class="button-group">
            <!-- 登入按鈕 -->
            <a href="login.php" class="btn btn-primary">
                🔐 登入系統
            </a>
            
            <!-- 搜尋人才按鈕（訪客功能） -->
            <a href="search.php" class="btn btn-secondary">
                🔍 搜尋人才
            </a>
        </div>
        
        <!-- 特色說明 -->
        <div class="features">
            <h2>系統特色</h2>
            <ul class="feature-list">
                <li>學生可以建立個人檔案，展示專業技能</li>
                <li>上傳學習成果，包含證照、競賽、專長等</li>
                <li>管理員審核認證，確保成果真實性</li>
                <li>訪客可搜尋符合需求的專業人才</li>
            </ul>
        </div>
        
        <!-- 頁尾 -->
        <div class="footer">
            © 2025 學生學習成果認證系統
        </div>
    </div>
</body>
</html>