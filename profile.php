<?php
require_once('db.php');
require_once('iden.php');
require_once('header.php');

// 1. 判斷使用者與目標 ID
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$target_id = isset($_GET['id']) ? intval($_GET['id']) : $current_user_id;

if ($target_id === 0) {
    header("Location: index.php");
    exit();
}

$can_edit = ($current_user_id === $target_id);

// 設定頁面參數
$page_title = $can_edit ? '編輯個人檔案' : '學生詳細資料';
$upload_dir = "uploads/";
$error = "";
$success = "";

// 2. 處理表單提交 (僅編輯模式)
if ($can_edit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($_SESSION['user_role'] !== 'student') {
        $error = "您沒有權限執行此操作。";
    } else {
        // 接收簡介 (不進行額外過濾，直接接收)
        $new_intro = trim($_POST['bio'] ?? '');
        
        // 取得舊照片路徑
        $old_data = fetchOne("SELECT photo_path FROM users WHERE id = ?", [$target_id]);
        $db_photo_path = $old_data['photo_path'] ?? '';
        
        $photo_updated = false;

        // 圖片上傳處理
        if (isset($_FILES["fileToUpload"]) && $_FILES["fileToUpload"]["error"] === UPLOAD_ERR_OK) {
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_name = $_FILES["fileToUpload"]["name"];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $new_filename = "user_" . $target_id . "." . $file_ext; 
                $target_filepath = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_filepath)) {
                    $db_photo_path = $target_filepath;
                    $photo_updated = true;
                } else {
                    $error .= "照片存檔失敗。<br>";
                }
            } else {
                $error .= "檔案類型不支援。<br>";
            }
        }

        // 更新資料庫
        if (empty($error)) {
            $sql_update = "UPDATE users SET photo_path = ?, bio = ? WHERE id = ?";
            $result = execute($sql_update, [$db_photo_path, $new_intro, $target_id]);
            if ($result) {
                $success = "資料更新成功！";
            } else {
                $error = "資料庫更新失敗。";
            }
        }
    }
}

// 3. 讀取顯示資料
$sql_select = "SELECT name, photo_path, bio, role FROM users WHERE id = ?";
$user_data = fetchOne($sql_select, [$target_id]);

if (!$user_data) {
    echo '<div class="container py-5"><div class="alert alert-danger">查無此學生資料。</div></div>';
    require_once('footer.php');
    exit();
}

// 準備顯示用的變數 (直接賦值，不使用 htmlspecialchars)
$display_name = $user_data['name'];
$display_intro = $user_data['bio'];
$display_photo = !empty($user_data['photo_path']) ? $user_data['photo_path'] : 'https://via.placeholder.com/180?text=No+Image';

?>

<div class="container" style="padding-top: 40px; padding-bottom: 40px; max-width: 800px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color: #007bff;">
            <i class="bi bi-person-circle"></i> 
            <?php echo $display_name; ?> 的檔案
        </h2>
        <?php if (!$can_edit): ?>
            <a href="index.php" class="btn btn-outline-secondary">返回列表</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card p-4 shadow-lg border-0">
        
        <?php if ($can_edit): ?>
            <form action="profile.php?id=<?= $target_id ?>" method="post" enctype="multipart/form-data">
        <?php else: ?>
            <div class="view-mode">
        <?php endif; ?>

            <div class="text-center mb-5">
                <img 
                    src="<?= $display_photo ?>" 
                    class="rounded-circle border border-primary border-3"
                    style="width: 180px; height: 180px; object-fit: cover; background: #f0f0f0;"
                >
                <p class="mt-3 fw-bold fs-4"><?= $display_name ?></p>
            </div>
            
            <?php if ($can_edit): ?>
                <div class="mb-4">
                    <label for="fileToUpload" class="form-label fw-bold text-primary">更換大頭貼:</label>
                    <input class="form-control" type="file" name="fileToUpload" id="fileToUpload" accept="image/*">
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary">個人簡介 / 科系 / 特長：</label>
                <?php if ($can_edit): ?>
                    <textarea class="form-control" name="bio" rows="8" style="resize: none;"><?= $display_intro ?></textarea>
                <?php else: ?>
                    <div class="p-3 bg-light rounded border" style="min-height: 150px; white-space: pre-wrap;">
                        <?= !empty($display_intro) ? $display_intro : '<span class="text-muted">（這位同學很懶，還沒寫簡介）</span>' ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($can_edit): ?>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-save"></i> 儲存修改
                    </button>
                </div>
            <?php endif; ?>

        <?php if ($can_edit): ?>
            </form>
        <?php else: ?>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php require_once('footer.php'); ?>