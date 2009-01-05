/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

int is_leap_year(int year)
{
	if(year % 4) return 0;
	if(year % 100) return 1;
	if(year % 400) return 0;
	return 1;
}

char * get_period(struct tm *today, int period, int up_payments)
{
	struct tm *t;
	static time_t new_time, old_time;
	static char from[11], to[11];
	char *result;
	
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
			case HALFYEARLY:
				t->tm_mon += 6;
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
			case HALFYEARLY:
				t->tm_mon -= 6;
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
		result = (char *) malloc(strlen(from)+strlen(to)+3);

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

char * get_diff_period(time_t fromdate, time_t todate)
{
	static char from[11], to[11];
	char *result;
	
	strftime(from, 11, "%Y/%m/%d", localtime(&fromdate));
	strftime(to, 11, "%Y/%m/%d", localtime(&todate));

	result = (char *) malloc(strlen(from)+strlen(to)+3);

	sprintf(result, "%s-%s", from, to);
	
	return result;
}

char * get_num_period_start(struct tm *t, int period)
{
	static char res[12];
	struct tm tt = *t;

	switch(period)
	{
		case DAILY:
		break;
		case WEEKLY:
			if (tt.tm_wday)
				tt.tm_mday -= (tt.tm_wday - 1); // last Monday
			else
				tt.tm_mday -= 6;
			tt.tm_wday = 1;
		break;
		case MONTHLY:
			tt.tm_mday = 1;
		break;
		case QUARTERLY:
			tt.tm_mday = 1;
			switch(tt.tm_mon)
			{
				case 0: case 1: case 2: tt.tm_mon = 0; break;
				case 3: case 4: case 5: tt.tm_mon = 3; break;
				case 6: case 7: case 8: tt.tm_mon = 6; break;
				case 9: case 10: case 11: tt.tm_mon = 9; break;
			}
		break;
		case HALFYEARLY:
			tt.tm_mday = 1;
			tt.tm_mon = tt.tm_mon < 6 ? 0 : 6;
		break;
		case CONTINUOUS:
			tt.tm_year = 70; // 1970-01-01
			tt.tm_mday = 1;
			tt.tm_mon = 0;
		break;
		default: //YEARLY
			tt.tm_mday = 1;
			tt.tm_mon = 0; // January
		break;
	}
	strftime(res,	sizeof(res), "%s", &tt);
	return res;
}

char * get_num_period_end(struct tm *t, int period)
{
	static char res[12];
	struct tm tt = *t;

	switch(period)
	{
		case DAILY:
			tt.tm_mday++; // tomorrow
		break;
		case WEEKLY:
			if (tt.tm_wday)
				tt.tm_mday += (8 - tt.tm_wday); // next Monday
			else
				tt.tm_mday++;
		break;
		case MONTHLY:
			tt.tm_mday = 1;
			tt.tm_mon++; // next month
		break;
		case QUARTERLY:
			tt.tm_mday = 1;
			tt.tm_mon += 3; // first month of next quarter
		break;
		case HALFYEARLY:
			tt.tm_mday = 1;
			if (tt.tm_mon < 6)
				tt.tm_mon = 6;
			else {
				tt.tm_mon = 0;
				tt.tm_year += 1;
			}
		break;
		case CONTINUOUS:
			tt.tm_year = 138; // 2038-01-01
			tt.tm_mday = 1;
			tt.tm_mon = 0;
		break;
		default: //YEARLY
			tt.tm_mday = 1;
			tt.tm_year++; // next year
		break;
	}
	strftime(res, sizeof(res), "%s", &tt);
	return res;
}

void reload(GLOBAL *g, struct payments_module *p)
{
	QueryHandle *res, *result;
	char *insert, *description, *invoiceid, *value, *taxid;
	char *d_period, *w_period, *m_period, *q_period, *y_period, *h_period;
	int i, imonth, imday, today, n=2, k=2, m=2, o=2, pl=0;
	int docid=0, last_customerid=0, exec=0, suspended=0, itemid=0;

	time_t t;
	struct tm *tt;
	char monthday[3], month[3], year[5], quarterday[3], weekday[2], yearday[4], halfday[4];  //odjac jeden?
	char monthname[20];

	char *nets = strdup(" AND EXISTS (SELECT 1 FROM nodes, networks n \
				WHERE ownerid = ats.customerid \
				AND (%nets) \
	                        AND ((ipaddr > address AND ipaddr < ("BROADCAST")) \
				OR (ipaddr_pub > address AND ipaddr_pub < ("BROADCAST"))) \
				)");
				
	char *netnames = strdup(p->networks);
	char *netname = strdup(netnames);
	char *netsql = strdup("");

	char *enets = strdup(" AND NOT EXISTS (SELECT 1 FROM nodes, networks n \
				WHERE ownerid = ats.customerid \
				AND (%enets) \
	                        AND ((ipaddr > address AND ipaddr < ("BROADCAST")) \
				OR (ipaddr_pub > address AND ipaddr_pub < ("BROADCAST"))) \
				)");
				
	char *enetnames = strdup(p->excluded_networks);
	char *enetname = strdup(enetnames);
	char *enetsql = strdup("");
			
	char *groups = strdup(" AND EXISTS (SELECT 1 FROM customergroups g, customerassignments a \
				WHERE a.customerid = ats.customerid \
				AND g.id = a.customergroupid \
				AND (%groups)) \
				");
	
	char *groupnames = strdup(p->customergroups);
	char *groupname = strdup(groupnames);
	char *groupsql = strdup("");

	char *egroups = strdup(" AND NOT EXISTS (SELECT 1 FROM customergroups g, customerassignments a \
				WHERE a.customerid = ats.customerid \
				AND g.id = a.customergroupid \
				AND (%egroups)) \
				");
	
	char *egroupnames = strdup(p->excluded_customergroups);
	char *egroupname = strdup(egroupnames);
	char *egroupsql = strdup("");

	while( n>1 )
	{
    		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )
		{
			netsql = realloc(netsql, sizeof(char *) * (strlen(netsql) + strlen(netname) + 30));
			if(strlen(netsql))
				strcat(netsql, " OR UPPER(n.name) = UPPER('");
			else
				strcat(netsql, "UPPER(n.name) = UPPER('");
			
			strcat(netsql, netname);
			strcat(netsql, "')");
		}
	}
	free(netname); free(netnames);
	
	if(strlen(netsql))
		g->str_replace(&nets, "%nets", netsql);

	while( o>1 )
	{
    		o = sscanf(enetnames, "%s %[._a-zA-Z0-9- ]", enetname, enetnames);

		if( strlen(enetname) )
		{
			enetsql = realloc(enetsql, sizeof(char *) * (strlen(enetsql) + strlen(enetname) + 30));
			if(strlen(enetsql))
				strcat(enetsql, " OR UPPER(n.name) = UPPER('");
			else
				strcat(enetsql, "UPPER(n.name) = UPPER('");
			
			strcat(enetsql, enetname);
			strcat(enetsql, "')");
		}
	}
	free(enetname); free(enetnames);
	
	if(strlen(enetsql))
		g->str_replace(&enets, "%enets", enetsql);

	while( k>1 )
	{
		k = sscanf(groupnames, "%s %[._a-zA-Z0-9- ]", groupname, groupnames);

		if( strlen(groupname) )
		{
			groupsql = realloc(groupsql, sizeof(char *) * (strlen(groupsql) + strlen(groupname) + 30));
			if(strlen(groupsql))
				strcat(groupsql, " OR UPPER(g.name) = UPPER('");
			else
				strcat(groupsql, "UPPER(g.name) = UPPER('");
			
			strcat(groupsql, groupname);
			strcat(groupsql, "')");
		}		
	}		
	free(groupname); free(groupnames);

	if(strlen(groupsql))
		g->str_replace(&groups, "%groups", groupsql);
	
	while( m>1 )
	{
		m = sscanf(egroupnames, "%s %[._a-zA-Z0-9- ]", egroupname, egroupnames);

		if( strlen(egroupname) )
		{
			egroupsql = realloc(egroupsql, sizeof(char *) * (strlen(egroupsql) + strlen(egroupname) + 30));
			if(strlen(egroupsql))
				strcat(egroupsql, " OR UPPER(g.name) = UPPER('");
			else
				strcat(egroupsql, "UPPER(g.name) = UPPER('");
			
			strcat(egroupsql, egroupname);
			strcat(egroupsql, "')");
		}		
	}		
	free(egroupname); free(egroupnames);

	if(strlen(egroupsql))
		g->str_replace(&egroups, "%egroups", egroupsql);
	
	// get current date
	t = time(NULL);
	tt = localtime(&t);
	strftime(monthday, 	sizeof(monthday), 	"%d", tt);
	strftime(weekday, 	sizeof(weekday), 	"%u", tt);
	strftime(monthname, 	sizeof(monthname), 	"%B", tt);
	strftime(month, 	sizeof(month), 		"%m", tt);
	strftime(year, 		sizeof(year), 		"%Y", tt);

	imday = tt->tm_mday;
	imonth = tt->tm_mon+1;

	// leap year fix
	if(is_leap_year(tt->tm_year+1900) && tt->tm_yday+1 > 31+28)
		strncpy(yearday, itoa(tt->tm_yday), sizeof(yearday));
	else
		strncpy(yearday, itoa(tt->tm_yday+1), sizeof(yearday));
	
	// halfyear 
	if(imonth > 6)
		strncpy(halfday, itoa(imday + (imonth - 7) * 100), sizeof(halfday));
	else
		strncpy(halfday, itoa(imday + (imonth - 1) * 100), sizeof(halfday));

	switch(imonth) {
		case 1:
		case 4:
		case 7:
		case 10:
			sprintf(quarterday, "%d", imday);
			break;
		case 2:
		case 5:
		case 8:
		case 12:
			sprintf(quarterday, "%d", imday+100);
			break;
		default:
			sprintf(quarterday, "%d", imday+200);
			break;
	}

	y_period = get_period(tt, YEARLY, p->up_payments);
	h_period = get_period(tt, HALFYEARLY, p->up_payments);
	q_period = get_period(tt, QUARTERLY, p->up_payments);
	m_period = get_period(tt, MONTHLY, p->up_payments);
 	w_period = get_period(tt, WEEKLY, p->up_payments);
	d_period = get_period(tt, DAILY, p->up_payments);

	// today (for disposable liabilities)
	tt->tm_sec = 0;
	tt->tm_min = 0;
	tt->tm_hour = 0;
	today = mktime(tt);

	/****** main payments *******/
	if( (res = g->db_pquery(g->conn, "SELECT * FROM payments "
		"WHERE value <> 0 AND (period="_DAILY_" OR (period="_WEEKLY_" AND at=?) "
			"OR (period="_MONTHLY_" AND at=?) "
			"OR (period="_QUARTERLY_" AND at=?) "
			"OR (period="_HALFYEARLY_" AND at=?) "
			"OR (period="_YEARLY_" AND at=?))",
			weekday, monthday, quarterday, halfday, yearday))!= NULL )
	{
		for(i=0; i<g->db_nrows(res); i++) 
		{
			exec = (g->db_pexec(g->conn, "INSERT INTO cash (time, type, value, customerid, comment, docid) "
				"VALUES (%NOW%, 1, ? * -1, 0, '? / ?', 0)",
					g->db_get_data(res,i,"value"),
					g->db_get_data(res,i,"name"),
					g->db_get_data(res,i,"creditor")
				) ? 1 : exec);
		}
		g->db_free(&res);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/payments] Main payments reloaded", p->base.instance);
#endif
	}
	else 
		syslog(LOG_ERR, "[%s/payments] Unable to read 'payments' table", p->base.instance);

	/****** customer payments *******/
	// let's create main query
	char *query = strdup("\
			SELECT tariffid, liabilityid, customerid, period, at, suspended, invoice, \
			    UPPER(lastname) AS lastname, customers.name AS custname, address, zip, city, ten, ssn, \
			    ats.id AS assignmentid, settlement, datefrom, discount, divisionid, paytime, \
			    (CASE liabilityid WHEN 0 THEN tariffs.name ELSE liabilities.name END) AS name, \
			    (CASE liabilityid WHEN 0 THEN tariffs.taxid ELSE liabilities.taxid END) AS taxid, \
			    (CASE liabilityid WHEN 0 THEN tariffs.prodid ELSE liabilities.prodid END) AS prodid, \
			    (CASE liabilityid WHEN 0 THEN \
				ROUND(CASE discount WHEN 0 THEN tariffs.value ELSE tariffs.value-tariffs.value*discount/100 END, 2) \
			    ELSE \
				ROUND(CASE discount WHEN 0 THEN liabilities.value ELSE liabilities.value-liabilities.value*discount/100 END, 2) \
			    END) AS value \
			FROM assignments ats \
			LEFT JOIN tariffs ON (ats.tariffid = tariffs.id) \
			LEFT JOIN liabilities ON (ats.liabilityid = liabilities.id) \
			LEFT JOIN customers ON (ats.customerid = customers.id) \
			WHERE status = 3 AND deleted = 0 \
			    AND (period="_DAILY_" \
				    OR (period="_WEEKLY_" AND at=?) \
				    OR (period="_MONTHLY_" AND at=?) \
				    OR (period="_QUARTERLY_" AND at=?) \
				    OR (period="_HALFYEARLY_" AND at=?) \
				    OR (period="_YEARLY_" AND at=?) \
				    OR (period="_DISPOSABLE_" AND at=?)) \
			    AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) \
			    %nets \
			    %enets \
			    %groups \
			    %egroups \
			ORDER BY ats.customerid, invoice DESC, value DESC\
			");
			
	g->str_replace(&query, "%nets", strlen(netsql) ? nets : "");	
	g->str_replace(&query, "%enets", strlen(enetsql) ? enets : "");	
	g->str_replace(&query, "%groups", strlen(groupsql) ? groups : "");	
	g->str_replace(&query, "%egroups", strlen(egroupsql) ? egroups : "");	
		
	if( (res = g->db_pquery(g->conn, query, weekday, monthday, quarterday, halfday, yearday, itoa(today))) != NULL)
	{
		struct plan *plans = (struct plan *) malloc(sizeof(struct plan));
		int invoice_number = 0;
			
		if( g->db_nrows(res) )
		{
			if (!p->numberplanid)
			{
				// get numbering plans for all divisions
				result = g->db_query(g->conn, "SELECT n.id, n.period, COALESCE(a.divisionid, 0) AS divid "
    					"FROM numberplans n "
	    				"LEFT JOIN numberplanassignments a ON (a.planid = n.id) "
		    			"WHERE doctype = 1 AND isdefault = 1");
			
				for(i=0; i<g->db_nrows(result); i++) 
				{
					plans = (struct plan *) realloc(plans, (sizeof(struct plan) * (pl+1)));
					plans[pl].plan = atoi(g->db_get_data(result, i, "id"));
					plans[pl].period = atoi(g->db_get_data(result, i, "period"));
					plans[pl].division = atoi(g->db_get_data(result, i, "divid"));
					plans[pl].number = 0;
					pl++;
    				}
				g->db_free(&result);
			}
		}
#ifdef DEBUG1
		else
			syslog(LOG_INFO, "DEBUG: [%s/payments] Not found customer assignments", p->base.instance);
#endif		
		// payments accounting and invoices writing
		for(i=0; i<g->db_nrows(res); i++) 
		{
			int uid = atoi(g->db_get_data(res,i,"customerid"));
			int s_state = atoi(g->db_get_data(res,i,"suspended"));
			int period = atoi(g->db_get_data(res,i,"period"));
			int liabilityid = atoi(g->db_get_data(res,i,"liabilityid"));
			int settlement = atoi(g->db_get_data(res,i,"settlement"));
			int datefrom = atoi(g->db_get_data(res,i,"datefrom"));
			char *discount = g->db_get_data(res,i,"discount");
			double val = atof(g->db_get_data(res,i,"value"));
			
			if( !val ) continue;
			
			// assignments suspending check
			if( suspended != uid )
			{
				result = g->db_pquery(g->conn, "SELECT 1 FROM assignments, customers "
					"WHERE customerid = customers.id AND tariffid = 0 AND liabilityid = 0 "
					"AND (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) "
					"AND customerid = ?", g->db_get_data(res,i,"customerid"));

				if( g->db_nrows(result) ) 
				{
					suspended = uid;
				}
				g->db_free(&result);
			}

			if( suspended == uid || s_state )
				val = val * p->suspension_percentage / 100;
			
			if( !val ) continue;

			value = ftoa(val);
			taxid = g->db_get_data(res,i,"taxid");

			// prepare insert to 'cash' table
			insert = strdup("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) "
				"VALUES (%NOW%, %value * -1, %taxid, %customerid, '?', %docid, %itemid)");
			g->str_replace(&insert, "%customerid", g->db_get_data(res,i,"customerid"));
			g->str_replace(&insert, "%value", value);
			g->str_replace(&insert, "%taxid", taxid);
			
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
				case HALFYEARLY: g->str_replace(&description, "%period", h_period); break;
				case YEARLY: g->str_replace(&description, "%period", y_period); break;
			}
			g->str_replace(&description, "%tariff", g->db_get_data(res,i,"name"));
			g->str_replace(&description, "%month", monthname);
			g->str_replace(&description, "%year", year);
			
			if( atoi(g->db_get_data(res,i,"invoice")) ) 
			{
				if( last_customerid != uid ) 
				{
					char *divisionid = g->db_get_data(res,i,"divisionid");
					int divid = atoi(divisionid);
					int period, number = 0;
					char *numberplanid, *paytime;
					
					// select numberplan
					for(n=0; n<pl; n++)
						if(plans[n].division == divid)
							break;
					
					// numberplan found
					if(n < pl)
					{
						numberplanid = strdup(itoa(plans[n].plan));
						period = plans[n].period;
						number = plans[n].number;
					}
					else // not found, use default/shared plan
					{
						numberplanid = strdup(itoa(p->numberplanid));
						period = p->num_period;
						number = invoice_number;
					}
					
					if(!number)
					{
						char *start = get_num_period_start(tt, period);
						char *end = get_num_period_end(tt, period);

						// set invoice number
						result = g->db_pquery(g->conn, "SELECT MAX(number) AS number FROM documents "
							"WHERE cdate >= ? AND cdate < ? AND numberplanid = ? AND type = 1", 
							start, end, numberplanid); 
	
						if( g->db_nrows(result) )
							number = atoi(g->db_get_data(result,0,"number"));
						g->db_free(&result);
					}

					++number;
					
					if(n < pl)
					{
						// update number
						for(m=0; m<pl; m++)
							if(plans[m].plan == plans[n].plan)
								plans[m].number = number;
					}
					else
						invoice_number = number;
					
					// deadline
					if(atoi(g->db_get_data(res,i,"paytime")) < 0)
						paytime = p->deadline;
					else
						paytime = g->db_get_data(res,i,"paytime");

					// prepare insert to 'invoices' table
					g->db_pexec(g->conn, "INSERT INTO documents (number, numberplanid, type, divisionid, "
						"customerid, name, address, zip, city, ten, ssn, cdate, paytime, paytype) "
						"VALUES (?, ?, 1, ?, ?, '? ?', '?', '?', '?', '?', '?', %NOW%, ?, '?')",
						itoa(number),
						numberplanid,
						divisionid,
						g->db_get_data(res,i,"customerid"),
						g->db_get_data(res,i,"lastname"),
						g->db_get_data(res,i,"custname"),
						g->db_get_data(res,i,"address"),
						g->db_get_data(res,i,"zip"),
						g->db_get_data(res,i,"city"),
						g->db_get_data(res,i,"ten"),
						g->db_get_data(res,i,"ssn"),
						paytime,
						p->paytype
					);

					docid = g->db_last_insert_id(g->conn, "documents");
					itemid = 0;
					
					free(numberplanid);
				}
				
				invoiceid = strdup(itoa(docid));
				
				result = g->db_pquery(g->conn, "SELECT itemid FROM invoicecontents WHERE tariffid = ? AND docid = ? AND description = '?' AND value = ? AND discount = ?", g->db_get_data(res,i,"tariffid"), invoiceid, description, value, discount);

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
					
					g->db_pexec(g->conn,"INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, description, tariffid, discount) VALUES (?, ?, ?, ?, '?', 'szt.', 1, '?', ?, ?)",
						invoiceid,
						itoa(itemid),
						value,
						taxid,
						g->db_get_data(res,i,"prodid"),
						description,
						g->db_get_data(res,i,"tariffid"),
						discount
						);
					
					g->str_replace(&insert, "%docid", invoiceid);
					g->str_replace(&insert, "%itemid", itoa(itemid));
					exec = g->db_pexec(g->conn, insert, description);
				}
				
				g->db_free(&result);
				free(invoiceid);
			} 
			else 
			{
				g->str_replace(&insert, "%docid", "0");
				g->str_replace(&insert, "%itemid", "0");
				exec = g->db_pexec(g->conn, insert, description) ? 1 : exec;
			}

			free(insert);
			free(description);
			
			// remove disposable liabilities
			if( liabilityid && !period )
			{
				g->db_pexec(g->conn, "DELETE FROM liabilities WHERE id=?", itoa(liabilityid));
			}
			
			// settlements accounting has sense only for up payments
			if( settlement && datefrom && p->up_payments)
			{
				int alldays = 1;
				int diffdays = (int) ((today - datefrom)/86400);
				
				switch( period )
				{
					// there are no disposable or daily liabilities with settlement
					case WEEKLY: 	alldays = 7; break;
					case MONTHLY: 	alldays = 30; break;
					case QUARTERLY: alldays = 90; break;
					case HALFYEARLY: alldays = 182; break;
					case YEARLY: 	alldays = 365; break;
				}
				
				value = ftoa(diffdays * val/alldays);
				
				description = strdup(p->s_comment);
				g->str_replace(&description, "%period", get_diff_period(datefrom, today-86400));
				g->str_replace(&description, "%tariff", g->db_get_data(res,i,"name"));
				g->str_replace(&description, "%month", monthname);
				g->str_replace(&description, "%year", year);
				
				// prepare insert to 'cash' table
				insert = strdup("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) VALUES (%NOW%, %value * -1, %taxid, %customerid, '?', %docid, %itemid)");
				g->str_replace(&insert, "%customerid", g->db_get_data(res,i,"customerid"));
				g->str_replace(&insert, "%value", value);
				g->str_replace(&insert, "%taxid", taxid);

				// we're using transaction to not disable settlement flag
				// when something will goes wrong
				g->db_begin(g->conn);
				
				if( atoi(g->db_get_data(res,i,"invoice")) ) 
				{
					// oh, now we've got invoice id
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
					
						g->db_pexec(g->conn,"INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, description, tariffid, discount) VALUES (?, ?, ?, ?, '?', 'szt.', 1, '?', ?, ?)",
							invoiceid,
							itoa(itemid),
							value,
							taxid,
							g->db_get_data(res,i,"prodid"),
							description,
							g->db_get_data(res,i,"tariffid"),
							discount
							);
					
						g->str_replace(&insert, "%docid", invoiceid);
						g->str_replace(&insert, "%itemid", itoa(itemid));
						g->str_replace(&insert, "%value", value);
						exec = g->db_pexec(g->conn, insert, description);
					}
				
					g->db_free(&result);
					free(invoiceid);
				} 
				else 
				{
					g->str_replace(&insert, "%docid", "0");
					g->str_replace(&insert, "%itemid", "0");
					g->str_replace(&insert, "%value", value);
					g->db_pexec(g->conn, insert, description) ? 1 : exec;
				}
				
				// uncheck settlement flag
				g->db_pexec(g->conn, "UPDATE assignments SET settlement = 0 WHERE id = ?", g->db_get_data(res,i,"assignmentid"));

				g->db_commit(g->conn);
				
				free(insert);
				free(description);
			}

			last_customerid = uid;
		}
    		
		g->db_free(&res);
		free(y_period);
		free(h_period);
		free(q_period);
		free(m_period);
		free(w_period);
		free(d_period);
		
		free(plans);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/payments] customer payments reloaded", p->base.instance);
#endif
	}
	else 
		syslog(LOG_ERR, "[%s/payments] Unable to read tariff assignments", p->base.instance);

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
	free(nets); free(enets);
	free(groups); free(egroups);
	free(netsql); free(enetsql);
	free(groupsql);	free(egroupsql);
	free(p->comment);
	free(p->s_comment);
	free(p->deadline);
	free(p->paytype);
	free(p->networks);
	free(p->customergroups);
	free(p->excluded_networks);
	free(p->excluded_customergroups);
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
	p->s_comment = strdup(g->config_getstring(p->base.ini, p->base.instance, "settlement_comment", p->comment));
	p->deadline = strdup(g->config_getstring(p->base.ini, p->base.instance, "deadline", "14"));
	p->paytype = strdup(g->config_getstring(p->base.ini, p->base.instance, "paytype", "TRANSFER"));
	p->up_payments = g->config_getbool(p->base.ini, p->base.instance, "up_payments", 1);
	p->expiry_days = g->config_getint(p->base.ini, p->base.instance, "expiry_days", 30);
	p->networks = strdup(g->config_getstring(p->base.ini, p->base.instance, "networks", ""));
	p->customergroups = strdup(g->config_getstring(p->base.ini, p->base.instance, "customergroups", ""));
	p->excluded_customergroups = strdup(g->config_getstring(p->base.ini, p->base.instance, "excluded_customergroups", ""));
	p->excluded_networks = strdup(g->config_getstring(p->base.ini, p->base.instance, "excluded_networks", ""));
	p->numberplanid = g->config_getint(p->base.ini, p->base.instance, "numberplan", 0);
	p->num_period = YEARLY;
	
	res = g->db_query(g->conn, "SELECT value FROM uiconfig WHERE section='finances' AND var='suspension_percentage' AND disabled=0");
	if( g->db_nrows(res) )
		p->suspension_percentage = atof(g->db_get_data(res, 0, "value"));
	else
		p->suspension_percentage = 0;
	g->db_free(&res);

	if(p->numberplanid)
	{
		res = g->db_pquery(g->conn, "SELECT id, period FROM numberplans WHERE doctype=1 AND id=?", itoa(p->numberplanid));
		if(g->db_nrows(res))
			p->num_period = atoi(g->db_get_data(res, 0, "period"));
		else
			p->numberplanid = 0;

		g->db_free(&res);
	}

#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/payments] initialized", p->base.instance);
#endif	
	return(p);
}
