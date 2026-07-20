<?php
/**
 * Tính Tỷ lệ thu hồi (Yield Rate) cho một lô hàng (batch),
 * tách riêng hao hụt tự nhiên và hao hụt bất thường.
 *
 * @param PDO $pdo
 * @param string $batch_id
 * @return array|null  Trả về null nếu batch không tồn tại hoặc chưa có dữ liệu QC
 */
function calculateYieldRate(PDO $pdo, string $batch_id): ?array {
    // 1. Lấy khối lượng nguyên liệu thô đầu vào
    $stmt = $pdo->prepare("SELECT BCH_initial_volume_kg FROM BATCHES WHERE BCH_batch_id = :id");
    $stmt->execute([':id' => $batch_id]);
    $batch = $stmt->fetch();
    if (!$batch || $batch['BCH_initial_volume_kg'] <= 0) {
        return null; // Tránh chia cho 0
    }
    $rawInput = (float) $batch['BCH_initial_volume_kg'];

    // 2. Tổng hợp dữ liệu QC (có thể có nhiều lần kiểm định trên 1 batch)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(QCI_usable_weight_kg) AS usable,
            SUM(QCI_natural_loss_weight_kg) AS natural_loss,
            SUM(QCI_rotten_weight_kg) AS abnormal_loss
        FROM QC_INSPECTIONS
        WHERE QCI_batch_id = :id
    ");
    $stmt->execute([':id' => $batch_id]);
    $qc = $stmt->fetch();

    $usable = (float) ($qc['usable'] ?? 0);
    $naturalLoss = (float) ($qc['natural_loss'] ?? 0);
    $abnormalLoss = (float) ($qc['abnormal_loss'] ?? 0);

    // 3. Lấy khối lượng thành phẩm thực tế (nếu đã sản xuất xong)
    $stmt = $pdo->prepare("
        SELECT SUM(FGD_total_cans * FGD_kg_per_can) AS finished_weight
        FROM FINISHED_GOODS
        WHERE FGD_batch_id = :id
    ");
    $stmt->execute([':id' => $batch_id]);
    $fg = $stmt->fetch();
    $finishedWeight = $fg['finished_weight'] !== null ? (float) $fg['finished_weight'] : null;

    // 4. Tính yield rate
    // Ưu tiên dùng khối lượng thành phẩm thực tế nếu đã có (chính xác nhất theo ví dụ phỏng vấn)
    // Nếu chưa sản xuất xong, tạm dùng usable_weight sau QC làm ước tính
    $referenceOutput = $finishedWeight ?? $usable;
    $yieldRate = round(($referenceOutput / $rawInput) * 100, 2);

    return [
        'batch_id'          => $batch_id,
        'raw_input_kg'      => $rawInput,
        'usable_kg'         => $usable,
        'natural_loss_kg'   => $naturalLoss,
        'natural_loss_pct'  => round(($naturalLoss / $rawInput) * 100, 2),
        'abnormal_loss_kg'  => $abnormalLoss,
        'abnormal_loss_pct' => round(($abnormalLoss / $rawInput) * 100, 2),
        'finished_weight_kg'=> $finishedWeight,
        'yield_rate_pct'    => $yieldRate,
        'is_estimated'      => $finishedWeight === null, // true nếu batch chưa hoàn tất sản xuất
    ];
}