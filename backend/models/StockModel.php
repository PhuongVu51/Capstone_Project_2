<?php
// backend/models/StockModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class StockModel extends BaseModel {
    private function getCurrentShiftType() {
        $hour = (int) date('G');

        if ($hour >= 6 && $hour < 14) {
            return 'Morning';
        }

        if ($hour >= 14 && $hour < 22) {
            return 'Afternoon';
        }

        return 'Overtime';
    }

    private function getCurrentOpenShiftId() {
        require_once __DIR__ . '/ShiftModel.php';
        $shiftModel = new ShiftModel();
        $shift = $shiftModel->getRealTimeShift();
        return $shift ? (int) $shift['SHF_shift_id'] : null;
    }

    public function getProductShelfLife($productId) {
        $stmt = $this->pdo->prepare("SELECT PRD_shelf_life_days FROM PRODUCTS WHERE PRD_product_id = :pid");
        $stmt->execute([':pid' => (int) $productId]);
        $days = $stmt->fetchColumn();
        return ($days && intval($days) > 0) ? intval($days) : 180;
    }

    private function isShiftOpen($shiftId) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM SHIFTS
             WHERE SHF_shift_id = :shift_id
               AND SHF_status = 'Open'"
        );
        $stmt->execute([':shift_id' => (int) $shiftId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function batchHasClosedShiftMovement($batchId) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM STOCK_MOVEMENTS sm
             JOIN BATCHES b ON sm.STM_batch_id = b.BCH_batch_id
             JOIN SHIFTS s ON s.SHF_shift_id = COALESCE(sm.STM_shift_id, b.BCH_shift_id)
             WHERE sm.STM_batch_id = :batch_id
               AND s.SHF_status = 'Closed'"
        );
        $stmt->execute([':batch_id' => $batchId]);

        return (int) $stmt->fetchColumn() > 0;
    }
    
    private function buildSearchConditions($search, &$params) {
        if ($search === '') {
            return '';
        }

        $searchClean = trim($search);
        $searchLower = mb_strtolower($searchClean);
        $escaped = preg_quote($searchLower, '/');

        $params[':search_like'] = '%' . $searchLower . '%';
        $params[':search_regex'] = '[[:<:]]' . $escaped . '[[:>:]]';

        return "(
            LOWER(b.BCH_batch_id) COLLATE utf8mb4_bin LIKE :search_like
            OR LOWER(p.PRD_product_name) COLLATE utf8mb4_bin REGEXP :search_regex
            OR LOWER(COALESCE(p.PRD_product_name_en, '')) COLLATE utf8mb4_bin REGEXP :search_regex
        )";
    }

    // Lấy danh sách tồn kho với phân trang và lọc
    public function getInventoryList($search = '', $statusFilter = '', $offset = 0, $perPage = 10) {
        $conditions = [];
        $params = [];

        $searchCondition = $this->buildSearchConditions($search, $params);
        if ($searchCondition !== '') {
            $conditions[] = $searchCondition;
        }

        if ($statusFilter !== '') {
            $conditions[] = "(CASE WHEN b.BCH_available_stock_kg <= 0 THEN 'Out of Stock' WHEN b.BCH_available_stock_kg < 100 THEN 'Low Stock' ELSE 'In Stock' END = :status)";
            $params[':status'] = $statusFilter;
        }

        $whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $lang = $_SESSION['lang'] ?? 'vi';
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        $zoneNameCol = ($lang === 'en') ? 'COALESCE(z.STZ_zone_name_en, z.STZ_zone_name)' : 'z.STZ_zone_name';

        $sql = "SELECT b.BCH_batch_id,
                       $productNameCol AS PRD_product_name,
                       p.PRD_material_grade,
                       b.BCH_initial_volume_kg,
                       b.BCH_available_stock_kg,
                       b.BCH_current_stage,
                       b.BCH_health_status,
                       b.BCH_received_date,
                       b.BCH_expiry_date,
                       $zoneNameCol AS STZ_zone_name,
                       CASE
                           WHEN b.BCH_available_stock_kg <= 0 THEN 'Out of Stock'
                           WHEN b.BCH_available_stock_kg < 100 THEN 'Low Stock'
                           ELSE 'In Stock'
                       END AS stock_status
                FROM BATCHES b
                LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                LEFT JOIN STORAGE_ZONES z ON b.BCH_zone_id = z.STZ_zone_id
                $whereSql
                ORDER BY b.BCH_received_date DESC
                LIMIT :offset, :perPage";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', (int)$perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $items = $stmt->fetchAll();
        if ($lang === 'en' && !empty($items)) {
            foreach ($items as &$it) {
                if (function_exists('translate_product_name')) {
                    $it['PRD_product_name'] = translate_product_name($it['PRD_product_name']);
                }
                if (function_exists('translate_zone_name')) {
                    $it['STZ_zone_name'] = translate_zone_name($it['STZ_zone_name']);
                }
            }
        }
        return $items;
    }

    public function getInventoryCount($search = '', $statusFilter = '') {
        $conditions = [];
        $params = [];

        $searchCondition = $this->buildSearchConditions($search, $params);
        if ($searchCondition !== '') {
            $conditions[] = $searchCondition;
        }

        if ($statusFilter !== '') {
            $conditions[] = "(CASE WHEN b.BCH_available_stock_kg <= 0 THEN 'Out of Stock' WHEN b.BCH_available_stock_kg < 100 THEN 'Low Stock' ELSE 'In Stock' END = :status)";
            $params[':status'] = $statusFilter;
        }

        $whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT COUNT(*) AS total FROM BATCHES b LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id $whereSql";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function getBatchDetails($batchId, $lang = 'vi') {
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        $zoneNameCol = ($lang === 'en') ? 'COALESCE(z.STZ_zone_name_en, z.STZ_zone_name)' : 'z.STZ_zone_name';
        $supplierNameCol = ($lang === 'en') ? 'COALESCE(s.SUP_supplier_name_en, s.SUP_supplier_name)' : 's.SUP_supplier_name';
        
        $sql = "SELECT b.BCH_batch_id,
                       $productNameCol AS PRD_product_name,
                       p.PRD_material_grade,
                       b.BCH_initial_volume_kg,
                       b.BCH_available_stock_kg,
                       b.BCH_current_stage,
                       b.BCH_health_status,
                       b.BCH_received_date,
                       b.BCH_expiry_date,
                       $zoneNameCol AS STZ_zone_name,
                       $supplierNameCol AS SUP_supplier_name
                FROM BATCHES b
                LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                LEFT JOIN STORAGE_ZONES z ON b.BCH_zone_id = z.STZ_zone_id
                LEFT JOIN SUPPLIERS s ON b.BCH_supplier_id = s.SUP_supplier_id
                WHERE b.BCH_batch_id = :batch_id
                LIMIT 1";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':batch_id' => $batchId]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($batch && $lang === 'en') {
            if (function_exists('translate_product_name')) {
                $batch['PRD_product_name'] = translate_product_name($batch['PRD_product_name']);
            }
            if (function_exists('translate_zone_name')) {
                $batch['STZ_zone_name'] = translate_zone_name($batch['STZ_zone_name']);
            }
            if (function_exists('translate_supplier_name')) {
                $batch['SUP_supplier_name'] = translate_supplier_name($batch['SUP_supplier_name']);
            }
        }
        return $batch;
    }

    public function deleteBatch($batchId) {
        try {
            $this->pdo->beginTransaction();

            if ($this->batchHasClosedShiftMovement($batchId)) {
                $this->pdo->rollBack();
                return false;
            }

            // Xóa các dữ liệu liên quan (Foreign Key constraints)
            $this->pdo->prepare('DELETE FROM STOCK_MOVEMENTS WHERE STM_batch_id = :id')->execute([':id' => $batchId]);
            $this->pdo->prepare('DELETE FROM QC_INSPECTIONS WHERE QCI_batch_id = :id')->execute([':id' => $batchId]);
            $this->pdo->prepare('DELETE FROM MATERIAL_ALLOCATIONS WHERE ALC_batch_id = :id')->execute([':id' => $batchId]);
            $this->pdo->prepare('DELETE FROM FINISHED_GOODS WHERE FGD_batch_id = :id')->execute([':id' => $batchId]);

            // Xóa batch chính
            $stmt = $this->pdo->prepare('DELETE FROM BATCHES WHERE BCH_batch_id = :batch_id');
            $stmt->execute([':batch_id' => $batchId]);
            $success = $stmt->rowCount() > 0;

            $this->pdo->commit();
            return $success;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getSuppliersByProduct($productId, $lang = 'vi') {
        $productId = intval($productId);
        $supplierNameCol = ($lang === 'en') ? 'COALESCE(s.SUP_supplier_name_en, s.SUP_supplier_name)' : 's.SUP_supplier_name';
        $sql = "SELECT s.SUP_supplier_id, $supplierNameCol AS SUP_supplier_name 
                FROM SUPPLIERS s
                JOIN PRODUCT_SUPPLIERS ps ON s.SUP_supplier_id = ps.PSP_supplier_id
                WHERE ps.PSP_product_id = :product_id 
                  AND s.SUP_supplier_name NOT IN ('SUP_UNKNOWN', 'Unknown', 'unknown')
                ORDER BY s.SUP_supplier_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($lang === 'en' && !empty($rows)) {
            foreach ($rows as &$r) {
                $r['SUP_supplier_name'] = translate_supplier_name($r['SUP_supplier_name']);
            }
        }
        return $rows;
    }

    // Nhập kho (Stock in) an toàn chống SQL Injection với Transaction
    public function stockIn($batchId, $productId, $supplierId, $shiftId, $zoneId, $receivedDate, $expiryDate, $initialVolume, $userId) {
        try {
            // Bắt đầu Transaction
            $this->pdo->beginTransaction();

            if (!$this->isShiftOpen($shiftId)) {
                $this->pdo->rollBack();
                return false;
            }

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


            // 2. Ghi log vào STOCK_MOVEMENTS
            $referenceCode = 'IN_' . time() . '_' . rand(100, 999); // Sinh mã reference duy nhất
            $sqlMove = "INSERT INTO STOCK_MOVEMENTS (STM_reference_code, STM_batch_id, STM_shift_id, STM_movement_type, STM_quantity_kg, STM_user_id)
                        VALUES (:ref_code, :batch_id, :shift_id, 'IN', :quantity, :user_id)";
            $stmtMove = $this->pdo->prepare($sqlMove);
            $stmtMove->execute([
                ':ref_code' => $referenceCode,
                ':batch_id' => $batchId,
                ':shift_id' => $shiftId,
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
            $shiftId = $this->getCurrentOpenShiftId();

            if (!$batch || !$shiftId || $batch['BCH_available_stock_kg'] < $outVolume) {
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
            $sqlMove = "INSERT INTO STOCK_MOVEMENTS (STM_reference_code, STM_batch_id, STM_shift_id, STM_movement_type, STM_quantity_kg, STM_user_id)
                        VALUES (:ref_code, :batch_id, :shift_id, 'OUT', :quantity, :user_id)";
            $stmtMove = $this->pdo->prepare($sqlMove);
            $stmtMove->execute([
                ':ref_code' => $referenceCode,
                ':batch_id' => $batchId,
                ':shift_id' => $shiftId,
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
                $shiftId = $this->getCurrentOpenShiftId();
                if (!$shiftId) {
                    $this->pdo->rollBack();
                    return false;
                }

                $referenceCode = 'ADJ_' . time() . '_' . rand(100, 999);
                $sqlMove = "INSERT INTO STOCK_MOVEMENTS (STM_reference_code, STM_batch_id, STM_shift_id, STM_movement_type, STM_quantity_kg, STM_user_id)
                            VALUES (:ref_code, :batch_id, :shift_id, 'ADJUSTMENT', 0, :user_id)";
                $stmtMove = $this->pdo->prepare($sqlMove);
                $stmtMove->execute([
                    ':ref_code' => $referenceCode,
                    ':batch_id' => $batchId,
                    ':shift_id' => $shiftId,
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

    public function getBatchFullDetails($batchId, $lang = 'vi') {
        $productNameCol = ($lang === 'en') ? 'COALESCE(p.PRD_product_name_en, p.PRD_product_name)' : 'p.PRD_product_name';
        $zoneNameCol = ($lang === 'en') ? 'COALESCE(z.STZ_zone_name_en, z.STZ_zone_name)' : 'z.STZ_zone_name';
        $supplierNameCol = ($lang === 'en') ? 'COALESCE(s.SUP_supplier_name_en, s.SUP_supplier_name)' : 's.SUP_supplier_name';
        
        $sql = "SELECT b.*,
                       $productNameCol AS PRD_product_name,
                       p.PRD_material_grade,
                       p.PRD_unit_price,
                       $zoneNameCol AS STZ_zone_name,
                       $supplierNameCol AS SUP_supplier_name,
                       sh.SHF_shift_date,
                       sh.SHF_shift_type
                FROM BATCHES b
                LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
                LEFT JOIN STORAGE_ZONES z ON b.BCH_zone_id = z.STZ_zone_id
                LEFT JOIN SUPPLIERS s ON b.BCH_supplier_id = s.SUP_supplier_id
                LEFT JOIN SHIFTS sh ON b.BCH_shift_id = sh.SHF_shift_id
                WHERE b.BCH_batch_id = :batch_id
                LIMIT 1";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':batch_id' => $batchId]);
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$batch) return null;

        if ($lang === 'en') {
            if (function_exists('translate_product_name')) {
                $batch['PRD_product_name'] = translate_product_name($batch['PRD_product_name']);
            }
            if (function_exists('translate_zone_name')) {
                $batch['STZ_zone_name'] = translate_zone_name($batch['STZ_zone_name']);
            }
            if (function_exists('translate_supplier_name')) {
                $batch['SUP_supplier_name'] = translate_supplier_name($batch['SUP_supplier_name']);
            }
        }

        $stmtMoves = $this->pdo->prepare("
            SELECT m.*, u.USR_full_name 
            FROM STOCK_MOVEMENTS m
            LEFT JOIN USERS u ON m.STM_user_id = u.USR_user_id
            WHERE m.STM_batch_id = :batch_id
            ORDER BY m.STM_timestamp DESC
        ");
        $stmtMoves->execute([':batch_id' => $batchId]);
        $batch['movements'] = $stmtMoves->fetchAll(PDO::FETCH_ASSOC);

        return $batch;
    }

    public function updateBatchDetailsFull($batchId, $newZoneId, $newExpiryDate, $availableStock, $healthStatus, $userId) {
        try {
            $this->pdo->beginTransaction();

            $sqlCheck = "SELECT * FROM BATCHES WHERE BCH_batch_id = :batch_id FOR UPDATE";
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute([':batch_id' => $batchId]);
            $batch = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$batch) {
                $this->pdo->rollBack();
                return false;
            }

            $oldZoneId = $batch['BCH_zone_id'];
            $oldStock = floatval($batch['BCH_available_stock_kg']);
            $newStock = floatval($availableStock);

            if ($oldZoneId != $newZoneId) {
                $stmtSub = $this->pdo->prepare("UPDATE STORAGE_ZONES SET STZ_current_load_kg = GREATEST(0, STZ_current_load_kg - :stock) WHERE STZ_zone_id = :old_zone");
                $stmtSub->execute([':stock' => $oldStock, ':old_zone' => $oldZoneId]);

                $stmtAdd = $this->pdo->prepare("UPDATE STORAGE_ZONES SET STZ_current_load_kg = STZ_current_load_kg + :stock WHERE STZ_zone_id = :new_zone");
                $stmtAdd->execute([':stock' => $newStock, ':new_zone' => $newZoneId]);
            } else if ($oldStock != $newStock) {
                $diff = $newStock - $oldStock;
                $stmtDiff = $this->pdo->prepare("UPDATE STORAGE_ZONES SET STZ_current_load_kg = GREATEST(0, STZ_current_load_kg + :diff) WHERE STZ_zone_id = :zone_id");
                $stmtDiff->execute([':diff' => $diff, ':zone_id' => $newZoneId]);
            }

            $stmtUpdate = $this->pdo->prepare("
                UPDATE BATCHES 
                SET BCH_zone_id = :zone_id,
                    BCH_expiry_date = :expiry_date,
                    BCH_available_stock_kg = :stock,
                    BCH_health_status = :health_status
                WHERE BCH_batch_id = :batch_id
            ");
            $stmtUpdate->execute([
                ':zone_id' => $newZoneId,
                ':expiry_date' => $newExpiryDate,
                ':stock' => $newStock,
                ':health_status' => $healthStatus,
                ':batch_id' => $batchId
            ]);

            $shiftId = $this->getCurrentOpenShiftId();
            if ($shiftId) {
                $ref = 'ADJ_' . time() . '_' . rand(100, 999);
                $stmtMove = $this->pdo->prepare("
                    INSERT INTO STOCK_MOVEMENTS (STM_reference_code, STM_batch_id, STM_shift_id, STM_movement_type, STM_quantity_kg, STM_user_id)
                    VALUES (:ref_code, :batch_id, :shift_id, 'ADJUSTMENT', :quantity, :user_id)
                ");
                $stmtMove->execute([
                    ':ref_code' => $ref,
                    ':batch_id' => $batchId,
                    ':shift_id' => $shiftId,
                    ':quantity' => abs($newStock - $oldStock),
                    ':user_id' => $userId
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
?>