/*
 * LMS version 1.7-cvs
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
#include <stdlib.h>
#include <syslog.h>
#include <string.h>
#include <time.h>

#include "lmsd.h"
#include "cutoff.h"

void reload(GLOBAL *g, struct cutoff_module *c)
{
	QueryHandle *res;
	int i, execu=0, execn=0, u=0, n=0;
	char time_fmt[11];
	size_t tmax=11;
	char fmt[]="%Y/%m/%d";
	struct tm *wsk;
	time_t t;
	
	t=time(&t);
	wsk=localtime(&t);
	
	strftime(time_fmt,tmax,fmt,wsk);
	if(*c->warning)
		g->str_replace(&c->warning, "%time", time_fmt);

	if( (res = g->db_pquery(g->conn, "SELECT customers.id AS id FROM customers LEFT JOIN cash ON customers.id = cash.customerid AND (cash.type = 3 OR cash.type = 4) WHERE deleted = 0 GROUP BY customers.id HAVING SUM((type * -2 + 7) * cash.value) < ?", c->limit))!=NULL )
	{
		for(i=0; i<g->db_nrows(res); i++) 
		{
			char *customerid = g->db_get_data(res,i,"id");
			
    			if(!c->warn_only)
				n = g->db_pexec(g->conn, "UPDATE nodes SET access = 0 ? WHERE ownerid = ? AND access = 1", (*c->warning ? ", warning = 1" : ""), customerid);
			else 
				n = g->db_pexec(g->conn, "UPDATE nodes SET warning = 1 WHERE ownerid = ? AND warning = 0", customerid);

			execn = n ? 1 : execn;
			
			if(*c->warning && n)
			{
				u = g->db_pexec(g->conn, "UPDATE customers SET message = '?' WHERE id = ?", c->warning, customerid);
				execu = u ? 1 : execu;
			}
		}	
		g->db_free(&res);

		// set timestamps
		if(execu)
		{
			g->db_exec(g->conn, "DELETE FROM timestamps WHERE tablename = 'customers'");
			g->db_exec(g->conn, "INSERT INTO timestamps (tablename,time) VALUES ('customers',%NOW%)");
		}
		if(execn)
		{
			g->db_exec(g->conn, "DELETE FROM timestamps WHERE tablename = 'nodes'");
			g->db_exec(g->conn, "INSERT INTO timestamps (tablename,time) VALUES ('nodes',%NOW%)");
		}	
		if(execn || execu)
		{
			g->db_exec(g->conn, "DELETE FROM timestamps WHERE tablename = '_global'");
			g->db_exec(g->conn, "INSERT INTO timestamps (tablename,time) VALUES ('_global',%NOW%)");
			system(c->command);
		}
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/cutoff] reloaded", c->base.instance);
#endif
	} 
	else 
		syslog(LOG_ERR, "[%s/cutoff] Unable to read 'customers' table", c->base.instance);

	free(c->warning);
	free(c->command);
	free(c->limit);
}

struct cutoff_module * init(GLOBAL *g, MODULE *m)
{
	struct cutoff_module *c;
	
	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	c = (struct cutoff_module *) realloc(m, sizeof(struct cutoff_module));
	
	c->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	c->limit = strdup(g->config_getstring(c->base.ini, c->base.instance, "limit", "0"));
	c->warning = strdup(g->config_getstring(c->base.ini, c->base.instance, "warning", "Blocked automatically due to payment deadline override on %time"));
	c->command = strdup(g->config_getstring(c->base.ini, c->base.instance, "command", ""));
	c->warn_only = g->config_getbool(c->base.ini, c->base.instance, "warnings_only", 0);
	
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/cutoff] initialized", c->base.instance);
#endif	
	return(c);
}
