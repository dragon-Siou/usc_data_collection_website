<?php
/**
 * 查詢健康調查資料
 * get_health_survey.php
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
                    pi.*,
                    hs.*,
                    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age,
                    ROUND(hs.weight / POWER(hs.height/100, 2), 2) as bmi
                FROM personal_info pi
                LEFT JOIN health_survey hs ON pi.person_id = hs.person_id
                WHERE pi.id_number = ?
            ");
            $stmt->execute([$_GET['id_number']]);
            $data = $stmt->fetch();
            
            if (!$data) {
                sendErrorResponse('查無資料', 404);
            }
            
            // 解析 JSON 欄位
            if ($data['chronic_diseases']) {
                $data['chronic_diseases'] = json_decode($data['chronic_diseases']);
            }
            if ($data['medications']) {
                $data['medications'] = json_decode($data['medications']);
            }
            
            sendSuccessResponse($data, '查詢成功');
            break;
            
        case 'stats':
            // 統計資料
            $stmt = $pdo->query("
                SELECT 
                    COUNT(DISTINCT pi.person_id) as total_persons,
                    COUNT(hs.survey_id) as total_surveys,
                    AVG(TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE())) as avg_age,
                    AVG(hs.height) as avg_height,
                    AVG(hs.weight) as avg_weight,
                    AVG(hs.weight / POWER(hs.height/100, 2)) as avg_bmi,
                    AVG(hs.systolic_bp) as avg_systolic_bp,
                    AVG(hs.diastolic_bp) as avg_diastolic_bp
                FROM personal_info pi
                LEFT JOIN health_survey hs ON pi.person_id = hs.person_id
            ");
            $data = $stmt->fetch();
            
            sendSuccessResponse($data, '統計資料查詢成功');
            break;
            
        case 'by_city':
            // 根據縣市查詢
            if (empty($_GET['city'])) {
                sendErrorResponse('請提供縣市');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    pi.name,
                    pi.id_number,
                    pi.gender,
                    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age,
                    hs.city,
                    hs.district,
                    hs.submitted_at,
                    ROUND(hs.weight / POWER(hs.height/100, 2), 2) as bmi
                FROM personal_info pi
                INNER JOIN health_survey hs ON pi.person_id = hs.person_id
                WHERE hs.city = ?
                ORDER BY hs.submitted_at DESC
            ");
            $stmt->execute([$_GET['city']]);
            $data = $stmt->fetchAll();
            
            sendSuccessResponse($data, '查詢成功');
            break;
            
        case 'all':
        default:
            // 查詢所有健康調查（含個人資料）
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $stmt = $pdo->prepare("
                SELECT 
                    pi.person_id,
                    pi.id_number,
                    pi.name,
                    pi.gender,
                    pi.birth_date,
                    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age,
                    hs.city,
                    hs.district,
                    hs.employment,
                    hs.height,
                    hs.weight,
                    ROUND(hs.weight / POWER(hs.height/100, 2), 2) as bmi,
                    hs.systolic_bp,
                    hs.diastolic_bp,
                    hs.submitted_at
                FROM personal_info pi
                INNER JOIN health_survey hs ON pi.person_id = hs.person_id
                ORDER BY hs.submitted_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $data = $stmt->fetchAll();
            
            // 取得總筆數
            $countStmt = $pdo->query("
                SELECT COUNT(*) as total 
                FROM health_survey
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
    error_log("Health Survey Query Error: " . $e->getMessage());
    sendErrorResponse('查詢失敗', 500);
} catch (Exception $e) {
    sendErrorResponse($e->getMessage(), 500);
}
?>