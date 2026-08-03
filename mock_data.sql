USE Project2_db;

-- 1. Cập nhật giá tiền (PRD_unit_price) cho các sản phẩm
UPDATE PRODUCTS SET PRD_unit_price = 15000 WHERE PRD_product_name LIKE '%Dứa%';
UPDATE PRODUCTS SET PRD_unit_price = 12000 WHERE PRD_product_name LIKE '%Dưa%';
UPDATE PRODUCTS SET PRD_unit_price = 18000 WHERE PRD_product_name LIKE '%Ngô%';
UPDATE PRODUCTS SET PRD_unit_price = 10000 WHERE PRD_unit_price = 0 OR PRD_unit_price IS NULL;

-- 2. Đưa TẤT CẢ ngày nhập lô hàng (BCH_received_date) về ngẫu nhiên trong 30 ngày gần nhất
-- Phân bổ đều dữ liệu để biểu đồ đường (Trend) có nhiều biến động lên xuống liên tục
UPDATE BATCHES 
SET BCH_received_date = DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 30) DAY);

-- 3. Tạo dữ liệu hao hụt ngẫu nhiên theo từng mức độ để biểu đồ và Scorecard hiển thị đủ màu (Xanh, Vàng, Đỏ)
-- Nhóm 1: Các lô hàng TỐT (Màu xanh - Hao hụt cực thấp 0-2%) - Chiếm phần lớn (~50%)
UPDATE QC_INSPECTIONS q JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
SET 
    q.QCI_rotten_weight_kg = ROUND(b.BCH_initial_volume_kg * (RAND() * 0.02), 2),
    q.QCI_natural_loss_weight_kg = ROUND(b.BCH_initial_volume_kg * (RAND() * 0.01), 2),
    q.QCI_actual_yield_pct = FLOOR(RAND() * (100 - 95 + 1)) + 95,
    q.QCI_rejection_reason = 'Không đáng kể'
WHERE q.QCI_inspection_id % 2 = 0;

-- Nhóm 2: Các lô hàng CẦN THEO DÕI (Màu vàng - Hao hụt trung bình 5-15%) - Chiếm ~30%
UPDATE QC_INSPECTIONS q JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
SET 
    q.QCI_rotten_weight_kg = ROUND(b.BCH_initial_volume_kg * (0.05 + RAND() * 0.10), 2),
    q.QCI_natural_loss_weight_kg = ROUND(b.BCH_initial_volume_kg * (0.01 + RAND() * 0.03), 2),
    q.QCI_actual_yield_pct = FLOOR(RAND() * (90 - 75 + 1)) + 75,
    q.QCI_rejection_reason = ELT(FLOOR(RAND() * 2) + 1, 'Sai quy cách', 'Dập nát')
WHERE q.QCI_inspection_id % 3 = 0 AND q.QCI_inspection_id % 2 != 0;

-- Nhóm 3: Các lô hàng CẢNH BÁO / TỒI TỆ (Màu đỏ - Hao hụt cực cao 20-45%) - Chiếm ~20%
UPDATE QC_INSPECTIONS q JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
SET 
    q.QCI_rotten_weight_kg = ROUND(b.BCH_initial_volume_kg * (0.20 + RAND() * 0.25), 2),
    q.QCI_natural_loss_weight_kg = ROUND(b.BCH_initial_volume_kg * (0.02 + RAND() * 0.05), 2),
    q.QCI_actual_yield_pct = FLOOR(RAND() * (70 - 45 + 1)) + 45,
    q.QCI_rejection_reason = ELT(FLOOR(RAND() * 2) + 1, 'Mốc / Lên men', 'Sâu bệnh')
WHERE q.QCI_inspection_id % 2 != 0 AND q.QCI_inspection_id % 3 != 0;

-- 4. Cuối cùng, cập nhật đồng loạt Lượng dùng được (Usable) = Tổng - Hỏng - Hao hụt tự nhiên
UPDATE QC_INSPECTIONS q JOIN BATCHES b ON q.QCI_batch_id = b.BCH_batch_id
SET q.QCI_usable_weight_kg = ROUND(b.BCH_initial_volume_kg - q.QCI_rotten_weight_kg - q.QCI_natural_loss_weight_kg, 2);
