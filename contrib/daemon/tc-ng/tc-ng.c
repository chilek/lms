/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

#include <stdio.h>
#include <stdlib.h>
#include <syslog.h>
#include <string.h>
#include <netinet/in.h>

#include "lmsd.h"
#include "tc-ng.h"

unsigned long inet_addr(char *);

char * itoa(int i)
{
	static char string[12];
	sprintf(string, "%d", i);
	return string;
}

char * itoha(int i)
{
	static char string[8];
	sprintf(string, "%x", i);
	return string;
}

void reload(GLOBAL *g, struct tc_module *tc)
{
	FILE *fh;
	QueryHandle *res, *gres;
	int i, j, m=0, k=2, n=2, cc=0, nc=0, gc=0;
	int x = XVALUE;
	
	struct customer *customers = (struct customer *) malloc(sizeof(struct customer));

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(tc->networks);	
	char *netname = strdup(netnames);

	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(tc->customergroups);	
	char *groupname = strdup(groupnames);

	// get table of networks
	while( n>1 ) 
	{
		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) ) 
		{
			res = g->db_pquery(g->conn, "SELECT name, address, INET_ATON(mask) AS mask, interface FROM networks WHERE UPPER(name)=UPPER('?')",netname);
			if( g->db_nrows(res) ) 
			{
		    		nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].name = strdup(g->db_get_data(res,0,"name"));
				nets[nc].interface = strdup(g->db_get_data(res,0,"interface"));
				nets[nc].address = inet_addr(g->db_get_data(res,0,"address"));
				nets[nc].mask = inet_addr(g->db_get_data(res,0,"mask"));
				nc++;
			}
    			g->db_free(&res);
		}				
	}
	free(netname); free(netnames);

	// get table of networks (if 'networks' variable is not set)
	if(!nc)
	{
	        res = g->db_pquery(g->conn, "SELECT name, address, INET_ATON(mask) AS mask, interface FROM networks");
		for(nc=0; nc<g->db_nrows(res); nc++)
		{
		        nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
			nets[nc].name = strdup(g->db_get_data(res,nc,"name"));
			nets[nc].interface = strdup(g->db_get_data(res,nc,"interface"));
			nets[nc].address = inet_addr(g->db_get_data(res,nc,"address"));
			nets[nc].mask = inet_addr(g->db_get_data(res,nc,"mask"));
		}
		g->db_free(&res);
	 }

	// get table of customergroups
	while( k>1 ) 
	{
		k = sscanf(groupnames, "%s %[._a-zA-Z0-9- ]", groupname, groupnames);

		if( strlen(groupname) )
		{
			res = g->db_pquery(g->conn, "SELECT name, id FROM customergroups WHERE UPPER(name)=UPPER('?')",groupname);
			if( g->db_nrows(res) ) 
			{
				ugps = (struct group *) realloc(ugps, (sizeof(struct group) * (gc+1)));
				ugps[gc].name = strdup(g->db_get_data(res,0,"name"));
				ugps[gc].id = atoi(g->db_get_data(res,0,"id"));
				gc++;
			}
    			g->db_free(&res);
		}				
	}
	free(groupname); free(groupnames);

	// get customers with tariff rates summaries (ie. channels when one_class_per_host=disabled)
	res = g->db_query(g->conn, 
			"SELECT customerid AS id, "
				"ROUND(SUM(uprate)) AS uprate, "
				"ROUND(SUM(downrate)) AS downrate, "
				"ROUND(SUM(upceil)) AS upceil, "
				"ROUND(SUM(downceil)) AS downceil, "
				"ROUND(SUM(climit)) AS climit, "
				"ROUND(SUM(plimit)) AS plimit "
			"FROM assignments "
			"LEFT JOIN tariffs ON (tariffid = tariffs.id) "
			"WHERE (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) "
			"GROUP BY customerid"
			);

	for(i=0; i<g->db_nrows(res); i++) 
	{	
		int cid = atoi(g->db_get_data(res,i,"id"));
		// test customer's membership in customergroups
		if(gc)
		{
			gres = g->db_pquery(g->conn, "SELECT customergroupid FROM customerassignments WHERE customerid=?", itoa(cid));
			for(k=0; k<g->db_nrows(gres); k++) 
			{
				int groupid = atoi(g->db_get_data(gres, k, "customergroupid"));
				for(m=0; m<gc; m++) 
					if(ugps[m].id==groupid) 
						break;
				if(m!=gc) break;
			}
			g->db_free(&gres);
		}
					
		if( !gc || m!=gc ) 
		{
			int uprate 	= atoi(g->db_get_data(res,i,"uprate"));
			int downrate 	= atoi(g->db_get_data(res,i,"downrate"));
			int upceil 	= atoi(g->db_get_data(res,i,"upceil"));
			int downceil 	= atoi(g->db_get_data(res,i,"downceil"));

			if( uprate || downrate || upceil || downceil )
			{ 
				customers = (struct customer *) realloc(customers, (sizeof(struct customer) * (cc+1)));
				customers[cc].id = cid;
				customers[cc].no = 0;
				customers[cc].nodes = NULL;
				cc++;
			}
		}
	}
	g->db_free(&res);

	// nodes
	res = g->db_query(g->conn, 
		"SELECT t.downrate, t.downceil, t.uprate, t.upceil, t.climit, t.plimit, cn.cnt, "
			"n.id, n.ownerid, n.name, INET_NTOA(n.ipaddr) AS ip, n.mac " 
		"FROM nodeassignments na "
		"JOIN assignments a ON (na.assignmentid = a.id) "
		"JOIN tariffs t ON (a.tariffid = t.id) "
		"JOIN nodes n ON (na.nodeid = n.id) "
	    		// subquery: number of enabled nodes in assignment
		"JOIN ( "
			"SELECT count(*) AS cnt, assignmentid "
			"FROM nodeassignments "
			"JOIN nodes ON (nodeid = nodes.id) "
			"WHERE access = 1 "
			"GROUP BY assignmentid "
			") cn ON (cn.assignmentid = na.assignmentid) "
		"WHERE "
			"(a.datefrom <= %NOW% OR a.datefrom = 0) AND (a.dateto >= %NOW% OR a.dateto = 0) "
			"AND "
			"n.access = 1 "
	);

	if(!g->db_nrows(res))
	{
	        syslog(LOG_ERR, "[%s/tc-ng] Unable to read database or assignments table is empty", tc->base.instance);
		return;
	}

	// adding nodes to customers array
	for(i=0; i<g->db_nrows(res); i++)
        {
		char *ip = g->db_get_data(res,i,"ip");
        	int ownerid = atoi(g->db_get_data(res,i,"ownerid"));
        	int nodeid = atoi(g->db_get_data(res,i,"id"));
		unsigned long inet = inet_addr(ip);

		// Networks test
		for(n=0; n<nc; n++)
	                if(nets[n].address == (inet & nets[n].mask))
	                        break;
			
		if(n == nc) continue;
		
		// looking for customer
		for(j=0; j<cc; j++)
			if(customers[j].id == ownerid)
				break;
		
		if(j == cc) continue; // break loop if customer's not found

		int cnt 	= atoi(g->db_get_data(res,i,"cnt"));
		int uprate 	= atoi(g->db_get_data(res,i,"uprate")) / cnt;
		int downrate 	= atoi(g->db_get_data(res,i,"downrate")) / cnt;
		int upceil 	= atoi(g->db_get_data(res,i,"upceil")) / cnt;
		int downceil 	= atoi(g->db_get_data(res,i,"downceil")) / cnt;
		int climit 	= atoi(g->db_get_data(res,i,"climit")) / cnt;
		int plimit 	= atoi(g->db_get_data(res,i,"plimit")) / cnt;
		
		// looking for node
		for(k=0; k<customers[j].no; k++)
			if(customers[j].nodes[k].id == nodeid)
				break;

		if(k == customers[j].no) // node not exists
		{
			customers[j].nodes = (struct node *) realloc(customers[j].nodes, (sizeof(struct node) * (k+1)));
			customers[j].nodes[k].id = nodeid;
			customers[j].nodes[k].uprate = uprate;
			customers[j].nodes[k].upceil = upceil;
			customers[j].nodes[k].downrate = downrate;
			customers[j].nodes[k].downceil = downceil;
			customers[j].nodes[k].climit = climit;
			customers[j].nodes[k].plimit = plimit;
			customers[j].nodes[k].network = n;
			customers[j].nodes[k].ip = strdup(ip);
			customers[j].nodes[k].name = strdup(g->db_get_data(res,i,"name"));
			customers[j].nodes[k].mac = strdup(g->db_get_data(res,i,"mac"));
			customers[j].no++;
		}
		else
		{
			customers[j].nodes[k].uprate += uprate;
			customers[j].nodes[k].upceil += upceil;
			customers[j].nodes[k].downrate += downrate;
			customers[j].nodes[k].downceil += downceil;
			customers[j].nodes[k].climit += climit;
			customers[j].nodes[k].plimit += plimit;
		}
	}
	g->db_free(&res);

	// open file	
	fh = fopen(tc->file, "w");
	if(fh) 
	{
		fprintf(fh, "%s", tc->begin);
		
		// customers loop
		for(i=0; i<cc; i++) 
		{	
			struct customer c = customers[i];

			for(j=0; j<c.no; j++)
			{
				char *mark_up = strdup(tc->host_mark_up);
				char *mark_down = strdup(tc->host_mark_down);
				char *htb_up = strdup(tc->host_htb_up);
				char *htb_down = strdup(tc->host_htb_down);
				char *cl = strdup(tc->host_climit);
				char *pl = strdup(tc->host_plimit);
			
				struct node host = c.nodes[j];

				unsigned int hostip = ntohl(inet_addr(host.ip));
				char *o1 = strdup(itoa((hostip >> 24) & 0xff)); // first octet
				char *o2 = strdup(itoa((hostip >> 16) & 0xff)); // second octet
				char *o3 = strdup(itoa((hostip >> 8) & 0xff)); // third octet
				char *o4 = strdup(itoa(hostip & 0xff)); // last octet
				char *i16 = strdup(itoha(hostip & 0xff));  // last octet in hex

				if(host.uprate && host.downrate)
				{
					g->str_replace(&mark_up, "%n", host.name);
					g->str_replace(&mark_up, "%if", nets[host.network].interface);
					g->str_replace(&mark_up, "%i16", i16);
					g->str_replace(&mark_up, "%i", host.ip);
					g->str_replace(&mark_up, "%m", host.mac);
					g->str_replace(&mark_up, "%x", itoa(x));
					g->str_replace(&mark_up, "%o1", o1);
					g->str_replace(&mark_up, "%o2", o2);
					g->str_replace(&mark_up, "%o3", o3);
					g->str_replace(&mark_up, "%o4", o4);
			    		
					g->str_replace(&mark_down, "%n", host.name);
					g->str_replace(&mark_down, "%if", nets[host.network].interface);
					g->str_replace(&mark_down, "%i16", i16);
					g->str_replace(&mark_down, "%i", host.ip);
					g->str_replace(&mark_down, "%m", host.mac);
					g->str_replace(&mark_down, "%x", itoa(x));
					g->str_replace(&mark_down, "%o1", o1);
					g->str_replace(&mark_down, "%o2", o2);
					g->str_replace(&mark_down, "%o3", o3);
					g->str_replace(&mark_down, "%o4", o4);
		
					g->str_replace(&htb_up, "%n", host.name);
					g->str_replace(&htb_up, "%if", nets[host.network].interface);
					g->str_replace(&htb_up, "%i16", i16);
					g->str_replace(&htb_up, "%i", host.ip);
					g->str_replace(&htb_up, "%m", host.mac);
					g->str_replace(&htb_up, "%x", itoa(x));
					g->str_replace(&htb_up, "%uprate", itoa(host.uprate));
	
					if(!host.upceil)
						g->str_replace(&htb_up, "%upceil", itoa(host.uprate));
					else
						g->str_replace(&htb_up, "%upceil", itoa(host.upceil));
					
					g->str_replace(&htb_down, "%n", host.name);
					g->str_replace(&htb_down, "%if", nets[host.network].interface);
					g->str_replace(&htb_down, "%i16", i16);
					g->str_replace(&htb_down, "%i", host.ip);
					g->str_replace(&htb_down, "%m", host.mac);
					g->str_replace(&htb_down, "%x", itoa(x));
					g->str_replace(&htb_down, "%downrate", itoa(host.downrate));

					if(!host.downceil)
						g->str_replace(&htb_down, "%downceil", itoa(host.downrate));
					else						
						g->str_replace(&htb_down, "%downceil", itoa(host.downceil));
	
					// write to file
					fprintf(fh, "%s", mark_up);
					fprintf(fh, "%s", mark_down);
					fprintf(fh, "%s", htb_up);
					fprintf(fh, "%s", htb_down);
				}

				if(host.climit)
				{
					g->str_replace(&cl, "%climit", itoa(host.climit));
					g->str_replace(&cl, "%n", host.name);
					g->str_replace(&cl, "%if", nets[host.network].interface);
	    				g->str_replace(&cl, "%i16", i16);
	    				g->str_replace(&cl, "%i", host.ip);
	    				g->str_replace(&cl, "%m", host.mac);
					g->str_replace(&cl, "%x", itoa(x));
					g->str_replace(&cl, "%o1", o1);
					g->str_replace(&cl, "%o2", o2);
					g->str_replace(&cl, "%o3", o3);
					g->str_replace(&cl, "%o4", o4);

					fprintf(fh, "%s", cl);
				}
							
				if(host.plimit)
				{
					g->str_replace(&pl, "%plimit", itoa(host.plimit));
					g->str_replace(&pl, "%n", host.name);
					g->str_replace(&pl, "%if", nets[host.network].interface);
					g->str_replace(&pl, "%i16", i16);
					g->str_replace(&pl, "%i", host.ip);
					g->str_replace(&pl, "%m", host.mac);
					g->str_replace(&pl, "%x", itoa(x));
					g->str_replace(&pl, "%o1", o1);
					g->str_replace(&pl, "%o2", o2);
					g->str_replace(&pl, "%o3", o3);
					g->str_replace(&pl, "%o4", o4);

					fprintf(fh, "%s", pl);
				}	
			
				x++;
			
				free(cl); 
				free(pl); 
				free(mark_up); 
				free(mark_down);
				free(htb_up); 
				free(htb_down);
				free(o1); free(o2); free(o3); free(o4); free(i16);
			}
		}
		
		fprintf(fh, "%s", tc->end);
		
		fclose(fh);
		system(tc->command);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/tc-ng] reloaded", tc->base.instance);	
#endif
	}
	else
		syslog(LOG_ERR, "[%s/tc-ng] Unable to write to file '%s'", tc->base.instance, tc->file);

	for(i=0; i<nc; i++)
	{
		free(nets[i].name);
		free(nets[i].interface);
	}
	free(nets);
	
	for(i=0;i<gc;i++)
		free(ugps[i].name);
	free(ugps);

	for(i=0; i<cc; i++)
	{
		for(j=0; j<customers[i].no; j++)
		{
			free(customers[i].nodes[j].ip);
			free(customers[i].nodes[j].name);
			free(customers[i].nodes[j].mac);
		}
		free(customers[i].nodes);
	}
	free(customers);
	
	free(tc->file);
	free(tc->command);	
	free(tc->begin);
	free(tc->end);	
	free(tc->host_htb_up);
	free(tc->host_htb_down);
	free(tc->host_mark_up);
	free(tc->host_mark_down);
	free(tc->host_climit);
	free(tc->host_plimit);
	free(tc->networks);
	free(tc->customergroups);
}

struct tc_module * init(GLOBAL *g, MODULE *m)
{
	struct tc_module *tc;
	
	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	tc = (struct tc_module*) realloc(m, sizeof(struct tc_module));
	
	tc->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	
	tc->file = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "file", "/etc/rc.d/rc.htb"));
	tc->command = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "command", "sh /etc/rc.d/rc.htb start"));
	tc->begin = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "begin", "\
#!/bin/sh\n\
IPT=/usr/sbin/iptables\n\
TC=/sbin/tc\n\
LAN=eth0\n\
WAN=eth1\n\
BURST=\"burst 30k\"\n\
\n\
stop ()\n\
{\n\
$IPT -t mangle -D FORWARD -i $WAN -j LIMITS >/dev/null 2>&1\n\
$IPT -t mangle -D FORWARD -o $WAN -j LIMITS >/dev/null 2>&1\n\
$IPT -t mangle -F LIMITS >/dev/null 2>&1\n\
$IPT -t mangle -X LIMITS >/dev/null 2>&1\n\
$IPT -t mangle -F OUTPUT\n\
$IPT -t filter -F FORWARD\n\
$TC qdisc del dev $LAN root 2> /dev/null\n\
$TC qdisc del dev $WAN root 2> /dev/null\n\
}\n\
\n\
start ()\n\
{\n\
stop\n\
$IPT -t mangle -N LIMITS\n\
$IPT -t mangle -I FORWARD -i $WAN -j LIMITS\n\
$IPT -t mangle -I FORWARD -o $WAN -j LIMITS\n\
# incomming traffic\n\
$IPT -t mangle -A OUTPUT -j MARK --set-mark 1\n\
$TC qdisc add dev $LAN root handle 1:0 htb default 3 r2q 1\n\
$TC class add dev $LAN parent 1:0 classid 1:1 htb rate 99000kbit ceil 99000kbit quantum 1500\n\
$TC class add dev $LAN parent 1:1 classid 1:2 htb rate   500kbit ceil   500kbit\n\
$TC class add dev $LAN parent 1:1 classid 1:3 htb rate 98500kbit ceil 98500kbit prio 9 quantum 1500\n\
$TC qdisc add dev $LAN parent 1:3 esfq perturb 10 hash dst\n\
# priorities for ICMP, TOS 0x10 and ports 22 and 53\n\
$TC class add dev $LAN parent 1:2 classid 1:20 htb rate 50kbit ceil 500kbit $BURST prio 1 quantum 1500\n\
$TC qdisc add dev $LAN parent 1:20 esfq perturb 10 hash dst\n\
$TC filter add dev $LAN parent 1:0 protocol ip prio 2 u32 match ip sport 22 0xffff flowid 1:20\n\
$TC filter add dev $LAN parent 1:0 protocol ip prio 2 u32 match ip sport 53 0xffff flowid 1:20\n\
$TC filter add dev $LAN parent 1:0 protocol ip prio 1 u32 match ip tos 0x10 0xff flowid 1:20\n\
$TC filter add dev $LAN parent 1:0 protocol ip prio 1 u32 match ip protocol 1 0xff flowid 1:20\n\
# server -> LAN\n\
$TC filter add dev $LAN parent 1:0 protocol ip prio 4 handle 1 fw flowid 1:3\n\
\n\
# outgoing traffic\n\
$TC qdisc add dev $WAN root handle 2:0 htb default 11 r2q 1\n\
$TC class add dev $WAN parent 2:0 classid 2:1 htb rate 120kbit ceil 120kbit\n\
# priorities for ACK, ICMP, TOS 0x10, ports 22 and 53\n\
$TC class add dev $WAN parent 2:1 classid 2:10 htb rate 60kbit ceil 120kbit prio 1 quantum 1500\n\
$TC qdisc add dev $WAN parent 2:10 esfq perturb 10 hash dst\n\
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip protocol 6 0xff match u8 0x05 0x0f at 0 match u16 0x0000 0xffc0 at 1 match u8 0x10 0xff at 33 flowid 2:10\n\
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip dport 22 0xffff flowid 2:10\n\
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip dport 53 0xffff flowid 2:10\n\
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip tos 0x10 0xff flowid 2:10\n\
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip protocol 1 0xff flowid 2:10\n\
# server -> Internet\n\
$TC class add dev $WAN parent 2:1 classid 2:11 htb rate 30kbit ceil 120kbit prio 2 quantum 1500\n\
$TC qdisc add dev $WAN parent 2:11 esfq perturb 10 hash dst\n\
$TC filter add dev $WAN parent 2:0 protocol ip prio 3 handle 1 fw flowid 2:11\n\
$TC filter add dev $WAN parent 2:0 protocol ip prio 9 u32 match ip dst 0/0 flowid 2:11\n\
\n"));
	tc->end = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "end", "\n\
}\n\
\n\
case \"$1\" in\n\
    'start')\n\
	start\n\
    ;;\n\
    'stop')\n\
	stop\n\
    ;;\n\
    'status')\n\
	echo \"WAN Interface\"\n\
	echo \"=============\"\n\
	$TC class show dev $WAN | grep root\n\
	$TC class show dev $WAN | grep -v root | sort | nl\n\
	echo \"LAN Interface\"\n\
	echo \"=============\"\n\
	$TC class show dev $LAN | grep root\n\
	$TC class show dev $LAN | grep -v root | sort | nl\n\
    ;;\n\
    *)\n\
	echo -e \"\\nUsage: rc.htb start|stop|status\"\n\
    ;;\n\
esac\n\
"));
	tc->host_mark_up = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "host_mark_up", 
"# %n\n\
$IPT -t mangle -A LIMITS -s %i -j MARK --set-mark %x\n"));

	tc->host_mark_down = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "host_mark_down",
"$IPT -t mangle -A LIMITS -d %i -j MARK --set-mark %x\n"));

	tc->host_htb_up = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "host_htb_up",
"$TC class add dev $WAN parent 2:1 classid 2:%x htb rate %upratekbit ceil %upceilkbit $BURST prio 2 quantum 1500\n\
$TC qdisc add dev $WAN parent 2:%x esfq perturb 10 hash dst\n\
$TC filter add dev $WAN parent 2:0 protocol ip prio 5 handle %x fw flowid 2:%x\n"));
	
	tc->host_htb_down = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "host_htb_down",
"$TC class add dev $LAN parent 1:2 classid 1:%x htb rate %downratekbit ceil %downceilkbit $BURST prio 2 quantum 1500\n\
$TC qdisc add dev $LAN parent 1:%x esfq perturb 10 hash dst\n\
$TC filter add dev $LAN parent 1:0 protocol ip prio 5 handle %x fw flowid 1:%x\n"));
	
	tc->host_climit = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "host_climit",
"$IPT -t filter -I FORWARD -p tcp -s %i -m connlimit --connlimit-above %climit -m ipp2p --ipp2p -j REJECT\n"));
	
	tc->host_plimit = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "host_plimit",
"$IPT -t filter -I FORWARD -p tcp -d %i -m limit --limit %plimit/s -m ipp2p --ipp2p -j ACCEPT\n\
$IPT -t filter -I FORWARD -p tcp -s %i -m limit --limit %plimit/s -m ipp2p --ipp2p -j ACCEPT\n"));
	
	tc->networks = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "networks", ""));
	tc->customergroups = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "customergroups", ""));
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/tc-ng] initialized", tc->base.instance);
#endif
	return (tc);
}
