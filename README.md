# F&G Food - Inventory & Production Management System

## 1. Giới thiệu
Đây là hệ thống quản lý kho, chất lượng và sản xuất cho doanh nghiệp thực phẩm F&G, được xây dựng dưới dạng web application bằng PHP + MySQL, tập trung vào việc số hóa quy trình từ nhập nguyên liệu, kiểm tra chất lượng, cấp phát nguyên vật liệu, sản xuất, đến lưu trữ sản phẩm thành phẩm.

Hệ thống này mang mục tiêu chính là:
- kiểm soát tồn kho theo thời gian thực,
- giảm sai sót trong quy trình nhập/xuất nguyên liệu,
- hỗ trợ kiểm tra chất lượng QC,
- quản lý sản xuất theo lô và theo ca làm việc,
- cung cấp báo cáo, dashboard và trợ lý AI cho người dùng.

---

## 2. Mục tiêu và phạm vi hệ thống
Hệ thống phù hợp cho mô hình sản xuất thực phẩm có đặc thù như:
- nhiều SKU nguyên liệu và thành phẩm,
- cần theo dõi ngày hết hạn, lô hàng, khu vực lưu trữ,
- yêu cầu kiểm tra chất lượng đầu vào trước khi cho vào sản xuất,
- cần kiểm soát lượng tồn kho, mức cảnh báo, và báo cáo theo thời gian.

Các chức năng chính bao gồm:
- Quản lý tài khoản người dùng theo vai trò,
- Quản lý kho và tồn kho nguyên liệu,
- Theo dõi số lượng, vị trí lưu trữ, batch, FEFO,
- Kiểm định chất lượng (QC), ghi nhận lỗi / phế phẩm / hao hụt,
- Yêu cầu nguyên liệu và cấp phát cho sản xuất,
- Theo dõi sản xuất / finished goods,
- Bảng điều khiển báo cáo và analytics,
- Hệ thống thông báo và n8n automation,
- AI chatbot truy vấn dữ liệu bằng ngôn ngữ tự nhiên.

---

## 3. Cấu trúc dự án

```text
Capstone_Project_2/
├── .env                          # Cấu hình môi trường, API key
├── README.md                    # Hướng dẫn dự án
├── Project2_db.sql              # Schema database chính
├── seed_data.sql                # Dữ liệu mẫu / mock data
├── mock_data.sql                # Dữ liệu bổ sung (nếu có)
├── automaticallyMail.n8n        # Workflow n8n mẫu
├── backend/
│   ├── api/                     # API phục vụ UI / chatbot / notification
│   ├── connection/              # File xử lý login, logout, DB, business actions
│   ├── controllers/             # Controller logic
│   ├── core/                    # Base model / common classes
│   ├── helpers/                 # Helper cho n8n, config, docs
│   ├── includes/                # Auth, i18n, utilities
│   ├── models/                  # Model thao tác DB
│   ├── services/                # Service Weather / notification / logic nghiệp vụ
│   └── lang/                    # File ngôn ngữ / translations
├── frontend/
│   ├── includes/                # Giao diện dùng chung
│   ├── *.php                    # Trang dashboard, quản lý kho, QC, sản xuất, login
│   └── assets/                  # CSS/JS/static assets
├── image/                       # Hình ảnh hệ thống / ảnh tĩnh
├── screenshots_by_module/       # Hình ảnh demo cho từng module
├── n8n_workflows/              # Workflow n8n JSON + docs
├── tests/                       # Test script, automated checks
└── vendor/ or dependencies      # tùy môi trường cài đặt thêm
```

---

## 4. Công nghệ sử dụng
- PHP 7.4+ / 8.x
- MySQL / MariaDB
- Apache via XAMPP
- PDO cho kết nối database
- HTML + CSS + JavaScript
- Tailwind CSS cho giao diện
- n8n cho automation workflow
- AI API: Groq / Gemini / OpenRouter (nếu cấu hình)
- Weather API cho dashboard thời tiết

---

## 5. Yêu cầu hệ thống
Trước khi chạy dự án, cần chuẩn bị:
- XAMPP / WAMP / MAMP
- Apache bật và MySQL chạy
- PHP đã cài đặt tương thích
- Trình duyệt Chrome/Edge/Firefox hiện đại
- Kết nối internet nếu cần dùng AI API / Weather API

---

## 6. Cài đặt và khởi chạy

### 6.1. Clone / đặt project vào thư mục làm việc
Đặt thư mục project vào thư mục htdocs của XAMPP, ví dụ:

```text
C:\xampp\htdocs\Capstone_Project_2\Capstone_Project_2
```

### 6.2. Khởi động môi trường
- Mở XAMPP Control Panel
- Bật Apache
- Bật MySQL

### 6.3. Tạo database
Vào phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Tạo database mới với tên:

```sql
CREATE DATABASE Project2_db;
```

### 6.4. Import dữ liệu
Import theo thứ tự:
1. `Project2_db.sql`
2. `seed_data.sql`

Lưu ý:
- Nếu hệ thống có dữ liệu demo theo role và tài khoản, `seed_data.sql` nên được import sau schema chính.
- Nếu có lỗi password hash hoặc mật khẩu không khớp, có thể chạy script bổ trợ trong `backend/connection/fix_pass.php` hoặc kiểm tra lại dữ liệu người dùng.

### 6.5. Cấu hình .env
File `.env` nằm ở thư mục gốc dự án. Nội dung ví dụ:

```env
WEATHER_API_KEY=your_weather_api_key
GROQ_API_KEY=your_groq_api_key
GEMINI_API_KEY=your_gemini_api_key
OPENROUTER_API_KEY=your_openrouter_api_key
N8N_WEBHOOK_BASE_URL=http://localhost:5678/webhook
```

Nếu không sử dụng AI hoặc Weather API, hệ thống vẫn có thể chạy ở mức chức năng core, nhưng một số module có thể bị hạn chế hoặc hiển thị lỗi khi gọi API.

### 6.6. Cấu hình kết nối database
File kết nối mặc định nằm tại:

```php
backend/connection/db_connect.php
```

Mặc định của XAMPP là:
- host: localhost
- user: root
- password: ""
- database: Project2_db

Nếu bạn đang dùng môi trường khác, cần cập nhật các thông số trong file này tương ứng.

---

## 7. Chạy ứng dụng
Sau khi database và cấu hình đã sẵn sàng, truy cập:

```text
http://localhost/Capstone_Project_2/Capstone_Project_2/frontend/login.php
```

Hoặc nếu bạn đặt dự án tại ví dụ khác, thay đường dẫn tương ứng.

---

## 8. Tài khoản demo / đăng nhập mẫu
Các tài khoản demo được tạo sẵn trong `seed_data.sql` với role tương ứng. Mật khẩu mặc định trong hệ thống đang dùng chung là:

```text
123456
```

Danh sách tài khoản mẫu:

| Vai trò | Username | Mật khẩu | Trang sau khi đăng nhập |
|---|---|---|---|
| Production Manager | pm_alex | 123456 | dashboard_production.php |
| QC | nhung_thuy | 123456 | qc_dashboard.php |
| Warehouse Staff | wh_admin04 | 123456 | dashboard_warehouse.php |
| Director | director_demo | 123456 | dashboard_director.php |

Lưu ý:
- Hệ thống dùng `USR_role` trong DB để phân quyền.
- File `backend/connection/process_login.php` sẽ xác định role và redirect đến dashboard tương ứng.

---

## 9. Phân quyền người dùng
Hệ thống được thiết kế theo mô hình RBAC (Role-Based Access Control), bao gồm các vai trò chính:

### 9.1. Warehouse_Staff
- Quản lý kho nguyên liệu
- Nhập kho / cập nhật lô hàng
- Theo dõi vị trí lưu trữ
- Xem dashboard kho, báo cáo tồn kho
- Thực hiện điều chỉnh / xử lý dữ liệu tồn kho

### 9.2. QC
- Kiểm tra nguyên liệu đầu vào
- Ghi nhận lỗi, phế phẩm, hao hụt
- Chốt đánh giá QC và báo cáo chất lượng
- Theo dõi tiến độ QC

### 9.3. Production_Manager
- Yêu cầu nguyên liệu sản xuất
- Phân bổ lô phù hợp cho dây chuyền
- Theo dõi tiến độ sản xuất
- Quản lý yield, material allocation, production analytics

### 9.4. Director
- Xem dashboard tổng quan
- Đánh giá KPI / báo cáo cấp cao
- Theo dõi hiệu suất toàn hệ thống
- Có quyền xem dữ liệu báo cáo cấp quản lý

---

## 10. Quy trình vận hành thực tế của hệ thống

### 10.1. Quy trình nhập kho
1. Warehouse Staff đăng nhập.
2. Vào module kho / inventory.
3. Thêm hoặc cập nhật batch nguyên liệu.
4. Gán vị trí lưu trữ, thông tin số lượng, hạn sử dụng, mốc thời gian nhận hàng.
5. Lưu dữ liệu vào bảng `BATCHES` và `STOCK_MOVEMENTS`.

### 10.2. Quy trình QC
1. QC truy cập dashboard QC.
2. Chọn batch cần kiểm tra.
3. Kiểm tra chất lượng đầu vào, ghi nhận lỗi / phế phẩm / tỷ lệ reject.
4. Cập nhật trạng thái batch và kết quả đánh giá.
5. Nếu đạt chuẩn, cho phép batch đi vào sản xuất; nếu không, đưa vào trạng thái lỗi hoặc cần xử lý.

### 10.3. Quy trình yêu cầu nguyên liệu và sản xuất
1. Production Manager tạo request material.
2. Hệ thống kiểm tra tồn kho và lô hàng còn khả dụng.
3. Chọn batch phù hợp theo tiêu chí FEFO / expiry / stock matching.
4. Giao nguyên liệu cho dây chuyền.
5. Theo dõi tiến độ, yield và lượng thành phẩm thu được.

### 10.4. Quy trình xuất kho / hoàn thành sản phẩm
- Sau sản xuất, finished goods được ghi nhận vào hệ thống.
- Có thể đưa vào trạng thái quarantine, ready to ship, hoặc rejected tùy nghiệp vụ.
- Hệ thống giữ nhật ký bằng bảng `STOCK_MOVEMENTS`, `FINISHED_GOODS`, `MATERIAL_ALLOCATIONS`.

---

## 11. Các module chính trong hệ thống

### 11.1. Dashboard Warehouse
- Tổng quan tồn kho, lượng nhập / xuất, batch đang hoạt động
- Cảnh báo tồn kho thấp
- Hiển thị các dòng dữ liệu nhất định theo thời gian thực

### 11.2. Inventory / Stock Management
- Quản lý lô hàng, vị trí lưu trữ, số lượng, hạn sử dụng
- Tìm kiếm batch theo mã sản phẩm, ngày, khu vực
- Theo dõi lịch sử movement

### 11.3. QC Dashboard
- Xem danh sách batch chờ QC
- Đánh giá chất lượng và xử lý reject reason
- Tạo báo cáo QC

### 11.4. Production FEFO / Material Allocation
- Chọn nguyên liệu phù hợp theo FEFO
- Cấp phát vật tư cho dây chuyền
- Giảm rủi ro hết hạn hoặc sản xuất sai nguyên liệu

### 11.5. Production Analytics
- Hiển thị yield, throughput, hiệu suất tồn kho
- Theo dõi sản xuất trong các chu kỳ khác nhau

### 11.6. Reports & Security UI
- Báo cáo xuất nhập kho, QC, sản xuất
- Dashboard quản lý, báo cáo tổng thể
- Giao diện có thể được dùng làm chứng minh cho dự án và demo

### 11.7. AI Chatbot
- Người dùng có thể tương tác bằng ngôn ngữ tự nhiên
- Chatbot chuyển câu hỏi thành truy vấn SQL / logic dữ liệu
- Hiện đang hỗ trợ các provider như Groq, Gemini, OpenRouter tùy cấu hình

### 11.8. n8n Automation
- Gửi email báo cáo tổng hợp theo định kỳ
- Có thể tích hợp alert / notification trên webhook
- Workflow mẫu nằm trong thư mục `n8n_workflows/` và `automaticallyMail.n8n`

---

## 12. Cách sử dụng hệ thống từng vai trò

### 12.1. Với Warehouse Staff
- Đăng nhập bằng tài khoản warehouse
- Kiểm tra tồn kho và nhập Kho
- Cập nhật batch / vị trí lưu trữ
- Theo dõi lịch sử stock movements
- Xử lý các batch hết hạn, thiếu hàng, tồn kho bất thường

### 12.2. Với QC
- Mở dashboard QC
- Chọn batch cần kiểm tra
- Ghi nhận kết quả kiểm tra
- Nếu không đạt, update rejection reason và trạng thái batch
- Theo dõi chỉ số chất lượng theo ngày / ca / nhà cung cấp

### 12.3. Với Production Manager
- Vào dashboard sản xuất
- Tạo material request hoặc allocate batch
- Theo dõi lô, tiến độ, nhịp sản xuất, yield
- Quyết định nguyên liệu nào phù hợp để cấp cho dây chuyền

### 12.4. Với Director
- Xem dashboard tổng quan
- Nhận KPI và báo cáo theo cấp quản lý
- Đánh giá hiệu suất kho / QC / sản xuất
- Dùng cho mục tiêu executive summary

---

## 13. Hướng dẫn vận hành và bảo trì

### 13.1. Backup database
Nên backup trước khi import dữ liệu mới hoặc chạy fix scripts.

```bash
mysqldump -u root -p Project2_db > backup_project2.sql
```

### 13.2. Reset dữ liệu mẫu
Nếu cần làm lại môi trường demo:
1. Xóa database `Project2_db`
2. Tạo lại database mới
3. Import `Project2_db.sql`
4. Import `seed_data.sql`

### 13.3. Chạy script sửa lỗi
Trong thư mục `backend/connection` có các file helper như:
- `fix_pass.php`
- `fix_all_en_translations.php`
- `fix_qc_reasons.php`
- `fix_shifts_data.php`

Dùng cho mục đích sửa hash/password, chuẩn hóa dữ liệu hoặc cập nhật dữ liệu phụ trợ.

### 13.4. Chạy kiểm thử
Thư mục `tests/` chứa các script kiểm thử như:
- `test_auth.py`
- `test_login.py`
- `test_module*.py`
- `run_all_tests.py`

Có thể chạy theo cú pháp Python tương ứng.

---

## 14. Một số lỗi thường gặp và cách xử lý

### 14.1. Không vào được trang đăng nhập
- Kiểm tra Apache đã chạy chưa.
- Kiểm tra đường dẫn file PHP và URL đúng.
- Kiểm tra database `Project2_db` đã được import chưa.

### 14.2. Lỗi kết nối database
- Kiểm tra file `backend/connection/db_connect.php`
- Đảm bảo user `root`, password rỗng hoặc đúng cấu hình local
- Kiểm tra MySQL đang chạy trên port 3306

### 14.3. Lỗi login / sai mật khẩu
- Xác nhận username có tồn tại trong bảng `USERS`
- Nếu mật khẩu bị lỗi hash, kiểm tra `fix_pass.php`
- Đảm bảo user đang có `USR_is_active = 1`

### 14.4. API AI / Weather không hoạt động
- Kiểm tra file `.env`
- Đảm bảo key hợp lệ và không bị thiếu chữ/chấm
- Kiểm tra Internet / API quota

### 14.5. Dashboard không tải dữ liệu
- Kiểm tra xem bảng `BATCHES`, `STOCK_MOVEMENTS`, `QC_INSPECTIONS` đã có dữ liệu hay chưa
- Đảm bảo import `seed_data.sql` và schema đúng

---

## 15. Hướng dẫn demo và trình bày đồ án
Khi trình bày đồ án, nên theo flow sau:

1. Đăng nhập bằng tài khoản demo
2. Hiển thị dashboard warehouse / QC / production
3. Chứng minh quy trình nhập kho
4. Chứng minh QC inspection
5. Chứng minh request material và material allocation
6. Hiển thị dashboard analytics và report
7. Demo AI chatbot và n8n automation

Lưu ý: nên chuẩn bị ảnh chụp màn hình cho từng module trong folder `screenshots_by_module/` để trình bày dễ dàng hơn.

---

## 16. Tóm tắt nhanh
Dự án này là một hệ thống quản lý kho - QC - sản xuất thực tế, chạy trên PHP & MySQL, phù hợp cho mô hình nhà máy thực phẩm. Hệ thống cho phép người dùng:
- quản lý tồn kho chi tiết,
- kiểm tra chất lượng nguyên liệu,
- cấp phát nguyên liệu theo FEFO,
- theo dõi sản xuất và finished goods,
- theo dõi báo cáo, analytics và tự động hóa qua n8n.

---

## 17. Kết luận
Nếu bạn muốn chạy demo thành công, hãy làm đúng ba bước quan trọng:
1. Bật Apache + MySQL
2. Import `Project2_db.sql` rồi `seed_data.sql`
3. Truy cập vào đường dẫn login và đăng nhập với tài khoản demo

Đây là hệ thống có đủ yếu tố của một đồ án Capstone 2: nghiệp vụ thực tế, phân quyền, dashboard, báo cáo, AI, automation và triển khai web theo mô hình doanh nghiệp.

---

## 18. Ghi chú người phát triển
- Đây là dự án web theo mô hình backend PHP + frontend PHP + database MySQL.
- Nếu cần nâng cấp lên production, nên thêm:
  - validation dữ liệu mạnh hơn,
  - bảo mật session / CSRF,
  - role permission chuẩn hóa,
  - logging audit,
  - deployment trên Apache + production database.

---

## 19. Tài liệu liên quan
- `Project2_db.sql`
- `seed_data.sql`
- `backend/connection/db_connect.php`
- `backend/connection/process_login.php`
- `frontend/login.php`
- `n8n_workflows/`
- `screenshots_by_module/`

Hy vọng README này sẽ giúp bạn và người khác dễ dàng cài đặt, sử dụng và bảo trì hệ thống một cách nhanh chóng, đúng quy trình và phù hợp với đồ án Capstone Project 2.
