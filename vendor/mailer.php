<?php


// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//echo getcwd();
//if (!class_exists('PHPMailer', false)) {
    //require_once(ABSPATH . '/promo/vendor/src/PHPMailer.php');
//};
require __DIR__.'/promo/vendor/src/PHPMailer.php';
require __DIR__.'/promo/vendor/src/PHPMailer.php';
require __DIR__.'/promo/vendor/src/SMTP.php';
//require 'src/Exception.php';
//require 'src/PHPMailer.php';
//require 'src/SMTP.php';

//Load Composer's autoloader
//require 'vendor/phpmailer/phpmailer/PHPMailerAutoload.php';

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    $mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = 'o.hainyzhnyk';                 // SMTP username
    $mail->Password = 'HAIgoogle3711';                           // SMTP password
    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;     //465                               // TCP port to connect to

    //Recipients
    $mail->setFrom('o.hainyzhnyk@gmail.com', 'Mailer');
    $mail->addAddress('helga-hai@ukr.net', 'Joe User');     // Add a recipient
    $mail->addAddress('o.hai-nyzhnyk@gazer.com');               // Name is optional
    $mail->addReplyTo('helga-hai@ukr.net', 'Information');
    $mail->addCC('helga-hai@ukr.net');
    $mail->addBCC('helga-hai@ukr.net');

    //Attachments
    //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Here is the subject';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}

?>