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
#include "shaper.h"

unsigned long inet_addr(unsigned char *);

char * itoa(int i)
{
	static char string[12];
	sprintf(string, "%d", i);
	return string;
}

void reload(GLOBAL *g, struct shaper_module *shaper)
{
	FILE *fh;
	QueryHandle *res, *ures, *nres;
	int x=100, i, j, m, v, k=2, n=2, nc=0, gc=0;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(shaper->networks);	
	char *netname = strdup(netnames);

	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(shaper->customergroups);	
	char *groupname = strdup(groupnames);

	// get table of networks
	while( n>1 ) 
	{
		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )
		{
			res = g->db_pquery(g->conn, "SELECT name, domain, address, INET_ATON(mask) AS mask, interface FROM networks WHERE UPPER(name)=UPPER('?')", netname);
			if( g->db_nrows(res) ) 
			{
		    		nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].name = strdup(g->db_get_data(res,0,"name"));
				nets[nc].domain = strdup(g->db_get_data(res,0,"domain"));
				nets[nc].interface = strdup(g->db_get_data(res,0,"interface"));
				nets[nc].address = inet_addr(g->db_get_data(res,0,"address"));
				nets[nc].mask = inet_addr(g->db_get_data(res,0,"mask"));
				nc++;
			}
    			g->db_free(&res);
		}				
	}
	free(netname); free(netnames);

	// get table of customergroups
	while( k>1 ) 
	{
		k = sscanf(groupnames, "%s %[._a-zA-Z0-9- ]", groupname, groupnames);

		if( strlen(groupname) )
		{
			res = g->db_pquery(g->conn, "SELECT name, id FROM customergroups WHERE UPPER(name)=UPPER('?')", groupname);
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

	// open temporary file	
	fh = fopen(shaper->file, "w");
	if(fh) 
	{
		// get (htb) data for any customer with connected nodes and active assignments
		// we need customer ID and average data values for nodes
		if( (ures = g->db_query(g->conn, "\
			SELECT customerid AS id, \
				SUM(uprate)/COUNT(DISTINCT nodes.id) AS uprate, \
				SUM(downrate)/COUNT(DISTINCT nodes.id) AS downrate, \
				SUM(upceil)/COUNT(DISTINCT nodes.id) AS upceil, \
				SUM(downceil)/COUNT(DISTINCT nodes.id) AS downceil \
			FROM assignments \
				LEFT JOIN tariffs ON (tariffid = tariffs.id) \
				LEFT JOIN nodes ON (customerid = ownerid) \
			WHERE access = 1 AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) \
			GROUP BY customerid \
			ORDER BY customerid"))!=NULL ) 
		{
		
		fprintf(fh, "%s", shaper->begin);
		
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
					int n_upceil = atoi(upceil);
					int n_downceil = atoi(downceil);
					int n_uprate = atoi(uprate);
					int n_downrate = atoi(downrate);
					
					int got_node = 0;

					nres = g->db_pquery(g->conn, "\
						SELECT INET_NTOA(ipaddr) AS ip, ipaddr, mac, name \
						FROM nodes \
						WHERE ownerid = ? AND access = 1 \
						ORDER BY ipaddr", g->db_get_data(ures,i,"id"));
					
					for(j=0; j<g->db_nrows(nres); j++) 
					{	
						char *ipaddr = g->db_get_data(nres,j,"ip");
						char *mac = g->db_get_data(nres,j,"mac");
						char *name = g->db_get_data(nres,j,"name");
						char *htb = strdup(shaper->host_htb);
						int h_uprate = (int) n_uprate/nres->nrows;
						int h_upceil = (int) n_upceil/nres->nrows;
						int h_downrate = (int) n_downrate/nres->nrows;
						int h_downceil = (int) n_downceil/nres->nrows;  
						
						// test node's membership in networks
						if(nc)
							for(v=0; v<nc; v++)
								if(nets[v].address == (inet_addr(ipaddr) & nets[v].mask)) 
									break;
																	
						if(!nc || v!=nc)
						{
							got_node = 1;
						
							if(h_uprate && h_downrate)
							{
								if(shaper->one_class_per_host)
								{
									g->str_replace(&htb, "%n", name);
									g->str_replace(&htb, "%i", ipaddr);
									g->str_replace(&htb, "%m", mac);
									g->str_replace(&htb, "%x", itoa(x));
									g->str_replace(&htb, "%uprate", itoa(h_uprate));
									if(!h_upceil)
										g->str_replace(&htb, "%upceil", itoa(h_uprate));
									else
										g->str_replace(&htb, "%upceil", itoa(h_upceil));
								
									g->str_replace(&htb, "%downrate", itoa(h_downrate));
									if(!h_downceil)
										g->str_replace(&htb, "%downceil", itoa(h_downrate));
									else						
										g->str_replace(&htb, "%downceil", itoa(h_downceil));
						
									// write to file
									fprintf(fh, "%s", htb);
								}
							}
								if(shaper->one_class_per_host) x++;
						}
						
						if(!shaper->one_class_per_host && j==nres->nrows-1 && got_node && n_downrate && n_uprate)
						{
							g->str_replace(&htb, "%n", name);
							g->str_replace(&htb, "%x", itoa(x));
							g->str_replace(&htb, "%i", ipaddr);
							g->str_replace(&htb, "%uprate", uprate);
							if(!n_upceil)
								g->str_replace(&htb, "%upceil", uprate);
							else
								g->str_replace(&htb, "%upceil", upceil);
							g->str_replace(&htb, "%downrate", downrate);
							if(!n_downceil)
								g->str_replace(&htb, "%downceil", downrate);
							else
								g->str_replace(&htb, "%downceil", downceil);
						
							// write to file
							fprintf(fh, "%s", htb);
							
							x++;
						}
						else
						{
							g->str_replace(&htb, "%n", name);
							g->str_replace(&htb, "%x", itoa(x));
							g->str_replace(&htb, "%i", ipaddr);
							g->str_replace(&htb, "%uprate", "");
							g->str_replace(&htb, "%upceil", "");
							g->str_replace(&htb, "%downrate", "");
							g->str_replace(&htb, "%downceil", "");
					
							// write to file
							fprintf(fh, "%s", htb);
							
							x++;
						}
						free(htb);
					}
					g->db_free(&nres);
				}
			}
			g->db_free(&ures);
			
			fprintf(fh, "%s", shaper->end);
		}
		else
			syslog(LOG_ERR, "[%s/shaper] Unable to read database", shaper->base.instance);
			
		fclose(fh);
		system(shaper->command);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/shaper] reloaded", shaper->base.instance);	
#endif
	}
	else
		syslog(LOG_ERR, "[%s/shaper] Unable to write a temporary file '%s'", shaper->base.instance, shaper->file);

	for(i=0;i<nc;i++)
	{
		free(nets[i].name);
		free(nets[i].domain);	
		free(nets[i].interface);
	}
	free(nets);
	
	for(i=0;i<gc;i++)
		free(ugps[i].name);
	free(ugps);
	
	free(shaper->file);
	free(shaper->command);	
	free(shaper->begin);
	free(shaper->end);	
	free(shaper->host_htb);
	free(shaper->networks);
	free(shaper->customergroups);
}

struct shaper_module * init(GLOBAL *g, MODULE *m)
{
	struct shaper_module *shaper;
	
	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	shaper = (struct shaper_module*) realloc(m, sizeof(struct shaper_module));
	
	shaper->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	
	shaper->file = strdup(g->config_getstring(shaper->base.ini, shaper->base.instance, "file", "/etc/shaper/iplist.0"));
	shaper->command = strdup(g->config_getstring(shaper->base.ini, shaper->base.instance, "command", "sh /etc/init.d/shaperd restart"));
	shaper->begin = strdup(g->config_getstring(shaper->base.ini, shaper->base.instance, "begin", "# Lista ip\n#1.1.1.1=eth0 eth0\n\n"));
	shaper->end = strdup(g->config_getstring(shaper->base.ini, shaper->base.instance, "end", "\n# koniec listy\n"));
	shaper->host_htb = strdup(g->config_getstring(shaper->base.ini, shaper->base.instance, "host_htb", "%i=eth1 eth0 %downrate %downceil %uprate %upceil \"\" \"\"\n"));
	shaper->networks = strdup(g->config_getstring(shaper->base.ini, shaper->base.instance, "networks", ""));
	shaper->customergroups = strdup(g->config_getstring(shaper->base.ini, shaper->base.instance, "customergroups", ""));
	shaper->one_class_per_host = g->config_getbool(shaper->base.ini, shaper->base.instance, "one_class_per_host", 0);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/shaper] initialized", shaper->base.instance);
#endif
	return (shaper);
}
