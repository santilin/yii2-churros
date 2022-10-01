<?php

namespace santilin\churros\components;

use Yii;

trait EmailSenderModelTrait
{
	public function doSendEmail(string $view_name, ?string $from, $to, string $subject, $body, array $options = []): bool
	{
		if( YII_ENV_DEV ) {
			$from = $to = Yii::$app->params['testEmail']??'z@zzzzz.es';
		} else if( empty($from) ) {
			$from = Yii::$app->params['adminEmail'];
		}
		$to = array($to);
		if( is_array($body) ) {
			$body = '<p>'.implode("</p>\n<p>", $body)."</p>\n";
		}
		$sent = false;
		$sent_message = '';
		try {
			$composed = Yii::$app->mailer->compose()
				->setFrom($from)
				->setTo($to)
				->setSubject($subject)
				->setTextBody(strip_tags($body))
				->setHtmlBody($body);
			$sent = $composed->send();
		} catch ( \Swift_TransportException $e ) {
			$sent_message = $e->getMessage();
		} catch( \Swift_RfcComplianceException $e ) {
			$sent_message = $e->getMessage();
		}
		if( !$sent ) {
			$error_message = Yii::t('churros', 'Unable to send email to {email}',
				['{email}' => is_array($to)?array_pop($to) . '...':$to]);
			if( YII_ENV_DEV ) {
				$error_message = $sent_message . '<br/>' . $error_message;
			}
			$this->addError($view_name, $error_message);
			return false;
		}
		return true;
	}

} // trait

