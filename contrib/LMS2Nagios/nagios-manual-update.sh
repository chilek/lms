#!/bin/sh
rm /etc/nagios/hosts.cfg.lms
rm /etc/nagios/services.cfg.lms
rm /etc/nagios/hostgroups.cfg.lms

/root/mgc/nagios-pre-update-v4-generator.py

cat /etc/nagios/hosts.cfg.static > /etc/nagios/hosts.cfg
cat /etc/nagios/hosts.cfg.lms >> /etc/nagios/hosts.cfg

cat /etc/nagios/services.cfg.static > /etc/nagios/services.cfg
cat /etc/nagios/services.cfg.lms >> /etc/nagios/services.cfg

cat /etc/nagios/hostgroups.cfg.static > /etc/nagios/hostgroups.cfg
cat /etc/nagios/hostgroups.cfg.lms >> /etc/nagios/hostgroups.cfg

/etc/init.d/nagios stop
/etc/init.d/nagios start
