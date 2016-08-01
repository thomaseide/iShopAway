<?php 
require_once('phpmailer/class.phpmailer.php');

try{
	
	
}catch(Exception $e){
	
	Exception $e
}





	$to_addresss1="p.krunal13@gmail.com";
	$subject = "Welcome to Engage Safety!";	
$body='<font size="4" face="arial black, sans-serif" style="background-color:rgb(255,255,255)" color="#0b5394"><b>Greetings!<b></font>
			<br><br><br>
			Hello Krunal,<br><br>

			Welcome to Engage Safety and thanks for signing up!<br><br>
			
			Now you can submit your personal medical report to us.<br><br>
			
			Cheers,<br>
			Engage Safety Team.';
			
$subject = "Engage Safety Report Submission";
/*$body='<font size="4" face="arial black, sans-serif" style="background-color:rgb(255,255,255)" color="#0b5394"><b>Thank You!</b></font>
			<br><br><br>
			Hello Krunal,<br><br>

			We have successfully received your medial report.<br><br>
						
			Cheers,<br>
			Engage Safety Team.';*/


		$SmtpUser="engagesafetyapp@gmail.com";
		$SmtpPass="klinexa123";
		//$SmtpUser="p.krunal13@gmail.com";
		//$SmtpPass="krunal1393";
		$SmtpServer="smtp.gmail.com";

//				$SmtpServer="smtp.gmail.com";
		$SmtpPort="465"; //default
		$mail = new PHPMailer();
		$mail->IsHTML(true);
		$mail->IsSMTP(); // telling the class to use SMTP
		$mail->Host       = $SmtpServer; // SMTP server
		$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
		$mail->SMTPSecure = "ssl";										   
		$mail->SMTPAuth   = true;                  // enable SMTP authentication
		$mail->Host       = $SmtpServer; // sets the SMTP server
		$mail->Port       = $SmtpPort;                    // set the SMTP port for the GMAIL server
		$mail->Username   = $SmtpUser; // SMTP account username
		$mail->Password   = $SmtpPass;        // SMTP account password
		
		$mail->From=$SmtpUser;
		//$mail->FromName="Yelow";
		
		$mail->Subject    = $subject;
		
		$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
		$mail->MsgHTML($body);
		$mail->Body=$body;		
		//smtp_host,smtp_port,username,password,from_address,to_address
		
		$mail->AddAddress($to_addresss1, $to_addresss1);
		
		
		if(!$mail->Send()) {
			 echo $msg = "error"; 
			 
			} else {
			  echo $msg = "Email has been sent to your email address."; 
			
			}

?>