<?php
namespace FormGuide\Handlx;
use FormGuide\PHPFormValidator\FormValidator;
use PHPMailer;
use FormGuide\Handlx\Microtemplate;
use Gregwar\Captcha\CaptchaBuilder;

/**
 * FormHandler 
 *  A wrapper class that handles common form handling tasks
 *  	- handles Form validations using PHPFormValidator class
 *  	- sends email using PHPMailer 
 *  	- can handle captcha validation
 *  	- can handle file uploads and attaching the upload to email
 *  	
 *  ==== Sample usage ====*/
   $fh = FormHandler::create()->validate(function($validator)
    		{
    	 		$validator->fields(['name','email','phone'])
    	 				  ->areRequired()->maxLength(50);
    	       	$validator->field('email')->isEmail();
    	       	
            })->useMailTemplate(__DIR__.'/templ/email.php')
            ->sendEmailTo('o.hainyzhnyk@gmail.com');
            
    $fh->process($_POST);
 
class FormHandler
{
	private $emails;
	public $validator;
	private $mailer;
	private $mail_template;
	private $captcha;
	private $attachments;
	private $recaptcha;

	public function __construct()
	{
		$this->emails = array();
		$this->validator = FormValidator::create();
		$this->mailer = new PHPMailer;
		$this->mail_template='';

		$this->mailer->Subject = "GAZER PROMO";

		$host = isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'localhost';
        $from_email ='forms@'.$host;
   		$this->mailer->setFrom($from_email,'Contact Form',false);  

   		$this->captcha = false;   

   		$this->attachments = [];

   		$this->recaptcha =null;


	}

	/**
	 * sendEmailTo: add a recipient email address
	 * @param  string/array $email_s one or more emails. If more than one emails, pass the emails as array
	 * @return The form handler object itself so that the methods can be chained
	 */
	/*public function getInputEmailTo($post)
	{
		$cusrmail = "\n";

		foreach($post as $name=>$value)
		{

			$cusrmail .= ucwords($name).":";
			$cusrmail .= "$value";
			
		}
		//preg_match_all("/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/is", $cusrmail, $email_s);

		return $this;
	}*/

	public function sendEmailTo($email_s)
	{
		/*getInputEmailTo();
		$cusrmail = "\n";

		foreach($post as $name=>$value)
		{

			$cusrmail .= ucwords($name).":";
			$cusrmail .= "$value";
			
		}
		preg_match_all("/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/is", $cusrmail, $email_s);
		//$mail->AddAddress($email);*/
		if(is_array($email_s))
		{
			$this->emails =array_merge($this->emails, $email_s);
		}
		else
		{
			$this->emails[] = $email_s;	
		}
		//$this->mailer->AddAddress($email_s);
		return $this;
	}

	public function useMailTemplate($templ_path)
	{
		$this->mail_template = $templ_path;
		return $this;
	}

	/**
	 * [attachFiles find the file uplods and attach to the email]
	 * @param  array $fields The array of field names
	  */
	public function attachFiles($fields)
	{
		$this->attachments = array_merge($this->attachments, $fields);
		return $this;
	}

	public function getRecipients()
	{
		return $this->emails;
	}

	/**
	 * [validate add Validations. This function takes a call back function which receives the PHPFormValidator object]
	 * @param  function $validator_fn The funtion gets a validator parameter using which, you can add validations 
	 */
	public function validate($validator_fn)
	{
		$validator_fn($this->validator);
		return $this;
	}

	public function requireReCaptcha($config_fn=null)
	{
		$this->recaptcha = new ReCaptchaValidator();
		$this->recaptcha->enable(true);
		if($config_fn)
		{
			$config_fn($this->recaptcha);	
		}
		return $this;
	}
	public function getReCaptcha()
	{
		return $this->recaptcha;
	}

	public function requireCaptcha($enable=true)
	{
		$this->captcha = $enable;
		return $this;
	}

	public function getValidator()
	{
		return $this->validator;
	}

	public function configMailer($mailconfig_fn)
	{
		$mailconfig_fn($this->mailer);
		return $this;
	}

	public function getMailer()
	{
		return $this->mailer;
	}

	public static function create()
	{
		return new FormHandler();
	}

	public function process($post_data)
	{
		if($this->captcha === true)
		{
			$res = $this->validate_captcha($post_data);
			if($res !== true)
			{
				return $res;
			}
		}
		if($this->recaptcha !== null &&
		   $this->recaptcha->isEnabled())
		{
			if($this->recaptcha->validate() !== true)
			{
				return json_encode([
				'result'=>'recaptcha_validation_failed',
				'errors'=>['captcha'=>'ReCaptcha Validation Failed.']
				]);
			}
		}

		$this->validator->test($post_data);

		//if(false == $this->validator->test($post_data))
		if($this->validator->hasErrors())
		{
			return json_encode([
				'result'=>'validation_failed',
				'errors'=>$this->validator->getErrors(/*associative*/ true)
				]);
		}

		if(!empty($this->emails))
		{
			foreach($this->emails as $email)
			{
				$this->mailer->addAddress($email);
			}

			$this->compose_mail($post_data);

			if(!empty($this->attachments))
			{
				$this->attach_files();
			}

			if(!$this->mailer->send())
			{
				return json_encode([
					'result'=>'error_sending_email',
					'errors'=> ['mail'=> $this->mailer->ErrorInfo]
					]);			
			}
		}
		
		return json_encode(['result'=>'success']);
	}

	private function validate_captcha($post)
	{
		@session_start();
		if(empty($post['captcha']))
		{
			return json_encode([
						'result'=>'captcha_error',
						'errors'=>['captcha'=>'Captcha code not entered']
						]);
		}
		else
		{
			$usercaptcha = trim($post['captcha']);

			if($_SESSION['user_phrase'] !== $usercaptcha)
			{
				return json_encode([
						'result'=>'captcha_error',
						'errors'=>['captcha'=>'Captcha code does not match']
						]);		
			}
		}
		return true;
	}


	private function attach_files()
	{
		
		foreach($this->attachments as $file_field)
		{
			if (!array_key_exists($file_field, $_FILES))
			{
				continue;
			}
			$filename = $_FILES[$file_field]['name'];

    		$uploadfile = tempnam(sys_get_temp_dir(), sha1($filename));

    		if (!move_uploaded_file($_FILES[$file_field]['tmp_name'], 
    			$uploadfile))
    		{
    			continue;
    		}

    		$this->mailer->addAttachment($uploadfile, $filename);
		}
	}

	private function compose_mail($post)
	{
		$content = "\n";
		$co = "\n";
		foreach($post as $name=>$value)
		{

			$content .= ucwords($name).": ";
			$content .= "$value\n\n";

			if (strpos($content, 'F225')){
				$co .= "Щоб придбати реєстратор Gazer F225 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G225-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'F715')){
				$co .= "Щоб придбати реєстратор Gazer F715 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G715-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'F122')){
				$co .= "Щоб придбати реєстратор Gazer F122 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G122-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'F150')){
				$co .= "Щоб придбати реєстратор Gazer F150 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G150-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'F735g')){
				$co .= "Щоб придбати реєстратор Gazer F735g зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G735-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'F714')){
				$co .= "Щоб придбати реєстратор Gazer F714 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G714-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'F121')){
				$co .= "Щоб придбати реєстратор Gazer F121 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G121-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'F525')){
				$co .= "Щоб придбати реєстратор Gazer F525 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G525-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'F720')){
				$co .= "Щоб придбати реєстратор Gazer F720 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G720-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'F230w')){
				$co .= "Щоб придбати реєстратор Gazer F230w зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G230-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'F117')){
				$co .= "Щоб придбати реєстратор Gazer F117 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G117-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'H521')){
				$co .= "Щоб придбати реєстратор Gazer H521 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G521-A215-Z575-E73R\n\n";
			}
			if (strpos($content, 'ELDORADO')){
				$co .= "Щоб придбати продукцію Gazer зі знижкою 20% в мережі магазинів ELDORADO, надайте промокод: НЕВІДОМО ЯКИЙ\n\n";
			}
			if (strpos($content, 'BAZA')){
				$co .= "Щоб придбати продукцію Gazer зі знижкою 20% в мережі магазинів База Автозвуку, надайте промокод: XXXX-XXXX-XXXX-XXXX\n\n";
			}
			
		}

		/*$co = "Отримайте знажку 20% в мережі магазинів ";
		$co .= str_replace('BAZA', 'База Автозвуку', $content);

		$pos = strpos($content, $findme);
		strpos($content, $findme)
		switch (strpos($content, $findme)) {
				case "F225":
			        $sw == "Щоб придбати реєстратор Gazer F225 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G225-A215-Z575-E73R\n\n";
			        break;
			    }*/
			    /*$mailyou=
			    sendEmailTo($email_s)*/
/*			if (strpos($name, 'email')){
				$mailyou .= "$value";
			};*/
		//$tocontent = "111\n";
		//preg_match_all("/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/is", $content, $mailyou);
		//$co .= "$mailyou\n\n\n\n\n\n";

		//$content=$co;
		/*if (strpos($content, 'ELDORADO')){
			for(i=0,i<10,i++){
				$PromoEldorado='';
			};
			$co .= "Щоб придбати продукцію Gazer зі знижкою 20% в мережі магазинів ELDORADO, надайте промокод: ". $PromoEldorado ."\n\n";
		}
		if (strpos($content, 'BAZA')){
			$co .= "Щоб придбати продукцію Gazer зі знижкою 20% в мережі магазинів База Автозвуку, надайте промокод: XXXX-XXXX-XXXX-XXXX\n\n";
		}*/
		/*$this->mailer->*/

		$this->mailer->Body= $co;
	}


}