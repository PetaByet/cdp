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

$backups       = json_decode(file_get_contents($config['path'] . '/includes/db-backups.json'), true);
$backupjobs    = json_decode(file_get_contents($config['path'] . '/includes/db-backupjobs.json'), true);
$backupservers = json_decode(file_get_contents($config['path'] . '/includes/db-backupservers.json'), true);

function exitcron()
{
    global $log;
    global $config;
    if ($config['sendnotification'] && filter_var($config['adminemail'], FILTER_VALIDATE_EMAIL)) {
        mail($config['adminemail'], 'CDP.me backup report', wordwrap($log, 70));
    } else {
        echo $log;
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

if ($backupjob['type'] == 'full' || $backupjob['type'] == 'incremental') {
    
    $log .= 'Starting file backup' . PHP_EOL;
    set_include_path($config['path'] . '/phpseclib');
    
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
        $incrementalbackups     = json_decode(file_get_contents($config['path'] . '/includes/db-backups.json'), true);
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
    
    $backups[count($backups)] = array(
        'id' => $backupjob['id'],
        'file' => $dirname . '.tar.gz',
        'size' => filesize($config['path'] . '/files/' . $dirname . '.tar.gz'),
        'time' => $starttime
    );
    
    file_put_contents($config['path'] . '/includes/db-backups.json', json_encode($backups));
} elseif ($backupjob['type'] == 'mysql') {
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
        $log .= 'MySQL error: ' . mysql_error() . PHP_EOL;
        exitcron();
    }
    
    if ($tables == '*') {
        $tables = array();
        $result = mysql_query('SHOW TABLES');
        if (!$result) {
            $log .= 'MySQL query error: ' . mysql_error() . PHP_EOL;
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
            $log .= 'MySQL query error: ' . mysql_error() . PHP_EOL;
            exitcron();
        }
        $num_fields = mysql_num_fields($result);
        
        $return = 'DROP TABLE ' . $table . ';';
        $row2   = mysql_query('SHOW CREATE TABLE ' . $table);
        if (!$row2) {
            $log .= 'MySQL query error: ' . mysql_error() . PHP_EOL;
            exitcron();
        }
        $row2 = mysql_fetch_row($row2);
        $return .= "\n\n" . $row2[1] . ";\n\n";
        
        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = mysql_fetch_row($result)) {
                $return .= 'INSERT INTO ' . $table . ' VALUES(';
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
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
    file_put_contents($config['path'] . '/files/' . $backupname, $return);
    
    $backups[count($backups)] = array(
        'id' => $backupjob['id'],
        'file' => $backupname,
        'size' => filesize($config['path'] . '/files/' . $backupname),
        'time' => $starttime
    );
    
    file_put_contents($config['path'] . '/includes/db-backups.json', json_encode($backups));
} else {
    $log .= 'Backup type not valid' . PHP_EOL;
    exitcron();
}

$timetaken = time() - $starttime;

$log .= 'Backup completed in ' . $timetaken . ' seconds.' . PHP_EOL;

if (isset($backupjob['expiry'])) {
    $backups = json_decode(file_get_contents($config['path'] . '/includes/db-backups.json'), true);
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
    file_put_contents($config['path'] . '/includes/db-backups.json', json_encode($backups));
}

exitcron();

?>