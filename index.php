<?php

// Define your bot token and 2Captcha API key from environment variables
define('BOT_TOKEN', getenv('BOT_TOKEN'));
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('CAPTCHA_API_KEY', 'YOUR_2CAPTCHA_API_KEY'); // Hardcode your 2Captcha API key here

function apiRequest($method, $parameters) {
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }
    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }

    $parameters["method"] = $method;
    header('Content-Type: application/json');
    $options = array(
        'http' => array(
            'method'  => 'POST',
            'content' => json_encode($parameters),
            'header'  => "Content-Type: application/json\r\n"
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents(API_URL, false, $context);

    return $result;
}

function processMessage($message) {
    if (isset($message['text'])) {
        $chat_id = $message['chat']['id'];
        $text = $message['text'];

        if ($text === '/check_key') {
            $response = checkCaptchaKey();
            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $response));
        } else {
            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Please use /check_key to check the 2Captcha API key.'));
        }
    }
}

function checkCaptchaKey() {
    $url = "http://2captcha.com/res.php?key=" . CAPTCHA_API_KEY . "&action=getbalance";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if (is_numeric($response)) {
        return "Your 2Captcha balance is: $response USD";
    } else {
        return "Failed to check 2Captcha key. Error: $response";
    }
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

if (isset($update["message"])) {
    processMessage($update["message"]);
}
