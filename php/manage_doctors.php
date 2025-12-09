<?php
/**
 * 醫師資料管理
 * manage_doctors.php
 */

require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 查詢醫師清單
        $status = $_GET['status'] ?? 'active';
        
        if ($status === 'all') {
            $stmt = $pdo->query("
                SELECT 
                    doctor_id,
                    id_number,
                    name,
                    specialty,
                    phone,
                    email,
                    status,
                    created_at,
                    updated_at
                FROM doctors
                ORDER BY name
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT 
                    doctor_id,
                    id_number,
                    name,
                    specialty,
                    phone,
                    email,
                    status,
                    created_at,
                    updated_at
                FROM doctors
                WHERE status = ?
                ORDER BY name
            ");
            $stmt->execute([$status]);
        }
        
        $data = $stmt->fetchAll();
        sendSuccessResponse($data, '查詢成功');
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 新增醫師
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            sendErrorResponse('無效的 JSON 資料');
        }
        
        // 驗證必填欄位
        if (empty($data['idNumber']) || empty($data['name'])) {
            sendErrorResponse('請填寫身分證字號和姓名');
        }
        
        // 驗證身分證格式
        if (strlen($data['idNumber']) !== 10) {
            sendErrorResponse('身分證字號必須為10碼');
        }
        
        // 檢查是否已存在
        $stmt = $pdo->prepare("
            SELECT doctor_id FROM doctors WHERE id_number = ?
        ");
        $stmt->execute([$data['idNumber']]);
        if ($stmt->fetch()) {
            sendErrorResponse('此身分證字號已存在');
        }
        
        // 新增醫師
        $stmt = $pdo->prepare("
            INSERT INTO doctors (
                id_number, name, specialty, phone, email, status
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['idNumber'],
            $data['name'],
            $data['specialty'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['status'] ?? 'active'
        ]);
        
        $doctor_id = $pdo->lastInsertId();
        
        sendSuccessResponse([
            'doctor_id' => $doctor_id
        ], '醫師資料新增成功');
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // 更新醫師資料
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data || empty($data['doctorId'])) {
            sendErrorResponse('無效的資料');
        }
        
        $stmt = $pdo->prepare("
            UPDATE doctors SET
                name = ?,
                specialty = ?,
                phone = ?,
                email = ?,
                status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE doctor_id = ?
        ");
        
        $stmt->execute([
            $data['name'],
            $data['specialty'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['status'] ?? 'active',
            $data['doctorId']
        ]);
        
        sendSuccessResponse([], '醫師資料更新成功');
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // 刪除醫師（軟刪除，改為inactive）
        $doctor_id = $_GET['doctor_id'] ?? null;
        
        if (!$doctor_id) {
            sendErrorResponse('請提供醫師ID');
        }
        
        $stmt = $pdo->prepare("
            UPDATE doctors SET
                status = 'inactive',
                updated_at = CURRENT_TIMESTAMP
            WHERE doctor_id = ?
        ");
        
        $stmt->execute([$doctor_id]);
        
        sendSuccessResponse([], '醫師資料已停用');
        
    } else {
        sendErrorResponse('不支援的請求方法', 405);
    }
    
} catch (PDOException $e) {
    error_log("Doctor Management Error: " . $e->getMessage());
    sendErrorResponse('操作失敗：' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendErrorResponse($e->getMessage(), 500);
}
?>
