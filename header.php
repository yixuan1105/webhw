<?php
// test 123
// 啟動 Session 加油
// session_status() 檢查 Session 是否已經啟動，避免重複啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 讀取 Session 資訊，以便導覽列使用
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';

// 
// 變數 $page_title 和 $page_css_files 
// 應該在引用此檔案 *之前* 的頁面 (例如 index.php) 中定義
//

if (!isset($page_title)) {
    $page_title = '學生學習成果認證系統'; // 預設標題
}
if (!isset($page_css_files)) {
    $page_css_files = []; // 預設沒有額外 CSS
}

// 假設您的專案根目錄在 http://localhost/webhw/
// 我們使用絕對路徑 /webhw/ 來確保 CSS 和連結在任何頁面都能正確載入
$base_path = '/webhw/';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($page_title); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
    
    <link rel="stylesheet" href="<?php echo $base_path; ?>common.css">
    
    <?php foreach ($page_css_files as $css_file): ?>
        <link rel="stylesheet" href="<?php echo $base_path . ($css_file); ?>">
    <?php endforeach; ?>
    
</head>
<body>

<header>
    <nav class="navbar">
        <div class="container">
            <h1 style="margin: 0; font-size: 20px;">
                <a href="<?php echo $base_path; ?>index.php" style="color:white; text-decoration:none;">🎓 學生學習成果認證系統</a>
            </h1>
            
            <ul class="navbar-menu" style="margin: 0;">
                <?php if ($is_logged_in): ?>
                    <li style="padding: 8px 10px; color: #eee;">
                        <?php echo ($user_name); ?>
                    </li>
                    
                    <?php if ($user_role === 'admin'): ?>
                        <li><a href="<?php echo $base_path; ?>admin/review.php">審核列表</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_path; ?>profile.php">個人檔案</a></li>
                        <li><a href="<?php echo $base_path; ?>achievement.php">成果列表</a></li>
                    <?php endif; ?>

                    <li><a href="<?php echo $base_path; ?>logout.php" style="background-color: #e74c3c; padding: 8px 15px;">登出</a></li>
                    
                <?php else: ?>
                    <li><a href="<?php echo $base_path; ?>login.php" style="background-color: #3498db; padding: 8px 15px;">登入</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>