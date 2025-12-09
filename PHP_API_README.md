# PHP 後端 API 說明文件

## 📁 檔案清單

### 核心檔案
1. **config.php** - 資料庫連線設定（需要從現有專案複製）
2. **save_metabolic_prevention.php** - 代謝防治資料儲存
3. **get_metabolic_prevention.php** - 代謝防治資料查詢
4. **save_lab_test.php** - 檢驗檢查資料儲存
5. **get_lab_test.php** - 檢驗檢查資料查詢
6. **manage_doctors.php** - 醫師資料管理

---

## 🔧 安裝步驟

### 1. 建立資料庫表格
執行 SQL 檔案建立所需的資料表：
```bash
mysql -u root -p data_collection_system < 代謝防治與檢驗檢查表格.sql
```

### 2. 部署 PHP 檔案
將所有 PHP 檔案放置到網站的 `php/` 目錄下：
```
your-website/
├── php/
│   ├── config.php (已存在)
│   ├── save_metabolic_prevention.php (新增)
│   ├── get_metabolic_prevention.php (新增)
│   ├── save_lab_test.php (新增)
│   ├── get_lab_test.php (新增)
│   └── manage_doctors.php (新增)
├── metabolic_prevention.html
├── lab_test.html
└── ...
```

### 3. 確認 config.php 設定
確保 `config.php` 包含以下必要函數：
- `getDBConnection()` - 資料庫連線
- `sendErrorResponse()` - 錯誤回應
- `sendSuccessResponse()` - 成功回應
- `getClientIP()` - 取得客戶端 IP

---

## 📡 API 使用說明

## 一、代謝防治 API

### 1. 儲存代謝防治資料
**端點**: `POST php/save_metabolic_prevention.php`

**請求範例**:
```javascript
fetch('php/save_metabolic_prevention.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    // 第一頁：基本資料
    idNumber: 'A123456789',
    birthDate: '1958-05-15',
    name: '王小明',
    gender: '0',  // 0=男, 1=女
    caseDate: '2024-01-15',
    
    // 第二頁：危險因子
    smoking: '1',     // 1=無, 2=偶爾, 3=10支以下, 4=10支以上
    betelNut: '1',    // 1=無, 2=偶爾, 3=經常
    exercise: '3',    // 1=無, 2=偶爾, 3=經常
    diseases: ['2', '3'],  // 伴隨疾病（複選）
    diseaseOther: '',      // 其他疾病說明
    
    // 第三頁：檢查資料
    checkDate: '2024-01-15',
    height: 170,
    weight: 70,
    waist: 88,
    systolicBP: 135,
    diastolicBP: 85,
    bpSource: '1',        // 0=非診間, 1=診間
    bpMedicine: '1',      // 0=無, 1=有
    sugarMedicine: '0',
    lipidMedicine: '0',
    fastingGlucose: 110.5,
    triglyceride: 150.2,
    hdl: 45.0,
    ldl: 120.0,
    hba1c: 6.5,
    totalCholesterol: 200.0
  })
})
```

**成功回應**:
```json
{
  "success": true,
  "message": "代謝防治資料儲存成功",
  "data": {
    "person_id": 1,
    "metabolic_id": 123,
    "bmi": 24.22,
    "bmi_status": "正常範圍",
    "bp_status": "血壓偏高"
  }
}
```

### 2. 查詢代謝防治資料
**端點**: `GET php/get_metabolic_prevention.php`

**查詢參數**:
- `type` - 查詢類型：
  - `by_id` - 根據身分證查詢（需提供 `id_number`）
  - `stats` - 統計資料
  - `risk_analysis` - 風險分析（需提供 `risk_type`: smoking/betel_nut/exercise）
  - `recent` - 最近記錄（可提供 `limit`，預設10筆）
  - `all` - 所有記錄（分頁，可提供 `limit` 和 `offset`）

**範例 1: 根據身分證查詢**
```javascript
fetch('php/get_metabolic_prevention.php?type=by_id&id_number=A123456789')
```

**範例 2: 查詢統計資料**
```javascript
fetch('php/get_metabolic_prevention.php?type=stats')
```

**範例 3: 風險分析**
```javascript
fetch('php/get_metabolic_prevention.php?type=risk_analysis&risk_type=smoking')
```

---

## 二、檢驗檢查 API

### 1. 儲存檢驗檢查資料
**端點**: `POST php/save_lab_test.php`

**請求範例**:
```javascript
fetch('php/save_lab_test.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    // 基本資料
    idNumber: 'A123456789',
    birthDate: '1958-05-15',
    cardDate: '2024-01-15',
    visitNumber: 'A001',  // 4碼
    doctorId: 'D123456789',
    
    // 單項檢驗（動態陣列）
    singleTests: [
      {
        testCode: '09005C',
        result: '110'
      },
      {
        testCode: '14065C',
        result: '陰性'
      },
      {
        testCode: 'other',
        customCode: 'CUSTOM01',
        result: '正常'
      }
    ],
    
    // 尿液檢查（06012C）
    urineTest: {
      appearance: '清澈',
      color: '淡黃色',
      reaction: '6.0',
      glucose: '-',
      occultBlood: '-',
      protein: '-',
      urobilinogen: '-',
      nitrite: '-',
      leukocyte: '-',
      bilirubin: '-',
      ketoneBody: '-',
      specificGravi: '1.010',
      rbc: '0-2',
      wbc: '0-2',
      clarity: '清澈'
    },
    
    // 血液檢查（08012C）
    bloodTest: {
      wbc: '7500',
      rbc: '4.5',
      hb: '14.5',
      hct: '42',
      mch: '28',
      mcv: '90',
      mchc: '33'
    }
  })
})
```

**成功回應**:
```json
{
  "success": true,
  "message": "檢驗資料上傳成功",
  "data": {
    "person_id": 1,
    "test_id": 456,
    "total_items": 5,
    "single_test_count": 3,
    "urine_test_items": 15,
    "blood_test_items": 7
  }
}
```

### 2. 查詢檢驗檢查資料
**端點**: `GET php/get_lab_test.php`

**查詢參數**:
- `type` - 查詢類型：
  - `by_id` - 根據身分證查詢（需提供 `id_number`）
  - `by_test_id` - 根據檢驗ID查詢完整資料（需提供 `test_id`）
  - `stats` - 統計資料
  - `by_doctor` - 根據醫師查詢（需提供 `doctor_id`）
  - `recent` - 最近記錄（可提供 `limit`）
  - `all` - 所有記錄（分頁）

**範例 1: 根據身分證查詢**
```javascript
fetch('php/get_lab_test.php?type=by_id&id_number=A123456789')
```

**範例 2: 查詢完整檢驗資料**
```javascript
fetch('php/get_lab_test.php?type=by_test_id&test_id=456')
```

**範例 3: 根據醫師查詢**
```javascript
fetch('php/get_lab_test.php?type=by_doctor&doctor_id=D123456789')
```

---

## 三、醫師管理 API

### 1. 查詢醫師清單
**端點**: `GET php/manage_doctors.php`

**查詢參數**:
- `status` - 狀態：`active`（預設）、`inactive`、`all`

**範例**:
```javascript
fetch('php/manage_doctors.php?status=active')
```

### 2. 新增醫師
**端點**: `POST php/manage_doctors.php`

**請求範例**:
```javascript
fetch('php/manage_doctors.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    idNumber: 'D123456789',
    name: '王醫師',
    specialty: '內科',
    phone: '04-12345678',
    email: 'doctor@example.com',
    status: 'active'
  })
})
```

### 3. 更新醫師資料
**端點**: `PUT php/manage_doctors.php`

**請求範例**:
```javascript
fetch('php/manage_doctors.php', {
  method: 'PUT',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    doctorId: 1,
    name: '王醫師',
    specialty: '心臟內科',
    phone: '04-12345678',
    email: 'doctor@example.com',
    status: 'active'
  })
})
```

### 4. 停用醫師
**端點**: `DELETE php/manage_doctors.php?doctor_id=1`

---

## ⚠️ 錯誤處理

所有 API 在發生錯誤時會回傳以下格式：
```json
{
  "success": false,
  "message": "錯誤訊息說明"
}
```

常見錯誤碼：
- `400` - 請求參數錯誤
- `404` - 資料不存在
- `405` - 請求方法不支援
- `500` - 伺服器內部錯誤

---

## 🔒 安全性建議

1. **啟用 HTTPS** - 所有 API 應透過 HTTPS 傳輸
2. **驗證使用者身份** - 在 config.php 中加入 session 驗證
3. **SQL 注入防護** - 已使用 PDO prepared statements
4. **輸入驗證** - 所有欄位都有基本驗證，可視需求加強
5. **日誌記錄** - 錯誤已記錄到 error_log，可設定儲存位置

---

## 📊 資料庫關聯

### 代謝防治表 (metabolic_prevention)
- 關聯 `personal_info` (person_id) - 個人基本資料
- 關聯 `users` (user_id) - 填寫人員（選填）

### 檢驗檢查主表 (lab_test)
- 關聯 `personal_info` (person_id) - 個人基本資料
- 關聯 `users` (user_id) - 填寫人員（選填）
- 關聯 `doctors` (doctor_id) - 醫師資料

### 檢驗檢查子表
- `lab_test_single_items` - 單項檢驗（一對多）
- `lab_test_urine` - 尿液檢查（一對一）
- `lab_test_blood` - 血液檢查（一對一）

---

## 🎯 前端整合範例

### 代謝防治表單整合
在 `metabolic_prevention.html` 的 `submitForm` 方法中：

```javascript
async submitForm() {
  if (!this.validateCurrentPage()) {
    return;
  }

  this.isSubmitting = true;
  try {
    const response = await fetch('php/save_metabolic_prevention.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(this.formData)
    });

    const result = await response.json();
    if (result.success) {
      this.submitted = true;
      setTimeout(() => {
        window.location.href = 'index.html';
      }, 2000);
    } else {
      alert('送出失敗:' + (result.message || '未知錯誤'));
    }
  } catch (error) {
    console.error('送出錯誤:', error);
    alert('送出失敗,請稍後再試');
  } finally {
    this.isSubmitting = false;
  }
}
```

### 檢驗檢查表單整合
在 `lab_test.html` 的 `handleSubmit` 方法中：

```javascript
async handleSubmit() {
  if (this.validateForm()) {
    try {
      const response = await fetch('php/save_lab_test.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(this.formData)
      });

      const result = await response.json();
      if (result.success) {
        console.log('儲存成功：', result);
        this.submitted = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        alert('儲存失敗：' + result.message);
      }
    } catch (error) {
      console.error('送出錯誤：', error);
      alert('資料送出時發生錯誤，請稍後再試');
    }
  }
}
```

---

## 📝 測試建議

### 1. 單元測試
使用 Postman 或 curl 測試每個 API 端點

### 2. 整合測試
測試前端表單完整流程：
1. 填寫表單
2. 送出資料
3. 查詢資料驗證

### 3. 壓力測試
測試同時多筆資料上傳的效能

---

## 🐛 問題排查

### 資料無法儲存
1. 檢查資料庫連線設定
2. 確認資料表是否建立完成
3. 查看 PHP error_log

### 查詢無資料
1. 確認資料是否已成功儲存
2. 檢查查詢參數是否正確
3. 驗證資料庫外鍵關聯

### 前端無法連接後端
1. 確認 PHP 檔案路徑正確
2. 檢查 CORS 設定
3. 查看瀏覽器 Console 錯誤訊息

---

## 📞 技術支援

如有問題請檢查：
1. PHP 版本 >= 7.4
2. MySQL 版本 >= 5.7
3. PDO 擴展已啟用
4. JSON 擴展已啟用
