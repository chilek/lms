/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2007 LMS Developers
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
#include <time.h>
#include <netinet/in.h>

#include "lmsd.h"
#include "tc-new.h"

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
	QueryHandle *res;
	int i, j, k=2, n=2, cc=0, nc=0, night=0;
	char *query;

	struct channel *channels = (struct channel *) malloc(sizeof(struct channel));

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(tc->networks);	
	char *netname = strdup(netnames);

	char *groups = strdup(" AND EXISTS (SELECT 1 FROM customergroups g, customerassignments ca "
				"WHERE a.customerid = ca.customerid "
				"AND g.id = ca.customergroupid "
				"AND (%groups)) "
				);

	char *groupnames = strdup(tc->customergroups);
	char *groupname = strdup(groupnames);
	char *groupsql = strdup("");

	while( k>1 )
	{
		k = sscanf(groupnames, "%s %[._a-zA-Z0-9- ]", groupname, groupnames);

		if( strlen(groupname) )
		{
			groupsql = realloc(groupsql, sizeof(char *) * (strlen(groupsql) + strlen(groupname) + 30));
			if(strlen(groupsql))
				strcat(groupsql, " OR UPPER(g.name) = UPPER('");
			else
				strcat(groupsql, "UPPER(g.name) = UPPER('");

			strcat(groupsql, groupname);
			strcat(groupsql, "')");
		}
	}
	free(groupname); free(groupnames);

	if(strlen(groupsql))
		g->str_replace(&groups, "%groups", groupsql);

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

	// handle night-time tariffs
	if (strlen(tc->night_hours))
	{
		int start_h, end_h;

		if (sscanf(tc->night_hours, "%d-%d", &start_h, &end_h) == 2)
		{
			// get current date
			time_t t = time(NULL);
			struct tm *tt = localtime(&t);
			int hour = tt->tm_hour;

			if (end_h < 18) end_h += 24; 
			if (start_h < 18) start_h += 24; 
			if (hour < 18) hour += 24; 

			if (start_h >= end_h)
				syslog(LOG_ERR, "[%s/tc-new] Wrong 'night_hours' format: %s", tc->base.instance, tc->night_hours);
			else if (hour >= start_h && hour < end_h)
				night = 1;
		}
		else
	    		syslog(LOG_ERR, "[%s/tc-new] Wrong 'night_hours' format: %s", tc->base.instance, tc->night_hours);
	}

	// nodes
	query = strdup("SELECT t.downrate AS downrate, t.downceil AS downceil, t.uprate AS uprate, "
			"t.upceil AS upceil, t.climit AS climit, t.plimit AS plimit, "
			"n.id, n.ownerid, n.name, INET_NTOA(n.ipaddr) AS ip, n.mac, "
			"na.assignmentid, "
			"TRIM(%cfullname) AS customer "
		"FROM nodeassignments na "
		"JOIN assignments a ON (na.assignmentid = a.id) "
		"JOIN tariffs t ON (a.tariffid = t.id) "
		"JOIN %nodes n ON (na.nodeid = n.id) "
		"JOIN customers c ON (a.customerid = c.id) "
		"%night_debtors"
		"WHERE "
			"a.datefrom <= %NOW% "
			"AND (a.dateto >= %NOW% OR a.dateto = 0) "
			"AND n.access = 1 "
			"AND (t.downrate > 0 OR t.downceil > 0 OR t.uprate > 0 OR t.upceil > 0) "
			"%groups"
		"ORDER BY a.customerid, a.id");

	char * cfullname = g->db->concat(3, "c.lastname", "' '", "c.name");
	g->str_replace(&query, "%cfullname", cfullname);
	free(cfullname);

	if (night)
	{
		g->str_replace(&query, "t.downrate", "(CASE WHEN %debtor t.downrate_n > 0 THEN t.downrate_n ELSE t.downrate END)");
		g->str_replace(&query, "t.downceil", "(CASE WHEN %debtor t.downceil_n > 0 THEN t.downceil_n ELSE t.downceil END)");
		g->str_replace(&query, "t.uprate", "(CASE WHEN %debtor t.uprate_n > 0 THEN t.uprate_n ELSE t.uprate END)");
		g->str_replace(&query, "t.upceil", "(CASE WHEN %debtor t.upceil_n > 0 THEN t.upceil_n ELSE t.upceil END)");
		g->str_replace(&query, "t.climit", "(CASE WHEN %debtor t.climit_n IS NOT NULL THEN t.climit_n ELSE t.climit END)");
		g->str_replace(&query, "t.plimit", "(CASE WHEN %debtor t.plimit_n IS NOT NULL THEN t.plimit_n ELSE t.plimit END)");
		g->str_replace(&query, "%debtor", tc->night_no_debtors ? "d.id IS NULL AND" : "");
	}

	if (night && tc->night_no_debtors)
	{
		g->str_replace(&query, "%night_debtors",
			"LEFT JOIN ("
				"SELECT DISTINCT d.customerid AS id "
				"FROM documents d "
//				"JOIN customers c ON (d.customerid = c.id) "
				"WHERE d.type = 1 "
					"AND d.closed = 0 "
					"AND d.cdate + d.paytime * 86400 < %NOW% - 86400 * %deadline"
//					" AND c.deleted = 0"
//					" AND c.cutoffstop < %NOW%"
			") d ON (n.ownerid = d.id) "
		);
		g->str_replace(&query, "%deadline", itoa(tc->night_deadline));
	}
	else
		g->str_replace(&query, "%night_debtors", "");

    g->str_replace(&query, "%nodes", tc->multi_mac ? "vmacs" : "vnodes");
	g->str_replace(&query, "%groups", strlen(groupsql) ? groups : "");

	res = g->db->query(g->db->conn, query);
	free(query);

	if(!g->db->nrows(res))
	{
        syslog(LOG_ERR, "[%s/tc-new] Unable to read database or assignments table is empty", tc->base.instance);
		return;
	}

	// adding nodes to channels array
	for(i=0; i<g->db->nrows(res); i++)
    {
		int assignmentid 	= atoi(g->db->get_data(res,i,"assignmentid"));
		char *ip 		= g->db->get_data(res,i,"ip");
		unsigned long inet 	= inet_addr(ip);

		// Networks test
		for(n=0; n<nc; n++)
            if(nets[n].address == (inet & nets[n].mask))
                break;

		if(n == nc) continue;

		// looking for channel
		for(j=0; j<cc; j++)
			if(channels[j].id == assignmentid)
				break;

       	int nodeid 	 = atoi(g->db->get_data(res,i,"id"));
		int uprate 	 = atoi(g->db->get_data(res,i,"uprate"));
		int downrate = atoi(g->db->get_data(res,i,"downrate"));
		int upceil 	 = atoi(g->db->get_data(res,i,"upceil"));
		int downceil = atoi(g->db->get_data(res,i,"downceil"));
		int climit 	 = atoi(g->db->get_data(res,i,"climit"));
		int plimit 	 = atoi(g->db->get_data(res,i,"plimit"));

		if(j == cc) // channel (assignment) not found
		{
			int x, y;

			// mozliwe ze komputer jest juz przypisany do innego
			// zobowiazania, uwzgledniamy to...
			for(j=0; j<cc; j++)
			{
				for(x=0; x<channels[j].no; x++)
		            if(channels[j].nodes[x].id == nodeid)
	        	        break;

				if(x != channels[j].no)
					break;
			}

			// ...komputer znaleziony, sprawdzmy czy kanal nie
			// zawiera juz tego zobowiazania
			if(j != cc)
			{
				for(y=0; y<channels[j].subno; y++)
		            		if(channels[j].subs[y] == assignmentid)
	        	            		break;

				// zobowiazanie nie znalezione, zwiekszamy kanal
				if(y == channels[j].subno)
				{
					channels[j].uprate += uprate;
					channels[j].upceil += upceil;
					channels[j].downrate += downrate;
					channels[j].downceil += downceil;
					channels[j].climit += climit;
					channels[j].plimit += plimit;

					channels[j].subs = (int *) realloc(channels[j].subs, sizeof(int) * channels[j].subno + 1);
					channels[j].subs[channels[j].subno] = assignmentid;
					channels[j].subno++;
				}

				continue;
			}

			// ...nie znaleziono komputera, tworzymy kanal
			channels = (struct channel *) realloc(channels, (sizeof(struct channel) * (cc+1)));

			channels[cc].id = assignmentid;
			channels[cc].no = 0;
			channels[cc].nodes = NULL;
			channels[cc].subno = 0;
			channels[cc].subs = NULL;
			channels[cc].cid = atoi(g->db->get_data(res,i,"ownerid"));
			channels[cc].customer = strdup(g->db->get_data(res,i,"customer"));

			channels[cc].uprate = uprate;
			channels[cc].upceil = upceil;
			channels[cc].downrate = downrate;
			channels[cc].downceil = downceil;
			channels[cc].climit = climit;
			channels[cc].plimit = plimit;
			cc++;
		}

		k = channels[j].no;
		channels[j].nodes = (struct node *) realloc(channels[j].nodes, (sizeof(struct node) * (k+1)));
		channels[j].nodes[k].id = nodeid;
		channels[j].nodes[k].network = n;
		channels[j].nodes[k].ip = strdup(ip);
		channels[j].nodes[k].name = strdup(g->db->get_data(res,i,"name"));
		channels[j].nodes[k].mac = strdup(g->db->get_data(res,i,"mac"));
		channels[j].no++;
	}
	g->db->free(&res);

	// open file
	fh = fopen(tc->file, "w");
	if(fh) 
	{
		int ux = XVALUE;
		int dx = XVALUE;
		int umark = XVALUE;
		int dmark = XVALUE+1;

		fprintf(fh, "%s", tc->begin);

		// channels loop
		for(i=0; i<cc; i++)
		{
			struct channel c = channels[i];

			char *c_up = strdup(tc->class_up);
			char *c_down = strdup(tc->class_down);

			// make rules...
			g->str_replace(&c_up, "%cid", itoa(c.cid));
			g->str_replace(&c_up, "%cname", c.customer);
			g->str_replace(&c_up, "%h", itoa(ux));
			g->str_replace(&c_up, "%uprate", itoa(c.uprate));

			if(!c.upceil)
				g->str_replace(&c_up, "%upceil", itoa(c.uprate));
			else
				g->str_replace(&c_up, "%upceil", itoa(c.upceil));

			g->str_replace(&c_down, "%cid", itoa(c.cid));
			g->str_replace(&c_down, "%cname", c.customer);
			g->str_replace(&c_down, "%h", itoa(dx));
			g->str_replace(&c_down, "%downrate", itoa(c.downrate));

			if(!c.downceil)
				g->str_replace(&c_down, "%downceil", itoa(c.downrate));
			else
				g->str_replace(&c_down, "%downceil", itoa(c.downceil));

			// ... and write to file
			fprintf(fh, "%s", c_up);
			fprintf(fh, "%s", c_down);

			free(c_up);
			free(c_down);

			for(j=0; j<c.no; j++)
			{
				struct node host = c.nodes[j];

				// octal parts of IP
				unsigned int hostip = ntohl(inet_addr(host.ip));

				int d1 = (hostip >> 24) & 0xff; // first octet
				int d2 = (hostip >> 16) & 0xff; // second octet
				int d3 = (hostip >> 8) & 0xff;  // third octet
				int d4 = hostip & 0xff;         // last octet

				char *o1 = strdup(itoa(d1));
				char *o2 = strdup(itoa(d2));
				char *o3 = strdup(itoa(d3));
				char *o4 = strdup(itoa(d4));

				char *h1 = strdup(itoha(d1));
				char *h2 = strdup(itoha(d2));
				char *h3 = strdup(itoha(d3));
				char *h4 = strdup(itoha(d4));

				char *h_up = strdup(tc->filter_up);
				char *h_down = strdup(tc->filter_down);

				// get first mac from the list
				char *mac = strdup(host.mac);
				if (!tc->multi_mac && strlen(mac)) {
				    mac = strtok(mac, ",");
				}

				// make rules...
				g->str_replace(&h_up, "%n", host.name);
				g->str_replace(&h_up, "%if", nets[host.network].interface);
				g->str_replace(&h_up, "%i16", h4);
				g->str_replace(&h_up, "%i", host.ip);
				g->str_replace(&h_up, "%ms", host.mac);
				g->str_replace(&h_up, "%m", mac);
				g->str_replace(&h_up, "%x", itoa(umark));
				g->str_replace(&h_up, "%o1", o1);
				g->str_replace(&h_up, "%o2", o2);
				g->str_replace(&h_up, "%o3", o3);
				g->str_replace(&h_up, "%o4", o4);
				g->str_replace(&h_up, "%h1", h1);
				g->str_replace(&h_up, "%h2", h2);
				g->str_replace(&h_up, "%h3", h3);
				g->str_replace(&h_up, "%h4", h4);
				g->str_replace(&h_up, "%h", itoa(ux));

				g->str_replace(&h_down, "%n", host.name);
				g->str_replace(&h_down, "%if", nets[host.network].interface);
				g->str_replace(&h_down, "%i16", h4);
				g->str_replace(&h_down, "%i", host.ip);
				g->str_replace(&h_down, "%ms", host.mac);
				g->str_replace(&h_down, "%m", mac);
				g->str_replace(&h_down, "%x", itoa(dmark));
				g->str_replace(&h_down, "%o1", o1);
				g->str_replace(&h_down, "%o2", o2);
				g->str_replace(&h_down, "%o3", o3);
				g->str_replace(&h_down, "%o4", o4);
				g->str_replace(&h_down, "%h1", h1);
				g->str_replace(&h_down, "%h2", h2);
				g->str_replace(&h_down, "%h3", h3);
				g->str_replace(&h_down, "%h4", h4);
				g->str_replace(&h_down, "%h", itoa(dx));

				// ...write to file
				fprintf(fh, "%s", h_up);
				fprintf(fh, "%s", h_down);

				free(h_up);
				free(h_down);

				if(c.climit)
				{
					char *cl = strdup(tc->climit);

					g->str_replace(&cl, "%climit", itoa(c.climit));
					g->str_replace(&cl, "%n", host.name);
					g->str_replace(&cl, "%if", nets[host.network].interface);
    				g->str_replace(&cl, "%i16", h4);
    				g->str_replace(&cl, "%i", host.ip);
    				g->str_replace(&cl, "%ms", host.mac);
    				g->str_replace(&cl, "%m", mac);
					g->str_replace(&cl, "%o1", o1);
					g->str_replace(&cl, "%o2", o2);
					g->str_replace(&cl, "%o3", o3);
					g->str_replace(&cl, "%o4", o4);
					g->str_replace(&cl, "%h1", h1);
					g->str_replace(&cl, "%h2", h2);
					g->str_replace(&cl, "%h3", h3);
					g->str_replace(&cl, "%h4", h4);

					fprintf(fh, "%s", cl);
					free(cl);
				}

				if(c.plimit)
				{
					char *pl = strdup(tc->plimit);

					g->str_replace(&pl, "%plimit", itoa(c.plimit));
					g->str_replace(&pl, "%n", host.name);
					g->str_replace(&pl, "%if", nets[host.network].interface);
					g->str_replace(&pl, "%i16", h4);
					g->str_replace(&pl, "%i", host.ip);
					g->str_replace(&pl, "%ms", host.mac);
					g->str_replace(&pl, "%m", mac);
					g->str_replace(&pl, "%o1", o1);
					g->str_replace(&pl, "%o2", o2);
					g->str_replace(&pl, "%o3", o3);
					g->str_replace(&pl, "%o4", o4);
					g->str_replace(&pl, "%h1", h1);
					g->str_replace(&pl, "%h2", h2);
					g->str_replace(&pl, "%h3", h3);
					g->str_replace(&pl, "%h4", h4);

					fprintf(fh, "%s", pl);
					free(pl);
				}

				dmark += 2;
				umark += 2;

				free(o1);
				free(o2);
				free(o3);
				free(o4);
				free(h1);
				free(h2);
				free(h3);
				free(h4);
				free(mac);
			}

			ux += 2;
			dx += 2;
		}

		// file footer
		fprintf(fh, "%s", tc->end);

		fclose(fh);
		system(tc->command);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/tc-new] reloaded", tc->base.instance);	
#endif
	}
	else
		syslog(LOG_ERR, "[%s/tc-new] Unable to write to file '%s'", tc->base.instance, tc->file);

	for(i=0; i<nc; i++)
	{
		free(nets[i].name);
		free(nets[i].interface);
	}
	free(nets);

	for(i=0; i<cc; i++)
	{
		for(j=0; j<channels[i].no; j++)
		{
			free(channels[i].nodes[j].ip);
			free(channels[i].nodes[j].name);
			free(channels[i].nodes[j].mac);
		}
		free(channels[i].nodes);
		free(channels[i].subs);
		free(channels[i].customer);
	}
	free(channels);

	free(groupsql);
	free(tc->file);
	free(tc->command);
	free(tc->begin);
	free(tc->end);
	free(tc->class_up);
	free(tc->class_down);
	free(tc->filter_up);
	free(tc->filter_down);
	free(tc->climit);
	free(tc->plimit);
	free(tc->networks);
	free(tc->customergroups);
	free(tc->night_hours);
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
	tc->class_up = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "class_up",
	"# %cname (ID:%cid)\n"
	"$TC class add dev $WAN parent 2:1 classid 2:%h htb rate %upratekbit ceil %upceilkbit $BURST prio 2 quantum 1500\n"
	"$TC qdisc add dev $WAN parent 2:%h esfq perturb 10 hash dst\n"
	));

	tc->class_down = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "class_down",
	"$TC class add dev $LAN parent 1:2 classid 1:%h htb rate %downratekbit ceil %downceilkbit $BURST prio 2 quantum 1500\n"
	"$TC qdisc add dev $LAN parent 1:%h esfq perturb 10 hash dst\n"
	));

	tc->filter_up = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "filter_up",
	"# %n\n"
	"$IPT -t mangle -A LIMITS -s %i -j MARK --set-mark %x\n"
	"$TC filter add dev $WAN parent 2:0 protocol ip prio 5 handle %x fw flowid 2:%h\n"
	));

	tc->filter_down = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "filter_down",
	"$IPT -t mangle -A LIMITS -d %i -j MARK --set-mark %x\n"
	"$TC filter add dev $LAN parent 1:0 protocol ip prio 5 handle %x fw flowid 1:%h\n"
	));

	tc->climit = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "climit",
	"$IPT -t filter -I FORWARD -p tcp -s %i -m connlimit --connlimit-above %climit -j REJECT\n"
	));

	tc->plimit = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "plimit",
	"$IPT -t filter -I FORWARD -p tcp -d %i -m limit --limit %plimit/s -j ACCEPT\n"
	"$IPT -t filter -I FORWARD -p tcp -s %i -m limit --limit %plimit/s -j ACCEPT\n"
	));

	tc->networks = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "networks", ""));
	tc->customergroups = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "customergroups", ""));
	tc->night_hours = strdup(g->config_getstring(tc->base.ini, tc->base.instance, "night_hours", ""));
	tc->night_no_debtors = g->config_getbool(tc->base.ini, tc->base.instance, "night_no_debtors", 0);
	tc->night_deadline = g->config_getint(tc->base.ini, tc->base.instance, "night_deadline", 0);
    tc->multi_mac = g->config_getbool(tc->base.ini, tc->base.instance, "multi_mac", 0);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/tc-new] initialized", tc->base.instance);
#endif
	return (tc);
}
