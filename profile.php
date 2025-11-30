<?php
// 設置頁面標題和額外的 CSS 檔案
$page_title = '個人檔案編輯';
$page_css_files = ['profile.css']; // 假設您有一個 profile.css 檔案用於美化

// 引入資料庫連線函式
require_once('db.php');
// 引入身份驗證函式 (包含 session_start(), requireLogin() 等)
require_once('iden.php'); 
// 引入頁面頭部 (包含 HTML <head>, 導覽列, $base_path)
require_once('header.php'); 

// ----------------------------------------------------
// 0. 安全檢查：確保使用者已登入且為學生角色
// ----------------------------------------------------
// 使用 iden.php 中的 requireLogin() 檢查是否登入
requireLogin();

// 檢查角色是否為 student (管理員不應該進入此頁面)
if ($_SESSION['user_role'] !== 'student') {
    // 導向首頁或顯示錯誤
    header("Location: index.php?error=permission_denied");
    exit();
}

// 取得登入學生的核心資料
$user_id = $_SESSION['user_id'];
// 關鍵變數：使用 iden.php 中設定的 'username' 作為檔案命名基礎
$account_name = $_SESSION['username'] ?? 'unknown_user'; 

$upload_dir = "uploads/";
$error = "";
// 使用者偏好的成功訊息變數名稱
$success = "";

// 確保上傳目錄存在 (如果不存在就創建它)
if (!is_dir($upload_dir)) {
    // 遞迴創建目錄並賦予最大權限 (實際生產環境應更保守)
    mkdir($upload_dir, 0777, true); 
}

// ----------------------------------------------------
// 1. 資料庫載入與更新輔助函式
// ----------------------------------------------------

/**
 * 從資料庫載入學生的個人檔案資料
 * @param int $userId 學生ID
 * @return array|false 包含 photo_path 和 introduction 的陣列 (photo_path 包含 $base_path)
 */
function getStudentProfileFromDB($userId) {
    global $base_path;
    // 修正: 更改 SQL 查詢的欄位名稱為 bio
    $sql = "SELECT photo_path, bio FROM users WHERE id = ?";
    // 使用 db.php 提供的 fetchOne 函式
    $profile = fetchOne($sql, [$userId]);
    
    // 如果資料庫有路徑，加上 $base_path 供網頁顯示
    if ($profile && !empty($profile['photo_path'])) {
        // 確保在顯示時，路徑是正確的 (例如 /webhw/uploads/S123456.jpg)
        $profile['photo_path'] = $base_path . $profile['photo_path'];
    } elseif ($profile) {
        // 如果沒有照片，提供預設圖片
        $profile['photo_path'] = 'https://via.placeholder.com/180?text=尚未上傳照片';
    }
    
    // 將資料庫的 'bio' 鍵名轉換為程式中使用的 'introduction' 鍵名
    if ($profile && isset($profile['bio'])) {
        $profile['introduction'] = $profile['bio'];
        unset($profile['bio']); // 移除舊的鍵名
    }

    return $profile;
}

/**
 * 更新學生的照片路徑和簡介到資料庫
 * @param int $userId 學生ID
 * @param string $photoPath 照片路徑 (相對於專案根目錄，例如: uploads/S109876543.jpg)
 * @param string $introduction 個人簡介 (注意：使用 bio 欄位名稱)
 * @return int 受影響的行數
 */
function updateStudentProfileInDB($userId, $photoPath, $introduction) {
    // 修正: 確保 SQL 語句使用 'bio' 欄位名稱
    $sql = "UPDATE users SET photo_path = ?, bio = ? WHERE id = ?";
    // 使用 db.php 提供的 execute 函式
    return execute($sql, [$photoPath, $introduction, $userId]);
}


// ----------------------------------------------------
// 2. 載入目前資料
// ----------------------------------------------------

$current_data = getStudentProfileFromDB($user_id);
if (!$current_data) {
    $currentPhotoPath = 'https://via.placeholder.com/180?text=無資料';
    $currentIntro = '';
    $error .= "警告：無法從資料庫載入您的個人檔案資訊。<br>";
} else {
    $currentPhotoPath = $current_data['photo_path']; // 這是包含 $base_path 的顯示路徑
    $currentIntro = $current_data['bio']; // 這是從 helper function 轉換後的名稱
}


// ----------------------------------------------------
// 3. 處理表單提交 (POST)
// ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 取得新的簡介內容，並清理潛在的 HTML 標籤
    // 修正: 雖然資料庫是 bio，但 HTML 表單 name 仍然是 'introduction'，所以這裡不動
    $new_intro = trim(htmlspecialchars($_POST['introduction'] ?? '')); 
    
    // 預設要寫入資料庫的路徑為舊路徑 (不含 $base_path)
    $db_photo_path = str_replace($base_path, '', $current_data['photo_path'] ?? '');
    
    $photo_updated = false;

    // 檢查是否有檔案上傳
    if (isset($_FILES["fileToUpload"]) && $_FILES["fileToUpload"]["error"] === UPLOAD_ERR_OK) {
        
        $file_name = $_FILES["fileToUpload"]["name"];
        $file_tmp_name = $_FILES["fileToUpload"]["tmp_name"];
        
        $file_info = pathinfo($file_name);
        $file_extension = strtolower($file_info['extension'] ?? '');
        
        // 檢查是否為允許的圖片類型
        if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            
            // 核心邏輯：使用學生的帳號名稱作為新檔名
            $target_filename_base = $account_name; 
            $new_photo_filename = $target_filename_base . "." . $file_extension;
            
            // 完整的目標儲存路徑 (uploads/學號.ext)
            $target_filepath = $upload_dir . $new_photo_filename; 
            
            if (move_uploaded_file($file_tmp_name, $target_filepath)) {
                
                // 儲存到資料庫的路徑 (例如: uploads/S109876543.jpg)
                $db_photo_path = $target_filepath; 
                $success .= "照片上傳成功！<br>";
                $photo_updated = true;
            
            } else {
                $error .= "照片檔案移動失敗，請檢查目錄寫入權限。<br>";
            }
        } else {
            $error .= "不支援的檔案類型。只允許 JPG, JPEG, PNG 或 GIF。<br>";
        }
    } elseif (isset($_FILES["fileToUpload"]) && $_FILES["fileToUpload"]["error"] !== UPLOAD_ERR_NO_FILE) {
        // 處理非 '沒有上傳檔案' 的其他錯誤
        $error .= "檔案上傳錯誤代碼: " . $_FILES["fileToUpload"]["error"] . "。<br>";
    }

    // 4. 更新資料庫
    if (empty($error)) {
        // updateStudentProfileInDB 已經修正為使用 bio 欄位
        if (updateStudentProfileInDB($user_id, $db_photo_path, $new_intro) !== false) {
            
            // 訊息顯示優化
            if (!$photo_updated) {
                 $success .= "個人簡介文字已儲存！";
            } else {
                 // 如果照片也更新了，給一個更完整的訊息
                 $success .= "個人檔案（包含照片）已成功更新！";
            }

            // 重新載入資料以更新頁面顯示
            $current_data = getStudentProfileFromDB($user_id);
            $currentPhotoPath = $current_data['photo_path']; 
            $currentIntro = $current_data['introduction']; 
        } else {
             $error .= "資料庫更新失敗，請檢查 SQL 語句或欄位名稱。<br>";
        }
    }
}

// ----------------------------------------------------
// 5. HTML 顯示 (使用 Bootstrap 樣式)
// ----------------------------------------------------
?>
<div class="container" style="padding-top: 40px; padding-bottom: 40px; max-width: 800px;">
    
    <h2 style="text-align: center; margin-bottom: 30px; color: #007bff; font-weight: 600;">
        <i class="bi bi-person-circle" style="margin-right: 10px;"></i>
        <?php echo htmlspecialchars($_SESSION['user_name'] ?? '學生') ?> 的個人檔案編輯
    </h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="alert">
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="card p-4 shadow-lg border-0">
        <!-- 表單 name 必須是 'introduction' 才能被 PHP 接收 -->
        <form action="profile.php" method="post" enctype="multipart/form-data">
            
            <!-- 目前照片區塊 -->
            <div class="text-center mb-5">
                <p class="mb-3 text-muted fw-bold">目前大頭貼 (檔名將使用您的帳號)</p>
                <img 
                    src="<?= htmlspecialchars($currentPhotoPath) ?>" 
                    alt="目前的大頭貼" 
                    class="rounded-circle border border-primary border-3"
                    style="width: 180px; height: 180px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.1);"
                    onerror="this.src='https://via.placeholder.com/180?text=圖片無法顯示'"
                >
                <p class="small text-muted mt-3">（帳號: <?= htmlspecialchars($account_name) ?>）</p>
            </div>
            
            <!-- 照片上傳 -->
            <div class="mb-4">
                <label for="fileToUpload" class="form-label fw-bold">上傳新的個人照片:</label>
                <!-- name 必須是 fileToUpload 來匹配 PHP 處理邏輯 -->
                <input class="form-control" type="file" name="fileToUpload" id="fileToUpload" accept="image/jpeg, image/png, image/gif">
                <div class="form-text">檔案將自動以您的帳號 (<?= htmlspecialchars($account_name) ?>) 命名並覆蓋舊照片。</div>
            </div>

            <!-- 個人簡介 -->
            <div class="mb-4">
                <label for="introduction" class="form-label fw-bold">個人簡介 / 科系 / 特長：</label>
                <!-- name 必須是 'introduction' 才能被 PHP 接收 -->
                <textarea 
                    class="form-control" 
                    name="introduction" 
                    id="introduction" 
                    rows="8" 
                    placeholder="請輸入您的個人簡介、科系、專長技能等..." 
                    style="resize: none;"
                ><?= htmlspecialchars($currentIntro) ?></textarea>
            </div>
            
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg w-100" name="submit" style="background-color: #007bff; border-color: #007bff;">
                    <i class="bi bi-save"></i> 儲存個人檔案
                </button>
            </div>

        </form>
    </div>
</div>

<?php
// 引入頁面底部
require_once('footer.php'); 
?>