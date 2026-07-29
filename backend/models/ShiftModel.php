<?php
require_once __DIR__ . '/../core/BaseModel.php';

class ShiftModel extends BaseModel
{
    private function getCurrentShiftType()
    {
        $hour = (int) date('G');

        if ($hour >= 6 && $hour < 14) {
            return 'Morning';
        }

        if ($hour >= 14 && $hour < 22) {
            return 'Afternoon';
        }

        return 'Overtime';
    }

    public function getCurrentOpenShift()
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM SHIFTS
             WHERE SHF_status = 'Open'
               AND SHF_shift_date = CURDATE()
               AND SHF_shift_type = :shift_type
             ORDER BY SHF_shift_id DESC
             LIMIT 1"
        );
        $stmt->execute([':shift_type' => $this->getCurrentShiftType()]);
        $shift = $stmt->fetch();

        if ($shift) {
            return $shift;
        }

        $stmt = $this->pdo->query(
            "SELECT *
             FROM SHIFTS
             WHERE SHF_status = 'Open'
             ORDER BY SHF_shift_date DESC,
                      FIELD(SHF_shift_type, 'Overtime', 'Afternoon', 'Morning'),
                      SHF_shift_id DESC
             LIMIT 1"
        );

        return $stmt->fetch();
    }

    public function getShiftById($shiftId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, u.USR_full_name AS closed_by_name
             FROM SHIFTS s
             LEFT JOIN USERS u ON s.SHF_closed_by = u.USR_user_id
             WHERE s.SHF_shift_id = :shift_id
             LIMIT 1"
        );
        $stmt->execute([':shift_id' => (int) $shiftId]);

        return $stmt->fetch();
    }

    public function getMovementsForShift($shiftId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT sm.STM_movement_id,
                    sm.STM_reference_code,
                    sm.STM_batch_id,
                    sm.STM_movement_type,
                    sm.STM_quantity_kg,
                    sm.STM_timestamp,
                    p.PRD_product_name,
                    p.PRD_product_name_en,
                    u.USR_full_name
             FROM STOCK_MOVEMENTS sm
             JOIN BATCHES b ON sm.STM_batch_id = b.BCH_batch_id
             LEFT JOIN PRODUCTS p ON b.BCH_product_id = p.PRD_product_id
             LEFT JOIN USERS u ON sm.STM_user_id = u.USR_user_id
             WHERE sm.STM_shift_id = :shift_id
                OR (sm.STM_shift_id IS NULL AND b.BCH_shift_id = :shift_id)
             ORDER BY sm.STM_timestamp ASC, sm.STM_movement_id ASC"
        );
        $stmt->execute([':shift_id' => (int) $shiftId]);

        return $stmt->fetchAll();
    }

    public function getShiftSummary($shiftId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN sm.STM_movement_type = 'IN' THEN sm.STM_quantity_kg ELSE 0 END), 0) AS total_in_kg,
                    COALESCE(SUM(CASE WHEN sm.STM_movement_type = 'OUT' THEN sm.STM_quantity_kg ELSE 0 END), 0) AS total_out_kg,
                    COUNT(DISTINCT sm.STM_batch_id) AS batch_count,
                    COUNT(sm.STM_movement_id) AS movement_count,
                    COALESCE(SUM(CASE WHEN sm.STM_movement_type = 'ADJUSTMENT' THEN 1 ELSE 0 END), 0) AS incident_count
             FROM STOCK_MOVEMENTS sm
             JOIN BATCHES b ON sm.STM_batch_id = b.BCH_batch_id
             WHERE sm.STM_shift_id = :shift_id
                OR (sm.STM_shift_id IS NULL AND b.BCH_shift_id = :shift_id)"
        );
        $stmt->execute([':shift_id' => (int) $shiftId]);
        $summary = $stmt->fetch();

        return $summary ?: [
            'total_in_kg' => 0,
            'total_out_kg' => 0,
            'batch_count' => 0,
            'movement_count' => 0,
            'incident_count' => 0,
        ];
    }

    public function closeShift($shiftId, $userId)
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                "SELECT *
                 FROM SHIFTS
                 WHERE SHF_shift_id = :shift_id
                 FOR UPDATE"
            );
            $stmt->execute([':shift_id' => (int) $shiftId]);
            $shift = $stmt->fetch();

            if (!$shift) {
                throw new Exception('shift_not_found');
            }

            if ($shift['SHF_status'] === 'Closed') {
                throw new Exception('shift_already_closed');
            }

            $summary = $this->getShiftSummary($shiftId);
            $closedAt = date('Y-m-d H:i:s');

            $stmt = $this->pdo->prepare(
                "UPDATE SHIFTS
                 SET SHF_status = 'Closed',
                     SHF_closed_at = :closed_at,
                     SHF_closed_by = :closed_by
                 WHERE SHF_shift_id = :shift_id"
            );
            $stmt->execute([
                ':closed_at' => $closedAt,
                ':closed_by' => (int) $userId,
                ':shift_id' => (int) $shiftId,
            ]);

            $audit = $this->pdo->prepare(
                "INSERT INTO SYSTEM_AUDIT_LOGS
                    (LOG_user_id, LOG_action, LOG_table_name, LOG_record_id, LOG_old_value, LOG_new_value)
                 VALUES
                    (:user_id, 'CLOSE_SHIFT', 'SHIFTS', :record_id, :old_value, :new_value)"
            );
            $audit->execute([
                ':user_id' => (int) $userId,
                ':record_id' => (string) $shiftId,
                ':old_value' => 'Open',
                ':new_value' => json_encode([
                    'status' => 'Closed',
                    'closed_at' => $closedAt,
                    'summary' => $summary,
                ]),
            ]);

            $this->pdo->commit();

            $shift['SHF_status'] = 'Closed';
            $shift['SHF_closed_at'] = $closedAt;
            $shift['SHF_closed_by'] = (int) $userId;

            return [
                'shift' => $shift,
                'summary' => $summary,
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getClosedShiftHistory($date = null)
    {
        $params = [];
        $where = "WHERE s.SHF_status = 'Closed'";

        if ($date) {
            $where .= " AND s.SHF_shift_date = :shift_date";
            $params[':shift_date'] = $date;
        }

        $stmt = $this->pdo->prepare(
            "SELECT s.*, u.USR_full_name AS closed_by_name
             FROM SHIFTS s
             LEFT JOIN USERS u ON s.SHF_closed_by = u.USR_user_id
             $where
             ORDER BY s.SHF_shift_date DESC, s.SHF_closed_at DESC, s.SHF_shift_id DESC
             LIMIT 100"
        );
        $stmt->execute($params);
        $shifts = $stmt->fetchAll();

        foreach ($shifts as &$shift) {
            $shift['summary'] = $this->getShiftSummary($shift['SHF_shift_id']);
        }

        return $shifts;
    }
}
?>
