-- Run this once on an existing Project2_db database before using shift closing.

ALTER TABLE SHIFTS
    ADD COLUMN SHF_closed_at DATETIME DEFAULT NULL AFTER SHF_status,
    ADD COLUMN SHF_closed_by INT DEFAULT NULL AFTER SHF_closed_at;

ALTER TABLE SHIFTS
    ADD CONSTRAINT fk_shifts_closed_by
    FOREIGN KEY (SHF_closed_by) REFERENCES USERS(USR_user_id)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE STOCK_MOVEMENTS
    ADD COLUMN STM_shift_id INT DEFAULT NULL AFTER STM_batch_id;

UPDATE STOCK_MOVEMENTS sm
JOIN BATCHES b ON sm.STM_batch_id = b.BCH_batch_id
SET sm.STM_shift_id = b.BCH_shift_id
WHERE sm.STM_shift_id IS NULL;

ALTER TABLE STOCK_MOVEMENTS
    ADD INDEX idx_stock_movements_shift_id (STM_shift_id);

ALTER TABLE STOCK_MOVEMENTS
    ADD CONSTRAINT fk_stock_movements_shift_id
    FOREIGN KEY (STM_shift_id) REFERENCES SHIFTS(SHF_shift_id)
    ON DELETE SET NULL ON UPDATE CASCADE;
