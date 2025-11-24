<?php
// (新增成果頁面)
require_once('iden.php');
require_once('header.php');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 接收 category
    $category = $_POST['category'] ?? ''; // 新增
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    if (empty($title) || empty($category)) { // 檢查類別是否為空
        $error = "標題與類別均為必填";
    } else {
        // 2. SQL 語法加入 category 欄位
        $sql = "INSERT INTO achievements (user_id, category, title, description, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        try {
            // 3. 執行時帶入 $category 參數
            execute($sql, [$user_id, $category, $title, $description]);
            header("Location: achievement.php");
            exit();
        } catch (PDOException $e) {
            $error = "新增失敗：" . $e->getMessage();
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

        <form method="POST" action="achievement_create.php">
            
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

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">確認上傳</button>
                <a href="achievement.php" class="btn btn-secondary">取消</a>
            </div>
        </form>
    </div>
</div>
<?php require_once('footer.php'); ?>