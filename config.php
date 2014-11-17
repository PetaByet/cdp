<?php

error_reporting ( E_ALL ); //error reporting
ini_set ( 'display_errors', 1 );

date_default_timezone_set ( 'UTC' ); //set time zone

$config                       = array();
$config[ 'adminemail' ]       = 'someone@test.com'; //the email address to send notifications to
$config[ 'sendnotification' ] = true; //send email notification (recommended)
$config[ 'path' ]             = '/var/www/html'; //script root path
$config[ 'version' ]          = 'Beta 1.3'; //script version
$config[ 'logintimeout' ]     = '1800'; //inactivity timeout in seconds
$config[ 'debug' ]            = false; //debug

?>