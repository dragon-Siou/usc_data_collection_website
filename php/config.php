<?php
/**
 * 資料庫連線設定檔
 * config.php
 */

// 資料庫連線參數
define('DB_HOST', 'localhost');           // 資料庫主機
define('DB_NAME', 'data_collection_system'); // 資料庫名稱
define('DB_USER', 'root');                // 資料庫使用者
define('DB_PASS', '');                    // 資料庫密碼
define('DB_CHARSET', 'utf8mb4');          // 字元集

// 建立資料庫連線
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // 記錄錯誤到日誌檔
        error_log("Database Connection Error: " . $e->getMessage());
        throw new Exception("資料庫連線失敗");
    }
}

// CORS 設定（允許前端跨域請求）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// 處理 OPTIONS 請求（預檢請求）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 錯誤處理函數
function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 成功回應函數
function sendSuccessResponse($data, $message = '操作成功') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 取得客戶端 IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
?>