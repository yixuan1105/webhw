<?php
// ==========================================
// index.php - 網站首頁 (整合搜尋功能)
// ==========================================

// 步驟 1：引入資料庫
require_once('db.php');

// 步驟 2：定義此頁面專屬的資訊
$page_title = '成果展示 - 學生學習成果認證系統';
$page_css_files = ['admin.css']; // 繼續使用 admin.css 的卡片樣式

// 步驟 3：引入共用的 header.php
require_once('header.php'); 

// 步驟 4：取得 GET 傳來的搜尋條件
// 使用 trim() 去除前後空白，'??' 確保變數存在
$search_name = trim($_GET['search_name'] ?? '');
$search_skill = trim($_GET['search_skill'] ?? '');
$search_dept = trim($_GET['search_dept'] ?? '');

// 步驟 5：從資料庫撈取學生資料
try {
    
    // ** 變更點：動態建立 SQL 查詢 **
    
    // 基礎查詢：只找學生
    // (iden.php) 顯示 users 表有 'name' 和 'role' 欄位
    $sql = "SELECT id, name FROM users WHERE role = 'student'";
    
    // 用來存放 SQL 參數，防止 SQL Injection
    $params = [];
    
    // 如果「學生姓名」搜尋框不是空的
    if (!empty($search_name)) {
        // (iden.php) 'name' 欄位存在於 users 表
        $sql .= " AND name LIKE ?"; 
        $params[] = "%" . $search_name . "%"; // 模糊比對
    }
    
    // --- !! 關於技能與科系的重要說明 !! ---
    //
    // 目前您的 `users` 表格只有 'name' 欄位可供搜尋。
    // 為了讓「技能」和「科系」搜尋能運作，
    // 您未來需要建立一個 `profiles` 表格來儲存這些資料。
    //
    // 範例 (未來)：
    // if (!empty($search_skill)) {
    //    // 假設您在 profiles 表有一個 skills 欄位
    //    $sql .= " AND p.skills LIKE ?"; 
    //    $params[] = "%" . $search_skill . "%";
    // }
    // if (!empty($search_dept)) {
    //    // 假設您在 profiles 表有一個 department 欄位
    //    $sql .= " AND p.department LIKE ?";
    //    $params[] = "%" . $search_dept . "%";
    // }
    //
    // (目前我們只實作姓名的搜尋)
    // ---
    
    // 執行 SQL 查詢
    $students_from_db = fetchAll($sql, $params);
    
    // (如同上一版，這裡暫時虛構照片和簡介)
    $students = [];
    foreach ($students_from_db as $student) {
        $students[] = [
            'id' => $student['id'],
            'name' => $student['name'],
            'photo' => 'https://via.placeholder.com/100', // 暫時：請替換成您的照片欄位
            'intro' => '學生簡介/科系...' // 暫時：請替換成您的簡介欄位
        ];
    }

} catch (PDOException $e) {
    $students = [];
    echo '<div class="container"><div class="alert alert-error">無法載入學生資料： ' . htmlspecialchars($e->getMessage()) . '</div></div>';
}
?>

<div class="container" style="padding-top: 40px; padding-bottom: 40px;">

    <h2 style="text-align: center; margin-bottom: 30px;">瀏覽學生專案與成果</h2>

    <form method="GET" action="index.php" class="filter-bar" style="flex-wrap: wrap;">
        
        <div style="flex-grow: 1; min-width: 200px;">
            <label for="search_name">學生姓名:</label>
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
            <label for="search_skill">具備技能:</label>
            <input 
                type="text" 
                id="search_skill" 
                name="search_skill" 
                placeholder="例如：PHP, 攝影..." 
                class="form-control"
                value="<?php echo htmlspecialchars($search_skill); ?>"
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
                value="<?php echo htmlspecialchars($search_dept); ?>"
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
                    <img src="<?php echo htmlspecialchars($student['photo']); ?>" alt="<?php echo htmlspecialchars($student['name']); ?> 的大頭貼">
                    <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                    <p><?php echo htmlspecialchars($student['intro']); ?></p>
                    
                    <a href="profile_view.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">
                        查看完整檔案
                    </a>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>

    </div> </div> <?php
// 步驟 7：引入共用的 footer.php
require_once('footer.php'); 
?>