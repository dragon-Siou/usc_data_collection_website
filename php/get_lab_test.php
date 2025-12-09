<?php
/**
 * 查詢檢驗檢查資料
 * get_lab_test.php
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
        case 'by_id':
            // 根據身分證查詢
            if (empty($_GET['id_number'])) {
                sendErrorResponse('請提供身分證字號');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    lt.*,
                    pi.name,
                    pi.phone,
                    pi.email,
                    d.name as doctor_name,
                    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age
                FROM lab_test lt
                INNER JOIN personal_info pi ON lt.person_id = pi.person_id
                LEFT JOIN doctors d ON lt.doctor_id = d.id_number
                WHERE lt.id_number = ?
                ORDER BY lt.created_at DESC
            ");
            $stmt->execute([$_GET['id_number']]);
            $data = $stmt->fetchAll();
            
            if (empty($data)) {
                sendErrorResponse('查無資料', 404);
            }
            
            // 對每筆檢驗資料，取得相關的單項、尿液、血液檢查
            foreach ($data as &$test) {
                $test_id = $test['test_id'];
                
                // 取得單項檢驗
                $stmt = $pdo->prepare("
                    SELECT * FROM lab_test_single_items 
                    WHERE test_id = ?
                    ORDER BY item_id
                ");
                $stmt->execute([$test_id]);
                $test['single_items'] = $stmt->fetchAll();
                
                // 取得尿液檢查
                $stmt = $pdo->prepare("
                    SELECT * FROM lab_test_urine 
                    WHERE test_id = ?
                ");
                $stmt->execute([$test_id]);
                $test['urine_test'] = $stmt->fetch();
                
                // 取得血液檢查
                $stmt = $pdo->prepare("
                    SELECT * FROM lab_test_blood 
                    WHERE test_id = ?
                ");
                $stmt->execute([$test_id]);
                $test['blood_test'] = $stmt->fetch();
            }
            
            sendSuccessResponse($data, '查詢成功');
            break;
            
        case 'by_test_id':
            // 根據檢驗ID查詢完整資料
            if (empty($_GET['test_id'])) {
                sendErrorResponse('請提供檢驗ID');
            }
            
            $test_id = intval($_GET['test_id']);
            
            // 取得主表資料
            $stmt = $pdo->prepare("
                SELECT 
                    lt.*,
                    pi.name,
                    pi.phone,
                    pi.email,
                    pi.gender,
                    d.name as doctor_name,
                    d.specialty as doctor_specialty,
                    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age
                FROM lab_test lt
                INNER JOIN personal_info pi ON lt.person_id = pi.person_id
                LEFT JOIN doctors d ON lt.doctor_id = d.id_number
                WHERE lt.test_id = ?
            ");
            $stmt->execute([$test_id]);
            $data = $stmt->fetch();
            
            if (!$data) {
                sendErrorResponse('查無資料', 404);
            }
            
            // 取得單項檢驗
            $stmt = $pdo->prepare("
                SELECT * FROM lab_test_single_items 
                WHERE test_id = ?
                ORDER BY item_id
            ");
            $stmt->execute([$test_id]);
            $data['single_items'] = $stmt->fetchAll();
            
            // 取得尿液檢查
            $stmt = $pdo->prepare("
                SELECT * FROM lab_test_urine 
                WHERE test_id = ?
            ");
            $stmt->execute([$test_id]);
            $data['urine_test'] = $stmt->fetch();
            
            // 取得血液檢查
            $stmt = $pdo->prepare("
                SELECT * FROM lab_test_blood 
                WHERE test_id = ?
            ");
            $stmt->execute([$test_id]);
            $data['blood_test'] = $stmt->fetch();
            
            sendSuccessResponse($data, '查詢成功');
            break;
            
        case 'stats':
            // 統計資料
            $stmt = $pdo->query("
                SELECT 
                    COUNT(DISTINCT lt.test_id) as total_tests,
                    COUNT(DISTINCT lt.person_id) as total_persons,
                    COUNT(lts.item_id) as total_single_items,
                    COUNT(DISTINCT ltu.test_id) as urine_test_count,
                    COUNT(DISTINCT ltb.test_id) as blood_test_count,
                    COUNT(DISTINCT lt.doctor_id) as doctor_count
                FROM lab_test lt
                LEFT JOIN lab_test_single_items lts ON lt.test_id = lts.test_id
                LEFT JOIN lab_test_urine ltu ON lt.test_id = ltu.test_id
                LEFT JOIN lab_test_blood ltb ON lt.test_id = ltb.test_id
            ");
            $data = $stmt->fetch();
            
            sendSuccessResponse($data, '統計資料查詢成功');
            break;
            
        case 'by_doctor':
            // 根據醫師查詢
            if (empty($_GET['doctor_id'])) {
                sendErrorResponse('請提供醫師身分證');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    lt.test_id,
                    lt.id_number,
                    pi.name,
                    lt.card_date,
                    lt.medical_serial,
                    lt.created_at,
                    d.name as doctor_name,
                    COUNT(lts.item_id) as item_count
                FROM lab_test lt
                INNER JOIN personal_info pi ON lt.person_id = pi.person_id
                LEFT JOIN doctors d ON lt.doctor_id = d.id_number
                LEFT JOIN lab_test_single_items lts ON lt.test_id = lts.test_id
                WHERE lt.doctor_id = ?
                GROUP BY lt.test_id
                ORDER BY lt.created_at DESC
            ");
            $stmt->execute([$_GET['doctor_id']]);
            $data = $stmt->fetchAll();
            
            sendSuccessResponse($data, '查詢成功');
            break;
            
        case 'recent':
            // 查詢最近的記錄
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $limit = min($limit, 100); // 最多100筆
            
            $stmt = $pdo->prepare("
                SELECT 
                    lt.test_id,
                    lt.id_number,
                    pi.name,
                    lt.card_date,
                    lt.medical_serial,
                    d.name as doctor_name,
                    lt.created_at,
                    COUNT(lts.item_id) as item_count
                FROM lab_test lt
                INNER JOIN personal_info pi ON lt.person_id = pi.person_id
                LEFT JOIN doctors d ON lt.doctor_id = d.id_number
                LEFT JOIN lab_test_single_items lts ON lt.test_id = lts.test_id
                GROUP BY lt.test_id
                ORDER BY lt.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $data = $stmt->fetchAll();
            
            sendSuccessResponse($data, '查詢成功');
            break;
            
        case 'all':
        default:
            // 查詢所有記錄（分頁）
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $stmt = $pdo->prepare("
                SELECT 
                    lt.test_id,
                    lt.id_number,
                    pi.name,
                    pi.gender,
                    lt.birth_date,
                    TIMESTAMPDIFF(YEAR, lt.birth_date, CURDATE()) as age,
                    lt.card_date,
                    lt.medical_serial,
                    d.name as doctor_name,
                    lt.created_at,
                    COUNT(lts.item_id) as item_count
                FROM lab_test lt
                INNER JOIN personal_info pi ON lt.person_id = pi.person_id
                LEFT JOIN doctors d ON lt.doctor_id = d.id_number
                LEFT JOIN lab_test_single_items lts ON lt.test_id = lts.test_id
                GROUP BY lt.test_id
                ORDER BY lt.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $data = $stmt->fetchAll();
            
            // 取得總筆數
            $countStmt = $pdo->query("
                SELECT COUNT(*) as total 
                FROM lab_test
            ");
            $total = $countStmt->fetch()['total'];
            
            sendSuccessResponse([
                'items' => $data,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ], '查詢成功');
            break;
    }
    
} catch (PDOException $e) {
    error_log("Lab Test Query Error: " . $e->getMessage());
    sendErrorResponse('查詢失敗', 500);
} catch (Exception $e) {
    sendErrorResponse($e->getMessage(), 500);
}
?>
