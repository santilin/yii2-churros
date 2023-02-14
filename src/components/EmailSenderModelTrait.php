<?php

namespace santilin\churros\components;
use Yii;
use santilin\churros\helpers\AppHelper;

trait EmailSenderModelTrait
{
	/**
	 * Sends a email composed with a view and a model
	 * @param array|string $to the recipients
	 * @param string $subject
	 * @param string $view_name
	 * @param array $view_params
	 * @param array $email_params
	 */
	public function sendModelEmail($to, string $subject, string $view_name,
		array $view_params = [], array $email_params = []): bool
	{
		$sent = false;
		$sent_message = '';
		Yii::$app->mailer->on(\yii\mail\BaseMailer::EVENT_AFTER_SEND,
			function(\yii\mail\MailEvent $event) use ($sent) {
				$sent = $event->isSuccessful;
				if( !$event->isSuccessful  ) {
					Yii::$app->mailer->saveMessage($event->message);
				}
			}
		);
		if( !isset($view_params['model']) ) {
			$view_params['model'] = $this;
		}
		$from = $email_params['from']??AppHelper::yiiparam('adminEmail');
		$to = (array)$to;
		if( YII_ENV_DEV ) {
			$subject = "[dev:to:" . reset($to) . "]$subject";
		}
		try {
			$composed = Yii::$app->mailer
				->compose( [ 'html' => $view_name, 'text' => "text/$view_name" ], $view_params )
				->setFrom($from)
				->setTo( YII_ENV_DEV ? [AppHelper::yiiparam('develEmailTo')] : $to )
				->setSubject( $subject);
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
				$error_message = $sent_message . '<br/>' . $error_message . "<br/>" . $m;
			}
			$this->addError($view_name, $error_message);
			$this->addError($view_name, Yii::t('churros', 'Please, send an email to {0} to get support', AppHelper::yiiparam('adminEmail')));
			return false;
		}
		return true;
	}

} // trait

