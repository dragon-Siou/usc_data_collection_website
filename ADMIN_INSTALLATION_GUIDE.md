# 後台管理系統安裝說明

## 📦 檔案清單

### 前端檔案（1個）
- **admin.html** - 後台管理頁面

### 後端檔案（2個）
- **export_excel.php** - Excel 匯出 API
- **get_blood_pressure.php** - 血壓統計查詢 API

### 相關檔案
- 已存在的查詢 API：
  - get_health_survey.php
  - get_lab_test.php
  - get_metabolic_prevention.php

## 🎯 功能特色

### 1. 四大功能模組
- ✅ 健康調查資料匯出
- ✅ 血壓資料匯出
- ✅ 檢驗檢查資料匯出
- ✅ 代謝防治資料匯出

### 2. 日期區間篩選
- 開始日期和結束日期選擇
- 快速選擇按鈕：
  - 今天
  - 昨天
  - 本週
  - 本月
  - 全部

### 3. 統計資訊卡片
- 顯示各類型資料的總筆數
- 即時更新統計數字

### 4. 匯出歷史記錄
- 顯示最近 5 次匯出記錄
- 記錄匯出時間和狀態

### 5. Excel 格式化
- 自動標題列樣式（藍底白字）
- 資料列邊框和對齊
- 自動欄寬調整
- 凍結首列

## 📋 安裝前準備

### 1. 檢查 PHP 版本
```bash
php -v
# 需要 PHP 7.4 或以上
```

### 2. 安裝 Composer（如果還沒安裝）
```bash
# Linux/Mac
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Windows
# 下載並執行 Composer-Setup.exe
# https://getcomposer.org/download/
```

### 3. 安裝 PhpSpreadsheet
```bash
cd /var/www/html/php  # 根據實際路徑調整
composer require phpoffice/phpspreadsheet
```

安裝後會產生 `vendor/` 目錄。

## 🚀 部署步驟（5分鐘）

### 步驟 1：上傳前端檔案

將 `admin.html` 上傳到網站根目錄：
```
your-website/
├── index.html
├── login.html
├── admin.html  ← 新增
├── health.html
└── ...
```

### 步驟 2：上傳後端檔案

將 PHP 檔案上傳到 `php/` 目錄：
```
your-website/php/
├── config.php (已存在)
├── export_excel.php  ← 新增
├── get_blood_pressure.php  ← 新增
├── get_health_survey.php (已存在)
├── get_lab_test.php (已存在)
├── get_metabolic_prevention.php (已存在)
└── vendor/  ← Composer 安裝產生
    └── phpoffice/
```

### 步驟 3：設定檔案權限

```bash
cd /var/www/html
chmod 644 admin.html

cd php
chmod 644 export_excel.php
chmod 644 get_blood_pressure.php
chmod -R 755 vendor/

chown -R www-data:www-data .  # 根據實際使用者調整
```

### 步驟 4：測試功能

1. 開啟瀏覽器，訪問：`http://your-site/admin.html`
2. 使用登入帳號進入（1234 / 0000）
3. 測試各個匯出按鈕

## 🔧 設定選項

### 修改登入權限（建議）

目前所有登入的使用者都可以訪問後台。如果要限制只有管理員可以訪問，修改 `admin.html`：

```javascript
// 在 mounted() 方法中加入權限檢查
mounted() {
  this.checkLogin();
  
  // 檢查是否為管理員
  const username = sessionStorage.getItem('username');
  if (username !== '1234') {  // 只允許管理員帳號
    alert('您沒有權限訪問後台');
    window.location.href = 'index.html';
    return;
  }
  
  this.displayName = sessionStorage.getItem('displayName') || 'XXX';
  this.loadStatistics();
}
```

### 自訂 Excel 樣式

修改 `export_excel.php` 中的 `createExcelFile()` 函數：

```php
// 修改標題列顏色
'startColor' => ['rgb' => '4472C4']  // 藍色
// 改為其他顏色，例如：
'startColor' => ['rgb' => '2E7D32']  // 綠色
'startColor' => ['rgb' => 'D32F2F']  // 紅色
'startColor' => ['rgb' => 'F57C00']  // 橘色
```

### 調整統計 API 快取

如果資料量很大，可以加入快取機制：

```php
// 在 get_blood_pressure.php 的開頭加入
$cache_time = 300; // 5分鐘快取
header("Cache-Control: max-age={$cache_time}");
```

## 📊 Excel 欄位說明

### 健康調查 Excel 欄位
- 身分證字號、姓名、性別、出生日期、年齡
- 就業別、主要照顧者、居住縣市、居住地區
- 家庭生命週期、慢性病史、長期藥物
- 抽菸、喝酒、嚼檳榔
- 身高、體重、BMI、血壓、腰圍、脈搏
- 提交時間

### 血壓資料 Excel 欄位
- 身分證字號、姓名、性別、出生日期、年齡
- 過卡日期、就醫序號
- 收縮壓、舒張壓、血壓狀態
- 上傳時間

### 檢驗檢查 Excel 欄位
- 檢驗ID、身分證字號、姓名、性別
- 出生日期、年齡、過卡日期、就醫序號
- 醫師身分證、醫師姓名
- 單項檢驗結果（合併顯示）
- 建立時間

### 代謝防治 Excel 欄位
- 身分證字號、姓名、性別、出生日期、年齡
- 收案日期、檢查日期
- 抽菸、嚼檳榔、運動、伴隨疾病
- 身高、體重、BMI、腰圍
- 血壓、血壓來源、藥物使用
- 血糖、血脂相關檢驗值
- 建立時間

## 🧪 測試案例

### 測試 1：匯出全部健康調查資料

1. 開啟後台頁面
2. 點選「全部」快速按鈕（清空日期區間）
3. 點選「健康調查」的「下載 Excel」
4. 檢查下載的檔案

**預期結果**：
- 檔案名稱：`健康調查_YYYYMMDD_HHMMSS.xlsx`
- 包含所有健康調查記錄
- 標題列為藍底白字
- 資料完整且格式正確

### 測試 2：匯出指定日期區間的血壓資料

1. 設定開始日期：2024-01-01
2. 設定結束日期：2024-12-31
3. 點選「血壓資料」的「下載 Excel」
4. 檢查下載的檔案

**預期結果**：
- 只包含 2024 年的血壓記錄
- 血壓狀態欄位顯示正確（正常、血壓偏高等）

### 測試 3：快速選擇「本月」

1. 點選「本月」按鈕
2. 確認日期區間正確（本月1日 至 今天）
3. 匯出任一類型資料

**預期結果**：
- 只包含本月的記錄

### 測試 4：統計數字顯示

1. 檢查頁面上方的統計卡片
2. 確認各類型資料的筆數正確

**預期結果**：
- 顯示正確的資料筆數
- 卡片顏色與對應按鈕一致

## ⚠️ 常見問題

### 問題 1：下載時出現錯誤

**錯誤訊息**：
```
Fatal error: Uncaught Error: Class 'PhpOffice\PhpSpreadsheet\Spreadsheet' not found
```

**解決方法**：
```bash
# 確認 PhpSpreadsheet 已安裝
cd /var/www/html/php
composer require phpoffice/phpspreadsheet

# 檢查 vendor/ 目錄是否存在
ls -la vendor/
```

### 問題 2：匯出的 Excel 檔案損壞

**可能原因**：
- PHP 檔案有 BOM 標記
- 輸出前有額外的空白或換行

**解決方法**：
```bash
# 檢查 PHP 檔案編碼
file export_excel.php

# 應該顯示：export_excel.php: PHP script, UTF-8 Unicode text
# 如果顯示 "with BOM"，需要移除 BOM

# 使用編輯器重新保存為 UTF-8 without BOM
```

### 問題 3：統計數字不顯示

**檢查步驟**：
1. 開啟瀏覽器開發者工具（F12）
2. 查看 Console 是否有錯誤
3. 查看 Network 標籤，檢查 API 請求

**常見原因**：
- API 路徑錯誤
- get_*.php 檔案沒有上傳
- 資料庫連線失敗

**解決方法**：
```bash
# 測試 API
curl http://your-site/php/get_health_survey.php?type=stats

# 檢查 PHP 錯誤日誌
tail -f /var/log/apache2/error.log
```

### 問題 4：日期區間篩選無效

**檢查 SQL 語法**：

在 `export_excel.php` 的各個匯出函數中，確認有正確處理日期參數：

```php
if ($start_date) {
    $sql .= " AND DATE(table.submitted_at) >= :start_date";
}
if ($end_date) {
    $sql .= " AND DATE(table.submitted_at) <= :end_date";
}
```

### 問題 5：記憶體不足

**錯誤訊息**：
```
Fatal error: Allowed memory size of 134217728 bytes exhausted
```

**解決方法 1**：增加 PHP 記憶體限制

```bash
# 編輯 php.ini
sudo nano /etc/php/7.4/apache2/php.ini

# 修改這行
memory_limit = 256M  # 從 128M 改為 256M

# 重啟 Apache
sudo systemctl restart apache2
```

**解決方法 2**：在 export_excel.php 開頭加入

```php
ini_set('memory_limit', '256M');
```

**解決方法 3**：使用分批匯出

```php
// 對於大量資料，使用 LIMIT 分批查詢
$limit = 1000;
$offset = 0;
// 循環查詢並寫入 Excel
```

## 📱 首頁整合

### 在首頁加入後台入口

修改 `index.html`，加入後台管理按鈕：

```html
<!-- 在導覽列中加入 -->
<a href="admin.html" class="flex items-center text-indigo-600 hover:text-indigo-700 text-xl md:text-2xl font-medium bg-white px-4 py-2 md:px-6 md:py-3 rounded-xl shadow-md hover:shadow-lg transition-all">
  <svg class="w-5 h-5 md:w-6 md:h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
  </svg>
  後台管理
</a>
```

## 🔒 安全建議

### 1. 限制訪問權限

```php
// 在 export_excel.php 開頭加入
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die('無權限訪問');
}
```

### 2. 驗證日期格式

```php
// 在 export_excel.php 中加入日期驗證
if ($start_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    sendErrorResponse('日期格式錯誤');
}
```

### 3. SQL 注入防護

已使用 PDO prepared statements，無需額外處理。

### 4. 檔案大小限制

```php
// 在 export_excel.php 開頭加入
$max_rows = 50000;  // 最多匯出 5 萬筆

// 在查詢時加入
$sql .= " LIMIT {$max_rows}";
```

## 📈 效能優化

### 1. 加入資料庫索引

```sql
-- 為常用查詢欄位加入索引
CREATE INDEX idx_submitted_at ON health_survey(submitted_at);
CREATE INDEX idx_bp_submitted_at ON blood_pressure(submitted_at);
CREATE INDEX idx_lab_created_at ON lab_test(created_at);
CREATE INDEX idx_met_created_at ON metabolic_prevention(created_at);
```

### 2. 使用查詢快取

```php
// 在統計查詢時使用快取
$cache_key = "stats_{$type}";
$cached = apcu_fetch($cache_key);
if ($cached !== false) {
    sendSuccessResponse($cached, '統計資料查詢成功');
    exit;
}
// ... 執行查詢 ...
apcu_store($cache_key, $data, 300);  // 快取 5 分鐘
```

### 3. 非同步載入統計

前端可以使用 Promise.all 同時載入四個統計：

```javascript
async loadStatistics() {
  const promises = [
    fetch('php/get_health_survey.php?type=stats'),
    fetch('php/get_blood_pressure.php?type=stats'),
    fetch('php/get_lab_test.php?type=stats'),
    fetch('php/get_metabolic_prevention.php?type=stats')
  ];
  
  const results = await Promise.all(promises);
  // 處理結果...
}
```

## ✅ 部署檢查清單

- [ ] Composer 已安裝
- [ ] PhpSpreadsheet 已安裝
- [ ] admin.html 已上傳
- [ ] export_excel.php 已上傳
- [ ] get_blood_pressure.php 已上傳
- [ ] 檔案權限已設定
- [ ] 測試健康調查匯出
- [ ] 測試血壓資料匯出
- [ ] 測試檢驗檢查匯出
- [ ] 測試代謝防治匯出
- [ ] 測試日期區間篩選
- [ ] 統計數字顯示正常

## 🎉 完成

後台管理系統安裝完成！現在可以使用以下功能：

1. ✅ 四種資料類型的 Excel 匯出
2. ✅ 靈活的日期區間篩選
3. ✅ 即時統計數字顯示
4. ✅ 匯出歷史記錄
5. ✅ 美觀的使用者介面

如有任何問題，請查看常見問題章節或檢查 PHP 錯誤日誌。
