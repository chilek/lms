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
#include <arpa/inet.h>

#include "lmsd.h"
#include "ethers.h"

void reload(GLOBAL *g, struct ethers_module *fm)
{
	FILE *fh;
	QueryHandle *res, *res1;
	int i, j, m, k=2, gc=0, nc=0, n=2;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(fm->networks);	
	char *netname = strdup(netnames);
    
	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(fm->customergroups);	
	char *groupname = strdup(groupnames);

	while( n>1 )
	{
    		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )
		{
		        res = g->db->pquery(g->db->conn, "SELECT name, address, INET_ATON(mask) AS mask  FROM networks WHERE UPPER(name)=UPPER('?')", netname);

			if( g->db->nrows(res) ) 
			{
				nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].name = strdup(g->db->get_data(res,0,"name"));
				nets[nc].address = inet_addr(g->db->get_data(res,0,"address"));
				nets[nc].mask = inet_addr(g->db->get_data(res,0,"mask"));
				nc++;
			}
	    		g->db->free(&res);
		}				
	}
	free(netname); free(netnames);

	if( !nc )
	{
		res = g->db->query(g->db->conn, "SELECT name, address, INET_ATON(mask) AS mask FROM networks");
		for(nc=0; nc<g->db->nrows(res); nc++)
		{
			nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
			nets[nc].name = strdup(g->db->get_data(res,nc,"name"));
			nets[nc].address = inet_addr(g->db->get_data(res,nc,"address"));
			nets[nc].mask = inet_addr(g->db->get_data(res,nc,"mask"));
		}
		g->db->free(&res);
	}

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

	fh = fopen(fm->file, "w");
	if(fh)
	{
		res = g->db->query(g->db->conn, "SELECT mac, ipaddr, access, ownerid FROM vmacs ORDER BY ipaddr");
	
		for(i=0; i<g->db->nrows(res); i++)
		{
			unsigned long inet = inet_addr(g->db->get_data(res,i,"ipaddr"));
			int ownerid = atoi(g->db->get_data(res,i,"ownerid"));
				
			// networks test
			for(j=0; j<nc; j++)
				if(nets[j].address == (inet & nets[j].mask)) 
					break;
			
			// groups test
			m = gc;
			if(gc && ownerid)
			{
				res1 = g->db->pquery(g->db->conn, "SELECT customergroupid FROM customerassignments WHERE customerid=?", g->db->get_data(res,i,"ownerid"));
				for(k=0; k<g->db->nrows(res1); k++)
				{
					int groupid = atoi(g->db->get_data(res1, k, "customergroupid"));
					for(m=0; m<gc; m++) 
						if(ugps[m].id==groupid) 
							break;
					if( m!=gc ) break;
				}
				g->db->free(&res1);
			}
				
			if( j!=nc && (gc==0 || m!=gc) )
			{
				if( atoi(g->db->get_data(res,i,"access")) )
					fprintf(fh, "%s\t%s\n", g->db->get_data(res,i,"mac"), inet_ntoa(inet_makeaddr(htonl(inet), 0)));
				else
					if( fm->dummy_macs )
						fprintf(fh, "00:00:00:00:00:00\t%s\n", inet_ntoa(inet_makeaddr(htonl(inet), 0)));	
			}
		}

    		g->db->free(&res);
		fclose(fh);
		system(fm->command);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ethers] reloaded", fm->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/ethers] Unable to write a temporary file '%s'", fm->base.instance, fm->file);

	for(i=0;i<nc;i++)
		free(nets[i].name);
	free(nets);
	
	for(i=0;i<gc;i++)
		free(ugps[i].name);
	free(ugps);
	
	free(fm->networks);
	free(fm->customergroups);
	free(fm->file);
	free(fm->command);
}

struct ethers_module * init(GLOBAL *g, MODULE *m)
{
	struct ethers_module *fm;
	
	if(g->api_version != APIVERSION)
	{
		return(NULL);
	}
	
	fm = (struct ethers_module *) realloc(m, sizeof(struct ethers_module));
	
	fm->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	fm->file = strdup(g->config_getstring(fm->base.ini, fm->base.instance, "file", "/etc/ethers"));
	fm->command = strdup(g->config_getstring(fm->base.ini, fm->base.instance, "command", "arp -f /etc/ethers"));
	fm->dummy_macs = g->config_getbool(fm->base.ini, fm->base.instance, "dummy_macs", 0);
	fm->networks = strdup(g->config_getstring(fm->base.ini, fm->base.instance, "networks", ""));
	fm->customergroups = strdup(g->config_getstring(fm->base.ini, fm->base.instance, "customergroups", ""));

#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/ethers] initialized", fm->base.instance);
#endif	
	return(fm);
}
