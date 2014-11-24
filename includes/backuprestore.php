<?php

if (constant('FILEACCESS')) {
    checkacl('restoreb');
    echo '<pre>';
    //making sure backup restore job is not terminated
    ignore_user_abort(true);
    set_time_limit(0);
    echo 'Initiating backup restore...' . PHP_EOL;
    $backups       = json_decode(file_get_contents($config['path'] . '/includes/db-backups.json'), true);
    $backupjobs    = json_decode(file_get_contents($config['path'] . '/includes/db-backupjobs.json'), true);
    $backupservers = json_decode(file_get_contents($config['path'] . '/includes/db-backupservers.json'), true);
    
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
    $backup       = GetBackupDetails($_GET['id']);
    $backupjob    = GetJobDetails($backup['id']);
    $backupserver = GetServerDetails($backupjob['source']);
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
        echo $sftp->chdir('/');
        if (isset($backupjob['encryption']) && $backupjob['encryption'] = 'AES-256') {
            echo 'Decrypting file with AES-256'.PHP_EOL;
            $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
            $cipher->setKey($backupjob['encryptionkey']);
            file_put_contents($config['path'] . '/files/' . $_GET['id'].'.decrypted', $cipher->decrypt(file_get_contents($config['path'] . '/files/' . $_GET['id'])));
            echo 'Transferring the file'.PHP_EOL;
            echo $sftp->put($_GET['id'], config['path'] . '/files/' . $_GET['id'].'.decrypted', NET_SFTP_LOCAL_FILE);
            unlink($config['path'] . '/files/' . $_GET['id'] . '.decrypted');
        }
        else {
            echo 'Transferring the file'.PHP_EOL;
            echo $sftp->put($_GET['id'], $config['path'] . '/files/' . $_GET['id'], NET_SFTP_LOCAL_FILE);
        }
        echo $ssh->exec(escapeshellcmd('tar -zxvf /' . $_GET['id'] . ' -C /'));
        echo $ssh->exec(escapeshellcmd('rm -f /' . $_GET['id']));
    } elseif ($backupjob['type'] == 'mysql') {
        $database = explode(' ', $backupjob['directory']);
        $db       = new PDO('mysql:host=' . $backupserver['host'] . ';port=' . $backupserver['port'] . ';dbname=' . $database[0] . ';charset=utf8', $backupserver['username'], $backupserver['password']);
        if (isset($backupjob['encryption']) && $backupjob['encryption'] = 'AES-256') {
            echo 'Decrypting file with AES-256'.PHP_EOL;
            $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
            $cipher->setKey($backupjob['encryptionkey']);
            file_put_contents($config['path'] . '/files/' . $_GET['id'].'.decrypted', $cipher->decrypt(file_get_contents($config['path'] . '/files/' . $_GET['id'])));
            echo 'Restoring MySQL Backup'.PHP_EOL;
            echo $db->exec(file_get_contents($config['path'] . '/files/' . $_GET['id'] . '.decrypted'));
            unlink($config['path'] . '/files/' . $_GET['id'] . '.decrypted');
        }
        else {
            echo 'Restoring MySQL Backup'.PHP_EOL;
            echo $db->exec(file_get_contents($config['path'] . '/files/' . $_GET['id']));
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
        echo $sftp->chdir('/');
        if (isset($backupjob['encryption']) && $backupjob['encryption'] = 'AES-256') {
            echo 'Decrypting file with AES-256'.PHP_EOL;
            $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
            $cipher->setKey($backupjob['encryptionkey']);
            file_put_contents($config['path'] . '/files/' . $_GET['id'].'.decrypted', $cipher->decrypt(file_get_contents($config['path'] . '/files/' . $_GET['id'])));
            echo 'Transferring the file'.PHP_EOL;
            echo $sftp->put($_GET['id'], config['path'] . '/files/' . $_GET['id'].'.decrypted', NET_SFTP_LOCAL_FILE);
            unlink($config['path'] . '/files/' . $_GET['id'] . '.decrypted');
        }
        else {
            echo 'Transferring the file'.PHP_EOL;
            echo $sftp->put($_GET['id'], $config['path'] . '/files/' . $_GET['id'], NET_SFTP_LOCAL_FILE);
        }
        $ctid = explode('vzdump-', trim($_GET['id']));
        $ctid = explode('.tgz', $ctid[1]);
        echo $ssh->exec(escapeshellcmd('vzctl stop ' . $ctid[0]));
        echo $ssh->exec(escapeshellcmd('vzctl destroy ' . $ctid[0]));
        echo $ssh->exec(escapeshellcmd('vzdump --restore /' . $_GET['id'] . ' ' . $ctid[0]));
        echo $ssh->exec(escapeshellcmd('vzctl start ' . $ctid[0]));
        echo $ssh->exec(escapeshellcmd('rm -f /' . $_GET['id']));
    } elseif ($backupjob['type'] == 'cpanel') {
        echo 'CDP.me does not support automatic cPanel backup restores at this moment, however you may download and restore the backup manually.';
    } else {
        die('Backup type not found');
    }
    echo 'Backup restored';
    echo '</pre>';
    logevent('User ' . $_SESSION['user'] . ' restored backup', 'activity');
}

?>