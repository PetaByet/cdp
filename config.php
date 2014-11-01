<?php

error_reporting(E_ALL);							//error reporting
ini_set('display_errors', 1);

date_default_timezone_set('UTC');					//set time zone

$config = array();
$config['adminusername'] = 'admin';					//admin user name
$config['adminpassword'] = '5f4dcc3b5aa765d61d8327deb882cf99'; 		//md5 encrypted hash (default password is 'password')
$config['adminemail'] = 'someone@example.com';				//admin email address
$config['sendnotification'] = true;					//send email notification (recommended)

?>
