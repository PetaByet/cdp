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

$backups       = json_decode(file_get_contents('/var/www/html/includes/db-backups.json'), true);
$backupjobs    = json_decode(file_get_contents('/var/www/html/includes/db-backupjobs.json'), true);
$backupservers = json_decode(file_get_contents('/var/www/html/includes/db-backupservers.json'), true);

function exitcron()
{
    global $log;
    global $config;
    if ($config['sendnotification'] && filter_var($config['adminemail'], FILTER_VALIDATE_EMAIL)) {
        mail($config['adminemail'], 'CDP.me backup report', wordwrap($log, 70));
    }
    die();
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

set_include_path('/var/www/html/phpseclib');

include('Net/SSH2.php');
include('Net/SFTP.php');
include('Crypt/RSA.php');
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
    $key = new Crypt_RSA();
    $key->loadKey(file_get_contents($backupserver['password']));
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
$log .= $ssh->exec('mkdir /tmp/' . $dirname) . PHP_EOL;
$log .= $ssh->exec('tar -zcvf ' . $dirname . '.tar.gz ' . $backupjob['directory']) . PHP_EOL;
$log .= $ssh->exec('mv ' . $dirname . '.tar.gz /tmp/' . $dirname) . PHP_EOL;
$log .= $sftp->chdir('/tmp/' . $dirname) . PHP_EOL;
$sftpfiletransfer = $sftp->get($dirname . '.tar.gz', $dirname . '.tar.gz') . PHP_EOL;
if (!$sftpfiletransfer) {
    $log .= 'File transfer failed' . PHP_EOL;
    exitcron();
} else {
    $log .= $sftpfiletransfer;
}
$log .= $ssh->exec('rm -rf /tmp/' . $dirname) . PHP_EOL;
$log .= rename($dirname . '.tar.gz', '/var/www/html/files/' . $dirname . '.tar.gz') . PHP_EOL;

$timetaken = time() - $starttime;

$log .= 'Backup completed in ' . $timetaken . ' seconds.' . PHP_EOL;

$backups[count($backups)] = array(
    'id' => $backupjob['id'],
    'file' => $dirname . '.tar.gz',
    'size' => filesize('/var/www/html/files/' . $dirname . '.tar.gz'),
    'time' => $starttime
);

file_put_contents('/var/www/html/includes/db-backups.json', json_encode($backups));

exitcron();

?>