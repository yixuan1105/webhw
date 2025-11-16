<?php
define('DB_HOST', '127.0.0.1:3307');          // 資料庫主機位置 (通常是 localhost)
define('DB_NAME', 'web_student');    // 您在 phpMyAdmin 中建立的資料庫名稱
define('DB_USER', 'root');               // 資料庫使用者名稱
define('DB_PASS', '');                   // 資料庫密碼 (如果使用 XAMPP/MAMP 預設可能是空)

// -----------------------------------------------------
// 建立 PDO 連線實例
// -----------------------------------------------------
function connectDB() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // 發生錯誤時拋出例外
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // 預設以關聯陣列取得結果
        PDO::ATTR_EMULATE_PREPARES => false,                // 禁用模擬預處理，提高安全性
    ];

    try {
        // 建立並返回 PDO 物件
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (\PDOException $e) {
        // 連線失敗時，輸出錯誤並停止腳本執行
        // 在生產環境中，應記錄錯誤而不是直接顯示
        die("資料庫連線失敗: " . $e->getMessage());
    }
}


// -----------------------------------------------------
// 通用資料庫操作函式 (用於 SELECT, INSERT, UPDATE, DELETE)
// -----------------------------------------------------

/**
 * 執行 SELECT 查詢並回傳多筆結果
 * @param string $sql 查詢 SQL 語句
 * @param array $params 綁定參數陣列 (防止 SQL 注入)
 * @return array 查詢結果陣列
 */
function fetchAll($sql, $params = []) {
    $pdo = connectDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * 執行 SELECT 查詢並回傳單筆結果 (例如：登入、查詢單一成果)
 * @param string $sql 查詢 SQL 語句
 * @param array $params 綁定參數陣列
 * @return array|false 單筆結果陣列，若無結果則返回 false
 */
function fetchOne($sql, $params = []) {
    $pdo = connectDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * 執行 INSERT, UPDATE, DELETE 語句
 * @param string $sql 執行 SQL 語句
 * @param array $params 綁定參數陣列
 * @return int 受影響的行數
 */
function execute($sql, $params = []) {
    $pdo = connectDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * 執行 INSERT 語句並回傳新插入資料的 ID (用於新增成果/使用者)
 * @param string $sql 執行 INSERT SQL 語句
 * @param array $params 綁定參數陣列
 * @return string 新插入資料的 ID
 */
function insertAndGetId($sql, $params = []) {
    $pdo = connectDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

?>