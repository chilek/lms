/*
 * LMS version 1.2-cvs
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
unsigned char *inet_ntoa(unsigned long);

void reload(GLOBAL *g, struct tc_module *tc)
{
	FILE *fh;
	QUERY_HANDLE *res;
	int i, id_next, id, id_last=0;
	
	fh = fopen(tc->file, "w");
	if(fh) {
		if( (res = g->db_query("SELECT userid, ipaddr, SUM(uprate) AS uprate, SUM(downrate) AS downrate FROM assignments, tariffs, nodes WHERE tariffs.id = tariffid AND userid = ownerid GROUP BY userid, ipaddr ORDER BY userid"))!=NULL ) {
		
			for(i=0; i<res->nrows; i++) {
				unsigned char *uprate;
				unsigned char *downrate;
				unsigned char *ipaddr;
				
				id = atoi(g->db_get_data(res,i,"userid"));
				if( i<res->nrows-1 )
					id_next = atoi(g->db_get_data(res,i+1,"userid"));
				
				uprate  = g->db_get_data(res,i,"uprate");
				downrate = g->db_get_data(res,i,"downrate");
				ipaddr = inet_ntoa(inet_addr(g->db_get_data(res,i,"ipaddr")));
			
				if( uprate && downrate && ipaddr ) {
					
					if( id != id_last )
						fprintf(fh, "%s %s %s ", downrate, uprate, ipaddr);
					else
						fprintf(fh, "%s ", ipaddr);
				}
				
				if( id != id_next ) 
					fprintf(fh, "\n");
				
				id_last = id;
			}
			g->db_free(res);
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

	free(tc->file);
	free(tc->command);	
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
	tc->file = strdup(g->iniparser_getstring(ini, s, "/tmp/tc.dat"));
	free(s); s = g->str_concat(instance, ":command");
	tc->command = strdup(g->iniparser_getstring(ini, s, "/bin/sh sample/htb.sh < /tmp/tc.dat"));
	
	g->iniparser_freedict(ini);
	free(instance);
	free(s);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/tc] initialized", tc->base.instance);
#endif
	return (tc);
}

