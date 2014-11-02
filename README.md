CDP.me (Continuous Data Protection)
===
---------------------------------------
#### About
CDP.me is a backup script designed for Linux Server backups.

CDP.me is released under GNU GPL v2. By downloading, using and editing CDP.me you must agree to the terms set forth in the license provided.

---------------------------------------
#### Features 
* Web Based Administration Panel
  * Add Backup Servers
  * Add Backup Jobs
  * View Backups
  * Download Backups
  * Restore Backups
* Json Flat File Database (no MySQL involved)
* Backups ran using cron
* Server authentication using password or key
* Email notifications

---------------------------------------
#### Installation
You may use the following command to install CDP.me on your storage/backup server.
The backup server **has** to be running CentOS/Debian/Ubuntu, but the server(s) to be backed up may run any linux distrubution.
`wget cdp.me/install.sh && chmod +x install.sh && ./install.sh`

If your backup server is running a distribution other than the ones above or you do not want to use Apache, you may install CDP.me manually using the wiki guide here https://github.com/PetaByet/cdp/wiki/Manual-Installation

---------------------------------------
#### System/Software Requirements
* Backup Server (the server to store all backups)
  * Minimal / Fresh OS Installation
  * Linux
* Source Server (the server to be backed up)
  * SSH + SFTP
  * tar
  * *99% of servers meet this requirement*
  
---------------------------------------
For more information about CDP.me, please visit our wiki at https://github.com/PetaByet/cdp/wiki
Please report any bugs that you have found here https://github.com/PetaByet/cdp/issues
  