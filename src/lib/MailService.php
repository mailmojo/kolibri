<?php
require("class.phpmailer.php");

/**
 * This class provides an interface to send e-mail messages, using the PHPMailer library which
 * must be located in your include_path before this class is used.
 *
 * The following configuration settings under a [mail] section is supported:
 *
 *   from.email
 *   from.name
 *   smtp.auth
 *   smtp.host
 *   smtp.username
 *   smtp.password
 * 
 * The from.* configs will only be used if specific e-mail messages don't specify their from
 * address. smtp.host can be left empty if you want to use PHPs built-in mail() function as
 * opposed to regular SMTP.
 *
 * This service is designed to be used in conjunction with the Email class. An instance of that
 * class represents a complete e-mail message, which can then be sent by passing it to this
 * class' send() method.
 */
class MailService extends PHPMailer {

	/**
	 * Creates an instance of the mail service, set up using the values of the mail
	 * configuration settings.
	 *
	 * @param bool $exceptions Whether or not to throw external exceptions. We default to
	 *                         true even though PHPMailer defaults to false.
	 * @param bool $persistent Whether or not to keep the connection to the SMTP server alive
	 *                         after sending a mail. Useful if sending multiple messages. Only
	 *                         applies if SMTP sending is used.
	 */
	public function __construct ($exceptions = true, $persistent = false) {
		parent::__construct($exceptions);
		$conf = Config::get('mail');

		if (!empty($conf['smtp.auth'])) {
			$this->IsSmtp();
			$this->SMTPAuth = ($conf['smtp.auth'] ? true : false);
			$this->Host = $conf['smtp.host'];

			if ($this->SMTPAuth) {
				$this->Username = $conf['smtp.username'];
				$this->Password = $conf['smtp.password'];
			}

			if (isset($conf['smtp.port'])) {
				$this->Port = $conf['smtp.port'];
			}

			$this->SMTPKeepAlive = $persistent;
		}

		$this->CharSet  = 'utf-8';

		if (!empty($conf['sender'])) {
			/*
			 * Set the Sender address (Return-Path) of the message. Will be sent as MAIL FROM
			 * to the SMTP server.
			 */
			$this->Sender = $conf['sender'];
		}
	}

	/**
	 * Destructor which closes the SMTP connection, if any.
	 */
	public function __destruct () {
		if ($this->Mailer == 'smtp') {
			$this->SmtpClose();
		}
	}

	/**
	 * Sends a specific e-mail message. If a from address isn't specified by the e-mail, the
	 * sender (Return-Path) address configured for this mail service is used.
	 *
	 * @param Email $mail The e-mail message to send.
	 * @return bool       TRUE if the e-mail was sent successfully, FALSE if not.
	 */
	public function send ($mail) {
		if (empty($mail->from)) {
			$conf = Config::get('mail');
			$mail->from = $conf['from.email'];
			$mail->fromName = $conf['from.name'];
		}

		$this->SetFrom($mail->from, $mail->fromName);

		if (!empty($mail->sender)) {
			$this->Sender = $mail->sender;
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
