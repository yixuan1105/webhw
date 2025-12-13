<?php
require_once('db.php');

$page_title = '成果展示 - 學生學習成果認證系統';
$page_css_files = ['admin.css']; 

require_once('header.php'); 

// 接收搜尋參數 (如果沒有就是空字串)
$search_name  = $_GET['search_name'] ?? ''; 
$search_skill = $_GET['search_skill'] ?? ''; 
$search_dept  = $_GET['search_dept'] ?? ''; 

$pdo = connectDB();

// 準備 SQL 查詢
// 使用 LEFT JOIN 把科系名稱 (dept_name) 一起抓出來
$sql = "SELECT u.*, d.name as dept_name 
        FROM users u
        LEFT JOIN departments d ON u.dept_id = d.id
        WHERE u.role = 'student'";

$params = [];

// 姓名
if ($search_name) {
    $sql .= " AND u.name LIKE ?"; 
    $params[] = "%" . $search_name . "%";
}

// 技能 (搜尋簡介)
if ($search_skill) {
    $sql .= " AND u.bio LIKE ?"; 
    $params[] = "%" . $search_skill . "%";
}

// 科系 
if ($search_dept) {
    $sql .= " AND d.name LIKE ?";
    $params[] = "%" . $search_dept . "%";
}

// 執行查詢
$students = fetchAll($sql, $params);
?>

<div class="container" style="padding-top: 40px; padding-bottom: 40px;">

    <h2 style="text-align: center; margin-bottom: 30px;">人才搜尋</h2>

    <form method="GET" action="index.php" class="filter-bar" style="display: flex; flex-wrap: wrap; gap: 15px; background: #f8f9fa; padding: 20px; border-radius: 8px;">
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_name" style="font-weight: bold;">學生姓名:</label>
            <input type="text" name="search_name" placeholder="輸入學生姓名..." class="form-control"
                   value="<?php echo $search_name; ?>">
        </div>
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_skill" style="font-weight: bold;">具備技能:</label>
            <input type="text" name="search_skill" placeholder="例如: PHP, 攝影..." class="form-control"
                   value="<?php echo $search_skill; ?>">
        </div>
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_dept" style="font-weight: bold;">學校科系:</label>
            <input type="text" name="search_dept" placeholder="例如：資訊..." class="form-control"
                   value="<?php echo $search_dept; ?>">
        </div>
        
        <div style="display: flex; gap: 10px; align-self: flex-end;">
            <button type="submit" class="btn btn-primary" style="margin-top: 0;">搜尋</button>
            <a href="index.php" class="btn btn-secondary" style="margin-top: 0;">清除</a>
        </div>
    </form>

    <div class="students-grid" style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
        
        <?php if (empty($students)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff; border-radius: 8px;">
                <p style="font-size: 18px; color: #777; margin: 0;">沒有找到符合條件的學生。</p>
            </div>
        <?php else: ?>
            <?php foreach ($students as $student): ?>
                <div class="student-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    
                    <a href="profile_view.php?id=<?php echo $student['id']; ?>" style="text-decoration: none; color: inherit;">
                        
                        <?php 
                            $photo = $student['photo_path'] ? $student['photo_path'] : 'img/default_avatar.png';
                        ?>
                        <img src="<?php echo $photo; ?>" 
                             style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #eee; margin-bottom: 15px;">
                        
                        <h3 style="margin: 10px 0; color: #333; font-size: 1.2rem;">
                            <?php echo $student['name']; ?>
                        </h3>
                    </a>
                    
                    <p style="color: #666; font-size: 0.9rem; min-height: 40px; margin-bottom: 20px;">
                        <?php 
                            $intro = $student['bio'] ? $student['bio'] : '這位同學尚未填寫簡介...';
                            echo mb_substr($intro, 0, 40, 'utf-8') . '...';
                        ?>
                    </p>
                    
                    <a href="profile_view.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">
                        查看完整檔案
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div> 
</div> 

<?php require_once('footer.php'); ?>