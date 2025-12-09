<?php
/**
 * 儲存代謝防治資料
 * save_metabolic_prevention.php
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
    
    // 驗證必填欄位 - 第一頁：基本資料
    $requiredFields = [
        'idNumber', 'birthDate', 'name', 'gender', 'caseDate'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field]) && $data[$field] !== '0' && $data[$field] !== 0) {
            sendErrorResponse("缺少必填欄位: {$field}");
        }
    }
    
    // 驗證必填欄位 - 第二頁：危險因子
    if (empty($data['smoking'])) {
        sendErrorResponse('請選擇抽菸習慣');
    }
    if (empty($data['betelNut'])) {
        sendErrorResponse('請選擇嚼檳榔習慣');
    }
    if (empty($data['exercise'])) {
        sendErrorResponse('請選擇運動習慣');
    }
    
    // 驗證必填欄位 - 第三頁：檢查資料
    $examRequiredFields = [
        'checkDate', 'height', 'weight', 'waist', 
        'systolicBP', 'diastolicBP', 'bpSource',
        'bpMedicine', 'sugarMedicine', 'lipidMedicine',
        'fastingGlucose', 'triglyceride', 'hdl', 'ldl', 
        'hba1c', 'totalCholesterol'
    ];
    
    foreach ($examRequiredFields as $field) {
        if (empty($data[$field]) && $data[$field] !== '0' && $data[$field] !== 0) {
            sendErrorResponse("缺少必填欄位: {$field}");
        }
    }
    
    // 驗證身分證格式（10碼）
    if (strlen($data['idNumber']) !== 10) {
        sendErrorResponse('身分證字號必須為10碼');
    }
    
    // 驗證性別格式
    if (!in_array($data['gender'], ['0', '1'])) {
        sendErrorResponse('性別格式錯誤');
    }
    
    // 驗證數值範圍
    if ($data['height'] <= 0 || $data['height'] > 300) {
        sendErrorResponse('身高數值異常（1-300cm）');
    }
    
    if ($data['weight'] <= 0 || $data['weight'] > 500) {
        sendErrorResponse('體重數值異常（1-500kg）');
    }
    
    if ($data['systolicBP'] <= 0 || $data['systolicBP'] > 300) {
        sendErrorResponse('收縮壓數值異常（1-300mmHg）');
    }
    
    if ($data['diastolicBP'] <= 0 || $data['diastolicBP'] > 200) {
        sendErrorResponse('舒張壓數值異常（1-200mmHg）');
    }
    
    if ($data['systolicBP'] <= $data['diastolicBP']) {
        sendErrorResponse('收縮壓必須大於舒張壓');
    }
    
    // 取得資料庫連線
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // 1. 檢查個人資料是否存在
    $stmt = $pdo->prepare("
        SELECT person_id FROM personal_info WHERE id_number = ?
    ");
    $stmt->execute([$data['idNumber']]);
    $person = $stmt->fetch();
    
    // 轉換性別格式 (0->男, 1->女)
    $gender = ($data['gender'] === '0') ? '男' : '女';
    
    if ($person) {
        $person_id = $person['person_id'];
        
        // 更新個人資料
        $stmt = $pdo->prepare("
            UPDATE personal_info 
            SET name = ?, birth_date = ?, gender = ?, updated_at = CURRENT_TIMESTAMP
            WHERE person_id = ?
        ");
        $stmt->execute([
            $data['name'],
            $data['birthDate'],
            $gender,
            $person_id
        ]);
    } else {
        // 新增個人資料
        $stmt = $pdo->prepare("
            INSERT INTO personal_info (id_number, name, birth_date, gender)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['idNumber'],
            $data['name'],
            $data['birthDate'],
            $gender
        ]);
        $person_id = $pdo->lastInsertId();
    }
    
    // 2. 處理伴隨疾病（轉換為 JSON）
    $diseases = !empty($data['diseases']) ? 
        json_encode($data['diseases'], JSON_UNESCAPED_UNICODE) : null;
    
    // 3. 新增代謝防治資料
    $stmt = $pdo->prepare("
        INSERT INTO metabolic_prevention (
            person_id, id_number, birth_date, name, gender, collection_date,
            risk_smoking, risk_betel_nut, risk_exercise,
            accompanying_diseases, accompanying_diseases_other,
            examination_date, height, weight, waist, systolic_bp, diastolic_bp,
            bp_source, antihypertensive_drug, hypoglycemic_drug, lipid_lowering_drug,
            fasting_glucose, triglyceride, hdl_cholesterol, ldl_cholesterol,
            hba1c, total_cholesterol, ip_address
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $person_id,
        $data['idNumber'],
        $data['birthDate'],
        $data['name'],
        $data['gender'],
        $data['caseDate'],
        // 危險因子
        $data['smoking'],
        $data['betelNut'],
        $data['exercise'],
        $diseases,
        $data['diseaseOther'] ?? null,
        // 檢查資料
        $data['checkDate'],
        $data['height'],
        $data['weight'],
        $data['waist'],
        $data['systolicBP'],
        $data['diastolicBP'],
        $data['bpSource'],
        $data['bpMedicine'],
        $data['sugarMedicine'],
        $data['lipidMedicine'],
        $data['fastingGlucose'],
        $data['triglyceride'],
        $data['hdl'],
        $data['ldl'],
        $data['hba1c'],
        $data['totalCholesterol'],
        getClientIP()
    ]);
    
    $metabolic_id = $pdo->lastInsertId();
    
    // 提交交易
    $pdo->commit();
    
    // 計算 BMI
    $bmi = round($data['weight'] / pow($data['height']/100, 2), 2);
    
    // 判斷 BMI 狀態
    $bmi_status = '';
    if ($bmi < 18.5) {
        $bmi_status = '體重過輕';
    } elseif ($bmi < 24) {
        $bmi_status = '正常範圍';
    } elseif ($bmi < 27) {
        $bmi_status = '過重';
    } elseif ($bmi < 30) {
        $bmi_status = '輕度肥胖';
    } elseif ($bmi < 35) {
        $bmi_status = '中度肥胖';
    } else {
        $bmi_status = '重度肥胖';
    }
    
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
        'metabolic_id' => $metabolic_id,
        'bmi' => $bmi,
        'bmi_status' => $bmi_status,
        'bp_status' => $bp_status
    ], '代謝防治資料儲存成功');
    
} catch (PDOException $e) {
    // 回滾交易
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 記錄錯誤
    error_log("Metabolic Prevention Save Error: " . $e->getMessage());
    sendErrorResponse('資料儲存失敗：' . $e->getMessage(), 500);
    
} catch (Exception $e) {
    // 回滾交易
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    sendErrorResponse($e->getMessage(), 500);
}
?>
