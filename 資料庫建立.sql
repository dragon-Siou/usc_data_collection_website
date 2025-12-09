-- ============================================
-- 資料收集系統 - MySQL 資料庫結構（更新版）
-- ============================================

-- 建立資料庫
CREATE DATABASE IF NOT EXISTS data_collection_system 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE data_collection_system;

-- ============================================
-- 1. 使用者資料表
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '使用者ID',
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '帳號',
    password VARCHAR(255) NOT NULL COMMENT '密碼（建議使用加密）',
    display_name VARCHAR(100) NOT NULL COMMENT '顯示名稱',
    email VARCHAR(100) COMMENT '電子郵件',
    phone VARCHAR(20) COMMENT '聯絡電話',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active' COMMENT '帳號狀態',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    last_login TIMESTAMP NULL COMMENT '最後登入時間',
    INDEX idx_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='使用者資料表';

-- 插入預設使用者
INSERT INTO users (username, password, display_name, email) VALUES 
('1234', '0000', 'XXX', 'admin@example.com');

-- ============================================
-- 2. 個人基本資料表（身分證、姓名、生日獨立）
-- ============================================
CREATE TABLE IF NOT EXISTS personal_info (
    person_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '個人ID',
    id_number VARCHAR(10) NOT NULL UNIQUE COMMENT '身分證字號',
    name VARCHAR(100) COMMENT '姓名',
    birth_date DATE NOT NULL COMMENT '出生日期',
    gender ENUM('男', '女') NOT NULL COMMENT '性別',
    phone VARCHAR(20) COMMENT '聯絡電話',
    email VARCHAR(100) COMMENT '電子郵件',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    INDEX idx_id_number (id_number),
    INDEX idx_name (name),
    INDEX idx_birth_date (birth_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='個人基本資料表';

-- ============================================
-- 3. 健康調查資料表（更新版 - 一對一關聯）
-- ============================================
CREATE TABLE IF NOT EXISTS health_survey (
    survey_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '調查ID',
    user_id INT COMMENT '填寫人ID（關聯users表）',
    person_id INT NOT NULL UNIQUE COMMENT '個人ID（關聯personal_info表，一對一）',
    
    -- 基本資料（第一頁）
    employment VARCHAR(50) NOT NULL COMMENT '就業別',
    employment_other VARCHAR(200) COMMENT '就業別-其他說明',
    caregiver VARCHAR(50) NOT NULL COMMENT '主要照顧者',
    caregiver_other VARCHAR(200) COMMENT '主要照顧者-其他說明',
    city VARCHAR(50) NOT NULL COMMENT '居住縣市',
    district VARCHAR(50) NOT NULL COMMENT '居住地區',
    family_life_cycle VARCHAR(100) NOT NULL COMMENT '家庭生命週期',
    family_life_cycle_other VARCHAR(200) COMMENT '家庭生命週期-其他說明',
    chronic_diseases JSON COMMENT '慢性病史（JSON格式儲存多選）',
    chronic_disease_other VARCHAR(500) COMMENT '慢性病史-其他說明',
    
    -- 醫療病史及菸酒檳習慣（第二頁）
    medications JSON COMMENT '長期藥物使用（JSON格式儲存多選）',
    medication_other VARCHAR(500) COMMENT '長期藥物-其他說明',
    food_allergy TEXT COMMENT '食物過敏史',
    drug_allergy TEXT COMMENT '藥物過敏史',
    smoking VARCHAR(100) COMMENT '抽菸習慣',
    drinking VARCHAR(100) COMMENT '喝酒習慣',
    betel_nut VARCHAR(100) COMMENT '嚼檳榔習慣',
    
    -- 身體檢查（第三頁）
    height DECIMAL(5,1) NOT NULL COMMENT '身高(cm)',
    weight DECIMAL(5,1) NOT NULL COMMENT '體重(kg)',
    systolic_bp INT NOT NULL COMMENT '血壓收縮壓(mmHg)',
    diastolic_bp INT NOT NULL COMMENT '血壓舒張壓(mmHg)',
    waist DECIMAL(5,1) NOT NULL COMMENT '腰圍(cm)',
    pulse INT NOT NULL COMMENT '脈搏(次/每分鐘)',
    
    -- 系統欄位
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '提交時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    ip_address VARCHAR(45) COMMENT 'IP位址',
    
    INDEX idx_user_id (user_id),
    INDEX idx_person_id (person_id),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_city (city),
    INDEX idx_district (district),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (person_id) REFERENCES personal_info(person_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='健康調查資料表（一對一關聯）';

-- ============================================
-- 4. 活動報名資料表（一對一關聯）
-- ============================================
CREATE TABLE IF NOT EXISTS activity_registration (
    registration_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '報名ID',
    user_id INT COMMENT '報名人ID（關聯users表）',
    person_id INT NOT NULL UNIQUE COMMENT '個人ID（關聯personal_info表，一對一）',
    city VARCHAR(50) NOT NULL COMMENT '居住縣市',
    district VARCHAR(50) NOT NULL COMMENT '居住地區',
    activity_name VARCHAR(200) NOT NULL COMMENT '活動名稱',
    participant_count INT DEFAULT 1 COMMENT '參加人數',
    special_needs TEXT COMMENT '特殊需求',
    emergency_contact VARCHAR(100) COMMENT '緊急聯絡人',
    emergency_phone VARCHAR(20) COMMENT '緊急聯絡電話',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '提交時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending' COMMENT '報名狀態',
    INDEX idx_user_id (user_id),
    INDEX idx_person_id (person_id),
    INDEX idx_activity_name (activity_name),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (person_id) REFERENCES personal_info(person_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='活動報名資料表（一對一關聯）';

-- ============================================
-- 5. 意見回饋資料表（一對一關聯）
-- ============================================
CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '回饋ID',
    user_id INT COMMENT '回饋人ID（關聯users表）',
    person_id INT NOT NULL UNIQUE COMMENT '個人ID（關聯personal_info表，一對一）',
    city VARCHAR(50) NOT NULL COMMENT '居住縣市',
    district VARCHAR(50) NOT NULL COMMENT '居住地區',
    feedback_type ENUM('建議', '抱怨', '讚美', '其他') NOT NULL COMMENT '回饋類型',
    subject VARCHAR(200) NOT NULL COMMENT '主旨',
    content TEXT NOT NULL COMMENT '內容',
    satisfaction_rating INT COMMENT '滿意度評分（1-5分）',
    contact_preference ENUM('電話', '簡訊', 'LINE', '電子郵件', '其他') COMMENT '偏好聯絡方式',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '提交時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    status ENUM('new', 'processing', 'resolved', 'closed') DEFAULT 'new' COMMENT '處理狀態',
    response TEXT COMMENT '回覆內容',
    responded_at TIMESTAMP NULL COMMENT '回覆時間',
    responded_by INT COMMENT '回覆人員ID',
    INDEX idx_user_id (user_id),
    INDEX idx_person_id (person_id),
    INDEX idx_feedback_type (feedback_type),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (person_id) REFERENCES personal_info(person_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='意見回饋資料表（一對一關聯）';

-- ============================================
-- 6. 服務申請資料表（一對一關聯）
-- ============================================
CREATE TABLE IF NOT EXISTS service_application (
    application_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '申請ID',
    user_id INT COMMENT '申請人ID（關聯users表）',
    person_id INT NOT NULL UNIQUE COMMENT '個人ID（關聯personal_info表，一對一）',
    city VARCHAR(50) NOT NULL COMMENT '居住縣市',
    district VARCHAR(50) NOT NULL COMMENT '居住地區',
    address TEXT COMMENT '詳細地址',
    service_type VARCHAR(100) NOT NULL COMMENT '服務類型',
    service_items JSON COMMENT '申請服務項目（JSON格式儲存多選）',
    other_service VARCHAR(200) COMMENT '其他服務說明',
    urgency ENUM('緊急', '高', '中', '低') DEFAULT '中' COMMENT '緊急程度',
    description TEXT COMMENT '需求說明',
    preferred_date DATE COMMENT '希望服務日期',
    preferred_time VARCHAR(50) COMMENT '希望服務時間',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '提交時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending' COMMENT '申請狀態',
    approved_at TIMESTAMP NULL COMMENT '核准時間',
    completed_at TIMESTAMP NULL COMMENT '完成時間',
    INDEX idx_user_id (user_id),
    INDEX idx_person_id (person_id),
    INDEX idx_service_type (service_type),
    INDEX idx_status (status),
    INDEX idx_urgency (urgency),
    INDEX idx_submitted_at (submitted_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (person_id) REFERENCES personal_info(person_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='服務申請資料表（一對一關聯）';

-- ============================================
-- 7. 血壓資料表
-- ============================================
CREATE TABLE IF NOT EXISTS blood_pressure (
    bp_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '血壓記錄ID',
    person_id INT NOT NULL COMMENT '個人ID（關聯personal_info表）',
    id_number VARCHAR(10) NOT NULL COMMENT '身分證字號',
    birth_date DATE NOT NULL COMMENT '生日',
    card_date DATE NOT NULL COMMENT '過卡日期',
    visit_number VARCHAR(4) NOT NULL COMMENT '就醫序號（4碼）',
    systolic_bp INT NOT NULL COMMENT '收縮壓(mmHg)',
    diastolic_bp INT NOT NULL COMMENT '舒張壓(mmHg)',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '上傳時間',
    ip_address VARCHAR(45) COMMENT 'IP位址',
    INDEX idx_person_id (person_id),
    INDEX idx_id_number (id_number),
    INDEX idx_card_date (card_date),
    INDEX idx_submitted_at (submitted_at),
    FOREIGN KEY (person_id) REFERENCES personal_info(person_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='血壓資料表';

-- ============================================
-- 8. 檢驗檢查資料表
-- ============================================
CREATE TABLE IF NOT EXISTS lab_test (
    test_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '檢驗ID',
    person_id INT NOT NULL COMMENT '個人ID（關聯personal_info表）',
    id_number VARCHAR(10) NOT NULL COMMENT '身分證字號',
    birth_date DATE NOT NULL COMMENT '生日',
    card_date DATE NOT NULL COMMENT '過卡日期',
    visit_number VARCHAR(4) NOT NULL COMMENT '就醫序號（4碼英數）',
    doctor_id VARCHAR(100) NOT NULL COMMENT '醫師身分證',
    
    -- 單項檢驗（JSON格式儲存）
    single_tests JSON COMMENT '單項檢驗資料',
    
    -- 尿液常規檢查 (06012C)
    urine_appearance VARCHAR(50) COMMENT '外觀',
    urine_color VARCHAR(50) COMMENT '顏色',
    urine_reaction VARCHAR(20) COMMENT 'PH 酸鹼度',
    urine_glucose VARCHAR(20) COMMENT '尿糖',
    urine_occult_blood VARCHAR(20) COMMENT '尿潛血',
    urine_protein VARCHAR(20) COMMENT '尿蛋白',
    urine_urobilinogen VARCHAR(20) COMMENT '尿膽素元',
    urine_nitrite VARCHAR(20) COMMENT '亞硝酸鹽',
    urine_leukocyte VARCHAR(20) COMMENT '白血球脂酶',
    urine_bilirubin VARCHAR(20) COMMENT '尿膽紅素',
    urine_ketone_body VARCHAR(20) COMMENT '酮體',
    urine_specific_gravi VARCHAR(20) COMMENT '比重',
    urine_rbc VARCHAR(50) COMMENT '紅血球',
    urine_wbc VARCHAR(50) COMMENT '白血球',
    urine_clarity VARCHAR(50) COMMENT '混濁度',
    
    -- 血液套組 (08012C)
    blood_wbc VARCHAR(50) COMMENT 'WBC',
    blood_rbc VARCHAR(50) COMMENT 'RBC',
    blood_hb VARCHAR(50) COMMENT 'HB 血紅素',
    blood_hct VARCHAR(50) COMMENT 'HCT 血球容積比',
    blood_mch VARCHAR(50) COMMENT 'MCH 平均紅血球血紅素量',
    blood_mcv VARCHAR(50) COMMENT 'MCV 平均紅血球容積',
    blood_mchc VARCHAR(50) COMMENT 'MCHC 平均紅血球血紅素濃度',
    
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '上傳時間',
    ip_address VARCHAR(45) COMMENT 'IP位址',
    
    INDEX idx_person_id (person_id),
    INDEX idx_id_number (id_number),
    INDEX idx_card_date (card_date),
    INDEX idx_submitted_at (submitted_at),
    FOREIGN KEY (person_id) REFERENCES personal_info(person_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='檢驗檢查資料表';

-- ============================================
-- 9. 登入記錄表
-- ============================================
CREATE TABLE IF NOT EXISTS login_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '記錄ID',
    user_id INT COMMENT '使用者ID',
    username VARCHAR(50) NOT NULL COMMENT '帳號',
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '登入時間',
    ip_address VARCHAR(45) COMMENT 'IP位址',
    user_agent TEXT COMMENT '瀏覽器資訊',
    login_status ENUM('success', 'failed') NOT NULL COMMENT '登入狀態',
    failure_reason VARCHAR(200) COMMENT '失敗原因',
    INDEX idx_user_id (user_id),
    INDEX idx_username (username),
    INDEX idx_login_time (login_time),
    INDEX idx_login_status (login_status),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登入記錄表';

-- ============================================
-- 10. 系統設定表
-- ============================================
CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '設定ID',
    setting_key VARCHAR(100) NOT NULL UNIQUE COMMENT '設定鍵',
    setting_value TEXT COMMENT '設定值',
    setting_type VARCHAR(50) COMMENT '設定類型',
    description VARCHAR(255) COMMENT '說明',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    updated_by INT COMMENT '更新人員ID',
    INDEX idx_setting_key (setting_key),
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統設定表';

-- 建立視圖 - 檢驗檢查完整資料
CREATE OR REPLACE VIEW v_lab_test_full AS
SELECT 
    lt.test_id,
    lt.person_id,
    pi.id_number,
    pi.name,
    pi.birth_date,
    pi.gender,
    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age,
    lt.card_date,
    lt.visit_number,
    lt.doctor_id,
    lt.single_tests,
    lt.urine_appearance, lt.urine_color, lt.urine_reaction, lt.urine_glucose,
    lt.urine_occult_blood, lt.urine_protein, lt.urine_urobilinogen, lt.urine_nitrite,
    lt.urine_leukocyte, lt.urine_bilirubin, lt.urine_ketone_body, lt.urine_specific_gravi,
    lt.urine_rbc, lt.urine_wbc, lt.urine_clarity,
    lt.blood_wbc, lt.blood_rbc, lt.blood_hb, lt.blood_hct,
    lt.blood_mch, lt.blood_mcv, lt.blood_mchc,
    lt.submitted_at,
    lt.ip_address
FROM lab_test lt
INNER JOIN personal_info pi ON lt.person_id = pi.person_id;

-- 建立視圖 - 血壓資料（含個人資料）
CREATE OR REPLACE VIEW v_blood_pressure_full AS
SELECT 
    bp.bp_id,
    bp.person_id,
    pi.id_number,
    pi.name,
    pi.birth_date,
    pi.gender,
    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age,
    bp.card_date,
    bp.visit_number,
    bp.systolic_bp,
    bp.diastolic_bp,
    CASE 
        WHEN bp.systolic_bp >= 180 OR bp.diastolic_bp >= 120 THEN '高血壓危象'
        WHEN bp.systolic_bp >= 140 OR bp.diastolic_bp >= 90 THEN '高血壓'
        WHEN bp.systolic_bp >= 120 OR bp.diastolic_bp >= 80 THEN '血壓偏高'
        WHEN bp.systolic_bp >= 90 AND bp.diastolic_bp >= 60 THEN '正常'
        ELSE '血壓偏低'
    END as bp_status,
    bp.submitted_at,
    bp.ip_address
FROM blood_pressure bp
INNER JOIN personal_info pi ON bp.person_id = pi.person_id;

-- 建立視圖 - 血壓統計
CREATE OR REPLACE VIEW v_blood_pressure_stats AS
SELECT 
    pi.gender,
    CASE 
        WHEN TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) < 20 THEN '0-19歲'
        WHEN TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) < 40 THEN '20-39歲'
        WHEN TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) < 60 THEN '40-59歲'
        WHEN TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) < 80 THEN '60-79歲'
        ELSE '80歲以上'
    END as age_group,
    COUNT(*) as record_count,
    AVG(bp.systolic_bp) as avg_systolic_bp,
    AVG(bp.diastolic_bp) as avg_diastolic_bp,
    MIN(bp.systolic_bp) as min_systolic_bp,
    MAX(bp.systolic_bp) as max_systolic_bp,
    MIN(bp.diastolic_bp) as min_diastolic_bp,
    MAX(bp.diastolic_bp) as max_diastolic_bp
FROM blood_pressure bp
INNER JOIN personal_info pi ON bp.person_id = pi.person_id
GROUP BY pi.gender, age_group;

-- ============================================
-- 建立視圖 - 健康調查完整資料（含個人資料）
-- ============================================
CREATE OR REPLACE VIEW v_health_survey_full AS
SELECT 
    hs.survey_id,
    hs.user_id,
    pi.person_id,
    pi.id_number,
    pi.name,
    pi.birth_date,
    pi.gender,
    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age,
    hs.employment,
    hs.employment_other,
    hs.caregiver,
    hs.caregiver_other,
    hs.city,
    hs.district,
    hs.family_life_cycle,
    hs.family_life_cycle_other,
    hs.chronic_diseases,
    hs.chronic_disease_other,
    hs.medications,
    hs.medication_other,
    hs.food_allergy,
    hs.drug_allergy,
    hs.smoking,
    hs.drinking,
    hs.betel_nut,
    hs.height,
    hs.weight,
    ROUND(hs.weight / POWER(hs.height/100, 2), 2) as bmi,
    hs.systolic_bp,
    hs.diastolic_bp,
    hs.waist,
    hs.pulse,
    hs.submitted_at,
    hs.ip_address
FROM health_survey hs
INNER JOIN personal_info pi ON hs.person_id = pi.person_id;

-- ============================================
-- 建立視圖 - 個人資料統計（一對一關聯）
-- ============================================
CREATE OR REPLACE VIEW v_personal_info_stats AS
SELECT 
    pi.person_id,
    pi.id_number,
    pi.name,
    pi.birth_date,
    pi.gender,
    pi.phone,
    pi.email,
    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age,
    CASE WHEN hs.survey_id IS NOT NULL THEN 1 ELSE 0 END as has_health_survey,
    CASE WHEN ar.registration_id IS NOT NULL THEN 1 ELSE 0 END as has_activity_registration,
    CASE WHEN fb.feedback_id IS NOT NULL THEN 1 ELSE 0 END as has_feedback,
    CASE WHEN sa.application_id IS NOT NULL THEN 1 ELSE 0 END as has_service_application,
    pi.created_at
FROM personal_info pi
LEFT JOIN health_survey hs ON pi.person_id = hs.person_id
LEFT JOIN activity_registration ar ON pi.person_id = ar.person_id
LEFT JOIN feedback fb ON pi.person_id = fb.person_id
LEFT JOIN service_application sa ON pi.person_id = sa.person_id;

-- ============================================
-- 建立視圖 - 健康調查統計
-- ============================================
CREATE OR REPLACE VIEW v_health_survey_stats AS
SELECT 
    hs.city,
    hs.district,
    pi.gender,
    CASE 
        WHEN TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) < 20 THEN '0-19歲'
        WHEN TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) < 40 THEN '20-39歲'
        WHEN TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) < 60 THEN '40-59歲'
        WHEN TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) < 80 THEN '60-79歲'
        ELSE '80歲以上'
    END as age_group,
    COUNT(*) as survey_count,
    AVG(hs.height) as avg_height,
    AVG(hs.weight) as avg_weight,
    AVG(hs.weight / POWER(hs.height/100, 2)) as avg_bmi,
    AVG(hs.systolic_bp) as avg_systolic_bp,
    AVG(hs.diastolic_bp) as avg_diastolic_bp,
    AVG(hs.waist) as avg_waist,
    AVG(hs.pulse) as avg_pulse
FROM health_survey hs
INNER JOIN personal_info pi ON hs.person_id = pi.person_id
GROUP BY hs.city, hs.district, pi.gender, age_group;

-- ============================================
-- 建立預存程序 - 新增或取得個人資料
-- ============================================
DELIMITER //
CREATE PROCEDURE sp_get_or_create_person(
    IN p_id_number VARCHAR(10),
    IN p_name VARCHAR(100),
    IN p_birth_date DATE,
    IN p_gender ENUM('男', '女'),
    OUT p_person_id INT
)
BEGIN
    -- 檢查是否已存在
    SELECT person_id INTO p_person_id 
    FROM personal_info 
    WHERE id_number = p_id_number
    LIMIT 1;
    
    -- 如果不存在則新增
    IF p_person_id IS NULL THEN
        INSERT INTO personal_info (id_number, name, birth_date, gender)
        VALUES (p_id_number, p_name, p_birth_date, p_gender);
        
        SET p_person_id = LAST_INSERT_ID();
    ELSE
        -- 如果存在則更新資料
        UPDATE personal_info 
        SET name = p_name, 
            birth_date = p_birth_date, 
            gender = p_gender,
            updated_at = CURRENT_TIMESTAMP
        WHERE person_id = p_person_id;
    END IF;
END //
DELIMITER ;

-- ============================================
-- 建立預存程序 - 新增健康調查資料
-- ============================================
DELIMITER //
CREATE PROCEDURE sp_insert_health_survey(
    IN p_user_id INT,
    IN p_id_number VARCHAR(10),
    IN p_name VARCHAR(100),
    IN p_birth_date DATE,
    IN p_gender ENUM('男', '女'),
    IN p_employment VARCHAR(50),
    IN p_employment_other VARCHAR(200),
    IN p_caregiver VARCHAR(50),
    IN p_caregiver_other VARCHAR(200),
    IN p_city VARCHAR(50),
    IN p_district VARCHAR(50),
    IN p_family_life_cycle VARCHAR(100),
    IN p_family_life_cycle_other VARCHAR(200),
    IN p_chronic_diseases JSON,
    IN p_chronic_disease_other VARCHAR(500),
    IN p_medications JSON,
    IN p_medication_other VARCHAR(500),
    IN p_food_allergy TEXT,
    IN p_drug_allergy TEXT,
    IN p_smoking VARCHAR(100),
    IN p_drinking VARCHAR(100),
    IN p_betel_nut VARCHAR(100),
    IN p_height DECIMAL(5,1),
    IN p_weight DECIMAL(5,1),
    IN p_systolic_bp INT,
    IN p_diastolic_bp INT,
    IN p_waist DECIMAL(5,1),
    IN p_pulse INT,
    IN p_ip_address VARCHAR(45),
    OUT p_survey_id INT
)
BEGIN
    DECLARE v_person_id INT;
    
    -- 先取得或建立個人資料
    CALL sp_get_or_create_person(p_id_number, p_name, p_birth_date, p_gender, v_person_id);
    
    -- 新增健康調查資料
    INSERT INTO health_survey (
        user_id, person_id, employment, employment_other, caregiver, caregiver_other,
        city, district, family_life_cycle, family_life_cycle_other,
        chronic_diseases, chronic_disease_other, medications, medication_other,
        food_allergy, drug_allergy, smoking, drinking, betel_nut,
        height, weight, systolic_bp, diastolic_bp, waist, pulse, ip_address
    ) VALUES (
        p_user_id, v_person_id, p_employment, p_employment_other, p_caregiver, p_caregiver_other,
        p_city, p_district, p_family_life_cycle, p_family_life_cycle_other,
        p_chronic_diseases, p_chronic_disease_other, p_medications, p_medication_other,
        p_food_allergy, p_drug_allergy, p_smoking, p_drinking, p_betel_nut,
        p_height, p_weight, p_systolic_bp, p_diastolic_bp, p_waist, p_pulse, p_ip_address
    );
    
    SET p_survey_id = LAST_INSERT_ID();
END //
DELIMITER ;

-- ============================================
-- 建立觸發器 - 記錄個人資料變更
-- ============================================
CREATE TABLE IF NOT EXISTS personal_info_audit (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    action_type ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_data JSON,
    new_data JSON,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by INT,
    INDEX idx_person_id (person_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='個人資料變更記錄';

DELIMITER //
CREATE TRIGGER trg_personal_info_update
AFTER UPDATE ON personal_info
FOR EACH ROW
BEGIN
    INSERT INTO personal_info_audit (person_id, action_type, old_data, new_data)
    VALUES (
        OLD.person_id,
        'UPDATE',
        JSON_OBJECT('name', OLD.name, 'birth_date', OLD.birth_date, 'gender', OLD.gender),
        JSON_OBJECT('name', NEW.name, 'birth_date', NEW.birth_date, 'gender', NEW.gender)
    );
END //
DELIMITER ;

-- ============================================
-- 插入測試資料
-- ============================================

-- 個人資料測試資料
INSERT INTO personal_info (id_number, name, birth_date, gender) VALUES
('A123456789', '王小明', '1958-05-15', '男'),
('B234567890', '李美華', '1965-08-20', '女'),
('C345678901', '張大同', '1953-12-10', '男'),
('D456789012', '陳淑芬', '1961-03-25', '女');

-- 健康調查測試資料
INSERT INTO health_survey (
    user_id, person_id, employment, caregiver, city, district, 
    family_life_cycle, chronic_diseases, height, weight, 
    systolic_bp, diastolic_bp, waist, pulse
) VALUES
(1, 1, '退休', '本人', '台中市', '西區', '空巢', 
 '["1.高血壓", "10.關節炎"]', 170.0, 68.5, 135, 85, 88.0, 72),
(1, 2, '家管', '子女', '台中市', '南屯區', '老化的家庭', 
 '["2.糖尿病"]', 158.0, 55.0, 128, 78, 75.5, 68);

-- ============================================
-- 查詢範例
-- ============================================

-- 1. 查詢完整健康調查資料（含個人資料和BMI計算）
-- SELECT * FROM v_health_survey_full ORDER BY submitted_at DESC;

-- 2. 查詢個人的所有填寫記錄
-- SELECT * FROM v_personal_info_stats WHERE name = '王小明';

-- 3. 查詢特定地區的健康統計
-- SELECT * FROM v_health_survey_stats WHERE city = '台中市';

-- 4. 查詢有高血壓病史的人數
-- SELECT COUNT(*) as high_bp_count 
-- FROM health_survey 
-- WHERE JSON_CONTAINS(chronic_diseases, '"1.高血壓"');

-- 5. 查詢BMI超標的案例（BMI > 24）
-- SELECT id_number, name, gender, age, height, weight, bmi
-- FROM v_health_survey_full
-- WHERE bmi > 24
-- ORDER BY bmi DESC;

-- ============================================
-- 備份與還原
-- ============================================
-- 備份指令：
-- mysqldump -u root -p data_collection_system > backup_$(date +%Y%m%d_%H%M%S).sql

-- 還原指令：
-- mysql -u root -p data_collection_system < backup_YYYYMMDD_HHMMSS.sql

-- ============================================
-- 資料庫維護
-- ============================================
-- 定期清理30天前的登入記錄
-- DELETE FROM login_logs WHERE login_time < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 重建索引
-- OPTIMIZE TABLE health_survey;
-- OPTIMIZE TABLE personal_info;-- ============================================
-- 代謝防治與檢驗檢查資料表
-- ============================================

USE data_collection_system;

-- ============================================
-- 1. 代謝防治資料表
-- ============================================
CREATE TABLE IF NOT EXISTS metabolic_prevention (
    metabolic_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '代謝防治記錄ID',
    person_id INT NOT NULL COMMENT '個人ID（關聯personal_info表）',
    user_id INT COMMENT '填寫人ID（關聯users表）',
    
    -- 第一頁：基本資料
    id_number VARCHAR(10) NOT NULL COMMENT '身分證字號',
    birth_date DATE NOT NULL COMMENT '生日',
    name VARCHAR(100) NOT NULL COMMENT '姓名',
    gender ENUM('0', '1') NOT NULL COMMENT '性別 0.男 1.女',
    collection_date DATE NOT NULL COMMENT '收案日期',
    
    -- 第二頁：危險因子
    risk_smoking ENUM('1', '2', '3', '4') COMMENT '危險因子-抽菸 1.無 2.偶爾交際應酬 3.平均一天約吸10支菸以下 4.平均一天約吸10支菸(含)以上',
    risk_betel_nut ENUM('1', '2', '3') COMMENT '危險因子-嚼檳榔 1.無 2.偶爾交際應酬 3.經常嚼或習慣在嚼',
    risk_exercise ENUM('1', '2', '3') COMMENT '危險因子-運動 1.無 2.偶爾運動 3.經常運動(每週累計達150分鐘)',
    accompanying_diseases JSON COMMENT '伴隨疾病（複選）1.無 2.糖尿病 3.高血壓 4.心臟血管疾病 5.高血脂症 6.腎臟病 7.腦血管疾病',
    accompanying_diseases_other TEXT COMMENT '伴隨疾病(其他) IDC10',
    
    -- 第三頁：檢查資料
    examination_date DATE NOT NULL COMMENT '檢查日期',
    height INT NOT NULL COMMENT '身高(cm) 正整數',
    weight INT NOT NULL COMMENT '體重(kg) 正整數',
    waist INT NOT NULL COMMENT '腰圍(cm) 正整數',
    systolic_bp INT NOT NULL COMMENT '收縮壓(mmHg) 正整數',
    diastolic_bp INT NOT NULL COMMENT '舒張壓(mmHg) 正整數',
    bp_source ENUM('0', '1') NOT NULL COMMENT '血壓值來源 0.非診間量測 1.診間量測',
    antihypertensive_drug ENUM('0', '1') NOT NULL COMMENT '降血壓藥物 0.無 1.有',
    hypoglycemic_drug ENUM('0', '1') NOT NULL COMMENT '降血糖藥物 0.無 1.有',
    lipid_lowering_drug ENUM('0', '1') NOT NULL COMMENT '降血脂藥物 0.無 1.有',
    fasting_glucose DECIMAL(6,2) COMMENT '飯前血糖值(mg/dl) 數字',
    triglyceride DECIMAL(6,2) COMMENT '三酸甘油脂(mg/dl) 數字',
    hdl_cholesterol DECIMAL(6,2) COMMENT '高密度脂蛋白膽固醇值(mg/dl) 數字',
    ldl_cholesterol DECIMAL(6,2) COMMENT '低密度脂蛋白膽固醇值(mg/dl) 數字',
    hba1c DECIMAL(4,2) COMMENT '醣化血紅素(%) 數字',
    total_cholesterol DECIMAL(6,2) COMMENT '總膽固醇值(mg/dl) 數字',
    
    -- 計算欄位
    bmi DECIMAL(5,2) GENERATED ALWAYS AS (weight / POWER(height/100, 2)) STORED COMMENT 'BMI值（自動計算）',
    
    -- 系統欄位
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    ip_address VARCHAR(45) COMMENT 'IP位址',
    
    INDEX idx_person_id (person_id),
    INDEX idx_user_id (user_id),
    INDEX idx_id_number (id_number),
    INDEX idx_collection_date (collection_date),
    INDEX idx_examination_date (examination_date),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (person_id) REFERENCES personal_info(person_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='代謝防治資料表';

-- ============================================
-- 2. 檢驗檢查資料表（主表）
-- ============================================
CREATE TABLE IF NOT EXISTS lab_test (
    test_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '檢驗ID',
    person_id INT NOT NULL COMMENT '個人ID（關聯personal_info表）',
    user_id INT COMMENT '填寫人ID（關聯users表）',
    
    -- 基本資料
    id_number VARCHAR(10) NOT NULL COMMENT '身分證字號',
    birth_date DATE NOT NULL COMMENT '生日',
    card_date DATE NOT NULL COMMENT '過卡日期',
    medical_serial VARCHAR(4) NOT NULL COMMENT '就醫序號 4碼英數',
    doctor_id VARCHAR(10) COMMENT '醫師身分證（下拉式選單）',
    
    -- 系統欄位
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    ip_address VARCHAR(45) COMMENT 'IP位址',
    
    INDEX idx_person_id (person_id),
    INDEX idx_user_id (user_id),
    INDEX idx_id_number (id_number),
    INDEX idx_card_date (card_date),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (person_id) REFERENCES personal_info(person_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='檢驗檢查主表';

-- ============================================
-- 3. 檢驗檢查單項資料表
-- ============================================
CREATE TABLE IF NOT EXISTS lab_test_single_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '單項ID',
    test_id INT NOT NULL COMMENT '檢驗ID（關聯lab_test表）',
    
    -- 單項檢驗代碼與結果
    test_code VARCHAR(20) NOT NULL COMMENT '檢驗代碼（如：09005C, 14065C等）',
    test_name VARCHAR(200) NOT NULL COMMENT '檢驗名稱',
    test_result TEXT COMMENT '檢驗結果',
    
    -- 系統欄位
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    
    INDEX idx_test_id (test_id),
    INDEX idx_test_code (test_code),
    FOREIGN KEY (test_id) REFERENCES lab_test(test_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='檢驗檢查單項資料表';

-- ============================================
-- 4. 尿液常規檢查資料表（06012C）
-- ============================================
CREATE TABLE IF NOT EXISTS lab_test_urine (
    urine_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '尿液檢查ID',
    test_id INT NOT NULL UNIQUE COMMENT '檢驗ID（關聯lab_test表，一對一）',
    
    -- 尿液常規檢查項目
    appearance VARCHAR(100) COMMENT 'Appearance 外觀 (06012C)',
    color VARCHAR(100) COMMENT 'Color 顏色 (06012C)',
    reaction_ph VARCHAR(100) COMMENT 'Reaction PH 酸鹼度 (06012C)',
    glucose VARCHAR(100) COMMENT 'Glucose 尿糖 (06012C)',
    occult_blood VARCHAR(100) COMMENT 'Occult blood 尿潛血 (06012C)',
    protein VARCHAR(100) COMMENT 'Protein 尿蛋白 (06012C)',
    urobilinogen VARCHAR(100) COMMENT 'Urobilinogen 尿膽素元 (06012C)',
    nitrite VARCHAR(100) COMMENT 'Nitrite 亞硝酸鹽 (06012C)',
    leukocyte VARCHAR(100) COMMENT 'Leukocyte 白血球脂酶 (06012C)',
    bilirubin VARCHAR(100) COMMENT 'Bilirubin 尿膽紅素 (06012C)',
    ketone_body VARCHAR(100) COMMENT 'Ketone body 酮體 (06012C)',
    specific_gravity VARCHAR(100) COMMENT 'Specific Gravi 比重 (06012C)',
    rbc VARCHAR(100) COMMENT 'RBC 紅血球 (06012C)',
    wbc VARCHAR(100) COMMENT 'WBC 白血球 (06012C)',
    clarity VARCHAR(100) COMMENT 'Clarity 混濁度 (06012C)',
    
    -- 系統欄位
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    INDEX idx_test_id (test_id),
    FOREIGN KEY (test_id) REFERENCES lab_test(test_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='尿液常規檢查資料表';

-- ============================================
-- 5. 血液套組檢查資料表（08012C）
-- ============================================
CREATE TABLE IF NOT EXISTS lab_test_blood (
    blood_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '血液檢查ID',
    test_id INT NOT NULL UNIQUE COMMENT '檢驗ID（關聯lab_test表，一對一）',
    
    -- 血液套組檢查項目
    wbc VARCHAR(100) COMMENT 'WBC (08012C)',
    rbc VARCHAR(100) COMMENT 'RBC (08012C)',
    hb VARCHAR(100) COMMENT 'HB (08012C)',
    hct VARCHAR(100) COMMENT 'HCT (08012C)',
    mch VARCHAR(100) COMMENT 'MCH (08012C)',
    mcv VARCHAR(100) COMMENT 'MCV (08012C)',
    mchc VARCHAR(100) COMMENT 'MCHC (08012C)',
    
    -- 系統欄位
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    INDEX idx_test_id (test_id),
    FOREIGN KEY (test_id) REFERENCES lab_test(test_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='血液套組檢查資料表';

-- ============================================
-- 6. 醫師資料表（用於檢驗檢查的醫師下拉選單）
-- ============================================
CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '醫師ID',
    id_number VARCHAR(10) NOT NULL UNIQUE COMMENT '醫師身分證字號',
    name VARCHAR(100) NOT NULL COMMENT '醫師姓名',
    specialty VARCHAR(100) COMMENT '專科',
    phone VARCHAR(20) COMMENT '聯絡電話',
    email VARCHAR(100) COMMENT '電子郵件',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT '狀態',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    INDEX idx_id_number (id_number),
    INDEX idx_name (name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='醫師資料表';

-- ============================================
-- 建立視圖 - 代謝防治完整資料
-- ============================================
CREATE OR REPLACE VIEW v_metabolic_prevention_full AS
SELECT 
    mp.*,
    pi.name as person_name,
    pi.phone,
    pi.email,
    u.display_name as submitted_by,
    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age
FROM metabolic_prevention mp
INNER JOIN personal_info pi ON mp.person_id = pi.person_id
LEFT JOIN users u ON mp.user_id = u.user_id;

-- ============================================
-- 建立視圖 - 檢驗檢查完整資料
-- ============================================
CREATE OR REPLACE VIEW v_lab_test_full AS
SELECT 
    lt.*,
    pi.name as person_name,
    pi.phone,
    pi.email,
    d.name as doctor_name,
    u.display_name as submitted_by,
    TIMESTAMPDIFF(YEAR, pi.birth_date, CURDATE()) as age
FROM lab_test lt
INNER JOIN personal_info pi ON lt.person_id = pi.person_id
LEFT JOIN doctors d ON lt.doctor_id = d.id_number
LEFT JOIN users u ON lt.user_id = u.user_id;

-- ============================================
-- 插入測試資料 - 醫師資料
-- ============================================
INSERT INTO doctors (id_number, name, specialty) VALUES
('D123456789', '王醫師', '內科'),
('D234567890', '李醫師', '家醫科'),
('D345678901', '張醫師', '心臟科'),
('D456789012', '陳醫師', '新陳代謝科');

-- ============================================
-- 插入測試資料 - 代謝防治
-- ============================================
INSERT INTO metabolic_prevention (
    person_id, user_id, id_number, birth_date, name, gender, collection_date,
    risk_smoking, risk_betel_nut, risk_exercise,
    examination_date, height, weight, waist, systolic_bp, diastolic_bp,
    bp_source, antihypertensive_drug, hypoglycemic_drug, lipid_lowering_drug
) VALUES
(1, 1, 'A123456789', '1958-05-15', '王小明', '0', '2024-01-15',
 '1', '1', '3',
 '2024-01-15', 170, 70, 88, 135, 85,
 '1', '1', '0', '0');

-- ============================================
-- 插入測試資料 - 檢驗檢查
-- ============================================
-- 先插入主表
INSERT INTO lab_test (
    person_id, user_id, id_number, birth_date, card_date, medical_serial, doctor_id
) VALUES
(1, 1, 'A123456789', '1958-05-15', '2024-01-15', 'A001', 'D123456789');

-- 取得剛插入的test_id
SET @test_id = LAST_INSERT_ID();

-- 插入單項檢驗
INSERT INTO lab_test_single_items (test_id, test_code, test_name, test_result) VALUES
(@test_id, '09005C', '指尖血血糖 One touch Glucose', '110'),
(@test_id, '14065C', 'A型流感', '陰性');

-- 插入尿液檢查
INSERT INTO lab_test_urine (
    test_id, appearance, color, reaction_ph, glucose, protein
) VALUES
(@test_id, '清澈', '淡黃色', '6.0', '-', '-');

-- 插入血液檢查
INSERT INTO lab_test_blood (
    test_id, wbc, rbc, hb, hct
) VALUES
(@test_id, '7500', '4.5', '14.5', '42');

-- ============================================
-- 查詢範例
-- ============================================

-- 1. 查詢代謝防治完整資料
-- SELECT * FROM v_metabolic_prevention_full ORDER BY created_at DESC;

-- 2. 查詢檢驗檢查完整資料
-- SELECT * FROM v_lab_test_full ORDER BY created_at DESC;

-- 3. 查詢特定人員的代謝防治記錄
-- SELECT * FROM metabolic_prevention WHERE id_number = 'A123456789';

-- 4. 查詢特定檢驗的所有單項結果
-- SELECT lt.*, lts.* 
-- FROM lab_test lt
-- LEFT JOIN lab_test_single_items lts ON lt.test_id = lts.test_id
-- WHERE lt.id_number = 'A123456789';

-- 5. 查詢特定檢驗的尿液檢查結果
-- SELECT lt.*, ltu.* 
-- FROM lab_test lt
-- LEFT JOIN lab_test_urine ltu ON lt.test_id = ltu.test_id
-- WHERE lt.id_number = 'A123456789';

-- 6. 查詢特定檢驗的血液檢查結果
-- SELECT lt.*, ltb.* 
-- FROM lab_test lt
-- LEFT JOIN lab_test_blood ltb ON lt.test_id = ltb.test_id
-- WHERE lt.id_number = 'A123456789';

-- 7. 統計代謝防治資料按風險因子分組
-- SELECT 
--     risk_smoking,
--     COUNT(*) as count,
--     AVG(bmi) as avg_bmi,
--     AVG(systolic_bp) as avg_systolic,
--     AVG(diastolic_bp) as avg_diastolic
-- FROM metabolic_prevention
-- GROUP BY risk_smoking;

-- ============================================
-- 建立預存程序 - 新增代謝防治資料
-- ============================================
DELIMITER //
CREATE PROCEDURE sp_insert_metabolic_prevention(
    IN p_user_id INT,
    IN p_id_number VARCHAR(10),
    IN p_birth_date DATE,
    IN p_name VARCHAR(100),
    IN p_gender ENUM('0', '1'),
    IN p_collection_date DATE,
    IN p_risk_smoking ENUM('1', '2', '3', '4'),
    IN p_risk_betel_nut ENUM('1', '2', '3'),
    IN p_risk_exercise ENUM('1', '2', '3'),
    IN p_accompanying_diseases JSON,
    IN p_accompanying_diseases_other TEXT,
    IN p_examination_date DATE,
    IN p_height INT,
    IN p_weight INT,
    IN p_waist INT,
    IN p_systolic_bp INT,
    IN p_diastolic_bp INT,
    IN p_bp_source ENUM('0', '1'),
    IN p_antihypertensive_drug ENUM('0', '1'),
    IN p_hypoglycemic_drug ENUM('0', '1'),
    IN p_lipid_lowering_drug ENUM('0', '1'),
    IN p_fasting_glucose DECIMAL(6,2),
    IN p_triglyceride DECIMAL(6,2),
    IN p_hdl_cholesterol DECIMAL(6,2),
    IN p_ldl_cholesterol DECIMAL(6,2),
    IN p_hba1c DECIMAL(4,2),
    IN p_total_cholesterol DECIMAL(6,2),
    IN p_ip_address VARCHAR(45),
    OUT p_metabolic_id INT
)
BEGIN
    DECLARE v_person_id INT;
    DECLARE v_person_gender ENUM('男', '女');
    
    -- 轉換性別格式 (0->男, 1->女)
    IF p_gender = '0' THEN
        SET v_person_gender = '男';
    ELSE
        SET v_person_gender = '女';
    END IF;
    
    -- 先取得或建立個人資料
    CALL sp_get_or_create_person(p_id_number, p_name, p_birth_date, v_person_gender, v_person_id);
    
    -- 新增代謝防治資料
    INSERT INTO metabolic_prevention (
        person_id, user_id, id_number, birth_date, name, gender, collection_date,
        risk_smoking, risk_betel_nut, risk_exercise, 
        accompanying_diseases, accompanying_diseases_other,
        examination_date, height, weight, waist, systolic_bp, diastolic_bp,
        bp_source, antihypertensive_drug, hypoglycemic_drug, lipid_lowering_drug,
        fasting_glucose, triglyceride, hdl_cholesterol, ldl_cholesterol, 
        hba1c, total_cholesterol, ip_address
    ) VALUES (
        v_person_id, p_user_id, p_id_number, p_birth_date, p_name, p_gender, p_collection_date,
        p_risk_smoking, p_risk_betel_nut, p_risk_exercise,
        p_accompanying_diseases, p_accompanying_diseases_other,
        p_examination_date, p_height, p_weight, p_waist, p_systolic_bp, p_diastolic_bp,
        p_bp_source, p_antihypertensive_drug, p_hypoglycemic_drug, p_lipid_lowering_drug,
        p_fasting_glucose, p_triglyceride, p_hdl_cholesterol, p_ldl_cholesterol,
        p_hba1c, p_total_cholesterol, p_ip_address
    );
    
    SET p_metabolic_id = LAST_INSERT_ID();
END //
DELIMITER ;

-- ============================================
-- 建立預存程序 - 新增檢驗檢查資料
-- ============================================
DELIMITER //
CREATE PROCEDURE sp_insert_lab_test(
    IN p_user_id INT,
    IN p_id_number VARCHAR(10),
    IN p_birth_date DATE,
    IN p_card_date DATE,
    IN p_medical_serial VARCHAR(4),
    IN p_doctor_id VARCHAR(10),
    IN p_ip_address VARCHAR(45),
    OUT p_test_id INT
)
BEGIN
    DECLARE v_person_id INT;
    DECLARE v_name VARCHAR(100);
    DECLARE v_gender ENUM('男', '女');
    
    -- 從personal_info表取得個人資料
    SELECT person_id, name, gender INTO v_person_id, v_name, v_gender
    FROM personal_info
    WHERE id_number = p_id_number
    LIMIT 1;
    
    -- 如果找不到，建立基本資料（這種情況應該要有名字和性別）
    IF v_person_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '找不到對應的個人資料，請先建立個人基本資料';
    END IF;
    
    -- 新增檢驗檢查主表資料
    INSERT INTO lab_test (
        person_id, user_id, id_number, birth_date, card_date, 
        medical_serial, doctor_id, ip_address
    ) VALUES (
        v_person_id, p_user_id, p_id_number, p_birth_date, p_card_date,
        p_medical_serial, p_doctor_id, p_ip_address
    );
    
    SET p_test_id = LAST_INSERT_ID();
END //
DELIMITER ;