<?php
require_once FCPATH . '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body, $from = null)
{
    $config = [
        'host' => 'smtp.gmail.com',
        'username' => 'developer.rabaya.dg@gmail.com',
        'password' => 'ktqwphxhkwxferch', // app password
        'port' => 587, // SMTP port (587 for TLS, 465 for SSL)
        'encryption' => 'tls', // Encryption type (tls or ssl)
        'from_email' => $from ?? 'developer.rabaya.dg@gmail.com', 
        'from_name' => 'Pangasinan Ride',
    ];

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];

        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}
