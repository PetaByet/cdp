<?php

if (constant('FILEACCESS')) {
    checkacl('bjpaccess');
    $backupjobs    = json_decode(file_get_contents($config['path'] . '/includes/db-backupjobs.json'), true);
    $backupservers = json_decode(file_get_contents($config['path'] . '/includes/db-backupservers.json'), true);
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
            file_put_contents($config['path'] . '/includes/db-backupjobs.json', json_encode($backupjobs));
            logevent('User ' . $_SESSION['user'] . ' added backup job', 'activity');
            header('Location: index.php?action=backupjobs&created=true&id=' . $id);
        } elseif ($_REQUEST['backupjob'] == 'remove' && isset($_REQUEST['id'])) {
            checkacl('deljob');
            foreach ($backupjobs as $key => $backupjob) {
                if ($backupjob['id'] == $_REQUEST['id']) {
                    unset($backupjobs[$key]);
                }
            }
            file_put_contents($config['path'] . '/includes/db-backupjobs.json', json_encode($backupjobs));
            logevent('User ' . $_SESSION['user'] . ' removed backup job', 'activity');
            header('Location: index.php?action=backupjobs');
        }
    } else {
        include($config['path'] . '/includes/header.php');
?>
<div class="container">
	<h2 class="text-center">Backup Jobs</h2>
	<?php
        if (isset($_GET['id']) && isset($_GET['created']) && $_GET['created']) {
            echo '<div class="alert alert-info">';
            echo 'Congratulations! Your backup job has been created. You may now add it to crontab to run it automatically. Here are some examples:<br>';
            echo 'A backup every 15 minutes: <code>*/15 * * * * php ' . $config['path'] . '/cron.php ' . $_GET['id'] . ' >/dev/null 2>&1</code><br>';
            echo 'Hourly backups: <code>0 * * * * php ' . $config['path'] . '/cron.php ' . $_GET['id'] . ' >/dev/null 2>&1</code><br>';
            echo 'Daily backups at 3am: <code>0 3 * * * php ' . $config['path'] . '/cron.php ' . $_GET['id'] . ' >/dev/null 2>&1</code><br>';
            echo 'For more information about how crontab works, please use <a href="http://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/">this guide</a>.<br>';
            echo 'And here is the command itself: <input type="text" value="php ' . $config['path'] . '/cron.php ' . $_GET['id'] . '">';
            echo '</div>';
        }
?>
	<table class="table table-striped table-bordered">
		<tr><th>Source</th><th>Dir / DB / Excl.CT</th><th>ID</th><th>Type</th><th>Backup Auto-Delete</th><th>Encryption</th><th>Actions</th></tr>
<?php
        if (is_array($backupjobs)) {
            foreach ($backupjobs as $backupjob) {
                echo '<tr><td>' . $backupjob['source'] . '</td>';
                echo '<td>' . $backupjob['directory'] . '</td>';
                echo '<td>' . $backupjob['id'] . '</td>';
                echo '<td>' . $backupjob['type'] . '</td>';
                echo '<td>' . $backupjob['expiry'] . ' Days</td>';
                if (isset($backupjob['encryption'])) {
                    echo '<td>' . $backupjob['encryption'] . '</td>';
                } else {
                    echo '<td>No Encryption</td>';
                }
                echo '<td><a href="index.php?action=viewbackups&id=' . $backupjob['id'] . '" class="btn btn-info">View Backups</a> <a href="index.php?action=runbackup&id=' . $backupjob['id'] . '" class="btn btn-success">Backup Now</a> <a href="index.php?action=backupjobs&backupjob=remove&id=' . $backupjob['id'] . '" class="btn btn-danger">Delete</a></td></tr>';
            }
        }
?>
	</table>
    <div role="tabpanel">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#full" aria-controls="full" role="tab" data-toggle="tab">Full</a></li>
            <li role="presentation"><a href="#incremental" aria-controls="incremental" role="tab" data-toggle="tab">Incremental</a></li>
            <li role="presentation"><a href="#mysql" aria-controls="mysql" role="tab" data-toggle="tab">MySQL</a></li>
            <li role="presentation"><a href="#openvz" aria-controls="openvz" role="tab" data-toggle="tab">OpenVZ</a></li>
            <li role="presentation"><a href="#cpanel" aria-controls="cpanel" role="tab" data-toggle="tab">cPanel</a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade in active" id="full">
               <h3>Add full backup job</h3>
                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <input type="hidden" name="action" value="backupjobs">
                    <input type="hidden" name="backupjob" value="add">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Source</label>
                        <div class="col-sm-10">
            <?php
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
        if (count($fileservers) == 0) {
            echo '<div class="alert alert-warning">Please add a file server first.</div>';
        } else {
            echo '<select name="source">';
            foreach ($fileservers as $backupserver) {
                echo '<option value="' . $backupserver['host'] . '">' . $backupserver['host'] . '</option>';
            }
            echo '</select>';
        }
?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Directory</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="directory" id="inputUsername3" placeholder="/var/www/html" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Encryption</label>
                        <div class="col-sm-10">
                            <select name="encryption"><option value="false">No Encryption</option><option value="AES-256">AES-256</option></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Encryption Password</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="encryptionpassword" id="inputUsername3" placeholder="password" required>
                            <p>Only enter if you plan to encrypt your backups.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Backup Auto-Delete (days)</label>
                        <div class="col-sm-10">
                            <input type="number" class="form-control" name="expiry" min="1" max="999" value="30" required>
                        </div>
                    </div>
                    <input type="hidden" name="type" value="full">
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Add Job</button>
                        </div>
                    </div>
                </form>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="incremental">
               <h3>Add incremental backup job</h3>
                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <input type="hidden" name="action" value="backupjobs">
                    <input type="hidden" name="backupjob" value="add">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Source</label>
                        <div class="col-sm-10">
            <?php
        if (count($fileservers) == 0) {
            echo '<div class="alert alert-warning">Please add a file server first.</div>';
        } else {
            echo '<select name="source">';
            foreach ($fileservers as $backupserver) {
                echo '<option value="' . $backupserver['host'] . '">' . $backupserver['host'] . '</option>';
            }
            echo '</select>';
        }
?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Directory</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="directory" id="inputUsername3" placeholder="/var/www/html" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Backup Auto-Delete (days)</label>
                        <div class="col-sm-10">
                            <input type="number" class="form-control" name="expiry" min="1" max="999" value="30" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Encryption</label>
                        <div class="col-sm-10">
                            <select name="encryption"><option value="false">No Encryption</option><option value="AES-256">AES-256</option></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Encryption Password</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="encryptionpassword" id="inputUsername3" placeholder="password" required>
                            <p>Only enter if you plan to encrypt your backups.</p>
                        </div>
                    </div>
                    <input type="hidden" name="type" value="incremental">
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Add Job</button>
                        </div>
                    </div>
                </form>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="mysql">
               <h3>Add mysql backup job</h3>
                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <input type="hidden" name="action" value="backupjobs">
                    <input type="hidden" name="backupjob" value="add">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Source</label>
                        <div class="col-sm-10">
            <?php
        if (count($sqlservers) == 0) {
            echo '<div class="alert alert-warning">Please add a MySQL server first.</div>';
        } else {
            echo '<select name="source">';
            foreach ($sqlservers as $backupserver) {
                echo '<option value="' . $backupserver['host'] . '">' . $backupserver['host'] . '</option>';
            }
            echo '</select>';
        }
?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Database / Tables</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="directory" id="inputUsername3" placeholder="mydatabase table1,table2,table3" required>
                            <p>Enter the MySQL database and tables to be backup (separated with a comma), eg: mydatabase table1,table2,table3. Leave tables empty if you want a backup of all tables.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Backup Auto-Delete (days)</label>
                        <div class="col-sm-10">
                            <input type="number" class="form-control" name="expiry" min="1" max="999" value="30" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Encryption</label>
                        <div class="col-sm-10">
                            <select name="encryption"><option value="false">No Encryption</option><option value="AES-256">AES-256</option></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Encryption Password</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="encryptionpassword" id="inputUsername3" placeholder="password" required>
                            <p>Only enter if you plan to encrypt your backups.</p>
                        </div>
                    </div>
                    <input type="hidden" name="type" value="mysql">
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Add Job</button>
                        </div>
                    </div>
                </form>
                <div class="alert alert-info">Make sure that the MySQL <code>max_allowed_packet</code> variable is <b>larger</b> than the database size.</div>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="openvz">
               <h3>Add OpenVZ backup job</h3>
                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <input type="hidden" name="action" value="backupjobs">
                    <input type="hidden" name="backupjob" value="add">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Source</label>
                        <div class="col-sm-10">
            <?php
        if (count($fileservers) == 0) {
            echo '<div class="alert alert-warning">Please add an OpenVZ server first.</div>';
        } else {
            echo '<select name="source">';
            foreach ($fileservers as $backupserver) {
                echo '<option value="' . $backupserver['host'] . '">' . $backupserver['host'] . '</option>';
            }
            echo '</select>';
        }
?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Excluded CTID's</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="directory" id="inputUsername3" placeholder="101" required>
                            <p>Enter the container ID's that you do not wish to be backed up, seperated with a space (eg: 101 102 103). Enter 0 to backup all containers.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Backup Auto-Delete (days)</label>
                        <div class="col-sm-10">
                            <input type="number" class="form-control" name="expiry" min="1" max="999" value="30" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Encryption</label>
                        <div class="col-sm-10">
                            <select name="encryption"><option value="false">No Encryption</option><option value="AES-256">AES-256</option></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Encryption Password</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="encryptionpassword" id="inputUsername3" placeholder="password" required>
                            <p>Only enter if you plan to encrypt your backups.</p>
                        </div>
                    </div>
                    <input type="hidden" name="type" value="openvz">
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Add Job</button>
                        </div>
                    </div>
                </form>
                <div class="alert alert-info">OpenVZ backup requires vzdump and lvm2. If /vz is not a logical volume, the VPS will be suspended / <b>offline</b> when it's checkpointed. <b>The VPS will stay online if /vz is a logical volume.</b></div>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="cpanel">
               <h3>Add cPanel backup job</h3>
                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <input type="hidden" name="action" value="backupjobs">
                    <input type="hidden" name="backupjob" value="add">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Source</label>
                        <div class="col-sm-10">
            <?php
        if (count($cpanelservers) == 0) {
            echo '<div class="alert alert-warning">Please add a cPanel server first.</div>';
        } else {
            echo '<select name="source">';
            foreach ($cpanelservers as $backupserver) {
                echo '<option value="' . $backupserver['host'] . '">' . $backupserver['host'] . '</option>';
            }
            echo '</select>';
        }
?>
                        </div>
                    </div>
                    <input type="hidden" name="directory" value="N/A">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Backup Auto-Delete (days)</label>
                        <div class="col-sm-10">
                            <input type="number" class="form-control" name="expiry" min="1" max="999" value="30" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Encryption</label>
                        <div class="col-sm-10">
                            <select name="encryption"><option value="false">No Encryption</option><option value="AES-256">AES-256</option></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Encryption Password</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="encryptionpassword" id="inputUsername3" placeholder="password" required>
                            <p>Only enter if you plan to encrypt your backups.</p>
                        </div>
                    </div>
                    <input type="hidden" name="type" value="cpanel">
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Add Job</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
        include($config['path'] . '/includes/footer.php');
    }
}

?>