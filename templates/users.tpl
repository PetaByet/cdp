
<div class="container">
	<h2 class="text-center">Users</h2>
    <table class="table table-striped table-bordered">
        <tr><th>Username</th><th>ACL</th><th>User ID</th><th>2-Factor Authentication</th><th>Actions</th></tr>
        {if is_array($users)}
            {foreach $users as $user}
                <tr>
                    <td>{$user.username}</td>
                    <td>
                    {foreach $acls as $acl}
                        {if $acl.id eq $user.acl}
                            {$acl.name}
                        {/if}
                    {/foreach}
                    </td>
                    <td>{$user.id}</td>
                    {if isset($user.2fo) and $user.2fo}
                        <td>Enabled</td>
                    {else}
                        <td>Disabled</td>
                    {/if}
                    <td>
                    <a href="index.php?action=users&id={$user.id}" class="btn btn-info">Edit</a> 
                    <a href="index.php?action=users&users=remove&id={$user.id}" class="btn btn-danger">Delete</a>
                    </td>
                </tr>
            {/foreach}
        {/if}
    </table>
    {if isset($userdetails) and is_array($userdetails)}
    <h3>Edit user</h3>
    <form class="form-horizontal" role="form" method="post" action="index.php">
        <input type="hidden" name="action" value="users">
        <input type="hidden" name="users" value="edit">
        <input type="hidden" name="userid" value="{$smarty.request.id}">
        <div class="form-group">
            <label class="col-sm-2 control-label">Username</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="username" value="{$userdetails.username}" required>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">Password</label>
            <div class="col-sm-10">
                <input type="password" class="form-control" name="password" placeholder="Only enter if you want to change password">
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">Enable 2-Factor Authentication</label>
            <div class="col-sm-10">
                {if isset($userdetails.2fo) and $userdetails.2fo}
                    <input type="radio" name="2fo" value="true" checked>Enable <input type="radio" name="2fo" value="false">Disable
                {else}
                    <input type="radio" name="2fo" value="true">Enable <input type="radio" name="2fo" value="false" checked>Disable
                {/if}
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">Google Authenticator Key <a class="btn btn-sm btn-primary" id="gen2fokey">Generate</a></label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="2fokey" name="2fokey" placeholder="This field is required if 2-factor authentication is enabled">
            </div>
        </div>
        <div id="qrcode"></div>
        {literal}
        <script>
        $('#gen2fokey').click(function(){
            $("#gen2fokey").text('Loading...');
            $('#gen2fokey').attr('disabled','disabled');
            $.get('index.php?action=users&users=2focreatekey', function(result) {
                $('#2fokey').val(result);
                $("#gen2fokey").text('Generate');
                $("#qrcode").load("index.php?action=users&users=generateqr&generateqr="+result);
            });
        });
        </script>
        {/literal}
        <div class="form-group">
            <label class="col-sm-2 control-label">ACL</label>
            <div class="col-sm-10">
                <select name="acl">
                {foreach $acls as $acl}
                    {if $acl.id == $user.acl}
                        <option value="{$acl.id}" selected>{$acl.name}</option>
                    {else}
                        <option value="{$acl.id}">{$acl.name}</option>
                    {/if}
                {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default">Submit</button>
            </div>
        </div>
    </form>
    {else}
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
            <label class="col-sm-2 control-label">Enable 2-Factor Authentication</label>
            <div class="col-sm-10">
               <input type="radio" name="2fo" value="true">Enable <input type="radio" name="2fo" value="false" checked>Disable  
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">Google Authenticator Key <a class="btn btn-sm btn-primary" id="gen2fokey">Generate</a></label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="2fokey" name="2fokey" placeholder="This field is required if 2-factor authentication is enabled">
            </div>
        </div>
        <div id="qrcode"></div>
        {literal}
        <script>
        $('#gen2fokey').click(function(){
            $("#gen2fokey").text('Loading...');
            $('#gen2fokey').attr('disabled','disabled');
            $.get('index.php?action=users&users=2focreatekey', function(result) {
                $('#2fokey').val(result);
                $("#gen2fokey").text('Generate');
                $("#qrcode").load("index.php?action=users&users=generateqr&generateqr="+result);
            });
        });
        </script>
        {/literal}
        <div class="form-group">
            <label class="col-sm-2 control-label">ACL</label>
            <div class="col-sm-10">
                <select name="acl">
                    {foreach $acls as $acl}
                        <option value="{$acl.id}">{$acl.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default">Submit</button>
            </div>
        </div>
    </form>
    {/if}
</div>