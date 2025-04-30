<?php
require __DIR__ . '/vendor/autoload.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Update;

// Load environment
$telegram_token = getenv('TELEGRAM_TOKEN');
$premiumy_api = getenv('PREMIUMY_API');
$iprn_api = getenv('IPRN_API');

$telegram = new Telegram($telegram_token);
$telegram->useGetUpdatesWithoutDatabase();

// Webhook Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update = json_decode(file_get_contents('php://input'), true);
    processUpdate(new Update($update));
    exit;
}

// OTP Check Endpoint
if (isset($_GET['check_otp'])) {
    checkPendingOTPs();
    exit;
}

// Health Check
if ($_SERVER['REQUEST_URI'] === '/health') {
    echo json_encode(['status' => 'ok']);
    exit;
}

function processUpdate(Update $update) {
    $message = $update->getMessage();
    $text = $message->getText();
    $chat_id = $message->getChat()->getId();

    if ($text === '/start') {
        sendWelcome($chat_id);
    } elseif ($text === '/getnumber') {
        handleNumberRequest($chat_id);
    }
}

function sendWelcome($chat_id) {
    Request::sendMessage([
        'chat_id' => $chat_id,
        'text' => "🚀 *OTP Receiver Bot*\n\nUse /getnumber to request a phone number",
        'parse_mode' => 'Markdown'
    ]);
}

function handleNumberRequest($chat_id) {
    $users = loadUsers();
    $platform = (count($users) % 2 === 0) ? 'premiumy' : 'iprnsms';
    
    $number_data = getNumberFromAPI($platform);
    $users[$chat_id] = [
        'number' => $number_data['number'],
        'service' => 'Temu', // Default service
        'timestamp' => time()
    ];
    
    saveUsers($users);
    
    Request::sendMessage([
        'chat_id' => $chat_id,
        'text' => "✅ *Number Acquired!*\n`{$number_data['number']}`",
        'parse_mode' => 'Markdown'
    ]);
}

function checkPendingOTPs() {
    $users = loadUsers();
    foreach ($users as $chat_id => $data) {
        $otp_data = fetchOTP($data['number']);
        if ($otp_data) {
            sendOTPMessage($chat_id, $otp_data);
            unset($users[$chat_id]);
        }
    }
    saveUsers($users);
}

function sendOTPMessage($chat_id, $otp_data) {
    $message = "🔔 *OTP Received* 🔔\n\n"
        . "🕒 *Time:* `{$otp_data['time']}`\n"
        . "📱 *Number:* `{$otp_data['number']}`\n"
        . "📡 *Service:* `{$otp_data['service']}`\n"
        . "🔢 *OTP Code:* `{$otp_data['code']}`\n\n"
        . "💬 *Message:*\n`{$otp_data['message']}`\n\n"
        . "_Do not share with anyone_";

    Request::sendMessage([
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ]);
}

// Data Handling
function loadUsers() {
    return json_decode(file_get_contents('users.json'), true) ?: [];
}

function saveUsers($data) {
    file_put_contents('users.json', json_encode($data));
}

// API Integration
function getNumberFromAPI($platform) {
    $api_key = $platform === 'premiumy' ? $GLOBALS['premiumy_api'] : $GLOBALS['iprn_api'];
    // Implement actual API call
    return ['number' => '213' . rand(100000000, 999999999)];
}

function fetchOTP($number) {
    // Implement actual OTP fetching
    return [
        'time' => date('Y-m-d H:i:s'),
        'number' => $number,
        'service' => 'Temu',
        'code' => rand(100000, 999999),
        'message' => 'Your verification code is: ' . rand(100000, 999999)
    ];
}