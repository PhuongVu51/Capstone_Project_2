# Capstone Project 2: F&G Food Inventory & Production Management System

*(Bản tiếng Việt ở bên dưới)*

## 🇬🇧 English

### 1. Introduction
This is a comprehensive Inventory and Production Management System designed for the Capstone Project 2 (F&G Food). It digitizes factory operations, streamlining workflows from receiving raw materials to quality control, production, and finished goods management. 

### 2. Key Features
- **Role-Based Access Control (RBAC):** Segregated dashboards and features for 4 main roles: Warehouse Staff, QC Inspector, Production Manager, and Director.
- **Warehouse & Inventory Management:** Track material batches, manage storage zones (capacity, temperature, humidity), and enforce FEFO (First-Expired, First-Out) logic for production allocation.
- **Quality Control (QC):** Dedicated modules to inspect incoming materials, record defects/losses, and generate QC reports.
- **Production Tracking:** Request materials, allocate approved batches to production lines, track yield, and log finished goods into quarantine or export-ready statuses.
- **AI Chatbot Assistant:** An integrated AI assistant powered by Groq/Gemini APIs that allows users to query operational data (Text-to-SQL) using natural language directly from the dashboard.
- **Automated Workflows:** Integration with n8n to send automated weekly summary emails and notifications.
- **Real-time Weather Integration:** Dashboard widgets utilizing Weather API to display real-time storage environmental factors.

### 3. Project Structure
- `backend/`: Server-side logic including Controllers, Models, APIs, DB connections, and Authentication.
- `frontend/`: User interface written in PHP, HTML, CSS (TailwindCSS), and JS.
- `image/` & `screenshots_by_module/`: Static assets and documentation images.
- `n8n_workflows/`: Automation scripts and templates for n8n.
- `.env`: Configuration file for API keys (Weather API, Groq/Gemini).
- `Project2_db.sql` & `seed_data.sql`: Database schema and mock data.

### 4. Installation & Setup
1. **Prerequisites:**
   - XAMPP / WAMP / MAMP (Apache & MySQL).
   - PHP 7.4 or higher.
2. **Setup Workspace:**
   - Clone or extract this repository into your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\Capstone_Project_2`).
3. **Database Configuration:**
   - Start Apache and MySQL in XAMPP.
   - Open phpMyAdmin (`http://localhost/phpmyadmin`).
   - Create a new database named `Project2_db`.
   - Import `Project2_db.sql` first, followed by `seed_data.sql` to populate mock data.
   - *(Optional)* Update DB credentials in `backend/connection/db_connect.php` if you are not using default XAMPP settings (`root` / empty password).
4. **Environment Variables:**
   - Ensure the `.env` file exists in the root directory.
   - Provide your API keys (e.g., `WEATHER_API_KEY`, `GROQ_API_KEY`).
5. **Run the Application:**
   - Open a modern web browser and navigate to: `http://localhost/Capstone_Project_2/frontend/login.php`
   - Login using the sample accounts provided in the `seed_data.sql` file.

---

## 🇻🇳 Tiếng Việt

### 1. Giới thiệu
Đây là Hệ thống Quản lý Kho và Sản xuất toàn diện được thiết kế cho Đồ án Capstone Project 2 (Công ty Thực phẩm F&G). Hệ thống giúp số hóa các hoạt động của nhà máy, tối ưu hóa quy trình làm việc từ khâu nhận nguyên liệu thô đến kiểm tra chất lượng, sản xuất và quản lý thành phẩm.

### 2. Các Tính Năng Nổi Bật
- **Phân Quyền Người Dùng (RBAC):** Giao diện và chức năng được thiết kế riêng biệt cho 4 vai trò chính: Nhân viên Kho, Nhân viên QC, Quản lý Sản xuất, và Giám đốc.
- **Quản lý Kho & Tồn Kho:** Theo dõi các lô nguyên liệu, quản lý khu vực lưu trữ (sức chứa, nhiệt độ, độ ẩm) và áp dụng logic xuất kho FEFO (Hết hạn trước - Xuất trước).
- **Kiểm Soát Chất Lượng (QC):** Các module chuyên dụng để kiểm tra nguyên liệu đầu vào, ghi nhận phế phẩm/hao hụt, và tạo báo cáo QC.
- **Theo Dõi Sản Xuất:** Yêu cầu nguyên liệu, cấp phát các lô hàng đã qua kiểm duyệt cho dây chuyền sản xuất, theo dõi tỷ lệ thu hồi (yield), và lưu trữ thành phẩm vào trạng thái cách ly hoặc sẵn sàng xuất khẩu.
- **Trợ lý AI (Chatbot):** Tích hợp AI thông qua API của Groq/Gemini, cho phép người dùng truy vấn dữ liệu vận hành (Text-to-SQL) bằng ngôn ngữ tự nhiên ngay trên bảng điều khiển.
- **Tự động hóa Quy trình:** Tích hợp với n8n để tự động gửi email báo cáo tổng hợp hàng tuần.
- **Tích hợp Thời tiết:** Widget trên bảng điều khiển sử dụng Weather API để hiển thị các yếu tố môi trường lưu trữ theo thời gian thực.

### 3. Cấu trúc Thư mục
- `backend/`: Logic phía máy chủ bao gồm Controller, Model, API, Kết nối CSDL và Xác thực.
- `frontend/`: Giao diện người dùng được viết bằng PHP, HTML, CSS (TailwindCSS) và JS.
- `image/` & `screenshots_by_module/`: Tài nguyên tĩnh và hình ảnh tài liệu.
- `n8n_workflows/`: Các kịch bản tự động hóa cho n8n.
- `.env`: File cấu hình chứa các API Key (Weather API, Groq/Gemini).
- `Project2_db.sql` & `seed_data.sql`: Lược đồ cơ sở dữ liệu và dữ liệu mẫu.

### 4. Hướng dẫn Cài đặt & Khởi chạy
1. **Yêu cầu hệ thống:**
   - XAMPP / WAMP / MAMP (Apache & MySQL).
   - PHP 7.4 trở lên.
2. **Thiết lập thư mục:**
   - Copy thư mục dự án vào thư mục `htdocs` của XAMPP (ví dụ: `C:\xampp\htdocs\Capstone_Project_2`).
3. **Cấu hình Cơ sở dữ liệu:**
   - Khởi động Apache và MySQL trong XAMPP.
   - Mở phpMyAdmin (`http://localhost/phpmyadmin`).
   - Tạo một cơ sở dữ liệu mới tên là `Project2_db`.
   - Nhập (Import) file `Project2_db.sql` trước, sau đó nhập file `seed_data.sql` để có dữ liệu mẫu.
   - *(Tùy chọn)* Cập nhật thông tin kết nối DB trong `backend/connection/db_connect.php` nếu bạn không dùng mặc định của XAMPP (`root` / mật khẩu rỗng).
4. **Cấu hình Môi trường:**
   - Kiểm tra file `.env` ở thư mục gốc của dự án.
   - Điền các API Key của bạn (ví dụ: `WEATHER_API_KEY`, `GROQ_API_KEY`).
5. **Chạy Ứng dụng:**
   - Mở trình duyệt web và truy cập đường dẫn: `http://localhost/Capstone_Project_2/frontend/login.php`
   - Đăng nhập bằng các tài khoản mẫu đã được tạo sẵn trong file `seed_data.sql`.
