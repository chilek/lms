/*
 * LMS version 1.4-cvs
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
			case 0:	//week
				t->tm_mday += 7;
				break;
			case 1:	//month
				t->tm_mon += 1;
				break;
			case 2: //quarter
				t->tm_mon += 3;
				break;
			case 3:	//year
				t->tm_mon += 12;
				break;
		}
	else
		switch(period) {
			case 0:	//week
				t->tm_mday -= 7;
				break;		
			case 1:	//month
				t->tm_mon -= 1;
				break;
			case 2: //quarter
				t->tm_mon -= 3;
				break;
			case 3:	//year
				t->tm_mon -= 12;
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
	QUERY_HANDLE *res, *result, *sres;
	unsigned char *query, *insert, *insert_inv, *update;
	unsigned char *w_period, *m_period, *q_period, *y_period, *value, *taxvalue;
	unsigned char *description;
	int i, invoiceid, last_userid=0, number=0, exec=0, suspended=0;

	time_t t;
	struct tm *tt;
	unsigned char monthday[3], month[3], year[5], quarterday[3], weekday[2], yearday[4];  //odjac jeden?
	unsigned char yearstart[12], yearend[12];
	
	// get current date
	t = time(NULL);
	tt = localtime(&t);
	strftime(monthday, 	sizeof(monthday), 	"%d", tt);
	strftime(weekday, 	sizeof(weekday), 	"%u", tt);	
	strftime(yearday, 	sizeof(yearday), 	"%j", tt);
	strftime(month, 	sizeof(month), 		"%m", tt);	
	strftime(year, 		sizeof(year), 		"%Y", tt);

	switch(atoi(month)) {
		case 1:
		case 4:
		case 7:
		case 10:
			sprintf(quarterday, "%d", atoi(monthday));
			break;
		case 2:
		case 5:
		case 8:
		case 12:
			sprintf(quarterday, "%d", atoi(monthday)+100);
			break;
		default:
			sprintf(quarterday, "%d", atoi(monthday)+200);
			break;
	}

	y_period = get_period(tt, 3, p->up_payments);
	q_period = get_period(tt, 2, p->up_payments);
	m_period = get_period(tt, 1, p->up_payments);
 	w_period = get_period(tt, 0, p->up_payments);

	// set begin and end date for present year 
	tt->tm_sec = 0; tt->tm_min = 0; tt->tm_hour = 0; tt->tm_mday = 1; tt->tm_mon = 0;
	tt->tm_year = atoi(year)-1900;
	strftime(yearstart,	sizeof(yearstart),	"%s", tt);
	tt->tm_sec = 59; tt->tm_min = 59; tt->tm_hour = 23; tt->tm_mon = 11; tt->tm_mday = 31;
	tt->tm_year = atoi(year)-1900;
	strftime(yearend,	sizeof(yearend),	"%s",tt);

	/****** main payments *******/
	if( (res = g->db_pquery("SELECT * FROM payments WHERE value <> 0 AND ((period=0 AND at=?) OR (period=1 AND at=?) OR (period=2 AND at=?) OR (period=3 AND at=?))", weekday, monthday, quarterday, yearday))!= NULL ) {	

		for(i=0; i<res->nrows; i++) {
			
			exec = (g->db_pexec("INSERT INTO cash (time, type, value, userid, comment, invoiceid) VALUES (%NOW%, 2, ?, 0, '? / ?', 0)",
					g->db_get_data(res,i,"value"),
					g->db_get_data(res,i,"name"),
					g->db_get_data(res,i,"creditor")
				) ? 1 : exec);
		}
		g->db_free(res);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/payments] main payments reloaded", p->base.instance);
#endif
	} else 
		syslog(LOG_ERR, "[%s/payments] Unable to read 'payments' table",p->base.instance);
		
	/****** user payments *******/
	// first get max invoiceid for present year
	if( (res = g->db_pquery("SELECT MAX(number) AS number FROM invoices WHERE cdate >= ? AND cdate <= ?", yearstart, yearend))!= NULL ) {
 
 		if( res->nrows )
			number = atoi(g->db_get_data(res,0,"number"));
		g->db_free(res);

		// payments accounting and invoices writing
		if( (res = g->db_pquery("SELECT assignments.id AS id, tariffid, userid, period, at, value, taxvalue, pkwiu, uprate, downrate, tariffs.name AS tariff, invoice, UPPER(lastname) AS lastname, users.name AS name, address, zip, city, nip, pesel, phone1 AS phone FROM assignments, tariffs, users WHERE tariffs.id = tariffid AND userid = users.id AND status = 3 AND deleted = 0 AND suspended = 0 AND value <> 0 AND ((period = 0 AND at = ?) OR (period = 1 AND at = ?) OR (period = 2 AND at = ?) OR (period = 3 AND at = ?)) AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) ORDER BY userid, value DESC", weekday, monthday, quarterday, yearday))!= NULL ) {
	
			for(i=0; i<res->nrows; i++) {
			
				int uid = atoi(g->db_get_data(res,i,"userid"));
				
				// assignments suspending check
				if(suspended != uid)
				{
					if( (sres = g->db_pquery("SELECT 1 FROM assignments, users WHERE userid = users.id AND tariffid = 0 AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) AND userid = ?", g->db_get_data(res,i,"userid"))) != NULL ) {
				
						if(sres->nrows) {
							suspended = uid;
							continue;
						}
						g->db_free(sres);
					}
				} else
					continue;

    				value = g->db_get_data(res,i,"value");
				taxvalue = g->db_get_data(res,i,"taxvalue");
				// prepare insert to 'cash' table
				insert = strdup("INSERT INTO cash (time, type, value, taxvalue, userid, comment, invoiceid) VALUES (%NOW%, 4, %value, %taxvalue, %userid, '%comment', %invoiceid)");
				g->str_replace(&insert, "%userid", g->db_get_data(res,i,"userid"));
				g->str_replace(&insert, "%value", value);
				description = strdup(p->comment);
				switch( atoi(g->db_get_data(res,i,"period")) ) {
					case 0: g->str_replace(&description, "%period", w_period); break;
					case 1: g->str_replace(&description, "%period", m_period); break;
					case 2: g->str_replace(&description, "%period", q_period); break;
					case 3: g->str_replace(&description, "%period", y_period); break;
				}
				g->str_replace(&description, "%tariff", g->db_get_data(res,i,"tariff"));
				g->str_replace(&insert, "%comment", description);
				if( strlen(taxvalue) )
					g->str_replace(&insert, "%taxvalue", taxvalue);
				else
					g->str_replace(&insert, "%taxvalue", "NULL");
				
				if( atoi(g->db_get_data(res,i,"invoice")) ) {
				
					if( last_userid != uid ) {
						// prepare insert to 'invoices' table
						insert_inv = strdup("INSERT INTO invoices (number, customerid, name, address, zip, city, phone, nip, pesel, cdate, paytime, paytype, finished) VALUES (%number, %customerid, '%lastname %name', '%address', '%zip', '%city', '%phone', '%nip', '%pesel', %NOW%, %deadline, '%paytype', 1 )");
						g->str_replace(&insert_inv, "%number", itoa(++number));
						g->str_replace(&insert_inv, "%customerid", g->db_get_data(res,i,"userid"));				
						g->str_replace(&insert_inv, "%lastname", g->db_get_data(res,i,"lastname"));
						g->str_replace(&insert_inv, "%name", g->db_get_data(res,i,"name"));
						g->str_replace(&insert_inv, "%address", g->db_get_data(res,i,"address"));
						g->str_replace(&insert_inv, "%zip", g->db_get_data(res,i,"zip"));
						g->str_replace(&insert_inv, "%city", g->db_get_data(res,i,"city"));
						g->str_replace(&insert_inv, "%phone", g->db_get_data(res,i,"phone"));
						g->str_replace(&insert_inv, "%nip", g->db_get_data(res,i,"nip"));
						g->str_replace(&insert_inv, "%pesel", g->db_get_data(res,i,"pesel"));
						g->str_replace(&insert_inv, "%deadline", p->deadline);
						g->str_replace(&insert_inv, "%paytype", p->paytype);
			
						g->db_exec(insert_inv);			
						free(insert_inv);
						
						// ma³e uproszczenie w stosunku do lms-payments
						if( (result = g->db_query("SELECT MAX(id) AS id FROM invoices"))!=NULL ) {
							invoiceid = (result->nrows ? atoi(g->db_get_data(result,0,"id")) : 0);
							g->db_free(result);
						}
					}
					
					if( (result = g->db_pquery("SELECT * FROM invoicecontents WHERE tariffid = ? AND invoiceid = ? AND description = '?'", g->db_get_data(res,i,"tariffid"), itoa(invoiceid), description))!=NULL ) {
						
						if( result->nrows ) {
							query = strdup("UPDATE invoicecontents SET count=count+1 WHERE tariffid = %tariffid AND invoiceid = %invoiceid AND description = '%desc'");
							g->str_replace(&query, "%invoiceid", itoa(invoiceid));
							g->str_replace(&query, "%tariffid", g->db_get_data(res,i,"tariffid"));
							g->str_replace(&query, "%desc", description);
						} else {
							query = strdup("INSERT INTO invoicecontents (invoiceid, value, taxvalue, pkwiu, content, count, description, tariffid) VALUES (%invoiceid, %value, %taxvalue, '%pkwiu', 'szt.', 1, '%desc', %tariffid)");
							g->str_replace(&query, "%invoiceid", itoa(invoiceid));
							g->str_replace(&query, "%tariffid", g->db_get_data(res,i,"tariffid"));
							g->str_replace(&query, "%value", g->db_get_data(res,i,"value"));
							g->str_replace(&query, "%pkwiu", g->db_get_data(res,i,"pkwiu"));
							g->str_replace(&query, "%desc", description);
							if( strlen(taxvalue) )
								g->str_replace(&query, "%taxvalue", taxvalue);
							else
								g->str_replace(&query, "%taxvalue", "NULL");
						}
						g->db_exec(query);									
						g->db_free(result);
						free(query);
					}
					
					g->str_replace(&insert, "%invoiceid", itoa(invoiceid));
					exec = g->db_exec(insert);
				
				} else {
					g->str_replace(&insert, "%invoiceid", "0");
					exec = g->db_exec(insert) ? 1 : exec;
				}

				last_userid = uid;
				free(insert);
				free(description);
			}
    			g->db_free(res);
		}	
		free(y_period);
		free(q_period);
		free(m_period);
		free(w_period);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/payments] user payments reloaded", p->base.instance);
#endif
	} 
	else {
		free(query);
		syslog(LOG_ERR, "[%s/payments] Unable to read 'invoices' table",p->base.instance);
	}

	// set timestamps
	if(exec) {
		g->db_exec("DELETE FROM timestamps WHERE tablename = 'cash' OR tablename = '_global'");
		g->db_exec("INSERT INTO timestamps (tablename, time) VALUES ('cash', %NOW%)");
		g->db_exec("INSERT INTO timestamps (tablename, time) VALUES ('_global', %NOW%)");
	}
	// remove old assignments
	if(p->expiry_days<0) p->expiry_days *= -1; // number of expiry days can't be negative
	g->db_pexec("DELETE FROM assignments WHERE dateto < %NOW% - 86400 * ? AND dateto != 0 ", itoa(p->expiry_days));

	// clean up
	free(p->comment);
	free(p->deadline);
	free(p->paytype);
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
	free(s); s = g->str_concat(instance, ":deadline");
	p->deadline = strdup(g->iniparser_getstring(ini, s, "14"));
	free(s); s = g->str_concat(instance, ":paytype");
	p->paytype = strdup(g->iniparser_getstring(ini, s, "PRZELEW"));
	free(s); s = g->str_concat(instance, ":up_payments");
	p->up_payments = g->iniparser_getboolean(ini, s, 1);
	free(s); s = g->str_concat(instance, ":expiry_days");
	p->expiry_days = g->iniparser_getint(ini, s, 30);
	
	g->iniparser_freedict(ini);
	free(s);
	free(instance);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/payments] initialized", p->base.instance);
#endif	
	return(p);
}
