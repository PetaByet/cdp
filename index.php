<?php

session_start();

require('config.php');

define('FILEACCESS', true);

require($config['path'].'/libs/smarty/Smarty.class.php');
$smarty = new Smarty();
$smarty->setTemplateDir($config['path'].'/templates/');
$smarty->setCompileDir($config['path'].'/templates_c/');
$smarty->setConfigDir($config['path'].'/configs/');
$smarty->setCacheDir($config['path'].'/cache/');

$smarty->assign('version',$config['version']);
$smarty->assign('path',$config['path']);

if ($config['debug']) {
    echo '<pre>Debug info' . PHP_EOL;
    echo '$_REQUEST' . PHP_EOL;
    print_r($_REQUEST);
    echo '$_SESSION' . PHP_EOL;
    print_r($_SESSION);
    echo '</pre>';
    $smarty->debugging = true;
}

function checkacl($acl, $noredirect = false)
{
    if ($_SESSION['acl'][$acl] != 'true') {
        if (isset($noredirect) && $noredirect) {
            return false;
        } else {
            header('Location: index.php?action=accessdenied');
            die();
        }
    } else {
        return true;
    }
}

function is_md5($md5)
{
    if (isset($md5) && preg_match('/^[a-f0-9]{32}$/', $md5)) {
        return true;
    }
    return false;
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

if (!isset($_SESSION['user']) || !isset($_SESSION['acl'])) {
    //login check
    $loggedin = false;
    $smarty->assign('loggedin', false);
    include($config['path'] . '/includes/login.php');
} else {
    if ($_SESSION['ip'] != $_SERVER['REMOTE_ADDR']) {
        //session ip check
        session_unset();
        session_destroy();
        header('Location: index.php');
    } elseif ($_SESSION['time'] > $config['logintimeout'] + time()) {
        //inactivity timeout check
        session_unset();
        session_destroy();
        header('Location: index.php');
    } else {
        //restart inactivity timer
        $_SESSION['time'] = time();
        $loggedin         = true;
        $smarty->assign('loggedin', true);
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'accessdenied') {
            $smarty->display($config['path'].'/templates/header.tpl');
            echo '<div class="container"><div class="alert alert-danger"><h1>Access Denied</h1><p>You are not authorized to access this page</p></div></div>';
            $smarty->display($config['path'].'/templates/footer.tpl');
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backupservers') {
            checkacl('spaccess');
            include($config['path'] . '/includes/backupservers.php');
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backupjobs') {
            checkacl('bjpaccess');
            include($config['path'] . '/includes/backupjobs.php');
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'viewbackups') {
            checkacl('viewbackup');
            include($config['path'] . '/includes/viewbackups.php');
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'users') {
            checkacl('upaccess');
            include($config['path'] . '/includes/users.php');
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'useracl') {
            checkacl('apaccess');
            include($config['path'] . '/includes/useracl.php');
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backupdownload' && isset($_REQUEST['id'])) {
            checkacl('downloadb');
            if (file_exists($config['path'] . '/files/' . $_REQUEST['id']) == 1) {
                header('Content-Disposition: attachment; filename="' . basename($_REQUEST['id']) . '"');
                readfile($config['path'] . '/files/' . $_REQUEST['id']);
                logevent('User ' . $_SESSION['user'] . ' downloaded ' . $_REQUEST['id'], 'activity');
            } else {
                echo 'File not found';
            }
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backupdownloaddecrypt' && isset($_REQUEST['id'])) {
            checkacl('downloadb');
            if (file_exists($config['path'] . '/files/' . $_REQUEST['id']) == 1) {
                $backups       = json_decode(file_get_contents($config['path'] . '/db/db-backups.json'), true);
                $backupjobs    = json_decode(file_get_contents($config['path'] . '/db/db-backupjobs.json'), true);
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
                $backup       = GetBackupDetails($_REQUEST['id']);
                $backupjob    = GetJobDetails($backup['id']);
                header('Content-Disposition: attachment; filename="' . basename($_REQUEST['id']) . '"');
                set_include_path($config['path'] . '/libs/phpseclib');
                include('Crypt/AES.php');
                $cipher = new Crypt_AES(CRYPT_AES_MODE_ECB);
                $cipher->setKey($backupjob['encryptionkey']);
                echo $cipher->decrypt(file_get_contents($config['path'] . '/files/' . $_REQUEST['id']));
                logevent('User ' . $_SESSION['user'] . ' downloaded decrypted backup ' . $_REQUEST['id'], 'activity');
            } else {
                echo 'File not found';
            }
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backupdelete' && isset($_REQUEST['id'])) {
            checkacl('deleteb');
            if (file_exists($config['path'] . '/files/' . $_REQUEST['id'])) {
                if (unlink($config['path'] . '/files/' . $_REQUEST['id'])) {
                    $backups = json_decode(file_get_contents($config['path'] . '/db/db-backups.json'), true);
                    foreach ($backups as $key => $backup) {
                        if ($backup['file'] == $_REQUEST['id']) {
                            unset($backups[$key]);
                        }
                    }
                    file_put_contents($config['path'] . '/db/db-backups.json', json_encode($backups));
                    logevent('User ' . $_SESSION['user'] . ' deleted backup', 'activity');
                    header('Location: index.php?action=backupjobs');
                } else {
                    echo 'Unable to delete file';
                }
            } else {
                echo 'File does not exist';
            }
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backuprestore') {
            checkacl('restoreb');
            include($config['path'] . '/includes/backuprestore.php');
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'logout') {
            session_unset();
            session_destroy();
            logevent('User ' . $_SESSION['user'] . ' logged out', 'activity');
            header('Location: index.php');
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'runbackup' && isset($_REQUEST['id']) && is_md5($_REQUEST['id'])) {
            checkacl('backnow');
            logevent('User ' . $_SESSION['user'] . ' ran backup job manually', 'activity');
            //making sure backup job is not terminated
            ignore_user_abort(true);
            set_time_limit(0);
            echo 'Backup task has been started, please do not close this window <pre>';
            echo shell_exec(escapeshellcmd('php ' . $config['path'] . '/cron.php ' . $_REQUEST['id']));
            echo '</pre>';
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'activitylogs') {
            checkacl('alog');
            $smarty->display($config['path'].'/templates/header.tpl');
            echo '<h4>Activity Logs</h4>';
            $activitylogs = json_decode(file_get_contents($config['path'] . '/db/db-activitylog.json'), true);
            $activitylogs = array_reverse($activitylogs);
            echo '<table class="table table-bordered table-striped">';
            echo '<tr><th>Data</th><th>Time</th><th>IP</th></tr>';
            foreach ($activitylogs as $i => $log) {
                if (isset($activitylogs[$i]) && is_array($activitylogs[$i])) {
                    echo '<tr><td>';
                    echo $activitylogs[$i]['data'];
                    echo'</td><td>' . date("Y-m-d H:i:s", $activitylogs[$i]['time']) . '</td><td>' . $activitylogs[$i]['ip'] . '</td></tr>';
                } else {
                    echo '<tr><td>-</td><td>-</td><td>-</td></tr>';
                }
            }
            echo '</table>';
            $smarty->display($config['path'].'/templates/footer.tpl');
        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backuplogs') {
            checkacl('blog');
            $smarty->display($config['path'].'/templates/header.tpl');
            echo '<h4>Backup Logs</h4>';
            $backuplogs = json_decode(file_get_contents($config['path'] . '/db/db-backuplog.json'), true);
            $backuplogs = array_reverse($backuplogs);
            echo '<table class="table table-bordered table-striped">';
            echo '<tr><th>Data</th><th>Time</th><th>IP</th></tr>';
            foreach ($backuplogs as $i => $log) {
                if (isset($backuplogs[$i]) && is_array($backuplogs[$i])) {
                    echo '<tr><td>' . $backuplogs[$i]['data'] . '</td><td>' . date("Y-m-d H:i:s", $backuplogs[$i]['time']) . '</td><td>' . $backuplogs[$i]['ip'] . '</td></tr>';
                } else {
                    echo '<tr><td>-</td><td>-</td><td>-</td></tr>';
                }
            }
            echo '</table>';
            $smarty->display($config['path'].'/templates/footer.tpl');
        } else {
            include($config['path'] . '/includes/home.php');
        }
    }
}
?>