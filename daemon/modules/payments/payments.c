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
#include "payments.h"

char * itoa(int i)
{
	static char string[12];
	sprintf(string, "%d", i);
	return string;
}

char * ftoa(double i)
{
	static char string[12];
	sprintf(string, "%.2f", i);
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

	strftime(to, 11, "%Y/%m/%d", localtime(&old_time));
	strftime(from, 11, "%Y/%m/%d", localtime(&new_time));

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
	QueryHandle *res, *result, *sres;
	unsigned char *insert;
	unsigned char *w_period, *m_period, *q_period, *y_period, *value, *taxid;
	unsigned char *description;
	int i, invoiceid=0, last_customerid=0, number=0, exec=0, suspended=0, itemid=0;

	time_t t;
	struct tm *tt;
	unsigned char monthday[3], month[3], year[5], quarterday[3], weekday[2], yearday[4];  //odjac jeden?
	unsigned char start[12], end[12];
	
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

	// and setting appropriate time limits to get current invoice number
	tt->tm_sec = 0;
	tt->tm_min = 0;
	tt->tm_hour = 0;
	tt->tm_mday = 1;
	tt->tm_year = atoi(year)-1900;

	if(p->monthly_num)
	{
		tt->tm_mon = atoi(month)-1; // current month
		strftime(start,	sizeof(start), "%s", tt);
		tt->tm_mon++; // next month
		strftime(end, sizeof(end), "%s", tt);
	}
	else
	{
		tt->tm_mon = 0; // January
		strftime(start,	sizeof(start), "%s", tt);
		tt->tm_year++; // next year
		strftime(end, sizeof(end), "%s", tt);
	}

	/****** main payments *******/
	if( (res = g->db_pquery(g->conn, "SELECT * FROM payments WHERE value <> 0 AND ((period=0 AND at=?) OR (period=1 AND at=?) OR (period=2 AND at=?) OR (period=3 AND at=?))", weekday, monthday, quarterday, yearday))!= NULL )
	{
		for(i=0; i<g->db_nrows(res); i++) 
		{
			exec = (g->db_pexec(g->conn, "INSERT INTO cash (time, type, value, customerid, comment, docid) VALUES (%NOW%, 2, ?, 0, '? / ?', 0)",
					g->db_get_data(res,i,"value"),
					g->db_get_data(res,i,"name"),
					g->db_get_data(res,i,"creditor")
				) ? 1 : exec);
		}
		g->db_free(&res);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/payments] main payments reloaded", p->base.instance);
#endif
	} else 
		syslog(LOG_ERR, "[%s/payments] Unable to read 'payments' table",p->base.instance);
		
	/****** customer payments *******/
	// first get max invoiceid for present year
	if( (res = g->db_pquery(g->conn, "SELECT MAX(number) AS number FROM documents WHERE cdate >= ? AND cdate < ? AND type = 1", start, end))!= NULL ) 
	{
  		if( g->db_nrows(res) )
			number = atoi(g->db_get_data(res,0,"number"));
		g->db_free(&res);

		// payments accounting and invoices writing
		res = g->db_pquery(g->conn, "SELECT assignments.id AS id, tariffid, customerid, period, at, ROUND(CASE discount WHEN 0 THEN value ELSE value-value*discount/100 END, 2) AS value, taxid, suspended, prodid, uprate, downrate, tariffs.name AS tariff, invoice, UPPER(lastname) AS lastname, customers.name AS name, address, zip, city, ten, ssn FROM assignments, tariffs, customers WHERE tariffs.id = tariffid AND customerid = customers.id AND status = 3 AND deleted = 0 AND value <> 0 AND ((period = 0 AND at = ?) OR (period = 1 AND at = ?) OR (period = 2 AND at = ?) OR (period = 3 AND at = ?)) AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) ORDER BY customerid, invoice DESC, value DESC", weekday, monthday, quarterday, yearday);
		
		for(i=0; i<g->db_nrows(res); i++) 
		{
			int uid = atoi(g->db_get_data(res,i,"customerid"));
			int s_state = atoi(g->db_get_data(res,i,"suspended"));
			double val = atof(g->db_get_data(res,i,"value"));
			
			// assignments suspending check
			if( suspended != uid )
			{
				sres = g->db_pquery(g->conn, "SELECT 1 FROM assignments, customers WHERE customerid = customers.id AND tariffid = 0 AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) AND customerid = ?", g->db_get_data(res,i,"customerid"));
				if( g->db_nrows(sres) ) 
				{
					suspended = uid;
				}
				g->db_free(&sres);
			}

			if( suspended == uid || s_state )
				val = val * p->suspension_percentage / 100;
			
			if( !val )
				continue;

			value = ftoa(val);
			taxid = g->db_get_data(res,i,"taxid");

			// prepare insert to 'cash' table
			insert = strdup("INSERT INTO cash (time, type, value, taxid, customerid, comment, docid, itemid) VALUES (%NOW%, 4, %value, %taxid, %customerid, '?', %invoiceid, %itemid)");
			g->str_replace(&insert, "%customerid", g->db_get_data(res,i,"customerid"));
			g->str_replace(&insert, "%value", value);
			description = strdup(p->comment);
			switch( atoi(g->db_get_data(res,i,"period")) ) 
			{
				case 0: g->str_replace(&description, "%period", w_period); break;
				case 1: g->str_replace(&description, "%period", m_period); break;
				case 2: g->str_replace(&description, "%period", q_period); break;
				case 3: g->str_replace(&description, "%period", y_period); break;
			}
			g->str_replace(&description, "%tariff", g->db_get_data(res,i,"tariff"));
			g->str_replace(&insert, "%taxid", taxid);
			
			if( atoi(g->db_get_data(res,i,"invoice")) ) 
			{
				if( last_customerid != uid ) 
				{
					// prepare insert to 'invoices' table
					g->db_pexec(g->conn, "INSERT INTO documents (number, type, customerid, name, address, zip, city, ten, ssn, cdate, paytime, paytype) VALUES (?, 1, ?, '? ?', '?', '?', '?', '?', '?', %NOW%, ?, '?')",
						itoa(++number),
						g->db_get_data(res,i,"customerid"),
						g->db_get_data(res,i,"lastname"),
						g->db_get_data(res,i,"name"),
						g->db_get_data(res,i,"address"),
						g->db_get_data(res,i,"zip"),
						g->db_get_data(res,i,"city"),
						g->db_get_data(res,i,"ten"),
						g->db_get_data(res,i,"ssn"),
						p->deadline,
						p->paytype
					);

					// ma³e uproszczenie w stosunku do lms-payments
					result = g->db_query(g->conn, "SELECT MAX(id) AS id FROM documents WHERE type = 1");
					invoiceid = (g->db_nrows(result) ? atoi(g->db_get_data(result,0,"id")) : 0);
					g->db_free(&result);
					itemid = 0;
				}
				
				result = g->db_pquery(g->conn, "SELECT itemid FROM invoicecontents WHERE tariffid = ? AND docid = ? AND description = '?'", g->db_get_data(res,i,"tariffid"), itoa(invoiceid), description);

				if( g->db_nrows(result) ) 
				{
					g->db_pexec(g->conn, "UPDATE invoicecontents SET count=count+1 WHERE tariffid = ? AND docid = ? AND description = '?'",
						g->db_get_data(res,i,"tariffid"),
						itoa(invoiceid),
						description
						);
					
					exec = g->db_pexec(g->conn, "UPDATE cash SET value=value+? WHERE docid=? AND itemid=?",value, itoa(invoiceid), g->db_get_data(result,0,"itemid"));
				}
				else 
				{
					itemid++;

					g->db_pexec(g->conn,"INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, description, tariffid) VALUES (?, ?, ?, ?, '?', 'szt.', 1, '?', ?)",
						itoa(invoiceid),
						itoa(itemid),
						value,
						taxid,
						g->db_get_data(res,i,"prodid"),
						description,
						g->db_get_data(res,i,"tariffid")
						);
					
					g->str_replace(&insert, "%invoiceid", itoa(invoiceid));
					g->str_replace(&insert, "%itemid", itoa(itemid));
					exec = g->db_pexec(g->conn, insert, description);
				}
				g->db_free(&result);
			} 
			else 
			{
				g->str_replace(&insert, "%invoiceid", "0");
				g->str_replace(&insert, "%itemid", "0");
				exec = g->db_pexec(g->conn, insert, description) ? 1 : exec;
			}

			last_customerid = uid;
			free(insert);
			free(description);
		}
    		
		g->db_free(&res);
		free(y_period);
		free(q_period);
		free(m_period);
		free(w_period);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/payments] customer payments reloaded", p->base.instance);
#endif
	}
	else 
		syslog(LOG_ERR, "[%s/payments] Unable to read 'invoices' table",p->base.instance);

	// set timestamps
	if(exec) 
	{
		g->db_exec(g->conn, "DELETE FROM timestamps WHERE tablename = 'cash' OR tablename = '_global'");
		g->db_exec(g->conn, "INSERT INTO timestamps (tablename, time) VALUES ('cash', %NOW%)");
		g->db_exec(g->conn, "INSERT INTO timestamps (tablename, time) VALUES ('_global', %NOW%)");
	}
	// remove old assignments
	if(p->expiry_days<0) p->expiry_days *= -1; // number of expiry days can't be negative
	g->db_pexec(g->conn, "DELETE FROM assignments WHERE dateto < %NOW% - 86400 * ? AND dateto != 0 ", itoa(p->expiry_days));

	// clean up
	free(p->comment);
	free(p->deadline);
	free(p->paytype);
}

struct payments_module * init(GLOBAL *g, MODULE *m)
{
	struct payments_module *p;
	QueryHandle *res;
	
	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	p = (struct payments_module *) realloc(m, sizeof(struct payments_module));
	
	p->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	
	p->comment = strdup(g->config_getstring(p->base.ini, p->base.instance, "comment", "Subscription: %tariff for period: %period"));
	p->deadline = strdup(g->config_getstring(p->base.ini, p->base.instance, "deadline", "14"));
	p->paytype = strdup(g->config_getstring(p->base.ini, p->base.instance, "paytype", "TRANSFER"));
	p->up_payments = g->config_getbool(p->base.ini, p->base.instance, "up_payments", 1);
	p->expiry_days = g->config_getint(p->base.ini, p->base.instance, "expiry_days", 30);
	
	res = g->db_query(g->conn, "SELECT value FROM uiconfig WHERE section='invoices' AND var='monthly_numbering' AND disabled=0");
	if( g->db_nrows(res) )
	{
		char *str = g->db_get_data(res, 0, "value");
		switch( str[0] )
		{
			case 'y': p->monthly_num = 1; break;
			case 'Y': p->monthly_num = 1; break;
			case 'T': p->monthly_num = 1; break;
			case 't': p->monthly_num = 1; break;
			case '1': p->monthly_num = 1; break;
		}
	}
	g->db_free(&res);
	p->monthly_num = p->monthly_num ? p->monthly_num : 0;

	res = g->db_query(g->conn, "SELECT value FROM uiconfig WHERE section='finances' AND var='suspension_percentage' AND disabled=0");
	if( g->db_nrows(res) )
		p->suspension_percentage = atof(g->db_get_data(res, 0, "value"));
	else
		p->suspension_percentage = 0;
	g->db_free(&res);
	
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/payments] initialized", p->base.instance);
#endif	
	return(p);
}
