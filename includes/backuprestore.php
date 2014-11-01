<?php

if (constant('FILEACCESS')) {
    echo '<pre>';
    ignore_user_abort(true);
    set_time_limit(0);
    echo 'Initiating backup restore...' . PHP_EOL;
    $backups       = json_decode(file_get_contents('/var/www/html/includes/db-backups.json'), true);
    $backupjobs    = json_decode(file_get_contents('/var/www/html/includes/db-backupjobs.json'), true);
    $backupservers = json_decode(file_get_contents('/var/www/html/includes/db-backupservers.json'), true);
    
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
    set_include_path('/var/www/html/phpseclib');
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
        $key = new Crypt_RSA();
        $key->loadKey(file_get_contents($backupserver['password']));
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
    echo $sftp->put($_GET['id'], '/var/www/html/files/' . $_GET['id'], NET_SFTP_LOCAL_FILE);
    echo $ssh->exec('tar -zxvf /' . $_GET['id'] . ' -C /');
    echo $ssh->exec('rm -f /' . $_GET['id']);
    echo 'Backup restored';
    echo '</pre>';
}

?>