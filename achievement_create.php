<?php
// achievement_create.php - 上傳新成果
require_once('iden.php');
require_once('header.php');
require_once('db.php');

requireLogin();
$user_id = $_SESSION['user_id'];
$error = null; // 初始化錯誤訊息

// 處理表單送出
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $category = $_POST['category'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    // 簡單檢查必填
    if (empty($title) || empty($category)) {
        $error = "標題與類別為必填！";
    } 
    // 檢查是否有上傳檔案
    elseif (empty($_FILES['achievement_file']['name'])) {
        $error = "請務必上傳證明文件！";
    } 
    else {
        // 處理檔案上傳
        $upload_dir = 'fileupload/'; // 確保這個資料夾存在
        $file_ext = pathinfo($_FILES['achievement_file']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $file_ext; // 產生唯一檔名
        $target_file = $upload_dir . $new_filename;

        // 搬移檔案到伺服器
        if (move_uploaded_file($_FILES['achievement_file']['tmp_name'], $target_file)) {
            
            // 3. 寫入資料庫 (狀態預設為 pending)
            $sql = "INSERT INTO achievements (user_id, category, title, description, file_path, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            
            execute($sql, [$user_id, $category, $title, $description, $target_file]);
            
            // 4. 成功後導回列表
            header("Location: achievement.php");
            exit();

        } else {
            $error = "檔案上傳失敗，請檢查 fileupload 資料夾權限。";
        }
    }
}
?>

<div class="container-small" style="padding-top: 40px;">
    <div class="card">
        <h2>上傳新的學習成果</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="achievement_create.php" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="category">成果類別 <span class="required">*</span></label>
                <select name="category" class="form-control" required>
                    <option value="" disabled selected>請選擇類別...</option>
                    <option value="程式語言">程式語言</option>
                    <option value="證照">證照</option>
                    <option value="競賽">競賽</option>
                    <option value="科目">科目</option>
                    <option value="其他">其他</option>
                </select>
            </div>

            <div class="form-group">
                <label for="title">成果標題 <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="例如：PHP 期末專案報告" required>
            </div>

            <div class="form-group">
                <label for="description">詳細說明</label>
                <textarea name="description" class="form-control" rows="5"></textarea>
            </div>

            <div class="form-group">
                <label for="achievement_file">證明文件上傳 (圖片或PDF) <span class="required">*</span></label>
                <input type="file" name="achievement_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                <small style="color: #666;">支援格式：JPG, PNG, PDF (必填)</small>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">確認上傳</button>
                <a href="achievement.php" class="btn btn-secondary">取消</a>
            </div>
        </form>
    </div>
</div>

<?php require_once('footer.php'); ?>