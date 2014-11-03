<?php

if (constant('FILEACCESS')) {

	include($config['path'].'/includes/header.php');
    $backups       = json_decode(file_get_contents($config['path'].'/includes/db-backups.json'), true);
    $backupjobs    = json_decode(file_get_contents($config['path'].'/includes/db-backupjobs.json'), true);
    $backupservers = json_decode(file_get_contents($config['path'].'/includes/db-backupservers.json'), true);
?>


<div class="container">
	<h2 class="text-center">Dashboard</h2>
	<div class="col-md-4">
	    <h3 class="text-center">Backup Jobs</h3>
        <p class="lead text-center"><?php echo count($backupjobs); ?></p>
	</div>
	<div class="col-md-4">
	    <h3 class="text-center">Servers</h3>
        <p class="lead text-center"><?php echo count($backupservers); ?></p>
	</div>
	<div class="col-md-4">
	    <h3 class="text-center">Backups</h3>
        <p class="lead text-center"><?php echo count($backups); ?></p>
	</div>
	<div class="col-md-6 col-md-offset-3">
        <h3 class="text-center">MD5 password generator</h3>
        <?php
            if (isset($_REQUEST['md5'])) {
                echo '<div class="alert alert-info">Your md5 hashed password is <code>'.md5(urldecode($_REQUEST['md5'])).'</code></div>';
            }
        ?>
        <p class="test-center">Use this MD5 password generator if you would like to update your password in the config file.</p>
        <form class="form-horizontal" role="form" method="post" action="index.php" enctype="application/x-www-form-urlencoded">
            <div class="form-group">
                <input type="password" class="form-control" name="md5" placeholder="enter your new password" required>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-default">Generate MD5</button>
                </div>
            </div>
        </form>
	</div>
</div>

<?php
	include($config['path'].'/includes/footer.php');
}

?>
