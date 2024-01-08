#!/bin/bash

#LMS Dependencies
apt install -y php apache2 postgresql git bash-completion net-tools patch wget mtr \
  php-{intl,gd,pgsql,bcmath,soap,snmp,imap,gmp,iconv,mailparse,zip,curl,imagick,pear,xml} \
  libdbi-perl libconfig-inifiles-perl libdbd-pg-perl makepasswd sudo bsd-mailx

apt update
apt dist-upgrade -y

DBNAME='lmsdb'
DBUSER='lmsdbuser'
DBPASS=`makepasswd --chars=12`
INIFILE='/etc/lms/lms.ini'
FQDN=`hostname -f`
APACHEUSER='www-data'
A2SITE='/etc/apache2/sites-available/lms.conf'
PHPINIFILE=`find /etc/php/ -wholename '*apache2/php.ini'`
LMSDIR='/var/www/html/lms'
USERPANELDIR="${LMSDIR}/userpanel"

#System settings
echo -e "pl_PL.UTF-8 UTF-8\nen_GB.UTF-8 UTF-8" > /etc/locale.gen
echo "LC_ALL=pl_PL.UTF-8" >> /etc/default/locale
rm /etc/localtime
ln -s /usr/share/zoneinfo/Europe/Warsaw /etc/localtime
locale-gen

#PHP Settings
sed -i -e 's|;date.timezone =|data.timezone = Europe/Warsaw|g' -e 's|;max_input_vars = 1000|max_input_vars = 100000|g' -e 's|max_execution_time = 60|max_execution_time = 600|g' -e 's|memory_limit = 128M|memory_limit = 2G|g' -e 's|post_max_size = 8M|post_max_size = 20M|g' -e 's|upload_max_filesize = 2M|upload_max_filesize = 15M|g' ${PHPINIFILE}

#LMS APP
git clone https://github.com/lmsgit/lms ${LMSDIR}

#DIR PRIVILGES
cd ${LMSDIR}
for dir in 'backups cache documents templates_c userpanel/templates_c rtattachments js/xajax_js/deferred storage/rt storage/voipcalls storage/customercalls storage/promotions storage/promotionschemas'
do
    mkdir -p $dir
    chmod o-rwx -R $dir
    chown ${APACHEUSER}:${APACHEUSER} -R $dir
done

#install composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

php composer.phar update --no-dev
cd ${USERPANELDIR}/style/bclean; php composer.phar update --no-dev

#LMS DB APP
PGCONF=`find /etc/postgresql -type f -name 'pg_hba.conf' | tail -1`
cp ${PGCONF} ${PGCONF}.bak

su - postgres -c cat <<EOF > ${PGCONF}
local   all             postgres                                peer
local   all             all                                     md5
host    all             all             127.0.0.1/32            ident
host    all             all             ::1/128                 ident
EOF

systemctl restart postgresql

sudo su - postgres << EOF
psql -d postgres -c "UPDATE pg_database SET datistemplate = FALSE WHERE datname = 'template1'"
dropdb template1
createdb template1 --encoding=UNICODE --template=template0
psql -c "UPDATE pg_database SET datistemplate = TRUE WHERE datname = 'template1' "
psql -d template1 -c 'VACUUM FREEZE'
createuser -s -e -w "${DBUSER}"
psql -d postgres -c "ALTER user ${DBUSER} WITH PASSWORD '${DBPASS}'"
createdb -E UTF8 -O ${DBUSER} ${DBNAME}
echo "localhost:*:${DBNAME}:${DBUSER}:${DBPASS}" > ~/.pgpass
chmod 600 ~/.pgpass
psql ${DBNAME} -f ${LMSDIR}/doc/lms.pgsql
EOF

#LMS CONFIG
mkdir /etc/lms
cat <<EOF >> ${INIFILE}
[database]
type = postgres
host = ''
user = ${DBUSER}
password = ${DBPASS}
database = ${DBNAME}

[directories]
sys_dir = ${LMSDIR}
backup_dir = ${LMSDIR}/backups

[rt]
mail_dir = ${LMSDIR}/rtattachements
EOF

#HTTPD CONFIG
cp -f ${LMSDIR}/sample/lms.apache24.conf ${A2SITE}
sed -i -e "s|DocumentRoot /var/www/html/lms|DocumentRoot ${LMSDIR}|g" -e "s|ServerName lms.org.pl|ServerName ${FQDN}|g" -e "s|logs/lms.org.pl-error_log|/var/log/apache2/${FQDN}-error_log|g" -e "s|logs/lms.org.pl-access_log|/var/log/apache2/${FQDN}-access_log|g" -e "s|Directory \"/var/www/html/lms\"|Directory \"${LMSDIR}\"|g" ${A2SITE}

a2ensite lms
systemctl reload apache2
