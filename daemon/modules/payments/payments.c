/*
 * LMS version 1.9-cvs
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
			case WEEKLY:
				t->tm_mday += 6;
				break;
			case MONTHLY:
				t->tm_mon += 1;
				t->tm_mday -= 1; 
				break;
			case QUARTERLY:
				t->tm_mon += 3;
				t->tm_mday -= 1;
				break;
			case YEARLY:
				t->tm_mon += 12;
				t->tm_mday -= 1; 
				break;
		}
	else
		switch(period) {
			case WEEKLY:
				t->tm_mday -= 6;
				break;		
			case MONTHLY:
				t->tm_mon -= 1;
				t->tm_mday += 1;
				break;
			case QUARTERLY:
				t->tm_mon -= 3;
				t->tm_mday += 1;
				break;
			case YEARLY:
				t->tm_mon -= 12;
				t->tm_mday += 1;
				break;
		}

	new_time = mktime(t);

	strftime(to, 11, "%Y/%m/%d", localtime(&old_time));
	strftime(from, 11, "%Y/%m/%d", localtime(&new_time));

	if(period != DAILY)
	{
		result = (unsigned char *) malloc(strlen(from)+strlen(to)+3);

		if( up_payments )
			sprintf(result, "%s-%s", to, from);
		else
			sprintf(result, "%s-%s", from, to);
	}
	else
	{
		result = strdup(to);
	}
	
	today = localtime(&old_time);
	
	return result;
}

void reload(GLOBAL *g, struct payments_module *p)
{
	QueryHandle *res, *result, *sres;
	unsigned char *insert;
	unsigned char *d_period, *w_period, *m_period, *q_period, *y_period, *value, *taxid;
	unsigned char *description, *invoiceid;
	int i, today, docid=0, last_customerid=0, number=0, exec=0, suspended=0, itemid=0;

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

	y_period = get_period(tt, YEARLY, p->up_payments);
	q_period = get_period(tt, QUARTERLY, p->up_payments);
	m_period = get_period(tt, MONTHLY, p->up_payments);
 	w_period = get_period(tt, WEEKLY, p->up_payments);
	d_period = get_period(tt, DAILY, p->up_payments);

	// and set appropriate time limits to get current invoice number
	tt->tm_sec = 0;
	tt->tm_min = 0;
	tt->tm_hour = 0;
	tt->tm_mday = 1;

	tt->tm_mon = atoi(month)-1;
	tt->tm_year = atoi(year)-1900;

	switch(p->num_period)
	{
		case DAILY:
			tt->tm_mday = atoi(monthday); // current day
		break;
		case WEEKLY:
			tt->tm_mday = atoi(monthday) - atoi(weekday) + 1; // last Monday
		break;
		case MONTHLY:
		break;
		case QUARTERLY:
			switch(tt->tm_mon)
			{
				case 0: case 1: case 2: tt->tm_mon = 0; break;
				case 3: case 4: case 5: tt->tm_mon = 3; break;
				case 6: case 7: case 8: tt->tm_mon = 6; break;
				case 9: case 10: case 11: tt->tm_mon = 9; break;
			}
		break;
		default: //YEARLY
			tt->tm_mon = 0; // January
		break;
	}
	strftime(start,	sizeof(start), "%s", tt);
	switch(p->num_period)
	{
		case DAILY:
			tt->tm_mday++; // tomorrow
		break;
		case WEEKLY:
			tt->tm_wday += 7; // start of next week
		break;
		case MONTHLY:
			tt->tm_mon++; // next month
		break;
		case QUARTERLY:
			tt->tm_mon += 3; // first month of next quarter
		break;
		default: //YEARLY
			tt->tm_year++; // next year
		break;
	}
	strftime(end, sizeof(end), "%s", tt);

	// today (for disposable liabilities)
	tt->tm_sec = 0;
	tt->tm_min = 0;
	tt->tm_hour = 0;
	tt->tm_wday = atoi(weekday);
	tt->tm_mday = atoi(monthday);
	tt->tm_mon = atoi(month)-1;
	tt->tm_year = atoi(year)-1900;
	today = (int) mktime(tt);

	/****** main payments *******/
	if( (res = g->db_pquery(g->conn, "SELECT * FROM payments WHERE value <> 0 AND (period="_DAILY_" OR (period="_WEEKLY_" AND at=?) OR (period="_MONTHLY_" AND at=?) OR (period="_QUARTERLY_" AND at=?) OR (period="_YEARLY_" AND at=?))", weekday, monthday, quarterday, yearday))!= NULL )
	{
		for(i=0; i<g->db_nrows(res); i++) 
		{
			exec = (g->db_pexec(g->conn, "INSERT INTO cash (time, type, value, customerid, comment, docid) VALUES (%NOW%, 1, ? * -1, 0, '? / ?', 0)",
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
	// first get next invoiceid
	if( (res = g->db_pquery(g->conn, "SELECT MAX(number) AS number FROM documents WHERE cdate >= ? AND cdate < ? AND numberplanid = ? AND type = 1", start, end, p->numberplanid))!= NULL ) 
	{
  		if( g->db_nrows(res) )
			number = atoi(g->db_get_data(res,0,"number"));
		g->db_free(&res);

		// payments accounting and invoices writing
		res = g->db_pquery(g->conn, "\
			SELECT tariffid, liabilityid, customerid, period, at, suspended, invoice, \
			    UPPER(lastname) AS lastname, customers.name AS custname, address, zip, city, ten, ssn, \
			    (CASE liabilityid WHEN 0 THEN tariffs.name ELSE liabilities.name END) AS name, \
			    (CASE liabilityid WHEN 0 THEN tariffs.taxid ELSE liabilities.taxid END) AS taxid, \
			    (CASE liabilityid WHEN 0 THEN tariffs.prodid ELSE liabilities.prodid END) AS prodid, \
			    (CASE liabilityid WHEN 0 THEN \
				ROUND(CASE discount WHEN 0 THEN tariffs.value ELSE tariffs.value-tariffs.value*discount/100 END, 2) \
			    ELSE \
				ROUND(CASE discount WHEN 0 THEN liabilities.value ELSE liabilities.value-liabilities.value*discount/100 END, 2) \
			    END) AS value \
			FROM assignments \
			LEFT JOIN tariffs ON (tariffid = tariffs.id) \
			LEFT JOIN liabilities ON (liabilityid = liabilities.id) \
			LEFT JOIN customers ON (customerid = customers.id) \
			WHERE status = 3 AND deleted = 0 \
			    AND (period="_DAILY_" \
			    OR (period="_WEEKLY_" AND at=?) \
			    OR (period="_MONTHLY_" AND at=?) \
			    OR (period="_QUARTERLY_" AND at=?) \
			    OR (period="_YEARLY_" AND at=?) \
			    OR (period="_DISPOSABLE_" AND at=?)) \
			    AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) \
			ORDER BY customerid, invoice DESC, value DESC", weekday, monthday, quarterday, yearday, itoa(today));
		
		for(i=0; i<g->db_nrows(res); i++) 
		{
			int uid = atoi(g->db_get_data(res,i,"customerid"));
			int s_state = atoi(g->db_get_data(res,i,"suspended"));
			int period = atoi(g->db_get_data(res,i,"period"));
			int liabilityid = atoi(g->db_get_data(res,i,"liabilityid"));
			double val = atof(g->db_get_data(res,i,"value"));
			
			if( !atof(g->db_get_data(res,i,"value")) ) continue;
			
			// assignments suspending check
			if( suspended != uid )
			{
				sres = g->db_pquery(g->conn, "SELECT 1 FROM assignments, customers WHERE customerid = customers.id AND tariffid = 0 AND liabilityid = 0 AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) AND customerid = ?", g->db_get_data(res,i,"customerid"));
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
			insert = strdup("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) VALUES (%NOW%, %value * -1, %taxid, %customerid, '?', %invoiceid, %itemid)");
			g->str_replace(&insert, "%customerid", g->db_get_data(res,i,"customerid"));
			g->str_replace(&insert, "%value", value);
			
			if( period == DISPOSABLE )
				description = strdup(g->db_get_data(res,i,"name"));
			else
				description = strdup(p->comment);
				
			switch( period ) 
			{
				case DAILY: g->str_replace(&description, "%period", d_period); break;
				case WEEKLY: g->str_replace(&description, "%period", w_period); break;
				case MONTHLY: g->str_replace(&description, "%period", m_period); break;
				case QUARTERLY: g->str_replace(&description, "%period", q_period); break;
				case YEARLY: g->str_replace(&description, "%period", y_period); break;
			}
			g->str_replace(&description, "%tariff", g->db_get_data(res,i,"name"));
			g->str_replace(&insert, "%taxid", taxid);
			
			if( atoi(g->db_get_data(res,i,"invoice")) ) 
			{
				if( last_customerid != uid ) 
				{
					// prepare insert to 'invoices' table
					g->db_pexec(g->conn, "INSERT INTO documents (number, numberplanid, type, customerid, name, address, zip, city, ten, ssn, cdate, paytime, paytype) VALUES (?, ?, 1, ?, '? ?', '?', '?', '?', '?', '?', %NOW%, ?, '?')",
						itoa(++number),
						p->numberplanid,
						g->db_get_data(res,i,"customerid"),
						g->db_get_data(res,i,"lastname"),
						g->db_get_data(res,i,"custname"),
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
					docid = g->db_nrows(result) ? atoi(g->db_get_data(result,0,"id")) : 0;
					g->db_free(&result);
					itemid = 0;
				}
				
				invoiceid = strdup(itoa(docid));
				
				result = g->db_pquery(g->conn, "SELECT itemid FROM invoicecontents WHERE tariffid = ? AND docid = ? AND description = '?' AND value = ?", g->db_get_data(res,i,"tariffid"), invoiceid, description, value);

				if( g->db_nrows(result) ) 
				{
					g->db_pexec(g->conn, "UPDATE invoicecontents SET count = count+1 WHERE docid = ? AND itemid = ?",
						invoiceid,
						g->db_get_data(result,0,"itemid")
						);
					
					exec = g->db_pexec(g->conn, "UPDATE cash SET value = value + (? * -1) WHERE docid = ? AND itemid = ?", value, invoiceid, g->db_get_data(result,0,"itemid"));
				}
				else 
				{
					itemid++;
					
					g->db_pexec(g->conn,"INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, description, tariffid) VALUES (?, ?, ?, ?, '?', 'szt.', 1, '?', ?)",
						invoiceid,
						itoa(itemid),
						value,
						taxid,
						g->db_get_data(res,i,"prodid"),
						description,
						g->db_get_data(res,i,"tariffid")
						);
					
					g->str_replace(&insert, "%invoiceid", invoiceid);
					g->str_replace(&insert, "%itemid", itoa(itemid));
					exec = g->db_pexec(g->conn, insert, description);
				}
				
				g->db_free(&result);
				free(invoiceid);
			} 
			else 
			{
				g->str_replace(&insert, "%invoiceid", "0");
				g->str_replace(&insert, "%itemid", "0");
				exec = g->db_pexec(g->conn, insert, description) ? 1 : exec;
			}

			// remove disposable liabilities
			if( liabilityid && !period )
			{
				g->db_pexec(g->conn, "DELETE FROM liabilities WHERE id=?", itoa(liabilityid));
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
		free(d_period);
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
	
	res = g->db_pquery(g->conn, "SELECT liabilityid AS id FROM assignments WHERE dateto < %NOW% - 86400 * ? AND dateto != 0 AND liabilityid != 0", itoa(p->expiry_days));
	for(i=0; i<g->db_nrows(res); i++) 
	{
		g->db_pexec(g->conn, "DELETE FROM liabilities WHERE id = ?", g->db_get_data(res,i,"id"));
	}
	g->db_free(&res);
	g->db_pexec(g->conn, "DELETE FROM assignments WHERE dateto < %NOW% - 86400 * ? AND dateto != 0 ", itoa(p->expiry_days));
	g->db_pexec(g->conn, "DELETE FROM assignments WHERE at = ?", itoa(today));

	// clean up
	free(p->comment);
	free(p->deadline);
	free(p->paytype);
	free(p->numberplanid);
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
	
	res = g->db_query(g->conn, "SELECT value FROM uiconfig WHERE section='finances' AND var='suspension_percentage' AND disabled=0");
	if( g->db_nrows(res) )
		p->suspension_percentage = atof(g->db_get_data(res, 0, "value"));
	else
		p->suspension_percentage = 0;
	g->db_free(&res);

	res = g->db_query(g->conn, "SELECT id, period FROM numberplans WHERE doctype=1 AND isdefault=1");
	if( g->db_nrows(res) )
	{
		p->num_period = atoi(g->db_get_data(res, 0, "period"));
		p->numberplanid = strdup(g->db_get_data(res, 0, "id"));
	}
	else
	{
		p->num_period = YEARLY;
		p->numberplanid = strdup("0");
	}
	g->db_free(&res);
	
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/payments] initialized", p->base.instance);
#endif	
	return(p);
}
