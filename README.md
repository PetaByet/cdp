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
  * Add Users
  * Add ACL's
  * View Backups
  * Download Backups
  * Restore Backups
  * Session security (IP Check + inactivity timeout)
* Json Flat File Database (no MySQL database involved)
* Backups ran using cron
* Server authentication using password or RSA key
* Email notifications (sendmail / SMTP)
* Backup rotation / auto delete
* Full Backups
* Incremental Backups
* MySQL Backups
* OpenVZ Node Backups
* cPanel Account Backups
* 2-Factor Authentication (Google Authenticator)
* Backup Encryption (AES-256/GPG)
* User accounts / ACL's

---------------------------------------
#### Installation
You may use one of the following commands to install CDP.me on your storage/backup server.
The backup server **has** to be running CentOS/Debian/Ubuntu, but the server(s) to be backed up may run any linux distrubution.

`/usr/bin/env bash <((wget -qO - cdp.me/install.sh))`

or

`/usr/bin/env bash <((curl -sL cdp.me/install.sh))`

If your backup server is running a distribution other than the ones above or you do not want to use Apache, you may install CDP.me manually using the wiki guide here https://github.com/PetaByet/cdp/wiki/Manual-Installation

The latest version available can be found [here](https://github.com/PetaByet/cdp/releases).

---------------------------------------
#### System/Software Requirements
* Backup Server (the server to store all backups)
  * Minimal / Fresh OS Installation
  * Linux
  
* Host Server (the server to be backed up)
  * File
    * SSH + SFTP
    * tar
    * *99% of servers meet this requirement*
  * MySQL
    * MySQL 5
  * OpenVZ
    * SSH + SFTP
    * vzdump
    * lvm2
    * It is recommended that /vz is a logical volume
  
---------------------------------------
For more information about CDP.me, please visit our wiki at https://github.com/PetaByet/cdp/wiki
Please report any bugs that you have found here https://github.com/PetaByet/cdp/issues
We no longer maintain a change log, however you may look at the release notes for changes.
  
