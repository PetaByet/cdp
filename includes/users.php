<?php

if ( constant ( 'FILEACCESS' ) )
{
    checkacl ( 'upaccess' );
    $users = json_decode ( file_get_contents ( $config[ 'path' ].'/includes/db-users.json' ), true );
    $acls  = json_decode ( file_get_contents ( $config[ 'path' ].'/includes/db-acl.json' ), true );
    if ( isset( $_REQUEST[ 'users' ] ) )
    {
        if ( $_REQUEST[ 'users' ] == 'add' && isset( $_REQUEST[ 'username' ] ) && isset( $_REQUEST[ 'password' ] ) && isset( $_REQUEST[ 'acl' ] ) )
        {
            checkacl ( 'adduser' );
            $users[ count ( $users ) ] = array(
                'id'       => count ( $users ) + 1,
                'username' => trim ( $_REQUEST[ 'username' ] ),
                'password' => md5 ( $_REQUEST[ 'password' ] ),
                'acl'      => $_REQUEST[ 'acl' ]
            );
            file_put_contents ( $config[ 'path' ].'/includes/db-users.json', json_encode ( $users ) );
            logevent ( 'User '.$_SESSION[ 'user' ].' added user '.$_REQUEST[ 'username' ], 'activity' );
            header ( 'Location: index.php?action=users' );
        } elseif ( $_REQUEST[ 'users' ] == 'edit' && isset( $_REQUEST[ 'username' ] ) && isset( $_REQUEST[ 'userid' ] ) && isset( $_REQUEST[ 'acl' ] ) )
        {
            checkacl ( 'edituser' );
            foreach ( $users as $userkey => $user )
            {
                if ( $user[ 'id' ] == $_REQUEST[ 'userid' ] )
                {
                    $users[ $userkey ][ 'username' ] = $_REQUEST[ 'username' ];
                    $users[ $userkey ][ 'acl' ]      = $_REQUEST[ 'acl' ];
                    if ( isset( $_REQUEST[ 'password' ] ) )
                    {
                        $users[ $userkey ][ 'password' ] = md5 ( $_REQUEST[ 'password' ] );
                    }
                }
            }
            file_put_contents ( $config[ 'path' ].'/includes/db-users.json', json_encode ( $users ) );
            logevent ( 'User '.$_SESSION[ 'user' ].' edited user '.$_REQUEST[ 'username' ], 'activity' );
            header ( 'Location: index.php?action=users' );
        } elseif ( $_REQUEST[ 'users' ] == 'remove' && isset( $_REQUEST[ 'id' ] ) )
        {
            checkacl ( 'deluser' );
            foreach ( $users as $userkey => $user )
            {
                if ( $user[ 'id' ] == $_REQUEST[ 'id' ] )
                {
                    unset( $users[ $userkey ] );
                }
            }
            file_put_contents ( $config[ 'path' ].'/includes/db-users.json', json_encode ( $users ) );
            logevent ( 'User '.$_SESSION[ 'user' ].' removed user', 'activity' );
            header ( 'Location: index.php?action=users' );
        }
    } else
    {
        include ( $config[ 'path' ].'/includes/header.php' );
        ?>

        <div class="container">
            <h2 class="text-center">Users</h2>
            <table class="table table-striped table-bordered">
                <tr>
                    <th>Username</th>
                    <th>ACL</th>
                    <th>User ID</th>
                    <th>Actions</th>
                </tr>
                <?php
                if ( is_array ( $users ) )
                {
                    foreach ( $users as $user )
                    {
                        echo '<tr><td>'.$user[ 'username' ].'</td>';
                        echo '<td>';
                        foreach ( $acls as $acl )
                        {
                            if ( $acl[ 'id' ] == $user[ 'acl' ] )
                            {
                                echo $acl[ 'name' ];
                            }
                        }
                        echo '</td>';
                        echo '<td>'.$user[ 'id' ].'</td>';
                        echo '<td><a href="index.php?action=users&id='.$user[ 'id' ].'" class="btn btn-info">Edit</a> <a href="index.php?action=users&users=remove&id='.$user[ 'id' ].'" class="btn btn-danger">Delete</a></td></tr>';
                    }
                }
                ?>
            </table>
            <?php
            if ( isset( $_REQUEST[ 'id' ] ) && is_array ( $users ) )
            {
                foreach ( $users as $user )
                {
                    if ( $user[ 'id' ] == $_REQUEST[ 'id' ] )
                    {
                        $userdetails = $user;
                    }
                }
            }
            if ( isset( $userdetails ) && is_array ( $userdetails ) )
            {
                ?>
                <h3>Edit user</h3>
                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <input type="hidden" name="action" value="users">
                    <input type="hidden" name="users" value="edit">
                    <input type="hidden" name="userid" value="<?php echo $_REQUEST[ 'id' ]; ?>">

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Username</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="username"
                                   value="<?php echo $userdetails[ 'username' ]; ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Password</label>

                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="password"
                                   placeholder="Only enter if you want to change password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">ACL</label>

                        <div class="col-sm-10">
                            <?php
                            echo '<select name="acl">';
                            foreach ( $acls as $acl )
                            {
                                if ( $acl[ 'id' ] == $user[ 'acl' ] )
                                {
                                    echo '<option value="'.$acl[ 'id' ].'" selected>'.$acl[ 'name' ].'</option>';
                                } else
                                {
                                    echo '<option value="'.$acl[ 'id' ].'">'.$acl[ 'name' ].'</option>';
                                }
                            }
                            echo '</select>';
                            ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Submit</button>
                        </div>
                    </div>
                </form>
            <?php
            } else
            {
                ?>
                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <input type="hidden" name="action" value="users">
                    <input type="hidden" name="users" value="add">

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Username</label>

                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Password</label>

                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">ACL</label>

                        <div class="col-sm-10">
                            <?php
                            echo '<select name="acl">';
                            foreach ( $acls as $acl )
                            {
                                if ( $acl[ 'id' ] == $user[ 'acl' ] )
                                {
                                    echo '<option value="'.$acl[ 'id' ].'" selected>'.$acl[ 'name' ].'</option>';
                                } else
                                {
                                    echo '<option value="'.$acl[ 'id' ].'">'.$acl[ 'name' ].'</option>';
                                }
                            }
                            echo '</select>';
                            ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Submit</button>
                        </div>
                    </div>
                </form>
            <?php } ?>
        </div>
        <?php
        include ( $config[ 'path' ].'/includes/footer.php' );
    }
}

?>