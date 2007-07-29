#!/bin/sh
LOCK="/tmp/regulki.lock"
if [ -f $LOCK ]; then
  	exit
fi
touch $LOCK
echo `date` >> $LOCK
DIR=/root/mgc/
VER=6
cd $DIR
#$DIR/generuj-ident.py > /etc/oidentd_masq.conf
$DIR/nagios-v$VER-check.py
CONT=`cat /tmp/regulki`
if [ $CONT -eq "1" ]; then
	rm /etc/nagios/hosts.cfg.lms
	rm /etc/nagios/services.cfg.lms
	rm /etc/nagios/hostgroups.cfg.lms

	$DIR/nagios-v$VER-gen.py

	cat /etc/nagios/hosts.cfg.static > /etc/nagios/hosts.cfg
	cat /etc/nagios/hosts.cfg.lms >> /etc/nagios/hosts.cfg

	cat /etc/nagios/services.cfg.static > /etc/nagios/services.cfg
	cat /etc/nagios/services.cfg.lms >> /etc/nagios/services.cfg

	cat /etc/nagios/hostgroups.cfg.static > /etc/nagios/hostgroups.cfg
	cat /etc/nagios/hostgroups.cfg.lms >> /etc/nagios/hostgroups.cfg

	/etc/init.d/nagios stop > /dev/null 
	/etc/init.d/nagios restart > /dev/null
	#bo czasami ma opory wstaæ ;-)
	/etc/init.d/nagios restart > /dev/null

	echo `date` >> log
fi
rm -f $LOCK

