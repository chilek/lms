/*
 * LMS version 1.3-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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
#include <syslog.h>
#include <string.h>

#include "almsd.h"
#include "tc.h"

unsigned long inet_addr(unsigned char *);

char * itoa(int i)
{
	static char string[12];
	sprintf(string, "%d", i);
	return string;
}

void reload(GLOBAL *g, struct tc_module *tc)
{
	FILE *fh;
	QUERY_HANDLE *res, *ures, *nres;
	int x=100, i, j, m, v, k=2, n=2, nc=0, gc=0;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(tc->networks);	
	char *netname = strdup(netnames);

	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(tc->usergroups);	
	char *groupname = strdup(groupnames);

	// get table of networks
	while( n>1 ) 
	{
		n = sscanf(netnames, "%s %[a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) ) 
			if( res = g->db_pquery("SELECT name, domain, address, INET_ATON(mask) AS mask, interface FROM networks WHERE UPPER(name)=UPPER('?')",netname)) 
			{
				if(res->nrows) 
				{
		    			nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
					nets[nc].name = strdup(g->db_get_data(res,0,"name"));
					nets[nc].domain = strdup(g->db_get_data(res,0,"domain"));
					nets[nc].interface = strdup(g->db_get_data(res,0,"interface"));
					nets[nc].address = inet_addr(g->db_get_data(res,0,"address"));
					nets[nc].mask = inet_addr(g->db_get_data(res,0,"mask"));
					nc++;
				}
    				g->db_free(res);
			}				
	}
	free(netname); free(netnames);

	// get table of usergroups
	while( k>1 ) 
	{
		k = sscanf(groupnames, "%s %[a-zA-Z0-9- ]", groupname, groupnames);

		if( strlen(groupname) )
			if( res = g->db_pquery("SELECT name, id FROM usergroups WHERE UPPER(name)=UPPER('?')",groupname)) 
			{
				if(res->nrows) 
				{
			    		ugps = (struct group *) realloc(ugps, (sizeof(struct group) * (gc+1)));
					ugps[gc].name = strdup(g->db_get_data(res,0,"name"));
					ugps[gc].id = atoi(g->db_get_data(res,0,"id"));
					gc++;
					printf("&");
				}
    				g->db_free(res);
			}				
	}
	free(groupname); free(groupnames);

	// open temporary file	
	fh = fopen(tc->file, "w");
	if(fh) 
	{
		// get (htb) data for any user with connected nodes and active assignments
		// we need user ID and average data values for nodes
		if( (ures = g->db_query("\
			SELECT userid AS id, \
				ROUND(SUM(uprate)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS uprate, \
				ROUND(SUM(downrate)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS downrate, \
				ROUND(SUM(upceil)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS upceil, \
				ROUND(SUM(downceil)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS downceil, \
				ROUND(SUM(climit)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS climit, \
				ROUND(SUM(plimit)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS plimit \
			FROM assignments \
				LEFT JOIN tariffs ON (tariffid = tariffs.id) \
				LEFT JOIN nodes ON (userid = ownerid) \
			WHERE access = 1 AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) \
			GROUP BY userid \
			ORDER BY userid"))!=NULL ) 
		{
		
		fprintf(fh, "%s", tc->begin);
		
			for(i=0; i<ures->nrows; i++) 
			{	
				// test user's membership in usergroups
				if(gc)
					if( res = g->db_pquery("SELECT usergroupid FROM userassignments WHERE userid=?", g->db_get_data(ures,i,"id"))) {
						for(k=0; k<res->nrows; k++) {
							int groupid = atoi(g->db_get_data(res, k, "usergroupid"));
							for(m=0; m<gc; m++) 
								if(ugps[m].id==groupid) 
									break;
							if(m!=gc) break;
						}
						g->db_free(res);
					}
					
				if( !gc || m!=gc ) 
				{
					char *uprate = g->db_get_data(ures,i,"uprate");
					char *downrate = g->db_get_data(ures,i,"downrate");
					char *upceil = g->db_get_data(ures,i,"upceil");
					char *downceil = g->db_get_data(ures,i,"downceil");
					char *climit = g->db_get_data(ures,i,"climit");
					char *plimit = g->db_get_data(ures,i,"plimit");
					int n_upceil = atoi(upceil);					
					int n_downceil = atoi(downceil);					
					int n_uprate = atoi(uprate);					
					int n_downrate = atoi(downrate);					
					int n_climit = atoi(climit);
					int n_plimit = atoi(plimit);

					if( (nres = g->db_pquery(" \
						SELECT INET_NTOA(ipaddr) AS ip, ipaddr, mac, name \
						FROM nodes \
						WHERE ownerid = ? AND access = 1 \
						ORDER BY ipaddr", g->db_get_data(ures,i,"id")))!=NULL ) 
					{
			
						for(j=0; j<nres->nrows; j++) 
						{	
							char *ipaddr = g->db_get_data(nres,j,"ip");
							char *mac = g->db_get_data(nres,j,"mac");
							unsigned char *name = g->db_get_data(nres,j,"name");
							unsigned char *s = strdup(tc->host);
							unsigned char *cl = strdup(tc->host_climit);
							unsigned char *pl = strdup(tc->host_plimit);
							
							// test node's membership in networks
							if(nc)
								for(v=0; v<nc; v++)
									if(nets[v].address == (inet_addr(ipaddr) & nets[v].mask)) 
										break;
						
							if(!nc || v!=nc)
							{
								if(n_uprate && n_downrate)
								{
									g->str_replace(&s, "%n", name);
									g->str_replace(&s, "%i", ipaddr);
									g->str_replace(&s, "%m", mac);
									g->str_replace(&s, "%x", itoa(x));
									g->str_replace(&s, "%climit", climit);
									g->str_replace(&s, "%plimit", plimit);
									g->str_replace(&s, "%uprate", uprate);
									g->str_replace(&s, "%downrate", downrate);
									if(!n_upceil)
										g->str_replace(&s, "%upceil", uprate);
									else
										g->str_replace(&s, "%upceil", upceil);
									
									if(!n_downceil)
										g->str_replace(&s, "%downceil", downrate);
									else						
										g->str_replace(&s, "%downceil", downceil);
							
									// write to file
									fprintf(fh, "%s", s);
								}
								
								if(n_climit)
								{
									g->str_replace(&cl, "%climit", climit);
									g->str_replace(&cl, "%n", name);
	    								g->str_replace(&cl, "%i", ipaddr);
									g->str_replace(&cl, "%m", mac);
									g->str_replace(&cl, "%x", itoa(x));
									fprintf(fh, "%s", cl);
								}
								
								if(n_plimit)
								{
									g->str_replace(&pl, "%plimit", plimit);
									g->str_replace(&pl, "%n", name);
									g->str_replace(&pl, "%i", ipaddr);
									g->str_replace(&pl, "%m", mac);
									g->str_replace(&pl, "%x", itoa(x));
									fprintf(fh, "%s", pl);
								}	
							
								free(cl); free(pl); free(s);
								x++;
							}
						}
						g->db_free(nres);
					}
				}
			}
			g->db_free(ures);
			
			fprintf(fh, "%s", tc->end);
		}
		else
			syslog(LOG_ERR, "[%s/tc] Unable to read database", tc->base.instance);
			
		fclose(fh);
		system(tc->command);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/tc] reloaded", tc->base.instance);	
#endif
	}
	else
		syslog(LOG_ERR, "[%s/tc] Unable to write a temporary file '%s'", tc->base.instance, tc->file);

	for(i=0;i<nc;i++) {
		free(nets[i].name);
		free(nets[i].domain);	
		free(nets[i].interface);
	}
	free(nets);
	
	for(i=0;i<gc;i++) {
		free(ugps[i].name);
	}
	free(ugps);
	
	free(tc->file);
	free(tc->command);	
	free(tc->begin);
	free(tc->end);	
	free(tc->host);
	free(tc->host_climit);
	free(tc->host_plimit);
	free(tc->networks);
	free(tc->usergroups);
}

struct tc_module * init(GLOBAL *g, MODULE *m)
{
	struct tc_module *tc;
	unsigned char *instance, *s;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
		return (NULL);
	
	instance = m->instance;
	
	tc = (struct tc_module*) realloc(m, sizeof(struct tc_module));
	
	tc->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	tc->base.instance = strdup(instance);
	
	ini = g->iniparser_load(g->inifile);
	
	s = g->str_concat(instance, ":file");
	tc->file = strdup(g->iniparser_getstring(ini, s, "/etc/rc.d/rc.htb"));
	free(s); s = g->str_concat(instance, ":command");
	tc->command = strdup(g->iniparser_getstring(ini, s, "sh /etc/rc.d/rc.htb start"));
	free(s); s = g->str_concat(instance, ":begin");
	tc->begin = strdup(g->iniparser_getstring(ini, s, "\n\
#!/bin/sh\n\
IPT=/usr/sbin/iptables\n\
TC=/sbin/tc\n\
LAN=eth0\n\
WAN=eth1\n\
BURST=\"burst 15k\"\n\
\
stop ()\n\
{\n\
$IPT -t mangle -F POSTROUTING\n\
$IPT -t mangle -F OUTPUT\n\
$IPT -t filter -F FORWARD\n\
$TC qdisc del dev $LAN root 2> /dev/null\n\
$TC qdisc del dev $WAN root 2> /dev/null\n\
}\n\
\
start ()\n\
{\n\
stop\n\
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
"));
	free(s); s = g->str_concat(instance, ":end");
	tc->end = strdup(g->iniparser_getstring(ini, s, "\n\
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
	free(s); s = g->str_concat(instance, ":host");
	tc->host = strdup(g->iniparser_getstring(ini, s, 
"# %n\n\
$IPT -t mangle -A POSTROUTING -s %i -j MARK --set-mark %x\n\
$TC class add dev $LAN parent 1:2 classid 1:%x htb rate %downratekbit ceil %downceilkbit $BURST prio 2 quantum 1500\n\
$TC qdisc add dev $LAN parent 1:%x esfq perturb 10 hash dst\n\
$TC filter add dev $LAN parent 1:0 protocol ip prio 5 u32 match ip dst %i flowid 1:%x\n\
$TC class add dev $WAN parent 2:1 classid 2:%x htb rate %upratekbit ceil %upceilkbit $BURST prio 2 quantum 1500\n\
$TC qdisc add dev $WAN parent 2:%x esfq perturb 10 hash dst\n\
$TC filter add dev $WAN parent 2:0 protocol ip prio 5 handle %x fw flowid 2:%x\n\
"));
	free(s); s = g->str_concat(instance, ":host_climit");
	tc->host_climit = strdup(g->iniparser_getstring(ini, s, "\n\
$IPT -t filter -I FORWARD -p tcp -s %i -m connlimit --connlimit-above %climit -m ipp2p --ipp2p -j REJECT\n"));
	free(s); s = g->str_concat(instance, ":host_plimit");
	tc->host_plimit = strdup(g->iniparser_getstring(ini, s, "\n\
$IPT -t filter -I FORWARD -p tcp -d %i -m limit --limit %plimit/s -m ipp2p --ipp2p -j ACCEPT\n\
$IPT -t filter -I FORWARD -p tcp -s %i -m limit --limit %plimit/s -m ipp2p --ipp2p -j ACCEPT\n"));
	free(s); s = g->str_concat(instance, ":networks");
	tc->networks = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":usergroups");
	tc->usergroups = strdup(g->iniparser_getstring(ini, s, ""));
	
	g->iniparser_freedict(ini);
	free(instance);
	free(s);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/tc] initialized", tc->base.instance);
#endif
	return (tc);
}
