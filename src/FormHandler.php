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
   /*$fh = FormHandler::create()->validate(function($validator)
    		{
    	 		$validator->fields(['name','email','phone'])
    	 				  ->areRequired()->maxLength(50);
    	       	$validator->field('email')->isEmail();
    	       	
            })->useMailTemplate(__DIR__.'/templ/email.php')
            ->sendEmailTo('o.hainyzhnyk@gmail.com');
            
    $fh->process($_POST);*/
 
class MyClass {
    private $a;
    public function func() {
    	$file_all = file_get_contents('code1.txt');
    	$line = substr($file_all, -12);//set last 39 symvols as our promocode

		// load the data and delete the line from the array 
		$lines = file('code1.txt'); 
		$last = sizeof($lines) - 1; 
		unset($lines[$last]); 

		// write the new data to the file 
		$fp = fopen('code1.txt', 'w'); 
		fwrite($fp, implode('', $lines)); 
		fclose($fp); 

        $this->a = $line; 

        return $this->a;
    }
}
 
//$my = new MyClass();
//$my->func(); // Результат: 5


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

	public function sendEmailTo($email_s)
	{

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

		if(false == $this->validator->test($post_data))
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

	/*private function eldorado_200_codes()
	{
		$onecod="22";
		//$promocod="2222";
		return $onecod;

	}*/

	private function compose_mail($post)
	{
		$content = "<span style='display:none;'>";//<span style='display:none;'>
		
		foreach($post as $name=>$value)
		{
			$content .= $name.": $value\n\n";/*ucwords($name)*/

			//if ($name!=='g-recaptcha-response') {
				/*if (strpos($content,'F225')){
				//if ((strpos($content, 'F225')!== false) and ($co === "\n")){
					$co .= "Щоб придбати реєстратор Gazer F225 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G225-A215-Z575-E73R\n\n";
					//$this->mailer->Body= $co;
				}
				elseif (strpos($content, 'F715')){
					$co .= "Щоб придбати реєстратор Gazer F715 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G715-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'F122')){
					$co .= "Щоб придбати реєстратор Gazer F122 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G122-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'F150')){
					$co .= "Щоб придбати реєстратор Gazer F150 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G150-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'F735g')){
					$co .= "Щоб придбати реєстратор Gazer F735g зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G735-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'F714')){
					$co .= "Щоб придбати реєстратор Gazer F714 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G714-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'F121')){
					$co .= "Щоб придбати реєстратор Gazer F121 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G121-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'F525')){
					$co .= "Щоб придбати реєстратор Gazer F525 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G525-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'F720')){
					$co .= "Щоб придбати реєстратор Gazer F720 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G720-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'F230w')){
					$co .= "Щоб придбати реєстратор Gazer F230w зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G230-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'F117')){
					$co .= "Щоб придбати реєстратор Gazer F117 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G117-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'H521')){
					$co .= "Щоб придбати реєстратор Gazer H521 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G521-A215-Z575-E73R\n\n";
				}
				elseif (strpos($content, 'ELDORADO')){
					$co .= "Щоб придбати продукцію Gazer зі знижкою 20% в мережі магазинів ELDORADO, надайте промокод: НЕВІДОМО ЯКИЙ\n\n";
				}
				elseif (strpos($content, 'BAZA')){
					$co .= "Щоб придбати продукцію Gazer зі знижкою 20% в мережі магазинів База Автозвуку, надайте промокод: XXXX-XXXX-XXXX-XXXX\n\n";
				}
				elseif (strpos($content, 'UA130')){
					$co .= "Щоб придбати продукцію Gazer зі знижкою 20% в мережі магазинів 130.ua, надайте промокод: XXXX-XXX\n\n";
				}*/

			//}
		}

		//$this->mailer->IsHTML(true); 

		if(strpos($content,'F230w')){
			$content .= "</span>Щоб придбати реєстратор Gazer F230w зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G230-A970-Z140-E44R.\n\n Його можна застосувати під час оформлення замовлення на allo.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'F225')){
			$content .= "</span>Щоб придбати реєстратор Gazer F225 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G225-A925-Z586-E84R.\n\n Його можна застосувати під час оформлення замовлення на allo.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'F150')){
			$content .= "</span>Щоб придбати реєстратор Gazer F150 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G150-A253-Z111-E99R.\n\n Його можна застосувати під час оформлення замовлення на allo.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'F735g')){
			$content .= "</span>Щоб придбати реєстратор Gazer F735g зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G735-A355-Z610-E00R.\n\n Його можна застосувати під час оформлення замовлення на allo.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'F720')){
			$content .= "</span>Щоб придбати реєстратор Gazer F720 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G720-A015-Z330-E20R.\n\n Його можна застосувати під час оформлення замовлення на allo.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'F122')){
			$content .= "</span>Щоб придбати реєстратор Gazer F122 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G122-A008-Z188-E66R.\n\n Його можна застосувати під час оформлення замовлення на allo.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'F117')){
			$content .= "</span>Щоб придбати реєстратор Gazer F117 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G117-A215-Z575-E73R.\n\n Його можна застосувати під час оформлення замовлення на allo.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'F715')){
			$content .= "</span>Щоб придбати реєстратор Gazer F715 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G715-A272-Z173-E71R.\n\n Його можна застосувати під час оформлення замовлення на allo.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'F525')){
			$content .= "</span>Щоб придбати реєстратор Gazer F525 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G525-A611-Z331-E29R.\n\n Його можна застосувати під час оформлення замовлення на allo.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'H521')){
			$content .= "</span>Щоб придбати реєстратор Gazer H521 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G521-A712-Z352-E80R.\n\n Його можна застосувати під час оформлення замовлення на allo.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		/*elseif (strpos($content, 'F714')){
			$content .= "</span>Щоб придбати реєстратор Gazer F714 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G714-A215-Z575-E73R\n\n";
		}
		elseif (strpos($content, 'F121')){
			$content .= "</span>Щоб придбати реєстратор Gazer F121 зі знижкою 20% в мережі магазинів АЛЛО, надайте промокод: G121-A215-Z575-E73R\n\n";
		}*/
		elseif (strpos($content, 'BAZA')){
			$content .= "</span>Щоб придбати продукцію Gazer зі знижкою 20% в мережі магазинів База Автозвуку, надайте промокод: GazerAvtozvuk.\n\n Його можна застосувати під час оформлення замовлення на avtozvuk.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'UA130')){
			$content .= "</span>Щоб придбати продукцію Gazer зі знижкою 20% в мережі магазинів 130.com.ua, надайте промокод: Gazer130.\n\n Його можна застосувати під час оформлення замовлення на 130.ua або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'FOXTROT')){
			$content .= "</span>Щоб придбати продукцію Gazer зі знижкою 20% в мережі магазинів FOXTROT, надайте промокод: GazerFoxtrot18<br> Його можна застосувати під час оформлення замовлення на www.foxtrot.com.ua у кошику або показати промокод представнику в магазині 28 жовтня.";
		}
		elseif (strpos($content, 'ELDORADO')){
			//$content .= eldorado_200_codes();
			//$content = ;
			$my = new MyClass();
			//$my->func();
			//$my->mailer->Body ="qweqwe";
			$content .="</span> Ваш промокод на відеореєстратори Gazer в магазині ELDORADO – ".$my->func();
			//echo $my->process($_POST);
			//$mailer = $pp->getMailer();
			//$content .= "</span>ELDORADO</a>";
		}
		else {
			$content .= "</span>Ви не обрали в якій мережі будете купувати продукцію. <br>Поверніться у форму та зробіть свій вибір: <a href='https://www.gazer.com/promo/formpage.html'>www.gazer.com/promo/formpage.html</a><br> Або скористайся наступними промокодами:<br>База Автозвуку (знижка 20% на будь-який товар Gazer) промокод: <b>GazerAvtozvuk</b><br>130.com.ua (знижка 20% на будь-який товар Gazer)  промокод: <b>Gazer130</b><br>FOXTROT (знижка 20% на реєстратори Gazer)  промокод: <b>GazerFoxtrot18</b>";
		}

		$this->mailer->Body = $content;//(strpos($content, '</span>'))?$content:($my->func());
		//$this->mailer->AltBodyy = $co;
	}


}