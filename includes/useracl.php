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
checkacl('apaccess');
$aclarray = array(
    //in format of slug => text
    "Dashboard" => array(
        "backupjobs" => "Backup Jobs",
        "servers" => "Servers",
        "backups" => "Backups",
        "disk" => "Disk",
        "loadavg" => "Load Average",
        "alog" => "Activity Log",
        "blog" => "Backup Log"
    ),
    "Backup Jobs" => array(
        "bjpaccess" => "Page Access",
        "addjob" => "Add Backup Job",
        "viewbackup" => "View Backup",
        "backnow" => "Backup Now",
        "deljob" => "Backup Job Delete",
        "downloadb" => "Download Backup",
        "restoreb" => "Restore Backup",
        "deleteb" => "Backup Delete"
    ),
    "Servers" => array(
        "spaccess" => "Page Access",
        "addserver" => "Add Server",
        "delserver" => "Delete Server"
    ),
    "Users" => array(
        "upaccess" => "Page Access",
        "adduser" => "Add User",
        "edituser" => "Edit User",
        "deluser" => "Delete User"
    ),
    "User ACLs" => array(
        "apaccess" => "Page Access",
        "addacl" => "Add ACL",
        "editacl" => "Edit ACL",
        "delacl" => "Delete ACL"
    )
);

$acls = json_decode(file_get_contents($config['path'] . '/db/db-acl.json'), true);
if (isset($_REQUEST['acl'])) {
    if ($_REQUEST['acl'] == 'add' && isset($_REQUEST['perms']) && isset($_REQUEST['name']) && is_array($_REQUEST['perms'])) {
        checkacl('addacl');
        $acls[count($acls)] = array(
            'id' => count($acls) + 1,
            'perms' => $_REQUEST['perms'],
            'name' => trim($_REQUEST['name'])
        );

        file_put_contents($config['path'] . '/db/db-acl.json', json_encode($acls));
        logevent('User ' . $_SESSION['user'] . ' added ACL', 'activity');
        header('Location: index.php?action=useracl');
    } elseif ($_REQUEST['acl'] == 'edit' && isset($_REQUEST['perms']) && isset($_REQUEST['name']) && isset($_REQUEST['aclid']) && is_array($_REQUEST['perms'])) {
        checkacl('editacl');
        foreach ($acls as $aclkey => $acl) {
            if ($acl['id'] == $_REQUEST['aclid']) {
                $acls[$aclkey] = array(
                    'id' => $_REQUEST['aclid'],
                    'perms' => $_REQUEST['perms'],
                    'name' => trim($_REQUEST['name'])
                );
            }
        }
        file_put_contents($config['path'] . '/db/db-acl.json', json_encode($acls));
        logevent('User ' . $_SESSION['user'] . ' edited ACL ' . $_REQUEST['aclid'], 'activity');
        header('Location: index.php?action=useracl');
    } elseif ($_REQUEST['acl'] == 'remove' && isset($_REQUEST['id'])) {
        checkacl('delacl');
        foreach ($acls as $aclkey => $acl) {
            if ($acl['id'] == $_REQUEST['id']) {
                unset($acls[$aclkey]);
            }
        }
        file_put_contents($config['path'] . '/db/db-acl.json', json_encode($acls));
        logevent('User ' . $_SESSION['user'] . ' removed ACL ' . $_REQUEST['id'], 'activity');
        header('Location: index.php?action=useracl');
    }
} else {
    $smarty->assign('acls',$acls);
    $smarty->assign('aclarray',$aclarray);
    if (isset($_REQUEST['id']) && is_array($acls)) {
        foreach ($acls as $acl) {
            if ($acl['id'] == $_REQUEST['id']) {
                $smarty->assign('acldetails',$acl);
            }
        }
    }
    $smarty->display($config['path'].'/templates/header.tpl');
    $smarty->display($config['path'].'/templates/useracl.tpl');
    $smarty->display($config['path'].'/templates/footer.tpl');
}

?>