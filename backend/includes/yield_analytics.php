<?php
//   1. Hàm PHP tính Tỷ lệ thu hồi (Yield Rate), có xử lý hao hụt tự nhiên
//   2. Các câu SQL dùng GROUP BY / SUM / COUNT / JOIN cho:
//      - Tồn kho tổng hợp (Inventory Summary)
//      - Số lượng đơn hàng trễ (Delayed Export Orders) 
//      - Lượng hàng tươi sắp hỏng (Expiring Fresh Batches)

require_once 'db_connect.php'; // $pdo đã được khởi tạo ở đây


// ==============================================================================
// 1. HÀM PHP: TÍNH TỶ LỆ THU HỒI (YIELD RATE)
// ==============================================================================
/**
 * Tính tỷ lệ thu hồi = (Thành phẩm đầu ra / Nguyên liệu thô đầu vào) * 100
 * Có xử lý sai số hao hụt tự nhiên (natural loss) tách riêng khỏi hao hụt bất thường (rotten)
 *
 * @param float $raw_input_kg      Tổng lượng nguyên liệu thô đưa vào (kg)
 * @param float $rotten_kg         Lượng hỏng/loại bỏ bất thường (kg) - vd: hỏng do mất điện kho lạnh
 * @param float $natural_loss_kg   Lượng hao hụt tự nhiên (kg) - vd: gọt vỏ, cắt đầu đuôi
 * @param float $finished_output_kg Tổng lượng thành phẩm thực tế xuất ra (kg)
 * @return array ['usable_kg'=>.., 'yield_rate_pct'=>.., 'loss_rate_pct'=>..]
 */
function calculateYieldRate(
    float $raw_input_kg,
    float $rotten_kg = 0.0,
    float $natural_loss_kg = 0.0,
    ?float $finished_output_kg = null
): array {
    // Chống chia cho 0 / dữ liệu âm bất hợp lệ
    if ($raw_input_kg <= 0) {
        return ['usable_kg' => 0.0, 'yield_rate_pct' => 0.0, 'loss_rate_pct' => 0.0];
    }

    // Lượng nguyên liệu còn dùng được sau khi trừ hao hụt (bất thường + tự nhiên)
    $usable_kg = $raw_input_kg - $rotten_kg - $natural_loss_kg;
    $usable_kg = max(0, $usable_kg); // không để âm

    // Nếu đã có số liệu thành phẩm thực tế (sau chế biến) thì ưu tiên dùng số đó để tính yield thật
    // vì thành phẩm thực xuất ra có thể khác usable_kg do hao hụt trong quá trình chế biến (rửa, thanh trùng...)
    $output_for_yield = $finished_output_kg ?? $usable_kg;

    $yield_rate_pct = round(($output_for_yield / $raw_input_kg) * 100, 2);
    $loss_rate_pct  = round((($rotten_kg + $natural_loss_kg) / $raw_input_kg) * 100, 2);

    return [
        'usable_kg'      => round($usable_kg, 2),
        'yield_rate_pct' => $yield_rate_pct,
        'loss_rate_pct'  => $loss_rate_pct,
    ];
}


// ==============================================================================
// 2. SQL: TỒN KHO TỔNG HỢP (INVENTORY SUMMARY)
// ==============================================================================
// Gom tồn kho khả dụng theo từng sản phẩm và khu vực kho -> phục vụ Production/Warehouse dashboard
function getInventorySummary(PDO $pdo): array
{
    $sql = "
        SELECT
            p.PRD_product_name,
            z.STZ_zone_name,
            COUNT(b.BCH_batch_id)                  AS total_batches,
            SUM(b.BCH_available_stock_kg)          AS total_available_kg,
            SUM(b.BCH_initial_volume_kg)            AS total_initial_kg,
            ROUND(
                SUM(b.BCH_available_stock_kg) / NULLIF(SUM(b.BCH_initial_volume_kg), 0) * 100
            , 2)                                     AS pct_remaining
        FROM BATCHES b
        JOIN PRODUCTS p        ON b.BCH_product_id = p.PRD_product_id
        JOIN STORAGE_ZONES z   ON b.BCH_zone_id     = z.STZ_zone_id
        WHERE b.BCH_available_stock_kg > 0
        GROUP BY p.PRD_product_name, z.STZ_zone_name
        ORDER BY p.PRD_product_name, z.STZ_zone_name
    ";
    return $pdo->query($sql)->fetchAll();
}


// ==============================================================================
// 3. SQL: SỐ LƯỢNG "ĐƠN HÀNG" TRỄ (DELAYED EXPORT PROXY)
// ==============================================================================
// LƯU Ý QUAN TRỌNG (ghi vào tài liệu test/assumption):
// Database hiện tại KHÔNG có bảng ORDERS / EXPORT_DEMAND.
// Vì vậy "đơn hàng trễ" được xấp xỉ (proxy) bằng: lô thành phẩm đã qua bảo ôn xong
// (FGD_status = 'Ready_To_Export') nhưng đã trễ quá X ngày (mặc định 3 ngày) mà vẫn chưa xuất.
function getDelayedExportProxy(PDO $pdo, int $delay_threshold_days = 3): array
{
    $sql = "
        SELECT
            p.PRD_product_name,
            COUNT(f.FGD_fg_id)                     AS delayed_batches_count,
            SUM(f.FGD_total_cans)                   AS total_cans_delayed,
            DATEDIFF(CURDATE(), f.FGD_quarantine_end_date) AS days_overdue
        FROM FINISHED_GOODS f
        JOIN BATCHES b   ON f.FGD_batch_id = b.BCH_batch_id
        JOIN PRODUCTS p  ON b.BCH_product_id = p.PRD_product_id
        WHERE f.FGD_status = 'Ready_To_Export'
          AND f.FGD_quarantine_end_date <= DATE_SUB(CURDATE(), INTERVAL :delay_days DAY)
        GROUP BY p.PRD_product_name, f.FGD_quarantine_end_date
        ORDER BY days_overdue DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':delay_days' => $delay_threshold_days]);
    return $stmt->fetchAll();
}


// ==============================================================================
// 4. SQL: LƯỢNG HÀNG TƯƠI SẮP HỎNG (EXPIRING FRESH BATCHES - FEFO)
// ==============================================================================
// Đếm và tổng hợp theo sản phẩm các lô nguyên liệu tươi sắp hết hạn trong N giờ tới (mặc định 48h)
function getExpiringFreshBatches(PDO $pdo, int $hours_threshold = 48): array
{
    $sql = "
        SELECT
            p.PRD_product_name,
            z.STZ_zone_name,
            COUNT(b.BCH_batch_id)           AS expiring_batch_count,
            SUM(b.BCH_available_stock_kg)   AS expiring_total_kg,
            MIN(b.BCH_expiry_date)          AS nearest_expiry
        FROM BATCHES b
        JOIN PRODUCTS p       ON b.BCH_product_id = p.PRD_product_id
        JOIN STORAGE_ZONES z  ON b.BCH_zone_id     = z.STZ_zone_id
        WHERE b.BCH_available_stock_kg > 0
          AND b.BCH_expiry_date <= DATE_ADD(NOW(), INTERVAL :hours HOUR)
        GROUP BY p.PRD_product_name, z.STZ_zone_name
        ORDER BY nearest_expiry ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':hours' => $hours_threshold]);
    return $stmt->fetchAll();
}


