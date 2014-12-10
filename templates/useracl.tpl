
<div class="container">
	<h2 class="text-center">User ACLs</h2>
    <table class="table table-striped table-bordered">
        <tr><th>ID</th><th>Name</th><th>Actions</th></tr>
        {if is_array($acls)}
            {foreach $acls as $acl}
                <tr>
                    <td>{$acl.id}</td>
                    <td>{$acl.name}</td>
                    <td>
                        <a href="index.php?action=useracl&id={$acl.id}" class="btn btn-info">Edit</a> 
                        <a href="index.php?action=useracl&users=remove&id={$acl.id}" class="btn btn-danger">Delete</a>
                    </td>
                </tr>
            {/foreach}
        {/if}
    </table>
    {if isset($acldetails) and is_array($acldetails) and isset($smarty.request.id)}
    <h3>Edit ACL</h3>
    <div class="alert alert-info">ACL changes will take effect the next time the user logs in.</div>
    <form class="form-horizontal" role="form" method="post" action="index.php">
        <input type="hidden" name="action" value="useracl">
        <input type="hidden" name="acl" value="edit">
        <input type="hidden" name="aclid" value="{$smarty.request.id}">
        {foreach $aclarray as $groupkey => $aclgroup}
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">{$groupkey}</h4>
                    </div>
                    <div class="panel-body">
                    {foreach $aclgroup as $itemkey => $aclitem}
                        <div class="col-md-2">{$aclitem}</div>
                        <div class="col-md-1">
                            <select name="perms[{$itemkey}]">
                            {if $acldetails.perms.$itemkey}
                                <option value="true" selected>True</option><option value="false">False</option>
                            {else}
                                <option value="true">True</option><option value="false" selected>False</option>
                            {/if}
                           </select>
                       </div>
                    {/foreach}
                    </div>
                </div>
        {/foreach}
        <div class="form-group">
            <label class="col-sm-2 control-label">ACL Name</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="name" value="{$acldetails.name}" required>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default">Submit</button>
            </div>
        </div>
    </form>
    {else}
    <h3>Add ACL</h3>
    <form class="form-horizontal" role="form" method="post" action="index.php">
        <input type="hidden" name="action" value="useracl">
        <input type="hidden" name="acl" value="add">
        {foreach $aclarray as $groupkey => $aclgroup}
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">{$groupkey}</h4>
                    </div>
                    <div class="panel-body">
                    {foreach $aclgroup as $itemkey => $aclitem}
                        <div class="col-md-2">{$aclitem}</div>
                        <div class="col-md-1">
                            <select name="perms[{$itemkey}]">
                                <option value="true" selected>True</option>
                                <option value="false">False</option>
                           </select>
                       </div>
                    {/foreach}
                    </div>
                </div>
        {/foreach}
        <div class="form-group">
            <label class="col-sm-2 control-label">ACL Name</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="name" placeholder="Users" required>
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