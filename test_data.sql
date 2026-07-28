
USE Project2_db;
SET NAMES utf8mb4;
START TRANSACTION;

-- -----------------------------------------------------------------------------
-- 1. TEST SUPPLIER
-- -----------------------------------------------------------------------------
INSERT INTO SUPPLIERS (SUP_supplier_name, SUP_contact_info, SUP_origin_facility)
SELECT 'NCC Test - Anna QA', 'qa-test@fngfood.local', 'NM Hai Duong'
WHERE NOT EXISTS (SELECT 1 FROM SUPPLIERS WHERE SUP_supplier_name = 'NCC Test - Anna QA');

-- -----------------------------------------------------------------------------
-- 2. TEST PRODUCTS
-- Yield figures below are NOT invented - they match the real baseline confirmed
-- in the Director interview transcript:
--   - Cucumber (dua chuot): ~90-92% yield (only trims ends/rejects)
--   - Pineapple (dua MD2/Cayenne): ~49-50% yield (peel + core removed)
-- Shelf life: cucumber pickled product batches were referenced around 15-20 days
-- pre-export handling; pineapple similar range. Adjust if your team's PRODUCTS
-- data dictionary specifies an exact confirmed number instead.
-- -----------------------------------------------------------------------------
INSERT INTO PRODUCTS (PRD_product_name, PRD_material_grade, PRD_unit_price, PRD_expected_yield, PRD_shelf_life_days)
SELECT 'Dua chuot bao tu (Test - QA)', 'TEST_DUA_CHUOT', 12000.00, 92.00, 20
WHERE NOT EXISTS (SELECT 1 FROM PRODUCTS WHERE PRD_material_grade = 'TEST_DUA_CHUOT');

INSERT INTO PRODUCTS (PRD_product_name, PRD_material_grade, PRD_unit_price, PRD_expected_yield, PRD_shelf_life_days)
SELECT 'Dua MD2 (Test - QA)', 'TEST_DUA_MD2', 8000.00, 49.16, 15
WHERE NOT EXISTS (SELECT 1 FROM PRODUCTS WHERE PRD_material_grade = 'TEST_DUA_MD2');

INSERT INTO PRODUCTS (PRD_product_name, PRD_material_grade, PRD_unit_price, PRD_expected_yield, PRD_shelf_life_days)
SELECT 'Ngo ngot (Test - QA)', 'TEST_NGO', 9000.00, 85.00, 10
WHERE NOT EXISTS (SELECT 1 FROM PRODUCTS WHERE PRD_material_grade = 'TEST_NGO');

-- -----------------------------------------------------------------------------
-- 3. TEST SHIFT (today, Morning, Open)
-- Using CURDATE() so this stays valid regardless of when the test is run.
-- -----------------------------------------------------------------------------
INSERT INTO SHIFTS (SHF_shift_date, SHF_shift_type, SHF_worker_count, SHF_status)
SELECT CURDATE(), 'Morning', 12, 'Open'
WHERE NOT EXISTS (
    SELECT 1 FROM SHIFTS WHERE SHF_shift_date = CURDATE() AND SHF_shift_type = 'Morning'
);

-- -----------------------------------------------------------------------------
-- 4. BATCHES
-- Reuses the existing 'Kho NVL' storage zone from seed_data.sql (guaranteed to
-- exist since it's inserted unconditionally there).
-- -----------------------------------------------------------------------------

-- 4a. Pending_QC batch #1 - Cucumber, matches interview baseline: 8.4 tan input
--     Used for: TC_QC_02 (queue loads), TC_QC_06 (start inspection),
--     TC_QC_08 (client-side yield calc), TC_QC_10 (submit passing inspection),
--     and FR-05 acceptance test (system yield vs ~92% manual baseline).
INSERT INTO BATCHES (
    BCH_batch_id, BCH_product_id, BCH_supplier_id, BCH_shift_id, BCH_zone_id,
    BCH_received_date, BCH_expiry_date, BCH_priority,
    BCH_initial_volume_kg, BCH_available_stock_kg, BCH_current_stage, BCH_health_status
)
SELECT
    'BCH_TEST_QC_CUCUMBER_01',
    (SELECT PRD_product_id FROM PRODUCTS WHERE PRD_material_grade = 'TEST_DUA_CHUOT' LIMIT 1),
    (SELECT SUP_supplier_id FROM SUPPLIERS WHERE SUP_supplier_name = 'NCC Test - Anna QA' LIMIT 1),
    (SELECT SHF_shift_id FROM SHIFTS WHERE SHF_shift_date = CURDATE() AND SHF_shift_type = 'Morning' LIMIT 1),
    (SELECT STZ_zone_id FROM STORAGE_ZONES WHERE STZ_zone_name = 'Kho NVL' LIMIT 1),
    NOW(), DATE_ADD(NOW(), INTERVAL 20 DAY), 'NORMAL',
    8400.00, 8400.00, 'Pending_QC', 'Good'
WHERE NOT EXISTS (SELECT 1 FROM BATCHES WHERE BCH_batch_id = 'BCH_TEST_QC_CUCUMBER_01');

-- 4b. Pending_QC batch #2 - Pineapple, matches interview baseline: 4,778 kg -> 49.16%
--     Used for: FR-05 acceptance test comparing system yield vs manual Excel (~49%),
--     and as a SECOND queue row so TC_QC_03 (search filter) has >1 row to filter.
INSERT INTO BATCHES (
    BCH_batch_id, BCH_product_id, BCH_supplier_id, BCH_shift_id, BCH_zone_id,
    BCH_received_date, BCH_expiry_date, BCH_priority,
    BCH_initial_volume_kg, BCH_available_stock_kg, BCH_current_stage, BCH_health_status
)
SELECT
    'BCH_TEST_QC_PINEAPPLE_01',
    (SELECT PRD_product_id FROM PRODUCTS WHERE PRD_material_grade = 'TEST_DUA_MD2' LIMIT 1),
    (SELECT SUP_supplier_id FROM SUPPLIERS WHERE SUP_supplier_name = 'NCC Test - Anna QA' LIMIT 1),
    (SELECT SHF_shift_id FROM SHIFTS WHERE SHF_shift_date = CURDATE() AND SHF_shift_type = 'Morning' LIMIT 1),
    (SELECT STZ_zone_id FROM STORAGE_ZONES WHERE STZ_zone_name = 'Kho NVL' LIMIT 1),
    NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 'HIGH',
    4778.00, 4778.00, 'Pending_QC', 'Good'
WHERE NOT EXISTS (SELECT 1 FROM BATCHES WHERE BCH_batch_id = 'BCH_TEST_QC_PINEAPPLE_01');

-- 4c. Near-expiry batch (expires in 24h) - has available stock so it appears on
--     the Production dashboard's "Expiring Batches (48H)" counter AND on the
--     dedicated FEFO page. Used for: TC_PROD_08 (FEFO list), TC_PROD_09
--     (allocation modal max-stock limit).
INSERT INTO BATCHES (
    BCH_batch_id, BCH_product_id, BCH_supplier_id, BCH_shift_id, BCH_zone_id,
    BCH_received_date, BCH_expiry_date, BCH_priority,
    BCH_initial_volume_kg, BCH_available_stock_kg, BCH_current_stage, BCH_health_status
)
SELECT
    'BCH_TEST_FEFO_NEAR48H',
    (SELECT PRD_product_id FROM PRODUCTS WHERE PRD_material_grade = 'TEST_DUA_CHUOT' LIMIT 1),
    (SELECT SUP_supplier_id FROM SUPPLIERS WHERE SUP_supplier_name = 'NCC Test - Anna QA' LIMIT 1),
    (SELECT SHF_shift_id FROM SHIFTS WHERE SHF_shift_date = CURDATE() AND SHF_shift_type = 'Morning' LIMIT 1),
    (SELECT STZ_zone_id FROM STORAGE_ZONES WHERE STZ_zone_name = 'Kho NVL' LIMIT 1),
    DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 1 DAY), 'CRITICAL',
    850.00, 850.00, 'In_Production', 'Warning'
WHERE NOT EXISTS (SELECT 1 FROM BATCHES WHERE BCH_batch_id = 'BCH_TEST_FEFO_NEAR48H');

-- 4d. Already-expired batch (expired yesterday, still has stock, never processed)
--     Used for: FR-06 negative case - "escalates alert to Critical" when a batch
--     expires without being processed. This is the edge case the workbook example
--     explicitly asks for ("test ngay het han trong qua khu").
INSERT INTO BATCHES (
    BCH_batch_id, BCH_product_id, BCH_supplier_id, BCH_shift_id, BCH_zone_id,
    BCH_received_date, BCH_expiry_date, BCH_priority,
    BCH_initial_volume_kg, BCH_available_stock_kg, BCH_current_stage, BCH_health_status
)
SELECT
    'BCH_TEST_EXPIRED_ALREADY',
    (SELECT PRD_product_id FROM PRODUCTS WHERE PRD_material_grade = 'TEST_NGO' LIMIT 1),
    (SELECT SUP_supplier_id FROM SUPPLIERS WHERE SUP_supplier_name = 'NCC Test - Anna QA' LIMIT 1),
    (SELECT SHF_shift_id FROM SHIFTS WHERE SHF_shift_date = CURDATE() AND SHF_shift_type = 'Morning' LIMIT 1),
    (SELECT STZ_zone_id FROM STORAGE_ZONES WHERE STZ_zone_name = 'Kho NVL' LIMIT 1),
    DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 'CRITICAL',
    300.00, 300.00, 'In_Production', 'Critical'
WHERE NOT EXISTS (SELECT 1 FROM BATCHES WHERE BCH_batch_id = 'BCH_TEST_EXPIRED_ALREADY');

-- 4e. Clean allocation batch - round numbers, plenty of stock, normal expiry.
--     Used for: TC_PROD_05 (allocate valid qty), TC_PROD_06 (reject over-stock),
--     TC_PROD_07 (fully consume batch) so results are easy to eyeball (500 -> 0).
INSERT INTO BATCHES (
    BCH_batch_id, BCH_product_id, BCH_supplier_id, BCH_shift_id, BCH_zone_id,
    BCH_received_date, BCH_expiry_date, BCH_priority,
    BCH_initial_volume_kg, BCH_available_stock_kg, BCH_current_stage, BCH_health_status
)
SELECT
    'BCH_TEST_ALLOC_500KG',
    (SELECT PRD_product_id FROM PRODUCTS WHERE PRD_material_grade = 'TEST_DUA_MD2' LIMIT 1),
    (SELECT SUP_supplier_id FROM SUPPLIERS WHERE SUP_supplier_name = 'NCC Test - Anna QA' LIMIT 1),
    (SELECT SHF_shift_id FROM SHIFTS WHERE SHF_shift_date = CURDATE() AND SHF_shift_type = 'Morning' LIMIT 1),
    (SELECT STZ_zone_id FROM STORAGE_ZONES WHERE STZ_zone_name = 'Kho NVL' LIMIT 1),
    NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 'NORMAL',
    500.00, 500.00, 'In_Production', 'Good'
WHERE NOT EXISTS (SELECT 1 FROM BATCHES WHERE BCH_batch_id = 'BCH_TEST_ALLOC_500KG');

COMMIT;

