<?php

if (constant('FILEACCESS')) {
    checkacl('restoreb');
    if (isset($_GET['restoreaction']) && $_GET['restoreaction'] == 'initiate') {
        $tmpfilename = 'cdp-restore-'.md5(rand().time()).'.txt';
        shell_exec('php ' . $config['path'] . '/restore.php ' . escapeshellcmd($_GET['id']) . ' ' . escapeshellcmd($_GET['host']) . ' > ' . $config['path'] . '/files/' . $tmpfilename . ' 2>&1 &');
        echo $tmpfilename;
    } elseif (isset($_GET['restoreaction']) && $_GET['restoreaction'] == 'readtmpfile' && isset($_GET['tmpfilename'])) {
        $tmpfile = file_get_contents($config['path'] . '/files/' . $_GET['tmpfilename']);
        if (!empty($tmpfile)) {
            echo $tmpfile;
        }
    } elseif (isset($_GET['id'])) {
        $backups       = json_decode(file_get_contents($config['path'] . '/includes/db-backups.json'), true);
        $backupjobs    = json_decode(file_get_contents($config['path'] . '/includes/db-backupjobs.json'), true);
        $backupservers = json_decode(file_get_contents($config['path'] . '/includes/db-backupservers.json'), true);
        function GetBackupDetails($backupdata)
        {
            global $backups;
            foreach ($backups as $backup) {
                if ($backup['file'] == $backupdata) {
                    return $backup;
                }
            }
            return false;
        }
        $backupdetail = GetBackupDetails($_GET['id']);
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
        $jobdetail = GetJobDetails($backupdetail['id']);
        $smarty->assign('backupservers',$backupservers);
        $smarty->assign('backupdetail',$backupdetail);
        $smarty->assign('jobdetail',$jobdetail);
        $smarty->display($config['path'].'/templates/header.tpl');
        $smarty->display($config['path'].'/templates/backuprestore.tpl');
        $smarty->display($config['path'].'/templates/footer.tpl');
    }
}

?>