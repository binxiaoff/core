#!/usr/bin/env bash

locale-gen fr_FR.UTF-8
ln -sf /usr/share/zoneinfo/Europe/Paris /etc/localtime

# install percona (MySQL)
wget https://repo.percona.com/apt/percona-release_0.1-3.$(lsb_release -sc)_all.deb
dpkg -i percona-release_0.1-3.$(lsb_release -sc)_all.deb
apt-get update > /dev/null
debconf-set-selections <<< 'percona-server-server-5.6 percona-server-server/root_password password ROOTPASSWORD'
debconf-set-selections <<< 'percona-server-server-5.6 percona-server-server/root_password_again password ROOTPASSWORD'
apt-get install -y percona-server-server-5.6

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
rm -rf /vagrant/fixture

# install phpmyadmin
mkdir /vagrant/phpmyadmin/
#wget -O /vagrant/phpmyadmin/index.html http://www.phpmyadmin.net/
#awk 'BEGIN{ RS="<a *href *= *\""} NR>2 {sub(/".*/,"");print; }' /vagrant/phpmyadmin/index.html >> /vagrant/phpmyadmin/url-list.txt
#grep "https://files.phpmyadmin.net/phpMyAdmin/" /vagrant/phpmyadmin/url-list.txt > /vagrant/phpmyadmin/phpmyadmin.url
#sed -i 's/.zip/.tar.bz2/' /vagrant/phpmyadmin/phpmyadmin.url
wget --no-check-certificate --output-document=/vagrant/phpmyadmin/phpMyAdmin.tar.bz2 https://files.phpmyadmin.net/phpMyAdmin/4.6.1/phpMyAdmin-4.6.1-all-languages.tar.bz2 > /dev/null
mkdir /srv/sites/phpmyadmin
tar jxvf /vagrant/phpmyadmin/phpMyAdmin.tar.bz2 -C /srv/sites/phpmyadmin --strip 1 > /dev/null
rm -rf /vagrant/phpmyadmin

# configure phpmyadmin
mv /srv/sites/phpmyadmin/config.sample.inc.php /srv/sites/phpmyadmin/config.inc.php
mysql -uroot -pROOTPASSWORD -e "CREATE DATABASE pma"
mysql -uroot -pROOTPASSWORD -e "CREATE USER 'pma'@'localhost' IDENTIFIED BY 'PMAUSERPASSWD'"
mysql -uroot -pROOTPASSWORD -e "GRANT ALL ON pma.* TO 'pma'@'localhost'"
mysql -uroot -pROOTPASSWORD -e "flush privileges"
ln -fs /vagrant/conf/phpmyadmin.conf.php /srv/sites/phpmyadmin/config.inc.php

# create external user
mysql -uroot -pROOTPASSWORD -e "CREATE USER 'external'@'%' IDENTIFIED BY 'EXTERNALPASSWD'"
mysql -uroot -pROOTPASSWORD -e "GRANT ALL ON unilend.* TO 'external'@'%'"
mysql -uroot -pROOTPASSWORD -e "flush privileges"

# install php
add-apt-repository -y ppa:ondrej/php5-5.6
apt-get update > /dev/null
apt-get install -y php5-fpm php5-mcrypt php5-mysql php5-cli php5-gd php5-curl php5-memcache php5-intl php5-geoip memcached php5-xdebug php5-imagick libssh2-1-dev libssh2-php

# modify php.ini
sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php5/fpm/php.ini
sed -i "s/;session.save_path = .*/session.save_path = \/tmp/" /etc/php5/fpm/php.ini
sed -i "s/session.gc_maxlifetime = .*/session.gc_maxlifetime = 3600/" /etc/php5/fpm/php.ini
sed -i '/;date.timezone =/c date.timezone = "Europe/Paris"' /etc/php5/fpm/php.ini
sed -i "s/display_errors = .*/display_errors = On/" /etc/php5/fpm/php.ini
sed -i "s/html_errors = .*/html_errors = On/" /etc/php5/fpm/php.ini
sed -i "s/upload_max_filesize = .*/upload_max_filesize = 64M/" /etc/php5/fpm/php.ini
sed -i "s/post_max_size = .*/post_max_size = 64M/" /etc/php5/fpm/php.ini
sed -i "s/short_open_tag = Off/short_open_tag = On/" /etc/php5/fpm/php.ini
sed -i "s/max_execution_time = .*/max_execution_time = 300/" /etc/php5/fpm/php.ini
printf '
[xdebug]zend_extension=/usr/lib/php5/20131226/xdebug.so
xdebug.remote_enable=1
xdebug.remote_handler=dbgp
xdebug.remote_mode=req
xdebug.remote_host=127.0.0.1
xdebug.remote_port=9000
xdebug.profiler_enable_trigger=1
xdebug.profiler_output_dir=/vagrant/xdebug
xdebug.profiler_output_name=callgrind.%%t.%%R.cachegrind
xdebug.var_display_max_data=262144
xdebug.var_display_max_depth=10
xdebug.var_display_max_children=1024' >> /etc/php5/apache2/php.ini
sed -i '/;date.timezone =/c date.timezone = "Europe/Paris"' /etc/php5/cli/php.ini
restart php5-fpm

# install Nginx
apt-get install -y nginx
ln -fs /vagrant/conf/vhosts/admin.unilend.fr.conf /etc/nginx/sites-enabled/admin.unilend.fr.conf
ln -fs /vagrant/conf/vhosts/www.unilend.fr.conf /etc/nginx/sites-enabled/www.unilend.fr.conf
ln -fs /vagrant/conf/vhosts/phpmyadmin.conf /etc/nginx/sites-enabled/phpmyadmin.conf
sed -i "s/types_hash_max_size 2048;/types_hash_max_size 2048;\n        fastcgi_read_timeout 300;/" /etc/nginx/nginx.conf

# copy unversioned files
lftp -e 'set ssl:verify-certificate no; mirror /TechTeam/vagrant/files_outside_git  /srv/sites/unilend; bye' -u vagrantftp,X9d\@\$nsa -p 21 synology.corp.unilend.fr
chmod -R u+w /srv/sites/unilend

# install composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/

# install git
apt-get install -y git

# install java, maven and dataloader
add-apt-repository -y ppa:openjdk-r/ppa
apt-get update > /dev/null
apt-get install -y openjdk-8-jdk maven

yes | git clone https://www.github.com/forcedotcom/dataloader.git /srv/dataloader
cd /srv/dataloader
git checkout tags/26.0.0
git submodule init
git submodule update
mvn clean package -DskipTests

#instal wkhtmltopdf for snappy
add-apt-repository -y ppa:ecometrica/servers
apt-get update
apt-get install -y wkhtmltopdf

# install zsh et oh my zsh
apt-get install -y zsh
git clone git://github.com/robbyrussell/oh-my-zsh.git /home/vagrant/.oh-my-zsh
cp /home/vagrant/.oh-my-zsh/templates/zshrc.zsh-template /home/vagrant/.zshrc
sed -i 's/ZSH_THEME="robbyrussell"/ZSH_THEME="pygmalion"/g' /home/vagrant/.zshrc
sed -i 's/plugins=.*/plugins=(git colored-man colorize github jira vagrant zsh-syntax-highlighting)/' /home/vagrant/.zshrc
printf "\nalias composer=\"/usr/bin/composer.phar\"" >> /home/vagrant/.zshrc
chsh -s /bin/zsh vagrant

# install mailcatcher
apt-get install -y build-essential
apt-add-repository -y ppa:brightbox/ruby-ng
apt-get update > /dev/null
apt-get install -y ruby-all-dev ruby-switch libsqlite3-dev
ruby-switch --set ruby2.0
gem install mailcatcher
sed -i '/;sendmail_path =/c sendmail_path = /usr/bin/env /usr/local/bin/catchmail' /etc/php5/fpm/php.ini
sed -i '/;sendmail_path =/c sendmail_path = /usr/bin/env /usr/local/bin/catchmail' /etc/php5/cli/php.ini
ln -fs /vagrant/conf/vhosts/mailcatcher.conf /etc/nginx/sites-enabled/mailcatcher.conf
cp /vagrant/conf/mailcatcher.conf /etc/init/mailcatcher.conf

#increase swap memory
/bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024
/sbin/mkswap /var/swap.1
/sbin/swapon /var/swap.1