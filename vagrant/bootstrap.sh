#!/usr/bin/env bash

#install percona (mysql)
apt-key adv --keyserver keys.gnupg.net --recv-keys 1C4CBDCDCD2EFD2A
add-apt-repository "deb http://repo.percona.com/apt precise main"
add-apt-repository "deb-src http://repo.percona.com/apt precise main"
apt-get update
debconf-set-selections <<< 'percona-server-server-5.5 percona-server-server/root_password password ROOTPASSWORD'
debconf-set-selections <<< 'percona-server-server-5.5 percona-server-server/root_password_again password ROOTPASSWORD'
apt-get install -y percona-server-server-5.5

if [ -f /vagrant/fixture/schemas.sql ];
    then
        echo "CREATE DATABASE unilend" | mysql -uroot -pROOTPASSWORD
        mysql -uroot -pROOTPASSWORD unilend < /vagrant/fixture/schemas.sql
        cat /vagrant/fixture/unilend.*.sql | mysql -uroot -pROOTPASSWORD unilend
fi

# install phpmyadmin
mkdir /vagrant/phpmyadmin/
wget -O /vagrant/phpmyadmin/index.html http://www.phpmyadmin.net/
awk 'BEGIN{ RS="<a *href *= *\""} NR>2 {sub(/".*/,"");print; }' /vagrant/phpmyadmin/index.html >> /vagrant/phpmyadmin/url-list.txt
grep "https://files.phpmyadmin.net/phpMyAdmin/" /vagrant/phpmyadmin/url-list.txt > /vagrant/phpmyadmin/phpmyadmin.url
sed -i 's/.zip/.tar.bz2/' /vagrant/phpmyadmin/phpmyadmin.url
wget --no-check-certificate --output-document=/vagrant/phpmyadmin/phpMyAdmin.tar.bz2 `cat /vagrant/phpmyadmin/phpmyadmin.url`
mkdir /srv/sites/phpmyadmin
tar jxvf /vagrant/phpmyadmin/phpMyAdmin.tar.bz2 -C /srv/sites/phpmyadmin --strip 1
rm -rf /vagrant/phpmyadmin

# configure phpmyadmin
mv /srv/sites/phpmyadmin/config.sample.inc.php /srv/sites/phpmyadmin/config.inc.php
echo "CREATE DATABASE pma" | mysql -uroot -pROOTPASSWORD
echo "CREATE USER 'pma'@'localhost' IDENTIFIED BY 'PMAUSERPASSWD'" | mysql -uroot -pROOTPASSWORD
echo "GRANT ALL ON pma.* TO 'pma'@'localhost'" | mysql -uroot -pROOTPASSWORD
#echo "GRANT ALL ON phpmyadmin.* TO 'pma'@'localhost'" | mysql -uroot -pROOTPASSWORD
echo "flush privileges" | mysql -uroot -pROOTPASSWORD
cat /vagrant/conf/phpmyadmin.conf.php > /srv/sites/phpmyadmin/config.inc.php

#install apache2
apt-get install -y apache2
a2enmod deflate
a2enmod filter
a2enmod ssl
#if ! [ -L /var/www ]; then
#rm -rf /var/www
#ln -fs /srv/sites /var/www
a2enmod rewrite
ln -fs /vagrant/conf/vhosts/admin.unilend.fr.conf /etc/apache2/sites-enabled/admin.unilend.fr.conf
ln -fs /vagrant/conf/vhosts/www.unilend.fr.conf /etc/apache2/sites-enabled/www.unilend.fr.conf
ln -fs /vagrant/conf/vhosts/phpmyadmin.conf /etc/apache2/sites-enabled/phpmyadmin.conf
sed -i '/Listen 443/c Listen 443\n    NameVirtualHost *:443' /etc/apache2/ports.conf
echo "ServerName localhost" >> /etc/apache2/httpd.conf
service apache2 restart
update-rc.d apache2 defaults
#fi

# install php
apt-get install -y php5 libapache2-mod-php5 php5-mcrypt php5-mysql php5-cli php5-gd php5-curl php5-memcache php5-intl php5-geoip
sudo sed -i '/;session.save_path = "\/tmp"/c session.save_path = "\/tmp"' /etc/php5/apache2/php.ini
sudo sed -i '/session.gc_maxlifetime = 1440/c session.gc_maxlifetime = 3600' /etc/php5/apache2/php.ini

locale-gen fr_FR.UTF-8

service apache2 restart