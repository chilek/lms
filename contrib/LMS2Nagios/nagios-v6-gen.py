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

lmsy=['lms.db']

for lms in lmsy:
	db=MySQLdb.connect(host="lms.host",user="lms.user",passwd="lms.pass",db=lms)
	c=db.cursor()
	c.execute("""SELECT name,inet_ntoa(address),mask FROM networks WHERE inet_ntoa(address) NOT REGEXP '82.160' AND inet_ntoa(address) NOT REGEXP '192.168.8[2345678]' AND name NOT REGEXP 'ADDR-' AND name NOT REGEXP 'PRIV'""") 
	d=c.fetchall()
	
	for net in d:
		hostgroupswrite="""define hostgroup {
	hostgroup_name	%s
	alias		%s
	members		"""%(net[0],net[0])
		hostgroups.write(hostgroupswrite)
		dlugoscsieci=re.split('\.',net[2],4)
		dlugoscsieci=256-int(dlugoscsieci[3])
		c.execute("""SELECT inet_aton(%s)""",net[1])
		start=int(c.fetchone()[0])
		koniec=start+dlugoscsieci
		#klasa=str(net[1])
		c.execute("""SELECT name,inet_ntoa(ipaddr) FROM vnodes WHERE ipaddr BETWEEN %i AND %i AND (name LIKE 'BTS%%' OR name LIKE 'RTR%%') ORDER BY ipaddr"""%(start,koniec))
		z=c.fetchall()
		for j in z:
			hostswrite="""define host {
	use			generic-host
	host_name		%s
	alias			%s
	address			%s
	check_command		check-host-alive
        max_check_attempts	20
	notification_interval	600
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
	notification_interval	600
	notification_period	24x7
	notification_options	c,r
	check_command		check_ping!100.0,20%%!500.0,60%%
}

""" %string.lower(j[0])
			services.write(serviceswrite)
			hostgroupswrite="%s," %string.lower(j[0])
			hostgroups.write(hostgroupswrite)
		hostgroupswrite="""gw-%s\n}\n\n"""%string.lower(net[0])
		hostgroups.write(hostgroupswrite)
		gwname=int(start)+1
		c.execute("""SELECT inet_ntoa(%s)""",gwname)
		gwip=c.fetchone()[0]
		hostswrite="""define host {
	use			generic-host
	host_name		gw-%s
	alias			gw-%s
	address			%s
	check_command		check-host-alive
	max_check_attempts	10
	notification_interval	600
	notification_period	24x7
	notification_options	d,u,r
	contact_groups		admins
}
""" %(string.lower(net[0]),string.lower(net[0]),gwip)
		hosts.write(hostswrite)
		serviceswrite="""define service {
        use			generic-service
	host_name               gw-%s
	service_description     PING
	is_volatile             0
	active_checks_enabled   1
	passive_checks_enabled  1
	notifications_enabled   1
	check_period            24x7
	max_check_attempts      10
	normal_check_interval   5
	retry_check_interval    1
	contact_groups          admins
	notification_interval   600
	notification_period     24x7
	notification_options    c,r
	check_command           check_ping!100.0,20%%!500.0,60%%
}
"""%string.lower(net[0])
		services.write(serviceswrite)

hosts.close()
services.close()
hostgroups.close()

db=MySQLdb.connect(host="lms.host",user="lms.user",passwd="lms.pass",db="lms.db")
c=db.cursor()
cfg=config.get("init","timestamp")
select="""UPDATE reload SET %s=%s WHERE data=%s"""%(host,cfg,cfg)
c.execute(select)

