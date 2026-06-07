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
function app_base_path(): string {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return ($base === '' || $base === '.') ? '' : $base;
}
function app_url(string $path = '/'): string {
    $base = app_base_path();
    $path = '/' . ltrim($path, '/');
    return $base . ($path === '/' ? '/' : $path);
}
function static_url(string $path): string {
    return app_url('/static/' . ltrim($path, '/'));
}
function json_out($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function asset_url(?string $value): string {
    if (!$value) return '';
    $path = str_replace('\\', '/', $value);
    if (preg_match('#^https?://#', $path)) return $path;
    return app_url('/' . ltrim($path, '/'));
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
        ['name' => 'Long', 'avatar' => static_url('images/long.jpg'), 'avatar_url' => static_url('images/long.jpg')],
        ['name' => 'Hoa', 'avatar' => static_url('images/hoa.jpg'), 'avatar_url' => static_url('images/hoa.jpg')],
        ['name' => 'Linh', 'avatar' => static_url('images/linh.jpg'), 'avatar_url' => static_url('images/linh.jpg')],
        ['name' => 'LAnh', 'avatar' => static_url('images/lanh.jpg'), 'avatar_url' => static_url('images/lanh.jpg')],
        ['name' => 'Lan', 'avatar' => static_url('images/lan.jpg'), 'avatar_url' => static_url('images/lan.jpg')],
        ['name' => 'Bắc', 'avatar' => static_url('images/bac.jpg'), 'avatar_url' => static_url('images/bac.jpg')],
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
function fallback_weather(): array {
    $list = [];
    for ($i=1; $i<=4; $i++) $list[] = ['dt_txt'=>date('Y-m-d 12:00:00', strtotime("+$i day")), 'main'=>['temp'=>28 + ($i % 3)], 'weather'=>[['main'=>'Clear', 'description'=>'Trời đẹp']]];
    return [['weather'=>[['main'=>'Clear','description'=>'Trời đẹp']], 'main'=>['temp'=>29,'feels_like'=>32,'humidity'=>78], 'wind'=>['speed'=>14], 'name'=>'Cô Tô'], ['list'=>$list]];
}
function get_weather(): array {
    global $config;
    if (!$config['weather_api_key']) return fallback_weather();
    $params = http_build_query(['lat'=>20.9747, 'lon'=>107.7665, 'appid'=>$config['weather_api_key'], 'units'=>'metric', 'lang'=>'vi']);
    $current = @json_decode(@file_get_contents("https://api.openweathermap.org/data/2.5/weather?$params"), true);
    $forecast = @json_decode(@file_get_contents("https://api.openweathermap.org/data/2.5/forecast?$params"), true);
    if (!$current || empty($current['weather']) || empty($forecast['list'])) return fallback_weather();
    $current['wind']['speed'] = (int) round(($current['wind']['speed'] ?? 0) * 3.6);
    return [$current, ['list'=>array_slice($forecast['list'], 0, 4)]];
}
function read_json_body(): array { return json_decode(file_get_contents('php://input'), true) ?: []; }
