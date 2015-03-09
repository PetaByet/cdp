<?php

if (php_sapi_name() != 'cli') {
    die();
}
if (!isset($argv[1])) {
    die();
}

require('config.php');
$starttime = time();

$log = 'Backup job (' . $argv[1] . ') started' . PHP_EOL;

$backups       = json_decode(file_get_contents($config['path'] . '/db/db-backups.json'), true);
$backupjobs    = json_decode(file_get_contents($config['path'] . '/db/db-backupjobs.json'), true);
$backupservers = json_decode(file_get_contents($config['path'] . '/db/db-backupservers.json'), true);

function exitcron()
{
    global $log;
    global $config;
    if ($config['sendnotification'] && filter_var($config['adminemail'], FILTER_VALIDATE_EMAIL)) {
        require $config['path'].'/libs/phpmailer/PHPMailerAutoload.php';
        $mail = new PHPMailer;
        if ($config['smtp']) {
            $mail->isSMTP();
            $mail->Host = $config['smtpserver'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtpusername'];
            $mail->Password = $config['smtppassword'];
            $mail->SMTPSecure = $config['smtpsecure'];
            $mail->Port = $config['smtpport'];
        }
        $mail->From = $config['emailfrom'];
        $mail->FromName = 'CDP.me';
        $mail->addAddress($config['adminemail']);
        $mail->WordWrap = 50;
        $mail->AddStringAttachment($log,'log.txt');
        $mail->isHTML(true);
        $mail->Subject = 'CDP.me backup report';
        $mail->Body    = 'Hello!<br />This is your CDP.me backup report at '.date(DATE_RFC822).'.<br/>The backup log is attached.';
        $mail->AltBody = 'Hello! This is your CDP.me backup report at '.date(DATE_RFC822).'. The backup log is attached.';
        if(!$mail->send()) {
            echo 'Message could not be sent.' . PHP_EOL;
            echo 'Mailer Error: ' . $mail->ErrorInfo . PHP_EOL;
        } else {
            echo 'Message has been sent' . PHP_EOL;
        }
    } else {
        echo $log;
    }
    die();
}
function logevent($data, $type)
{
    global $config;
    if (empty($_SERVER['REMOTE_ADDR'])) {
        $ipaddr = 'Not found';
    } else {
        $ipaddr = $_SERVER['REMOTE_ADDR'];
    }
    if (isset($data) && isset($type)) {
        if ($type == 'activity') {
            $activitylogs                       = json_decode(file_get_contents($config['path'] . '/db/db-activitylog.json'), true);
            $activitylogs[count($activitylogs)] = array(
                'id' => count($activitylogs) + 1,
                'data' => trim($data),
                'time' => time(),
                'ip' => $ipaddr
            );
            file_put_contents($config['path'] . '/db/db-activitylog.json', json_encode($activitylogs));
        } elseif ($type == 'backup') {
            $backuplogs                     = json_decode(file_get_contents($config['path'] . '/db/db-backuplog.json'), true);
            $backuplogs[count($backuplogs)] = array(
                'id' => count($backuplogs) + 1,
                'data' => trim($data),
                'time' => time(),
                'ip' => $ipaddr
            );
            file_put_contents($config['path'] . '/db/db-backuplog.json', json_encode($backuplogs));
        }
    }
}
function GetJobDetails($jobid)
{
    global $backupjobs;
    foreach ($backupjobs as $backupjob) {
        if ($backupjob['id'] == $jobid) {
            return $backupjob;
        }
    }
    return false;
}
function GetServerDetails($host)
{
    global $backupservers;
    foreach ($backupservers as $backupserver) {
        if ($backupserver['host'] == $host) {
            return $backupserver;
        }
    }
    return false;
}
$backupjob = GetJobDetails($argv[1]);
if (!$backupjob) {
    $log .= 'Job ID does not exist' . PHP_EOL;
    exitcron();
}
$backupserver = GetServerDetails($backupjob['source']);
if (!$backupserver) {
    $log .= 'Server does not exist' . PHP_EOL;
    exitcron();
}

if ($backupjob['type'] == 'full' || $backupjob['type'] == 'incremental') {
    
    $log .= 'Starting file backup' . PHP_EOL;
    set_include_path($config['path'] . '/libs/phpseclib');
    
    include('Net/SSH2.php');
    include('Net/SFTP.php');
    include('Crypt/RSA.php');
    include('Crypt/AES.php');
    $ssh  = new Net_SSH2($backupserver['host'], $backupserver['port']);
    $sftp = new Net_SFTP($backupserver['host'], $backupserver['port']);
    if ($backupserver['authtype'] == 'password') {
        if (!$ssh->login($backupserver['username'], $backupserver['password'])) {
            $log .= 'SSH password login failed' . PHP_EOL;
            exitcron();
        }
        if (!$sftp->login($backupserver['username'], $backupserver['password'])) {
            $log .= 'SFTP password login failed' . PHP_EOL;
            exitcron();
        }
    } elseif ($backupserver['authtype'] == 'key') {
        $serverkey = explode(' ', $backupserver['password']);
        $key       = new Crypt_RSA();
        if (isset($serverkey[1])) {
            $key->setPassword($serverkey[1]);
        }
        $key->loadKey(file_get_contents($serverkey[0]));
        if (!$ssh->login($backupserver['username'], $key)) {
            $log .= 'SSH key login failed' . PHP_EOL;
            exitcron();
        }
        if (!$sftp->login($backupserver['username'], $key)) {
            $log .= 'SFTP key login failed' . PHP_EOL;
            exitcron();
        }
    } else {
        $log .= 'SSH login failed' . PHP_EOL;
        exitcron();
    }
    
    $dirname = 'cdpme-' . date("Y-m-d-H-i-s") . '-' . $backupjob['id'];
    $log .= $ssh->exec(escapeshellcmd('mkdir /tmp/' . $dirname)) . PHP_EOL;
    if ($backupjob['type'] == 'incremental') {
        $incrementalbackups     = json_decode(file_get_contents($config['path'] . '/db/db-backups.json'), true);
        $incrementalbackups     = array_reverse($incrementalbackups);
        $incrementalbackuparray = array();
        foreach ($backups as $backup) {
            if ($backupjob['id'] == $backup['id']) {
                $incrementalbackuparray[count($incrementalbackuparray)] = $backup;
            }
        }
        if (is_array($incrementalbackuparray[0])) {
            $incrementaltartime = date("Y-m-d H:i", $incrementalbackuparray[0]['time']);
        } else {
            $incrementaltartime = date("Y-m-d H:i", '0');
        }
        $log .= $ssh->exec(escapeshellcmd('tar --newer-mtime=' . $incrementaltartime . ' -zcvf ' . $dirname . '.tar.gz ' . $backupjob['directory'])) . PHP_EOL;
    } else {
        $log .= $ssh->exec(escapeshellcmd('tar -zcvf ' . $dirname . '.tar.gz ' . $backupjob['directory'])) . PHP_EOL;
    }
    $log .= $ssh->exec(escapeshellcmd('mv ' . $dirname . '.tar.gz /tmp/' . $dirname)) . PHP_EOL;
    $log .= $sftp->chdir('/tmp/' . $dirname) . PHP_EOL;
    $sftpfiletransfer = $sftp->get($dirname . '.tar.gz', $dirname . '.tar.gz') . PHP_EOL;
    if (!$sftpfiletransfer) {
        $log .= 'File transfer failed' . PHP_EOL;
        exitcron();
    } else {
        $log .= $sftpfiletransfer;
    }
    $log .= $ssh->exec(escapeshellcmd('rm -rf /tmp/' . $dirname)) . PHP_EOL;
    $log .= rename($dirname . '.tar.gz', $config['path'] . '/files/' . $dirname . '.tar.gz') . PHP_EOL;
    if (isset($backupjob['encryption']) && $backupjob['encryption'] == 'AES-256') {
        $log .= 'Encrypting file with AES-256'.PHP_EOL;
        $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
        $cipher->setKey($backupjob['encryptionkey']);
        file_put_contents($config['path'] . '/files/' . $dirname . '.tar.gz', $cipher->encrypt(file_get_contents($config['path'] . '/files/' . $dirname . '.tar.gz')));
    } elseif (isset($backupjob['encryption']) && $backupjob['encryption'] == 'GPG') {
        $log .= 'Encrypting file with GPG'.PHP_EOL;
        require_once $config['path'].'/libs/php-gpg-master/GPG.php';
        $gpg = new GPG();
        $pub_key = new GPG_Public_Key(file_get_contents($backupjob['encryptionkey']));
        file_put_contents($config['path'] . '/files/' . $dirname . '.tar.gz', $gpg->encrypt(file_get_contents($config['path'] . '/files/' . $dirname . '.tar.gz')));
    }
    $backups[count($backups)] = array(
        'id' => $backupjob['id'],
        'file' => $dirname . '.tar.gz',
        'size' => filesize($config['path'] . '/files/' . $dirname . '.tar.gz'),
        'time' => $starttime
    );
    
    file_put_contents($config['path'] . '/db/db-backups.json', json_encode($backups));
} elseif ($backupjob['type'] == 'mysql') {
    set_include_path($config['path'] . '/libs/phpseclib');
    include('Crypt/AES.php');
    $log .= 'Starting SQL backup' . PHP_EOL;
    $database = explode(' ', $backupjob['directory']);
    if (isset($database[1])) {
        $tables = $database[1];
    } else {
        $tables = '*';
    }
    $link = mysql_connect($backupserver['host'] . ':' . $backupserver['port'], $backupserver['username'], $backupserver['password']);
    if (!is_resource($link)) {
        $log .= 'Unable to establish a MySQL connection: ' . mysql_error() . PHP_EOL;
        exitcron();
    }
    $db_selected = mysql_select_db($database[0], $link);
    if (!$db_selected) {
        $log .= 'MySQL select db error: ' . mysql_error() . PHP_EOL;
        exitcron();
    }
    
    if ($tables == '*') {
        $tables = array();
        $result = mysql_query('SHOW TABLES');
        if (!$result) {
            $log .= 'MySQL SHOW TABLES query error: ' . mysql_error() . PHP_EOL;
            exitcron();
        }
        while ($row = mysql_fetch_row($result)) {
            $tables[] = $row[0];
        }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }
    
    foreach ($tables as $table) {
        $result = mysql_query('SELECT * FROM ' . $table);
        if (!$result) {
            $log .= 'MySQL SELECT * FROM query error: ' . mysql_error() . PHP_EOL;
            exitcron();
        }
        $num_fields = mysql_num_fields($result);
        
        $return = 'DROP TABLE ' . $table . ';';
        $row2   = mysql_query('SHOW CREATE TABLE ' . $table);
        if (!$row2) {
            $log .= 'MySQL SHOW CREATE TABLE query error: ' . mysql_error() . PHP_EOL;
            exitcron();
        }
        $row2 = mysql_fetch_row($row2);
        $return .= "\n\n" . $row2[1] . ";\n\n";
        
        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = mysql_fetch_row($result)) {
                $return .= 'INSERT INTO ' . $table . ' VALUES(';
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = preg_replace("/\r\n/","\\r\\n",$row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }
    $backupname = 'cdpme-' . date("Y-m-d-H-i-s") . '-' . $backupjob['id'] . '.sql';
    if (isset($backupjob['encryption']) && $backupjob['encryption'] = 'AES-256') {
        $log .= 'Encrypting file with AES-256'.PHP_EOL;
        $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
        $cipher->setKey($backupjob['encryptionkey']);
        file_put_contents($config['path'] . '/files/' . $backupname, $cipher->encrypt($return));
    } elseif (isset($backupjob['encryption']) && $backupjob['encryption'] == 'GPG') {
        $log .= 'Encrypting file with GPG'.PHP_EOL;
        require_once $config['path'].'/libs/php-gpg-master/GPG.php';
        $gpg = new GPG();
        $pub_key = new GPG_Public_Key(file_get_contents($backupjob['encryptionkey']));
        file_put_contents($config['path'] . '/files/' . $backupname, $gpg->encrypt($return));
    } else {
        file_put_contents($config['path'] . '/files/' . $backupname, $return);
    }
    
    $backups[count($backups)] = array(
        'id' => $backupjob['id'],
        'file' => $backupname,
        'size' => filesize($config['path'] . '/files/' . $backupname),
        'time' => $starttime
    );
    
    file_put_contents($config['path'] . '/db/db-backups.json', json_encode($backups));
} elseif ($backupjob['type'] == 'openvz') {
    $log .= 'Starting OpenVZ backup' . PHP_EOL;
    set_include_path($config['path'] . '/libs/phpseclib');
    
    include('Net/SSH2.php');
    include('Net/SFTP.php');
    include('Crypt/RSA.php');
    include('Crypt/AES.php');
    $ssh  = new Net_SSH2($backupserver['host'], $backupserver['port']);
    $sftp = new Net_SFTP($backupserver['host'], $backupserver['port']);
    if ($backupserver['authtype'] == 'password') {
        if (!$ssh->login($backupserver['username'], $backupserver['password'])) {
            $log .= 'SSH password login failed' . PHP_EOL;
            exitcron();
        }
        if (!$sftp->login($backupserver['username'], $backupserver['password'])) {
            $log .= 'SFTP password login failed' . PHP_EOL;
            exitcron();
        }
    } elseif ($backupserver['authtype'] == 'key') {
        $serverkey = explode(' ', $backupserver['password']);
        $key       = new Crypt_RSA();
        if (isset($serverkey[1])) {
            $key->setPassword($serverkey[1]);
        }
        $key->loadKey(file_get_contents($serverkey[0]));
        if (!$ssh->login($backupserver['username'], $key)) {
            $log .= 'SSH key login failed' . PHP_EOL;
            exitcron();
        }
        if (!$sftp->login($backupserver['username'], $key)) {
            $log .= 'SFTP key login failed' . PHP_EOL;
            exitcron();
        }
    } else {
        $log .= 'SSH login failed' . PHP_EOL;
        exitcron();
    }
    
    $dirname = 'cdpme-' . date("Y-m-d-H-i-s") . '-' . $backupjob['id'];
    $verifyopenvz = $ssh->exec(escapeshellcmd('vzctl --version'));
    if (strpos($verifyopenvz,'vzctl version') !== false) {
        $log .= 'OpenVZ detected' . PHP_EOL;
    } else {
        $log .= 'OpenVZ / vzctl not detected' . PHP_EOL;
        exitcron();
    }
    $verifyvzdump = $ssh->exec(escapeshellcmd('vzdump'));
    if (strpos($verifyvzdump,'command not found') !== false) {
        $log .= 'vzdump command not found' . PHP_EOL;
        exitcron();
    } else {
        $log .= 'vzdump detected' . PHP_EOL;
    }
    $verifyproxmox = $ssh->exec(escapeshellcmd('pveversion -v'));
    if (strpos($verifyproxmox, 'pve') !== false) {
        $log .= 'ProxMox detected'.PHP_EOL;
        $isproxmox = true;
    } else {
        $log .= 'Standard OpenVZ detected'.PHP_EOL;
        $isproxmox = false;
    }
    $log .= $ssh->exec(escapeshellcmd('mkdir /tmp/' . $dirname)) . PHP_EOL;
    $sftp->chdir('/tmp/' . $dirname);
    $containers = $ssh->exec(escapeshellcmd('vzlist -jao ctid'));
    $containers = json_decode($containers, true);
    $donotbackup = explode(' ', $backupjob['directory']);
    $containerstobackup = array();
    foreach ($containers as $container) {
        if (!in_array($container['ctid'], $donotbackup)) {
            $containerstobackup[count($containerstobackup)] = $container['ctid'];
        }
    }
    foreach ($containerstobackup as $container) {
        $log .= 'Backing up CT '. $container . PHP_EOL;
        $vzstarttime = time();
        if ($isproxmox) {
            $log .= $ssh->exec(escapeshellcmd('vzdump -mode snapshot -compress gzip -dumpdir /tmp/' . $dirname . ' ' .$container)) . PHP_EOL;
            $log .= $ssh->exec(escapeshellcmd('mv vzdump-openvz-*.tgz vzdump-'.$container.'.tgz')) . PHP_EOL;
        } else {
            $log .= $ssh->exec(escapeshellcmd('vzdump --snapshot --compress --dumpdir /tmp/' . $dirname . ' ' .$container)) . PHP_EOL;
        }
        if (!$sftp->size('vzdump-'.$container.'.tgz')) {
            $log .= 'vzdump-'.$container.'.tgz not found'. PHP_EOL;
        }
        else {
            $log .= 'CT backup completed, transferring CT '. $container . PHP_EOL;
            $sftpfiletransfer = $sftp->get('vzdump-'.$container.'.tgz', $dirname . '-vzdump-'.$container.'.tgz') . PHP_EOL;
            if (!$sftpfiletransfer) {
                $log .= 'CT transfer failed' . PHP_EOL;
            } else {
                $log .= $sftpfiletransfer;
                $log .= $ssh->exec(escapeshellcmd('rm -f /tmp/' . $dirname.'/vzdump-'.$container.'.tgz')) . PHP_EOL;
                $log .= rename($dirname . '-vzdump-'.$container.'.tgz', $config['path'] . '/files/' . $dirname . '-vzdump-'.$container.'.tgz') . PHP_EOL;
                if (isset($backupjob['encryption']) && $backupjob['encryption'] = 'AES-256') {
                    $log .= 'Encrypting file with AES-256'.PHP_EOL;
                    $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
                    $cipher->setKey($backupjob['encryptionkey']);
                    file_put_contents($config['path'] . '/files/' . $dirname . '-vzdump-'.$container.'.tgz', $cipher->encrypt(file_get_contents($config['path'] . '/files/' . $dirname . '-vzdump-'.$container.'.tgz')));
                } elseif (isset($backupjob['encryption']) && $backupjob['encryption'] == 'GPG') {
                    $log .= 'Encrypting file with GPG'.PHP_EOL;
                    require_once $config['path'].'/libs/php-gpg-master/GPG.php';
                    $gpg = new GPG();
                    $pub_key = new GPG_Public_Key(file_get_contents($backupjob['encryptionkey']));
                    file_put_contents($config['path'] . '/files/' . $dirname . '-vzdump-'.$container.'.tgz', $gpg->encrypt(file_get_contents($config['path'] . '/files/' . $dirname . '-vzdump-'.$container.'.tgz')));
                }
                $backups[count($backups)] = array(
                    'id' => $backupjob['id'],
                    'file' => $dirname . '-vzdump-'.$container.'.tgz',
                    'size' => filesize($config['path'] . '/files/' . $dirname . '-vzdump-'.$container.'.tgz'),
                    'time' => $vzstarttime
                );
                file_put_contents($config['path'] . '/db/db-backups.json', json_encode($backups));
            }
        }
    }
    $log .= $ssh->exec(escapeshellcmd('rm -rf /tmp/' . $dirname)) . PHP_EOL;
} elseif ($backupjob['type'] == 'cpanel') {
    $log .= 'Starting cPanel backup'.PHP_EOL;
    require $config['path'].'/libs/cpanelxmlapi-php/xmlapi.php';
    require $config['path'].'/libs/simplehtml/simple_html_dom.php';
    set_include_path($config['path'] . '/libs/phpseclib');
    include('Crypt/AES.php');
    $xmlapi = new xmlapi($backupserver['host']);
    $xmlapi->set_port($backupserver['port']);
    $xmlapi->password_auth($backupserver['username'], $backupserver['password']);
    $xmlapi->set_output('json');
    $xmlresult = json_decode($xmlapi->api1_query($backupserver['username'],'Fileman','fullbackup',array('homedir')), true);
    $cpstarttime = time();
    if ($xmlresult['event']['result'] == 1) {
        $log .=  'Backup started, waiting for it to finish'.PHP_EOL;
        $backupavailable = false;
        while(!$backupavailable) {
            $xmlresult = json_decode($xmlapi->api1_query($backupserver['username'],'Fileman','listfullbackups'), true);
            if (strpos($xmlresult['data']['result'],'okmsg') !== false) {
                $log .=  'Backup found, continuing'.PHP_EOL;
                $html = str_get_html($xmlresult['data']['result']);
                $latestbackup = $html->find('div[class=okmsg]', -1)->find('a', -1)->plaintext;
                $backupavailable = true;
            }
            else {
                $log .=  'Backup is not available yet, waiting 30 seconds and re-try.'.PHP_EOL;
                sleep(30);
                $log .= 'Re-trying'.PHP_EOL;
            }
        }
        $filename = 'cdpme-' . date("Y-m-d-H-i-s") . '-' . $latestbackup;
        $log .= 'Creating temporary FTP account for backup transfer'.PHP_EOL;
        $tempftpuser = 'cdpme'.rand(100,999);
        $tempftppw = rand().time();
        $createftp = json_decode($xmlapi->api2_query($backupserver['username'], 'Ftp', 'addftp',array('user' => $tempftpuser, 'pass' => $tempftppw, 'quota' => 0, 'homedir' => '/')), true);
        if ($createftp['cpanelresult']['data'][0]['result'] == 1) {
            $log .= 'Temporary FTP Account created'.PHP_EOL;;
        } else {
            $log .= 'Unable to create FTP account. The error returned was: '.$createftp['cpanelresult']['error'].PHP_EOL;;
            exitcron();
        }
        $maindomain = json_decode($xmlapi->api2_query($backupserver['username'], 'ZoneEdit','fetch_cpanel_generated_domains'), true);
        $maindomain = chop($maindomain['cpanelresult']['data'][count($maindomain['cpanelresult']['data'])-1]['domain'], '.');
        $ftp_connect = ftp_connect($backupserver['host']);
        if (!$ftp_connect) {
            $log .=  'FTP connect failed'.PHP_EOL;
            print_r(error_get_last());
            exitcron();
        }
        $ftp_login = @ftp_login($ftp_connect, $tempftpuser.'@'.$maindomain, $tempftppw);
        if (!$ftp_login) {
            $log .=  'FTP login failed ('.$tempftpuser.'@'.$maindomain.')'.PHP_EOL;
            print_r(error_get_last());
            exitcron();
        }
        if (ftp_get($ftp_connect, $filename, $latestbackup, FTP_BINARY)) {
            $log .=  'Backup downloaded, deleting from the cPanel server'.PHP_EOL;
        } else {
            $log .=  'Error downloading backup'.PHP_EOL;
            print_r(error_get_last());
            exitcron();
        }
        ftp_close($ftp_connect);
        $log .= rename($filename, $config['path'] . '/files/' . $filename) . PHP_EOL;
        $xmlresult = json_decode($xmlapi->api1_query($backupserver['username'],'Fileman','delfile',array('/',$latestbackup)), true);
        if ($xmlresult['event']['result'] == 1) {
            $log .=  'Backup deleted.'.PHP_EOL;
        }
        else {
            $log .=  'Backup not deleted form the cPanel server, exiting...'.PHP_EOL;
            exitcron();
        }
        $xmlapi->api1_query($backupserver['username'],'Fileman','Empty Trash');
        $deleteftp = json_decode($xmlapi->api2_query($backupserver['username'], 'Ftp', 'delftp',array('user' => $tempftpuser)), true);
        $log .= 'Deleting temporary FTP account for backup transfer'.PHP_EOL;
        if ($deleteftp['cpanelresult']['data'][0]['result'] == 1) {
            $log .= 'Temporary FTP Account deleted'.PHP_EOL;;
        } else {
            $log .= 'Unable to delete FTP account. The error returned was: '.$deleteftp['cpanelresult']['error'].PHP_EOL;;
            exitcron();
        }
        if (isset($backupjob['encryption']) && $backupjob['encryption'] = 'AES-256') {
            $log .= 'Encrypting file with AES-256'.PHP_EOL;
            $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
            $cipher->setKey($backupjob['encryptionkey']);
            file_put_contents($config['path'] . '/files/' . $filename, $cipher->encrypt(file_get_contents($config['path'] . '/files/' . $filename)));
        } elseif (isset($backupjob['encryption']) && $backupjob['encryption'] == 'GPG') {
            $log .= 'Encrypting file with GPG'.PHP_EOL;
            require_once $config['path'].'/libs/php-gpg-master/GPG.php';
            $gpg = new GPG();
            $pub_key = new GPG_Public_Key(file_get_contents($backupjob['encryptionkey']));
            file_put_contents($config['path'] . '/files/' . $filename, $gpg->encrypt(file_get_contents($config['path'] . '/files/' . $filename)));
        }
        $backups[count($backups)] = array(
            'id' => $backupjob['id'],
            'file' => $filename,
            'size' => filesize($config['path'] . '/files/' . $filename),
            'time' => $cpstarttime
        );
        file_put_contents($config['path'] . '/db/db-backups.json', json_encode($backups));
    }
    else {
        $log .=  'Backup failed';
        exitcron();
    }
} else {
    $log .= 'Backup type not valid' . PHP_EOL;
    exitcron();
}

$timetaken = time() - $starttime;

$log .= 'Backup completed in ' . $timetaken . ' seconds.' . PHP_EOL;

if (isset($backupjob['expiry'])) {
    $backups = json_decode(file_get_contents($config['path'] . '/db/db-backups.json'), true);
    $log .= 'Processing backup auto-delete' . PHP_EOL;
    $expirecutofftime = time() - 86400 * $backupjob['expiry'];
    foreach ($backups as $backupkey => $backup) {
        if ($backup['id'] == $backupjob['id'] && $backup['time'] < $expirecutofftime) {
            if (file_exists($config['path'] . '/files/' . $backup['file']) && unlink($config['path'] . '/files/' . $backup['file'])) {
                unset($backups[$backupkey]);
                $log .= 'Successfully removed ' . $backup['file'] . PHP_EOL;
            } else {
                $log .= 'Error removing ' . $backup['file'] . PHP_EOL;
            }
        }
    }
    file_put_contents($config['path'] . '/db/db-backups.json', json_encode($backups));
}

logevent('Backup for '.$backupserver['host'].' completed.', 'backup');

exitcron();

?>