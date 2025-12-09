<?php
/**
 * 儲存血壓資料
 * save_blood_pressure.php
 */

require_once 'config.php';

// 只接受 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('只接受 POST 請求', 405);
}

try {
    // 取得 JSON 資料
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        sendErrorResponse('無效的 JSON 資料');
    }
    
    // 驗證必填欄位
    $requiredFields = [
        'idNumber', 'birthDate', 'cardDate', 'visitNumber', 
        'systolicBP', 'diastolicBP'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field]) && $data[$field] !== '0' && $data[$field] !== 0) {
            sendErrorResponse("缺少必填欄位: {$field}");
        }
    }
    
    // 驗證身分證格式（10碼）
    if (strlen($data['idNumber']) !== 10) {
        sendErrorResponse('身分證字號必須為10碼');
    }
    
    // 驗證就醫序號格式（4碼數字）
    if (strlen($data['visitNumber']) !== 4 || !ctype_digit($data['visitNumber'])) {
        sendErrorResponse('就醫序號必須為4碼數字');
    }
    
    // 驗證血壓數值
    if ($data['systolicBP'] <= 0 || $data['systolicBP'] > 300) {
        sendErrorResponse('收縮壓數值異常（1-300）');
    }
    
    if ($data['diastolicBP'] <= 0 || $data['diastolicBP'] > 200) {
        sendErrorResponse('舒張壓數值異常（1-200）');
    }
    
    if ($data['systolicBP'] <= $data['diastolicBP']) {
        sendErrorResponse('收縮壓必須大於舒張壓');
    }
    
    // 取得資料庫連線
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // 1. 檢查個人資料是否存在
    $stmt = $pdo->prepare("
        SELECT person_id, gender FROM personal_info WHERE id_number = ?
    ");
    $stmt->execute([$data['idNumber']]);
    $person = $stmt->fetch();
    
    if ($person) {
        $person_id = $person['person_id'];
        
        // 更新生日（如果不同）
        $stmt = $pdo->prepare("
            UPDATE personal_info 
            SET birth_date = ?, updated_at = CURRENT_TIMESTAMP
            WHERE person_id = ? AND birth_date != ?
        ");
        $stmt->execute([
            $data['birthDate'],
            $person_id,
            $data['birthDate']
        ]);
    } else {
        // 如果個人資料不存在，建立新記錄（姓名為 NULL，性別暫時設為男）
        // 後續填寫其他表單時會補齊完整資料
        $stmt = $pdo->prepare("
            INSERT INTO personal_info (id_number, birth_date, gender, name)
            VALUES (?, ?, '男', NULL)
        ");
        $stmt->execute([
            $data['idNumber'],
            $data['birthDate']
        ]);
        $person_id = $pdo->lastInsertId();
    }
    
    // 2. 新增血壓記錄
    $stmt = $pdo->prepare("
        INSERT INTO blood_pressure (
            person_id, id_number, birth_date, card_date, 
            visit_number, systolic_bp, diastolic_bp, ip_address
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $person_id,
        $data['idNumber'],
        $data['birthDate'],
        $data['cardDate'],
        $data['visitNumber'],
        $data['systolicBP'],
        $data['diastolicBP'],
        getClientIP()
    ]);
    
    $bp_id = $pdo->lastInsertId();
    
    // 提交交易
    $pdo->commit();
    
    // 判斷血壓狀態
    $bp_status = '';
    if ($data['systolicBP'] >= 180 || $data['diastolicBP'] >= 120) {
        $bp_status = '高血壓危象';
    } elseif ($data['systolicBP'] >= 140 || $data['diastolicBP'] >= 90) {
        $bp_status = '高血壓';
    } elseif ($data['systolicBP'] >= 120 || $data['diastolicBP'] >= 80) {
        $bp_status = '血壓偏高';
    } elseif ($data['systolicBP'] >= 90 && $data['diastolicBP'] >= 60) {
        $bp_status = '正常';
    } else {
        $bp_status = '血壓偏低';
    }
    
    // 回傳成功訊息
    sendSuccessResponse([
        'person_id' => $person_id,
        'bp_id' => $bp_id,
        'bp_status' => $bp_status
    ], '血壓資料上傳成功');
    
} catch (PDOException $e) {
    // 回滾交易
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 記錄錯誤
    error_log("Blood Pressure Save Error: " . $e->getMessage());
    sendErrorResponse('資料儲存失敗：' . $e->getMessage(), 500);
    
} catch (Exception $e) {
    // 回滾交易
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    sendErrorResponse($e->getMessage(), 500);
}
?>
