#!/bin/bash
# Installe MariaDB dans le Codespace et crée le compte d'administration
# utilisé par installer.php, appliquer.php et verifier.php.
set -e
sudo apt-get update -qq
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mariadb-server mariadb-client
sudo service mariadb start
# extension PDO MySQL pour le PHP de l'image (sans effet si déjà présente)
php -m | grep -qi pdo_mysql || sudo docker-php-ext-install pdo_mysql
sudo mariadb -e "CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'admin';
                 GRANT ALL PRIVILEGES ON *.* TO 'admin'@'localhost' WITH GRANT OPTION;"
echo "MariaDB prête. Lancez : php installer.php"
