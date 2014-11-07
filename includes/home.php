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
        <?php if (checkacl('backupjobs', true)) { ?>
	    <h3 class="text-center">Backup Jobs</h3>
        <p class="lead text-center"><?php echo count($backupjobs); ?></p>
        <?php } ?>
	</div>
	<div class="col-md-4">
        <?php if (checkacl('servers', true)) { ?>
	    <h3 class="text-center">Servers</h3>
        <p class="lead text-center"><?php echo count($backupservers); ?></p>
        <?php } ?>
	</div>
	<div class="col-md-4">
        <?php if (checkacl('backups', true)) { ?>
	    <h3 class="text-center">Backups</h3>
        <p class="lead text-center"><?php echo count($backups); ?></p>
        <?php } ?>
	</div>
	<div class="col-md-8 col-md-offset-2">
        <h3 class="text-center">Server Status</h3>
        <?php if (checkacl('disk', true)) { ?>
        <h4>Disk <?php echo round((disk_total_space($config['path']) - disk_free_space($config['path'])) / disk_total_space($config['path']) * 100, 2); ?>%</h4>
        <div class="progress">
            <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo round((disk_total_space($config['path']) - disk_free_space($config['path'])) / disk_total_space($config['path']) * 100, 2); ?>%;">
            </div>
        </div>
        <?php } ?>
        <?php if (checkacl('loadavg', true)) { ?>
        <h4>Load Average</h4>
        <?php $loadavg = sys_getloadavg(); echo $loadavg[0].'/'.$loadavg[1].'/'.$loadavg[2] ?>
        <?php } ?>
    </div>
    <?php if (checkacl('alog', true)) { ?>
    <div class="col-md-4 col-md-offset-2">
        <h4>Last 10 Activity Logs</h4>
        <?php
            $activitylogs = json_decode(file_get_contents($config['path'] . '/includes/db-activitylog.json'), true);
            $activitylogs = array_reverse($activitylogs);
            echo '<table class="table table-bordered table-striped">';
            echo '<tr><th>Data</th><th>Time</th><th>IP</th></tr>';
            for ($i = 0 ; $i < 10; $i++){
                if (isset($activitylogs[$i]) && is_array($activitylogs[$i])) {
                    echo '<tr><td>'.$activitylogs[$i]['data'].'</td><td>'.date("Y-m-d H:i:s", $activitylogs[$i]['time']).'</td><td>'.$activitylogs[$i]['ip'].'</td></tr>';
                }
                else {
                    echo '<tr><td>-</td><td>-</td><td>-</td></tr>';
                }
            }
            echo '</table>';
        ?>
    </div>
    <?php } ?>
    <?php if (checkacl('blog', true)) { ?>
    <div class="col-md-4 col-md-offset-2">
        <h4>Last 10 Backup Logs</h4>
        <?php
            $backuplogs = json_decode(file_get_contents($config['path'] . '/includes/db-backuplog.json'), true);
            $backuplogs = array_reverse($backuplogs);
            echo '<table class="table table-bordered table-striped">';
            echo '<tr><th>Data</th><th>Time</th><th>IP</th></tr>';
            for ($i = 0 ; $i < 10; $i++){
                if (isset($backuplogs[$i]) && is_array($backuplogs[$i])) {
                    echo '<tr><td>'.$backuplogs[$i]['data'].'</td><td>'.date("Y-m-d H:i:s", $backuplogs[$i]['time']).'</td><td>'.$backuplogs[$i]['ip'].'</td></tr>';
                }
                else {
                    echo '<tr><td>-</td><td>-</td><td>-</td></tr>';
                }
            }
            echo '</table>';
        ?>
    </div>
    <?php } ?>
</div>

<?php
	include($config['path'].'/includes/footer.php');
}

?>
