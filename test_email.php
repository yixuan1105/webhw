<?php
// test_email.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once 'vendor/autoload.php'; 

// 載入 .env 檔案
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); 
$dotenv->safeLoad(); 

$mail = new PHPMailer(true);

try {
    // SMTP 設定
    $mail->isSMTP();
    $mail->Host       = 'smtp.office365.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['M365_USER'];
    $mail->Password   = $_ENV['M365_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet = 'UTF-8';

    // 收件人
    $mail->setFrom($_ENV['M365_USER'], '系統測試發信');
    $mail->addAddress($_ENV['ADMIN_EMAIL']); // 使用 .env 中的測試收件人

    // 郵件內容
    $mail->isHTML(false);
    $mail->Subject = '【PHPMailer 測試信】連線成功';
    $mail->Body    = '這是一封使用 Office 365 成功發送的測試郵件。時間: ' . date('Y-m-d H:i:s');

    $mail->send();
    echo '✅ Email sent successfully to ' . $_ENV['ADMIN_EMAIL'] . '!';
} catch (Exception $e) {
    echo "❌ Failed to send email. Error: {$mail->ErrorInfo}";
    echo "<br>請檢查：1. .env 檔案中的 M365 帳密是否正確； 2. M365 帳號是否啟用了 SMTP 認證。";
}
?>