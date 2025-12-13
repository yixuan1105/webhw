<?php
require_once('iden.php');
require_once('header.php');
require_once('db.php');

// 強制登入檢查
requireLogin();
$current_user_id = $_SESSION['user_id'];

// 撈取該學生的所有成果 (依時間倒序)
$sql = "SELECT * FROM achievements WHERE user_id = ? ORDER BY created_at DESC";
$achievements = fetchAll($sql, [$current_user_id]);
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
                        <th style="width: 15%;">類別</th> 
                        <th style="width: 30%;">標題</th>
                        <th style="width: 20%;">上傳時間</th>
                        <th style="width: 20%;">審核狀態</th>
                        <th style="width: 15%;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($achievements as $row): ?>
                        <tr>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?php echo $row['category']; ?>
                                </span>
                            </td>
                            
                            <td><?php echo $row['title']; ?></td>
                            
                            <td><?php echo $row['created_at']; ?></td>
                            
                            <td>
                                <?php if ($row['status'] === 'approved'): ?>
                                    <span class="badge badge-approved">已認證</span>
                                
                                <?php elseif ($row['status'] === 'rejected'): ?>
                                    <span class="badge badge-rejected">未通過</span>
                                    
                                    <?php if ($row['reviewer_comment']): ?>
                                        <div style="margin-top: 8px; font-size: 0.9em; color: #dc3545; background-color: #fff5f5; padding: 8px; border-radius: 4px; border-left: 3px solid #dc3545;">
                                            <strong>原因：</strong><br>
                                            <?php echo nl2br($row['reviewer_comment']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <span class="badge badge-pending">待審核</span>
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