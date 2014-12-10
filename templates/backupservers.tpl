<div class="container">
	<h2 class="text-center">Servers</h2>
	<table class="table table-striped table-bordered">
		<tr><th>Host</th><th>Port</th><th>SSH Authentication</th><th>Username</th><th>Action</th></tr>
        {if is_array($backupservers)}
            {foreach $backupservers as $backupserver}
                <tr>
                    <td>{$backupserver.host}</td>
                    <td>{$backupserver.port}</td>
                    <td>{$backupserver.authtype}</td>
                    <td>{$backupserver.username}</td>
                    <td><a href="index.php?action=backupservers&backupserver=remove&id={$backupserver.id}" class="btn btn-danger">Delete</a></td>
                </tr>
            {/foreach}
        {/if}
	</table>
   <h2 class="text-center">Add a backup server</h2>
    <div role="tabpanel">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#file" aria-controls="file" role="tab" data-toggle="tab">File / OpenVZ</a></li>
            <li role="presentation"><a href="#mysql" aria-controls="mysql" role="tab" data-toggle="tab">MySQL</a></li>
            <li role="presentation"><a href="#cpanel" aria-controls="cpanel" role="tab" data-toggle="tab">cPanel</a></li>
        </ul>
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade in active" id="file">  
                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <input type="hidden" name="action" value="backupservers">
                    <input type="hidden" name="backupserver" value="add">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Host</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="host" id="inputUsername3" placeholder="192.168.1.1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">SSH Port</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="port" id="inputUsername3" placeholder="22" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">SSH Authentication</label>
                        <div class="col-sm-10">
                            <input type="radio" name="authtype" value="password" checked> Password
                            <input type="radio" name="authtype" value="key"> SSH Key
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">SSH Username</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="username" id="inputUsername3" placeholder="root" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputPassword3" class="col-sm-2 control-label">Password or Key Path</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="password" id="inputPassword3" placeholder="ssh password or path to ssh key" required>
                            <p>Note: if your SSH key has a passphrase, please enter it after the key path, separated with a space (like /path/to/key password)</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Add Server</button>
                        </div>
                    </div>
                </form>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="mysql">
                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <input type="hidden" name="action" value="backupservers">
                    <input type="hidden" name="backupserver" value="add">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">Host</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="host" id="inputUsername3" placeholder="192.168.1.1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">MySQL Port</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="port" id="inputUsername3" placeholder="3306" required>
                        </div>
                    </div>
                    <input type="hidden" name="authtype" value="mysql">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">MySQL Username</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="username" id="inputUsername3" placeholder="root" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputPassword3" class="col-sm-2 control-label">MySQL Password</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="password" id="inputPassword3" placeholder="mysql password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Add Server</button>
                        </div>
                    </div>
                </form>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="cpanel">
                <form class="form-horizontal" role="form" method="post" action="index.php">
                    <input type="hidden" name="action" value="backupservers">
                    <input type="hidden" name="backupserver" value="add">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">cPanel Host</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="host" id="inputUsername3" placeholder="cpanel.cdp.me" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">cPanel Port</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="port" id="inputUsername3" placeholder="2083" required>
                        </div>
                    </div>
                    <input type="hidden" name="authtype" value="cpanel">
                    <div class="form-group">
                        <label for="inputUsername3" class="col-sm-2 control-label">cPanel Username</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="username" id="inputUsername3" placeholder="username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputPassword3" class="col-sm-2 control-label">cPanel Password</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="password" id="inputPassword3" placeholder="password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Add Server</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>