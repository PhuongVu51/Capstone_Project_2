<?php
// backend/models/StockModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class StockModel extends BaseModel {
    
    // Ví dụ: Lấy danh sách tồn kho
    public function getInventory() {
        $sql = "SELECT * FROM BATCHES";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSuppliersByProduct($productId) {
        $productId = intval($productId);
        $sql = "SELECT * FROM (
                    SELECT DISTINCT s.SUP_supplier_id, s.SUP_supplier_name
                    FROM SUPPLIERS s
                    JOIN PRODUCT_SUPPLIERS ps ON s.SUP_supplier_id = ps.PSP_supplier_id
                    WHERE ps.PSP_product_id = :product_id
                      AND s.SUP_supplier_name NOT IN ('SUP_UNKNOWN', 'Unknown', 'unknown')
                    UNION
                    SELECT DISTINCT s2.SUP_supplier_id, s2.SUP_supplier_name
                    FROM SUPPLIERS s2
                    JOIN BATCHES b ON s2.SUP_supplier_id = b.BCH_supplier_id
                    WHERE b.BCH_product_id = :product_id
                      AND s2.SUP_supplier_name NOT IN ('SUP_UNKNOWN', 'Unknown', 'unknown')
                 ) result
                 ORDER BY SUP_supplier_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll();
    }

    // Nhập kho (Stock in) an toàn chống SQL Injection với Transaction
    public function stockIn($batchId, $productId, $supplierId, $shiftId, $zoneId, $receivedDate, $expiryDate, $initialVolume, $userId) {
        try {
            // Bắt đầu Transaction
            $this->pdo->beginTransaction();

            // 1. Thêm vào BATCHES
            $sqlBatch = "INSERT INTO BATCHES (BCH_batch_id, BCH_product_id, BCH_supplier_id, BCH_shift_id, BCH_zone_id, BCH_received_date, BCH_expiry_date, BCH_initial_volume_kg, BCH_available_stock_kg)
                         VALUES (:batch_id, :product_id, :supplier_id, :shift_id, :zone_id, :received_date, :expiry_date, :initial_volume, :available_stock)";
            $stmtBatch = $this->pdo->prepare($sqlBatch);
            $stmtBatch->execute([
                ':batch_id' => $batchId,
                ':product_id' => $productId,
                ':supplier_id' => $supplierId,
                ':shift_id' => $shiftId,
                ':zone_id' => $zoneId,
                ':received_date' => $receivedDate,
                ':expiry_date' => $expiryDate,
                ':initial_volume' => $initialVolume,
                ':available_stock' => $initialVolume 
            ]);

            if (!empty($supplierId) && $supplierId > 0) {
                $sqlProductSupplier = "INSERT IGNORE INTO PRODUCT_SUPPLIERS (PSP_product_id, PSP_supplier_id)
                                       VALUES (:product_id, :supplier_id)";
                $stmtProductSupplier = $this->pdo->prepare($sqlProductSupplier);
                $stmtProductSupplier->execute([
                    ':product_id' => $productId,
                    ':supplier_id' => $supplierId
                ]);
            }

            // 2. Ghi log vào STOCK_MOVEMENTS
            $referenceCode = 'IN_' . time() . '_' . rand(100, 999); // Sinh mã reference duy nhất
            $sqlMove = "INSERT INTO STOCK_MOVEMENTS (STM_reference_code, STM_batch_id, STM_movement_type, STM_quantity_kg, STM_user_id)
                        VALUES (:ref_code, :batch_id, 'IN', :quantity, :user_id)";
            $stmtMove = $this->pdo->prepare($sqlMove);
            $stmtMove->execute([
                ':ref_code' => $referenceCode,
                ':batch_id' => $batchId,
                ':quantity' => $initialVolume,
                ':user_id' => $userId
            ]);

            // 3. Cập nhật sức chứa của STORAGE_ZONES
            $sqlUpdateZone = "UPDATE STORAGE_ZONES 
                              SET STZ_current_load_kg = STZ_current_load_kg + :added_volume 
                              WHERE STZ_zone_id = :zone_id";
            $stmtUpdateZone = $this->pdo->prepare($sqlUpdateZone);
            $stmtUpdateZone->execute([
                ':added_volume' => $initialVolume,
                ':zone_id' => $zoneId
            ]);

            // Nếu tất cả thành công, Commit transaction
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            // Nếu có lỗi ở bất kỳ bước nào, Rollback lại toàn bộ
            $this->pdo->rollBack();
            // Log lỗi nếu cần: error_log($e->getMessage());
            return false;
        }
    }

    // Xuất kho (Stock out) an toàn chống SQL Injection với Transaction
    public function stockOut($batchId, $outVolume, $userId) {
        try {
            $this->pdo->beginTransaction();

            // 1. Kiểm tra tồn kho hiện tại
            $sqlCheck = "SELECT BCH_available_stock_kg, BCH_zone_id FROM BATCHES WHERE BCH_batch_id = :batch_id FOR UPDATE";
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute([':batch_id' => $batchId]);
            $batch = $stmtCheck->fetch();

            if (!$batch || $batch['BCH_available_stock_kg'] < $outVolume) {
                $this->pdo->rollBack();
                return false; // Không đủ tồn kho hoặc không tìm thấy lô
            }

            // 2. Trừ số lượng trong BATCHES
            $sqlUpdateBatch = "UPDATE BATCHES SET BCH_available_stock_kg = BCH_available_stock_kg - :out_volume WHERE BCH_batch_id = :batch_id";
            $stmtUpdateBatch = $this->pdo->prepare($sqlUpdateBatch);
            $stmtUpdateBatch->execute([
                ':out_volume' => $outVolume,
                ':batch_id' => $batchId
            ]);

            // 3. Ghi log vào STOCK_MOVEMENTS
            $referenceCode = 'OUT_' . time() . '_' . rand(100, 999);
            $sqlMove = "INSERT INTO STOCK_MOVEMENTS (STM_reference_code, STM_batch_id, STM_movement_type, STM_quantity_kg, STM_user_id)
                        VALUES (:ref_code, :batch_id, 'OUT', :quantity, :user_id)";
            $stmtMove = $this->pdo->prepare($sqlMove);
            $stmtMove->execute([
                ':ref_code' => $referenceCode,
                ':batch_id' => $batchId,
                ':quantity' => $outVolume,
                ':user_id' => $userId
            ]);

            // 4. Cập nhật sức chứa kho STORAGE_ZONES
            $sqlUpdateZone = "UPDATE STORAGE_ZONES SET STZ_current_load_kg = STZ_current_load_kg - :out_volume WHERE STZ_zone_id = :zone_id";
            $stmtUpdateZone = $this->pdo->prepare($sqlUpdateZone);
            $stmtUpdateZone->execute([
                ':out_volume' => $outVolume,
                ':zone_id' => $batch['BCH_zone_id']
            ]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // Sửa thông tin (Update) - Chuyển vùng chứa hoặc gia hạn ngày
    public function updateBatch($batchId, $newZoneId, $newExpiryDate, $userId) {
        try {
            $this->pdo->beginTransaction();

            // 1. Lấy thông tin hiện tại của Batch
            $sqlCheck = "SELECT BCH_zone_id, BCH_available_stock_kg FROM BATCHES WHERE BCH_batch_id = :batch_id FOR UPDATE";
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute([':batch_id' => $batchId]);
            $batch = $stmtCheck->fetch();

            if (!$batch) {
                $this->pdo->rollBack();
                return false;
            }

            $oldZoneId = $batch['BCH_zone_id'];
            $stockVolume = $batch['BCH_available_stock_kg'];

            // 2. Nếu thay đổi khu vực kho (Zone) -> Cập nhật sức chứa
            if ($oldZoneId != $newZoneId) {
                // Trừ ở kho cũ
                $sqlSubOldZone = "UPDATE STORAGE_ZONES SET STZ_current_load_kg = STZ_current_load_kg - :stock WHERE STZ_zone_id = :old_zone";
                $stmtSubOldZone = $this->pdo->prepare($sqlSubOldZone);
                $stmtSubOldZone->execute([':stock' => $stockVolume, ':old_zone' => $oldZoneId]);

                // Cộng vào kho mới
                $sqlAddNewZone = "UPDATE STORAGE_ZONES SET STZ_current_load_kg = STZ_current_load_kg + :stock WHERE STZ_zone_id = :new_zone";
                $stmtAddNewZone = $this->pdo->prepare($sqlAddNewZone);
                $stmtAddNewZone->execute([':stock' => $stockVolume, ':new_zone' => $newZoneId]);

                // Log vào hệ thống dưới dạng ADJUSTMENT
                $referenceCode = 'ADJ_' . time() . '_' . rand(100, 999);
                $sqlMove = "INSERT INTO STOCK_MOVEMENTS (STM_reference_code, STM_batch_id, STM_movement_type, STM_quantity_kg, STM_user_id)
                            VALUES (:ref_code, :batch_id, 'ADJUSTMENT', 0, :user_id)";
                $stmtMove = $this->pdo->prepare($sqlMove);
                $stmtMove->execute([
                    ':ref_code' => $referenceCode,
                    ':batch_id' => $batchId,
                    ':user_id' => $userId
                ]);
            }

            // 3. Cập nhật BATCHES
            $sqlUpdateBatch = "UPDATE BATCHES SET BCH_zone_id = :new_zone, BCH_expiry_date = :new_expiry WHERE BCH_batch_id = :batch_id";
            $stmtUpdateBatch = $this->pdo->prepare($sqlUpdateBatch);
            $stmtUpdateBatch->execute([
                ':new_zone' => $newZoneId,
                ':new_expiry' => $newExpiryDate,
                ':batch_id' => $batchId
            ]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
?>
