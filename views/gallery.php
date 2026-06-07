<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Thư viện ảnh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/static/style.css">
    <link rel="icon"type="image/jpeg" href="/static/images/logo.jpg">
    <link rel="apple-touch-icon" href="/static/images/logo.jpg">
    <link rel="manifest" href="/static/manifest.json">
</head>

<body class="app-body gallery-app">
    <main class="mobile-shell gallery-shell">
        <header class="page-hero gallery-hero">
            <div>
                <p class="eyebrow">Thư viện</p>
                <h1>Kỷ niệm chuyến đi</h1>
                <p>Lưu lại ảnh, mở xem toàn màn hình và gom khoảnh khắc của cả nhóm.</p>
            </div>
        </header>

        <section class="component-card gallery-uploader">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Tải ảnh</p>
                    <h2>Thêm khoảnh khắc</h2>
                </div>
            </div>

            <div class="uploader-actions">
                <label class="media-button">
                    <input id="gallery-file-input" type="file" accept="image/*" multiple>
                    <i data-lucide="upload-cloud"></i>
                    <span>Tải ảnh lên</span>
                </label>
                <label class="media-button">
                    <input
                        id="gallery-camera-input"
                        type="file"
                        accept="image/*"
                        capture="environment"
                        multiple
                    >
                    <i data-lucide="camera"></i>
                    <span>Chụp ảnh</span>
                </label>
            </div>
        </section>

        <section>
            <div class="section-heading flush-heading">
                <div>
                    <p class="eyebrow">Album nhóm</p>
                    <h2>Ảnh đã lưu</h2>
                </div>
                <span id="gallery-count" class="count-pill">0 ảnh</span>
            </div>
            <section class="memory-section">

                <h2 class="memory-title">
                    ✨ Nơi Lưu Trữ Khoảnh Khắc ✨
                </h2>

                <p class="memory-subtitle">
                    Mỗi bức ảnh là một câu chuyện, mỗi hành trình là một kỷ niệm đẹp.
                </p>

                <div class="gallery-slider">

                    <button id="prev-slide" class="slider-btn">
                        ❮
                    </button>

                    <div id="gallery-grid" class="slider-container"></div>

                    <button id="next-slide" class="slider-btn">
                        ❯
                    </button>

                </div>

            </section>
        </section>
    </main>

    <div id="image-preview-modal" class="preview-modal" aria-hidden="true">

        <button class="modal-close" type="button" aria-label="Đóng xem ảnh">
            <i data-lucide="x"></i>
        </button>

        <a
            id="download-image-btn"
            class="download-btn preview-download"
            href="#"
            download
        >
            <i data-lucide="download"></i>
            <span>Tải ảnh</span>
        </a>

        <img id="preview-image" alt="Ảnh xem trước">
    </div>

    <nav class="bottom-nav" aria-label="Điều hướng chính">
        <a href="/" aria-label="Trang chủ"><i data-lucide="house"></i><span>Trang chủ</span></a>
        <a href="/expenses" aria-label="Chi phí"><i data-lucide="wallet"></i><span>Chi phí</span></a>
        <a href="/gallery" class="active-nav" aria-label="Thư viện ảnh"><i data-lucide="images"></i><span>Ảnh</span></a>
        <a href="/ai" aria-label="Trợ lý du lịch"><i data-lucide="bot"></i><span>Trợ lý</span></a>
        <a href="/map" aria-label="Lịch trình"><i data-lucide="route"></i><span>Lịch</span></a>
    </nav>
    <script>
    window.GALLERY_IMAGES = <?= json_encode($gallery_items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.TRIP_MEMBERS = <?= json_encode($members, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="/static/script.js?v=8"></script>
    
</body>

</html>
