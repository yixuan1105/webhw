<?php

require_once('db.php');

$page_title = '成果展示 - 學生學習成果認證系統';
$page_css_files = ['admin.css']; 

require_once('header.php'); 

$search_name = trim($_GET['search_name'] ?? '');
$search_skill = trim($_GET['search_skill'] ?? '');
$search_dept = trim($_GET['search_dept'] ?? '');

try {
    $sql = "SELECT id, name FROM users WHERE role = 'student'";
    $params = [];
    
    if (!empty($search_name)) {
        $sql .= " AND name LIKE ?"; 
        $params[] = "%" . $search_name . "%"; 
    }
    
    $students_from_db = fetchAll($sql, $params);
    
    $students = [];
    foreach ($students_from_db as $student) {
        $students[] = [
            'id' => $student['id'],
            'name' => $student['name'],
            'photo' => 'https://via.placeholder.com/100', 
            'intro' => '學生簡介/科系...' 
        ];
    }

} catch (PDOException $e) {
    $students = [];
    echo '<div class="container"><div class="alert alert-error">無法載入學生資料： ' . $e->getMessage() . '</div></div>';
}
?>

<div class="container" style="padding-top: 40px; padding-bottom: 40px;">

    <h2 style="text-align: center; margin-bottom: 30px;">人才搜尋</h2>

    <form method="GET" action="index.php" class="filter-bar" style="flex-wrap: wrap;">
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_name">學生姓名:</label>
            <input 
                type="text" 
                id="search_name" 
                name="search_name" 
                placeholder="輸入學生姓名..." 
                class="form-control"
                value="<?php echo $search_name; ?>" 
            >
        </div>
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_skill">具備技能:</label>
            <input 
                type="text" 
                id="search_skill" 
                name="search_skill" 
                placeholder="例如:PHP, 攝影..." 
                class="form-control"
                value="<?php echo $search_skill; ?>"
            >
        </div>
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_dept">學校科系:</label>
            <input 
                type="text" 
                id="search_dept" 
                name="search_dept" 
                placeholder="例如：資訊工程..." 
                class="form-control"
                value="<?php echo $search_dept; ?>"
            >
        </div>
        
        <div style="display: flex; gap: 10px; align-self: flex-end;">
            <button type="submit" class="btn btn-primary" style="margin-top: 0;">搜尋</button>
            <a href="index.php" class="btn btn-secondary" style="margin-top: 0;">清除篩選</a>
        </div>
    </form>


    <div class="students-grid" style="margin-top: 30px;">
        
        <?php if (empty($students)): ?>
            <p style="text-align: center; grid-column: 1 / -1; font-size: 18px; color: #777;">
                查無符合條件的學生。
            </p>
        
        <?php else: ?>
            <?php foreach ($students as $student): ?>
                
                <div class="student-card">
                    <img src="<?php echo $student['photo']; ?>" alt="<?php echo $student['name']; ?> 的大頭貼">
                    <h3><?php echo $student['name']; ?></h3>
                    <p><?php echo $student['intro']; ?></p>
                    
                    <a href="profile_view.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">
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