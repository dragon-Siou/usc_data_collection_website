<?php
/**
 * Excel 匯出 API
 * export_excel.php
 * 
 * 需要安裝 PhpSpreadsheet:
 * composer require phpoffice/phpspreadsheet
 */

require_once 'config.php';

// 引入 PhpSpreadsheet
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// 只接受 GET 請求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('只接受 GET 請求', 405);
}

try {
    $pdo = getDBConnection();
    
    // 取得參數
    $type = $_GET['type'] ?? '';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    
    // 驗證資料類型
    $valid_types = ['health_survey', 'blood_pressure', 'lab_test', 'metabolic_prevention'];
    if (!in_array($type, $valid_types)) {
        sendErrorResponse('無效的資料類型');
    }
    
    // 根據類型匯出不同的資料
    switch ($type) {
        case 'health_survey':
            exportHealthSurvey($pdo, $start_date, $end_date);
            break;
        case 'blood_pressure':
            exportBloodPressure($pdo, $start_date, $end_date);
            break;
        case 'lab_test':
            exportLabTest($pdo, $start_date, $end_date);
            break;
        case 'metabolic_prevention':
            exportMetabolicPrevention($pdo, $start_date, $end_date);
            break;
    }
    
} catch (Exception $e) {
    error_log("Export Error: " . $e->getMessage());
    sendErrorResponse('匯出失敗：' . $e->getMessage(), 500);
}

/**
 * 匯出健康調查資料
 */
function exportHealthSurvey($pdo, $start_date, $end_date) {
    // 建立 SQL 查詢
    $sql = "
        SELECT 
            pi.id_number as '身分證字號',
            pi.name as '姓名',
            pi.gender as '性別',
            pi.birth_date as '出生日期',
            TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as '年齡',
            hs.employment as '就業別',
            hs.caregiver as '主要照顧者',
            hs.city as '居住縣市',
            hs.district as '居住地區',
            hs.family_life_cycle as '家庭生命週期',
            hs.chronic_diseases as '慢性病史',
            hs.medications as '長期藥物',
            hs.smoking as '抽菸',
            hs.drinking as '喝酒',
            hs.betel_nut as '嚼檳榔',
            hs.height as '身高(cm)',
            hs.weight as '體重(kg)',
            ROUND(hs.weight / POWER(hs.height/100, 2), 2) as 'BMI',
            hs.systolic_bp as '收縮壓(mmHg)',
            hs.diastolic_bp as '舒張壓(mmHg)',
            hs.waist as '腰圍(cm)',
            hs.pulse as '脈搏(次/分)',
            hs.submitted_at as '提交時間'
        FROM health_survey hs
        INNER JOIN personal_info pi ON hs.person_id = pi.person_id
        WHERE 1=1
    ";
    
    // 加入日期篩選
    if ($start_date) {
        $sql .= " AND DATE(hs.submitted_at) >= :start_date";
    }
    if ($end_date) {
        $sql .= " AND DATE(hs.submitted_at) <= :end_date";
    }
    
    $sql .= " ORDER BY hs.submitted_at DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($start_date) $stmt->bindValue(':start_date', $start_date);
    if ($end_date) $stmt->bindValue(':end_date', $end_date);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 處理 JSON 欄位
    foreach ($data as &$row) {
        if ($row['慢性病史']) {
            $diseases = json_decode($row['慢性病史'], true);
            $row['慢性病史'] = is_array($diseases) ? implode('、', $diseases) : $row['慢性病史'];
        }
        if ($row['長期藥物']) {
            $medications = json_decode($row['長期藥物'], true);
            $row['長期藥物'] = is_array($medications) ? implode('、', $medications) : $row['長期藥物'];
        }
    }
    
    // 建立 Excel
    $filename = '健康調查_' . date('Ymd_His') . '.xlsx';
    createExcelFile($data, $filename, '健康調查資料');
}

/**
 * 匯出血壓資料
 */
function exportBloodPressure($pdo, $start_date, $end_date) {
    $sql = "
        SELECT 
            bp.id_number as '身分證字號',
            pi.name as '姓名',
            pi.gender as '性別',
            bp.birth_date as '出生日期',
            TIMESTAMPDIFF(YEAR, bp.birth_date, CURDATE()) as '年齡',
            bp.card_date as '過卡日期',
            bp.visit_number as '就醫序號',
            bp.systolic_bp as '收縮壓(mmHg)',
            bp.diastolic_bp as '舒張壓(mmHg)',
            CASE 
                WHEN bp.systolic_bp >= 180 OR bp.diastolic_bp >= 120 THEN '高血壓危象'
                WHEN bp.systolic_bp >= 140 OR bp.diastolic_bp >= 90 THEN '高血壓'
                WHEN bp.systolic_bp >= 120 OR bp.diastolic_bp >= 80 THEN '血壓偏高'
                WHEN bp.systolic_bp >= 90 AND bp.diastolic_bp >= 60 THEN '正常'
                ELSE '血壓偏低'
            END as '血壓狀態',
            bp.submitted_at as '上傳時間'
        FROM blood_pressure bp
        LEFT JOIN personal_info pi ON bp.person_id = pi.person_id
        WHERE 1=1
    ";
    
    if ($start_date) {
        $sql .= " AND DATE(bp.submitted_at) >= :start_date";
    }
    if ($end_date) {
        $sql .= " AND DATE(bp.submitted_at) <= :end_date";
    }
    
    $sql .= " ORDER BY bp.submitted_at DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($start_date) $stmt->bindValue(':start_date', $start_date);
    if ($end_date) $stmt->bindValue(':end_date', $end_date);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = '血壓資料_' . date('Ymd_His') . '.xlsx';
    createExcelFile($data, $filename, '血壓資料');
}

/**
 * 匯出檢驗檢查資料
 */
function exportLabTest($pdo, $start_date, $end_date) {
    // 主表資料
    $sql = "
        SELECT 
            lt.test_id as '檢驗ID',
            lt.id_number as '身分證字號',
            pi.name as '姓名',
            pi.gender as '性別',
            lt.birth_date as '出生日期',
            TIMESTAMPDIFF(YEAR, lt.birth_date, CURDATE()) as '年齡',
            lt.card_date as '過卡日期',
            lt.medical_serial as '就醫序號',
            lt.doctor_id as '醫師身分證',
            d.name as '醫師姓名',
            lt.created_at as '建立時間'
        FROM lab_test lt
        LEFT JOIN personal_info pi ON lt.person_id = pi.person_id
        LEFT JOIN doctors d ON lt.doctor_id = d.id_number
        WHERE 1=1
    ";
    
    if ($start_date) {
        $sql .= " AND DATE(lt.created_at) >= :start_date";
    }
    if ($end_date) {
        $sql .= " AND DATE(lt.created_at) <= :end_date";
    }
    
    $sql .= " ORDER BY lt.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($start_date) $stmt->bindValue(':start_date', $start_date);
    if ($end_date) $stmt->bindValue(':end_date', $end_date);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 為每筆資料加入單項檢驗
    foreach ($data as &$row) {
        $test_id = $row['檢驗ID'];
        
        // 取得單項檢驗
        $stmt = $pdo->prepare("
            SELECT test_name, test_result 
            FROM lab_test_single_items 
            WHERE test_id = ? 
            ORDER BY item_id
        ");
        $stmt->execute([$test_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $itemList = [];
        foreach ($items as $item) {
            $itemList[] = $item['test_name'] . ': ' . $item['test_result'];
        }
        $row['單項檢驗'] = implode(' | ', $itemList);
    }
    
    $filename = '檢驗檢查_' . date('Ymd_His') . '.xlsx';
    createExcelFile($data, $filename, '檢驗檢查資料');
}

/**
 * 匯出代謝防治資料
 */
function exportMetabolicPrevention($pdo, $start_date, $end_date) {
    $sql = "
        SELECT 
            mp.id_number as '身分證字號',
            mp.name as '姓名',
            CASE WHEN mp.gender = '0' THEN '男' ELSE '女' END as '性別',
            mp.birth_date as '出生日期',
            TIMESTAMPDIFF(YEAR, mp.birth_date, CURDATE()) as '年齡',
            mp.collection_date as '收案日期',
            CASE 
                WHEN mp.risk_smoking = '1' THEN '無'
                WHEN mp.risk_smoking = '2' THEN '偶爾'
                WHEN mp.risk_smoking = '3' THEN '約10支以下'
                ELSE '10支以上'
            END as '抽菸',
            CASE 
                WHEN mp.risk_betel_nut = '1' THEN '無'
                WHEN mp.risk_betel_nut = '2' THEN '偶爾'
                ELSE '經常'
            END as '嚼檳榔',
            CASE 
                WHEN mp.risk_exercise = '1' THEN '無'
                WHEN mp.risk_exercise = '2' THEN '偶爾'
                ELSE '經常(150分/週)'
            END as '運動',
            mp.accompanying_diseases as '伴隨疾病',
            mp.examination_date as '檢查日期',
            mp.height as '身高(cm)',
            mp.weight as '體重(kg)',
            mp.bmi as 'BMI',
            mp.waist as '腰圍(cm)',
            mp.systolic_bp as '收縮壓(mmHg)',
            mp.diastolic_bp as '舒張壓(mmHg)',
            CASE WHEN mp.bp_source = '0' THEN '非診間' ELSE '診間' END as '血壓來源',
            CASE WHEN mp.antihypertensive_drug = '0' THEN '無' ELSE '有' END as '降血壓藥',
            CASE WHEN mp.hypoglycemic_drug = '0' THEN '無' ELSE '有' END as '降血糖藥',
            CASE WHEN mp.lipid_lowering_drug = '0' THEN '無' ELSE '有' END as '降血脂藥',
            mp.fasting_glucose as '飯前血糖(mg/dl)',
            mp.triglyceride as '三酸甘油脂(mg/dl)',
            mp.hdl_cholesterol as 'HDL(mg/dl)',
            mp.ldl_cholesterol as 'LDL(mg/dl)',
            mp.hba1c as '醣化血紅素(%)',
            mp.total_cholesterol as '總膽固醇(mg/dl)',
            mp.created_at as '建立時間'
        FROM metabolic_prevention mp
        WHERE 1=1
    ";
    
    if ($start_date) {
        $sql .= " AND DATE(mp.created_at) >= :start_date";
    }
    if ($end_date) {
        $sql .= " AND DATE(mp.created_at) <= :end_date";
    }
    
    $sql .= " ORDER BY mp.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($start_date) $stmt->bindValue(':start_date', $start_date);
    if ($end_date) $stmt->bindValue(':end_date', $end_date);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 處理 JSON 欄位
    foreach ($data as &$row) {
        if ($row['伴隨疾病']) {
            $diseases = json_decode($row['伴隨疾病'], true);
            if (is_array($diseases)) {
                $diseaseNames = [
                    '1' => '無',
                    '2' => '糖尿病',
                    '3' => '高血壓',
                    '4' => '心臟血管疾病',
                    '5' => '高血脂症',
                    '6' => '腎臟病',
                    '7' => '腦血管疾病'
                ];
                $result = [];
                foreach ($diseases as $code) {
                    $result[] = $diseaseNames[$code] ?? $code;
                }
                $row['伴隨疾病'] = implode('、', $result);
            }
        }
    }
    
    $filename = '代謝防治_' . date('Ymd_His') . '.xlsx';
    createExcelFile($data, $filename, '代謝防治資料');
}

/**
 * 建立 Excel 檔案
 */
function createExcelFile($data, $filename, $sheetTitle) {
    if (empty($data)) {
        throw new Exception('沒有資料可匯出');
    }
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($sheetTitle);
    
    // 取得欄位名稱
    $headers = array_keys($data[0]);
    
    // 設定標題列
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }
    
    // 標題列樣式
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    
    $lastCol = chr(64 + count($headers));
    $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);
    
    // 設定欄寬
    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setWidth(15);
    }
    
    // 填入資料
    $row = 2;
    foreach ($data as $record) {
        $col = 'A';
        foreach ($record as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }
    
    // 資料列樣式
    $dataStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC']
            ]
        ]
    ];
    
    $lastRow = $row - 1;
    $sheet->getStyle('A2:' . $lastCol . $lastRow)->applyFromArray($dataStyle);
    
    // 凍結首列
    $sheet->freezePane('A2');
    
    // 輸出檔案
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>
