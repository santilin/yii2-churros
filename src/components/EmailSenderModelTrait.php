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
		$mailer_error = '';
		Yii::$app->mailer->on(\yii\mail\BaseMailer::EVENT_AFTER_SEND,
			function(\yii\mail\MailEvent $event) use ($mailer_error, $sent) {
				$sent = $event->isSuccessful;
				if( !$event->isSuccessful  ) {
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
		$composed = Yii::$app->mailer
			->compose( [ 'html' => $view_name, 'text' => "text/$view_name" ], $view_params )
			->setFrom($from)
			->setTo( YII_ENV_DEV ? [AppHelper::yiiparam('develEmail')] : $to )
			->setSubject( $subject);
		try {
			$sent = $composed->send();
		} catch ( \Swift_TransportException $e ) {
			$mailer_error = $e->getMessage();
		} catch( \Swift_RfcComplianceException $e ) {
			$mailer_error = $e->getMessage();
		}
		if( !$sent ) {
			if( count($to) > 1 ) {
				$error_message = Yii::t('churros', 'Unable to send email to {email} and other {ndest} recipients from {from}', ['email' => array_pop($to), 'ndest' => count($to), 'from' => $from]);
			} else {
				$error_message = Yii::t('churros', 'Unable to send email to {email} from {from}', ['email' => array_pop($to), 'from' => $from ]);
			}
			if (strpos($mailer_error, 'php_network_getaddresses: getaddrinfo failed') !== FALSE) {
				$this->addError('sendmail_network_error', $error_message);
				if( YII_ENV_DEV ) {
					$mail_message_parts = $composed->getSwiftMessage()->getChildren();
					$html_mail = $mail_message_parts[0];
					$this->addError('mailbody', "View: $view_name<br/>Subject: $subject<br/>"
						. $mailer_error . '<br/>' . $html_mail->getBody());
				}
				return true;
			} else {
				$this->addError('sendmail', $error_message);
				return false;
			}
		}
		return true;
	}

} // trait

