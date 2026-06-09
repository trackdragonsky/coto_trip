# Cô Tô Trip - PHP/MySQL

Ứng dụng đã được chuyển từ Python Flask sang PHP thuần dùng MySQL.

## Cài đặt nhanh

1. Upload toàn bộ mã nguồn lên hosting PHP.
2. Trong phpMyAdmin của XAMPP, tạo database tên `db` rồi import file `db.sql`.
3. Nếu dùng tên database khác, sửa `config.php` hoặc đặt biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`. Mặc định dự án dùng `DB_NAME=db`, user `root`, mật khẩu rỗng theo XAMPP.
4. Đặt thư mục dự án trong `htdocs` (ví dụ `htdocs/coto_trip`) hoặc trỏ document root vào thư mục dự án. Ứng dụng tự nhận base path `/coto_trip` khi chạy trong thư mục con và file `.htaccess` sẽ route các URL như `/expenses`, `/gallery`, `/map`, `/ai` về `index.php`.

## API tùy chọn

- `WEATHER_API_KEY`: khóa OpenWeatherMap cho thời tiết.
- `GROQ_API_KEY`: khóa Groq cho trợ lý AI.
- `GROQ_MODEL`: model Groq, mặc định `llama-3.1-8b-instant`.
