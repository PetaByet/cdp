#!/usr/bin/env bash

# CDP.me Installer
# Copyright (C) PetaByet.com / CDP.me All Rights Reserved

if [ -f /etc/redhat-release ]; then
        os=`cat /etc/redhat-release`
else
        echo 'OS not supported...exiting'
        exit
fi

sleep 1
echo "Welcome!"
echo
echo "Please make sure that your server"
echo "is running a fresh and minimal OS Installation"

echo "Press [Enter] key to start the installation or CTRL+C to abort..."
read -s -n 1 readEnterKey

while [ "$readEnterKey" != "" ]
do
    echo "Press [Enter] key to start the installation or CTRL+C to abort..."
    read -s -n 1 readEnterKey
done

arch=$(uname -a)

echo "Installing EPEL..."

if [[ $arch == *x86_64* ]]
then
    wget http://download.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm
    rpm -ivh epel-release-6-8.noarch.rpm
else
    wget http://download.fedoraproject.org/pub/epel/6/i386/epel-release-6-8.noarch.rpm
    rpm -ivh epel-release-6-8.noarch.rpm
fi

echo "Updating your system..."
yum -y -q update

echo "Installing required packages..."
yum -y -q install git httpd php php-cli php-mcrypt

cd /var/www/html

echo "Downloading CDP.me main package..."

git clone https://github.com/PetaByet/cdp.git

rm -f install.sh

mv htaccess.txt .htaccess

service httpd restart

echo "CDP.me has been successfully installed"
echo "The default login detail is:"
echo "Username: admin"
echo "Password: password"
echo "You may update the username and password"
echo "by editing config.php"

echo "Thank you for choosing CDP.me!"