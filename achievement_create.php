<?php
require_once('iden.php');
require_once('header.php');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 接收 category 接收並安全地獲取表單數據
    $category = $_POST['category'] ?? ''; // 新增
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    //用途： 確保只有當使用者透過表單提交資料（使用了 POST 方法）時，才執行大括號 {} 內的代碼。這能防止腳本在簡單的頁面載入（通常是 GET 方法）時執行資料處理。

    // 初始化檔案路徑
    $file_path = null;

    // 1. 修改：加入檔案是否為空的檢查
    // UPLOAD_ERR_NO_FILE 代表沒有上傳檔案
    if (empty($title) || empty($category)) { 
        $error = "標題與類別均為必填"; 
    } elseif (!isset($_FILES['achievement_file']) || $_FILES['achievement_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = "請務必上傳證明文件"; // 新增：如果沒檔案就報錯
    } else {
        
        // 檔案上傳處理
        // 檢查是否有上傳檔案，且沒有錯誤
        if ($_FILES['achievement_file']['error'] === UPLOAD_ERR_OK) {
            
            $upload_dir = 'fileupload/'; // 指定上傳資料夾
            
            // 產生唯一檔名
            $file_ext = pathinfo($_FILES['achievement_file']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;

            // 搬移檔案
            if (move_uploaded_file($_FILES['achievement_file']['tmp_name'], $target_file)) {
                $file_path = $target_file; // 上傳成功
            } else {
                $error = "檔案上傳失敗，請檢查 fileupload 資料夾是否存在。";
            }
        } else {
            // 處理其他上傳錯誤 (例如檔案太大等)
            $error = "檔案上傳發生錯誤，錯誤代碼：" . $_FILES['achievement_file']['error'];
        }

        // 如果沒有上傳錯誤，才執行資料庫寫入
        if (!isset($error)) {
            //將新成果寫入資料庫。狀態（status）預設設為 'pending'（審核中），創建時間（created_at）為當前時間
            // (注意：這裡 SQL 多加了 file_path 欄位)
            $sql = "INSERT INTO achievements (user_id, category, title, description, file_path, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            
            try { //錯誤處理： 使用 try...catch 結構來捕捉資料庫操作可能發生的錯誤。
                
                //執行寫入資料庫的操作 (參數多加了 $file_path)
                execute($sql, [$user_id, $category, $title, $description, $file_path]);
                
                header("Location: achievement.php");//如果資料新增成功，將使用者導回成果列表頁面。
                exit();
            } catch (PDOException $e) {
                $error = "新增失敗：" . $e->getMessage();
            }
        }
    }
}
?>

<div class="container-small" style="padding-top: 40px;">
    <div class="card">
        <h2>上傳新的學習成果</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="achievement_create.php" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="category">成果類別 <span class="required">*</span></label>
                <select id="category" name="category" class="form-control" required>
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
                <input type="text" id="title" name="title" class="form-control" placeholder="例如：PHP 期末專案報告" required>
            </div>

            <div class="form-group">
                <label for="description">詳細說明</label>
                <textarea id="description" name="description" class="form-control" rows="5"></textarea>
            </div>

            <div class="form-group">
                <label for="achievement_file">證明文件上傳 (圖片或PDF) <span class="required">*</span></label>
                
                <input type="file" id="achievement_file" name="achievement_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                
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