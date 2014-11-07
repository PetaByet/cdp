<?php

if (constant('FILEACCESS')) {
    checkacl('viewbackup');
    function formatBytes($size, $precision = 2)
    {
        $base     = log($size) / log(1024);
        $suffixes = array(
            'b',
            'k',
            'M',
            'G',
            'T'
        );
        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }
    $backups = json_decode(file_get_contents($config['path']. '/includes/db-backups.json'), true);
    $backups = array_reverse($backups); //reverse the array to make newest backups at the top
    include($config['path'].'/includes/header.php');
    echo '<div class="container"><h2>View Backups</h2>';
    echo '<table class="table table-striped table-bordered">';
    echo '<tr><th>File</th><th>Size</th><th>Time</th><th>Actions</th>';
    foreach ($backups as $backup) {
        if (isset($_GET['id']) && $_GET['id'] == $backup['id']) {
            echo '<tr><td>' . $backup['file'] . '</td>';
            echo '<td>' . formatBytes($backup['size']) . '</td>';
            echo '<td>' . date("Y-m-d H:i:s", $backup['time']) . '</td>';
            echo '<td><a href="index.php?action=backupdownload&id=' . $backup['file'] . '" class="btn btn-success">Download</a> <a href="index.php?action=backuprestore&id=' . $backup['file'] . '" class="btn btn-info">Restore</a> <a href="index.php?action=backupdelete&id=' . $backup['file'] . '" class="btn btn-danger">Delete</a></td></tr>';
        }
    }
    echo '</table>';
    echo '</div>';
    include($config['path'].'/includes/footer.php');
}

?>