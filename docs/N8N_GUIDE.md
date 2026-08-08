# HƯỚNG DẪN CÀI ĐẶT & CHẠY N8N WORKFLOW CHO ĐỒNG ĐỘI (TEAM)

Tài liệu này dành cho thành viên trong team khi pull dự án về máy mới và muốn chạy thử nghiệm toàn bộ hệ thống tự động hóa n8n.

---

## BƯỚC 1: CÀI ĐẶT YÊU CẦU NỀN TẢNG (Nếu máy chưa cài)

1. **Cài Node.js**: Tải và cài đặt phiên bản Node.js LTS từ [https://nodejs.org/](https://nodejs.org/).
2. **Cài n8n**: Mở Terminal / PowerShell (chạy dưới quyền Admin nếu cần) và gõ:
   ```bash
   npm install n8n -g
   ```

---

## BƯỚC 2: CẬP NHẬT CODE MỚI NHẤT

Mở Git Bash tại thư mục dự án và chạy:
```bash
git checkout main
git pull origin main
```

Các file workflow đã sẵn có tại thư mục:
- `n8n_workflows/flow1_realtime_critical_alerts.json` (Cảnh báo thời gian thực: QC, Tồn kho thấp, Yêu cầu nguyên liệu)
- `n8n_workflows/flow2_weekly_summary_flow.json` (Báo cáo tổng hợp hàng tuần)

---

## BƯỚC 3: KHỞI ĐỘNG N8N VÀ IMPORT WORKFLOW

1. Mở Terminal / CMD gõ lệnh để chạy n8n:
   ```bash
   n8n start
   ```
2. Mở trình duyệt truy cập: `http://localhost:5678` (Lần đầu truy cập n8n sẽ yêu cầu tạo tài khoản Admin local).
3. **Import Workflow**:
   - Chọn menu **Workflows** -> Nhấn nút **Import from File** (hoặc dấu 3 chấm góc phải trên).
   - Chọn file `n8n_workflows/flow1_realtime_critical_alerts.json` và `n8n_workflows/flow2_weekly_summary_flow.json`.

---

## BƯỚC 4: CẤU HÌNH GMAIL & KÍCH HOẠT (ACTIVE)

1. **Kết nối Gmail**:
   - Trong giao diện n8n, click vào node **Gmail** -> chọn **Create New Credential** -> Đăng nhập OAuth2 với Gmail để n8n có quyền gửi mail.
2. **Bật Active Workflow**:
   - Chuyển công tắc **Active** (ở góc trên cùng bên phải màn hình chỉnh sửa Workflow) từ `OFF` sang **`ON`**.
3. **Chạy XAMPP (Apache + MySQL)**:
   - Đảm bảo Apache và MySQL trên XAMPP đang bật. Khi bạn thực hiện QC Reject, Stock-Out thấp, hoặc gửi Yêu cầu nguyên vật liệu trên Web, PHP sẽ tự động bắn Webhook sang `http://localhost:5678/webhook/...` để n8n gửi email ngay lập tức!
