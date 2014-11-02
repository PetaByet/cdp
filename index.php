<?php

session_start();

require('config.php');

define('FILEACCESS', true);

if (!isset($_SESSION['user']) || $_SESSION['user'] != $config['adminusername']) {
    $loggedin = false;
    include('includes/login.php');
} else {
    $loggedin = true;
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backupservers') {
        include('includes/backupservers.php');
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backupjobs') {
        include('includes/backupjobs.php');
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'viewbackups') {
        include('includes/viewbackups.php');
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backupdownload' && isset($_GET['id'])) {
        if (file_exists(getcwd() . '/files/' . $_GET['id']) == 1) {
            header('Content-Disposition: attachment; filename="' . basename($_GET['id']) . '"');
            readfile('/var/www/html/files/' . $_GET['id']);
        } else {
            echo 'file not found';
        }
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backupdelete' && isset($_GET['id'])) {
        if (file_exists(getcwd() . '/files/' . $_GET['id'])) {
            if (unlink(getcwd() . '/files/' . $_GET['id'])) {
                $backups = json_decode(file_get_contents('/var/www/html/includes/db-backups.json'), true);
                foreach ($backups as $key => $backup) {
                    if ($backup['file'] == $_GET['id']) {
                        unset($backups[$key]);
                    }
                }
                file_put_contents('/var/www/html/includes/db-backups.json', json_encode($backups));
                header('Location: index.php?action=backupjobs');
            }
        }
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'backuprestore' && isset($_GET['id'])) {
        include('includes/backuprestore.php');
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'logout') {
        session_unset();
        session_destroy();
        header('Location: index.php');
    } else {
        include('includes/home.php');
    }
}
?>