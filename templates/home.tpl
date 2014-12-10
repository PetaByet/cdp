<div class="container">
	<h2 class="text-center">Dashboard</h2>
    {if $smarty.session.acl.backupjobs}
    <div class="col-md-4">
	    <h3 class="text-center">Backup Jobs</h3>
        <p class="lead text-center">{$backupjobscount}</p>
    </div>
    {/if}
    {if $smarty.session.acl.servers}
	<div class="col-md-4">
	    <h3 class="text-center">Servers</h3>
        <p class="lead text-center">{$backupserverscount}</p>
	</div>
    {/if}
    {if $smarty.session.acl.backups}
	<div class="col-md-4">
	    <h3 class="text-center">Backups</h3>
        <p class="lead text-center">{$backupscount}</p>
	</div>
    {/if}
    {if $smarty.session.acl.disk || $smarty.session.acl.loadavg}
	<div class="col-md-8 col-md-offset-2">
        <h3 class="text-center">Server Status</h3>
        {if $smarty.session.acl.disk}
        <h4>Disk {$disk}%</h4>
        <div class="progress">
            <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: {$disk}%;">
            </div>
        </div>
        {/if}
        {if $smarty.session.acl.loadavg}
        <h4>Load Average</h4>
        {$loadavg}
        {/if}
    </div>
    {/if}
    {if $smarty.session.acl.alog}
    <div class="col-md-4 col-md-offset-2">
        <h4>Last 10 Activity Logs</h4>
        <table class="table table-bordered table-striped">
        <tr><th>Data</th><th>Time</th><th>IP</th></tr>
        {foreach $activitylogentries as $activitylogentry}
        <tr>
            <td>{$activitylogentry.data}</td>
            <td>{$activitylogentry.time}</td>
            <td>{$activitylogentry.ip}</td>
        </tr>
        {/foreach}
        </table>
        <a href="index.php?action=activitylogs" class="btn btn-info">View All</a>
    </div>
    {/if}
    {if $smarty.session.acl.blog}
    <div class="col-md-4">
        <h4>Last 10 Backup Logs</h4>
        <table class="table table-bordered table-striped">
        <tr><th>Data</th><th>Time</th><th>IP</th></tr>
        {foreach $backuplogentries as $backuplogentry}
        <tr>
            <td>{$backuplogentry.data}</td>
            <td>{$backuplogentry.time}</td>
            <td>{$backuplogentry.ip}</td>
        </tr>
        {/foreach}
        </table>
        <a href="index.php?action=backuplogs" class="btn btn-info">View All</a>
    </div>
    {/if}
</div>