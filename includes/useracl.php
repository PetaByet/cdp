<?php

if (constant('FILEACCESS')) {
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
    
    $acls = json_decode(file_get_contents($config['path'] . '/includes/db-acl.json'), true);
    if (isset($_REQUEST['acl'])) {
        if ($_REQUEST['acl'] == 'add' && isset($_REQUEST['perms']) && isset($_REQUEST['name']) && is_array($_REQUEST['perms'])) {
            checkacl('addacl');
            $acls[count($acls)] = array(
                'id' => count($acls) + 1,
                'perms' => $_REQUEST['perms'],
                'name' => trim($_REQUEST['name'])
            );
            
            file_put_contents($config['path'] . '/includes/db-acl.json', json_encode($acls));
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
            file_put_contents($config['path'] . '/includes/db-acl.json', json_encode($acls));
            logevent('User ' . $_SESSION['user'] . ' edited ACL ' . $_REQUEST['aclid'], 'activity');
            header('Location: index.php?action=useracl');
        } elseif ($_REQUEST['acl'] == 'remove' && isset($_REQUEST['id'])) {
            checkacl('delacl');
            foreach ($acls as $aclkey => $acl) {
                if ($acl['id'] == $_REQUEST['id']) {
                    unset($acls[$aclkey]);
                }
            }
            file_put_contents($config['path'] . '/includes/db-acl.json', json_encode($acls));
            logevent('User ' . $_SESSION['user'] . ' removed ACL ' . $_REQUEST['id'], 'activity');
            header('Location: index.php?action=useracl');
        }
    } else {
        include($config['path'] . '/includes/header.php');
?>

<div class="container">
	<h2 class="text-center">User ACLs</h2>
    <table class="table table-striped table-bordered">
        <tr><th>ID</th><th>Name</th><th>Actions</th></tr>
    <?php
        if (is_array($acls)) {
            foreach ($acls as $acl) {
                echo '<tr><td>' . $acl['id'] . '</td>';
                echo '<td>' . $acl['name'] . '</td>';
                echo '<td><a href="index.php?action=useracl&id=' . $acl['id'] . '" class="btn btn-info">Edit</a> <a href="index.php?action=useracl&users=remove&id=' . $acl['id'] . '" class="btn btn-danger">Delete</a></td></tr>';
            }
        }
?>
    </table>
    <?php
        if (isset($_REQUEST['id']) && is_array($acl)) {
            foreach ($acls as $acl) {
                if ($acl['id'] == $_REQUEST['id']) {
                    $acldetails = $acl;
                }
            }
        }
        if (isset($acldetails) && is_array($acldetails)) {
?>
    <h3>Edit ACL</h3>
    <form class="form-horizontal" role="form" method="post" action="index.php">
        <input type="hidden" name="action" value="useracl">
        <input type="hidden" name="acl" value="edit">
        <input type="hidden" name="aclid" value="<?php
            echo $_REQUEST['id'];
?>">
        <?php
            foreach ($aclarray as $groupkey => $aclgroup) {
                echo '<div class="panel panel-default"><div class="panel-heading">';
                echo '<h4 class="panel-title">' . $groupkey . '</h4></div><div class="panel-body">';
                foreach ($acls as $acl) {
                    if ($acl['id'] == $_REQUEST['id']) {
                        $acldetails = $acl;
                    }
                }
                foreach ($aclgroup as $itemkey => $aclitem) {
                    echo '<div class="col-md-2">' . $aclitem . '</div><div class="col-md-1"><select name="perms[' . $itemkey . ']">';
                    if ($acldetails['perms'][$itemkey] == 'true') {
                        echo '<option value="true" selected>True</option><option value="false">False</option></select></div>';
                    } else {
                        echo '<option value="true">True</option><option value="false" selected>False</option></select></div>';
                    }
                }
                echo '</div></div>';
            }
?>
        <div class="form-group">
            <label class="col-sm-2 control-label">ACL Name</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="name" value="<?php
            echo $acldetails['name'];
?>" required>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default">Submit</button>
            </div>
        </div>
    </form>
    <?php
        } else {
?>
    <h3>Add ACL</h3>
    <form class="form-horizontal" role="form" method="post" action="index.php">
        <input type="hidden" name="action" value="useracl">
        <input type="hidden" name="acl" value="add">
        <?php
            foreach ($aclarray as $groupkey => $aclgroup) {
                echo '<div class="panel panel-default"><div class="panel-heading">';
                echo '<h4 class="panel-title">' . $groupkey . '</h4></div><div class="panel-body">';
                foreach ($aclgroup as $itemkey => $aclitem) {
                    echo '<div class="col-md-2">' . $aclitem . '</div><div class="col-md-1"><select name="perms[' . $itemkey . ']">';
                    echo '<option value="true">True</option><option value="false">False</option></select></div>';
                }
                echo '</div></div>';
            }
?>
        <div class="form-group">
            <label class="col-sm-2 control-label">ACL Name</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="name" placeholder="Users" required>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default">Submit</button>
            </div>
        </div>
    </form>
    <?php
        }
?>
</div>
<?php
        include($config['path'] . '/includes/footer.php');
    }
}

?>