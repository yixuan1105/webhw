<?php
require_once('iden.php');
require_once('header.php');

requireLogin();

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? ''; // 從網址取得成果 ID
$error = '';

//處理表單送出 (更新資料)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {//判斷使用者是否剛提交了表單（POST 請求）。
    $id = $_POST['id'] ?? '';//從表單隱藏欄位中安全地獲取成果 ID。
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // 再次確認這筆資料屬於該學生
    $check_sql = "SELECT id FROM achievements WHERE id = ? AND user_id = ?";
    if (fetchOne($check_sql, [$id, $user_id])) { // 如果查詢到結果（資料存在且權限匹配），則執行更新邏輯。
        
        if (empty($title) || empty($category)) {
            $error = "標題與類別不能為空";
        } else {
            // 更新資料時，強制將 status 改回 'pending'，並清空 reviewed_by(清除上次審核人)
            $sql = "UPDATE achievements SET title = ?, category = ?, description = ?, status = 'pending', reviewed_by = NULL WHERE id = ?";
            try {
                execute($sql, [$title, $category, $description, $id]); //執行資料庫更新操作。
                
                // 更新成功，導回成果列表
                header("Location: achievement.php");
                exit();
            } catch (PDOException $e) {
                $error = "更新失敗：" . $e->getMessage();
            }
        }
    } else {
        $error = "您無權限編輯此資料";
    }
}

// 讀取舊資料
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

        <form method="POST" action="achievement_edit.php?id=<?php echo $id; ?>">
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

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">儲存修改</button>
                <a href="achievement.php" class="btn btn-secondary">取消返回</a>
            </div>
        </form>
    </div>
</div>

<?php require_once('footer.php'); ?>