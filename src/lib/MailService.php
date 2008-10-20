<?php
require(ROOT . '/lib/phpmailer/class.phpmailer.php');

/**
 * <code>MailService</code> provides an interface to send e-mail messages over a SMTP connection.
 * <code>turbo-conf.php</code> must contain the following configuration settings for this service to work:
 *
 * 'mail'			=> array(
 *		'from'			=> '',		// E-mail address of the sender
 *		'from_name'		=> '',		// Name of the sender
 *		'smtp_auth'		=> true,	// A boolean indicating if the SMTP connection is authenticated
 *		'smtp_host'		=> '',		// Host of the SMTP server
 *		'smtp_username'	=> '',		// Username, if SMTP authentication is in use
 *		'smtp_password'	=> ''		// Password, if SMTP authentication is in use
 *	)
 *
 * This service is designed to be used in conjunction with the <code>Mail</code> class. An instance of that
 * class represents a complete e-mail message, which can then be sent by passing it to this class'
 * <code>send()</code> method.
 *
 * @version		$Id: MailService.php 1549 2008-09-05 13:53:28Z frode $
 */
class MailService extends PHPMailer {

	/**
	 * Creates an instance of the mail service, set up using the values of the <code>'mail'</code>
	 * configuration setting.
	 */
	public function __construct ($persistent = false) {
		$conf = Config::get('mail');

		$this->IsSmtp();
		$this->PluginDir	= ROOT . '/lib/phpmailer/';
		$this->Host			= $conf['smtp_host'];
		$this->SMTPAuth 	= $conf['smtp_auth'];

		if ($this->SMTPAuth) {
			$this->Username = $conf['smtp_username'];
			$this->Password = $conf['smtp_password'];
		}

		$this->CharSet		= 'utf-8';
		$this->Encoding		= 'quoted-printable';
		$this->WordWrap		= 76;

		// Set whether the SMTP connection should be persistent or closed after one mail is sent
		$this->SMTPKeepAlive = $persistent;

//		if (!empty($conf['sender'])) {
//			/*
//			 * Set the Sender address (Return-Path) of the message. Will be sent as MAIL FROM to the SMTP
//			 * server.
//			 */
//			$this->Sender = $conf['sender'];

			/*
			 * We must set a manual Sender header in addition to the sender above. This address must be set,
			 * and must match the sender address (Return-Path), or else external images in HTML emails does
			 * not work in Outlook (possibly among others).
			 */
//			if (!empty($conf['sender_name'])) {
//				$sender_header = '"' . $conf['sender_name'] . '" <' . $this->Sender . '>';
//			}
//			else {
//				$sender_header = $this->Sender;
//			}
//			
//			$this->AddCustomHeader('Sender: ' . $sender_header);
//		}
	}

	/**
	 * Sends a specific e-mail message. If a from address isn't specified by the e-mail, the sender
	 * (Return-Path) address configured for this mail service is used.
	 *
	 * @param Email $mail	The e-mail message to send.
	 * @return bool			TRUE if the e-mail was sent successfully, FALSE if not.
	 */
	public function send ($mail) {
		if (empty($mail->from)) {
			$conf = Config::get('mail');
			$mail->from		= $conf['from'];
			$mail->fromName	= $conf['from_name'];
		}

		$this->From		= $mail->from;
		$this->FromName	= $mail->fromName;

		if (!empty($mail->sender)) {
			$this->Sender = $mail->sender;
			$this->SetSenderHeader($mail->sender, $mail->senderName);
		}

		if (!empty($mail->replyTo)) {
			foreach ($mail->replyTo as $replyTo) {
				$this->AddReplyTo($replyTo[0], $replyTo[1]);
			}
		}

		if (!empty($mail->recipients)) {
			foreach ($mail->recipients as $recipient) {
				$this->AddAddress($recipient[0], $recipient[1]);
			}
		}

		if (!empty($mail->cc)) {
			foreach ($mail->cc as $cc) {
				$this->AddCC($cc[0], $cc[1]);
			}
		}

		if (!empty($mail->bcc)) {
			foreach ($mail->bcc as $bcc) {
				$this->AddBCC($bcc[0], $bcc[1]);
			}
		}

		$this->Subject	= $mail->subject;
		$this->Body		= $mail->body;

		if (!empty($mail->altBody)) {
			$this->AltBody = $mail->altBody;
			$this->IsHTML(true);
		}
		else {
			$this->AltBody = '';
			$this->IsHTML(false);
		}

		if (!empty($mail->attachments)) {
			foreach ($mail->attachments as $attachment) {
				$this->AddAttachment($attachment['file'], $attachment['name']);
			}
		}
		
		$status = parent::Send();
		$this->reset();
		return $status;
	}

	/**
	 * Resets the service so it can send a new message.
	 */
	private function reset () {
		$this->ClearAllRecipients();
		$this->ClearAttachments();
		$this->ClearCustomHeaders();
	}
}
?>
