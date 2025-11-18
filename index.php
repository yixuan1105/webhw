<?php
// ==========================================
// index.php - 網站首頁 (新版：成果展示)
// ==========================================

// 步驟 1：引入資料庫
require_once('db.php');

// 步驟 2：定義此頁面專屬的資訊
$page_title = '成果展示 - 學生學習成果認證系統';

// ** 變更點：我們不再載入 index.css **
// 我們改為載入 admin.css，因為它有 .students-grid 和 .student-card 的漂亮樣式
// (common.css 會由 header.php 自動載入)
$page_css_files = ['admin.css'];

// 步驟 3：引入共用的 header.php
// (這會自動啟動 session 並輸出 <head> 和導覽列)
require_once('header.php'); 

// 步驟 4：從資料庫撈取所有學生資料
// ** 注意：這是一個範例查詢 **
// 您需要根據您的資料庫結構 (例如 profile 表格) 來擴充這個 SQL
try {
    // 從 users 表格撈取所有學生
    $sql = "SELECT id, name FROM users WHERE role = 'student'";
    $students_from_db = fetchAll($sql);
    
    // ** 暫時處理：因為我不知道您儲存照片和簡介的欄位 **
    // 
    // 您未來需要修改這段，改成從您的 profile 表格撈取真實的照片和簡介
    //
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
    // 資料庫查詢失敗
    $students = [];
    echo '<div class="container"><div class="alert alert-error">無法載入學生資料： ' . htmlspecialchars($e->getMessage()) . '</div></div>';
}
?>

<div class="container" style="padding-top: 40px; padding-bottom: 40px;">

    <h2 style="text-align: center; margin-bottom: 30px;">瀏覽學生專案與成果</h2>

    <div class="filter-bar">
        <label for="filter-skill">依技能搜尋:</label>
        <input type="text" id="filter-skill" name="skill_search" placeholder="例如：PHP, 攝影, 專案管理...">
        
        <label for="filter-major">依科系:</label>
        <select id="filter-major" name="major">
            <option value="">所有科系</option>
            <option value="cs">資訊工程</option>
            <option value="design">數位設計</option>
        </select>
        
        </div>

    <div class.="students-grid">
        
        <?php if (empty($students)): ?>
            <p style="text-align: center; grid-column: 1 / -1;">目前沒有學生資料。</p>
        
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
// 步驟 6：引入共用的 footer.php
require_once('footer.php'); 
?>