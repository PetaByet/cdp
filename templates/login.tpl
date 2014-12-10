<div class="container">
    <div class="col-md-6 col-md-offset-3 well">
        {if isset($smarty.get.login) and $smarty.get.login eq 'failed'}
            <div class="alert alert-info">Login failed.</div>
        {/if}
	<h2 class="text-center">Please log in</h2>
        <form class="form-horizontal" role="form" method="post" action="index.php">
            <div class="form-group">
                <label for="inputUsername3" class="col-sm-2 control-label">Username</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="username" id="inputUsername3" placeholder="admin" required>
                </div>
            </div>
            <div class="form-group">
                <label for="inputPassword3" class="col-sm-2 control-label">Password</label>
                <div class="col-sm-10">
                    <input type="password" class="form-control" name="password" id="inputPassword3" placeholder="Password" required>
                </div>
            </div>
            <div class="form-group">
                <label for="inputPassword3" class="col-sm-2 control-label">2FO Key</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="2fokey" id="inputPassword3" placeholder="2 Factor Authentication One Time Key (only if enabled)">
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