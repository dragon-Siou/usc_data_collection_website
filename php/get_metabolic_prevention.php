<?php
/**
 * 查詢代謝防治資料
 * get_metabolic_prevention.php
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
                    mp.*,
                    pi.phone,
                    pi.email,
                    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age
                FROM metabolic_prevention mp
                INNER JOIN personal_info pi ON mp.person_id = pi.person_id
                WHERE mp.id_number = ?
                ORDER BY mp.created_at DESC
            ");
            $stmt->execute([$_GET['id_number']]);
            $data = $stmt->fetchAll();
            
            if (empty($data)) {
                sendErrorResponse('查無資料', 404);
            }
            
            // 解析 JSON 欄位
            foreach ($data as &$item) {
                if ($item['accompanying_diseases']) {
                    $item['accompanying_diseases'] = json_decode($item['accompanying_diseases']);
                }
            }
            
            sendSuccessResponse($data, '查詢成功');
            break;
            
        case 'stats':
            // 統計資料
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_records,
                    AVG(bmi) as avg_bmi,
                    AVG(systolic_bp) as avg_systolic_bp,
                    AVG(diastolic_bp) as avg_diastolic_bp,
                    AVG(fasting_glucose) as avg_fasting_glucose,
                    AVG(total_cholesterol) as avg_total_cholesterol,
                    SUM(CASE WHEN risk_smoking != '1' THEN 1 ELSE 0 END) as smoking_count,
                    SUM(CASE WHEN risk_betel_nut != '1' THEN 1 ELSE 0 END) as betel_nut_count,
                    SUM(CASE WHEN risk_exercise = '1' THEN 1 ELSE 0 END) as no_exercise_count,
                    SUM(CASE WHEN bmi >= 24 THEN 1 ELSE 0 END) as overweight_count,
                    SUM(CASE WHEN systolic_bp >= 140 OR diastolic_bp >= 90 THEN 1 ELSE 0 END) as hypertension_count
                FROM metabolic_prevention
            ");
            $data = $stmt->fetch();
            
            sendSuccessResponse($data, '統計資料查詢成功');
            break;
            
        case 'risk_analysis':
            // 風險分析（按危險因子分組）
            if (empty($_GET['risk_type'])) {
                sendErrorResponse('請提供危險因子類型（smoking, betel_nut, exercise）');
            }
            
            $riskType = $_GET['risk_type'];
            $validTypes = ['smoking', 'betel_nut', 'exercise'];
            
            if (!in_array($riskType, $validTypes)) {
                sendErrorResponse('無效的危險因子類型');
            }
            
            $column = 'risk_' . $riskType;
            
            $stmt = $pdo->prepare("
                SELECT 
                    {$column} as risk_level,
                    COUNT(*) as count,
                    AVG(bmi) as avg_bmi,
                    AVG(systolic_bp) as avg_systolic_bp,
                    AVG(diastolic_bp) as avg_diastolic_bp,
                    AVG(fasting_glucose) as avg_fasting_glucose
                FROM metabolic_prevention
                GROUP BY {$column}
                ORDER BY {$column}
            ");
            $stmt->execute();
            $data = $stmt->fetchAll();
            
            sendSuccessResponse($data, '風險分析查詢成功');
            break;
            
        case 'recent':
            // 查詢最近的記錄
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $limit = min($limit, 100); // 最多100筆
            
            $stmt = $pdo->prepare("
                SELECT 
                    mp.metabolic_id,
                    mp.id_number,
                    mp.name,
                    mp.gender,
                    mp.collection_date,
                    mp.bmi,
                    mp.systolic_bp,
                    mp.diastolic_bp,
                    mp.fasting_glucose,
                    mp.created_at,
                    TIMESTAMPDIFF(YEAR, mp.birth_date, CURDATE()) as age
                FROM metabolic_prevention mp
                ORDER BY mp.created_at DESC
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
                    mp.metabolic_id,
                    mp.id_number,
                    mp.name,
                    mp.gender,
                    mp.birth_date,
                    TIMESTAMPDIFF(YEAR, mp.birth_date, CURDATE()) as age,
                    mp.collection_date,
                    mp.examination_date,
                    mp.bmi,
                    mp.systolic_bp,
                    mp.diastolic_bp,
                    mp.risk_smoking,
                    mp.risk_betel_nut,
                    mp.risk_exercise,
                    mp.created_at
                FROM metabolic_prevention mp
                ORDER BY mp.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $data = $stmt->fetchAll();
            
            // 取得總筆數
            $countStmt = $pdo->query("
                SELECT COUNT(*) as total 
                FROM metabolic_prevention
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
    error_log("Metabolic Prevention Query Error: " . $e->getMessage());
    sendErrorResponse('查詢失敗', 500);
} catch (Exception $e) {
    sendErrorResponse($e->getMessage(), 500);
}
?>
