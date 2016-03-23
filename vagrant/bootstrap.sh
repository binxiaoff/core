#!/usr/bin/env bash

locale-gen fr_FR.UTF-8
ln -sf /usr/share/zoneinfo/Europe/Paris /etc/localtime

# install percona (MySQL)
apt-key adv --keyserver 213.133.103.71 --recv-keys 1C4CBDCDCD2EFD2A #keys.gnupg.net
add-apt-repository "deb http://repo.percona.com/apt precise main"
add-apt-repository "deb-src http://repo.percona.com/apt precise main"
apt-get update
debconf-set-selections <<< 'percona-server-server-5.5 percona-server-server/root_password password ROOTPASSWORD'
debconf-set-selections <<< 'percona-server-server-5.5 percona-server-server/root_password_again password ROOTPASSWORD'
apt-get install -y percona-server-server-5.5

# install lftp for download database
apt-get install -y lftp

lftp -e 'set ssl:verify-certificate no; mirror /TechTeam/vagrant/fixture /vagrant/fixture; bye' -u vagrantftp,X9d\@\$nsa -p 21 synology.corp.unilend.fr
lftp -e 'set ssl:verify-certificate no; mirror /TechTeam/vagrant/database /vagrant/database; bye' -u vagrantftp,X9d\@\$nsa -p 21 synology.corp.unilend.fr

if [ -f /vagrant/database/schemas.sql ];
then
    mysql -uroot -pROOTPASSWORD -e "CREATE DATABASE unilend"
    mysql -uroot -pROOTPASSWORD unilend < /vagrant/database/schemas.sql
    rm -f /vagrant/database/schemas.sql
    for sql in /vagrant/database/*.sql
    do
        echo "Import $sql"
        mysql -uroot -pROOTPASSWORD --max_allowed_packet=64M unilend < $sql
    done
    mysql -uroot -pROOTPASSWORD unilend < /vagrant/anonymize.sql
    cat /vagrant/fixture/*.sql | mysql -uroot -pROOTPASSWORD unilend
fi
rm -rf /vagrant/database

# install phpmyadmin 4.4.15 (last version compatible php 5.3)
mkdir /vagrant/phpmyadmin/
#wget -O /vagrant/phpmyadmin/index.html http://www.phpmyadmin.net/
#awk 'BEGIN{ RS="<a *href *= *\""} NR>2 {sub(/".*/,"");print; }' /vagrant/phpmyadmin/index.html >> /vagrant/phpmyadmin/url-list.txt
#grep "https://files.phpmyadmin.net/phpMyAdmin/" /vagrant/phpmyadmin/url-list.txt > /vagrant/phpmyadmin/phpmyadmin.url
#sed -i 's/.zip/.tar.bz2/' /vagrant/phpmyadmin/phpmyadmin.url
wget --no-check-certificate --output-document=/vagrant/phpmyadmin/phpMyAdmin.tar.bz2 https://files.phpmyadmin.net/phpMyAdmin/4.4.15/phpMyAdmin-4.4.15-all-languages.tar.bz2
mkdir /srv/sites/phpmyadmin
tar jxvf /vagrant/phpmyadmin/phpMyAdmin.tar.bz2 -C /srv/sites/phpmyadmin --strip 1
rm -rf /vagrant/phpmyadmin

# configure phpmyadmin
mv /srv/sites/phpmyadmin/config.sample.inc.php /srv/sites/phpmyadmin/config.inc.php
mysql -uroot -pROOTPASSWORD -e "CREATE DATABASE pma"
mysql -uroot -pROOTPASSWORD -e "CREATE USER 'pma'@'localhost' IDENTIFIED BY 'PMAUSERPASSWD'"
mysql -uroot -pROOTPASSWORD -e "GRANT ALL ON pma.* TO 'pma'@'localhost'"
mysql -uroot -pROOTPASSWORD -e "flush privileges"
cat /vagrant/conf/phpmyadmin.conf.php > /srv/sites/phpmyadmin/config.inc.php

# create external user
mysql -uroot -pROOTPASSWORD -e "CREATE USER 'external'@'%' IDENTIFIED BY 'EXTERNALPASSWD'"
mysql -uroot -pROOTPASSWORD -e "GRANT ALL ON unilend.* TO 'external'@'%'"
mysql -uroot -pROOTPASSWORD -e "flush privileges"

# install apache2
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
sed -i "s/post_max_size = .*/post_max_size = 64M/" /etc/php5/apache2/php.ini
printf "\n[xdebug]\nzend_extension=/usr/lib/php5/20090626/xdebug.so\nxdebug.remote_enable=1\nxdebug.remote_handler=dbgp\nxdebug.remote_mode=req\nxdebug.remote_host=127.0.0.1\nxdebug.remote_port=9000\nxdebug.profiler_enable_trigger=1\nxdebug.profiler_output_dir=/vagrant/xdebug\nxdebug.profiler_output_name=callgrind.%t.%R.cachegrind\nxdebug.var_display_max_data=65536\nxdebug.var_display_max_depth=10\nxdebug.var_display_max_children=1024\n" >> /etc/php5/apache2/php.ini

service apache2 restart

# copy unversioned files
lftp -e 'set ssl:verify-certificate no; mirror /TechTeam/vagrant/files_outside_git  /srv/sites/unilend; bye' -u vagrantftp,X9d\@\$nsa -p 21 synology.corp.unilend.fr
chmod -R u+w /srv/sites/unilend

# install composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/

# install java, maven et dataloader
add-apt-repository -y ppa:openjdk-r/ppa
apt-get update
apt-get install -y openjdk-8-jdk maven git

yes | git clone https://www.github.com/forcedotcom/dataloader.git /srv/dataloader
cd /srv/dataloader
git checkout tags/26.0.0
git submodule init
git submodule update
mvn clean package -DskipTests

#instal wkhtmltopdf for snappy
apt-get install xfonts-75dpi
wget http://download.gna.org/wkhtmltopdf/0.12/0.12.2.1/wkhtmltox-0.12.2.1_linux-trusty-amd64.deb
dpkg -i wkhtmltox-0.12.2.1_linux-trusty-amd64.deb
ln -s /usr/local/bin/wkhtmltopdf /usr/bin/wkhtmltopdf
rm -rf wkhtmltox-0.12.2.1_linux-trusty-amd64.deb

# install zsh et oh my zsh
apt-get install -y zsh
git clone git://github.com/robbyrussell/oh-my-zsh.git /home/vagrant/.oh-my-zsh
cp /home/vagrant/.oh-my-zsh/templates/zshrc.zsh-template /home/vagrant/.zshrc
sed -i 's/ZSH_THEME="robbyrussell"/ZSH_THEME="pygmalion"/g' /home/vagrant/.zshrc
sed -i 's/plugins=.*/plugins=(git colored-man colorize github jira vagrant zsh-syntax-highlighting)/' /home/vagrant/.zshrc
printf "\nalias composer=\"/usr/bin/composer.phar\"" >> /home/vagrant/.zshrc
chsh -s /bin/zsh vagrant

# install mailcatcher
apt-get install -y software-properties-common
apt-get remove -y ruby1.8
apt-get install -y ruby1.9.3 libsqlite3-dev
gem install mailcatcher
sed -i '/;sendmail_path =/c sendmail_path = /usr/bin/env catchmail' /etc/php5/apache2/php.ini
a2enmod proxy proxy_http
ln -fs /vagrant/conf/vhosts/mailcatcher.conf /etc/apache2/sites-enabled/mailcatcher.conf
cp /vagrant/conf/mailcatcher.conf /etc/init/mailcatcher.conf