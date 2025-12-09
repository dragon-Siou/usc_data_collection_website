-- ============================================
-- 修改 personal_info 表格，允許姓名為 NULL
-- ============================================

USE data_collection_system;

-- 修改 name 欄位，允許 NULL
ALTER TABLE personal_info 
MODIFY COLUMN name VARCHAR(100) NULL COMMENT '姓名';

-- 查看修改後的表格結構
DESCRIBE personal_info;
