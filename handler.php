<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/*
Tested working with PHP5.4 and above (including PHP 7 )

 */
require_once './vendor/autoload.php';

use FormGuide\Handlx\FormHandler;


$pp = new FormHandler(); 

$validator = $pp->getValidator();
$validator->fields(['Name','Email','phone'])->areRequired()->maxLength(50);
//$validator->field('dashcam')->isRequired()->req();
$validator->field('Email')->isEmail();
//$validator->field('Message')->maxLength(6000);


$pp->requireReCaptcha();
$pp->getReCaptcha()->initSecretKey('6LfNKHQUAAAAAFfyCa53bQzVp_G8V3eaIUFD5tLl');

//Customizing PHPMailer
$mailer = $pp->getMailer();
$mailer->setFrom('info@gazer.com','GAZER', false);
//Customizing PHPMailer END
$mailer->CharSet = 'UTF-8';
//$mailer->$sss = $_POST['email'];
//$pp->AdressTo();
$mailer->IsHTML(true); 
$mailer->AddAddress($_POST['Email']);
$pp->sendEmailTo($_POST['Email']); // ← Your email here  (['someone@gmail.com', 'someone.else@gmail.com']);['o.hainyzhnyk@gmail.com','helga-hai@ukr.net']

/*$fh = FormHandler::create()->validate(function($validator)
    		{
    	 		$validator->fields(['Name','Email','phone'])
    	 				  ->areRequired()->maxLength(50);
    	       	$validator->field('Email')->isEmail();
    	       	
            })->useMailTemplate(__DIR__.'src/templ/email.php')
            ->sendEmailTo('o.hainyzhnyk@gmail.com');
            
    $fh->process($_POST);*/

echo $pp->process($_POST);

