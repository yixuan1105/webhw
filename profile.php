<?php
// 設置頁面標題
$page_title = '個人檔案編輯';
$page_css_files = ['common.css']; 

require_once('db.php');   // 引入資料庫工具 (fetchOne, execute)
require_once('iden.php'); // 引入身分驗證
require_once('header.php'); 

// 0. 安全檢查
requireLogin();
if ($_SESSION['user_role'] !== 'student') {
    header("Location: index.php?error=permission_denied");
    exit();
}

// 取得基本變數
$user_id = $_SESSION['user_id'];
$account_name = $_SESSION['username'] ?? 'unknown_user'; 
$upload_dir = "uploads/";
$error = "";
$success = "";

// 確保上傳資料夾存在
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); 
}

// =================================================================
// 1. 讀取目前的資料 (原本包在函式裡，現在直接寫在這裡)
// =================================================================

// 準備 SQL：抓取照片路徑和簡介
$sql_select = "SELECT photo_path, bio FROM users WHERE id = ?";
// 執行查詢 (使用 db.php 的 fetchOne)
$current_data = fetchOne($sql_select, [$user_id]);

// 處理讀取到的資料
if (!$current_data) {
    // 沒抓到資料的情況
    $currentPhotoPath = 'https://via.placeholder.com/180?text=無資料';
    $currentIntro = '';
    $error .= "警告：無法從資料庫載入您的個人檔案資訊。<br>";
} else {
    // 抓到資料了，處理圖片路徑
    if (!empty($current_data['photo_path'])) {
        // 如果有路徑，加上網址前綴 ($base_path 來自 header.php 或全域設定)
        $currentPhotoPath = $base_path . $current_data['photo_path'];
    } else {
        // 如果欄位是空的，給預設圖
        $currentPhotoPath = '';
    }
    // 設定簡介變數
    $currentIntro = $current_data['bio']; 
}

// =================================================================
// 2. 處理表單提交 (當使用者按下儲存按鈕)
// =================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 接收簡介 (去除前後空白)
    $new_intro = trim($_POST['bio'] ?? ''); 
    
    // 預設圖片路徑 = 舊的路徑 (把 $base_path 拿掉，因為資料庫只存相對路徑)
    // 注意：如果 $current_data['photo_path'] 是空的，就給空字串
    $db_photo_path = str_replace($base_path, '', $current_data['photo_path'] ?? '');
    
    $photo_updated = false; // 紀錄是否有更新照片

    // --- 檢查是否有上傳圖片 ---
    if (isset($_FILES["fileToUpload"]) && $_FILES["fileToUpload"]["error"] === UPLOAD_ERR_OK) {
        
        $file_name = $_FILES["fileToUpload"]["name"];
        $file_tmp_name = $_FILES["fileToUpload"]["tmp_name"];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // 檢查副檔名
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            // 設定新檔名 (使用帳號命名)
            $new_filename = $account_name . "." . $file_ext;
            $target_filepath = $upload_dir . $new_filename; 
            
            // 搬移檔案
            if (move_uploaded_file($file_tmp_name, $target_filepath)) {
                $db_photo_path = $target_filepath; // 更新要寫入資料庫的路徑
                $success .= "照片上傳成功！<br>";
                $photo_updated = true;
            } else {
                $error .= "照片存檔失敗。<br>";
            }
        } else {
            $error .= "檔案類型不支援。<br>";
        }
    }

    // --- 執行資料庫更新 (原本包在 update 函式裡，現在直接寫) ---
    if (empty($error)) {
        
        $sql_update = "UPDATE users SET photo_path = ?, bio = ? WHERE id = ?";
        // 執行更新 (使用 db.php 的 execute)
        $result = execute($sql_update, [$db_photo_path, $new_intro, $user_id]);
        
        if ($result !== false) {
            // 更新成功後的訊息
            if ($photo_updated) {
                 $success .= "個人檔案與照片已更新！";
            } else {
                 $success .= "文字簡介已儲存！";
            }

            // === 重要：因為資料更新了，我們要重新讀取一次資料庫，讓網頁顯示最新的 ===
            $current_data = fetchOne($sql_select, [$user_id]);
            
            // 重新設定顯示變數
            if (!empty($current_data['photo_path'])) {
                $currentPhotoPath = $base_path . $current_data['photo_path'];
            }
            $currentIntro = $current_data['bio']; 
            
        } else {
             $error .= "資料庫更新失敗。<br>";
        }
    }
}
?>

<div class="container" style="padding-top: 40px; padding-bottom: 40px; max-width: 800px;">
    
    <h2 style="text-align: center; margin-bottom: 30px; color: #007bff; font-weight: 600;">
        <i class="bi bi-person-circle" style="margin-right: 10px;"></i>
        <?php echo ($_SESSION['user_name'] ?? '學生') ?> 的個人檔案編輯
    </h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card p-4 shadow-lg border-0">
        <form action="profile.php" method="post" enctype="multipart/form-data">
            
            <div class="text-center mb-5">
                <p class="mb-3 text-muted fw-bold">目前大頭貼</p>
                <img 
                    src="<?= $currentPhotoPath ?>" 
                    class="rounded-circle border border-primary border-3"
                    style="width: 180px; height: 180px; object-fit: cover;"
                >
                <p class="small text-muted mt-3">帳號: <?= $account_name ?></p>
            </div>
            
            <div class="mb-4">
                <label for="fileToUpload" class="form-label fw-bold">上傳新的個人照片:</label>
                <input class="form-control" type="file" name="fileToUpload" id="fileToUpload" accept="image/jpeg, image/png, image/gif">
            </div>

            <div class="mb-4">
                <label for="bio" class="form-label fw-bold">個人簡介 / 科系 / 特長：</label>
                <textarea class="form-control" name="bio" id="bio" rows="8" style="resize: none;"><?= $currentIntro ?></textarea>
            </div>
            
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg w-100" name="submit">
                    <i class="bi bi-save"></i> 儲存個人檔案
                </button>
            </div>

        </form>
    </div>
</div>

<?php require_once('footer.php'); ?>