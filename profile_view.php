<?php
require_once('db.php');
require_once('header.php'); 

// 取得 ID 
$student_id = $_GET['id'];
$pdo = connectDB();

// 查詢學生資料
// 用 LEFT JOIN 把科系名稱 (dept_name) 一起抓出來
$sql_user = "SELECT u.*, d.name as dept_name 
             FROM users u
             LEFT JOIN departments d ON u.dept_id = d.id
             WHERE u.id = ?";
$student = fetchOne($sql_user, [$student_id]);

// 查詢所有審核通過成果資料
$sql_ach = "SELECT * FROM achievements 
            WHERE user_id = ? AND status = 'approved' 
            ORDER BY created_at DESC";
$achievements = fetchAll($sql_ach, [$student_id]);

// 類別中文對照表
$category_map = ['subject'=>'擅長科目', 'language'=>'程式語言', 'competition'=>'競賽', 'certificate'=>'證照'];
?>

<div class="container" style="padding-top: 40px; padding-bottom: 60px;">

    <div class="card shadow-sm mb-5" style="border: none; background: #fff;">
        <div class="card-body text-center" style="padding: 40px;">
            
            <?php 
                $photo = $student['photo_path'];
            ?>
            <img src="<?php echo $photo; ?>" 
                 style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #f8f9fa; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            
            <h2 style="margin-bottom: 10px; font-weight: bold; color: #2c3e50;">
                <?php echo $student['name']; ?>
            </h2>
            
            <?php if ($student['dept_name']): ?>
                <span style="background-color: #3498db; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; display: inline-block; margin-bottom: 20px;">
                    <?php echo $student['dept_name']; ?>
                </span>
            <?php endif; ?>

            <div style="max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 10px; text-align: left;">
                <h5 style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                    About Me
                </h5>
                <p style="color: #555; line-height: 1.8; margin: 0;">
                    <?php echo nl2br($student['bio']); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center mb-4">
        <h3 style="margin: 0; font-weight: bold; border-left: 5px solid #f1c40f; padding-left: 15px;">
            🏆 學習成果與作品
        </h3>
        <span class="badge bg-secondary ms-2" style="font-size: 0.8rem; background: #ccc; padding: 5px 10px; border-radius: 10px; margin-left: 10px;">
            共 <?php echo count($achievements); ?> 筆
        </span>
    </div>

    <?php if (empty($achievements)): ?>
        <div style="text-align: center; padding: 50px; background: #f8f9fa; border-radius: 10px; border: 2px dashed #e9ecef;">
            <p style="color: #adb5bd; font-size: 1.1rem; margin: 0;">目前沒有公開的已認證成果。</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($achievements as $ach): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100" style="border: 1px solid #eee; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="font-size: 0.8rem; color: #3498db; background: #e8f4fc; padding: 3px 8px; border-radius: 4px;">
                                    <?php echo $category_map[$ach['category']] ?? $ach['category']; ?>
                                </span>
                                <small style="color: #aaa;">
                                    <?php echo date('Y/m/d', strtotime($ach['created_at'])); ?>
                                </small>
                            </div>

                            <h5 class="card-title" style="font-weight: bold; margin-bottom: 15px;">
                                <?php echo $ach['title']; ?>
                            </h5>
                            <p class="card-text" style="color: #666; font-size: 0.95rem; line-height: 1.6;">
                                <?php echo nl2br($ach['description']); ?>
                            </p>
                        </div>
                        
                        <div class="card-footer" style="background: white; border-top: 1px solid #f1f1f1; padding: 15px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($ach['file_path']): ?>
                                        <a href="<?php echo $ach['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 20px;">
                                            查看成果
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.85rem;">無附件</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center mt-5">
        <a href="index.php" class="btn btn-outline-secondary" style="padding: 10px 30px; border-radius: 20px;">
            ← 返回首頁
        </a>
    </div>

</div>

<?php require_once('footer.php'); ?>