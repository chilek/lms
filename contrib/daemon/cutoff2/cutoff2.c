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
#include <time.h>
#include <stdlib.h>

#include "lmsd.h"
#include "cutoff2.h"

int is_deadline(int at, int limit, time_t t)
{
	struct tm *tt;

	// go back to payment day
	t -= (limit*60*60*24);
	tt = localtime(&t);

	if(tt->tm_mday==at) return 1;

	return 0;
}

void reload(GLOBAL *g, struct cutoff_module *c)
{
	QueryHandle *res, *result;
	int i, plimit=0, limit=0, execu=0, execn=0, u=0, n=0;
	char time_fmt[DATE_FORMAT_LEN], fmt[]="(%d.%m.%Y)";
	struct tm *wsk;
	time_t t;

	t = time(&t);
	wsk = localtime(&t);
	
	strftime(time_fmt, DATE_FORMAT_LEN, fmt, wsk);
	if(*c->warning)
		g->str_replace(&c->warning, "%time", time_fmt);
		
	// is limit option a percentage value
	if(g->str_replace(&c->limit, "%", ""))
	{
		plimit = atoi(c->limit);
		plimit = (plimit < 0 ? plimit*-1 : plimit);
	}
	else
	{
		limit = atoi(c->limit);
		limit = (limit < 0 ? limit*-1 : limit);
	}
	
	if(c->deadline > 28) c->deadline = 28;

	res = g->db_query(g->conn, "SELECT customers.id AS id, SUM(cash.value)*-1 AS balance FROM customers LEFT JOIN cash ON customers.id = cash.customerid WHERE deleted = 0 GROUP BY customers.id HAVING SUM(cash.value) < 0");
	if( g->db_nrows(res) )
	{
		for(i=0; i<g->db_nrows(res); i++) 
		{
			char *customerid = g->db_get_data(res,i,"id");
			float balance = atof(g->db_get_data(res,i,"balance"));
			int at = 0;
			float value = 0;
			
			if( (result = g->db_pquery(g->conn,
			    "SELECT MAX(a.at) AS at, SUM(t.value) AS value "
			    "FROM assignments a, tariffs t "
			    "WHERE a.tariffid = t.id "
			        "AND a.customerid = ? AND a.period = 3 AND a.suspended = 0 "
			        "AND (a.datefrom <= %NOW% OR a.datefrom = 0) "
			        "AND (a.dateto >= %NOW% OR a.dateto = 0) "
			    "GROUP BY a.customerid HAVING SUM(t.value) > 0",
			    customerid)) != NULL
			) {
				if( g->db_nrows(result) )
				{
					at = atoi(g->db_get_data(result,0,"at"));
					value = atof(g->db_get_data(result,0,"value"));
				}
				g->db_free(&result);
			}
			else continue;
			
			// balance limit exceeded?
			if(plimit)
				if((plimit*value)/100.00 > balance)
					continue; 
			if(limit || (!limit && !plimit))
				if(limit > balance)
					continue; 
			
			// current day is deadline?
			if(c->deadline)
				if(!is_deadline(at, c->deadline, t))
					continue;
			
			//printf("UserID: %s\tPayDay: %d\tValue: %.2f\tBalance: %.2f\n",customerid, at, value, balance);
			
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

		if(execn || execu)
		{
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
	c->deadline = g->config_getint(c->base.ini, c->base.instance, "deadline", 0);
	c->warn_only = g->config_getbool(c->base.ini, c->base.instance, "warnings_only", 0);
	c->warning = strdup(g->config_getstring(c->base.ini, c->base.instance, "warning", "Automatyczna blokada spowodowana przekroczeniem terminu wp³aty %time"));
	c->command = strdup(g->config_getstring(c->base.ini, c->base.instance, "command", ""));
	
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/cutoff] initialized", c->base.instance);
#endif	
	return(c);
}
