<?php
if (php_sapi_name() != 'cli') {
    die();
}
if (!isset($argv[1]) || !isset($argv[2])) {
    die();
}

require('config.php');
$starttime = time();

echo 'Backup restore job (' . $argv[1] . ') started' . PHP_EOL;
//making sure backup restore job is not terminated
ignore_user_abort(true);
set_time_limit(0);
echo 'Initiating backup restore...' . PHP_EOL;
$backups       = json_decode(file_get_contents($config['path'] . '/db/db-backups.json'), true);
$backupjobs    = json_decode(file_get_contents($config['path'] . '/db/db-backupjobs.json'), true);
$backupservers = json_decode(file_get_contents($config['path'] . '/db/db-backupservers.json'), true);

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

function GetBackupDetails($backupdata)
{
    global $backups;
    foreach ($backups as $backup) {
        if ($backup['file'] == $backupdata) {
            return $backup;
        }
    }
    return false;
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
$backup       = GetBackupDetails($argv[1]);
$backupjob    = GetJobDetails($backup['id']);
$backupserver = GetServerDetails($argv[2]);
if (isset($backupjob['encryption']) && $backupjob['encryption'] == 'GPG') {
    echo 'GPG-encrypted backups cannot be automatically restored'.PHP_EOL;
    die();
}
set_include_path($config['path'] . '/libs/phpseclib');
include('Crypt/AES.php');
if ($backupjob['type'] == 'full' || $backupjob['type'] == 'incremental') {
    set_include_path($config['path'] . '/libs/phpseclib');
    include('Net/SSH2.php');
    include('Net/SFTP.php');
    include('Crypt/RSA.php');
    $ssh  = new Net_SSH2($backupserver['host'], $backupserver['port']);
    $sftp = new Net_SFTP($backupserver['host'], $backupserver['port']);
    if ($backupserver['authtype'] == 'password') {
        if (!$ssh->login($backupserver['username'], $backupserver['password'])) {
            die('SSH password login failed');
        }
        if (!$sftp->login($backupserver['username'], $backupserver['password'])) {
            die('SFTP password login failed');
        }
    } elseif ($backupserver['authtype'] == 'key') {
        $serverkey = explode(' ', $backupserver['password']);
        $key       = new Crypt_RSA();
        if (isset($serverkey[1])) {
            $key->setPassword($serverkey[1]);
        }
        $key->loadKey(file_get_contents($serverkey[0]));
        if (!$ssh->login($backupserver['username'], $key)) {
            die('SSH key login failed');
        }
        if (!$sftp->login($backupserver['username'], $key)) {
            die('SFTP key login failed');
        }
    } else {
        die('SSH login failed');
    }
    echo $sftp->chdir('/tmp');
    if (isset($backupjob['encryption']) && $backupjob['encryption'] == 'AES-256') {
        echo 'Decrypting file with AES-256'.PHP_EOL;
        $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
        $cipher->setKey($backupjob['encryptionkey']);
        file_put_contents($config['path'] . '/files/' . $argv[1].'.decrypted', $cipher->decrypt(file_get_contents($config['path'] . '/files/' . $argv[1])));
        echo 'Transferring the file'.PHP_EOL;
        echo $sftp->put($argv[1], $config['path'] . '/files/' . $argv[1].'.decrypted', NET_SFTP_LOCAL_FILE);
        unlink($config['path'] . '/files/' . $argv[1] . '.decrypted');
    }
    else {
        echo 'Transferring the file'.PHP_EOL;
        echo $sftp->put($argv[1], $config['path'] . '/files/' . $argv[1], NET_SFTP_LOCAL_FILE);
    }
    echo $ssh->exec(escapeshellcmd('tar -zxvf ./' . $argv[1] . ' -C /'));
    echo $ssh->exec(escapeshellcmd('rm -f ./' . $argv[1]));
} elseif ($backupjob['type'] == 'mysql') {
    $database = explode(' ', $backupjob['directory']);
    $db       = new PDO('mysql:host=' . $backupserver['host'] . ';port=' . $backupserver['port'] . ';dbname=' . $database[0] . ';charset=utf8', $backupserver['username'], $backupserver['password']);
    if (isset($backupjob['encryption']) && $backupjob['encryption'] == 'AES-256') {
        echo 'Decrypting file with AES-256'.PHP_EOL;
        $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
        $cipher->setKey($backupjob['encryptionkey']);
        file_put_contents($config['path'] . '/files/' . $argv[1].'.decrypted', $cipher->decrypt(file_get_contents($config['path'] . '/files/' . $argv[1])));
        echo 'Restoring MySQL Backup'.PHP_EOL;
        echo $db->exec(file_get_contents($config['path'] . '/files/' . $argv[1] . '.decrypted'));
        unlink($config['path'] . '/files/' . $argv[1] . '.decrypted');
    }
    else {
        echo 'Restoring MySQL Backup'.PHP_EOL;
        echo $db->exec(file_get_contents($config['path'] . '/files/' . $argv[1]));
    }
} elseif ($backupjob['type'] == 'openvz') {
    set_include_path($config['path'] . '/libs/phpseclib');
    include('Net/SSH2.php');
    include('Net/SFTP.php');
    include('Crypt/RSA.php');
    $ssh  = new Net_SSH2($backupserver['host'], $backupserver['port']);
    $sftp = new Net_SFTP($backupserver['host'], $backupserver['port']);
    if ($backupserver['authtype'] == 'password') {
        if (!$ssh->login($backupserver['username'], $backupserver['password'])) {
            die('SSH password login failed');
        }
        if (!$sftp->login($backupserver['username'], $backupserver['password'])) {
            die('SFTP password login failed');
        }
    } elseif ($backupserver['authtype'] == 'key') {
        $serverkey = explode(' ', $backupserver['password']);
        $key       = new Crypt_RSA();
        if (isset($serverkey[1])) {
            $key->setPassword($serverkey[1]);
        }
        $key->loadKey(file_get_contents($serverkey[0]));
        if (!$ssh->login($backupserver['username'], $key)) {
            die('SSH key login failed');
        }
        if (!$sftp->login($backupserver['username'], $key)) {
            die('SFTP key login failed');
        }
    } else {
        die('SSH login failed');
    }
    $verifyvzdump = $ssh->exec(escapeshellcmd('vzdump'));
    if (strpos($verifyvzdump,'command not found') !== false) {
        echo 'vzdump command not found' . PHP_EOL;
        die();
    }
    else {
        echo 'vzdump detected' . PHP_EOL;
    }
    echo $sftp->chdir('/tmp');
    if (isset($backupjob['encryption']) && $backupjob['encryption'] == 'AES-256') {
        echo 'Decrypting file with AES-256'.PHP_EOL;
        $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
        $cipher->setKey($backupjob['encryptionkey']);
        file_put_contents($config['path'] . '/files/' . $argv[1].'.decrypted', $cipher->decrypt(file_get_contents($config['path'] . '/files/' . $argv[1])));
        echo 'Transferring the file'.PHP_EOL;
        echo $sftp->put($argv[1], $config['path'] . '/files/' . $argv[1].'.decrypted', NET_SFTP_LOCAL_FILE);
        unlink($config['path'] . '/files/' . $argv[1] . '.decrypted');
    }
    else {
        echo 'Transferring the file'.PHP_EOL;
        echo $sftp->put($argv[1], $config['path'] . '/files/' . $argv[1], NET_SFTP_LOCAL_FILE);
    }
    $ctid = explode('vzdump-', trim($argv[1]));
    $ctid = explode('.tgz', $ctid[1]);
    echo $ssh->exec(escapeshellcmd('vzctl stop ' . $ctid[0]));
    echo $ssh->exec(escapeshellcmd('vzctl destroy ' . $ctid[0]));
    if (strpos($verifyproxmox, 'pve') !== false) {
        echo 'ProxMox detected, using vzrestore'.PHP_EOL;
        echo $ssh->exec(escapeshellcmd('vzrestore ./' . $argv[1] . ' ' . $ctid[0]));
    } else {
        echo 'Standard OpenVZ detected, using vzdump restore'.PHP_EOL;
        echo $ssh->exec(escapeshellcmd('vzdump --restore ./' . $argv[1] . ' ' . $ctid[0]));
    }
    echo $ssh->exec(escapeshellcmd('vzctl start ' . $ctid[0]));
    echo $ssh->exec(escapeshellcmd('rm -f ./' . $argv[1]));
} elseif ($backupjob['type'] == 'cpanel') {
    echo 'CDP.me does not support automatic cPanel backup restores at this moment, however you may download and restore the backup manually.'.PHP_EOL;
} else {
    die('Backup type not found');
}
echo 'Success! Backup restored.'.PHP_EOL;
logevent('User restored backup', 'activity');

?>
