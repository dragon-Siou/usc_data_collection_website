<?php
/**
 * 儲存檢驗檢查資料
 * save_lab_test.php
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
        'idNumber', 'birthDate', 'cardDate', 'visitNumber', 'doctorId'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendErrorResponse("缺少必填欄位: {$field}");
        }
    }
    
    // 驗證身分證格式（10碼）
    if (strlen($data['idNumber']) !== 10) {
        sendErrorResponse('身分證字號必須為10碼');
    }
    
    // 驗證就醫序號格式（4碼）
    if (strlen($data['visitNumber']) !== 4) {
        sendErrorResponse('就醫序號必須為4碼');
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
    
    if ($person) {
        $person_id = $person['person_id'];
    } else {
        sendErrorResponse('找不到對應的個人資料，請先填寫健康調查表單');
    }
    
    // 2. 處理單項檢驗資料
    $single_tests = null;
    if (!empty($data['singleTests'])) {
        // 過濾掉空的測試項目
        $validTests = array_filter($data['singleTests'], function($test) {
            return !empty($test['testCode']) || !empty($test['result']);
        });
        
        if (!empty($validTests)) {
            $single_tests = json_encode(array_values($validTests), JSON_UNESCAPED_UNICODE);
        }
    }
    
    // 3. 提取尿液檢查資料
    $urineTest = $data['urineTest'] ?? [];
    
    // 4. 提取血液檢查資料
    $bloodTest = $data['bloodTest'] ?? [];
    
    // 5. 新增檢驗檢查記錄
    $stmt = $pdo->prepare("
        INSERT INTO lab_test (
            person_id, id_number, birth_date, card_date, 
            visit_number, doctor_id, single_tests,
            urine_appearance, urine_color, urine_reaction, urine_glucose,
            urine_occult_blood, urine_protein, urine_urobilinogen, urine_nitrite,
            urine_leukocyte, urine_bilirubin, urine_ketone_body, urine_specific_gravi,
            urine_rbc, urine_wbc, urine_clarity,
            blood_wbc, blood_rbc, blood_hb, blood_hct,
            blood_mch, blood_mcv, blood_mchc, ip_address
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $person_id,
        $data['idNumber'],
        $data['birthDate'],
        $data['cardDate'],
        $data['visitNumber'],
        $data['doctorId'],
        $single_tests,
        // 尿液檢查
        $urineTest['appearance'] ?? null,
        $urineTest['color'] ?? null,
        $urineTest['reaction'] ?? null,
        $urineTest['glucose'] ?? null,
        $urineTest['occultBlood'] ?? null,
        $urineTest['protein'] ?? null,
        $urineTest['urobilinogen'] ?? null,
        $urineTest['nitrite'] ?? null,
        $urineTest['leukocyte'] ?? null,
        $urineTest['bilirubin'] ?? null,
        $urineTest['ketoneBody'] ?? null,
        $urineTest['specificGravi'] ?? null,
        $urineTest['rbc'] ?? null,
        $urineTest['wbc'] ?? null,
        $urineTest['clarity'] ?? null,
        // 血液檢查
        $bloodTest['wbc'] ?? null,
        $bloodTest['rbc'] ?? null,
        $bloodTest['hb'] ?? null,
        $bloodTest['hct'] ?? null,
        $bloodTest['mch'] ?? null,
        $bloodTest['mcv'] ?? null,
        $bloodTest['mchc'] ?? null,
        getClientIP()
    ]);
    
    $test_id = $pdo->lastInsertId();
    
    // 提交交易
    $pdo->commit();
    
    // 統計填寫的項目數
    $itemCount = 0;
    if ($single_tests) {
        $itemCount += count(json_decode($single_tests, true));
    }
    
    // 計算尿液檢查填寫項目
    $urineCount = count(array_filter($urineTest, function($v) {
        return !empty($v);
    }));
    if ($urineCount > 0) $itemCount++;
    
    // 計算血液檢查填寫項目
    $bloodCount = count(array_filter($bloodTest, function($v) {
        return !empty($v);
    }));
    if ($bloodCount > 0) $itemCount++;
    
    // 回傳成功訊息
    sendSuccessResponse([
        'person_id' => $person_id,
        'test_id' => $test_id,
        'item_count' => $itemCount,
        'single_test_count' => $single_tests ? count(json_decode($single_tests, true)) : 0,
        'urine_test_items' => $urineCount,
        'blood_test_items' => $bloodCount
    ], '檢驗資料上傳成功');
    
} catch (PDOException $e) {
    // 回滾交易
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 記錄錯誤
    error_log("Lab Test Save Error: " . $e->getMessage());
    sendErrorResponse('資料儲存失敗：' . $e->getMessage(), 500);
    
} catch (Exception $e) {
    // 回滾交易
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    sendErrorResponse($e->getMessage(), 500);
}
?>