# Capstone Project 2

Đây là một hệ thống quản lý kho và sản xuất dành cho đồ án Capstone Project 2.

## Mục tiêu

Hệ thống hỗ trợ các chức năng chính sau:
- Quản lý đăng nhập và phân quyền người dùng
- Nhập kho / xuất kho / theo dõi batch
- Quản lý sản phẩm, nhà cung cấp và khu vực kho
- Theo dõi QC inspection và báo cáo
- Dashboard cho Warehouse Staff, QC và Production Manager

## Cấu trúc thư mục

- backend/: logic xử lý server, controller, model, authentication
- frontend/: giao diện người dùng PHP
- image/: ảnh tài nguyên của hệ thống
- Project2_db.sql: schema cơ sở dữ liệu
- seed_data.sql: dữ liệu mẫu

## Yêu cầu môi trường

- XAMPP / Apache + MySQL
- PHP 7.4+
- Browser hiện đại

## Cài đặt và chạy

1. Đưa thư mục dự án vào thư mục web của XAMPP, ví dụ:
   - C:\xampp\htdocs\Capstone_Project_2\Capstone_Project_2

2. Khởi động Apache và MySQL trong XAMPP.

3. Tạo cơ sở dữ liệu MySQL và import các file:
   - Project2_db.sql
   - seed_data.sql

4. Mở trình duyệt và truy cập:
   - http://localhost/Capstone_Project_2/Capstone_Project_2/frontend/login.php

## Tài khoản mẫu

Sau khi import dữ liệu mẫu, có thể đăng nhập bằng các tài khoản được tạo trong file seed_data.sql.

## Ghi chú

- Nếu cần chỉnh cấu hình kết nối DB, vui lòng kiểm tra file:
  - backend/connection/db_connect.php

## Tác giả

Capstone Project 2
