/*
 * LMS version 1.1-cvs
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
#include "hostfile.h"

unsigned long inet_addr(char *);
char * inet_ntoa(unsigned long);

void reload(GLOBAL *g, struct hostfile_module *hm)
{
	FILE *fh;
	QUERY_HANDLE *res;
	int i;
	
	fh = fopen(hm->file, "w");
	if(fh)
	{
		fprintf(fh, "%s", hm->prefix);
		
		if( (res = g->db_query("SELECT name, mac, ipaddr, access FROM nodes ORDER BY ipaddr"))!=NULL) {
		
			for(i=0; i<res->nrows; i++) {
				unsigned char *literal_mac, *literal_ip, *literal_access, *name;
			
				literal_mac = g->db_get_data(res,i,"mac");
				literal_ip  = inet_ntoa(inet_addr(g->db_get_data(res,i,"ipaddr")));
				literal_access = g->db_get_data(res,i,"access");
				name = (unsigned char *)g->str_lwc(g->db_get_data(res,i,"name"));

				if(literal_ip && literal_mac && literal_access) {
					
					unsigned char *pattern, *s;
				
					if(*literal_access == '1')
						pattern = hm->grant;
					else
						pattern = hm->deny;
				
					s = strdup(pattern);
					g->str_replace(&s, "%i", literal_ip);
					g->str_replace(&s, "%m", literal_mac);
					g->str_replace(&s, "%n", name);
				
					fprintf(fh, "%s", s);
					free(s);
				}
			}
		g->db_free(res);
		}		
		fprintf(fh, "%s", hm->append);
		fclose(fh);
		system(hm->command);
#ifdef DEBUG1
		syslog(LOG_INFO,"DEBUG: [%s/hostfile] reloaded",hm->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/hostfile] Unable to write a temporary file '%s'", hm->base.instance, hm->file);
	
	free(hm->prefix);
	free(hm->append);
	free(hm->grant);	
	free(hm->deny);
	free(hm->file);
	free(hm->command);
}

struct hostfile_module * init(GLOBAL *g, MODULE *m)
{
	struct hostfile_module *hm;
	unsigned char *instance, *s;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
		return(NULL);

	instance = m->instance;
	
	hm = (struct hostfile_module *) realloc(m, sizeof(struct hostfile_module));
	
	hm->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	hm->base.instance = strdup(instance);
	
	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance,":begin");
	hm->prefix = strdup(g->iniparser_getstring(ini, s, "/usr/sbin/iptables -F FORWARD\n"));
	free(s); s = g->str_concat(instance,":end");
	hm->append = strdup(g->iniparser_getstring(ini, s, "/usr/sbin/iptables -A FORWARD -j REJECT\n"));
	free(s); s = g->str_concat(instance,":grantedhost");	
	hm->grant = strdup(g->iniparser_getstring(ini, s, "/usr/sbin/iptables -A FORWARD -s %i -m mac --mac-source %m -j ACCEPT\n/usr/sbin/iptables -A FORWARD -d %i -j ACCEPT\n"));
	free(s); s = g->str_concat(instance,":deniedhost");
	hm->deny = strdup(g->iniparser_getstring(ini, s, "/usr/sbin/iptables -A FORWARD -s %i -m mac --mac-source %m -j REJECT\n"));
	free(s); s = g->str_concat(instance,":file");
	hm->file = strdup(g->iniparser_getstring(ini, s, "/tmp/hostfile"));
	free(s); s = g->str_concat(instance,":command");
	hm->command = strdup(g->iniparser_getstring(ini, s, ""));

	g->iniparser_freedict(ini);
	free(instance);
	free(s);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/hostfile] initialized", hm->base.instance);
#endif
	return(hm);
}

