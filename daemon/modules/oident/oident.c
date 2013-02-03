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

#include "lmsd.h"
#include "oident.h"

unsigned long inet_addr(char *);
char * inet_ntoa(unsigned long);

void reload(GLOBAL *g, struct oident_module *o)
{
	FILE * fh;
	QueryHandle *res;
	int i, nc=0, n=2;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(o->networks);	
	char *netname = strdup(netnames);

	while( n>1 )
	{
		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )
		{
			res = g->db_pquery(g->conn, "SELECT name, domain, address, INET_ATON(mask) AS mask FROM networks WHERE UPPER(name)=UPPER('?')", netname);
			if( g->db_nrows(res) )
			{
		    		nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
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
		res = g->db_query(g->conn, "SELECT address, INET_ATON(mask) AS mask FROM networks");
		for(nc=0; nc<g->db_nrows(res); nc++)
		{
			nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
			nets[nc].address = inet_addr(g->db_get_data(res,nc,"address"));
			nets[nc].mask = inet_addr(g->db_get_data(res,nc,"mask"));
		}
		g->db_free(&res);
	}
		
	fh = fopen(o->file, "w");
	if(fh)
	{
		fprintf(fh, "%s\n", o->prefix);

		res = g->db_query(g->conn, "SELECT LOWER(name) AS name, mac, ipaddr FROM vmacs ORDER BY ipaddr");
		
		for(i=0; i<g->db_nrows(res); i++)
		{
			char *name, *mac, *ipaddr;
			
			name 	= g->db_get_data(res,i,"name");
			mac 	= g->db_get_data(res,i,"mac");
			ipaddr 	= g->db_get_data(res,i,"ipaddr");
				
			if( name && mac && ipaddr )
			{
				unsigned long inet = inet_addr(ipaddr);
				int j;
			
				for(j=0; j<nc; j++)
					if(nets[j].address == (inet & nets[j].mask)) 
						break;
				
				if( j != nc )
				{
					char my_mac[13], *s;
					
					if( strlen(mac) >= 17 )
						snprintf(my_mac, 13, "%c%c%c%c%c%c%c%c%c%c%c%c", mac[0], mac[1], mac[3], mac[4], mac[6], mac[7], mac[9], mac[10], mac[12], mac[13], mac[15], mac[16]);
					else
						snprintf(my_mac, 13, "unknownmac");
					
					s = strdup(o->host);
					g->str_replace(&s, "%n", name);
					g->str_replace(&s, "%m", my_mac);
					g->str_replace(&s, "%i", inet_ntoa(inet));
					fprintf(fh, "%s\n", s);
					free(s);
				}
			}
		}
		
		g->db_free(&res);
		fprintf(fh, "%s", o->append);
		fclose(fh);
		system(o->command);
#ifdef DEBUG1
		syslog(LOG_INFO,"DEBUG: [%s/oident] reloaded", o->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/oident] Unable to write a temporary file '%s'", o->base.instance, o->file);

	free(nets);
	
	free(o->file);
	free(o->command);
	free(o->prefix);
	free(o->append);
	free(o->host);
	free(o->networks);
}

struct oident_module * init(GLOBAL *g, MODULE *m)
{
	struct oident_module *o;
	
	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	o = (struct oident_module*) realloc(m, sizeof(struct oident_module));
	
	o->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	
	o->prefix = strdup(g->config_getstring(o->base.ini, o->base.instance, "begin", ""));
	o->append = strdup(g->config_getstring(o->base.ini, o->base.instance, "end", ""));
	o->host = strdup(g->config_getstring(o->base.ini, o->base.instance, "host", "%i\t%n\tUNIX"));
	o->file = strdup(g->config_getstring(o->base.ini, o->base.instance, "file", "/etc/oidentd.conf"));
	o->command = strdup(g->config_getstring(o->base.ini, o->base.instance, "command", ""));
	o->networks = strdup(g->config_getstring(o->base.ini, o->base.instance, "networks", ""));

#ifdef DEBUG1
	syslog(LOG_INFO, "[%s/oident] Initialized",o->base.instance);
#endif
	return (o);
}
