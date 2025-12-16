<?php
// achievement_create.php - 上傳新成果

// 1. 引入 PHPMailer (原本漏掉這段)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once('iden.php');
require_once('header.php');
require_once('db.php');

// 2. 引入 Composer 自動載入 (原本漏掉這段)
require_once __DIR__ . '/vendor/autoload.php';

// 3. 載入 .env 設定 (原本漏掉這段)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

requireLogin();
$user_id = $_SESSION['user_id'];
// 取得學生姓名供信件使用
$student_name = $_SESSION['user_name'] ?? '一位學生';
$error = null; 

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
        $upload_dir = 'fileupload/'; 
        $file_ext = pathinfo($_FILES['achievement_file']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $file_ext; 
        $target_file = $upload_dir . $new_filename;

        // 搬移檔案到伺服器
        if (move_uploaded_file($_FILES['achievement_file']['tmp_name'], $target_file)) {
            
            try {
                // 3. 寫入資料庫
                $sql = "INSERT INTO achievements (user_id, category, title, description, file_path, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
                
                execute($sql, [$user_id, $category, $title, $description, $target_file]);
                
                // ===========================================
                // 🔔 新增功能：發送 Email 通知
                // ===========================================
                try {
                    $admin_email = $_ENV['ADMIN_EMAIL'] ?? 'default_admin@example.com';
                    $base_url = $_ENV['BASE_URL'] ?? 'http://localhost/';

                    $mail = new PHPMailer(true);
                    
                    // SMTP 設定
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.office365.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $_ENV['M365_USER'];
                    $mail->Password   = $_ENV['M365_PASS'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    // 收件人
                    $mail->setFrom($_ENV['M365_USER'], '學生成果審核系統');
                    $mail->addAddress($admin_email);

                    // 信件內容
                    $mail->isHTML(false);
                    $mail->Subject = "【新成果上傳】" . htmlspecialchars($title);
                    
                    $body = "您好，管理員：\n\n";
                    $body .= "學生 {$student_name} 剛剛上傳了一份新的學習成果。\n\n";
                    $body .= "類別：{$category}\n";
                    $body .= "標題：{$title}\n";
                    $body .= "請前往系統後台進行審核。\n\n";
                    $body .= "系統自動發送";

                    $mail->Body = $body;
                    $mail->send();

                } catch (Exception $e) {
                    // 記錄 Email 錯誤但不阻擋流程
                    error_log("Create Email Failed: " . $mail->ErrorInfo);
                }

                // 4. 成功後導回列表
                header("Location: achievement.php");
                exit();

            } catch (PDOException $e) {
                $error = "資料庫錯誤：" . $e->getMessage();
            }

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
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
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