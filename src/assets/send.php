<?php
// токен доступа к боту
$telegram_api_key = "8064237454:AAE73NA9AQwQhheecuI-zgJqUo52Se1Vqy8";
// id чата
$chat_id = '-1002901332576';

// Запрещаем все методы, кроме POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    header('Allow: POST');
    die('Разрешены только POST-запросы');
}

// Получаем данные из POST-запроса
$name = htmlspecialchars($_POST['name'] ?? '');
$phone = htmlspecialchars($_POST['tel'] ?? '');
$form_name = htmlspecialchars($_POST['form-name'] ?? '');

// Проверка имени формы
if (empty($form_name)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Пошли нахер отсюда']);
    exit;
}

// Валидация имени
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Введите имя']);
    exit;
}

if (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
    echo json_encode(['status' => 'error', 'message' => 'Имя должно содержать от 2 до 50 символов']);
    exit;
}

// Валидация телефона
if (empty($phone)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Введите номер телефона']);
    exit;
}

// Удаляем все символы, кроме цифр и знака "+"
$cleanPhone = preg_replace('/[^\d\+]/', '', $phone);

// Если номер начинается с "00", можно заменить на "+"
if (strpos($cleanPhone, '00') === 0) {
    $cleanPhone = '+' . substr($cleanPhone, 2);
}

// Проверяем формат
if (!preg_match('/^\+?\d{7,15}$/', $cleanPhone)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Неверный формат телефона']);
    exit;
}

// Сообщение для отправки в группу
$message = "Новая заявка с сайта\nФорма: {$form_name}\nИмя: {$name}\nНомер телефона: {$phone}";

$ch = curl_init();
curl_setopt_array(
    $ch,
    array(
        CURLOPT_URL => 'https://api.telegram.org/bot' . $telegram_api_key . '/sendMessage',
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => array(
            'chat_id' => $chat_id,
            'text' => $message,
        ),
    )
);

$res = curl_exec($ch);
curl_close($ch);

$decode_res = json_decode($res,true);


// Проверяем результат отправки
if ($decode_res['ok'] === true) {
    echo json_encode([
        'status' => 'success',
        'response' => $res,
        'message' => 'Заявка успешно отправлена!',
    ]);
} else {
    echo json_encode([
        'status' => 'failed',
        'message' => 'Ошибка при отправке сообщения. Попробуйте позже!',
        'response' => $res,
    ]);
}