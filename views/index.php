<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Cô Tô 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/static/style.css')) ?>">
    <link rel="icon"type="image/jpeg" href="<?= e(url('/static/images/logo.jpg')) ?>">
    <link rel="apple-touch-icon" href="<?= e(url('/static/images/logo.jpg')) ?>">
    <link rel="manifest" href="<?= e(url('/static/manifest.json')) ?>">
</head>


<body class="app-body home-app">

    <main class="mobile-shell home-shell">
        <section class="home-hero" aria-label="Mở đầu chuyến đi">
            <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80" alt="Biển Cô Tô" loading="eager">
            <div class="hero-gradient"></div>
            <div class="floating-orbit orbit-one"></div>
            <div class="floating-orbit orbit-two"></div>
            <div class="home-hero-content">
                <p id="home-greeting" class="eyebrow">Chào buổi sáng</p>
                <h1>Cô Tô 2026</h1>
                <p>Không gian theo dõi chuyến đi của Long, Hoa, Lan, Linh, LAnh và Bắc.</p>
                <div class="trip-countdown" aria-label="Đếm ngược đến ngày khởi hành">
                    <div><strong id="home-days">0</strong><span>Ngày</span></div>
                    <div><strong id="home-hours">0</strong><span>Giờ</span></div>
                    <div><strong id="home-minutes">0</strong><span>Phút</span></div>
                </div>
            </div>
        </section>

        <section class="component-card weather-widget" id="weather-widget" aria-label="Thời tiết Cô Tô">
            <div class="skeleton-line"></div>
        </section>

        <section class="home-overview-grid" aria-label="Tổng quan chuyến đi">
            <article class="stat-card">
                <div class="stat-icon"><i data-lucide="map-pinned"></i></div>
                <span>Điểm đến</span>
                <strong>Cô Tô</strong>
            </article>
            <article class="stat-card">
                <div class="stat-icon"><i data-lucide="users-round"></i></div>
                <span>Thành viên</span>
                <strong><?= count($members) ?></strong>
            </article>
            <article class="stat-card">
                <div class="stat-icon">
                    <i data-lucide="camera"></i>
                </div>
                <span>Kỷ niệm</span>
                <strong><?= (int) $gallery_count ?></strong>
            </article>
        </section>

        <section class="component-card member-preview" aria-label="Thành viên chuyến đi">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Thành viên</p>
                    <h2>Đội hình chuyến đi</h2>
                </div>
            </div>
            <div class="member-preview-list">

                <?php foreach ($members as $member): ?>
                <div class="member-card">

                    <img
                        src="<?= e($member["avatar"]) ?>"
                        alt="<?= e($member["name"]) ?>"
                        class="member-avatar"
                    >

                    <span><?= e($member["name"]) ?></span>

                </div>
                <?php endforeach; ?>

            </div>
        </section>

        <section class="component-card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Truy cập nhanh</p>
                    <h2>Mở nhanh</h2>
                </div>
            </div>
            <div id="home-quick-actions" class="home-action-grid"></div>
        </section>

        <section class="component-card upcoming-preview">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Sắp tới</p>
                    <h2>Lịch trình gần nhất</h2>
                </div>
                <a class="text-link" href="<?= e(url('/map')) ?>">Mở</a>
            </div>
            <?php
            $icon_map = ["Di chuyển"=>"bus-front", "Khách sạn"=>"hotel", "Ăn uống"=>"utensils-crossed", "Chụp ảnh"=>"camera", "Vui chơi"=>"party-popper", "Nghỉ ngơi"=>"bed"];
            $color_map = ["Di chuyển"=>"#2563eb", "Khách sạn"=>"#8b5cf6", "Ăn uống"=>"#f97316", "Chụp ảnh"=>"#ec4899", "Vui chơi"=>"#10b981", "Nghỉ ngơi"=>"#64748b"];
            ?>

        <?php foreach ($upcoming_itineraries as $item): ?>
        <div class="upcoming-item">

            <div
                class="timeline-icon"
                style="background:<?= e($color_map[$item["activity_type"]] ?? "#2563eb") ?>"
            >
                <i data-lucide="<?= e($icon_map[$item["activity_type"]] ?? "calendar") ?>"></i>
            </div>

            <div>
                <strong>
                    <?= e($item["trip_time"]) ?> · <?= e($item["title"]) ?>
                </strong>

                <p>
                    <?= e($item["detail"] ?: $item["trip_date"]) ?>
                </p>
            </div>

        </div>
        <?php endforeach; ?>
        </section>
    </main>

    <nav class="bottom-nav" aria-label="Điều hướng chính">
        <a href="<?= e(url('/')) ?>" class="active-nav" aria-label="Trang chủ"><i data-lucide="house"></i><span>Trang chủ</span></a>
        <a href="<?= e(url('/expenses')) ?>" aria-label="Chi phí"><i data-lucide="wallet"></i><span>Chi phí</span></a>
        <a href="<?= e(url('/gallery')) ?>" aria-label="Thư viện ảnh"><i data-lucide="images"></i><span>Ảnh</span></a>
        <a href="<?= e(url('/ai')) ?>" aria-label="Trợ lý du lịch"><i data-lucide="bot"></i><span>Trợ lý</span></a>
        <a href="<?= e(url('/map')) ?>" aria-label="Lịch trình"><i data-lucide="route"></i><span>Lịch</span></a>
    </nav>

    <script>
        window.TRIP_MEMBERS = ["Long", "Hoa", "Lan", "Linh", "LAnh"];
        window.HOME_WEATHER = {
            main: <?= json_encode($weather_main, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            description: <?= json_encode($weather_desc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            temp: <?= round($current["main"]["temp"] ?? 0) ?>,
            feelsLike: <?= round($current["main"]["feels_like"] ?? 0) ?>,
            humidity: <?= (int) ($current["main"]["humidity"] ?? 0) ?>,
            wind: <?= (int) ($current["wind"]["speed"] ?? 0) ?>,
            location: "Cô Tô, Quảng Ninh",
            forecast: <?= json_encode(array_map(fn($item) => ["date" => substr($item["dt_txt"], 0, 10), "temp" => round($item["main"]["temp"]), "main" => $item["weather"][0]["main"] ?? "Clear"], $forecast["list"] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>window.APP_BASE_URL = <?= json_encode(base_path(), JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="<?= e(url('/static/script.js')) ?>"></script>
</body>

</html>
