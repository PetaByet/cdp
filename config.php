<?php

/**
CDP.me | Data Backups
Copyright (C) 2014  CDP.me / PetaByet.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
**/

error_reporting(E_ALL);                                 //error reporting
ini_set('display_errors', 1);

date_default_timezone_set('UTC');                       //set time zone

$config                     = array();
$config['adminemail']       = 'someone@test.com';       //the email address to send notifications to
$config['sendnotification'] = true;                     //send email notification (recommended)
$config['emailfrom']        = 'someone@test.com';       //send email from
$config['smtp']             = false;                    //use smtp to send emails
$config['smtpserver']       = '';                       //smtp server (only enter if smtp is true)
$config['smtpusername']     = '';                       //smtp username (only enter if smtp is true)
$config['smtppassword']     = '';                       //smtp password (only enter if smtp is true)
$config['smtpsecure']       = 'tls';                    //smtp encryption (tls / ssl)
$config['smtpport']         = 587;                       //smtp port (only enter if smtp is true)
$config['path']             = '/var/www/html';          //script root path
$config['version']          = 'Beta 1.5';               //script version
$config['logintimeout']     = '1800';                   //inactivity timeout in seconds
$config['debug']            = false;                    //debug mode

?>