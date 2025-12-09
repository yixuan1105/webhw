<?php
// achievement_edit.php - 編輯成果 (含檔案上傳功能)
require_once('iden.php');
require_once('header.php');

requireLogin();

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? ''; // 從網址取得成果 ID
$error = '';

// ==========================================
// 處理表單送出 (更新資料)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // 再次確認這筆資料屬於該學生 (防止惡意修改)
    $check_sql = "SELECT id FROM achievements WHERE id = ? AND user_id = ?";
    if (fetchOne($check_sql, [$id, $user_id])) {
        
        if (empty($title) || empty($category)) {
            $error = "標題與類別不能為空";
        } else {
            
            // ----------------------------------
            // 1. 處理檔案上傳邏輯
            // ----------------------------------
            $new_file_path = null; // 預設為 null (代表沒有要換檔案)
            
            // 檢查使用者是否有選擇新檔案
            if (isset($_FILES['achievement_file']) && $_FILES['achievement_file']['error'] === UPLOAD_ERR_OK) {
                
                $upload_dir = 'fileupload/'; // 指定上傳資料夾 (請確認資料夾存在!)
                
                // 產生唯一檔名 (避免覆蓋別人的檔案)
                $file_ext = pathinfo($_FILES['achievement_file']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;

                // 搬移檔案
                if (move_uploaded_file($_FILES['achievement_file']['tmp_name'], $target_file)) {
                    $new_file_path = $target_file; // 上傳成功，記錄新路徑
                } else {
                    $error = "檔案上傳失敗，請檢查 fileupload 資料夾是否存在。";
                }
            }

            // ----------------------------------
            // 2. 更新資料庫
            // ----------------------------------
            if (empty($error)) {
                
                // 使用者有上傳新檔案 -> 更新 file_path，同時更新 created_at = NOW()
                if ($new_file_path) {
                    $sql = "UPDATE achievements SET title = ?, category = ?, description = ?, file_path = ?, status = 'pending', reviewed_by = NULL, created_at = NOW() WHERE id = ?";
                    $params = [$title, $category, $description, $new_file_path, $id];
                } 
                // 使用者沒上傳新檔案 -> 保留舊檔案，但仍要更新 created_at = NOW()
                else {
                    $sql = "UPDATE achievements SET title = ?, category = ?, description = ?, status = 'pending', reviewed_by = NULL, created_at = NOW() WHERE id = ?";
                    $params = [$title, $category, $description, $id];
                }

                try {
                    execute($sql, $params);
                    
                    // 更新成功，導回列表
                    header("Location: achievement.php");
                    exit();
                } catch (PDOException $e) {
                    $error = "更新失敗：" . $e->getMessage();
                }
            }
        }
    } else {
        $error = "您無權限編輯此資料";
    }
}

// 讀取舊資料 (顯示在表單上)
$sql = "SELECT * FROM achievements WHERE id = ? AND user_id = ?";
$row = fetchOne($sql, [$id, $user_id]);

// 如果找不到資料，導回列表
if (!$row) {
    header("Location: achievement.php");
    exit();
}
?>

<div class="container-small" style="padding-top: 40px;">
    <div class="card">
        <h2>編輯學習成果</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="achievement_edit.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo ($row['id']); ?>">
            
            <div class="form-group">
                <label for="category">成果類別 <span class="required">*</span></label>
                <select id="category" name="category" class="form-control" required>
                    <option value="程式語言" <?php echo ($row['category'] == '程式語言') ? 'selected' : ''; ?>>程式語言</option>
                    <option value="證照" <?php echo ($row['category'] == '證照') ? 'selected' : ''; ?>>證照</option>
                    <option value="競賽" <?php echo ($row['category'] == '競賽') ? 'selected' : ''; ?>>競賽</option>
                    <option value="科目" <?php echo ($row['category'] == '科目') ? 'selected' : ''; ?>>科目</option>
                    <option value="其他" <?php echo ($row['category'] == '其他') ? 'selected' : ''; ?>>其他</option>
                </select>
            </div>

            <div class="form-group">
                <label for="title">成果標題 <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control" 
                       value="<?php echo ($row['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">詳細說明</label>
                <textarea id="description" name="description" class="form-control" rows="5"><?php echo ($row['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="achievement_file">更新證明文件 (若不修改請留空)</label>
                
                <?php if (!empty($row['file_path'])): ?>
                    <div style="margin-bottom: 10px; font-size: 14px; color: #555;">
                        目前檔案：<a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank">查看舊檔案</a>
                    </div>
                <?php endif; ?>

                <input type="file" id="achievement_file" name="achievement_file" class="form-control">
                <small style="color: #666;">上傳新檔案將會取代舊檔案。</small>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">儲存修改</button>
                <a href="achievement.php" class="btn btn-secondary">取消返回</a>
            </div>
        </form>
    </div>
</div>

<?php require_once('footer.php'); ?>