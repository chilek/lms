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
//unsigned char *inet_ntoa(unsigned long);

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
		if( (ures = g->db_query("
			SELECT userid AS id, 
				CEIL(SUM(uprate)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS uprate, 
				CEIL(SUM(downrate)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS downrate, 
				CEIL(SUM(upceil)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS upceil, 
				CEIL(SUM(downceil)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS downceil, 
				CEIL(SUM(climit)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS climit, 
				CEIL(SUM(plimit)/COUNT(DISTINCT nodes.id)/COUNT(DISTINCT nodes.id)) AS plimit
			FROM assignments
				LEFT JOIN tariffs ON (tariffid = tariffs.id)
				LEFT JOIN nodes ON (userid = ownerid)
			WHERE access = 1 AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0)
			GROUP BY userid 
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
					int n_climit = atoi(climit);
					int n_plimit = atoi(plimit);

					if( (nres = g->db_pquery("
						SELECT INET_NTOA(ipaddr) AS ip, ipaddr, mac, name					
						FROM nodes
						WHERE ownerid = ? AND access = 1
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
	tc->file = strdup(g->iniparser_getstring(ini, s, "/tmp/rc.htb"));
	free(s); s = g->str_concat(instance, ":command");
	tc->command = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":begin");
	tc->begin = strdup(g->iniparser_getstring(ini, s, 
"#!/bin/sh
IPT=/usr/sbin/iptables
TC=/sbin/tc
LAN=eth1
WAN=eth0
BURST=\"burst 5k\"

#marking cleanup
$IPT -t mangle -F PREROUTING
#limits cleanup
$IPT -t filter -F FORWARD
$TC qdisc del dev $LAN root 2> /dev/null
$TC qdisc del dev $WAN root 2> /dev/null
# incomming traffic
$TC qdisc add dev $LAN root handle 1:0 htb default 3 r2q 1
$TC class add dev $LAN parent 1:0 classid 1:1 htb rate 99mbit ceil 99mbit
$TC class add dev $LAN parent 1:1 classid 1:2 htb rate 640kbit ceil 640kbit $BURST
$TC class add dev $LAN parent 1:1 classid 1:3 htb rate 98360kbit ceil 98360kbit prio 9
$TC qdisc add dev $LAN parent 1:3 sfq perturb 10 hash dst
# priorities for ICMP, TOS 0x10 and port 22
$TC class add dev $LAN parent 1:2 classid 1:20 htb rate 50kbit ceil 50kbit $BURST prio 1 quantum 1500
$TC qdisc add dev $LAN parent 1:20 sfq perturb 10 hash dst
$TC filter add dev $LAN parent 1:0 protocol ip prio 2 u32 match ip sport 22 0xffff flowid 1:20
$TC filter add dev $LAN parent 1:0 protocol ip prio 1 u32 match ip tos 0xff flowid 1:20
$TC filter add dev $LAN parent 1:0 protocol ip prio 1 u32 match ip protocol 1 0xff flowid 1:20
# server -> LAN
$TC filter add dev $LAN parent 1:0 protocol ip prio 4 handle 1 fw flowid 1:3

# outgoing traffic
$TC qdisc add dev $WAN root handle 2:0 htb default 11 r2q 1
$TC class add dev $WAN parent 2:0 classid 2:1 htb rate 160kbit ceil 160kbit $BURST 
# priorities for ACK, ICMP, TOS 0x10
$TC class add dev $WAN parent 2:1 classid 2:10 htb rate 54kbit ceil 54kbit $BURST prio 1 quantum 1500
$TC qdisc add dev $WAN parent 2:10 sfq perturb 10 hash dst
$TC filter add dev $WAN parent 2:0 protocol ip prio 2 u32 match ip protocol 6 0xff \
    match u8 0x05 0x0f at 0 match u16 0x0000 0xffc0 at 1 match u8 0x10 0xff at 33 flowid 2:10
$TC filter add dev $WAN parent 2:0 protocol ip prio 2 u32 match ip dport 22 0xffff flowid 2:10
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip tos 0xff flowid 2:10
$TC filter add dev $WAN parent 2:0 protocol ip prio 1 u32 match ip protocol 1 0xff flowid 2:10
# server -> Internet
$TC class add dev $WAN parent 2:1 classid 2:11 htb rate 34kbit ceil 34kbit $BURST prio 2 quantum 1500
$TC qdisc add dev $WAN parent 2:11 sfq perturb 10 hash dst
$TC filter add dev $WAN parent 2:0 protocol ip prio 3 handle 1 fw flowid 2:11
$TC filter add dev $WAN parent 2:0 protocol ip prio 9 u32 match ip dst 0/0 flowid 2:11
"));
	free(s); s = g->str_concat(instance, ":end");
	tc->end = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":host");
	tc->host = strdup(g->iniparser_getstring(ini, s, 
"# %n
$IPT -t mangle -A PREROUTING -s %i -j MARK --set-mark %x
$TC class add dev $LAN parent 1:2 classid 1:%x htb rate %downratekbit ceil %downceilkbit $BURST prio 2 quantum 1500
$TC qdisc add dev $LAN parent 1:%x sfq perturb 1 hash classic
$TC filter add dev $LAN parent 1:0 protocol ip prio 5 u32 match ip dst %i flowid 1:%x
$TC class add dev $WAN parent 2:1 classid 2:%x htb rate %upratekbit ceil %upceilkbit $BURST prio 2 quantum 1500
$TC qdisc add dev $WAN parent 2:%x sfq perturb 1 hash classic
$TC filter add dev $WAN parent 2:0 protocol ip prio 5 handle %x fw flowid 2:%x
"));
	free(s); s = g->str_concat(instance, ":host_climit");
	tc->host_climit = strdup(g->iniparser_getstring(ini, s, "
$IPT -t filter -I FORWARD -p tcp -s %i -m connlimit --connlimit-above %climit -j REJECT
"));
	free(s); s = g->str_concat(instance, ":host_plimit");
	tc->host_plimit = strdup(g->iniparser_getstring(ini, s, "
$IPT -t filter -I FORWARD -p tcp -d %i -m limit --limit %plimit/s -j ACCEPT
$IPT -t filter -I FORWARD -p tcp -s %i -m limit --limit %plimit/s -j ACCEPT
"));
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
