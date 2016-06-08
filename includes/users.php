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
checkacl('upaccess');
$users = json_decode(file_get_contents($config['path'] . '/db/db-users.json'), true);
$acls  = json_decode(file_get_contents($config['path'] . '/db/db-acl.json'), true);
if (isset($_REQUEST['users'])) {
    if ($_REQUEST['users'] == '2focreatekey') {
        require($config['path'] . '/libs/googleauthenticator/GoogleAuthenticator.php');
        $ga = new PHPGangsta_GoogleAuthenticator();
        echo $ga->createSecret();
        die();
    } elseif ($_REQUEST['users'] == 'generateqr' && isset($_REQUEST['generateqr'])) {
        require($config['path'] . '/libs/googleauthenticator/GoogleAuthenticator.php');
        $ga = new PHPGangsta_GoogleAuthenticator();
        echo '<img src="'.$ga->getQRCodeGoogleUrl('CDP.me', $_REQUEST['generateqr']).'" alt="QR Code">';
        die();
    } elseif ($_REQUEST['users'] == 'add' && isset($_REQUEST['username']) && isset($_REQUEST['password']) && isset($_REQUEST['acl'])) {
        checkacl('adduser');
        if (!isset($_REQUEST['2fokey'])) {
            $_REQUEST['2fokey'] = null;
        }
        $users[count($users)] = array(
            'id' => count($users) + 1,
            'username' => trim($_REQUEST['username']),
            'password' => md5($_REQUEST['password']),
            'acl' => $_REQUEST['acl'],
            '2fo' => $_REQUEST['2fo'],
            '2fokey' => $_REQUEST['2fokey']
        );
        file_put_contents($config['path'] . '/db/db-users.json', json_encode($users));
        logevent('User ' . $_SESSION['user'] . ' added user ' . $_REQUEST['username'], 'activity');
        header('Location: index.php?action=users');
    } elseif ($_REQUEST['users'] == 'edit' && isset($_REQUEST['username']) && isset($_REQUEST['userid']) && isset($_REQUEST['acl'])) {
        checkacl('edituser');
        foreach ($users as $userkey => $user) {
            if ($user['id'] == $_REQUEST['userid']) {
                if (!isset($_REQUEST['2fokey'])) {
                    $_REQUEST['2fokey'] = null;
                }
                $users[$userkey]['username'] = $_REQUEST['username'];
                $users[$userkey]['acl']      = $_REQUEST['acl'];
                $users[$userkey]['2fo']      = $_REQUEST['2fo'];
                $users[$userkey]['2fokey']   = $_REQUEST['2fokey'];
                if (isset($_REQUEST['password']) && !empty($_REQUEST['password'])) {
                    $users[$userkey]['password'] = md5($_REQUEST['password']);
                }
                else $users[$userkey]['password'] = $user['password'];
            }
        }
        file_put_contents($config['path'] . '/db/db-users.json', json_encode($users));
        logevent('User ' . $_SESSION['user'] . ' edited user ' . $_REQUEST['username'], 'activity');
        header('Location: index.php?action=users');
    } elseif ($_REQUEST['users'] == 'remove' && isset($_REQUEST['id'])) {
        checkacl('deluser');
        foreach ($users as $userkey => $user) {
            if ($user['id'] == $_REQUEST['id']) {
                unset($users[$userkey]);
            }
        }
        file_put_contents($config['path'] . '/db/db-users.json', json_encode($users));
        logevent('User ' . $_SESSION['user'] . ' removed user', 'activity');
        header('Location: index.php?action=users');
    }
} else {
    $smarty->assign('users',$users);
    $smarty->assign('acls',$acls);
    if (isset($_REQUEST['id']) && is_array($users)) {
        foreach ($users as $user) {
            if ($user['id'] == $_REQUEST['id']) {
                $smarty->assign('userdetails',$user);
            }
        }
    }
    $smarty->display($config['path'].'/templates/header.tpl');
    $smarty->display($config['path'].'/templates/users.tpl');
    $smarty->display($config['path'].'/templates/footer.tpl');
}

?>
