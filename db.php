
<?php  
//要查網頁的時候:輸入http://127.0.0.1/webhw/.php
// 定義資料庫主機位置，127.0.0.1 是本機，3307 是 MySQL 的埠號
define('DB_HOST', '127.0.0.1');

// 定義要連線的資料庫名稱
define('DB_NAME', 'web_student');

// 定義資料庫登入的使用者名稱
define('DB_USER', 'root');

// 定義資料庫登入密碼（開發環境通常為空，生產環境務必設定強密碼）
define('DB_PASS', 'Peijun0629');

// ==========================================
// 資料庫連線類別（使用單例模式）
// ==========================================

class Database {
    // 宣告靜態私有變數，用來存放唯一的 PDO 連線實例
    private static $instance = null;
    
    /**
     * 取得資料庫連線（單例模式）
     * 確保整個應用程式只會建立一個資料庫連線
     * @return PDO 返回 PDO 連線物件
     */
    public static function getConnection() {
        // 檢查是否已經建立連線，如果沒有則建立新連線
        if (self::$instance === null) {
            
            // 組合 DSN (Data Source Name) 字串
            // 格式：mysql:host=主機;dbname=資料庫名稱;charset=字元編碼
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            
            // PDO 連線選項陣列
            $options = [
                // 設定錯誤模式為拋出例外，方便除錯和錯誤處理
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                
                // 設定預設的資料取得模式為關聯陣列（例如：['id' => 1, 'name' => 'John']）
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                
                // 禁用模擬預處理語句，使用真正的 prepared statements，提高安全性
                PDO::ATTR_EMULATE_PREPARES => false,
                
                // 使用持久連線，連線會被快取重複使用，提升效能
                PDO::ATTR_PERSISTENT => true,
            ];

            // 使用 try-catch 捕捉連線過程中可能發生的錯誤
            try {
                // 建立 PDO 物件並存入靜態變數中
                // 參數：DSN, 使用者名稱, 密碼, 選項陣列
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                
            } catch (\PDOException $e) {
                // 如果連線失敗，將錯誤訊息寫入伺服器日誌（不顯示給使用者）
                error_log("資料庫連線失敗: " . $e->getMessage());
                
                // 停止程式執行並顯示友善的錯誤訊息（不暴露敏感資訊）
                die("系統錯誤，請稍後再試");
            }
        }
        
        // 返回已建立的 PDO 連線實例
        return self::$instance;
    }
}

// ==========================================
// 通用資料庫操作函式
// ==========================================

/**
 * 執行 SELECT 查詢並回傳多筆結果
 * 適用場景：取得多個使用者、多筆成果記錄等
 * 
 * @param string $sql SQL 查詢語句（例如："SELECT * FROM users WHERE status = ?"）
 * @param array $params 綁定參數陣列（例如：['active']），用來防止 SQL 注入攻擊
 * @return array 查詢結果的二維陣列
 */
function fetchAll($sql, $params = []) {
    // 取得資料庫連線物件
    $pdo = Database::getConnection();
    
    // 準備 SQL 語句（預處理，尚未執行）
    $stmt = $pdo->prepare($sql);
    
    // 執行 SQL 語句，並綁定參數（參數會自動轉義，防止 SQL 注入）
    $stmt->execute($params);
    
    // 取得所有查詢結果並以陣列形式返回
    return $stmt->fetchAll();
}

/**
 * 執行 SELECT 查詢並回傳單筆結果
 * 適用場景：使用者登入驗證、查詢特定 ID 的資料等
 * 
 * @param string $sql SQL 查詢語句（例如："SELECT * FROM users WHERE id = ?"）
 * @param array $params 綁定參數陣列（例如：[123]）
 * @return array|false 單筆結果的關聯陣列，若無結果則返回 false
 */
function fetchOne($sql, $params = []) {
    // 取得資料庫連線物件
    $pdo = Database::getConnection();
    
    // 準備 SQL 語句
    $stmt = $pdo->prepare($sql);
    
    // 執行 SQL 語句並綁定參數
    $stmt->execute($params);
    
    // 取得單筆查詢結果（只取第一筆）
    return $stmt->fetch();
}

/**
 * 執行 INSERT、UPDATE、DELETE 語句
 * 適用場景：新增資料、更新資料、刪除資料
 * 
 * @param string $sql 執行的 SQL 語句（例如："UPDATE users SET name = ? WHERE id = ?"）
 * @param array $params 綁定參數陣列（例如：['新名稱', 123]）
 * @return int 受影響的行數（成功更新/刪除幾筆資料）
 */
function execute($sql, $params = []) {
    // 取得資料庫連線物件
    $pdo = Database::getConnection();
    
    // 準備 SQL 語句
    $stmt = $pdo->prepare($sql);
    
    // 執行 SQL 語句並綁定參數
    $stmt->execute($params);
    
    // 返回受影響的行數（例如：更新了 3 筆資料就返回 3）
    return $stmt->rowCount();
}

/**
 * 執行 INSERT 語句並回傳新插入資料的 ID
 * 適用場景：新增使用者後取得新使用者的 ID、新增成果後取得成果 ID
 * 
 * @param string $sql INSERT SQL 語句（例如："INSERT INTO users (name, email) VALUES (?, ?)"）
 * @param array $params 綁定參數陣列（例如：['John', 'john@example.com']）
 * @return string 新插入資料的自動遞增 ID（通常是主鍵 ID）
 */
function insertAndGetId($sql, $params = []) {
    // 取得資料庫連線物件
    $pdo = Database::getConnection();
    
    // 準備 SQL 語句
    $stmt = $pdo->prepare($sql);
    
    // 執行 INSERT 語句並綁定參數
    $stmt->execute($params);
    
    // 返回最後插入資料的自動遞增 ID
    return $pdo->lastInsertId();
}

?>