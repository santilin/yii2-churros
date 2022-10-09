<?php

namespace santilin\churros\components;

use Yii;

trait EmailSenderModelTrait
{
	public function sendModelEmail(string $view_name, ?string $from, $to, string $subject,
		array $params = []): bool
	{
		Yii::$app->mailer->on(\yii\mail\BaseMailer::EVENT_AFTER_SEND,
			function(\yii\mail\MailEvent $event) {
				if( !$event->isSuccessful  ) {
					Yii::$app->mailer->saveMessage($event->message);
				}
			});
		$params['model'] = $this;
		if( $from == null ) {
			$from = Yii::$app->params['adminEmail'];
		}
		if( YII_ENV_DEV ) {
			$to = Yii::$app->params['develEmailTo'];
		}
		$to = array($to);
		$sent = false;
		$sent_message = '';
		try {
			$composed = Yii::$app->mailer
				->compose( [ 'html' => $view_name, 'text' => "text/$view_name" ], $params)
				->setFrom($from)
				->setTo($to)
				->setSubject($subject);
			$sent = $composed->send();
		} catch ( \Swift_TransportException $e ) {
			$sent_message = $e->getMessage();
		} catch( \Swift_RfcComplianceException $e ) {
			$sent_message = $e->getMessage();
		}
		if( !$sent ) {
			if( count($to) > 1 ) {
				$error_message = Yii::t('churros', 'Unable to send email to {email} and other {ndest} recipients', ['email' => array_pop($to), 'nemails' => count($to)]);
			} else {
				$error_message = Yii::t('churros', 'Unable to send email to {email}', ['email' => array_pop($to) ]);
			}
			if( YII_ENV_DEV ) {
				$error_message = $sent_message . '<br/>' . $error_message;
			}
			$this->addError($view_name, $error_message);
			return false;
		}
		return true;
	}

} // trait

