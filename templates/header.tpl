<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CDP.me</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
    <nav class="navbar navbar-default navbar-inverse" role="navigation">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="index.php">CDP.me {$version}</a>
            </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li><a href="index.php">Home</a></li>
                    {if $loggedin}
                    <li><a href="index.php?action=backupjobs">Backup Jobs</a></li>
		            <li><a href="index.php?action=backupservers">Servers</a></li>
		            <li><a href="index.php?action=users">Users</a></li>
		            <li><a href="index.php?action=useracl">User ACL</a></li>
		            <li><a href="index.php?action=logout">Log Out</a></li>
                    {/if}
                </ul>
                {if $loggedin}
                <ul class="nav navbar-nav pull-right">
                    <li><a>Welcome back {$smarty.session.user}!</a></li>
                </ul>
                {/if}
            </div>
        </div>
    </nav>