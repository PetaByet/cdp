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

//adjust CDP configurations below

$config                     = array();
$config['adminemail']       = 'someone@test.com';              //the email address to send notifications to
$config['sendnotification'] = true;                            //send email notification (recommended)
$config['emailfrom']        = 'someone@test.com';              //send email from
$config['smtp']             = false;                           //use smtp to send emails
$config['smtpserver']       = '';                              //smtp server (only enter if smtp is true)
$config['smtpusername']     = '';                              //smtp username (only enter if smtp is true)
$config['smtppassword']     = '';                              //smtp password (only enter if smtp is true)
$config['smtpsecure']       = 'tls';                           //smtp encryption (tls / ssl)
$config['smtpport']         = 587;                             //smtp port (only enter if smtp is true)
$config['path']             = '/var/www/html';                 //script root path
$config['version']          = '1.0';                           //script version
$config['logintimeout']     = '1800';                          //inactivity timeout in seconds
$config['debug']            = false;                           //debug mode
$config['debuglevel']       = 2;                               //0 for just show errors, 1 for just log errors or 2 for show and log errors
$config['errorlevels']      = 'E_ALL | E_STRICT';              //error reporting level
$config['logerrors']        = true;                            //log errors to the syslog even when degub is set to false
$config['timezone']         = 'UTC';                           //default time zone to use

// DON'T EDIT BELOW THIS LINE

error_reporting($config['errorlevels']);                       //set error reporting level
date_default_timezone_set($config['timezone']);                //set time zone

if ($config['debug'])                                         //enable or disable debug mode
{
	switch ($config['debuglevel'])
	{
		case 0:
			ini_set('display_errors', 1);
			ini_set('log_errors', 0);
			break;
		case 1:
			ini_set('display_errors', 0);
			ini_set('log_errors', 1);
			break;
		case 2:
			ini_set('display_errors', 1);
			ini_set('log_errors', 1);
			break;
		default:
			ini_set('display_errors', 1);
			ini_set('log_errors', 1);
			break;
	}
}
else
{
	ini_set('display_errors', 0);
}

if ($config['logerrors'])                                      //enable or disable error logging for the syslog
{
	ini_set('log_errors', 1);
	ini_set('error_log', 'syslog');
}

?>