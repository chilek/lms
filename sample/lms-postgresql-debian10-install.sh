#!/bin/bash

#LMS Dependencies
apt install -y php7.3 apache2 postgresql php-gd git bash-completion net-tools patch wget mtr php-pgsql php-bcmath php-soap php-snmp php-imap composer libdbi-perl libconfig-inifiles-perl libdbd-pg-perl php-pear makepasswd sudo bsd-mailx php-gmp

dbname='lmsdb'
dbuser='lmsdbuser'
dbpass=`makepasswd --chars=12`
lmsdir='/var/www/html/lms'
apacheuser='www-data'
inifile='/etc/lms/lms.ini'
fqdn=`hostname -f`
a2site='/etc/apache2/sites-available/lms.conf'

apt update
apt dist-upgrade -y

#System settings
echo "pl_PL.UTF-8 UTF-8" > /etc/locale.gen
echo "en_GB.UTF-8 UTF-8" >> /etc/locale.gen
echo "LC_ALL=pl_PL.UTF-8" >> /etc/default/locale
locale-gen

#PHP Settings
phpinifile=`find /etc/php/ -wholename '*apache2/php.ini'`
sed -i 's|;date.timezone =|data.timezone = Europe/Warsaw|g' ${phpinifile}

#LMS APP
git clone https://github.com/lmsgit/lms ${lmsdir}
mkdir -p $lmsdir/{backups,documents,templates_c,userpanel/templates_c,rtattachments,img/xajax_js/deferred}
chmod o-rwx -R $lmsdir/{backups,documents,templates_c,userpanel/templates_c,rtattachments,img/xajax_js/deferred}
chown $apacheuser:$apacheuser -R $lmsdir/{backups,documents,templates_c,userpanel/templates_c,rtattachments,img/xajax_js/deferred}
cd ${lmsdir}; composer update --no-dev

#LMS DB APP
pgconf=`find /etc/postgresql -name 'pg_hba.conf' | tail -1`
su - postgres -c "/usr/bin/createuser -s -e -w ${dbuser}"
su - postgres -c "/usr/bin/psql -U postgres -d postgres -c \"ALTER user ${dbuser} WITH PASSWORD '${dbpass}'\" "
su - postgres -c "/usr/bin/createdb -E UTF8 -O ${dbuser} ${dbname};"
touch ~/.pgpass
chmod 600 ~/.pgpass
echo "localhost:*:${dbname}:${dbuser}:${dbpass}" > ~/.pgpass
su --shell="/bin/bash" postgres -c "cat ${lmsdir}/doc/lms.pgsql|/usr/bin/psql \"${dbname}\" "
su - postgres -c "cp ${pgconf} ${pgconf}.bak"
su - postgres -c "echo local   all             all                                     md5   > ${pgconf}"
su - postgres -c "echo host    all             all             127.0.0.1/32            ident >> ${pgconf}"
su - postgres -c "echo host    all             all             ::1/128                 ident >> ${pgconf}"
systemctl restart postgresql

#LMS CONFIG
mkdir /etc/lms
cat <<EOF >> ${inifile}
[database]
type = postgres
host = ''
user = ${dbuser}
password = ${dbpass}
database = ${dbname}

[directory]
sysdir = ${lmsdir}
backup_dir = ${lmsdir}/backups
doc_dir = ${lmsdir}/documents
smarty_compile_dir = ${lmsdir}/templates_c
userpanel_dir = ${lmsdir}/userpanel

[rt]
mail_dir = ${lmsdir}/rtattachements
EOF

#HTTPD CONFIG
cp -f ${lmsdir}/sample/lms-main.apache24.conf ${a2site}
sed -i 's|DocumentRoot /var/www/html/lms|DocumentRoot ${lmsdir}|g' ${a2site}
sed -i 's|ServerName lms.org.pl|ServerName ${fqdn}|g' ${a2site}
sed -i 's|logs/lms.org.pl-error_log|/var/log/apache2/${fqdn}-error_log|g' ${a2site}
sed -i 's|logs/lms.org.pl-access_log|/var/log/apache2/${fqdn}-access_log|g' ${a2site}
sed -i 's|Directory "/var/www/html/lms"|Directory "${lmsdir}"|g' ${a2site}

a2ensite lms
systemctl reload apache2
