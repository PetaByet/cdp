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
$backups = json_decode(file_get_contents($config['path'] . '/db/db-backups.json'), true);
$backups = array_reverse($backups); //reverse the array to make newest backups at the top
$backupjobs    = json_decode(file_get_contents($config['path'] . '/db/db-backupjobs.json'), true);
$backupservers = json_decode(file_get_contents($config['path'] . '/db/db-backupservers.json'), true);
function GetJobDetails($jobid)
{
    global $backupjobs;
    foreach ($backupjobs as $backupjob) {
        if ($backupjob['id'] == $jobid) {
            return $backupjob;
        }
    }
    return false;
}
$backupjob = GetJobDetails($_GET['id']);
$smarty->display($config['path'].'/templates/header.tpl');
echo '<div class="container"><h2>View Backups</h2>';
if ($backupjob['encryption'] == 'AES-256') {
    echo '<div class="alert alert-info">It may take a little longer to download decrypted files, please be patient!</div>';
}
echo '<table class="table table-striped table-bordered">';
echo '<tr><th>File</th><th>Size</th><th>Time</th><th>Actions</th>';
foreach ($backups as $backup) {
    if (isset($_GET['id']) && $_GET['id'] == $backup['id']) {
        echo '<tr><td>' . $backup['file'] . '</td>';
        echo '<td>' . formatBytes($backup['size']) . '</td>';
        echo '<td>' . date("Y-m-d H:i:s", $backup['time']) . '</td>';
        echo '<td><a href="index.php?action=backupdownload&id=' . $backup['file'] . '" class="btn btn-success">Download</a> ';
        if ($backupjob['encryption'] == 'AES-256') {
            echo '<a href="index.php?action=backupdownloaddecrypt&id=' . $backup['file'] . '" class="btn btn-success">Download (Decrypted)</a> ';
        }
        echo '<a href="#" onclick="ConfirmRestore(\'index.php?action=backuprestore&step=1&id=' . $backup['file'] . '\')" class="btn btn-info">Restore</a>
        <a href="index.php?action=backupdelete&id=' . $backup['file'] . '" class="btn btn-danger">Delete</a></td></tr>';
    }
}
echo '</table>';
echo '</div>';
?>
<script>
function ConfirmRestore(url) {
    if (confirm("Are you sure that you want to restore the backup? If so, please continue.") == true) {
        window.location.href = url;
    }
}
</script>
<?php
$smarty->display($config['path'].'/templates/footer.tpl');
?>