<?php
 
/*  Function using PHPMailer to send email
 *
 *  Password Reset for ICOM D-Star Callsign Registration
 *  Initial code by Jim Moen- K6JM 
 *		with code from tutorials at http://talkerscode.com/webtricks/password-reset-system-using-php.php
 *      and https://www.johnmorrisonline.com/create-email-based-password-reset-feature-login-script/
*/

function sendemail($to, $bcc, $subject, $message) {
	require_once('/usr/share/php/PHPMailer/PHPMailerAutoload.php');
	$ini = parse_ini_file('/etc/resetpw.ini'); //config file for resetpw
	
	$mail = new PHPMailer(true); //defaults to using php "mail()"; 
	// the true param means it will throw exceptions on errors, which we need to catch
	$result = true;
	$mail = new PHPMailer(true);
	try { $mail->CharSet = "utf-8";
		$mail->isSMTP();
		$mail->SMTPAuth = true;
		$mail->Username = $ini['mail_useraddr']; // Acct to log onto smtp server
		$mail->Password = $ini['mail_password']; // Password 
		$mail->SMTPSecure = "ssl";
		$mail->Host = $ini['mail_host'];  // e.g. smtp.gmail.com
		$mail->Port = $ini['mail_port'];
		$mail->SetFrom($ini['mail_fromaddr'],$ini['gw_callsign'] . " D-Star Gateway");
		$mail->Sender=$ini['mail_fromaddr']; // Force sender envelope 
		$mail->Subject = $subject;
		$mail->addAddress($to);
		if ($bcc <> "none") {  // Add bcc if specified
			$mail->addBCC($bcc);
		}
		$mail->isHTML(true);
		$mail->Body = $message;
		$mail->Send(); 
		} catch (phpmailerException $e) { 
			$log = "resetpwmail: Error sending email." . $e->errorMessage(); //err msgs from PHPMailer
        	file_put_contents('/var/log/resetdstarpw.txt', $log, FILE_APPEND);
        	$result = false;	
		} catch (Exception $e) { 
			$log = "resetpwmail: Error sending email." . $e->getMessage(); //other err msgs
        	file_put_contents('/var/log/resetdstarpw.txt', $log, FILE_APPEND);
        	$result = false;	
		}
	return $result;
}
?>