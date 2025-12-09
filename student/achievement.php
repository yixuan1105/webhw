<?php
 //  成果列表
require_once('iden.php'); // 引入身分驗證
require_once('header.php'); // 引入導覽列
require_once('db.php'); //引入資料庫工具 (包含 fetchAll)

//強制要求登入 (如果沒登入會被踢回登入頁)
requireLogin();
$current_user_id = $_SESSION['user_id'];// 從 Session 中取得目前登入使用者的 ID

//從資料庫讀取該學生的成果
$sql = "SELECT * FROM achievements WHERE user_id = ? ORDER BY created_at DESC";
$achievements = fetchAll($sql, [$current_user_id]);// 執行查詢，使用 fetchAll 取得所有符合條件的成果記錄
?>

<div class="container" style="padding-top: 40px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>我的學習成果列表</h2>
        
        <a href="achievement_create.php" class="btn btn-primary">
            + 上傳新成果
        </a>
    </div>

    <div class="card">
        <?php if (empty($achievements)): ?>
            <p style="text-align: center; color: #777; padding: 20px;">您目前還沒有上傳任何成果</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>類別</th> <th>標題</th>
                        <th>上傳時間</th>
                        <th>審核狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($achievements as $row): ?>
                        <tr>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?php echo ($row['category']); ?>
                                </span>
                            </td>
                            
                            <td><?php echo ($row['title']); ?></td>
                            <td><?php echo ($row['created_at']); ?></td>
                            
                            <td>
                                <?php if ($row['status'] === 'approved'): ?>
                                    <span class="badge badge-approved">✅ 已通過</span>
                                <?php elseif ($row['status'] === 'rejected'): ?>
                                    <span class="badge badge-rejected">❌ 未通過</span>
                                <?php else: ?>
                                    <span class="badge badge-pending">⏳ 審核中</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <a href="achievement_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm">編輯</a>
                                <a href="achievement_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('確定刪除？');">刪除</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once('footer.php'); ?>