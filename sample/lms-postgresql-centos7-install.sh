#!/bin/bash

# Use a CentOS 7.x Minimal Server version.
# Prepare config section.
# From root account run: bash ./lms-postgresql-centos7-install.sh


#####config###
enable_ssl=no

FQDN=lms.example.com
WEBMASTER_EMAIL=hostmaster@example.com
LMS_DIR=/var/www/html/lms

backup_dir=/mnt/backup/lms

shell_user=lms
shell_group=lms
shell_password=$(< /dev/urandom tr -dc _A-Z-a-z-0-9 | head -c8)

lms_db_host=localhost
lms_db_user=lms
lms_db_password=$(< /dev/urandom tr -dc _A-Z-a-z-0-9 | head -c16)
lms_db=lms

#####install#####
yum install httpd -y

sed  's/^\([^#]\)/#\1/g' -i /etc/httpd/conf.d/welcome.conf
touch /var/www/html/index.html

yum install postgresql-server postgresql-contrib -y
postgresql-setup initdb
systemctl start postgresql
systemctl enable postgresql

yum install php -y
yum install php-pgsql -y
yum install php-gd -y
yum install php-mbstring -y
yum install php-posix -y
yum install php-bcmath -y
yum install php-xml -y
yum install bison-* -y
yum install flex -y
yum install flex-devel -y
yum install unzip -y
yum install mod_ssl -y
yum install perl-Config-IniFiles -y
yum install perl-Mail-Sender -y
yum install wget -y
yum install policycoreutils-python -y
yum install setroubleshoot -y
yum groupinstall "Development Tools" -y

echo "date.timezone =Europe/Warsaw" >> /etc/php.ini

mkdir /etc/lms
touch /etc/lms/lms.ini

echo "[database]" >> /etc/lms/lms.ini
echo "type = postgres" >> /etc/lms/lms.ini
echo "host = $lms_db_host" >> /etc/lms/lms.ini
echo "user = $lms_db_user" >> /etc/lms/lms.ini
echo "password = $lms_db_password" >> /etc/lms/lms.ini
echo "database = $lms_db" >> /etc/lms/lms.ini

echo "[directories]" >> /etc/lms/lms.ini
echo "sys_dir          = $LMS_DIR" >> /etc/lms/lms.ini
echo "backup_dir       = $backup_dir" >> /etc/lms/lms.ini
echo "userpanel_dir  = $LMS_DIR/userpanel" >> /etc/lms/lms.ini

mkdir -p $backup_dir
chown -R 48:48 $backup_dir
chmod -R 755 $backup_dir

useradd $shell_user
echo "$shell_user:$shell_password" |chpasswd
mkdir $LMS_DIR
chown $shell_user.$shell_group $LMS_DIR

su $shell_user -c "cd /var/www/html; git clone https://github.com/lmsgit/lms.git"
su $shell_user -c "cd $LMS_DIR; curl -sS https://getcomposer.org/installer | php"
su $shell_user -c "cd $LMS_DIR; $LMS_DIR/composer.phar install"

mkdir $LMS_DIR/img/xajax_js/deferred
chown -R 48:48 $LMS_DIR/templates_c
chmod -R 755 $LMS_DIR/templates_c
chown -R 48:48 $LMS_DIR/backups
chmod -R 755 $LMS_DIR/backups
chown -R 48:48 $LMS_DIR/documents
chmod -R 755 $LMS_DIR/documents
chown -R 48:48 $LMS_DIR/img/xajax_js/deferred
chmod -R 755 $LMS_DIR/img/xajax_js/deferred
chown 48:48 $LMS_DIR/userpanel/templates_c
chmod 755 $LMS_DIR/userpanel/templates_c

touch /etc/httpd/conf.d/lms.conf

echo "<VirtualHost *:80>" >> /etc/httpd/conf.d/lms.conf
echo "    ServerAdmin $WEBMASTER_EMAIL" >> /etc/httpd/conf.d/lms.conf
echo "    DocumentRoot /var/www/html/lms" >> /etc/httpd/conf.d/lms.conf
echo "    ServerName $FQDN" >> /etc/httpd/conf.d/lms.conf
echo "    ErrorLog logs/$FQDN-error_log" >> /etc/httpd/conf.d/lms.conf
echo "    CustomLog logs/$FQDN-access_log common" >> /etc/httpd/conf.d/lms.conf
echo "</VirtualHost>" >> /etc/httpd/conf.d/lms.conf

su - postgres -c "createuser -DRS $lms_db_user"
su - postgres -c "createdb -E UNICODE -O $lms_db_user $lms_db"
su - postgres -c "psql -U postgres -d postgres -c \"alter user $lms_db_user with password '$lms_db_password';\""
su - $shell_user -c "psql -f $LMS_DIR/doc/lms.pgsql"
su - postgres -c "cp data/pg_hba.conf data/pg_hba.conf.bak"
su - postgres -c "echo local   all             all                                     md5   >data/pg_hba.conf"
su - postgres -c "echo host    all             all             127.0.0.1/32            ident >>data/pg_hba.conf"
su - postgres -c "echo host    all             all             ::1/128                 ident >>data/pg_hba.conf"

systemctl restart postgresql
systemctl restart httpd.service
systemctl enable httpd.service

firewall-cmd --zone=public --add-service=http
firewall-cmd --zone=public --permanent --add-service=http

selinux_status=$(getenforce)

if [ $selinux_status == Enforcing ]
then
  wget http://$FQDN
  ausearch -c 'httpd' --raw | audit2allow -M my-httpd
  semodule -i my-httpd.pp
fi


if [ $enable_ssl == yes ]
then
  yum install epel-release -y
  yum install python-certbot-apache -y
  certbot --apache -d $FQDN
  systemctl restart httpd.service
  firewall-cmd --zone=public --add-service=https
  firewall-cmd --zone=public --permanent --add-service=https
else
  echo "If you want using SSL encryption later, run:"
  echo
  echo "yum install epel-release -y"
  echo "yum install python-certbot-apache -y"
  echo "certbot --apache -d $FQDN"
  echo "systemctl restart httpd.service"
  echo "firewall-cmd --zone=public --add-service=https"
  echo "firewall-cmd --zone=public --permanent --add-service=https"
fi

echo
echo "LMS DIR $LMS_DIR"
echo "LMS shell user account: $shell_user"
echo "LMS shell user password: $shell_password" 
