SET NAMES utf8mb4;
-- ==============================================================================
-- 1. TẠO CÁC BẢNG MASTER
-- ==============================================================================

CREATE TABLE USERS (
    USR_user_id INT AUTO_INCREMENT PRIMARY KEY,
    USR_username VARCHAR(50) NOT NULL UNIQUE,
    USR_password_hash VARCHAR(255) NOT NULL,
    USR_role ENUM('Director', 'QC', 'Warehouse_Staff', 'Production_Manager') NOT NULL,
    USR_full_name VARCHAR(100) NOT NULL,
    USR_is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE SUPPLIERS (
    SUP_supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    SUP_supplier_name VARCHAR(150) NOT NULL,
    SUP_contact_info VARCHAR(255),
    SUP_origin_facility VARCHAR(100)
) ENGINE=InnoDB;

CREATE TABLE PRODUCTS (
    PRD_product_id INT AUTO_INCREMENT PRIMARY KEY,
    PRD_product_name VARCHAR(100) NOT NULL,
    PRD_material_grade VARCHAR(50), 
    PRD_unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRD_expected_yield DECIMAL(5,2) NOT NULL, 
    PRD_shelf_life_days INT NOT NULL,
    PRD_image_url VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE SHIFTS (
    SHF_shift_id INT AUTO_INCREMENT PRIMARY KEY,
    SHF_shift_date DATE NOT NULL,
    SHF_shift_type ENUM('Morning', 'Afternoon', 'Overtime') NOT NULL,
    SHF_worker_count INT,
    SHF_status ENUM('Open', 'Closed') DEFAULT 'Open'
) ENGINE=InnoDB;

-- BẢNG MỚI: Quản lý sức chứa và môi trường kho (Từ UI Node Status)
CREATE TABLE STORAGE_ZONES (
    STZ_zone_id INT AUTO_INCREMENT PRIMARY KEY,
    STZ_zone_name VARCHAR(100) NOT NULL,
    STZ_max_capacity_kg DECIMAL(10,2) NOT NULL,
    STZ_current_load_kg DECIMAL(10,2) DEFAULT 0.00,
    STZ_current_temp_c DECIMAL(5,2),
    STZ_current_humidity_pct DECIMAL(5,2)
) ENGINE=InnoDB;

CREATE TABLE PRODUCT_SUPPLIERS (
    PSP_product_id INT NOT NULL,
    PSP_supplier_id INT NOT NULL,
    PRIMARY KEY (PSP_product_id, PSP_supplier_id),
    FOREIGN KEY (PSP_product_id) REFERENCES PRODUCTS(PRD_product_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (PSP_supplier_id) REFERENCES SUPPLIERS(SUP_supplier_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============================================================================
-- 2. TẠO BẢNG CỐT LÕI 
-- ==============================================================================

CREATE TABLE BATCHES (
    BCH_batch_id VARCHAR(50) PRIMARY KEY, 
    BCH_product_id INT NOT NULL,
    BCH_supplier_id INT NOT NULL,
    BCH_shift_id INT NOT NULL,
    BCH_zone_id INT NOT NULL, -- Khóa ngoại mới trỏ về khu vực kho
    BCH_received_date DATETIME NOT NULL,
    BCH_expiry_date DATETIME NOT NULL,
    BCH_priority ENUM('LOW', 'NORMAL', 'HIGH', 'CRITICAL') DEFAULT 'NORMAL', 
    BCH_initial_volume_kg DECIMAL(10,2) NOT NULL,
    BCH_available_stock_kg DECIMAL(10,2) NOT NULL,
    BCH_current_stage VARCHAR(50) DEFAULT 'Pending_QC',
    BCH_health_status ENUM('Good', 'Warning', 'Critical') DEFAULT 'Good',
    
    FOREIGN KEY (BCH_product_id) REFERENCES PRODUCTS(PRD_product_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (BCH_supplier_id) REFERENCES SUPPLIERS(SUP_supplier_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (BCH_shift_id) REFERENCES SHIFTS(SHF_shift_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (BCH_zone_id) REFERENCES STORAGE_ZONES(STZ_zone_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============================================================================
-- 3. TẠO CÁC BẢNG CHI TIẾT & NHẬT KÝ (LOGS)
-- ==============================================================================

-- BẢNG MỚI: Nhật ký xuất nhập tồn (Từ UI "Destination Table: stock_movements")
CREATE TABLE STOCK_MOVEMENTS (
    STM_movement_id INT AUTO_INCREMENT PRIMARY KEY,
    STM_reference_code VARCHAR(100) UNIQUE, -- VD: STOCK_MVMT_44109
    STM_batch_id VARCHAR(50) NOT NULL,
    STM_movement_type ENUM('IN', 'OUT', 'ADJUSTMENT') NOT NULL,
    STM_quantity_kg DECIMAL(10,2) NOT NULL,
    STM_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    STM_user_id INT NOT NULL,
    
    FOREIGN KEY (STM_batch_id) REFERENCES BATCHES(BCH_batch_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (STM_user_id) REFERENCES USERS(USR_user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE MATERIAL_ALLOCATIONS (
    ALC_allocation_id INT AUTO_INCREMENT PRIMARY KEY,
    ALC_batch_id VARCHAR(50) NOT NULL,
    ALC_user_id INT NOT NULL,
    ALC_allocated_quantity_kg DECIMAL(10,2) NOT NULL,
    ALC_production_line VARCHAR(100) NOT NULL,
    ALC_allocation_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ALC_batch_id) REFERENCES BATCHES(BCH_batch_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (ALC_user_id) REFERENCES USERS(USR_user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE QC_INSPECTIONS (
    QCI_inspection_id INT AUTO_INCREMENT PRIMARY KEY,
    QCI_batch_id VARCHAR(50) NOT NULL,
    QCI_user_id INT NOT NULL,
    QCI_rotten_weight_kg DECIMAL(10,2) DEFAULT 0.00,
    QCI_natural_loss_weight_kg DECIMAL(10,2) DEFAULT 0.00,
    QCI_usable_weight_kg DECIMAL(10,2) NOT NULL,
    QCI_actual_yield_pct DECIMAL(5,2) NOT NULL,
    QCI_rejection_reason VARCHAR(255), 
    QCI_inspector_comments TEXT,       
    QCI_visual_record_url VARCHAR(255),
    QCI_destination VARCHAR(100),      
    
    FOREIGN KEY (QCI_batch_id) REFERENCES BATCHES(BCH_batch_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (QCI_user_id) REFERENCES USERS(USR_user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE FINISHED_GOODS (
    FGD_fg_id INT AUTO_INCREMENT PRIMARY KEY,
    FGD_batch_id VARCHAR(50) NOT NULL,
    FGD_shift_id INT NOT NULL,
    FGD_produced_date DATE NOT NULL,
    FGD_total_cans INT NOT NULL,
    FGD_kg_per_can DECIMAL(5,2) NOT NULL,
    FGD_actual_yield_rate DECIMAL(5,2),
    FGD_quarantine_end_date DATE NOT NULL,
    FGD_status ENUM('Quarantine', 'Ready_To_Export', 'Exported') DEFAULT 'Quarantine',
    
    FOREIGN KEY (FGD_batch_id) REFERENCES BATCHES(BCH_batch_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (FGD_shift_id) REFERENCES SHIFTS(SHF_shift_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE SYSTEM_AUDIT_LOGS (
    LOG_log_id INT AUTO_INCREMENT PRIMARY KEY,
    LOG_user_id INT NOT NULL,
    LOG_action VARCHAR(50) NOT NULL,
    LOG_table_name VARCHAR(50) NOT NULL,
    LOG_record_id VARCHAR(50) NOT NULL,
    LOG_old_value TEXT,
    LOG_new_value TEXT,
    LOG_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (LOG_user_id) REFERENCES USERS(USR_user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE MATERIAL_REQUESTS (
    REQ_id INT AUTO_INCREMENT PRIMARY KEY,
    REQ_material_id VARCHAR(50) NOT NULL, -- Khớp với mã vật tư như 'RM01'
    REQ_quantity DECIMAL(10,2) NOT NULL,
    REQ_needed_date DATE NOT NULL,
    REQ_priority VARCHAR(20) DEFAULT 'Normal',
    REQ_notes TEXT,
    REQ_status VARCHAR(20) DEFAULT 'Pending',
    REQ_requested_by INT NOT NULL, -- Khớp với ID user đang đăng nhập
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO USERS (USR_username, USR_password_hash, USR_role, USR_full_name, USR_is_active) VALUES
('pm_alex', '$2y$10$nOUIs5kJ7naTuTFkMD1Ze.pRExhw0qEEyEHQ0QOczzN/z4N1iUOWK', 'Production_Manager', 'Alex Rivera', 1),
('pm_sarah', '$2y$10$nOUIs5kJ7naTuTFkMD1Ze.pRExhw0qEEyEHQ0QOczzN/z4N1iUOWK', 'Production_Manager', 'Sarah Connor', 1),
('pm_david', '$2y$10$nOUIs5kJ7naTuTFkMD1Ze.pRExhw0qEEyEHQ0QOczzN/z4N1iUOWK', 'Production_Manager', 'David Kim', 1),
('nhung_thuy', '$2y$10$nOUIs5kJ7naTuTFkMD1Ze.pRExhw0qEEyEHQ0QOczzN/z4N1iUOWK', 'QC', 'Nhung Thủy', 1),
('qc_anna', '$2y$10$nOUIs5kJ7naTuTFkMD1Ze.pRExhw0qEEyEHQ0QOczzN/z4N1iUOWK', 'QC', 'Anna Smith', 1),
('qc_john', '$2y$10$nOUIs5kJ7naTuTFkMD1Ze.pRExhw0qEEyEHQ0QOczzN/z4N1iUOWK', 'QC', 'John Doe', 1),
('wh_admin04', '$2y$10$nOUIs5kJ7naTuTFkMD1Ze.pRExhw0qEEyEHQ0QOczzN/z4N1iUOWK', 'Warehouse_Staff', 'System Admin 04', 1),
('wh_mike', '$2y$10$nOUIs5kJ7naTuTFkMD1Ze.pRExhw0qEEyEHQ0QOczzN/z4N1iUOWK', 'Warehouse_Staff', 'Mike Johnson', 1),
('wh_lisa', '$2y$10$nOUIs5kJ7naTuTFkMD1Ze.pRExhw0qEEyEHQ0QOczzN/z4N1iUOWK', 'Warehouse_Staff', 'Lisa Wong', 1);
