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
#include "ethers.h"

unsigned long inet_addr(unsigned char*);
char * inet_ntoa(unsigned long);

void reload(GLOBAL *g, struct ethers_module *fm)
{
    FILE *fh;
    QUERY_HANDLE *res;
    int i;
    
    fh = fopen(fm->file, "w");
    if(fh) {
	
	if( (res = g->db_query("SELECT mac, ipaddr, access FROM nodes ORDER BY ipaddr"))!= NULL) {
	
	    fprintf(fh, "# Wygenerowany automatycznie\n\n");

	    for(i=0; i<res->nrows; i++) 
		
		    if( atoi(g->db_get_data(res,i,"access")) )
			    fprintf(fh, "%s\t%s\n", inet_ntoa(inet_addr(g->db_get_data(res,i,"ipaddr"))), g->db_get_data(res,i,"mac"));
		    else
			    if( fm->dummy_macs )
				    fprintf(fh, "%s\t00:00:00:00:00:00\n", inet_ntoa(inet_addr(g->db_get_data(res,i,"ipaddr"))));	
		
    	    g->db_free(res);
	}	
	
	fclose(fh);
	system(fm->command);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/ethers] reloaded", fm->base.instance);
#endif
    }
    else
	syslog(LOG_ERR, "[%s/ethers] Unable to write a temporary file '%s'", fm->base.instance, fm->file);
    
    free(fm->file);
    free(fm->command);
}

struct ethers_module * init(GLOBAL *g, MODULE *m)
{
	struct ethers_module *fm;
	unsigned char *instance, *s;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
	    return(NULL);
	
	instance = m->instance;
	
	fm = (struct ethers_module *) realloc(m, sizeof(struct ethers_module));
	
	fm->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	fm->base.instance = strdup(instance);

	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance, ":file");
	fm->file = strdup(g->iniparser_getstring(ini, s, "/tmp/ethers"));
	free(s); s = g->str_concat(instance, ":command");
	fm->command = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":dummy_macs");	
	fm->dummy_macs = g->iniparser_getboolean(ini,s,0);
	
	g->iniparser_freedict(ini);
	free(instance);
	free(s);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/ethers] initialized", fm->base.instance);
#endif	
	return(fm);
}

