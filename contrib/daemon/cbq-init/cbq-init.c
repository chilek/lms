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
#include <syslog.h>
#include <string.h>
#include <stdlib.h>

#include "lmsd.h"
#include "cbq-init.h"

unsigned long inet_addr(unsigned char *);

char * itoa(int i)
{
	static char string[12];
	sprintf(string, "%d", i);
	return string;
}

void reload(GLOBAL *g, struct cbq_module *cbq)
{
	FILE *fh, *fh1, *fh2;
	QueryHandle *res, *ures, *nres;
	int x=100, i, j, v, k=2, m=0, n=2, nc=0, gc=0;
	char *file;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(cbq->networks);	
	char *netname = strdup(netnames);

	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(cbq->customergroups);	
	char *groupname = strdup(groupnames);

	// get table of networks
	while( n>1 ) 
	{
		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) ) 
		{
			res = g->db_pquery(g->conn, "SELECT name, domain, address, INET_ATON(mask) AS mask, interface FROM networks WHERE UPPER(name)=UPPER('?')",netname);
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

	if(!nc)
	{
		res = g->db_query(g->conn, "SELECT name, domain, address, INET_ATON(mask) AS mask, interface, gateway FROM networks");
		for(nc=0; nc<g->db_nrows(res); nc++) 
		{
			nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
			nets[nc].name = strdup(g->db_get_data(res,nc,"name"));
			nets[nc].interface = strdup(g->db_get_data(res,0,"interface"));
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

	fh = fopen(cbq->mark_file, "w");
	if(fh)
	{
		// get data for any customer with connected nodes and active assignments
		// we need customer ID and average data values for nodes
		ures = g->db_query(g->conn, "SELECT customerid AS id, SUM(uprate)/COUNT(DISTINCT vnodes.id) AS uprate, SUM(downrate)/COUNT(DISTINCT vnodes.id) AS downrate, SUM(upceil)/COUNT(DISTINCT vnodes.id) AS upceil, SUM(downceil)/COUNT(DISTINCT vnodes.id) AS downceil, SUM(climit)/COUNT(DISTINCT vnodes.id) AS climit, SUM(plimit)/COUNT(DISTINCT vnodes.id) AS plimit FROM assignments LEFT JOIN tariffs ON (tariffid = tariffs.id) LEFT JOIN vnodes ON (customerid = ownerid) WHERE access = 1 AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) GROUP BY customerid ORDER BY customerid");
		if( g->db_nrows(ures) )
		{
			// delete old configuration files
			char *cmd = strdup("rm -f %d/cbq-*");
			g->str_replace(&cmd, "%d", cbq->path);
			system(cmd);
			free(cmd);
			
			fprintf(fh, "%s", cbq->mark_file_begin);
		
			for(i=0; i<g->db_nrows(ures); i++) 
			{	
				// test customer's membership in customergroups
				m = 0;
				if(gc)
				{
					res = g->db_pquery(g->conn, "SELECT customergroupid FROM customerassignments WHERE customerid=?", g->db_get_data(ures,i,"id"));
					for(k=0; k<g->db_nrows(res); k++) 
					{
						int groupid = atoi(g->db_get_data(res, k, "customergroupid"));
						for(m=0; m<gc; m++) 
							if(ugps[m].id==groupid) 
								break;
						if(m!=gc) break;
					}
					g->db_free(&res);
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
					
					nres = g->db_pquery(g->conn, "SELECT INET_NTOA(ipaddr) AS ip, ipaddr, mac, name FROM vnodes WHERE ownerid = ? AND access = 1 ORDER BY ipaddr", g->db_get_data(ures,i,"id")); 
					
					for(j=0; j<g->db_nrows(nres); j++) 
					{	
						char *ipaddr = g->db_get_data(nres,j,"ip");
						char *mac = g->db_get_data(nres,j,"mac");
						char *name = g->db_get_data(nres,j,"name");
						char *mark_rule = strdup(cbq->mark_rule);
						char *cbq_down = strdup(cbq->cbq_down);
						char *cbq_up = strdup(cbq->cbq_up);
						int h_uprate = (int) n_uprate/nres->nrows;
						int h_upceil = (int) n_upceil/nres->nrows;
						int h_downrate = (int) n_downrate/nres->nrows;
						int h_downceil = (int) n_downceil/nres->nrows;
						int h_plimit = (int) n_plimit/nres->nrows;
						int h_climit = (int) n_climit/nres->nrows;
						
						// test node's membership in networks
						// to get valid network's interface name
						for(v=0; v<nc; v++)
							if(nets[v].address == (inet_addr(ipaddr) & nets[v].mask)) 
								break;
						if(v!=nc)
						{
							g->str_replace(&mark_rule, "%n", name);
							g->str_replace(&mark_rule, "%if", nets[v].interface);
							g->str_replace(&mark_rule, "%i", ipaddr);
							g->str_replace(&mark_rule, "%m", mac);
							g->str_replace(&mark_rule, "%x", itoa(x));
										
							fprintf(fh, "%s", mark_rule);
							
							//create file 1
							file = strdup("%d/cbq-%x.%n");
							g->str_replace(&file, "%d", cbq->path);
							g->str_replace(&file, "%n", name);
							g->str_replace(&file, "%x", itoa(x));
							fh1 = fopen(file, "w");

							if(fh1)
							{
								g->str_replace(&cbq_down, "%n", name);
								g->str_replace(&cbq_down, "%if", nets[v].interface);
								g->str_replace(&cbq_down, "%i", ipaddr);
								g->str_replace(&cbq_down, "%m", mac);
								g->str_replace(&cbq_down, "%x", itoa(x));
								g->str_replace(&cbq_down, "%downrate", itoa(h_downrate));
								g->str_replace(&cbq_down, "%rw", itoa((int)(h_downrate/10)));
								g->str_replace(&cbq_down, "%downceil", itoa(h_downceil));
								g->str_replace(&cbq_down, "%cw", itoa((int)(h_downceil/10)));
								g->str_replace(&cbq_down, "%climit", itoa(h_climit));
								g->str_replace(&cbq_down, "%plimit", itoa(h_plimit));
								
								fprintf(fh1, "%s", cbq_down);
								fclose(fh1);
							}
							else if(strlen(cbq->cbq_down))
							{
								syslog(LOG_ERR, "[%s/cbq-init] Unable to write file %s", cbq->base.instance, file);
							}
							free(file);
							
							//create file 2
							file = strdup("%d/cbq-%x.%n");
							g->str_replace(&file, "%d", cbq->path);
							g->str_replace(&file, "%n", name);
							g->str_replace(&file, "%x", itoa(x+1000));
							fh2 = fopen(file, "w");
								
							if(fh2)
							{
								g->str_replace(&cbq_up, "%n", name);
								g->str_replace(&cbq_up, "%if", nets[v].interface);
								g->str_replace(&cbq_up, "%i", ipaddr);
								g->str_replace(&cbq_up, "%m", mac);
								g->str_replace(&cbq_up, "%x", itoa(x));
								g->str_replace(&cbq_up, "%uprate", itoa(h_uprate));
								g->str_replace(&cbq_up, "%rw", itoa((int)(h_uprate/10)));
								g->str_replace(&cbq_up, "%upceil", itoa(h_upceil));
								g->str_replace(&cbq_up, "%cw", itoa((int)(h_upceil/10)));
								g->str_replace(&cbq_up, "%climit", itoa(h_climit));
								g->str_replace(&cbq_up, "%plimit", itoa(h_plimit));
																	
								fprintf(fh2, "%s", cbq_up);
								fclose(fh2);
							} 
							else if(strlen(cbq->cbq_up))
							{
								syslog(LOG_ERR, "[%s/cbq-init] Unable to write file %s", cbq->base.instance, file);
							}
							free(file);
						}
						x++;
						free(cbq_down); 
						free(cbq_up); 
						free(mark_rule);	
					}
					g->db_free(&nres);
				}
			}
			g->db_free(&ures);
		}
		else
			syslog(LOG_ERR, "[%s/cbq-init] Unable to read database", cbq->base.instance);

		fprintf(fh, "%s", cbq->mark_file_end);
		fclose(fh);			
		system(cbq->command);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/cbq-init] reloaded", cbq->base.instance);	
#endif
	}
	else
		syslog(LOG_ERR, "[%s/cbq-init] Unable to write file '%s'", cbq->base.instance, cbq->mark_file);

	for(i=0;i<nc;i++)
	{
		free(nets[i].name);
		free(nets[i].interface);
	}
	free(nets);
	
	for(i=0;i<gc;i++)
		free(ugps[i].name);
	free(ugps);
	
	free(cbq->path);
	free(cbq->command);
	free(cbq->cbq_down);	
	free(cbq->cbq_up);	
	free(cbq->mark_rule);
	free(cbq->mark_file);
	free(cbq->mark_file_begin);
	free(cbq->mark_file_end);
	free(cbq->networks);
	free(cbq->customergroups);
}

struct cbq_module * init(GLOBAL *g, MODULE *m)
{
	struct cbq_module *cbq;
	
	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	cbq = (struct cbq_module*) realloc(m, sizeof(struct cbq_module));
	
	cbq->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	
	cbq->path = strdup(g->config_getstring(cbq->base.ini, cbq->base.instance, "path", "/etc/sysconfig/cbq"));
	cbq->command = strdup(g->config_getstring(cbq->base.ini, cbq->base.instance, "command", "/etc/rc.d/cbq.init restart nocache"));
	cbq->cbq_down = strdup(g->config_getstring(cbq->base.ini, cbq->base.instance, "cbq_down", "\
DEVICE=%if,100Mbit,10Mbit\n\
RATE=%downrateKbit\n\
WEIGHT=%rwKbit\n\
PRIO=5\n\
RULE=%i"));
	cbq->cbq_up = strdup(g->config_getstring(cbq->base.ini, cbq->base.instance, "cbq_up", "\
DEVICE=eth1,100Mbit,10Mbit\n\
RATE=%uprateKbit\n\
WEIGHT=%rwKbit\n\
PRIO=5\n\
MARK=%x"));
	cbq->mark_rule = strdup(g->config_getstring(cbq->base.ini, cbq->base.instance, "mark_rule", "# %n\n\
$IPT -t mangle -A LIMITS -s %i -j MARK --set-mark %x\n\
$IPT -t mangle -A LIMITS -d %i -j MARK --set-mark %x\n"));
	cbq->mark_file = strdup(g->config_getstring(cbq->base.ini, cbq->base.instance, "mark_file", "/etc/rc.d/rc.marks"));
	cbq->mark_file_begin = strdup(g->config_getstring(cbq->base.ini, cbq->base.instance, "mark_file_begin", "\
#!/bin/sh\n\
IPT=/sbin/iptables\n\
WAN=eth1\n\n\
$IPT -t mangle -D FORWARD -i $WAN -j LIMITS >/dev/null 2>&1\n\
$IPT -t mangle -D FORWARD -o $WAN -j LIMITS >/dev/null 2>&1\n\
$IPT -t mangle -F LIMITS >/dev/null 2>&1\n\
$IPT -t mangle -X LIMITS >/dev/null 2>&1\n\
$IPT -t mangle -N LIMITS\n\
$IPT -t mangle -I FORWARD -i $WAN -j LIMITS\n\
$IPT -t mangle -I FORWARD -o $WAN -j LIMITS\n\n"));
	cbq->mark_file_end = strdup(g->config_getstring(cbq->base.ini, cbq->base.instance, "mark_file_end", ""));
	cbq->networks = strdup(g->config_getstring(cbq->base.ini, cbq->base.instance, "networks", ""));
	cbq->customergroups = strdup(g->config_getstring(cbq->base.ini, cbq->base.instance, "customergroups", ""));
	
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/cbq-init] initialized", cbq->base.instance);
#endif
	return (cbq);
}
