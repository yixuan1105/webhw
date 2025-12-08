<?php
require_once('db.php');

$page_title = '成果展示 - 學生學習成果認證系統';
$page_css_files = ['admin.css']; 

require_once('header.php'); 

// 1. 接收和清理搜尋參數
$search_name  = trim($_GET['search_name'] ?? ''); 
$search_skill = trim($_GET['search_skill'] ?? ''); 
$search_dept  = trim($_GET['search_dept'] ?? ''); 

$students = []; // 儲存最終要顯示在頁面上的學生資料。

try {
    // 2. 基礎 SQL 查詢 (針對 users 表)
    $sql = "SELECT DISTINCT 
                u.id, 
                u.name, 
                u.photo_path, 
                u.bio 
            FROM users u
            ";
            
    $params = []; // 儲存動態的 SQL 參數。
    
    // 3. 動態 JOIN 語句和 WHERE 條件
    
    // --- 技能搜尋 (JOIN skills 和 user_skills) ---
    if (!empty($search_skill)) {
        // 為了篩選技能，必須 JOIN 中間表 user_skills 和 skills 表
        $sql .= " INNER JOIN user_skills us ON u.id = us.user_id";
        $sql .= " INNER JOIN skills s ON us.skill_id = s.id";
    }

    // --- 科系搜尋 (JOIN departments) ---
    if (!empty($search_dept)) {
        // 為了篩選科系，必須 JOIN departments 表
        $sql .= " INNER JOIN departments d ON u.dept_id = d.id";
    }

    // 基礎 WHERE 條件：角色必須是 'student'
    $sql .= " WHERE u.role = 'student'";
    
    // --- 姓名條件判斷 ---
    if (!empty($search_name)) {
        $sql .= " AND u.name LIKE ?"; 
        $params[] = "%" . $search_name . "%";
    }
    
    // --- 技能條件判斷 (針對 JOIN 後的 skills.name) ---
    if (!empty($search_skill)) {
        // 搜尋 skills 表中的名稱
        $sql .= " AND s.skill_name LIKE ?"; 
        $params[] = "%" . $search_skill . "%";
    }

    // --- 科系條件判斷 (針對 JOIN 後的 departments.name) ---
    if (!empty($search_dept)) {
        // 搜尋 departments 表中的名稱
        $sql .= " AND d.name LIKE ?";
        $params[] = "%" . $search_dept . "%";
    }

    // 執行資料庫查詢
    $students_from_db = fetchAll($sql, $params);
    
    // 4. 資料格式整理 (與原程式碼相同，用於簡介截斷)
    foreach ($students_from_db as $s) {
        $photo_url = $s['photo_path'] ?? ''; 
        
        $intro_full = $s['bio'] ?? '';
        $intro_short = '這位同學尚未填寫簡介...';
        
        if (!empty($intro_full)) {
            if (mb_strlen($intro_full, 'utf-8') > 40) {
                $intro_short = mb_substr($intro_full, 0, 40, 'utf-8') . '...';
            } else {
                $intro_short = $intro_full;
            }
        }

        $students[] = [
            'id'    => $s['id'],
            'name'  => $s['name'],
            'photo' => $photo_url, 
            'intro' => $intro_short
        ];
    }

} catch (PDOException $e) {
    // 錯誤處理
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
            <input 
                type="text" 
                id="search_name" 
                name="search_name" 
                placeholder="輸入學生姓名..." 
                class="form-control"
                value="<?php echo htmlspecialchars($search_name); ?>" 
            >
        </div>
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_skill" style="font-weight: bold;">具備技能:</label>
            <input 
                type="text" 
                id="search_skill" 
                name="search_skill" 
                placeholder="例如: PHP, 攝影..." 
                class="form-control"
                value="<?php echo htmlspecialchars($search_skill); ?>"
            >
        </div>
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_dept" style="font-weight: bold;">學校科系:</label>
            <input 
                type="text" 
                id="search_dept" 
                name="search_dept" 
                placeholder="例如：資訊工程..." 
                class="form-control"
                value="<?php echo htmlspecialchars($search_dept); ?>"
            >
        </div>
        
        <div style="display: flex; gap: 10px; align-self: flex-end;">
            <button type="submit" class="btn btn-primary" style="margin-top: 0;">
                <i class="bi bi-search"></i> 搜尋
            </button>
            <a href="index.php" class="btn btn-secondary" style="margin-top: 0;">清除篩選</a>
        </div>
    </form>


    <div class="students-grid" style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
        
        <?php if (empty($students)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <p style="font-size: 18px; color: #777; margin: 0;">
                    沒有找到符合條件的學生。
                </p>
            </div>
        
        <?php else: ?>
            <?php foreach ($students as $student): ?>
                
                <div class="student-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; transition: transform 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <a href="profile.php?id=<?php echo $student['id']; ?>" style="text-decoration: none; color: inherit;">
                        <img 
                            src="<?php echo htmlspecialchars($student['photo']); ?>" 
                            alt="<?php echo htmlspecialchars($student['name']); ?> 的大頭貼"
                            style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #eee; margin-bottom: 15px;"
                        >
                        <h3 style="margin: 10px 0; color: #333; font-size: 1.2rem;">
                            <?php echo htmlspecialchars($student['name']); ?>
                        </h3>
                    </a>
                    
                    <p style="color: #666; font-size: 0.9rem; min-height: 40px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($student['intro']); ?>
                    </p>
                    
                    <a href="profile.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">
                        查看完整檔案
                    </a>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>

    </div> 
</div> 

<?php
require_once('footer.php'); 
?>