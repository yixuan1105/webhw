<?php
// 單筆成果檢視與審核表單
require_once('../iden.php');
require_once('../db.php');

// 權限檢查：強制管理員登入
requireAdminLogin();
$pdo = connectDB();
$achievement = null;
$error_message = '';
$success_message = '';// 請在這裡加入這行：初始化成功訊息變數
$achievement_id = $_GET['id'] ?? null;

// 處理從列表頁導向過來的成功/錯誤訊息
if (isset($_GET['success'])) {
    $success_message = (urldecode($_GET['success']));
}
if (isset($_GET['error'])) {
    $error_message = (urldecode($_GET['error']));
}

// 查詢成果詳情
if ($achievement_id) {
    try {
        // 聯合查詢：獲取成果詳情和學生的名稱
        $sql = "
            SELECT
                a.*,
                u.name AS student_name,
                u.username,
                u.bio
            FROM
                achievements a
            JOIN
                users u ON a.user_id = u.id
            WHERE
                a.id = ?;
        ";
       
        $achievement = fetchOne($sql, [$achievement_id]);
       
        if (!$achievement) {
            $error_message = "找不到 ID 為 {$achievement_id} 的成果記錄。";
        }
       
    } catch (Exception $e) {
        $error_message = "資料庫查詢失敗: " . $e->getMessage();
    }
} else {
    $error_message = "URL 中缺少成果 ID 參數。";
}


$page_title = $achievement ? "審核成果: " . $achievement['title'] : "審核錯誤";


?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .proof-box { border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; background-color: #f8f9fa; }
        .approved { color: #198754; font-weight: bold; }
        .rejected { color: #dc3545; font-weight: bold; }
        .pending { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>


<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>成果審核中心</h1>
        <a href="review.php" class="btn btn-outline-secondary">回待審列表</a>
    </div>


    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert"><?= $error_message ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="alert alert-success" role="alert"><?= $success_message ?></div>
    <?php endif; ?>


    <?php if ($achievement): ?>


        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><?= ($achievement['title']) ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>學生資訊</h5>
                        <p><strong>姓名：</strong> <?= ($achievement['student_name']) ?></p>
                        <p><strong>帳號：</strong> <?= ($achievement['username']) ?></p>
                        <p><strong>個人簡介：</strong> <?= nl2br(($achievement['bio'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>成果資訊</h5>
                        <p><strong>類別：</strong> <?= ($achievement['category']) ?></p>
                        <p><strong>上傳日期：</strong> <?= ($achievement['created_at']) ?></p>
                        <p><strong>目前狀態：</strong> <span class="<?= ($achievement['status']) ?>"><?= ucfirst(($achievement['status'])) ?></span></p>
                    </div>
                </div>
               
                <h5 class="mt-4">成果描述</h5>
                <p><?= nl2br(($achievement['description'])) ?></p>


                <h5 class="mt-4">證明文件</h5>
                <div class="proof-box">
                    <?php if (!empty($achievement['file_path'])): ?>
                        <p>
                            <a href="../<?= ($achievement['file_path']) ?>" target="_blank">
                                📂 檢視證明文件 (<?= basename($achievement['file_path']) ?>)
                            </a>
                        </p>
                    <?php else: ?>
                        <p class="text-muted">無證明文件上傳</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5>執行審核</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="review_process.php">
                    <input type="hidden" name="achievement_id" value="<?= $achievement['id'] ?>">
                   
                    <div class="mb-3">
                        <label for="reviewer_comment" class="form-label">審核意見</label>
                        <textarea class="form-control" id="reviewer_comment" name="reviewer_comment" rows="3"><?= ($achievement['reviewer_comment'] ?? '') ?></textarea>
                        <div class="form-text">此意見若不通過學生可見。</div>
                    </div>
                   
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="action" value="reject" class="btn btn-danger me-2"
                                onclick="return confirm('確定不通過這項成果嗎？')">
                            不通過
                        </button>
                        <button type="submit" name="action" value="approve" class="btn btn-success"
                                onclick="return confirm('確定要認證通過這項成果嗎？')">
                            通過
                        </button>
                    </div>
                </form>
            </div>
        </div>


    <?php endif; ?>
</div>


</body>
</html>
