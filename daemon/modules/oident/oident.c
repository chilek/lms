/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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
#include "oident.h"

unsigned long inet_addr(char *);
char * inet_ntoa(unsigned long);

void reload(GLOBAL *g, struct oident_module *o)
{
	FILE * fh;
	QUERY_HANDLE *res;
	int i, nc=0, n=2;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(o->networks);	
	char *netname = strdup(netnames);

	while( n>1 ) {
		
		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) ) {

			if( (res = g->db_pquery("SELECT name, domain, address, INET_ATON(mask) AS mask FROM networks WHERE UPPER(name)=UPPER('?')",netname)) ) {

				if(res->nrows) {

			    		nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
					nets[nc].address = inet_addr(g->db_get_data(res,0,"address"));
					nets[nc].mask = inet_addr(g->db_get_data(res,0,"mask"));
					nc++;
				}
	    			g->db_free(res);
			}				
		}
	}
	free(netname); free(netnames);

	if(!nc)
		if( (res = g->db_query("SELECT address, INET_ATON(mask) AS mask FROM networks"))!=NULL ) {

			for(nc=0; nc<res->nrows; nc++) {
				
				nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].address = inet_addr(g->db_get_data(res,nc,"address"));
				nets[nc].mask = inet_addr(g->db_get_data(res,nc,"mask"));
			}
			g->db_free(res);
		}
		
	fh = fopen(o->file, "w");
	if(fh) {
		fprintf(fh, "%s\n", o->prefix);

		if( (res = g->db_query("SELECT name, mac, ipaddr FROM nodes ORDER BY ipaddr"))!=NULL ) {
		
			for(i=0; i<res->nrows; i++) {
				unsigned char *name, *mac, *ipaddr;
				unsigned char *s;
			
				name 	= g->db_get_data(res,i,"name");
				mac 	= g->db_get_data(res,i,"mac");
				ipaddr 	= g->db_get_data(res,i,"ipaddr");
				
				if( name && mac && ipaddr ) {
					unsigned long inet = inet_addr(ipaddr);
					int j;

			    		for(j=0; j<nc; j++)
						if(nets[j].address == (inet & nets[j].mask)) 
							break;
					
					if( j != nc ) {

						unsigned char my_mac[13];
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
			g->db_free(res);
		}
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
	unsigned char *instance, *s;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
		return (NULL);
	
	instance = m->instance;
	
	o = (struct oident_module*) realloc(m, sizeof(struct oident_module));
	
	o->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	o->base.instance = strdup(instance);
	
	ini = g->iniparser_load(g->inifile);
	
	s = g->str_concat(instance, ":begin");
	o->prefix = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":end");
	o->append = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":host");
	o->host = strdup(g->iniparser_getstring(ini, s, "%i\t%n\tUNIX"));
	free(s); s = g->str_concat(instance, ":file");
	o->file = strdup(g->iniparser_getstring(ini, s, "/etc/oidentd.conf"));
	free(s); s = g->str_concat(instance, ":command");
	o->command = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":networks");
	o->networks = strdup(g->iniparser_getstring(ini, s, ""));

	g->iniparser_freedict(ini);
	free(instance);
	free(s);
	
#ifdef DEBUG1
	syslog(LOG_INFO, "[%s/oident] Initialized",o->base.instance);
#endif
	return (o);
}
