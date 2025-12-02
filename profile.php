<?php
require_once('db.php');
require_once('iden.php');
require_once('header.php');

// 1. 判斷使用者與目標 ID
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;//獲取當前登入使用者的 ID；如果未登入，設為 0。
$target_id = isset($_GET['id']) ? intval($_GET['id']) : $current_user_id;//優先從 GET 參數中獲取要查看的 ID；如果沒有 ID，則使用當前登入者的 ID。並確保 ID 為整數。

if ($target_id === 0) {//強制檢查：如果沒有有效的目標 ID（表示未登入且 URL 中無 ID），將使用者導回首頁。
    header("Location: index.php");
    exit();
}

$can_edit = ($current_user_id === $target_id);//判斷當前登入者是否就是目標 ID 的擁有者。如果是，則允許編輯（$can_edit 為 true）。

// 設定頁面參數
$page_title = $can_edit ? '編輯個人檔案' : '學生詳細資料';//根據是否能編輯，設定不同的頁面標題。
$upload_dir = "uploads/";//設定上傳檔案的存放目錄路徑。
$error = "";
$success = "";

// 2. 處理表單提交 (僅編輯模式)
if ($can_edit && $_SERVER['REQUEST_METHOD'] === 'POST') { //處理條件1. 允許編輯 ($can_edit 為 true)，2. 請求方式是 POST (表單已提交)。
    
    if ($_SESSION['user_role'] !== 'student') { //只有角色為 'student' 的使用者才能執行編輯操作。
        $error = "您沒有權限執行此操作。";
    } else {
        // 接收簡介 (不進行額外過濾，直接接收)
        $new_intro = trim($_POST['bio'] ?? '');//接收新的個人簡介內容，並去除前後空白。
        
        // 取得舊照片路徑
        $old_data = fetchOne("SELECT photo_path FROM users WHERE id = ?", [$target_id]);//讀取舊資料： 查詢目標 ID 的舊照片路徑，以便稍後判斷是否需要更新。
        $db_photo_path = $old_data['photo_path'] ?? '';//獲取舊照片路徑，如果不存在則設為空字串。
        
        $photo_updated = false;//初始化照片是否更新的標誌。

        // 圖片上傳處理
        if (isset($_FILES["fileToUpload"]) && $_FILES["fileToUpload"]["error"] === UPLOAD_ERR_OK) {//圖片上傳條件檢查： 檢查是否有檔案上傳，並且上傳過程中沒有發生錯誤。
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);//如果上傳目錄不存在，則創建它並賦予權限。

            $file_name = $_FILES["fileToUpload"]["name"];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));//獲取並轉換檔案的副檔名為小寫。
            
            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {//檔案類型檢查： 檢查副檔名是否為允許的圖片格式。
                $new_filename = "user_" . $target_id . "." . $file_ext; //根據目標使用者 ID 生成唯一的檔案名稱。
                $target_filepath = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_filepath)) {//移動檔案： 將暫存檔案移動到指定的目標路徑。
                    $db_photo_path = $target_filepath;//更新資料庫中將儲存的照片路徑為新檔案的路徑。
                    $photo_updated = true;//設定照片已更新標誌。
                } else {
                    $error .= "照片存檔失敗。<br>";
                }
            } else {
                $error .= "檔案類型不支援。<br>";
            }
        }

        // 更新資料庫
        if (empty($error)) {//更新資料庫條件： 只有當之前沒有發生任何錯誤時，才執行資料庫更新。
            $sql_update = "UPDATE users SET photo_path = ?, bio = ? WHERE id = ?"; //準備更新 SQL： 準備更新語句，同時更新照片路徑和簡介。
            $result = execute($sql_update, [$db_photo_path, $new_intro, $target_id]);//執行更新： 執行資料庫更新操作。
            if ($result) {
                $success = "資料更新成功！";
            } else {
                $error = "資料庫更新失敗。";
            }
        }
    }
}

// 3. 讀取顯示資料
$sql_select = "SELECT name, photo_path, bio, role FROM users WHERE id = ?";//準備查詢 SQL： 準備查詢語句，用於提取目標使用者的名稱、照片路徑、簡介和角色。
$user_data = fetchOne($sql_select, [$target_id]);//執行查詢： 執行查詢，將結果存入 $user_data 變數中。

if (!$user_data) {//資料檢查： 如果找不到目標使用者的資料。
    echo '<div class="container py-5"><div class="alert alert-danger">查無此學生資料。</div></div>';
    require_once('footer.php');
    exit();
}

// 準備顯示用的變數 (直接賦值，不使用 htmlspecialchars)
$display_name = $user_data['name'];//獲取並設定顯示用的名稱。
$display_intro = $user_data['bio'];//獲取並設定顯示用的簡介。
$display_photo = !empty($user_data['photo_path']) ? $user_data['photo_path'] : '';

?>

<div class="container" style="padding-top: 40px; padding-bottom: 40px; max-width: 800px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color: #007bff;">
            <i class="bi bi-person-circle"></i> 
            <?php echo $display_name; ?>  <!-- 輸出目標使用者的名稱的檔案 -->
        </h2>
        <?php if (!$can_edit): ?>  <!-- 條件顯示： 如果處於觀看模式（不能編輯），則顯示「返回列表」按鈕。 -->
            <a href="index.php" class ="btn btn-outline-secondary">返回列表</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($success)): ?> <!-- 如果有成功訊息，則顯示綠色提示框並輸出訊息。 -->
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?> <!-- 如果有錯誤訊息，則顯示紅色提示框並輸出訊息。 -->
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card p-4 shadow-lg border-0">
        
    <!-- 模式切換： 根據是否允許編輯，決定使用標籤（編輯模式）還是標籤（觀看模式）。 -->
        <?php if ($can_edit): ?>
            <form action="profile.php?id=<?= $target_id ?>" method="post" enctype="multipart/form-data">
        <?php else: ?>
            <div class="view-mode">
        <?php endif; ?>

            <div class="text-center mb-5">
                <img 
                    src="<?= $display_photo ?>" 輸出照片路徑
                    class="rounded-circle border border-primary border-3"
                    style="width: 180px; height: 180px; object-fit: cover; background: #f0f0f0;"
                >
                <p class="mt-3 fw-bold fs-4"><?= $display_name ?></p>
            </div>
            
            <?php if ($can_edit): ?> <!-- //只有在編輯模式下才顯示「更換大頭貼」的檔案選擇輸入框。 -->
                <div class="mb-4">
                    <label for="fileToUpload" class="form-label fw-bold text-primary">更換大頭貼:</label>
                    <input class="form-control" type="file" name="fileToUpload" id="fileToUpload" accept="image/*">
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary">個人簡介 / 科系 / 特長：</label>
                <?php if ($can_edit): ?> <!-- 條件顯示與預填： 如果是編輯模式，則顯示 textarea 並預填簡介；如果是觀看模式，則顯示純文字，如果簡介為空則顯示提示文字。 -->
                    <textarea class="form-control" name="bio" rows="8" style="resize: none;"><?= $display_intro ?></textarea>
                <?php else: ?>
                    <div class="p-3 bg-light rounded border" style="min-height: 150px; white-space: pre-wrap;">
                        <?= !empty($display_intro) ? $display_intro : '<span class="text-muted">（這位同學很懶，還沒寫簡介）</span>' ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($can_edit): ?> <!-- //條件顯示： 只有在編輯模式下才顯示「儲存修改」按鈕。 -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-save"></i> <!-- 儲存修改 -->
                    </button>
                </div>
            <?php endif; ?>
        <!-- 表單結束切換： 根據模式結束 <form> 或 </div> 標籤。 -->
        <?php if ($can_edit): ?>
            </form>
        <?php else: ?>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php require_once('footer.php'); ?>