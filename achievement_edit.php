<?php

//引入必要的類別
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once('iden.php');
require_once('header.php');
require_once('db.php');

//載入 Composer 自動載入
require_once __DIR__ . '/vendor/autoload.php'; 

//載入 .env 檔案
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

requireLogin();
$user_id = $_SESSION['user_id']; //抓取學生id
$id = $_GET['id'] ?? $_POST['id']; //抓取成果的id GET:剛點進「修改」按鈕時 POST:修改完後「儲存修改」按鈕時

// 初始化變數
$error = '';
// 從 .env 取得管理員信箱，若沒設定則用預設值
$admin_email = $_ENV['ADMIN_EMAIL'] ?? 'default_admin@example.com';
$base_url    = $_ENV['BASE_URL'] ?? 'http://localhost/';
$student_name = $_SESSION['user_name'] ?? ''; 

// 處理表單送出
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $title       = $_POST['title'] ?? '';
    $category    = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';

    // 確認這筆資料屬於該學生
    $check_sql = "SELECT id, file_path FROM achievements WHERE id = ? AND user_id = ?";
    $old_achievement_info = fetchOne($check_sql, [$id, $user_id]);

    if ($old_achievement_info) {
        $old_file_path = $old_achievement_info['file_path'];
        
        if (empty($title) || empty($category)) {
            $error = "標題與類別不能為空";
        } else {
            
            //檔案上傳邏輯
            $new_file_path = null; 
            
            if (isset($_FILES['achievement_file']) && $_FILES['achievement_file']['error'] === UPLOAD_ERR_OK) {
                
                $upload_dir = 'fileupload/'; 
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_ext = pathinfo($_FILES['achievement_file']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['achievement_file']['tmp_name'], $target_file)) {
                    $new_file_path = $target_file;
                    
                    // 刪除舊檔案
                    if ($old_file_path && file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                } else {
                    $error = "檔案上傳失敗，請檢查權限。";
                }
            }

            // 更新資料庫 & 寄信
            if (empty($error)) {
                
                // 準備 SQL
                if ($new_file_path) {
                    $sql = "UPDATE achievements SET title = ?, category = ?, description = ?, file_path = ?, status = 'pending', created_at = NOW() WHERE id = ?";
                    $params = [$title, $category, $description, $new_file_path, $id];
                } else {
                    $sql = "UPDATE achievements SET title = ?, category = ?, description = ?, status = 'pending', created_at = NOW() WHERE id = ?";
                    $params = [$title, $category, $description, $id];
                }

                try {
                    // 執行資料庫更新
                    execute($sql, $params);
                    
                    // 發送 Email 通知 (包在 try-catch 裡，失敗不影響流程)
                    try {
                        $mail = new PHPMailer(true);
                        
                        // SMTP 設定 (從 .env 讀取)
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.office365.com'; // 您的學校郵局
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $_ENV['M365_USER']; 
                        $mail->Password   = $_ENV['M365_PASS']; 
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        // 收件人
                        $mail->setFrom($_ENV['M365_USER'], '學生成果系統'); 
                        $mail->addAddress($admin_email); 

                        // 內容
                        $mail->CharSet = 'UTF-8';
                        $mail->isHTML(false); 
                        $mail->Subject = "【系統通知】學生已更新成果：$title";
                        
                        $review_link = $base_url . "review_detail.php?id=" . $id; 
                        
                        $body = "管理員您好：\n\n";
                        $body .= "學生 {$student_name} 已更新成果「{$title}」。\n";
                        $body .= "請前往系統審核：{$review_link}\n";
                        $body .= "系統自動發送";
                        
                        $mail->Body = $body;

                        $mail->send();
                        
                    } catch (Exception $e) {
                        // 寄信失敗時，只記錄在後端 Log，不顯示給使用者看，以免驚慌
                        error_log("Email sending failed: " . $mail->ErrorInfo);
                    }

                    // 一切完成，跳轉回列表
                    header("Location: achievement.php");
                    exit();

                } catch (PDOException $e) {
                    $error = "資料庫更新失敗：" . $e->getMessage();
                }
            }
        }
    }
}

// 讀取舊資料顯示
$sql = "SELECT * FROM achievements WHERE id = ? AND user_id = ?";
$row = fetchOne($sql, [$id, $user_id]);
?>

<div class="container-small" style="padding-top: 40px;">
    <div class="card">
        <h2>編輯學習成果</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="color: red; background: #ffe6e6; padding: 10px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="achievement_edit.php?id=<?php echo htmlspecialchars($id); ?>" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
            
            <div class="form-group">
                <label>成果類別 <span class="required">*</span></label>
                <select name="category" class="form-control" required>
                    <option value="程式語言" <?php echo ($row['category'] == '程式語言') ? 'selected' : ''; ?>>程式語言</option>
                    <option value="證照" <?php echo ($row['category'] == '證照') ? 'selected' : ''; ?>>證照</option>
                    <option value="競賽" <?php echo ($row['category'] == '競賽') ? 'selected' : ''; ?>>競賽</option>
                    <option value="科目" <?php echo ($row['category'] == '科目') ? 'selected' : ''; ?>>科目</option>
                    <option value="其他" <?php echo ($row['category'] == '其他') ? 'selected' : ''; ?>>其他</option>
                </select>
            </div>

            <div class="form-group">
                <label>成果標題 <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($row['title']); ?>" required>
            </div>

            <div class="form-group">
                <label>詳細說明</label>
                <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($row['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label>更新證明文件</label>
                <?php if ($row['file_path']): ?>
                    <div style="margin-bottom: 10px;">目前檔案：<a href="<?php echo $row['file_path']; ?>" target="_blank">查看舊檔案</a></div>
                <?php endif; ?>
                <input type="file" name="achievement_file" class="form-control">
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">儲存修改</button>
                <a href="achievement.php" class="btn btn-secondary">取消返回</a>
            </div>
        </form>
    </div>
</div>

<?php require_once('footer.php'); ?>