/*
 * LMS version 1.5-cvs
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
#include <time.h>

#include "almsd.h"
#include "cutoff.h"

void reload(GLOBAL *g, struct cutoff_module *c)
{
	QUERY_HANDLE *res, *result;
	unsigned char *query, *update;
	int i, balance, value, exec = 0;
	char time_fmt[20];
	size_t tmax=20;
	char fmt[]="(%d.%m.%Y)";
	struct tm *tp, *wsk;
	time_t t;
	
	t=time(&t);
	wsk=localtime(&t);
	
	strftime(time_fmt,tmax,fmt,wsk);

	if( (res = g->db_query("SELECT users.id AS id, SUM((type * -2 +7) * cash.value) AS balance FROM users LEFT JOIN cash ON users.id = cash.userid AND (cash.type = 3 OR cash.type = 4) GROUP BY users.id"))!=NULL) { 

		for(i=0; i<res->nrows; i++) {
		
			balance = atoi(g->db_get_data(res,i,"balance"));
			
			if( balance < c->limit ) {
			
				update = strdup("UPDATE nodes SET access = 0, warning = 1 WHERE ownerid = %id");
				g->str_replace(&update, "%id", g->db_get_data(res,i,"id"));
				exec = g->db_exec(update);
				free(update);
				update = strdup("UPDATE users SET message = 'Automatyczna blokada spowodowana przekroczeniem terminu wp³aty %time' WHERE id = %id");
				g->str_replace(&update, "%id", g->db_get_data(res,i,"id"));
				g->str_replace(&update, "%time", time_fmt);
				exec = g->db_exec(update);
				free(update);
			}
		}	
		g->db_free(res);

		// set timestamps
		if( exec ) {
			g->db_exec("DELETE FROM timestamps WHERE tablename = 'nodes' OR tablename = '_global'");
			g->db_exec("INSERT INTO timestamps (tablename,time) VALUES ('nodes',%NOW%)");
			g->db_exec("INSERT INTO timestamps (tablename,time) VALUES ('_global',%NOW%)");
		}	
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/cutoff] reloaded", c->base.instance);
#endif
	} 
	else 
		syslog(LOG_ERR, "[%s/cutoff] Unable to read 'users' table", c->base.instance);

}

struct cutoff_module * init(GLOBAL *g, MODULE *m)
{
	struct cutoff_module *c;
	unsigned char *instance, *s;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
	    return (NULL);
	
	instance = m->instance;
	
	c = (struct cutoff_module *) realloc(m, sizeof(struct cutoff_module));
	
	c->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	c->base.instance = strdup(instance);

	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance, ":limit");
	c->limit = g->iniparser_getint(ini, s, 0);
	
	g->iniparser_freedict(ini);
	free(s);
	free(instance);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/cutoff] initialized", c->base.instance);
#endif	
	return(c);
}
