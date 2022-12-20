<?php

namespace santilin\churros\components;
use Yii;
use santilin\churros\helpers\AppHelper;

trait EmailSenderModelTrait
{
	public function sendModelEmail(string $view_name, ?string $from, $to, string $subject,
		array $params = []): bool
	{
		$sent = false;
		$sent_message = '';
		Yii::$app->mailer->on(\yii\mail\BaseMailer::EVENT_AFTER_SEND,
			function(\yii\mail\MailEvent $event) use (&$sent) {
				$sent = $event->isSuccessful;
				if( !$event->isSuccessful  ) {
					Yii::$app->mailer->saveMessage($event->message);
				}
			}
		);
		$params['model'] = $this;
		if( $from == null ) {
			$from = AppHelper::yiiparam('adminEmail');
		}
		$to = array($to);
		try {
			$composed = Yii::$app->mailer
				->compose( [ 'html' => $view_name, 'text' => "text/$view_name" ], $params)
				->setFrom($from)
				->setTo( YII_ENV_DEV ? [AppHelper::yiiparam('develEmailTo')] : $to )
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
			$this->addError($view_name, Yii::t('churros', 'Please, send an email to {0} to get support', AppHelper::yiiparam('adminEmail')));
			return false;
		}
		return true;
	}

	public function composeAndSendEmail(string $view_name, string $subject, array $email_params): bool
	{
		return $this->sendModelEmail($view_name, null, $email_params['to']??$this->email, $subject, $email_params);
	}

} // trait

