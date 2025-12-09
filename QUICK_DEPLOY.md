# 快速部署指南：姓名可為 NULL 修改

## 📦 檔案清單

```
修改套件包含以下檔案：
├── alter_personal_info.sql          # 資料庫結構修改
├── test_name_nullable.sql           # 測試腳本
├── save_blood_pressure.php          # 血壓上傳（已修改）
├── save_lab_test.php                # 檢驗檢查（已修改）
├── save_metabolic_prevention.php    # 代謝防治（已修改）
├── save_health_survey.php           # 健康調查（已修改）
├── MODIFICATION_GUIDE.md            # 完整說明文件
└── QUICK_DEPLOY.md                  # 本檔案
```

## 🚀 部署步驟（5分鐘）

### 步驟 1：備份資料庫（1分鐘）

```bash
# 完整備份
mysqldump -u root -p data_collection_system > backup_$(date +%Y%m%d_%H%M%S).sql

# 或只備份 personal_info 表
mysqldump -u root -p data_collection_system personal_info > backup_personal_info.sql
```

### 步驟 2：執行資料庫修改（30秒）

```bash
mysql -u root -p data_collection_system < alter_personal_info.sql
```

**預期輸出**：
```
Query OK, X rows affected (0.XX sec)
```

### 步驟 3：驗證資料庫修改（30秒）

```bash
mysql -u root -p data_collection_system -e "DESCRIBE personal_info;"
```

**檢查 name 欄位**：
```
Field       Type           Null    Key     Default
name        varchar(100)   YES             NULL
```
確認 `Null` 欄位顯示 `YES`。

### 步驟 4：備份現有 PHP 檔案（1分鐘）

```bash
cd /var/www/html/php  # 根據實際路徑調整

# 備份現有檔案
cp save_blood_pressure.php save_blood_pressure.php.backup
cp save_lab_test.php save_lab_test.php.backup
cp save_metabolic_prevention.php save_metabolic_prevention.php.backup
cp save_health_survey.php save_health_survey.php.backup
```

### 步驟 5：上傳新的 PHP 檔案（1分鐘）

```bash
# 方法 1：使用 SCP（從本機上傳）
scp save_*.php user@server:/var/www/html/php/

# 方法 2：使用 FTP 客戶端
# 上傳 save_blood_pressure.php
# 上傳 save_lab_test.php
# 上傳 save_metabolic_prevention.php
# 上傳 save_health_survey.php

# 方法 3：直接在伺服器上替換
# 將檔案內容複製貼上到對應檔案
```

### 步驟 6：設定檔案權限（30秒）

```bash
cd /var/www/html/php
chmod 644 save_*.php
chown www-data:www-data save_*.php  # 根據實際使用者調整
```

### 步驟 7：測試功能（1分鐘）

```bash
# 執行測試 SQL
mysql -u root -p data_collection_system < test_name_nullable.sql
```

## ✅ 驗證檢查清單

### 資料庫驗證

- [ ] `personal_info.name` 欄位允許 NULL
- [ ] 測試資料成功插入
- [ ] 測試資料成功更新

### PHP 檔案驗證

- [ ] save_blood_pressure.php 已更新
- [ ] save_lab_test.php 已更新
- [ ] save_metabolic_prevention.php 已更新
- [ ] save_health_survey.php 已更新

### 功能測試

- [ ] 血壓上傳（新病患，無姓名）成功
- [ ] 檢驗檢查（新病患，無姓名）成功
- [ ] 代謝防治（新病患，有姓名）成功
- [ ] 健康調查（更新現有病患姓名）成功

## 🧪 快速測試指令

### 測試 1：血壓上傳（新病患）

```bash
curl -X POST http://localhost/php/save_blood_pressure.php \
  -H "Content-Type: application/json" \
  -d '{
    "idNumber": "TEST999999",
    "birthDate": "1970-01-01",
    "cardDate": "2024-12-09",
    "visitNumber": "0001",
    "systolicBP": 120,
    "diastolicBP": 80
  }'
```

**預期回應**：
```json
{
  "success": true,
  "message": "血壓資料上傳成功",
  "data": {
    "person_id": 123,
    "bp_id": 456,
    "bp_status": "正常"
  }
}
```

### 測試 2：檢查資料庫

```sql
SELECT id_number, name, birth_date, gender 
FROM personal_info 
WHERE id_number = 'TEST999999';
```

**預期結果**：
```
id_number    name    birth_date    gender
TEST999999   NULL    1970-01-01    男
```

## 🔧 常見問題排除

### 問題 1：資料庫修改失敗

**錯誤訊息**：
```
ERROR 1265 (01000): Data truncated for column 'name' at row X
```

**解決方法**：
```sql
-- 先將現有的空字串改為 NULL
UPDATE personal_info SET name = NULL WHERE name = '';

-- 再執行 ALTER TABLE
ALTER TABLE personal_info MODIFY COLUMN name VARCHAR(100) NULL;
```

### 問題 2：PHP 檔案上傳後無效

**檢查點**：
1. 檔案權限是否正確（644）
2. 檔案擁有者是否正確（www-data 或 apache）
3. PHP 語法是否正確：`php -l save_blood_pressure.php`
4. 瀏覽器快取是否已清除

**解決方法**：
```bash
# 檢查 PHP 語法
php -l /var/www/html/php/save_blood_pressure.php

# 重啟 PHP-FPM（如果使用）
sudo systemctl restart php-fpm

# 或重啟 Apache
sudo systemctl restart apache2
```

### 問題 3：測試時出現「找不到對應的個人資料」

**可能原因**：
- PHP 檔案沒有正確更新
- 快取問題

**解決方法**：
```bash
# 確認檔案內容
grep -n "sendErrorResponse('找不到對應的個人資料" /var/www/html/php/save_blood_pressure.php

# 如果找到這行，表示檔案沒有更新成功
# 重新上傳檔案
```

## 📊 監控建議

### 每日檢查

```sql
-- 檢查姓名為 NULL 的病患數量
SELECT COUNT(*) as no_name_count
FROM personal_info
WHERE name IS NULL;

-- 檢查最近建立的病患
SELECT 
    id_number,
    COALESCE(name, '（未登記）') as name,
    birth_date,
    gender,
    created_at
FROM personal_info
WHERE DATE(created_at) = CURDATE()
ORDER BY created_at DESC;
```

### 每週報告

```sql
-- 本週資料完整度報告
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total,
    SUM(CASE WHEN name IS NULL THEN 1 ELSE 0 END) as no_name,
    SUM(CASE WHEN name IS NOT NULL THEN 1 ELSE 0 END) as has_name
FROM personal_info
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

## 🔄 回滾步驟（如需要）

### 回滾資料庫

```sql
-- 將 name 改回 NOT NULL（需要先處理 NULL 值）
UPDATE personal_info SET name = '未提供' WHERE name IS NULL;
ALTER TABLE personal_info MODIFY COLUMN name VARCHAR(100) NOT NULL;
```

### 回滾 PHP 檔案

```bash
cd /var/www/html/php

# 還原備份檔案
cp save_blood_pressure.php.backup save_blood_pressure.php
cp save_lab_test.php.backup save_lab_test.php
cp save_metabolic_prevention.php.backup save_metabolic_prevention.php
cp save_health_survey.php.backup save_health_survey.php

# 設定權限
chmod 644 save_*.php
```

## 📞 支援資訊

### 檢查日誌

```bash
# PHP 錯誤日誌
tail -f /var/log/apache2/error.log
# 或
tail -f /var/log/php-fpm/error.log

# MySQL 錯誤日誌
tail -f /var/log/mysql/error.log
```

### 常用除錯指令

```bash
# 檢查 PHP 版本
php -v

# 檢查 PDO 擴展
php -m | grep PDO

# 測試資料庫連線
mysql -u root -p -e "SELECT VERSION();"
```

## ✨ 完成確認

部署完成後，請確認以下項目：

- [x] 資料庫修改成功
- [x] 4 個 PHP 檔案已更新
- [x] 測試 SQL 執行成功
- [x] 血壓上傳測試通過
- [x] 檢驗檢查測試通過
- [x] 姓名 NULL 可正常顯示
- [x] 後續更新姓名正常

**恭喜！部署完成！** 🎉

系統現在可以在沒有姓名的情況下建立病患資料，並在後續表單中補充完整資訊。
