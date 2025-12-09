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
    
    // 2. 新增檢驗檢查主表資料
    $stmt = $pdo->prepare("
        INSERT INTO lab_test (
            person_id, id_number, birth_date, card_date, 
            medical_serial, doctor_id, ip_address
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $person_id,
        $data['idNumber'],
        $data['birthDate'],
        $data['cardDate'],
        $data['visitNumber'],
        $data['doctorId'],
        getClientIP()
    ]);
    
    $test_id = $pdo->lastInsertId();
    
    // 3. 處理單項檢驗資料
    $single_test_count = 0;
    if (!empty($data['singleTests']) && is_array($data['singleTests'])) {
        $stmt = $pdo->prepare("
            INSERT INTO lab_test_single_items (
                test_id, test_code, test_name, test_result
            ) VALUES (?, ?, ?, ?)
        ");
        
        foreach ($data['singleTests'] as $test) {
            // 跳過空的測試項目
            if (empty($test['testCode']) && empty($test['result'])) {
                continue;
            }
            
            // 判斷是否為自訂項目
            if ($test['testCode'] === 'other' && !empty($test['customCode'])) {
                $testCode = $test['customCode'];
                $testName = '其他檢驗：' . $testCode;
            } else {
                $testCode = $test['testCode'];
                // 根據代碼取得檢驗名稱
                $testName = getTestName($testCode);
            }
            
            $stmt->execute([
                $test_id,
                $testCode,
                $testName,
                $test['result'] ?? ''
            ]);
            
            $single_test_count++;
        }
    }
    
    // 4. 處理尿液檢查資料
    $urine_test_count = 0;
    if (!empty($data['urineTest'])) {
        $urineTest = $data['urineTest'];
        
        // 檢查是否有填寫任何尿液檢查項目
        $hasUrineData = false;
        foreach ($urineTest as $value) {
            if (!empty($value)) {
                $hasUrineData = true;
                break;
            }
        }
        
        if ($hasUrineData) {
            $stmt = $pdo->prepare("
                INSERT INTO lab_test_urine (
                    test_id, appearance, color, reaction_ph, glucose,
                    occult_blood, protein, urobilinogen, nitrite,
                    leukocyte, bilirubin, ketone_body, specific_gravity,
                    rbc, wbc, clarity
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $test_id,
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
                $urineTest['clarity'] ?? null
            ]);
            
            $urine_test_count = count(array_filter($urineTest, function($v) {
                return !empty($v);
            }));
        }
    }
    
    // 5. 處理血液檢查資料
    $blood_test_count = 0;
    if (!empty($data['bloodTest'])) {
        $bloodTest = $data['bloodTest'];
        
        // 檢查是否有填寫任何血液檢查項目
        $hasBloodData = false;
        foreach ($bloodTest as $value) {
            if (!empty($value)) {
                $hasBloodData = true;
                break;
            }
        }
        
        if ($hasBloodData) {
            $stmt = $pdo->prepare("
                INSERT INTO lab_test_blood (
                    test_id, wbc, rbc, hb, hct, mch, mcv, mchc
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $test_id,
                $bloodTest['wbc'] ?? null,
                $bloodTest['rbc'] ?? null,
                $bloodTest['hb'] ?? null,
                $bloodTest['hct'] ?? null,
                $bloodTest['mch'] ?? null,
                $bloodTest['mcv'] ?? null,
                $bloodTest['mchc'] ?? null
            ]);
            
            $blood_test_count = count(array_filter($bloodTest, function($v) {
                return !empty($v);
            }));
        }
    }
    
    // 提交交易
    $pdo->commit();
    
    // 計算總項目數
    $total_items = $single_test_count;
    if ($urine_test_count > 0) $total_items++;
    if ($blood_test_count > 0) $total_items++;
    
    // 回傳成功訊息
    sendSuccessResponse([
        'person_id' => $person_id,
        'test_id' => $test_id,
        'total_items' => $total_items,
        'single_test_count' => $single_test_count,
        'urine_test_items' => $urine_test_count,
        'blood_test_items' => $blood_test_count
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

/**
 * 根據檢驗代碼取得檢驗名稱
 */
function getTestName($code) {
    $testNames = [
        '09005C' => '指尖血血糖 One touch Glucose',
        '14065C' => 'A型流感',
        '14066C' => 'B型流感',
        '14058C' => 'RSV 呼吸融合細胞病毒',
        '06505C' => '驗孕',
        '14084C' => '新型冠狀病毒抗原檢測'
    ];
    
    return $testNames[$code] ?? $code;
}
?>
