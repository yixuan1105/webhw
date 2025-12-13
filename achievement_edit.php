<?php
// achievement_edit.php - 編輯成果
require_once('iden.php');
require_once('header.php');
require_once('db.php');

requireLogin();
$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? $_POST['id']; // 兼容 GET 和 POST

// 1. 處理表單送出 (更新資料)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $title = $_POST['title'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    
    // 檢查是否有上傳新檔案
    if (!empty($_FILES['achievement_file']['name'])) {
        
        // 有上傳：搬移檔案 + 更新所有欄位 (含 file_path)
        $upload_dir = 'fileupload/';
        $new_filename = uniqid() . '_' . $_FILES['achievement_file']['name'];
        $target_file = $upload_dir . $new_filename;
        
        move_uploaded_file($_FILES['achievement_file']['tmp_name'], $target_file);

        // 更新資料庫 (狀態改為 pending 待審核)
        $sql = "UPDATE achievements 
                SET title = ?, category = ?, description = ?, file_path = ?, status = 'pending', created_at = NOW() 
                WHERE id = ? AND user_id = ?";
        execute($sql, [$title, $category, $description, $target_file, $id, $user_id]);

    } else {
        
        // 沒上傳：只更新文字資料，不改 file_path
        $sql = "UPDATE achievements 
                SET title = ?, category = ?, description = ?, status = 'pending', created_at = NOW() 
                WHERE id = ? AND user_id = ?";
        execute($sql, [$title, $category, $description, $id, $user_id]);
    }

    // 更新完成，回列表
    header("Location: achievement.php");
    exit();
}

// 2. 讀取舊資料 (顯示在表單上)
$sql = "SELECT * FROM achievements WHERE id = ? AND user_id = ?";
$row = fetchOne($sql, [$id, $user_id]);
?>

<div class="container-small" style="padding-top: 40px;">
    <div class="card">
        <h2>編輯學習成果</h2>
        
        <form method="POST" action="achievement_edit.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
            
            <div class="form-group">
                <label for="category">成果類別 <span class="required">*</span></label>
                <select name="category" class="form-control" required>
                    <option value="程式語言" <?php echo ($row['category'] == '程式語言') ? 'selected' : ''; ?>>程式語言</option>
                    <option value="證照" <?php echo ($row['category'] == '證照') ? 'selected' : ''; ?>>證照</option>
                    <option value="競賽" <?php echo ($row['category'] == '競賽') ? 'selected' : ''; ?>>競賽</option>
                    <option value="科目" <?php echo ($row['category'] == '科目') ? 'selected' : ''; ?>>科目</option>
                    <option value="其他" <?php echo ($row['category'] == '其他') ? 'selected' : ''; ?>>其他</option>
                </select>
            </div>

            <div class="form-group">
                <label for="title">成果標題 <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" 
                       value="<?php echo $row['title']; ?>" required>
            </div>

            <div class="form-group">
                <label for="description">詳細說明</label>
                <textarea name="description" class="form-control" rows="5"><?php echo $row['description']; ?></textarea>
            </div>

            <div class="form-group">
                <label>更新證明文件 (若不修改請留空)</label>
                
                <?php if ($row['file_path']): ?>
                    <div style="margin-bottom: 10px; font-size: 14px; color: #555;">
                        目前檔案：<a href="<?php echo $row['file_path']; ?>" target="_blank">查看舊檔案</a>
                    </div>
                <?php endif; ?>

                <input type="file" name="achievement_file" class="form-control">
                <small style="color: #666;">上傳新檔案將會取代舊檔案，並重新送審。</small>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">儲存修改</button>
                <a href="achievement.php" class="btn btn-secondary">取消返回</a>
            </div>
        </form>
    </div>
</div>

<?php require_once('footer.php'); ?>