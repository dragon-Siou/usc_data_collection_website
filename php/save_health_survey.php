<?php
/**
 * 儲存健康調查資料
 * save_health_survey.php
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
        'idNumber', 'name', 'birthDate', 'gender',
        'employment', 'caregiver', 'city', 'district', 'familyLifeCycle',
        'height', 'weight', 'systolicBP', 'diastolicBP', 'waist', 'pulse'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field]) && $data[$field] !== '0') {
            sendErrorResponse("缺少必填欄位: {$field}");
        }
    }
    
    // 取得資料庫連線
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // 1. 處理個人基本資料（檢查是否已存在）
    $stmt = $pdo->prepare("
        SELECT person_id FROM personal_info WHERE id_number = ?
    ");
    $stmt->execute([$data['idNumber']]);
    $person = $stmt->fetch();
    
    if ($person) {
        // 更新現有資料
        $person_id = $person['person_id'];
        $stmt = $pdo->prepare("
            UPDATE personal_info 
            SET name = ?, birth_date = ?, gender = ?, updated_at = CURRENT_TIMESTAMP
            WHERE person_id = ?
        ");
        $stmt->execute([
            $data['name'],
            $data['birthDate'],
            $data['gender'],
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
            $data['gender']
        ]);
        $person_id = $pdo->lastInsertId();
    }
    
    // 2. 處理慢性病史（轉換為 JSON）
    $chronic_diseases = !empty($data['chronicDiseaseList']) ? 
        json_encode($data['chronicDiseaseList'], JSON_UNESCAPED_UNICODE) : null;
    
    // 3. 處理藥物清單（轉換為 JSON）
    $medications = !empty($data['medicationList']) ? 
        json_encode($data['medicationList'], JSON_UNESCAPED_UNICODE) : null;
    
    // 4. 檢查是否已有健康調查資料（一對一關聯）
    $stmt = $pdo->prepare("
        SELECT survey_id FROM health_survey WHERE person_id = ?
    ");
    $stmt->execute([$person_id]);
    $existingSurvey = $stmt->fetch();
    
    if ($existingSurvey) {
        // 更新現有健康調查
        $stmt = $pdo->prepare("
            UPDATE health_survey SET
                employment = ?,
                employment_other = ?,
                caregiver = ?,
                caregiver_other = ?,
                city = ?,
                district = ?,
                family_life_cycle = ?,
                family_life_cycle_other = ?,
                chronic_diseases = ?,
                chronic_disease_other = ?,
                medications = ?,
                medication_other = ?,
                food_allergy = ?,
                drug_allergy = ?,
                smoking = ?,
                drinking = ?,
                betel_nut = ?,
                height = ?,
                weight = ?,
                systolic_bp = ?,
                diastolic_bp = ?,
                waist = ?,
                pulse = ?,
                ip_address = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE person_id = ?
        ");
        
        $stmt->execute([
            $data['employment'],
            $data['employmentOther'] ?? null,
            $data['caregiver'],
            $data['caregiverOther'] ?? null,
            $data['city'],
            $data['district'],
            $data['familyLifeCycle'],
            $data['familyLifeCycleOther'] ?? null,
            $chronic_diseases,
            $data['chronicDiseaseOther'] ?? null,
            $medications,
            $data['medicationOther'] ?? null,
            $data['foodAllergy'] ?? null,
            $data['drugAllergy'] ?? null,
            $data['smoking'] ?? null,
            $data['drinking'] ?? null,
            $data['betelNut'] ?? null,
            $data['height'],
            $data['weight'],
            $data['systolicBP'],
            $data['diastolicBP'],
            $data['waist'],
            $data['pulse'],
            getClientIP(),
            $person_id
        ]);
        
        $survey_id = $existingSurvey['survey_id'];
        $message = '健康調查資料更新成功';
        
    } else {
        // 新增健康調查
        $stmt = $pdo->prepare("
            INSERT INTO health_survey (
                user_id, person_id, employment, employment_other, caregiver, caregiver_other,
                city, district, family_life_cycle, family_life_cycle_other,
                chronic_diseases, chronic_disease_other, medications, medication_other,
                food_allergy, drug_allergy, smoking, drinking, betel_nut,
                height, weight, systolic_bp, diastolic_bp, waist, pulse, ip_address
            ) VALUES (
                NULL, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $person_id,
            $data['employment'],
            $data['employmentOther'] ?? null,
            $data['caregiver'],
            $data['caregiverOther'] ?? null,
            $data['city'],
            $data['district'],
            $data['familyLifeCycle'],
            $data['familyLifeCycleOther'] ?? null,
            $chronic_diseases,
            $data['chronicDiseaseOther'] ?? null,
            $medications,
            $data['medicationOther'] ?? null,
            $data['foodAllergy'] ?? null,
            $data['drugAllergy'] ?? null,
            $data['smoking'] ?? null,
            $data['drinking'] ?? null,
            $data['betelNut'] ?? null,
            $data['height'],
            $data['weight'],
            $data['systolicBP'],
            $data['diastolicBP'],
            $data['waist'],
            $data['pulse'],
            getClientIP()
        ]);
        
        $survey_id = $pdo->lastInsertId();
        $message = '健康調查資料新增成功';
    }
    
    // 提交交易
    $pdo->commit();
    
    // 回傳成功訊息
    sendSuccessResponse([
        'person_id' => $person_id,
        'survey_id' => $survey_id
    ], $message);
    
} catch (PDOException $e) {
    // 回滾交易
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 記錄錯誤
    error_log("Health Survey Save Error: " . $e->getMessage());
    sendErrorResponse('資料儲存失敗：' . $e->getMessage(), 500);
    
} catch (Exception $e) {
    // 回滾交易
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    sendErrorResponse($e->getMessage(), 500);
}
?>