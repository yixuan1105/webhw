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
        // 接收簡介
        $new_intro = trim($_POST['bio'] ?? '');
        // 接收科系 ID (改用 dept_id)
        $new_dept_id = !empty($_POST['dept_id']) ? intval($_POST['dept_id']) : null;
        
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

        // 更新資料庫 (更新 dept_id)
        if (empty($error)) {
            $sql_update = "UPDATE users SET photo_path = ?, bio = ?, dept_id = ? WHERE id = ?";
            $result = execute($sql_update, [$db_photo_path, $new_intro, $new_dept_id, $target_id]);
            
            if ($result) {
                $success = "資料更新成功！";
            } else {
                $error = "資料庫更新失敗。";
            }
        }
    }
}

// 3. 讀取顯示資料
// 使用 LEFT JOIN 關聯 departments 表，透過 users.dept_id 取得科系名稱
$sql_select = "SELECT u.name, u.photo_path, u.bio, u.role, u.dept_id, d.name AS dept_name 
               FROM users u 
               LEFT JOIN departments d ON u.dept_id = d.id 
               WHERE u.id = ?";
$user_data = fetchOne($sql_select, [$target_id]);

if (!$user_data) {
    echo '<div class="container py-5"><div class="alert alert-danger">查無此學生資料。</div></div>';
    require_once('footer.php');
    exit();
}

// 準備顯示用的變數
$display_name = $user_data['name'];
$display_intro = $user_data['bio'];
$display_photo = !empty($user_data['photo_path']) ? $user_data['photo_path'] : '';
$display_dept_name = !empty($user_data['dept_name']) ? $user_data['dept_name'] : '尚未選擇科系';
$current_dept_id = $user_data['dept_id']; // 用於編輯模式預選

// 4. 取得所有科系列表 (僅在編輯模式下需要)
$departments_list = [];
if ($can_edit) {
    // 因為你的 db.php 裡已經有 fetchAll 了，直接用就好，不用 global $pdo
    $departments_list = fetchAll("SELECT * FROM departments ORDER BY id ASC");
}
?>

<div class="container" style="padding-top: 40px; padding-bottom: 40px; max-width: 800px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color: #007bff;">
            <i class="bi bi-person-circle"></i> 
            <?php echo htmlspecialchars($display_name); ?>
        </h2>
        <?php if (!$can_edit): ?>
            <a href="index.php" class ="btn btn-outline-secondary">返回列表</a>
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
                <?php if (!empty($display_photo)): ?>
                    <img src="<?= $display_photo ?>" class="rounded-circle border border-primary border-3" style="width: 180px; height: 180px; object-fit: cover; background: #f0f0f0;">
                <?php else: ?>
                    <div class="rounded-circle border border-secondary border-3 d-flex align-items-center justify-content-center mx-auto" style="width: 180px; height: 180px; background: #f0f0f0; color: #ccc;">
                        <i class="bi bi-person-fill" style="font-size: 5rem;"></i>
                    </div>
                <?php endif; ?>
                
                <p class="mt-3 fw-bold fs-4"><?= htmlspecialchars($display_name) ?></p>
                
                <?php if (!$can_edit): ?>
                    <span class="badge bg-info text-dark fs-6"><?= htmlspecialchars($display_dept_name) ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($can_edit): ?>
                <div class="mb-4">
                    <label for="fileToUpload" class="form-label fw-bold text-primary">更換大頭貼:</label>
                    <input class="form-control" type="file" name="fileToUpload" id="fileToUpload" accept="image/*">
                </div>
                
                <div class="mb-4">
                    <label for="dept_id" class="form-label fw-bold text-primary">所屬科系:</label>
                    <select class="form-select" name="dept_id" id="dept_id">
                        <option value="">請選擇科系...</option>
                        <?php foreach ($departments_list as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($dept['id'] == $current_dept_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary">特長簡介：</label>
                <?php if ($can_edit): ?>
                    <textarea class="form-control" name="bio" rows="8" style="resize: none;" placeholder="請填寫你的特長與簡介..."><?= $display_intro ?></textarea>
                <?php else: ?>
                    <div class="p-3 bg-light rounded border" style="min-height: 150px; white-space: pre-wrap;">
                        <?php if (!empty($display_intro)): ?>
                            <?= htmlspecialchars($display_intro) ?>
                        <?php else: ?>
                            <span class="text-muted">（這位同學很懶，還沒寫簡介）</span>
                        <?php endif; ?>
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