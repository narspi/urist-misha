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

if (empty($name)) {

}


if (empty($tel)) {
    
}

// Сообщение для отправки в группу
$message = "Новая заявка с сайта:\nИмя: $name\nНомер телефона: $phone";

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
    ]);
} else {
    echo json_encode([
        'status' => 'failed',
        'response' => $res,
    ]);
}