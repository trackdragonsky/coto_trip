<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Chi phí chuyến đi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/static/style.css')) ?>">
    <link rel="icon"type="image/jpeg" href="<?= e(url('/static/images/logo.jpg')) ?>">
    <link rel="apple-touch-icon" href="<?= e(url('/static/images/logo.jpg')) ?>">
    <link rel="manifest" href="<?= e(url('/static/manifest.json')) ?>">
</head>

<body class="app-body expense-app">

    <main class="mobile-shell expense-shell">
        <header class="page-hero compact-hero expense-hero">
            <div>
                <p class="eyebrow">Cô Tô 2026</p>
                <h1>Chi phí nhóm</h1>
                <p>Theo dõi khoản chi, số dư và phần chia của từng người.</p>
            </div>
            <div class="hero-chip">
                <i data-lucide="wallet-cards"></i>
                <span><?= number_format($total, 0, ",", ".") ?>đ</span>
            </div>
        </header>
        <section class="component-card collection-card">

            <div class="section-heading collection-header">

                <div>
                    <p class="eyebrow">Quỹ nhóm</p>
                    <h2>
                        <i data-lucide="piggy-bank"></i>
                        Tổng tiền đã thu
                    </h2>
                </div>

                <button
                    id="open-collection-modal"
                    class="add-collection-btn"
                >+</button>

            </div>

            <div class="collection-total">
                <?= number_format($total_collected, 0, ",", ".") ?> VNĐ
            </div>

        </section>

        <section class="component-card collection-history-card">

            <div class="section-heading">
                <div>
                    <p class="eyebrow">Lịch sử</p>
                    <h2><i data-lucide="history"></i>Thu tiền thành viên</h2>
                </div>
            </div>

            <div class="collection-list">

                <?php foreach ($collections as $item): ?>

                <div class="collection-row">

                    <div>

                        <strong>
                            <?= e($item["member_name"]) ?>
                        </strong>

                        <p>
                            <?= e($item["collected_at"]) ?>
                        </p>

                    </div>

                    <div class="collection-actions">

                        <span>
                            <?= number_format($item["amount"], 0, ",", ".") ?> VNĐ
                        </span>

                        <button
                            class="delete-collection-btn"
                            onclick="deleteCollection(<?= (int) $item["id"] ?>)"
                        >
                            <i data-lucide="trash-2"></i>
                        </button>

                    </div>

                </div>

                <?php endforeach; ?>

            </div>

        </section>
        <section class="component-card expense-summary-card" id="expense-summary" aria-label="Tổng quan chi tiêu">
            <div class="skeleton-line"></div>
        </section>

        <section class="component-card expense-form-card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Ghi nhận</p>
                    <h2>Thêm chi phí</h2>
                </div>
            </div>

            <form id="expense-form" class="expense-form" autocomplete="off">
                <input type="hidden" id="title" name="title" value="Di chuyển">
                <input type="hidden" id="category" name="category" value="Di chuyển">

                <div id="expense-category-selector" class="category-grid" aria-label="Chọn danh mục chi phí"></div>

                <label class="field-label" for="note">Ghi chú</label>
                <textarea id="note" name="note" rows="3" placeholder="Ví dụ: tàu cao tốc Vân Đồn đi Cô Tô"></textarea>

                <label class="field-label" for="amount">Số tiền</label>
                <div class="money-input">
                    <input id="amount" name="amount" inputmode="numeric" placeholder="0" aria-label="Số tiền">
                    <span>VNĐ</span>
                </div>

                <label class="field-label" for="payer">Người chi</label>
                <select id="payer" name="payer">
                    <option>Long</option>
                    <option>Hoa</option>
                    <option>Linh</option>
                    <option>LAnh</option>
                    <option>Lan</option>
                    <option>Bắc</option>                                       
                </select>

                <label class="toggle-row">
                    <input type="checkbox" id="split-evenly" checked>
                    <span>Chia đều cho mọi người</span>
                </label>

                <button id="add-expense-button" class="primary-action" type="submit">
                    <i data-lucide="plus"></i>
                    Thêm chi phí
                </button>
            </form>
        </section>

        <section class="component-card expense-list-card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Lịch sử</p>
                    <h2>Các khoản đã chi</h2>
                </div>
            </div>
            <div id="expense-list" class="expense-list"></div>
        </section>
        <div id="collection-modal" class="collection-modal">

            <div class="collection-modal-content">

                <h3>Thêm khoản thu</h3>

                <label>Thành viên</label>

                <select id="collection-member">

                    <?php foreach ($members as $member): ?>
                    <option value="<?= (int) $member["id"] ?>">
                        <?= e($member["name"]) ?>
                    </option>
                    <?php endforeach; ?>

                </select>

                <label>Số tiền</label>

                <input
                    id="collection-amount"
                    type="text"
                    placeholder="0 VNĐ"
                >

                <div class="collection-modal-actions">

                    <button
                        id="close-collection-modal"
                        class="secondary-btn"
                    >
                        Hủy
                    </button>

                    <button
                        id="save-collection-btn"
                        class="primary-action"
                    >
                        Lưu khoản thu
                    </button>

                </div>

            </div>

        </div>
    </main>

    <nav class="bottom-nav" aria-label="Điều hướng chính">
        <a href="<?= e(url('/')) ?>" aria-label="Trang chủ"><i data-lucide="house"></i><span>Trang chủ</span></a>
        <a href="<?= e(url('/expenses')) ?>" class="active-nav" aria-label="Chi phí"><i data-lucide="wallet"></i><span>Chi phí</span></a>
        <a href="<?= e(url('/gallery')) ?>" aria-label="Thư viện ảnh"><i data-lucide="images"></i><span>Ảnh</span></a>
        <a href="<?= e(url('/ai')) ?>" aria-label="Trợ lý du lịch"><i data-lucide="bot"></i><span>Trợ lý</span></a>
        <a href="<?= e(url('/map')) ?>" aria-label="Lịch trình"><i data-lucide="route"></i><span>Lịch</span></a>
    </nav>

    <script>
        window.TRIP_MEMBERS = <?= json_encode($members, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.EXPENSE_DATA = <?= json_encode(array_map(fn($expense) => ["id" => $expense["id"], "title" => $expense["title"] ?? "", "payer" => $expense["payer_name"] ?? "", "amount" => $expense["amount"] ?? 0, "category" => $expense["category"] ?? "", "note" => $expense["note"] ?? ""], $expenses), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>window.APP_BASE_URL = <?= json_encode(base_path(), JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="<?= e(url('/static/script.js')) ?>"></script>
</body>

</html>
