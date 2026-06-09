<?php
require __DIR__ . '/functions.php';

$path = request_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($path === '/' && $method === 'GET') {
        [$current, $forecast] = get_weather();
        $weather_main = $current['weather'][0]['main'] ?? 'Clear';
        $weather_desc = $current['weather'][0]['description'] ?? 'Trời đẹp';
        $gallery_items = get_gallery_items();
        $itineraries = get_itineraries();
        render('index', [
            'members' => default_members(),
            'current' => $current,
            'forecast' => $forecast,
            'weather_main' => $weather_main,
            'weather_desc' => $weather_desc,
            'gallery_count' => count($gallery_items),
            'upcoming_itineraries' => array_slice($itineraries, 0, 2),
        ]);
        exit;
    }

    if ($path === '/expenses' && $method === 'GET') {
        $members = get_members();
        $expenses = get_expenses();
        $collections = get_collections();
        $stats = calculate_expense_stats($expenses, $members);
        render('expenses', [
            'members' => $members,
            'expenses' => $expenses,
            'stats' => $stats,
            'total' => $stats['total'],
            'collections' => $collections,
            'total_collected' => array_sum(array_column($collections, 'amount')),
        ]);
        exit;
    }

    if ($path === '/add_expense' && $method === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? $title);
        $note = trim($_POST['note'] ?? '');
        $payer = find_member($_POST['payer_id'] ?? ($_POST['payer'] ?? ''));
        if ($title === '') json_out(['status'=>'error', 'message'=>'Vui lòng nhập tên khoản chi.'], 400);
        if (!$payer) json_out(['status'=>'error', 'message'=>'Người chi không tồn tại.'], 400);
        $amount = parse_amount($_POST['amount'] ?? '');
        $id = execute_sql('INSERT INTO expenses(title,category,note,payer_id,amount,split_evenly) VALUES(?,?,?,?,?,?)', [$title, $category, $note, $payer['id'], $amount, normalize_bool($_POST['split_evenly'] ?? null) ? 1 : 0]);
        json_out(['status'=>'success', 'expense'=>get_expense($id)]);
    }

    if ($path === '/add_collection' && $method === 'POST') {
        $member_id = (int) ($_POST['member_id'] ?? 0);
        $amount = parse_amount($_POST['amount'] ?? '');
        execute_sql('INSERT INTO collections(member_id, amount) VALUES(?,?)', [$member_id, $amount]);
        json_out(['status'=>'success']);
    }

    if (preg_match('#^/delete_expense/(\d+)$#', $path, $m) && in_array($method, ['DELETE', 'POST'], true)) {
        execute_sql('DELETE FROM expenses WHERE id=?', [(int) $m[1]]);
        json_out(['status'=>'success']);
    }

    if (preg_match('#^/delete_collection/(\d+)$#', $path, $m) && in_array($method, ['DELETE', 'POST'], true)) {
        execute_sql('DELETE FROM collections WHERE id=?', [(int) $m[1]]);
        json_out(['status'=>'success']);
    }

    if ($path === '/gallery' && $method === 'GET') {
        render('gallery', ['gallery_items' => get_gallery_items(), 'members' => get_members()]);
        exit;
    }

    if ($path === '/upload_gallery' && $method === 'POST') {
        $uploader = find_member($_POST['uploaded_by'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $allowed = ['jpg','jpeg','png','webp','gif','heic','heif'];
        $uploadDir = __DIR__ . '/static/uploads/gallery';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
        $saved = [];
        $files = $_FILES['images'] ?? ($_FILES['image'] ?? ($_FILES['file'] ?? null));
        if ($files) {
            $names = is_array($files['name']) ? $files['name'] : [$files['name']];
            $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
            $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
            foreach ($names as $i => $name) {
                if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION)) ?: 'jpg';
                if (!in_array($ext, $allowed, true)) continue;
                $base = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($name, PATHINFO_FILENAME)) ?: 'anh';
                $fileName = $base . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (!move_uploaded_file($tmpNames[$i], $uploadDir . '/' . $fileName)) continue;
                $relative = 'static/uploads/gallery/' . $fileName;
                $id = execute_sql('INSERT INTO gallery(image_url,caption,uploaded_by) VALUES(?,?,?)', [$relative, $caption ?: pathinfo($name, PATHINFO_FILENAME), $uploader['id'] ?? null]);
                $saved[] = get_gallery_item($id);
            }
        }
        if (!$saved) json_out(['status'=>'error', 'message'=>'Không có ảnh hợp lệ để tải lên.'], 400);
        json_out(['status'=>'success', 'items'=>$saved]);
    }

    if ($path === '/map' && $method === 'GET') {
        render('map', ['itineraries' => get_itineraries(), 'members' => default_members()]);
        exit;
    }

    if ($path === '/add_itinerary' && $method === 'POST') {
        $payload = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0 ? read_json_body() : $_POST;
        $title = trim($payload['title'] ?? '');
        $date = trim($payload['trip_date'] ?? ($payload['date'] ?? ''));
        $time = trim($payload['trip_time'] ?? ($payload['time'] ?? ''));
        $type = trim($payload['activity_type'] ?? ($payload['type'] ?? 'Hoạt động'));
        $detail = trim($payload['detail'] ?? '');
        if (!$title || !$date || !$time) json_out(['status'=>'error','message'=>'Vui lòng nhập đầy đủ lịch trình.'], 400);
        $id = execute_sql('INSERT INTO itineraries(title,trip_date,trip_time,activity_type,detail,created_by) VALUES(?,?,?,?,?,?)', [$title, $date, $time, $type, $detail, null]);
        json_out(['status'=>'success', 'itinerary'=>get_itinerary($id)]);
    }

    if (preg_match('#^/update_itinerary/(\d+)$#', $path, $m) && in_array($method, ['POST', 'PUT'], true)) {
        $payload = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === 0 ? read_json_body() : $_POST;
        execute_sql('UPDATE itineraries SET title=?, trip_date=?, trip_time=?, activity_type=?, detail=?, created_by=? WHERE id=?', [trim($payload['title'] ?? ''), trim($payload['trip_date'] ?? ($payload['date'] ?? '')), trim($payload['trip_time'] ?? ($payload['time'] ?? '')), trim($payload['activity_type'] ?? ($payload['type'] ?? 'Hoạt động')), trim($payload['detail'] ?? ''), null, (int)$m[1]]);
        json_out(['status'=>'success', 'itinerary'=>get_itinerary((int)$m[1])]);
    }

    if (preg_match('#^/delete_itinerary/(\d+)$#', $path, $m) && in_array($method, ['DELETE', 'POST'], true)) {
        execute_sql('DELETE FROM itineraries WHERE id=?', [(int) $m[1]]);
        json_out(['status'=>'success']);
    }

    if ($path === '/ai' && $method === 'GET') { render('ai', ['ai_messages' => get_ai_messages()]); exit; }

    if ($path === '/chatbot' && $method === 'POST') {
        global $config;
        $payload = read_json_body();
        $message = trim($payload['message'] ?? '');
        if ($message === '') json_out(['reply'=>'Bạn hãy nhập câu hỏi trước nhé.'], 400);
        save_ai_message('user', $message);
        if (!$config['groq_api_key']) {
            $reply = 'AI chưa được cấu hình GROQ_API_KEY.';
            save_ai_message('assistant', $reply);
            json_out(['reply'=>$reply], 503);
        }
        $body = json_encode(['model'=>$config['groq_model'], 'messages'=>[['role'=>'system','content'=>'Bạn là trợ lý du lịch Cô Tô cao cấp. Luôn trả lời bằng tiếng Việt thuần, ngắn gọn, thực tế.'], ['role'=>'user','content'=>$message]]], JSON_UNESCAPED_UNICODE);
        $context = stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nAuthorization: Bearer {$config['groq_api_key']}\r\n",'content'=>$body,'timeout'=>15]]);
        $response = @file_get_contents('https://api.groq.com/openai/v1/chat/completions', false, $context);
        $json = $response ? json_decode($response, true) : null;
        $reply = $json['choices'][0]['message']['content'] ?? 'AI đang bận, bạn thử lại sau nhé.';
        save_ai_message('assistant', $reply);
        json_out(['reply'=>$reply], $json ? 200 : 503);
    }

    if ($path === '/api/members') json_out(get_members());
    if ($path === '/api/expenses') { $expenses = get_expenses(); json_out(['expenses'=>$expenses, 'stats'=>calculate_expense_stats($expenses, get_members())]); }
    if ($path === '/api/gallery') json_out(get_gallery_items());
    if ($path === '/api/itineraries') json_out(get_itineraries());

    http_response_code(404);
    echo 'Không tìm thấy trang.';
} catch (Throwable $error) {
    if (strpos($path, '/api/') === 0 || $method !== 'GET') json_out(['status'=>'error', 'message'=>$error->getMessage()], 500);
    http_response_code(500);
    echo 'Lỗi ứng dụng: ' . e($error->getMessage());
}
