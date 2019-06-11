<?php

function MailSmtp($reciever, $subject, $content, $headers, $debug = 0)
{
    $smtp_server = 'o.hainyzhnyk@gmail.com'; // Адрес SMTP-сервера
    $smtp_port = 25; // Порт SMTP-сервера
    $smtp_user = 'o.hainyzhnyk'; // Имя пользователя для авторизации на SMTP-сервере
    $smtp_password = 'HAIgoogle3711'; // Пароль для авторизации на SMTP-сервере
    $mail_from = 'o.hainyzhnyk@gmail.com'; // Ящик, с которого отправляется письмо
/*  //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 2;
    //Ask for HTML-friendly debug output
    $mail->Debugoutput = 'html';*/
    $sock = fsockopen($smtp_server,$smtp_port,$errno,$errstr,30);
 
    $str = fgets($sock,512);
    if (!$sock)
    {
        printf("Socket is not created\n");
        exit(1);
        
    }
 
    smtp_msg($sock, "HELO " . $_SERVER['SERVER_NAME']);
    smtp_msg($sock, "AUTH LOGIN");
    smtp_msg($sock, base64_encode($smtp_user));
    smtp_msg($sock, base64_encode($smtp_password));
    smtp_msg($sock, "MAIL FROM: <" . $mail_from . ">");
    smtp_msg($sock, "RCPT TO: <" . $reciever . ">");
    smtp_msg($sock, "DATA");
 
    $headers = "Subject: " . $subject . "\r\n" . $headers;
    $data = $headers . "\r\n\r\n" . $content . "\r\n.";
 
    smtp_msg($sock, $data);
    smtp_msg($sock, "QUIT");
 
    fclose($sock);
}
 
function smtp_msg($sock, $msg)
{
    if (!$sock)
    {
        printf("Broken socket!\n");
        exit(1);
    }
 
    if (isset($_SERVER['debug']) && $_SERVER['debug'])
    {
        printf("Send from us: %s<BR>", nl2br(htmlspecialchars($msg)));
    }
    fputs($sock, "$msg\r\n");
    $str = fgets($sock, 512);
    if (!$sock)
    {
        printf("Socket is down\n");
        exit(1);
    }
    else
    {
        if (isset($_SERVER['debug']) && $_SERVER['debug'])
        {
        printf("Got from server: %s<BR>", nl2br(htmlspecialchars($str)));
        }
 
        $e = explode(" ", $str);
        $code = array_shift($e);
        $str = implode(" ", $e);
 
        if ($code > 499)
        {
            printf("Problems with SMTP conversation.<BR><BR>Code %d.<BR>Message %s<BR>", $code, $str);
            exit(1);
        }   
    }
}
 
//Заголовки
$head = "From: helga-hai@ukr.net - Форум Production <helga-hai@ukr.net>\r\n"; 
$head .= "Reply-To: helga-hai@ukr.net\r\n";
 
//Текст письма
$message = "текст";
 
//Отправляем
MailSmtp($mailto, "Тема письма", $message, $head, 0);
?>