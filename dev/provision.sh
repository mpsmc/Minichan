#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

debconf-set-selections <<< "phpmyadmin phpmyadmin/dbconfig-install boolean true"
debconf-set-selections <<< "phpmyadmin phpmyadmin/app-password-confirm password "
debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/admin-pass password "
debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/app-pass password "
debconf-set-selections <<< "phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2"

if [[ ! -f /1G.swap ]]; then
  dd if=/dev/zero of=/1G.swap count=1024 bs=1MiB
  mkswap /1G.swap
  chmod 0600 /1G.swap
  swapon /1G.swap
  echo '/1G.swap none swap sw 0 0' >> /etc/fstab
fi

set -x
if [[ ! -f /etc/apt/sources.list.d/arangodb.list || ! -f /etc/apt/sources.list.d/nodesource.list || ! -f /etc/apt/sources.list.d/rabbitmq.list ]]; then
  echo 'deb http://www.rabbitmq.com/debian/ testing main' > /etc/apt/sources.list.d/rabbitmq.list
  wget -q -O- https://www.arangodb.com/repositories/arangodb31/xUbuntu_16.04/Release.key | apt-key add -
  wget -q -O- https://www.rabbitmq.com/rabbitmq-release-signing-key.asc | apt-key add -
  echo 'deb https://www.arangodb.com/repositories/arangodb31/xUbuntu_16.04/ /' > /etc/apt/sources.list.d/arangodb.list
  curl -sL https://deb.nodesource.com/setup_6.x | bash -
fi

apt-get -y install dos2unix apache2 libapache2-mod-php php php-curl php-bcmath php-mysql php-apcu mysql-server mysql-client phpmyadmin nodejs build-essential htop unzip php-dev libapache2-mpm-itk tmux arangodb3 inotify-tools rabbitmq-server
echo 'update user set plugin="mysql_native_password"; flush privileges' | mysql mysql

function vcp {
  cat "$1" | dos2unix > "$2"
}

vcp /vagrant/dev/phpmyadmin.php /etc/phpmyadmin/conf.d/vagrant.php
vcp /vagrant/dev/minichan.apache /etc/apache2/sites-enabled/minichan.conf
vcp /vagrant/dev/minichan.mysql /etc/mysql/mysql.conf.d/99-minichan.cnf
vcp /vagrant/dev/minichan.php.ini /etc/php/7.0/mods-available/99-minichan.ini
vcp /vagrant/dev/arangod.conf /etc/arangodb3/arangod.conf
chmod +x /opt/webpack.sh

mkdir -p /vagrant/logs
phpenmod 99-minichan
a2enmod rewrite proxy_http
a2dissite 000-default

if [[ ! -d /root/pecl-database-mysql ]]; then
  (
    git clone https://github.com/php/pecl-database-mysql /root/pecl-database-mysql
    cd /root/pecl-database-mysql
    phpize
    ./configure
    make
    make install
  )
fi

echo 'extension=/usr/lib/php/20151012/mysql.so' > /etc/php/7.0/cli/conf.d/99-mysql.ini
echo 'extension=/usr/lib/php/20151012/mysql.so' > /etc/php/7.0/apache2/conf.d/99-mysql.ini

service apache2 restart
service mysql restart
service arangodb restart

if [[ ! -f /bin/composer ]]; then
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php -r "if (hash_file('SHA384', 'composer-setup.php') === 'aa96f26c2b67226a324c27919f1eb05f21c248b987e6195cad9690d5c1ff713d53020a02ac8c217dbf90a7eacc9d141d') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
  php composer-setup.php --install-dir=/bin --filename=composer
  php -r "unlink('composer-setup.php');"
fi

[[ ! -f /usr/bin/webpack ]] && npm install -g webpack

echo "CREATE DATABASE tinybbs_dev" | mysql

echo -e '#!/bin/bash -x\ncd /vagrant\nnpm --no-dist-links install' > /bin/run-npm
echo -e '#!/bin/bash -x\ncd /vagrant\ncomposer install' > /bin/run-composer
echo -e '#!/bin/bash -x\ncd /vagrant\nwebpack -d' > /bin/run-webpack
echo -e '#!/bin/bash -x\ncd /vagrant\nphp includes/upgrade.php' > /bin/run-upgrade
chmod +x /bin/run-*

cd /vagrant

[[ ! -f includes/config.php ]] && cp dev/config.php includes/config.php
run-composer
run-npm
run-webpack
run-upgrade

set +x

echo 'You can now visit http://dev.minichan.org to see the site'
echo '  phpMyAdmin: http://dev.minichan.org/phpmyadmin'
echo '  ArangoDB:   http://dev.minichan.org:8529'
echo '  RabbitMQ:   http://dev.minichan.org:15672'
echo 'You will have to run webpack, npm, composer manually when changing the corresponding files using'
echo '  vagrant ssh -c run-npm      # runs npm --no-dist-links install'
echo '  vagrant ssh -c run-composer # runs composer install'
echo '  vagrant ssh -c run-webpack  # runs webpack -d'
echo '  vagrant ssh -c run-upgrade  # runs php includes/upgrade.php'
echo
echo 'You might want to run webpack -d --watch on your host system if working on JavaScript/CSS (this does not work in the VM due to vagrant folder syncing)'
