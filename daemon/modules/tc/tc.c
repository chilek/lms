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
#include "tc.h"

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
	sprintf(string, "%02X", i);
	return string;
}

void reload(GLOBAL *g, struct tc_module *tc)
{
	FILE *fh;
	QueryHandle *res, *ures, *nres;
	int x=100, i, j, v=0, m=0, k=2, n=2, nc=0, gc=0;

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
			res = g->db->pquery(g->db->conn, "SELECT name, address, INET_ATON(mask) AS mask, interface FROM networks WHERE UPPER(name)=UPPER('?')",netname);
			if( g->db->nrows(res) ) 
			{
		    		nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].name = strdup(g->db->get_data(res,0,"name"));
				nets[nc].interface = strdup(g->db->get_data(res,0,"interface"));
				nets[nc].address = inet_addr(g->db->get_data(res,0,"address"));
				nets[nc].mask = inet_addr(g->db->get_data(res,0,"mask"));
				nc++;
			}
    			g->db->free(&res);
		}
	}
	free(netname); free(netnames);

	// get table of networks (if 'networks' variable is not set)
	if(!nc)
	{
	        res = g->db->pquery(g->db->conn, "SELECT name, address, INET_ATON(mask) AS mask, interface FROM networks");
		for(nc=0; nc<g->db->nrows(res); nc++)
		{
		        nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
			nets[nc].name = strdup(g->db->get_data(res,nc,"name"));
			nets[nc].interface = strdup(g->db->get_data(res,nc,"interface"));
			nets[nc].address = inet_addr(g->db->get_data(res,nc,"address"));
			nets[nc].mask = inet_addr(g->db->get_data(res,nc,"mask"));
		}
		g->db->free(&res);
	 }

	// get table of customergroups
	while( k>1 ) 
	{
		k = sscanf(groupnames, "%s %[._a-zA-Z0-9- ]", groupname, groupnames);

		if( strlen(groupname) )
		{
			res = g->db->pquery(g->db->conn, "SELECT name, id FROM customergroups WHERE UPPER(name)=UPPER('?')", groupname);
			if( g->db->nrows(res) )
			{
				ugps = (struct group *) realloc(ugps, (sizeof(struct group) * (gc+1)));
				ugps[gc].name = strdup(g->db->get_data(res,0,"name"));
				ugps[gc].id = atoi(g->db->get_data(res,0,"id"));
				gc++;
			}
    			g->db->free(&res);
		}
	}
	free(groupname); free(groupnames);

	// open temporary file
	fh = fopen(tc->file, "w");
	if(fh) 
	{
		// get (htb) data for any customer with connected nodes and active assignments
		// we need customer ID and average data values for nodes
		ures = g->db->query(g->db->conn, "\
			SELECT customerid AS id, \
			    COUNT(DISTINCT nodes.id) AS cnt, \
				ROUND(SUM(uprate)/COUNT(DISTINCT nodes.id)) AS uprate, \
				ROUND(SUM(downrate)/COUNT(DISTINCT nodes.id)) AS downrate, \
				ROUND(SUM(upceil)/COUNT(DISTINCT nodes.id)) AS upceil, \
				ROUND(SUM(downceil)/COUNT(DISTINCT nodes.id)) AS downceil, \
				ROUND(SUM(climit)/COUNT(DISTINCT nodes.id)) AS climit, \
				ROUND(SUM(plimit)/COUNT(DISTINCT nodes.id)) AS plimit \
			FROM assignments a \
				LEFT JOIN tariffs ON (tariffid = tariffs.id) \
				LEFT JOIN nodes ON (customerid = ownerid) \
			WHERE access = 1 AND a.datefrom <= %NOW% AND (a.dateto >= %NOW% OR a.dateto = 0) \
			GROUP BY customerid \
			ORDER BY customerid");
		
		if( g->db->nrows(ures) )
		{
			fprintf(fh, "%s", tc->begin);
		
			for(i=0; i<g->db->nrows(ures); i++) 
			{
				// test customer's membership in customergroups
				if(gc)
				{
					res = g->db->pquery(g->db->conn, "SELECT customergroupid FROM customerassignments WHERE customerid=?", g->db->get_data(ures,i,"id"));
					for(k=0; k<g->db->nrows(res); k++) 
					{
						int groupid = atoi(g->db->get_data(res, k, "customergroupid"));
						for(m=0; m<gc; m++) 
							if(ugps[m].id==groupid) 
								break;
						if(m!=gc) break;
					}
					g->db->free(&res);
				}
					
				if( !gc || m!=gc )
				{
					char *uprate = g->db->get_data(ures,i,"uprate");
					char *downrate = g->db->get_data(ures,i,"downrate");
					char *upceil = g->db->get_data(ures,i,"upceil");
					char *downceil = g->db->get_data(ures,i,"downceil");
					char *climit = g->db->get_data(ures,i,"climit");
					char *plimit = g->db->get_data(ures,i,"plimit");
					int n_upceil = atoi(upceil);
					int n_downceil = atoi(downceil);
					int n_uprate = atoi(uprate);
					int n_downrate = atoi(downrate);
					int n_climit = atoi(climit);
					int n_plimit = atoi(plimit);
					int cnt = atoi(g->db->get_data(ures,i,"cnt"));
					
					int got_node = 0;

					nres = g->db->pquery(g->db->conn, " \
						SELECT INET_NTOA(ipaddr) AS ip, mac, name \
						FROM vmacs \
						WHERE ownerid = ? AND access = 1 \
						ORDER BY ipaddr", g->db->get_data(ures,i,"id"));
					
					for(j=0; j<g->db->nrows(nres); j++) 
					{
						char *ipaddr = g->db->get_data(nres,j,"ip");
						char *mac = g->db->get_data(nres,j,"mac");
						char *name = g->db->get_data(nres,j,"name");
						char *mark_up = strdup(tc->host_mark_up);
						char *mark_down = strdup(tc->host_mark_down);
						char *htb_up = strdup(tc->host_htb_up);
						char *htb_down = strdup(tc->host_htb_down);
						char *cl = strdup(tc->host_climit);
						char *pl = strdup(tc->host_plimit);
						int h_uprate = (int) n_uprate/cnt;
						int h_upceil = (int) n_upceil/cnt;
						int h_downrate = (int) n_downrate/cnt;
						int h_downceil = (int) n_downceil/cnt;
						int h_plimit = (int) n_plimit/cnt;
						int h_climit = (int) n_climit/cnt;

						unsigned long iplong = inet_addr(ipaddr);
						unsigned int hostip = ntohl(iplong);

                        int d1 = (hostip >> 24) & 0xff; // 1st octet
                        int d2 = (hostip >> 16) & 0xff; // 2nd octet
                        int d3 = (hostip >> 8) & 0xff;  // 3rd octet
                        int d4 = hostip & 0xff;         // 4th octet

						char *o1 = strdup(itoa(d1));
						char *o2 = strdup(itoa(d2));
						char *o3 = strdup(itoa(d3));
						char *o4 = strdup(itoa(d4));

						char *h1 = strdup(itoha(d1)); // 1st octet in hex
						char *h2 = strdup(itoha(d2)); // 2nd octet in hex
						char *h3 = strdup(itoha(d3)); // 3rd octet in hex
						char *h4 = strdup(itoha(d4)); // 4th octet in hex

						// test node's membership in networks
						for(v=0; v<nc; v++)
							if(nets[v].address == (iplong & nets[v].mask)) 
								break;

						if(v!=nc)
						{
							got_node = 1;

							if(h_uprate && h_downrate)
							{
								g->str_replace(&mark_up, "%n", name);
								g->str_replace(&mark_up, "%if", nets[v].interface);
								g->str_replace(&mark_up, "%h1", h1);
								g->str_replace(&mark_up, "%h2", h2);
								g->str_replace(&mark_up, "%h3", h3);
								g->str_replace(&mark_up, "%h4", h4);
								g->str_replace(&mark_up, "%i16", h4); // for backward compat.
								g->str_replace(&mark_up, "%i", ipaddr);
								g->str_replace(&mark_up, "%m", mac);
								g->str_replace(&mark_up, "%o1", o1);
								g->str_replace(&mark_up, "%o2", o2);
								g->str_replace(&mark_up, "%o3", o3);
								g->str_replace(&mark_up, "%o4", o4);
								g->str_replace(&mark_up, "%x", itoa(x));
								fprintf(fh, "%s", mark_up);

								g->str_replace(&mark_down, "%n", name);
								g->str_replace(&mark_down, "%if", nets[v].interface);
								g->str_replace(&mark_down, "%h1", h1);
								g->str_replace(&mark_down, "%h2", h2);
								g->str_replace(&mark_down, "%h3", h3);
								g->str_replace(&mark_down, "%h4", h4);
								g->str_replace(&mark_down, "%i16", h4); // for backward comapt.
								g->str_replace(&mark_down, "%i", ipaddr);
								g->str_replace(&mark_down, "%m", mac);
								g->str_replace(&mark_down, "%o1", o1);
								g->str_replace(&mark_down, "%o2", o2);
								g->str_replace(&mark_down, "%o3", o3);
								g->str_replace(&mark_down, "%o4", o4);
								g->str_replace(&mark_down, "%x", itoa(x));
								fprintf(fh, "%s", mark_down);
					
								if(tc->one_class_per_host)
								{
									g->str_replace(&htb_up, "%n", name);
									g->str_replace(&htb_up, "%if", nets[v].interface);
    								g->str_replace(&htb_up, "%h1", h1);
	    							g->str_replace(&htb_up, "%h2", h2);
		    						g->str_replace(&htb_up, "%h3", h3);
			    					g->str_replace(&htb_up, "%h4", h4);
									g->str_replace(&htb_up, "%i16", h4); // for backard compat.
									g->str_replace(&htb_up, "%i", ipaddr);
									g->str_replace(&htb_up, "%m", mac);
    								g->str_replace(&htb_up, "%o1", o1);
	    							g->str_replace(&htb_up, "%o2", o2);
		    						g->str_replace(&htb_up, "%o3", o3);
			    					g->str_replace(&htb_up, "%o4", o4);
									g->str_replace(&htb_up, "%x", itoa(x));
									g->str_replace(&htb_up, "%uprate", itoa(h_uprate));
									if(!h_upceil)
										g->str_replace(&htb_up, "%upceil", itoa(h_uprate));
									else
										g->str_replace(&htb_up, "%upceil", itoa(h_upceil));
								
									g->str_replace(&htb_down, "%n", name);
									g->str_replace(&htb_down, "%if", nets[v].interface);
    								g->str_replace(&htb_down, "%h1", h1);
	    							g->str_replace(&htb_down, "%h2", h2);
		    						g->str_replace(&htb_down, "%h3", h3);
			    					g->str_replace(&htb_down, "%h4", h4);
									g->str_replace(&htb_down, "%i16", h4); // for backward compat.
									g->str_replace(&htb_down, "%i", ipaddr);
									g->str_replace(&htb_down, "%m", mac);
    								g->str_replace(&htb_down, "%o1", o1);
	    							g->str_replace(&htb_down, "%o2", o2);
		    						g->str_replace(&htb_down, "%o3", o3);
			    					g->str_replace(&htb_down, "%o4", o4);
									g->str_replace(&htb_down, "%x", itoa(x));
									g->str_replace(&htb_down, "%downrate", itoa(h_downrate));
									if(!h_downceil)
										g->str_replace(&htb_down, "%downceil", itoa(h_downrate));
									else
										g->str_replace(&htb_down, "%downceil", itoa(h_downceil));
						
									// write to file
									fprintf(fh, "%s", htb_up);
									fprintf(fh, "%s", htb_down);
								}
							}
							
							if(!tc->limit_per_host)
							{
							    if(h_climit)
							    {
								g->str_replace(&cl, "%climit", itoa(h_climit));
								g->str_replace(&cl, "%n", name);
								g->str_replace(&cl, "%if", nets[v].interface);
								g->str_replace(&cl, "%h1", h1);
								g->str_replace(&cl, "%h2", h2);
								g->str_replace(&cl, "%h3", h3);
								g->str_replace(&cl, "%h4", h4);
   								g->str_replace(&cl, "%i16", h4); // for backward compat.
								g->str_replace(&cl, "%i", ipaddr);
								g->str_replace(&cl, "%m", mac);
								g->str_replace(&cl, "%o1", o1);
								g->str_replace(&cl, "%o2", o2);
								g->str_replace(&cl, "%o3", o3);
								g->str_replace(&cl, "%o4", o4);
								g->str_replace(&cl, "%x", itoa(x));
								fprintf(fh, "%s", cl);
							    }

							    if(h_plimit)
							    {
								g->str_replace(&pl, "%plimit", itoa(h_plimit));
								g->str_replace(&pl, "%n", name);
								g->str_replace(&pl, "%if", nets[v].interface);
								g->str_replace(&pl, "%h1", h1);
								g->str_replace(&pl, "%h2", h2);
								g->str_replace(&pl, "%h3", h3);
								g->str_replace(&pl, "%h4", h4);
								g->str_replace(&pl, "%i16", h4); // for backward compat.
								g->str_replace(&pl, "%i", ipaddr);
								g->str_replace(&pl, "%m", mac);
								g->str_replace(&pl, "%o1", o1);
								g->str_replace(&pl, "%o2", o2);
								g->str_replace(&pl, "%o3", o3);
								g->str_replace(&pl, "%o4", o4);
								g->str_replace(&pl, "%x", itoa(x));
								fprintf(fh, "%s", pl);
							    }
							} else
							{
							    if(n_climit)
							    {
								g->str_replace(&cl, "%climit", itoa(n_climit));
								g->str_replace(&cl, "%n", name);
								g->str_replace(&cl, "%if", nets[v].interface);
								g->str_replace(&cl, "%h1", h1);
								g->str_replace(&cl, "%h2", h2);
								g->str_replace(&cl, "%h3", h3);
								g->str_replace(&cl, "%h4", h4);
    							g->str_replace(&cl, "%i16", h4); // for backward compat.
    							g->str_replace(&cl, "%i", ipaddr);
								g->str_replace(&cl, "%m", mac);
								g->str_replace(&cl, "%o1", o1);
								g->str_replace(&cl, "%o2", o2);
								g->str_replace(&cl, "%o3", o3);
								g->str_replace(&cl, "%o4", o4);
								g->str_replace(&cl, "%x", itoa(x));
								fprintf(fh, "%s", cl);
							    }

							    if(n_plimit)
							    {
								g->str_replace(&pl, "%plimit", itoa(n_plimit));
								g->str_replace(&pl, "%n", name);
								g->str_replace(&pl, "%if", nets[v].interface);
								g->str_replace(&pl, "%h1", h1);
								g->str_replace(&pl, "%h2", h2);
								g->str_replace(&pl, "%h3", h3);
								g->str_replace(&pl, "%h4", h4);
								g->str_replace(&pl, "%i16", h4); // for backward compat.
								g->str_replace(&pl, "%i", ipaddr);
								g->str_replace(&pl, "%m", mac);
								g->str_replace(&pl, "%o1", o1);
								g->str_replace(&pl, "%o2", o2);
								g->str_replace(&pl, "%o3", o3);
								g->str_replace(&pl, "%o4", o4);
								g->str_replace(&pl, "%x", itoa(x));
								fprintf(fh, "%s", pl);
							    }
							}

							if(tc->one_class_per_host) x++;
						}

						if(!tc->one_class_per_host && j==g->db->nrows(nres)-1 && got_node && n_downrate && n_uprate)
						{
							g->str_replace(&htb_up, "%n", name);
							g->str_replace(&htb_up, "%x", itoa(x));
							g->str_replace(&htb_up, "%uprate", uprate);
							if(!n_upceil)
								g->str_replace(&htb_up, "%upceil", uprate);
							else
								g->str_replace(&htb_up, "%upceil", upceil);
							g->str_replace(&htb_down, "%n", name);
							g->str_replace(&htb_down, "%x", itoa(x));
							g->str_replace(&htb_down, "%downrate", downrate);
							if(!n_downceil)
								g->str_replace(&htb_down, "%downceil", downrate);
							else
								g->str_replace(&htb_down, "%downceil", downceil);
						
							// write to file
							fprintf(fh, "%s", htb_up);
							fprintf(fh, "%s", htb_down);
							
							x++;
						}
						
						free(cl); free(pl); 
						free(mark_up); free(mark_down);
						free(htb_up); free(htb_down);
						free(o1); free(o2); free(o3); free(o4);
						free(h1); free(h2); free(h3); free(h4);
					}
					g->db->free(&nres);
				}
			}
		
			fprintf(fh, "%s", tc->end);
		}
		else
			syslog(LOG_ERR, "[%s/tc] Unable to read database", tc->base.instance);
		
		g->db->free(&ures);
		fclose(fh);
		system(tc->command);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/tc] reloaded", tc->base.instance);	
#endif
	}
	else
		syslog(LOG_ERR, "[%s/tc] Unable to write a temporary file '%s'", tc->base.instance, tc->file);

	for(i=0;i<nc;i++)
	{
		free(nets[i].name);
		free(nets[i].interface);
	}
	free(nets);
	
	for(i=0;i<gc;i++)
		free(ugps[i].name);
	free(ugps);
	
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
	tc->one_class_per_host = g->config_getbool(tc->base.ini, tc->base.instance, "one_class_per_host", 0);
	tc->limit_per_host = g->config_getbool(tc->base.ini, tc->base.instance, "limit_per_host", 0);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/tc] initialized", tc->base.instance);
#endif
	return (tc);
}
