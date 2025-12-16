<?php
// achievement_edit.php - 編輯成果 (含檔案上傳功能及 Email 通知)
// --- PHPMailer & Dotenv 引入 ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once('iden.php');
require_once('header.php');
// 載入 Composer 自動載入
// ⚠️ 確保此路徑正確，如果 vendor 資料夾不在上層，請調整路徑
require_once __DIR__ . '/vendor/autoload.php'; 

// 載入 .env 檔案
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); // 假設 .env 與此檔案在同一目錄
$dotenv->safeLoad(); // 使用 safeLoad 避免找不到檔案時報錯
$error = ''; 


requireLogin();
$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? $_POST['id']; // 兼容 GET 和 POST

// --- Email & URL 設定 (從 .env 取得) ---
$admin_email = $_ENV['ADMIN_EMAIL'] ?? 'default_admin@example.com';
$base_url = $_ENV['BASE_URL'] ?? 'http://localhost/';
// 確保定義學生姓名，如果 session 中沒有，可以使用預設值或從資料庫查詢
$student_name = $_SESSION['user_name'] ?? '一位學生'; 

// ==========================================
// 處理表單送出 (更新資料)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 再次確認這筆資料屬於該學生，並取得舊檔案路徑
    $check_sql = "SELECT id, file_path FROM achievements WHERE id = ? AND user_id = ?";
    $old_achievement_info = fetchOne($check_sql, [$id, $user_id]);

    if ($old_achievement_info) {
        $old_file_path = $old_achievement_info['file_path']; // 取得舊檔案路徑
        
        if (empty($title) || empty($category)) {
            $error = "標題與類別不能為空";
        } else {
            
            // ----------------------------------
            // 1. 處理檔案上傳邏輯 (修正：增加舊檔案刪除)
            // ----------------------------------
            $new_file_path = null; 
            
            if (isset($_FILES['achievement_file']) && $_FILES['achievement_file']['error'] === UPLOAD_ERR_OK) {
                
                $upload_dir = 'fileupload/'; 
                
                $file_ext = pathinfo($_FILES['achievement_file']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['achievement_file']['tmp_name'], $target_file)) {
                    $new_file_path = $target_file; // 上傳成功
                    
                    // 檔案上傳成功後，刪除舊檔案以清理伺服器空間
                    if ($old_file_path && file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                } else {
                    $error = "檔案上傳失敗，請檢查 fileupload 資料夾是否存在。";
                }
            }

            // ----------------------------------
            // 2. 更新資料庫
            // ----------------------------------
            if (empty($error)) {
                
                // 學生更新任何內容，狀態都重設為 'pending'
                if ($new_file_path) {
                    $sql = "UPDATE achievements SET title = ?, category = ?, description = ?, file_path = ?, status = 'pending', reviewed_by = NULL, created_at = NOW() WHERE id = ?";
                    $params = [$title, $category, $description, $new_file_path, $id];
                } 
                else {
                    $sql = "UPDATE achievements SET title = ?, category = ?, description = ?, status = 'pending', reviewed_by = NULL, created_at = NOW() WHERE id = ?";
                    $params = [$title, $category, $description, $id];
                }

                try {
                    execute($sql, $params);
                    
                    // ===========================================
                    // 🔔 整合 PHPMailer 通知功能
                    // ===========================================
                    
                    $mail = new PHPMailer(true);
                    
                    // SMTP 設定 (使用 .env 資訊)
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.office365.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $_ENV['M365_USER']; 
                    $mail->Password   = $_ENV['M365_PASS']; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // 收件人
                    $mail->setFrom($_ENV['M365_USER'], '學生成果審核系統'); 
                    $mail->addAddress($admin_email); 

                    // 郵件內容
                    $mail->CharSet = 'UTF-8';
                    $mail->isHTML(false); 
                    $mail->Subject = "【成果重新提交】編號 #{$id} - " . htmlspecialchars($title);
                    
                    $review_link = $base_url . "review_detail.php?id=" . $id; 
                    
                    $body = "您好，管理員：\n\n";
                    $body .= "學生 {$student_name} 已重新編輯並提交成果。\n\n";
                    $body .= "成果標題：{$title}\n";
                    $body .= "請點擊以下連結前往審核：\n{$review_link}\n\n";
                    $body .= "此成果已重設為待審核狀態 (pending)。\n";
                    $body .= "系統自動發送";
                    
                    $mail->Body    = $body;

                    $mail->send();
                    // Email 發送成功

                    // 更新成功，導回列表
                    header("Location: achievement.php");
                    exit();
                } catch (Exception $e) {
                    // PHPMailer 錯誤處理 (記錄錯誤，然後繼續導向)
                    error_log("Email failed for achievement ID {$id}: " . $mail->ErrorInfo);
                    
                    // 仍然導向成功頁面
                    header("Location: achievement.php");
                    exit();
                } catch (PDOException $e) {
                    $error = "更新失敗：" . $e->getMessage();
                }
            }
        }
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
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="achievement_edit.php?id=<?php echo htmlspecialchars($id); ?>" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
            
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
                <input type="text" id="title" name="title" class="form-control" 
                        value="<?php echo htmlspecialchars($row['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">詳細說明</label>
                <textarea id="description" name="description" class="form-control" rows="5"><?php echo htmlspecialchars($row['description']); ?></textarea>
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