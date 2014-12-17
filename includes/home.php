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
$backups       = json_decode(file_get_contents($config['path'] . '/db/db-backups.json'), true);
$backupjobs    = json_decode(file_get_contents($config['path'] . '/db/db-backupjobs.json'), true);
$backupservers = json_decode(file_get_contents($config['path'] . '/db/db-backupservers.json'), true);
$smarty->assign('path',$config['path']);
$smarty->assign('backupjobscount',count($backupjobs));
$smarty->assign('backupserverscount',count($backupservers));
$smarty->assign('backupscount',count($backups));
$smarty->assign('disk',round((disk_total_space($config['path']) - disk_free_space($config['path'])) / disk_total_space($config['path']) * 100, 2));
$loadavg = sys_getloadavg();
$smarty->assign('loadavg',$loadavg[0] . '/' . $loadavg[1] . '/' . $loadavg[2]);
$activitylogs = json_decode(file_get_contents($config['path'] . '/db/db-activitylog.json'), true);
$activitylogs = array_reverse($activitylogs);
$activitylogentries = array();
for ($i = 0; $i < 10; $i++) {
    if (isset($activitylogs[$i]) && is_array($activitylogs[$i])) {
        if (strlen($activitylogs[$i]['data']) > 50) {
            $stringCut = substr($activitylogs[$i]['data'], 0, 50);
            $activitylogdata = substr($stringCut, 0, strrpos($stringCut, ' ')).'...';
        } else {
            $activitylogdata = $activitylogs[$i]['data'];
        }
        $activitylogentries[count($activitylogentries)] = array(
            'data' => $activitylogdata,
            'time' => date("Y-m-d H:i:s", $activitylogs[$i]['time']),
            'ip' => $activitylogs[$i]['ip']
        );
    } else {
        $activitylogentries[count($activitylogentries)] = array(
            'data' => '-',
            'time' => '-',
            'ip' => '-'
        );
    }
}
$smarty->assign('activitylogentries',$activitylogentries);
$backuplogs = json_decode(file_get_contents($config['path'] . '/db/db-backuplog.json'), true);
$backuplogs = array_reverse($backuplogs);
$backuplogentries = array();
for ($i = 0; $i < 10; $i++) {
    if (isset($backuplogs[$i]) && is_array($backuplogs[$i])) {
        if (strlen($backuplogs[$i]['data']) > 50) {
            $stringCut = substr($backuplogs[$i]['data'], 0, 50);
            $backuplogdata = substr($stringCut, 0, strrpos($stringCut, ' ')).'...';
        } else {
            $backuplogdata = $backuplogs[$i]['data'];
        }
        $backuplogentries[count($backuplogentries)] = array(
            'data' => $backuplogdata,
            'time' => date("Y-m-d H:i:s", $backuplogs[$i]['time']),
            'ip' => $backuplogs[$i]['ip']
        );
    } else {
        $backuplogentries[count($backuplogentries)] = array(
            'data' => '-',
            'time' => '-',
            'ip' => '-'
        );
    }
}
$smarty->assign('backuplogentries',$backuplogentries);
$smarty->display($config['path'].'/templates/header.tpl');
$smarty->display($config['path'].'/templates/home.tpl');
$smarty->display($config['path'].'/templates/footer.tpl');
?>