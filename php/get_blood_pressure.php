<?php
/**
 * 查詢血壓資料統計
 * get_blood_pressure.php
 */

require_once 'config.php';

// 只接受 GET 請求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('只接受 GET 請求', 405);
}

try {
    $pdo = getDBConnection();
    
    // 查詢類型
    $type = $_GET['type'] ?? 'all';
    
    switch ($type) {
        case 'stats':
            // 統計資料
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_records,
                    COUNT(DISTINCT person_id) as total_persons,
                    AVG(systolic_bp) as avg_systolic,
                    AVG(diastolic_bp) as avg_diastolic,
                    MAX(submitted_at) as latest_record
                FROM blood_pressure
            ");
            $data = $stmt->fetch();
            
            sendSuccessResponse($data, '統計資料查詢成功');
            break;
            
        default:
            sendErrorResponse('不支援的查詢類型');
            break;
    }
    
} catch (PDOException $e) {
    error_log("Blood Pressure Query Error: " . $e->getMessage());
    sendErrorResponse('查詢失敗', 500);
} catch (Exception $e) {
    sendErrorResponse($e->getMessage(), 500);
}
?>
