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
</div>

<?php
	include($config['path'].'/includes/footer.php');
}

?>
