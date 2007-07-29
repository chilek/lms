#!/usr/bin/python
# encoding: iso8859-2
# LMS2tree-firewall
#
# GNU GPL
import MySQLdb, string, re
import ConfigParser

host="monitor"
regulkif="/tmp/regulki"
offline="/tmp/offline"

ol=open(offline,'w')

regulki=open(regulkif,'w')
config=ConfigParser.ConfigParser()
db=MySQLdb.connect(host="lms.host",user="lms.user",passwd="lms.pass",db="lms.pass")
c=db.cursor()
select="""SELECT id,data,%s FROM reload ORDER BY data DESC LIMIT 1""" %host
c.execute(select)
z=c.fetchone()
						
lmsy=['lms.db']

for lms in lmsy:
	db=MySQLdb.connect(host="lms.host",user="lms.user",passwd="lms.pass",db=lms)
	c=db.cursor()
	if z[2]==0:
		regulki.write("1\n")
		config.add_section("init")
		config.set("init","timestamp",z[1])
		config.write(ol)
		select="""UPDATE reload SET %s=1 WHERE data=%s"""%(host,z[1])
		c.execute(select)
	else:
		regulki.write("0\n")

regulki.close()
