#!/usr/bin/python
# + encoding: iso-8859-2 +
import MySQLdb,string,re
import locale
import ConfigParser

host="monitor"
rulesfhead="/tmp/offline"
config=ConfigParser.ConfigParser()
config.read(rulesfhead)

plikhosts="/etc/nagios/hosts.cfg.lms"
hosts=open(plikhosts,'w')

plikservices="/etc/nagios/services.cfg.lms"
services=open(plikservices,'w')

plikhostgroups="/etc/nagios/hostgroups.cfg.lms"
hostgroups=open(plikhostgroups,'w')

locale.setlocale(locale.LC_ALL, 'pl_PL')

lmsy=['lmsmain']

for lms in lmsy:
	db=MySQLdb.connect(host="host",user="lmsuser",passwd="lmspass",db=lms)
	c=db.cursor()
	c.execute("""SELECT name,inet_ntoa(address),mask FROM networks WHERE inet_ntoa(address) NOT REGEXP '82.160' AND inet_ntoa(address) NOT REGEXP '192.168.8[234567]' AND name NOT REGEXP 'ADDR-' AND name NOT REGEXP 'IMPORT'""") 
	d=c.fetchall()
	
	for net in d:
		hostgroupswrite="""define hostgroup {
	hostgroup_name	%s
	alias		%s
	members		"""%(net[0],net[0])
		hostgroups.write(hostgroupswrite)
		dlugoscsieci=re.split('\.',net[2],4)
		dlugoscsieci=256-int(dlugoscsieci[3])
		c.execute("""SELECT inet_aton(%s)""",net[1]);
		start=int(c.fetchone()[0])
		koniec=start+dlugoscsieci
		#klasa=str(net[1])
		c.execute("""SELECT name,inet_ntoa(ipaddr) FROM nodes WHERE ownerid=0 AND ipaddr BETWEEN %i AND %i ORDER BY ipaddr"""%(start,koniec))
		z=c.fetchall()
		for j in z:
			hostswrite="""define host {
	use			generic-host
	host_name		%s
	alias			%s
	address			%s
	check_command		check-host-alive
        max_check_attempts	20
	notification_interval	240
	notification_period	24x7
	notification_options	d,u,r
	contact_groups		admins
}

""" %(string.lower(j[0]),j[0],j[1])
			hosts.write(hostswrite)
			serviceswrite="""define service {
	use			generic-service
        host_name		%s
	service_description	PING
	is_volatile		0
	active_checks_enabled	1
	passive_checks_enabled	1
	notifications_enabled	1
	check_period		24x7
	max_check_attempts	20
	normal_check_interval	5
	retry_check_interval	1
	contact_groups		admins
	notification_interval	240
	notification_period	24x7
	notification_options	c,r
	check_command		check_ping!100.0,20%%!500.0,60%%
}

""" %string.lower(j[0])
			services.write(serviceswrite)
			hostgroupswrite="%s," %string.lower(j[0])
			hostgroups.write(hostgroupswrite)
		hostgroupswrite="""\n}\n\n"""
		hostgroups.write(hostgroupswrite)

		
        c.execute("""SELECT name,inet_ntoa(address) FROM networks WHERE inet_ntoa(address) REGEXP '192.168.8[2345678]'""")
        d=c.fetchall()
        for net in d:
                hostgroupswrite="""define hostgroup {
        hostgroup_name  %s
        alias           %s
        members         """%(net[0],net[0])
                hostgroups.write(hostgroupswrite)
                klasa=str(net[1])[:-2]
                c.execute("""SELECT name,inet_ntoa(ipaddr) FROM nodes WHERE inet_ntoa(ipaddr) REGEXP %s ORDER BY id""",klasa)
                z=c.fetchall()
                for j in z:
                        hostswrite="""define host {
        use                     generic-host
        host_name               %s
        alias                   %s
        address                 %s
        check_command           check-host-alive
        max_check_attempts      20
        notification_interval   240
        notification_period     24x7
        notification_options    d,u,r
        contact_groups          admins
}

""" %(string.lower(j[0]),j[0],j[1])
                        hosts.write(hostswrite)
                        serviceswrite="""define service {
        use                     generic-service
        host_name               %s
        service_description     PING
        is_volatile             0
        active_checks_enabled   1
        passive_checks_enabled  1
        notifications_enabled   1
        check_period            24x7
        max_check_attempts      20
        normal_check_interval   5
        retry_check_interval    1
        contact_groups          admins
        notification_interval   240
        notification_period     24x7
        notification_options    c,r
        check_command           check_ping!100.0,20%%!500.0,60%%
}

""" %string.lower(j[0])
                        services.write(serviceswrite)
                        hostgroupswrite="%s," %string.lower(j[0])
                        hostgroups.write(hostgroupswrite)
                hostgroupswrite="""\n}\n\n"""
                hostgroups.write(hostgroupswrite)

hosts.close()
services.close()
hostgroups.close()

db=MySQLdb.connect(host="192.168.50.4",user="lms",passwd="lms412",db="lmsmain")
c=db.cursor()
cfg=config.get("init","timestamp")
#select="""UPDATE reload SET %s=%s WHERE data=%s"""%(host,cfg,cfg)
#c.execute(select)

