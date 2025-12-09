<?php
// ==========================================
// index.php - 首頁 (混合搜尋版：搜簡介 + 搜科系表)
// ==========================================

require_once('db.php');

$page_title = '成果展示 - 學生學習成果認證系統';
$page_css_files = ['admin.css']; 

require_once('header.php'); 

// 1. 接收和清理搜尋參數
$search_name  = trim($_GET['search_name'] ?? ''); 
$search_skill = trim($_GET['search_skill'] ?? ''); 
$search_dept  = trim($_GET['search_dept'] ?? ''); 

$students = []; 

try {
    // 2. SQL 查詢
    // 我們使用 LEFT JOIN 連接 departments 表格來拿科系名稱
    // 技巧：技能搜尋不 JOIN 其他表，直接搜 users.bio
    $sql = "SELECT 
                u.id, 
                u.name, 
                u.photo_path, 
                u.bio,
                d.name as dept_name 
            FROM users u
            LEFT JOIN departments d ON u.dept_id = d.id
            WHERE u.role = 'student'";
    
    $params = [];
    
    // --- 條件 1: 姓名 (搜尋 users.name) ---
    if (!empty($search_name)) {
        $sql .= " AND u.name LIKE ?"; 
        $params[] = "%" . $search_name . "%";
    }
    
    // --- 條件 2: 技能 (直接搜尋 users.bio 簡介內容) ---
    // 只要簡介裡有提到這個技能 (例如 'PHP')，就會被搜出來
    if (!empty($search_skill)) {
        $sql .= " AND u.bio LIKE ?"; 
        $params[] = "%" . $search_skill . "%";
    }

    // --- 條件 3: 科系 (搜尋 departments.name) ---
    // 這會去對應您 departments 表格裡的名稱
    if (!empty($search_dept)) {
        $sql .= " AND d.name LIKE ?";
        $params[] = "%" . $search_dept . "%";
    }

    // 執行查詢
    $students_from_db = fetchAll($sql, $params);
    
    // 3. 資料格式整理
    foreach ($students_from_db as $s) {
        // 照片處理
        $photo_url = !empty($s['photo_path']) ? $s['photo_path'] : 'https://via.placeholder.com/100?text=No+Photo';
        
        // 簡介處理
        $intro_full = $s['bio'] ?? '';
        $intro_display = '這位同學尚未填寫簡介...';
        
        if (!empty($intro_full)) {
            // 截斷過長的簡介
            if (mb_strlen($intro_full, 'utf-8') > 40) {
                $intro_display = mb_substr($intro_full, 0, 40, 'utf-8') . '...';
            } else {
                $intro_display = $intro_full;
            }
        }
        
        // 把科系名稱加到簡介前面
        if (!empty($s['dept_name'])) {
            $intro_display = "【" . htmlspecialchars($s['dept_name']) . "】 " . $intro_display;
        }

        $students[] = [
            'id'    => $s['id'],
            'name'  => $s['name'],
            'photo' => $photo_url, 
            'intro' => $intro_display
        ];
    }

} catch (PDOException $e) {
    echo '<div class="container" style="padding-top:20px;">
              <div class="alert alert-danger">
                  系統錯誤： ' . $e->getMessage() . '
              </div>
            </div>';
    $students = [];
}
?>

<div class="container" style="padding-top: 40px; padding-bottom: 40px;">

    <h2 style="text-align: center; margin-bottom: 30px;">人才搜尋</h2>

    <form method="GET" action="index.php" class="filter-bar" style="display: flex; flex-wrap: wrap; gap: 15px; background: #f8f9fa; padding: 20px; border-radius: 8px;">
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_name" style="font-weight: bold;">學生姓名:</label>
            <input type="text" id="search_name" name="search_name" placeholder="輸入學生姓名..." class="form-control"
                   value="<?php echo htmlspecialchars($search_name); ?>">
        </div>
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_skill" style="font-weight: bold;">具備技能 (搜尋簡介):</label>
            <input type="text" id="search_skill" name="search_skill" placeholder="例如: PHP, 攝影..." class="form-control"
                   value="<?php echo htmlspecialchars($search_skill); ?>">
        </div>
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_dept" style="font-weight: bold;">學校科系:</label>
            <input type="text" id="search_dept" name="search_dept" placeholder="例如：資訊..." class="form-control"
                   value="<?php echo htmlspecialchars($search_dept); ?>">
        </div>
        
        <div style="display: flex; gap: 10px; align-self: flex-end;">
            <button type="submit" class="btn btn-primary" style="margin-top: 0;">搜尋</button>
            <a href="index.php" class="btn btn-secondary" style="margin-top: 0;">清除篩選</a>
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
                    <a href="profile.php?id=<?php echo $student['id']; ?>" style="text-decoration: none; color: inherit;">
                        <img src="<?php echo htmlspecialchars($student['photo']); ?>" 
                             alt="<?php echo htmlspecialchars($student['name']); ?>"
                             style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #eee; margin-bottom: 15px;">
                        <h3 style="margin: 10px 0; color: #333; font-size: 1.2rem;">
                            <?php echo htmlspecialchars($student['name']); ?>
                        </h3>
                    </a>
                    <p style="color: #666; font-size: 0.9rem; min-height: 40px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($student['intro']); ?>
                    </p>
                    <a href="profile.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">查看完整檔案</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div> 
</div> 

<?php require_once('footer.php'); ?>