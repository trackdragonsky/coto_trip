<?php
$config = require __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    global $config;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_port'], $config['db_name']);
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function query_all(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
function query_one(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}
function execute_sql(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) db()->lastInsertId();
}
function e($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function json_out($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function base_path(): string {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return ($base === '' || $base === '.') ? '' : $base;
}

function url(string $path = ''): string {
    $base = base_path();
    if ($path === '' || $path === '/') return $base === '' ? '/' : $base . '/';
    if (preg_match('#^(https?://|//)#', $path)) return $path;
    return $base . '/' . ltrim($path, '/');
}

function request_path(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
    $base = base_path();
    if ($base !== '' && ($path === $base || strpos($path, $base . '/') === 0)) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    if ($path === '/index.php' || strpos($path, '/index.php/') === 0) {
        $path = substr($path, strlen('/index.php')) ?: '/';
    }
    return '/' . trim($path, '/');
}

function asset_url(?string $value): string {
    if (!$value) return '';
    $path = str_replace('\\', '/', $value);
    if (preg_match('#^(https?://|//)#', $path)) return $path;
    return url($path);
}
function money_to_int($value): int { return (int) round((float) ($value ?? 0)); }
function parse_amount($value): float {
    $clean = preg_replace('/[^0-9]/', '', (string) ($value ?? ''));
    if ($clean === '') throw new InvalidArgumentException('Số tiền không hợp lệ');
    $amount = (float) $clean;
    if ($amount <= 0) throw new InvalidArgumentException('Số tiền phải lớn hơn 0');
    return $amount;
}
function normalize_bool($value, bool $default = true): bool {
    if ($value === null) return $default;
    return !in_array(strtolower((string) $value), ['0', 'false', 'off', 'no'], true);
}
function render(string $view, array $data = []): void {
    extract($data, EXTR_SKIP);
    require __DIR__ . '/views/' . $view . '.php';
}
function default_members(): array {
    return [
        ['name' => 'Long', 'avatar' => 'static/images/long.jpg', 'avatar_url' => asset_url('static/images/long.jpg')],
        ['name' => 'Hoa', 'avatar' => 'static/images/hoa.jpg', 'avatar_url' => asset_url('static/images/hoa.jpg')],
        ['name' => 'Linh', 'avatar' => 'static/images/linh.jpg', 'avatar_url' => asset_url('static/images/linh.jpg')],
        ['name' => 'LAnh', 'avatar' => 'static/images/lanh.jpg', 'avatar_url' => asset_url('static/images/lanh.jpg')],
        ['name' => 'Lan', 'avatar' => 'static/images/lan.jpg', 'avatar_url' => asset_url('static/images/lan.jpg')],
        ['name' => 'Bắc', 'avatar' => 'static/images/bac.jpg', 'avatar_url' => asset_url('static/images/bac.jpg')],
    ];
}
function serialize_member(array $row): array {
    return [
        'id' => (int) $row['id'], 'name' => $row['name'], 'avatar' => $row['avatar'] ?? '',
        'avatar_url' => asset_url($row['avatar'] ?? ''), 'role_name' => $row['role_name'] ?: 'Thành viên',
        'created_at' => (string) ($row['created_at'] ?? ''),
    ];
}
function get_members(): array {
    return array_map('serialize_member', query_all('SELECT id, name, avatar, role_name, created_at FROM members ORDER BY id ASC'));
}
function find_member($value): ?array {
    if (!$value) return null;
    if (ctype_digit((string) $value)) return query_one('SELECT * FROM members WHERE id = ?', [(int) $value]);
    return query_one('SELECT * FROM members WHERE name = ?', [trim((string) $value)]);
}
function serialize_expense(array $row): array {
    $avatar = $row['payer_avatar'] ?? '';
    return [
        'id' => (int) $row['id'], 'title' => $row['title'], 'category' => $row['category'] ?: $row['title'],
        'note' => $row['note'] ?? '', 'payer_id' => (int) $row['payer_id'], 'payer_name' => $row['payer_name'] ?: 'Không rõ',
        'payer_avatar' => $avatar, 'payer_avatar_url' => asset_url($avatar), 'amount' => money_to_int($row['amount']),
        'split_evenly' => (bool) $row['split_evenly'], 'created_at' => (string) $row['created_at'],
    ];
}
function get_expenses(): array {
    $rows = query_all('SELECT e.*, m.name AS payer_name, m.avatar AS payer_avatar FROM expenses e LEFT JOIN members m ON e.payer_id=m.id ORDER BY e.created_at DESC, e.id DESC');
    return array_map('serialize_expense', $rows);
}
function get_expense(int $id): ?array {
    $row = query_one('SELECT e.*, m.name AS payer_name, m.avatar AS payer_avatar FROM expenses e LEFT JOIN members m ON e.payer_id=m.id WHERE e.id=?', [$id]);
    return $row ? serialize_expense($row) : null;
}
function get_collections(): array {
    return array_map(fn($r) => ['id'=>(int)$r['id'], 'member_name'=>$r['member_name'], 'amount'=>money_to_int($r['amount']), 'collected_at'=>(string)$r['collected_at']],
        query_all('SELECT c.id,c.amount,c.collected_at,m.name AS member_name FROM collections c JOIN members m ON c.member_id=m.id ORDER BY c.collected_at DESC'));
}
function calculate_expense_stats(array $expenses, array $members): array {
    $total = array_sum(array_column($expenses, 'amount'));
    $shared = array_sum(array_map(fn($e) => !empty($e['split_evenly']) ? $e['amount'] : 0, $expenses));
    $count = count($members); $per = $count ? (int) round($shared / $count) : 0;
    $paid = array_fill_keys(array_map(fn($m) => $m['id'], $members), 0);
    foreach ($expenses as $expense) if (isset($paid[$expense['payer_id']])) $paid[$expense['payer_id']] += $expense['amount'];
    $balances = [];
    foreach ($members as $m) $balances[] = ['member_id'=>$m['id'], 'member_name'=>$m['name'], 'avatar_url'=>$m['avatar_url'], 'paid'=>$paid[$m['id']] ?? 0, 'share'=>$per, 'balance'=>($paid[$m['id']] ?? 0) - $per];
    return ['total'=>$total, 'shared_total'=>$shared, 'per_person'=>$per, 'balances'=>$balances, 'settlements'=>[], 'top_payer'=>$balances ? $balances[0] : null];
}
function serialize_gallery_item(array $row): array {
    return ['id'=>(int)$row['id'], 'image_url'=>$row['image_url'], 'src'=>asset_url($row['image_url']), 'caption'=>$row['caption'] ?: 'Khoảnh khắc chuyến đi', 'uploaded_by'=>$row['uploaded_by'], 'uploader_name'=>$row['uploader_name'] ?: 'Nhóm du lịch', 'uploader_avatar_url'=>asset_url($row['uploader_avatar'] ?? ''), 'created_at'=>(string)$row['created_at']];
}
function get_gallery_items(): array {
    return array_map('serialize_gallery_item', query_all('SELECT g.*, m.name AS uploader_name, m.avatar AS uploader_avatar FROM gallery g LEFT JOIN members m ON g.uploaded_by=m.id ORDER BY g.created_at DESC, g.id DESC'));
}
function get_gallery_item(int $id): ?array {
    $row = query_one('SELECT g.*, m.name AS uploader_name, m.avatar AS uploader_avatar FROM gallery g LEFT JOIN members m ON g.uploaded_by=m.id WHERE g.id=?', [$id]);
    return $row ? serialize_gallery_item($row) : null;
}
function serialize_itinerary(array $row): array {
    return ['id'=>(int)$row['id'], 'title'=>$row['title'], 'trip_date'=>(string)$row['trip_date'], 'trip_time'=>substr((string)$row['trip_time'], 0, 5), 'activity_type'=>$row['activity_type'] ?: 'Hoạt động', 'detail'=>$row['detail'] ?? '', 'created_by'=>$row['created_by'], 'creator_name'=>$row['creator_name'] ?: 'Nhóm du lịch', 'created_at'=>(string)$row['created_at']];
}
function get_itineraries(): array {
    return array_map('serialize_itinerary', query_all('SELECT i.*, m.name AS creator_name FROM itineraries i LEFT JOIN members m ON i.created_by=m.id ORDER BY i.trip_date ASC, i.trip_time ASC, i.id ASC'));
}
function get_itinerary(int $id): ?array {
    $row = query_one('SELECT i.*, m.name AS creator_name FROM itineraries i LEFT JOIN members m ON i.created_by=m.id WHERE i.id=?', [$id]);
    return $row ? serialize_itinerary($row) : null;
}
function get_ai_messages(int $limit = 80): array { return query_all('SELECT id,sender,message,created_at FROM ai_messages ORDER BY created_at ASC, id ASC LIMIT ' . (int)$limit); }
function save_ai_message(string $sender, string $message): int { return execute_sql('INSERT INTO ai_messages(sender,message) VALUES(?,?)', [$sender, $message]); }
function weather_timezone(): DateTimeZone {
    return new DateTimeZone('Asia/Ho_Chi_Minh');
}

function weather_location(): array {
    return ['lat' => 20.9747, 'lon' => 107.7665, 'label' => 'Cô Tô, Quảng Ninh'];
}

function daily_forecast_items(array $items, int $days = 4): array {
    $today = (new DateTimeImmutable('now', weather_timezone()))->format('Y-m-d');
    $byDate = [];

    foreach ($items as $item) {
        $date = substr((string) ($item['dt_txt'] ?? ''), 0, 10);

        if (!$date || $date <= $today) {
            continue;
        }

        $hour = (int) substr((string) ($item['dt_txt'] ?? '12:00:00'), 11, 2);
        $score = abs($hour - 12);

        if (!isset($byDate[$date]) || $score < $byDate[$date]['score']) {
            $byDate[$date] = ['score' => $score, 'item' => $item];
        }
    }

    ksort($byDate);

    return array_values(array_slice(array_map(fn($entry) => $entry['item'], $byDate), 0, $days));
}

function fallback_weather(): array {
    $list = [];
    $today = new DateTimeImmutable('now', weather_timezone());
    for ($i=1; $i<=4; $i++) {
        $forecastDate = $today->modify("+$i day")->setTime(12, 0);
        $list[] = ['dt_txt'=>$forecastDate->format('Y-m-d H:i:s'), 'main'=>['temp'=>28 + ($i % 3)], 'weather'=>[['main'=>'Clear', 'description'=>'Trời đẹp']]];
    }
    return [['weather'=>[['main'=>'Clear','description'=>'Trời đẹp']], 'main'=>['temp'=>29,'feels_like'=>32,'humidity'=>78], 'wind'=>['speed'=>14], 'name'=>'Cô Tô'], ['list'=>$list]];
}
function get_weather(): array {
    global $config;
    if (!$config['weather_api_key']) return fallback_weather();
    $location = weather_location();
    $params = http_build_query(['lat'=>$location['lat'], 'lon'=>$location['lon'], 'appid'=>$config['weather_api_key'], 'units'=>'metric', 'lang'=>'vi']);
    $current = @json_decode(@file_get_contents("https://api.openweathermap.org/data/2.5/weather?$params"), true);
    $forecast = @json_decode(@file_get_contents("https://api.openweathermap.org/data/2.5/forecast?$params"), true);
    if (!$current || empty($current['weather']) || empty($forecast['list'])) return fallback_weather();
    $current['wind']['speed'] = (int) round(($current['wind']['speed'] ?? 0) * 3.6);
    return [$current, ['list'=>daily_forecast_items($forecast['list'])]];
}
function weather_payload(array $current, array $forecast): array {
    $weather = $current['weather'][0] ?? [];
    $location = weather_location();

    return [
        'main' => $weather['main'] ?? 'Clear',
        'description' => $weather['description'] ?? 'Trời đẹp',
        'temp' => round($current['main']['temp'] ?? 0),
        'feelsLike' => round($current['main']['feels_like'] ?? 0),
        'humidity' => (int) ($current['main']['humidity'] ?? 0),
        'wind' => (int) ($current['wind']['speed'] ?? 0),
        'location' => $location['label'],
        'date' => (new DateTimeImmutable('now', weather_timezone()))->format('Y-m-d'),
        'forecast' => array_map(fn($item) => [
            'date' => substr((string) ($item['dt_txt'] ?? ''), 0, 10),
            'temp' => round($item['main']['temp'] ?? 0),
            'main' => $item['weather'][0]['main'] ?? 'Clear',
            'description' => $item['weather'][0]['description'] ?? 'Trời đẹp',
        ], $forecast['list'] ?? []),
    ];
}
function read_json_body(): array { return json_decode(file_get_contents('php://input'), true) ?: []; }
