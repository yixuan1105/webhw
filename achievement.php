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
        <h2><strong>我的學習成果列表</strong></h2>
        <a href="achievement_create.php" class="btn btn-primary">
            + 上傳新成果
        </a>
    </div>

    <div class="card border-0 shadow-sm"> <div class="card-body">
            <?php if (empty($achievements)): ?>
                <p style="text-align: center; color: #777; padding: 20px;">您目前還沒有上傳任何成果</p>
            <?php else: ?>
                <table class="table table-borderless table-hover align-middle">
                    <thead style="border-bottom: 1px solid #eee;"> <tr>
                            <th style="width: 10%;">類別</th> 
                            <th style="width: 15%;">標題</th>
                            <th style="width: 35%;">內容簡介</th>
                            <th style="width: 10%;">證明文件</th>
                            <th style="width: 10%;">狀態</th>
                            <th style="width: 20%; text-align: right;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($achievements as $row): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <div style="font-weight: bold; font-size: 1.05em; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </div>
                                    <small style="color: #999;">
                                        <?php echo date('Y-m-d', strtotime($row['created_at'])); ?>
                                    </small>
                                </td>
                                
                                <td>
                                    <div style="max-height: 100px; overflow-y: auto; color: #555; font-size: 0.95em; line-height: 1.5;">
                                        <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                                    </div>
                                </td>

                                <td>
                                    <?php if (!empty($row['file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" style="text-decoration: none; color: #0d6efd;">
                                            <i class="fas fa-paperclip"></i> 查看
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #ccc;">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if ($row['status'] === 'approved'): ?>
                                        <span class="badge badge-approved">已認證</span>
                                    
                                    <?php elseif ($row['status'] === 'rejected'): ?>
                                        <span class="badge badge-rejected">未通過</span>
                                        <?php if (!empty($row['reviewer_comment'])): ?>
                                            <div style="margin-top: 5px; font-size: 0.8em; color: #dc3545;">
                                                原因: <?php echo htmlspecialchars($row['reviewer_comment']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                    <?php else: ?>
                                        <span class="badge badge-pending">待審核</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td style="text-align: right;">
                                    <?php if ($row['status'] !== 'approved'): ?>
                                        <a href="achievement_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">編輯</a>
                                    <a href="achievement_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('確定刪除？');">刪除</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>