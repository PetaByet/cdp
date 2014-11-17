<?php

if ( constant ( 'FILEACCESS' ) )
{
    if ( isset( $_POST[ 'username' ] ) && isset( $_POST[ 'password' ] ) )
    {
        $users = json_decode ( file_get_contents ( $config[ 'path' ].'/includes/db-users.json' ), true );
        $acls  = json_decode ( file_get_contents ( $config[ 'path' ].'/includes/db-acl.json' ), true );
        foreach ( $users as $user )
        {
            if ( $user[ 'username' ] == $_POST[ 'username' ] )
            {
                $userdetails = $user;
            }
        }
        if ( is_array ( $userdetails ) && md5 ( $_POST[ 'password' ] ) == $userdetails[ 'password' ] )
        {
            $_SESSION[ 'user' ] = $_POST[ 'username' ];
            $_SESSION[ 'ip' ]   = $_SERVER[ 'REMOTE_ADDR' ];
            $_SESSION[ 'time' ] = time ();
            foreach ( $acls as $acl )
            {
                if ( $acl[ 'id' ] == $user[ 'acl' ] )
                {
                    $_SESSION[ 'acl' ] = $acl[ 'perms' ];
                }
            }
            logevent ( 'User '.$_SESSION[ 'user' ].' logged in', 'activity' );
            header ( 'Location: index.php' );
            die();
        } else
        {
            header ( 'Location: index.php?login=failed' );
        }
    } else
    {
        include ( $config[ 'path' ].'/includes/header.php' );
        ?>
        <div class="container">
            <div class="col-md-6 col-md-offset-3 well">
                <?php
                if ( isset( $_GET[ 'login' ] ) && $_GET[ 'login' ] == 'failed' )
                {
                    echo '<div class="alert alert-info">Login failed.</div>';
                }
                ?>
                <h2 class="text-center">Please log in</h2>

                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Username</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="username" id="inputUsername3"
                                   placeholder="admin">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputPassword3" class="col-sm-2 control-label">Password</label>

                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="password" id="inputPassword3"
                                   placeholder="Password">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Sign in</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
        include ( $config[ 'path' ].'/includes/footer.php' );
    }
}

?>