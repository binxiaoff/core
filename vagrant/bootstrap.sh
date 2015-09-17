#!/usr/bin/env bash

locale-gen fr_FR.UTF-8

#install percona (mysql)
apt-key adv --keyserver 213.133.103.71 --recv-keys 1C4CBDCDCD2EFD2A #keys.gnupg.net
add-apt-repository "deb http://repo.percona.com/apt precise main"
add-apt-repository "deb-src http://repo.percona.com/apt precise main"
apt-get update
debconf-set-selections <<< 'percona-server-server-5.5 percona-server-server/root_password password ROOTPASSWORD'
debconf-set-selections <<< 'percona-server-server-5.5 percona-server-server/root_password_again password ROOTPASSWORD'
apt-get install -y percona-server-server-5.5

# install lftp for download fixture
apt-get install -y lftp

lftp -e 'set ssl:verify-certificate no; mirror /TechTeam/vagrant/fixture  /vagrant/fixture; bye' -u vagrantftp,X9d\@\$nsa -p 21 synology.corp.unilend.fr

if [ -f /vagrant/fixture/schemas.sql ];
then
    echo "CREATE DATABASE unilend" | mysql -uroot -pROOTPASSWORD
    mysql -uroot -pROOTPASSWORD unilend < /vagrant/fixture/schemas.sql
    cat /vagrant/fixture/unilend.*.sql | mysql -uroot -pROOTPASSWORD unilend
fi
rm -rf /vagrant/fixture

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
echo "flush privileges" | mysql -uroot -pROOTPASSWORD
cat /vagrant/conf/phpmyadmin.conf.php > /srv/sites/phpmyadmin/config.inc.php

#install apache2
apt-get install -y apache2
a2enmod deflate
a2enmod filter
a2enmod ssl
a2enmod rewrite
ln -fs /vagrant/conf/vhosts/admin.unilend.fr.conf /etc/apache2/sites-enabled/admin.unilend.fr.conf
ln -fs /vagrant/conf/vhosts/www.unilend.fr.conf /etc/apache2/sites-enabled/www.unilend.fr.conf
ln -fs /vagrant/conf/vhosts/phpmyadmin.conf /etc/apache2/sites-enabled/phpmyadmin.conf
sed -i '/Listen 443/c Listen 443\n    NameVirtualHost *:443' /etc/apache2/ports.conf
echo "ServerName localhost" >> /etc/apache2/httpd.conf
service apache2 restart
update-rc.d apache2 defaults

# install php
apt-get install -y php5 libapache2-mod-php5 php5-mcrypt php5-mysql php5-cli php5-gd php5-curl php5-memcache php5-intl php5-geoip memcached php5-xdebug php5-imagick

# modify php.ini
sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php5/apache2/php.ini
sed -i "s/;session.save_path = .*/session.save_path = \/tmp/" /etc/php5/apache2/php.ini
sed -i "s/session.gc_maxlifetime = .*/session.gc_maxlifetime = 3600/" /etc/php5/apache2/php.ini
sed -i '/;date.timezone =/c date.timezone = "Europe/Paris"' /etc/php5/apache2/php.ini
sed -i "s/display_errors = .*/display_errors = On/" /etc/php5/apache2/php.ini
sed -i "s/html_errors = .*/html_errors = On/" /etc/php5/apache2/php.ini
sed -i "s/upload_max_filesize = .*/upload_max_filesize = 64M/" /etc/php5/apache2/php.ini
sed -i "/post_max_size =/c post_max_size = 64M \nzend_extension=/usr/lib/php5/20090626/xdebug.so \nxdebug.remote_enable=1 \nxdebug.remote_handler=dbgp \nxdebug.remote_mode=req \nxdebug.remote_host=127.0.0.1 \nxdebug.remote_port=9000/" /etc/php5/apache2/php.ini

service apache2 restart

#copy unversioned files
lftp -e 'set ssl:verify-certificate no; mirror /TechTeam/vagrant/files_outside_git  /srv/sites/unilend; bye' -u vagrantftp,X9d\@\$nsa -p 21 synology.corp.unilend.fr
chmod -R u+w /srv/sites/unilend

#install composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/