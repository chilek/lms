/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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
#include "payments.h"

char * itoa(int i)
{
	static char string[12];
	sprintf(string, "%d", i);
	return string;
}

unsigned char * get_period(struct tm *today, int period, int up_payments)
{
	struct tm *t;
	static time_t new_time, old_time;
	static char from[11], to[11];
	unsigned char *result;
	
	new_time = time(NULL);
	t = localtime(&new_time);
	
	t->tm_mday = today->tm_mday;
	t->tm_year = today->tm_year;
	t->tm_mon  = today->tm_mon;
	
	old_time = mktime(today);
	
	if( up_payments )
		switch(period) {
			case 1:	//year
				t->tm_mday += 365;
				break;
			case 2:	//month
				t->tm_mon += 1;
				break;
			case 3:	//week
				t->tm_mday += 7;
				break;
		}
	else
		switch(period) {
			case 1:	//year
				t->tm_mday -= 365;
				break;
			case 2:	//month
				t->tm_mon -= 1;
				break;
			case 3:	//week
				t->tm_mday -= 7;
				break;
		}
		
	new_time = mktime(t);

	strftime(to, 11, "%d.%m.%Y", localtime(&old_time)); 	
	strftime(from, 11, "%d.%m.%Y", localtime(&new_time)); 
	
	result = (unsigned char *) malloc(strlen(from)+strlen(to)+3);
	
	if( up_payments )
		sprintf(result, "%s-%s", to, from);
	else
		sprintf(result, "%s-%s", from, to);
		
	today = localtime(&old_time);
	
	return result;
}

void reload(GLOBAL *g, struct payments_module *p)
{
	QUERY_HANDLE *res;
	unsigned char *query, *insert, *w_period, *m_period, *y_period, *value;
	int i, invoiceid, exec=0;

	time_t t;
	struct tm *tt;
	unsigned char monthday[3], month[3], year[5], weekday[2], yearday[4];  //odjac jeden?
	unsigned char yearstart[12], yearend[12];
	
	t = time(NULL);
	tt = localtime(&t);
	strftime(monthday, 	sizeof(monthday), 	"%d", tt);
	strftime(weekday, 	sizeof(weekday), 	"%u", tt);	
	strftime(yearday, 	sizeof(yearday), 	"%j", tt);
	strftime(month, 	sizeof(month), 		"%m", tt);	
	strftime(year, 		sizeof(year), 		"%Y", tt);

	y_period = get_period(tt, 1, p->up_payments);
	m_period = get_period(tt, 2, p->up_payments);
	w_period = get_period(tt, 3, p->up_payments);

	// set begin and end date for present year 
	tt->tm_sec = 0; tt->tm_min = 0; tt->tm_hour = 0; tt->tm_mday = 1; tt->tm_mon = 1;
	tt->tm_year = atoi(year)-1900;
	strftime(yearstart,	sizeof(yearstart),	"%s", tt);
	tt->tm_sec = 59; tt->tm_min = 59; tt->tm_hour = 23; tt->tm_mon = 11; tt->tm_mday = 31;
	tt->tm_year = atoi(year)-1900;
	strftime(yearend,	sizeof(yearend),	"%s",tt);

	// first get max invoiceid for present year
	query = strdup("SELECT MAX(invoiceid) AS invoiceid FROM cash WHERE time >= %yearstart AND time <= %yearend");
	g->str_replace(&query, "%yearstart", yearstart);
	g->str_replace(&query, "%yearend", yearend);

	if( (res = g->db_query(query))!= NULL ) {
 
 		if( res->nrows )
			invoiceid = atoi(g->db_get_data(res,0,"invoiceid"));
		else
			invoiceid = 0;
		g->db_free(res);
		free(query);
	
		// monthly payments
		query = strdup("SELECT assignments.id AS id, tariffid, userid, period, at, value, uprate, downrate, tariffs.name AS name, invoice FROM assignments, tariffs, users WHERE tariffs.id = tariffid AND userid = users.id AND status = 3 AND deleted = 0 AND period = 0 AND at=%day");
		g->str_replace(&query, "%day", monthday);

		if( (res = g->db_query(query))!= NULL ) {
	
			for(i=0; i<res->nrows; i++) {
			
				if( atoi(value = g->db_get_data(res,i,"value")) ) {
			
    					insert = strdup("INSERT INTO cash (time, type, value, userid, comment, invoiceid) VALUES (?NOW?, 4, %value, %userid, '%comment', %invoiceid)");
				
					if( atoi(g->db_get_data(res,i,"invoice")) )
						g->str_replace(&insert, "%invoiceid", itoa(++invoiceid));
					else
						g->str_replace(&insert, "%invoiceid", "0");
					
					g->str_replace(&insert, "%userid", g->db_get_data(res,i,"userid"));
					g->str_replace(&insert, "%value", value);
					g->str_replace(&insert, "%comment", p->comment);
					g->str_replace(&insert, "%tariff", g->db_get_data(res,i,"name"));
					g->str_replace(&insert, "%period", m_period);	
					exec = g->db_exec(insert);
					free(insert);
				}
			}
    			g->db_free(res);
		}	
		free(query);

		// weekly payments
		query = strdup("SELECT assignments.id AS id, tariffid, userid, period, at, value, uprate, downrate, tariffs.name AS name, invoice FROM assignments, tariffs, users WHERE tariffs.id = tariffid AND userid = users.id AND status = 3 AND deleted = 0 AND period = 1 AND at = %weekday");
		g->str_replace(&query, "%weekday", weekday);
	
		if( (res = g->db_query(query))!= NULL ) {
	
			for(i=0; i<res->nrows; i++) {
			
				if( atoi(value = g->db_get_data(res,i,"value")) ) {
			
    					insert = strdup("INSERT INTO cash (time, type, value, userid, comment, invoiceid) VALUES (?NOW?, 4, %value, %userid, '%comment', %invoiceid)");
			
					if( atoi(g->db_get_data(res,i,"invoice")) )
						g->str_replace(&insert, "%invoiceid", itoa(++invoiceid));
					else
						g->str_replace(&insert, "%invoiceid", "0");
					
					g->str_replace(&insert, "%userid", g->db_get_data(res,i,"userid"));
					g->str_replace(&insert, "%value", value);
					g->str_replace(&insert, "%comment", p->comment);
					g->str_replace(&insert, "%tariff", g->db_get_data(res,i,"name"));
					g->str_replace(&insert, "%period", w_period);	
					exec = g->db_exec(insert);
					free(insert);
				}
			}
    			g->db_free(res);
		}	
		free(query);	
	
		// yearly payments
		query = strdup("SELECT assignments.id AS id, tariffid, userid, period, at, value, uprate, downrate, tariffs.name AS name, invoice FROM assignments, tariffs, users WHERE tariffs.id = tariffid AND userid = users.id AND status = 3 AND deleted = 0 AND period = 2 AND at = %yearday");
		g->str_replace(&query, "%yearday", yearday);
	
		if( (res = g->db_query(query))!= NULL ) {
	
			for(i=0; i<res->nrows; i++) {
			
				if( atoi(value = g->db_get_data(res,i,"value")) ) {
			
    					insert = strdup("INSERT INTO cash (time, type, value, userid, comment, invoiceid) VALUES (?NOW?, 4, %value, %userid, '%comment', %invoiceid)");
			
					if( atoi(g->db_get_data(res,i,"invoice")) )
						g->str_replace(&insert, "%invoiceid", itoa(++invoiceid));
					else
						g->str_replace(&insert, "%invoiceid", "0");
					
					g->str_replace(&insert, "%userid", g->db_get_data(res,i,"userid"));
					g->str_replace(&insert, "%value", value);
					g->str_replace(&insert, "%comment", p->comment);
					g->str_replace(&insert, "%tariff", g->db_get_data(res,i,"name"));
					g->str_replace(&insert, "%period", y_period);	
					exec = g->db_exec(insert);
					free(insert);
				}
			}
    			g->db_free(res);
		}	
		free(query);			
			
		// set timestamps
		if( exec) {
			g->db_exec("DELETE FROM timestamps WHERE tablename = 'cash' OR tablename = '_global'");
			g->db_exec("INSERT INTO timestamps (tablename, time) VALUES ('cash', ?NOW?)");
			g->db_exec("INSERT INTO timestamps (tablename, time) VALUES ('_global', ?NOW?)");
		}
		free(y_period);
		free(m_period);
		free(w_period);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/payments] reloaded", p->base.instance);
#endif
	} 
	else {
		free(query);
		syslog(LOG_ERR, "[%s/payments] Unable to read 'cash' table for invoice max id",p->base.instance);
	}

	// clean up
	free(p->comment);
}

struct payments_module * init(GLOBAL *g, MODULE *m)
{
	struct payments_module *p;
	unsigned char *instance, *s;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
	    return (NULL);
	
	instance = m->instance;
	
	p = (struct payments_module *) realloc(m, sizeof(struct payments_module));
	
	p->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	p->base.instance = strdup(instance);

	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance, ":comment");
	p->comment = strdup(g->iniparser_getstring(ini, s, "Abonament wg taryfy: %tariff za okres: %period"));
	free(s); s = g->str_concat(instance, ":up_payments");
	p->up_payments = g->iniparser_getboolean(ini, s, 0);
	
	g->iniparser_freedict(ini);
	free(s);
	free(instance);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/payments] initialized", p->base.instance);
#endif	
	return(p);
}
