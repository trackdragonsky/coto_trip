# Cô Tô Trip - PHP/MySQL

Ứng dụng đã được chuyển từ Python Flask sang PHP thuần dùng MySQL và chạy được trong XAMPP.

## Cài đặt nhanh trên XAMPP

1. Copy toàn bộ thư mục dự án vào `C:\xampp\htdocs\coto_trip`.
2. Bật **Apache** và **MySQL** trong XAMPP Control Panel.
3. Mở `http://localhost/phpmyadmin` và import file `db.sql`.
4. Database mặc định là `db`. Nếu XAMPP của bạn dùng user/password khác, sửa trong `config.php`.
5. Mở ứng dụng tại `http://localhost/coto_trip/`.

Các trang con chạy theo đúng thư mục con XAMPP:

- `http://localhost/coto_trip/expenses`
- `http://localhost/coto_trip/gallery`
- `http://localhost/coto_trip/map`
- `http://localhost/coto_trip/ai`

File `.htaccess` sẽ route URL đẹp về `index.php`. Nếu XAMPP báo 404 ở các trang con, hãy bật `mod_rewrite` trong Apache.

## Cấu hình database mặc định

- Database: `db`
- Host: `127.0.0.1`
- Port: `3306`
- User: `root`
- Password: rỗng

## API tùy chọn

- `WEATHER_API_KEY`: khóa OpenWeatherMap cho thời tiết.
- `GROQ_API_KEY`: khóa Groq cho trợ lý AI.
- `GROQ_MODEL`: model Groq, mặc định `llama-3.1-8b-instant`.
