<?php

if (constant('FILEACCESS')) {
    
    $backupservers = json_decode(file_get_contents(getcwd() . '/includes/db-backupservers.json'), true);
    if (isset($_REQUEST['backupserver'])) {
        if ($_REQUEST['backupserver'] == 'add' && isset($_REQUEST['host']) && isset($_REQUEST['port']) && isset($_REQUEST['authtype']) && isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
            $id                                   = md5(rand() . time() . $_REQUEST['host']);
            $backupservers[count($backupservers)] = array(
                'id' => $id,
                'host' => $_REQUEST['host'],
                'port' => $_REQUEST['port'],
                'authtype' => $_REQUEST['authtype'],
                'username' => $_REQUEST['username'],
                'password' => $_REQUEST['password']
            );
            file_put_contents(getcwd() . '/includes/db-backupservers.json', json_encode($backupservers));
            header('Location: index.php?action=backupservers');
        } elseif ($_REQUEST['backupserver'] == 'remove' && isset($_REQUEST['id'])) {
            foreach ($backupservers as $key => $backupserver) {
                if ($backupserver['id'] == $_REQUEST['id']) {
                    unset($backupservers[$key]);
                }
            }
            file_put_contents(getcwd() . '/includes/db-backupservers.json', json_encode($backupservers));
            header('Location: index.php?action=backupservers');
        }
    } else {
        include('header.php');
?>
<div class="container">
	<h2 class="text-center">Servers</h2>
	<table class="table table-striped table-bordered">
		<tr><th>Host</th><th>Port</th><th>SSH Authentication</th><th>Username</th><th>Action</th></tr>
<?php
        if (is_array($backupservers)) {
            foreach ($backupservers as $backupserver) {
                echo '<tr><td>' . $backupserver['host'] . '</td>';
                echo '<td>' . $backupserver['port'] . '</td>';
                echo '<td>' . $backupserver['authtype'] . '</td>';
                echo '<td>' . $backupserver['username'] . '</td>';
                echo '<td><a href="index.php?action=backupservers&backupserver=remove&id=' . $backupserver['id'] . '" class="btn btn-danger">Delete</a></td></tr>';
            }
        }
?>
	</table>
    <form class="form-horizontal" role="form" method="post" action="index.php">
        <input type="hidden" name="action" value="backupservers">
        <input type="hidden" name="backupserver" value="add">
        <div class="form-group">
            <label for="inputUsername3" class="col-sm-2 control-label">Host</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="host" id="inputUsername3" placeholder="192.168.1.1" required>
            </div>
        </div>
        <div class="form-group">
            <label for="inputUsername3" class="col-sm-2 control-label">SSH Port</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="port" id="inputUsername3" placeholder="22" required>
            </div>
        </div>
        <div class="form-group">
            <label for="inputUsername3" class="col-sm-2 control-label">SSH Authentication</label>
            <div class="col-sm-10">
                <input type="radio" name="authtype" value="password" checked> Password
                <input type="radio" name="authtype" value="key"> SSH Key
            </div>
        </div>
	<div class="form-group">
            <label for="inputUsername3" class="col-sm-2 control-label">SSH Username</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="username" id="inputUsername3" placeholder="root" required>
            </div>
        </div>
        <div class="form-group">
            <label for="inputPassword3" class="col-sm-2 control-label">Password or Key Path</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="password" id="inputPassword3" placeholder="ssh password or path to ssh key" required>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default">Add Server</button>
            </div>
        </div>
    </form>
</div>
<?php
        include('footer.php');
    }
}

?>