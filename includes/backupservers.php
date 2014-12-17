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
checkacl('spaccess');
$backupservers = json_decode(file_get_contents($config['path'] . '/db/db-backupservers.json'), true);
if (isset($_REQUEST['backupserver'])) {
    if ($_REQUEST['backupserver'] == 'add' && isset($_REQUEST['host']) && isset($_REQUEST['port']) && isset($_REQUEST['authtype']) && isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
        checkacl('addserver');
        $id                                   = md5(rand() . time() . $_REQUEST['host']);
        $backupservers[count($backupservers)] = array(
            'id' => $id,
            'host' => trim($_REQUEST['host']),
            'port' => trim($_REQUEST['port']),
            'authtype' => $_REQUEST['authtype'],
            'username' => $_REQUEST['username'],
            'password' => $_REQUEST['password']
        );
        file_put_contents($config['path'] . '/db/db-backupservers.json', json_encode($backupservers));
        logevent('User ' . $_SESSION['user'] . ' added server', 'activity');
        header('Location: index.php?action=backupservers');
    } elseif ($_REQUEST['backupserver'] == 'remove' && isset($_REQUEST['id'])) {
        checkacl('delserver');
        foreach ($backupservers as $key => $backupserver) {
            if ($backupserver['id'] == $_REQUEST['id']) {
                unset($backupservers[$key]);
            }
        }
        file_put_contents($config['path'] . '/db/db-backupservers.json', json_encode($backupservers));
        logevent('User ' . $_SESSION['user'] . ' removed server', 'activity');
        header('Location: index.php?action=backupservers');
    }
} else {
    $smarty->assign('backupservers',$backupservers);
    $smarty->display($config['path'].'/templates/header.tpl');
    $smarty->display($config['path'].'/templates/backupservers.tpl');
    $smarty->display($config['path'].'/templates/footer.tpl');
}

?>