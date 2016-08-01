<?php
$useName = $_POST['user_name'];
$userPwd = md5($_POST['user_password']);

//$encrypted_text = sha1($userPwd);
//$encrypted_text = md5($userPwd);
echo $encrypted_text;

$user_name ="admin";
//MD5 ENCRYPTED TEXT FOR "KARTIK"
$user_password ="c8d39cdb56a46ad807969ee04c4e660b";

//sha1 ENCRYPTED TEXT FOR "KARTIK"
$user_password ="be6f0a633873540a0d5343ec9203e0c13429675c";

if($useName==$user_name && $userPwd==$user_password){
	echo 'Authenticated';
}else{
	echo 'Authentication failed';
}



?>