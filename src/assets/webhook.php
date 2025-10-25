<?php
// webhook.php
// Версия: 2025-10-25
// Поместите файл на хостинг и в настройках Marquiz укажите URL на этот файл.

// --- Настройки: замените на свои ---
define('TELEGRAM_BOT_TOKEN', '8064237454:AAE73NA9AQwQhheecuI-zgJqUo52Se1Vqy8'); // ваш токен бота
define('TELEGRAM_CHAT_ID', '-1002901332576'); // id чата или пользователя (для групп/каналов обычно отрицательное / -100...)
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage');
// --- Конец настроек ---

// Установим заголовок ответа JSON (опционально)
header('Content-Type: application/json; charset=utf-8');

// Читаем необработанный входящий JSON
$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Empty body']);
    exit;
}

$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Извлекаем поля (с защитой от отсутствующих ключей)
$contacts = isset($data['contacts']) ? $data['contacts'] : [];
$name = isset($contacts['name']) ? trim($contacts['name']) : '';
$email = isset($contacts['email']) ? trim($contacts['email']) : '';
$phone = isset($contacts['phone']) ? trim($contacts['phone']) : '';

$quiz = isset($data['quiz']) ? $data['quiz'] : [];
$quizName = isset($quiz['name']) ? $quiz['name'] : '';
$created = isset($data['created']) ? $data['created'] : '';
$createdText = $created ? date('Y-m-d H:i:s', strtotime($created)) : '';

$answers = isset($data['answers']) ? $data['answers'] : [];
$rawAnswers = isset($data['raw']) ? $data['raw'] : [];
$utm = isset($data['extra']['utm']) ? $data['extra']['utm'] : [];
$ip = isset($data['extra']['ip']) ? $data['extra']['ip'] : (isset($data['ip']) ? $data['ip'] : '');

// Функция: красиво собрать вопросы-ответы
function format_answers($answers) {
    $out = [];
    // $answers приходит в виде массива уровней; безопасно обойти вложенно
    foreach ($answers as $block) {
        if (!is_array($block)) continue;
        foreach ($block as $qa) {
            if (!is_array($qa)) continue;
            $q = isset($qa['q']) ? $qa['q'] : '';
            $a = isset($qa['a']) ? $qa['a'] : '';
            if ($q === '' && $a === '') continue;
            $out[] = trim($q) . ': ' . trim($a);
        }
    }
    return $out;
}

$formattedAnswers = format_answers($answers);

// Так же можно добавить ответы из raw (id вопроса => id ответа) при необходимости
// $rawAnswers — оставляем для отладки

// Формируем текст сообщения (MarkdownV2)
$msg_lines = [];
$msg_lines[] = "*Новая заявка из Marquiz*";
if ($quizName) $msg_lines[] = "_Квиз:_ " . escape_md($quizName);
if ($createdText) $msg_lines[] = "_Дата:_ " . escape_md($createdText);
if ($name) $msg_lines[] = "_Имя:_ " . escape_md($name);
if ($phone) $msg_lines[] = "_Телефон:_ " . escape_md($phone);
if ($email) $msg_lines[] = "_Email:_ " . escape_md($email);
if ($ip) $msg_lines[] = "_IP:_ " . escape_md($ip);

if (!empty($formattedAnswers)) {
    $msg_lines[] = "";
    $msg_lines[] = "*Ответы:*";
    foreach ($formattedAnswers as $line) {
        // обрезаем длинные строки, если нужно
        $msg_lines[] = "• " . escape_md($line);
    }
}

// UTM
if (!empty($utm) && is_array($utm)) {
    $utm_parts = [];
    foreach ($utm as $k => $v) {
        $utm_parts[] = $k . '=' . $v;
    }
    $msg_lines[] = "";
    $msg_lines[] = "*UTM:* " . escape_md(implode(', ', $utm_parts));
}

// При желании можно добавить raw JSON (для отладки) — закомментировано
// $msg_lines[] = "\n`raw json:`\n" . escape_md_short(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

$message_text = implode("\n", $msg_lines);

// Отправляем сообщение в Telegram
$payload = [
    'chat_id' => TELEGRAM_CHAT_ID,
    'text' => $message_text,
    'parse_mode' => 'MarkdownV2',
    'disable_web_page_preview' => true,
];

// Выполняем POST к Telegram
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, TELEGRAM_API_URL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$curl_err = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Всегда отдаем 200 в ответ Marquiz (Marquiz требует положительный статус при приёме заявок — 2xx). :contentReference[oaicite:1]{index=1}
http_response_code(200);

// Можно логировать ошибки в файл (опционально)
if ($curl_err || $http_code >= 400) {
    // логирование (необязательно, но полезно)
    $log_line = date('Y-m-d H:i:s') . " Telegram send error: curl_err=" . $curl_err . " http_code=" . $http_code . " response=" . $response . " | payload=" . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents(__DIR__ . '/webhook_telegram_log.txt', $log_line, FILE_APPEND | LOCK_EX);
}

// Ответ для отладки (можно убрать)
echo json_encode(['ok' => true, 'telegram_http_code' => $http_code]);

/**
 * Escape string for Telegram MarkdownV2
 * (экранируем специальные символы согласно документации Telegram)
 */
function escape_md($text) {
    // Список символов: _ * [ ] ( ) ~ ` > # + - = | { } . !
    $escape_chars = ['\\', '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    $escaped = str_replace($escape_chars, array_map(function($c){ return '\\'.$c; }, $escape_chars), $text);
    // Ограничение длины — Telegram API поддерживает до 4096 символов в сообщении
    if (mb_strlen($escaped) > 3900) {
        $escaped = mb_substr($escaped, 0, 3900) . '...';
    }
    return $escaped;
}