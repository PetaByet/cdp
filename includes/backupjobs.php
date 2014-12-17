<?php

//prevent direct file access
if (phpversion() >= 5) {
    if (count(get_included_files()) == 1) {
        die();
    }
}
else {
    if (count(get_included_files()) == 0) {
        die();
    }
}
if (!defined('FILEACCESS')) {
    die();
}
checkacl('bjpaccess');
$backupjobs    = json_decode(file_get_contents($config['path'] . '/db/db-backupjobs.json'), true);
$backupservers = json_decode(file_get_contents($config['path'] . '/db/db-backupservers.json'), true);
if (isset($_REQUEST['backupjob'])) {
    if ($_REQUEST['backupjob'] == 'add' && isset($_REQUEST['source']) && isset($_REQUEST['directory']) && isset($_REQUEST['expiry']) && isset($_REQUEST['encryption'])) {
        checkacl('addjob');
        $id                             = md5(rand() . time() . $_REQUEST['source']);
        if (!isset($_REQUEST['encryptionkey'])) {
            $_REQUEST['encryptionkey'] = null;
        }
        $backupjobs[count($backupjobs)] = array(
            'id' => $id,
            'source' => $_REQUEST['source'],
            'directory' => $_REQUEST['directory'],
            'expiry' => $_REQUEST['expiry'],
            'encryption' => $_REQUEST['encryption'],
            'encryptionkey' => $_REQUEST['encryptionkey'],
            'type' => $_REQUEST['type']
        );
        file_put_contents($config['path'] . '/db/db-backupjobs.json', json_encode($backupjobs));
        logevent('User ' . $_SESSION['user'] . ' added backup job', 'activity');
        header('Location: index.php?action=backupjobs&created=true&id=' . $id);
    } elseif ($_REQUEST['backupjob'] == 'remove' && isset($_REQUEST['id'])) {
        checkacl('deljob');
        foreach ($backupjobs as $key => $backupjob) {
            if ($backupjob['id'] == $_REQUEST['id']) {
                unset($backupjobs[$key]);
            }
        }
        file_put_contents($config['path'] . '/db/db-backupjobs.json', json_encode($backupjobs));
        logevent('User ' . $_SESSION['user'] . ' removed backup job', 'activity');
        header('Location: index.php?action=backupjobs');
    }
} else {
    $fileservers = array();
    $sqlservers  = array();
    $cpanelservers = array();
    foreach ($backupservers as $backupserver) {
        if ($backupserver['authtype'] == 'password' || $backupserver['authtype'] == 'key') {
            $fileservers[count($fileservers)] = $backupserver;
        } elseif ($backupserver['authtype'] == 'mysql') {
            $sqlservers[count($sqlservers)] = $backupserver;
        } elseif ($backupserver['authtype'] == 'cpanel') {
            $cpanelservers[count($cpanelservers)] = $backupserver;
        } 
    }
    $smarty->assign('backupjobs',$backupjobs);
    $smarty->assign('fileservers',$fileservers);
    $smarty->assign('sqlservers',$sqlservers);
    $smarty->assign('cpanelservers',$cpanelservers);
    $smarty->display($config['path'].'/templates/header.tpl');
    $smarty->display($config['path'].'/templates/backupjobs.tpl');
    $smarty->display($config['path'].'/templates/footer.tpl');
}

?>