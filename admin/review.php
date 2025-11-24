<?php
// ==========================================
// admin/review.php - 審核成果列表
// ==========================================

// 步驟 1：引入必要模組
require_once('../db.php');      // 資料庫連線
require_once('../iden.php');    // 身份驗證

// 步驟 2：權限檢查（只有管理員可以進入）
requireAdminLogin();

// 步驟 3：定義此頁面專屬的資訊
$page_title = '審核成果列表 - 管理員後台';
$page_css_files = ['admin.css']; // 使用管理員專用樣式

// 步驟 3.5：處理成功/錯誤訊息（從 URL 參數取得）
$success_message = '';
$error_message = '';

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = $_GET['msg'] ?? '操作成功';
}

if (isset($_GET['error']) && $_GET['error'] == 1) {
    $error_message = $_GET['msg'] ?? '操作失敗';
}

// 步驟 4：引入共用的 header.php（往上一層找）
require_once('../header.php'); 

// 步驟 5：取得 GET 傳來的篩選條件
// 使用 trim() 去除前後空白，'??' 確保變數存在
$filter_status = trim($_GET['status'] ?? '');       // 狀態篩選（pending/approved/rejected）
$filter_category = trim($_GET['category'] ?? '');   // 類別篩選
$search_student = trim($_GET['student'] ?? '');     // 學生姓名搜尋

// 步驟 6：從資料庫撈取成果資料
try {
    
    // ** 動態建立 SQL 查詢 **
    
    // 基礎查詢：撈取成果資料並關聯學生姓名
    $sql = "SELECT 
                a.id, 
                a.title, 
                a.category, 
                a.description, 
                a.status, 
                a.created_at,
                u.name as student_name,
                u.id as student_id
            FROM achievements a
            INNER JOIN users u ON a.user_id = u.id
            WHERE 1=1"; // WHERE 1=1 是為了方便後續加條件
    
    // 用來存放 SQL 參數，防止 SQL Injection
    $params = [];
    
    // 如果有選擇狀態篩選
    if (!empty($filter_status) && $filter_status !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $filter_status;
    }
    
    // 如果有選擇類別篩選
    if (!empty($filter_category)) {
        $sql .= " AND a.category = ?";
        $params[] = $filter_category;
    }
    
    // 如果有輸入學生姓名搜尋
    if (!empty($search_student)) {
        $sql .= " AND u.name LIKE ?";
        $params[] = "%" . $search_student . "%"; // 模糊比對
    }
    
    // 排序：待審核的優先，然後按提交時間新到舊
    $sql .= " ORDER BY 
                CASE 
                    WHEN a.status = 'pending' THEN 1 
                    WHEN a.status = 'approved' THEN 2 
                    WHEN a.status = 'rejected' THEN 3 
                END,
                a.created_at DESC";
    
    // 執行 SQL 查詢
    $achievements = fetchAll($sql, $params);
    
    // 統計各狀態的數量（用於顯示在統計卡片）
    $sql_stats = "SELECT 
                    status, 
                    COUNT(*) as count 
                  FROM achievements 
                  GROUP BY status";
    $stats_raw = fetchAll($sql_stats);
    
    // 整理統計資料成關聯陣列
    $stats = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
    foreach ($stats_raw as $stat) {
        $stats[$stat['status']] = $stat['count'];
    }
    $stats['total'] = array_sum($stats);

} catch (PDOException $e) {
    $achievements = [];
    $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
    echo '<div class="container"><div class="alert alert-error">無法載入成果資料： ' . htmlspecialchars($e->getMessage()) . '</div></div>';
}

// 類別中文對照表
$category_map = [
    'subject' => '擅長科目',
    'language' => '程式語言',
    'competition' => '競賽',
    'certificate' => '證照'
];

// 狀態中文對照表
$status_map = [
    'pending' => '待審核',
    'approved' => '已認證',
    'rejected' => '不通過'
];
?>

<!-- ==========================================
     主要內容區域
     ========================================== -->
<div class="container" style="padding-top: 40px; padding-bottom: 40px;">

    <!-- 頁面標題 -->
    <h1 style="text-align: center; margin-bottom: 30px;">
        📋 審核成果列表
    </h1>
    
    <!-- 成功訊息 -->
    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
        ✓ <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>
    
    <!-- 錯誤訊息 -->
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-error">
        ✗ <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>
    
    <!-- ==========================================
         統計卡片區域
         ========================================== -->
    <div class="dashboard-stats" style="margin-bottom: 30px;">
        
        <!-- 待審核成果卡片 -->
        <div class="stat-card pending">
            <h3>待審核</h3>
            <div class="number"><?php echo $stats['pending']; ?></div>
            <a href="?status=pending">查看列表 →</a>
        </div>
        
        <!-- 已認證成果卡片 -->
        <div class="stat-card approved">
            <h3>已認證</h3>
            <div class="number"><?php echo $stats['approved']; ?></div>
            <a href="?status=approved">查看列表 →</a>
        </div>
        
        <!-- 不通過成果卡片 -->
        <div class="stat-card rejected">
            <h3>不通過</h3>
            <div class="number"><?php echo $stats['rejected']; ?></div>
            <a href="?status=rejected">查看列表 →</a>
        </div>
        
        <!-- 成果總數卡片 -->
        <div class="stat-card">
            <h3>總數</h3>
            <div class="number"><?php echo $stats['total']; ?></div>
            <a href="review.php">查看全部 →</a>
        </div>
        
    </div>

    <!-- ==========================================
         篩選器和搜尋列
         ========================================== -->
    <form method="GET" action="review.php" class="filter-bar" style="flex-wrap: wrap; margin-bottom: 30px;">
        
        <!-- 狀態篩選 -->
        <div style="flex-grow: 1; min-width: 150px;">
            <label for="status">狀態:</label>
            <select id="status" name="status" class="form-control">
                <option value="">全部狀態</option>
                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>待審核</option>
                <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>已認證</option>
                <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>不通過</option>
            </select>
        </div>
        
        <!-- 類別篩選 -->
        <div style="flex-grow: 1; min-width: 150px;">
            <label for="category">類別:</label>
            <select id="category" name="category" class="form-control">
                <option value="">全部類別</option>
                <option value="subject" <?php echo $filter_category === 'subject' ? 'selected' : ''; ?>>擅長科目</option>
                <option value="language" <?php echo $filter_category === 'language' ? 'selected' : ''; ?>>程式語言</option>
                <option value="competition" <?php echo $filter_category === 'competition' ? 'selected' : ''; ?>>競賽</option>
                <option value="certificate" <?php echo $filter_category === 'certificate' ? 'selected' : ''; ?>>證照</option>
            </select>
        </div>
        
        <!-- 學生姓名搜尋 -->
        <div style="flex-grow: 2; min-width: 200px;">
            <label for="student">學生姓名:</label>
            <input 
                type="text" 
                id="student" 
                name="student" 
                placeholder="輸入學生姓名..." 
                class="form-control"
                value="<?php echo htmlspecialchars($search_student); ?>"
            >
        </div>
        
        <!-- 按鈕群組 -->
        <div style="display: flex; gap: 10px; align-self: flex-end;">
            <button type="submit" class="btn btn-primary" style="margin-top: 0;">篩選</button>
            <a href="review.php" class="btn btn-secondary" style="margin-top: 0;">清除</a>
        </div>
    </form>

    <!-- ==========================================
         成果列表表格
         ========================================== -->
    <div class="review-table">
        
        <?php if (empty($achievements)): ?>
            <!-- 如果沒有資料 -->
            <p style="text-align: center; padding: 40px; font-size: 18px; color: #777;">
                查無符合條件的成果資料。
            </p>
        
        <?php else: ?>
            <!-- 成果資料表格 -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>學生姓名</th>
                        <th>成果標題</th>
                        <th>類別</th>
                        <th style="width: 100px;">狀態</th>
                        <th style="width: 150px;">提交時間</th>
                        <th style="width: 200px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($achievements as $ach): ?>
                    <tr>
                        <!-- ID -->
                        <td><?php echo $ach['id']; ?></td>
                        
                        <!-- 學生姓名（點擊可查看學生檔案） -->
                        <td>
                            <a href="../profile_view.php?id=<?php echo $ach['student_id']; ?>" 
                               style="color: #3498db; text-decoration: none;">
                                <?php echo ($ach['student_name']); ?>
                            </a>
                        </td>
                        
                        <!-- 成果標題 -->
                        <td>
                            <strong><?php echo ($ach['title']); ?></strong>
                            <?php if (!empty($ach['description'])): ?>
                                <br>
                                <small style="color: #7f8c8d;">
                                    <?php 
                                    // 只顯示前 50 個字元
                                    $desc = ($ach['description']);
                                    echo mb_substr($desc, 0, 50) . (mb_strlen($desc) > 50 ? '...' : ''); 
                                    ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        
                        <!-- 類別 -->
                        <td><?php echo $category_map[$ach['category']] ?? $ach['category']; ?></td>
                        
                        <!-- 狀態標籤 -->
                        <td>
                            <span class="badge badge-<?php echo $ach['status']; ?>">
                                <?php echo $status_map[$ach['status']] ?? $ach['status']; ?>
                            </span>
                        </td>
                        
                        <!-- 提交時間 -->
                        <td><?php echo date('Y-m-d H:i', strtotime($ach['created_at'])); ?></td>
                        
                        <!-- 操作按鈕 -->
                        <td>
                            <div class="actions">
                                <!-- 查看詳情按鈕 -->
                                <a href="review_detail.php?id=<?php echo $ach['id']; ?>" 
                                   class="btn-view">
                                    查看
                                </a>
                                
                                <!-- 只有待審核狀態才顯示審核按鈕 -->
                                <?php if ($ach['status'] === 'pending'): ?>
                                    <!-- 通過按鈕 -->
                                    <form method="POST" action="review_process.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $ach['id']; ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button 
                                            type="submit" 
                                            class="btn-approve"
                                            onclick="return confirm('確定要通過此成果嗎？');">
                                            通過
                                        </button>
                                    </form>
                                    
                                    <!-- 不通過按鈕 -->
                                    <form method="POST" action="review_process.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $ach['id']; ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button 
                                            type="submit" 
                                            class="btn-reject"
                                            onclick="return confirm('確定要標記為不通過嗎？');">
                                            不通過
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- 顯示總筆數 -->
            <div style="text-align: center; margin-top: 20px; color: #7f8c8d;">
                共 <?php echo count($achievements); ?> 筆成果
            </div>
        <?php endif; ?>

    </div> <!-- .review-table -->

</div> <!-- .container -->

<?php
// 步驟 ：引入共用的 footer.php（往上一層找）
require_once('../footer.php'); 
?>