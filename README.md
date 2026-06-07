# Cô Tô Trip - PHP/MySQL

Ứng dụng đã được chuyển từ Python Flask sang PHP thuần dùng MySQL.

## Cài đặt nhanh

1. Upload toàn bộ mã nguồn lên hosting PHP.
2. Vào phpMyAdmin và import file `db.sql`.
3. Sửa `config.php` hoặc đặt biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.
4. Trỏ document root vào thư mục dự án. File `.htaccess` sẽ route các URL như `/expenses`, `/gallery`, `/map`, `/ai` về `index.php`.

## API tùy chọn

- `WEATHER_API_KEY`: khóa OpenWeatherMap cho thời tiết.
- `GROQ_API_KEY`: khóa Groq cho trợ lý AI.
- `GROQ_MODEL`: model Groq, mặc định `llama-3.1-8b-instant`.
