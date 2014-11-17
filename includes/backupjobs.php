<?php

if ( constant ( 'FILEACCESS' ) )
{
    checkacl ( 'bjpaccess' );
    $backupjobs    = json_decode ( file_get_contents ( $config[ 'path' ].'/includes/db-backupjobs.json' ), true );
    $backupservers = json_decode ( file_get_contents ( $config[ 'path' ].'/includes/db-backupservers.json' ), true );
    if ( isset( $_REQUEST[ 'backupjob' ] ) )
    {
        if ( $_REQUEST[ 'backupjob' ] == 'add' && isset( $_REQUEST[ 'source' ] ) && isset( $_REQUEST[ 'directory' ] ) && isset( $_REQUEST[ 'expiry' ] ) )
        {
            checkacl ( 'addjob' );
            $id                                  = md5 ( rand ().time ().$_REQUEST[ 'source' ] );
            $backupjobs[ count ( $backupjobs ) ] = array(
                'id'        => $id,
                'source'    => $_REQUEST[ 'source' ],
                'directory' => $_REQUEST[ 'directory' ],
                'expiry'    => $_REQUEST[ 'expiry' ],
                'type'      => $_REQUEST[ 'type' ]
            );
            file_put_contents ( $config[ 'path' ].'/includes/db-backupjobs.json', json_encode ( $backupjobs ) );
            logevent ( 'User '.$_SESSION[ 'user' ].' added backup job', 'activity' );
            header ( 'Location: index.php?action=backupjobs&created=true&id='.$id );
        } elseif ( $_REQUEST[ 'backupjob' ] == 'remove' && isset( $_REQUEST[ 'id' ] ) )
        {
            checkacl ( 'deljob' );
            foreach ( $backupjobs as $key => $backupjob )
            {
                if ( $backupjob[ 'id' ] == $_REQUEST[ 'id' ] )
                {
                    unset( $backupjobs[ $key ] );
                }
            }
            file_put_contents ( $config[ 'path' ].'/includes/db-backupjobs.json', json_encode ( $backupjobs ) );
            logevent ( 'User '.$_SESSION[ 'user' ].' removed backup job', 'activity' );
            header ( 'Location: index.php?action=backupjobs' );
        }
    } else
    {
        include ( $config[ 'path' ].'/includes/header.php' );
        ?>
        <div class="container">
            <h2 class="text-center">Backup Jobs</h2>
            <?php
            if ( isset( $_GET[ 'id' ] ) && isset( $_GET[ 'created' ] ) && $_GET[ 'created' ] )
            {
                echo '<div class="alert alert-info">';
                echo 'Congratulations! Your backup job has been created. You may now add it to crontab to run it automatically. Here are some examples:<br>';
                echo 'A backup every 15 minutes: <code>*/15 * * * * php '.$config[ 'path' ].'/cron.php '.$_GET[ 'id' ].' >/dev/null 2>&1</code><br>';
                echo 'Hourly backups: <code>0 * * * * php '.$config[ 'path' ].'/cron.php '.$_GET[ 'id' ].' >/dev/null 2>&1</code><br>';
                echo 'Daily backups at 3am: <code>0 3 * * * php '.$config[ 'path' ].'/cron.php '.$_GET[ 'id' ].' >/dev/null 2>&1</code><br>';
                echo 'For more information about how crontab works, please use <a href="http://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/">this guide</a>.<br>';
                echo 'And here is the command itself: <input type="text" value="php '.$config[ 'path' ].'/cron.php '.$_GET[ 'id' ].'">';
                echo '</div>';
            }
            ?>
            <table class="table table-striped table-bordered">
                <tr>
                    <th>Source</th>
                    <th>Directory</th>
                    <th>ID</th>
                    <th>Backup Auto-Delete</th>
                    <th>Actions</th>
                </tr>
                <?php
                if ( is_array ( $backupjobs ) )
                {
                    foreach ( $backupjobs as $backupjob )
                    {
                        echo '<tr><td>'.$backupjob[ 'source' ].'</td>';
                        echo '<td>'.$backupjob[ 'directory' ].'</td>';
                        echo '<td>'.$backupjob[ 'id' ].'</td>';
                        echo '<td>'.$backupjob[ 'expiry' ].' Days</td>';
                        echo '<td><a href="index.php?action=viewbackups&id='.$backupjob[ 'id' ].'" class="btn btn-info">View Backups</a> <a href="index.php?action=runbackup&id='.$backupjob[ 'id' ].'" class="btn btn-success">Backup Now</a> <a href="index.php?action=backupjobs&backupjob=remove&id='.$backupjob[ 'id' ].'" class="btn btn-danger">Delete</a></td></tr>';
                    }
                }
                ?>
            </table>
            <form class="form-horizontal" role="form" method="post" action="index.php">
                <input type="hidden" name="action" value="backupjobs">
                <input type="hidden" name="backupjob" value="add">

                <div class="form-group">
                    <label for="inputUsername3" class="col-sm-2 control-label">Source</label>

                    <div class="col-sm-10">
                        <?php
                        if ( count ( $backupservers ) == 0 )
                        {
                            echo '<div class="alert alert-warning">Please add a server first.</div>';
                        } else
                        {
                            echo '<select name="source">';
                            foreach ( $backupservers as $backupserver )
                            {
                                echo '<option value="'.$backupserver[ 'host' ].'">'.$backupserver[ 'host' ].'</option>';
                            }
                            echo '</select>';
                        }
                        ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputUsername3" class="col-sm-2 control-label">Directory</label>

                    <div class="col-sm-10">
                        <input type="text" class="form-control" name="directory" id="inputUsername3"
                               placeholder="/var/www/html" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputUsername3" class="col-sm-2 control-label">Backup Auto-Delete (days)</label>

                    <div class="col-sm-10">
                        <input type="number" class="form-control" name="expiry" min="1" max="999" value="30" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputUsername3" class="col-sm-2 control-label">Backup Type</label>

                    <div class="col-sm-10">
                        <input type="radio" name="type" value="full" checked> Full
                        <input type="radio" name="type" value="incremental"> Incremental (only backups changed files)
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-default">Add Job</button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        include ( $config[ 'path' ].'/includes/footer.php' );
    }
}

?>
