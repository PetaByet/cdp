        <div class="container"><h2>Restore Backup</h2>
            {if isset($smarty.get.step) and $smarty.get.step eq 1}
        <p>Choose a server to restore this backup to:</p>
            <form action="index.php" method="get">
                <input type="hidden" name="action" value="backuprestore">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="id" value="{$smarty.get.id}">
                <select name="server">
                    {foreach $backupservers as $backupserver}
                        {if $backupserver.authtype eq 'password' || $backupserver.authtype eq 'key'}
                            {if $jobdetail.type eq 'full' || $jobdetail.type eq 'incremental' || $jobdetail.type eq 'openvz'}
                                <option value="{$backupserver.host}">{$backupserver.host}</option>
                            {/if}
                        {elseif $backupserver.authtype eq 'mysql' and $jobdetail.type eq 'mysql'}
                            <option value="{$backupserver.host}">{$backupserver.host}</option>
                        {elseif $backupserver.authtype eq 'cpanel' and $jobdetail.type eq 'cpanel'}
                            <option value="{$backupserver.host}">{$backupserver.host}</option>
                        {/if}
                    {/foreach}
                </select>
                <button type="submit" class="btn btn-primary">Continue</button>
            </form>
            {elseif isset($smarty.get.step) and $smarty.get.step eq 2 and isset($smarty.get.server)}
            <a id="initiatebackup" class="btn btn-success">Start backup restore.</a>
            <textarea class="form-control" rows="40" cols="75" id="output" readonly>
            </textarea>
            {literal}
            <script>
            //<![CDATA[
            $(document).ready(function(){
                $("#initiatebackup").click(function(){
                    $("#initiatebackup").prop("disabled",true);
                    $("#initiatebackup").text('Initiating backup restore...');
                    $.get("index.php?action=backuprestore&restoreaction=initiate&id={/literal}{$smarty.get.id}{literal}&host={/literal}{$smarty.get.server}{literal}", function( tmpfilename ) {
                        interval = window.setInterval(function(){
                            $("#initiatebackup").text('Restoring...');
                            $("#output").load("index.php?action=backuprestore&restoreaction=readtmpfile&tmpfilename="+tmpfilename);
                            var successmsg = 'Success! Backup restored.';
                            var output = $("#output").val();
                            if(successmsg.indexOf(output) != -1){
                                clearInterval(interval);
                                $("#initiatebackup").text('Backup restored...');
                            }
                        }, 5000);
                    });
                });
            });
            //]]>
            </script>
            {/literal}
            {/if}
        </div>