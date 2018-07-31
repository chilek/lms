/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
#include <regex.h>

#include "lmsd.h"
#include "payments.h"
#include "defs.h"

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
				if (t->tm_mon > 11) {
				    t->tm_mon -= 12;
				    t->tm_year += 1;
				}
				break;
			case HALFYEARLY:
				t->tm_mon += 6;
				t->tm_mday -= 1;
				if (t->tm_mon > 11) {
				    t->tm_mon -= 12;
				    t->tm_year += 1;
				}
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

char *get_tarifftype_str(struct payments_module *p, int tarifftype)
{
	switch (tarifftype) {
	case 1:
		return p->tariff_internet;
	case 2:
		return p->tariff_hosting;
	case 3:
		return p->tariff_service;
	case 4:
		return p->tariff_phone;
	case 5:
		return p->tariff_tv;
	default:
		return p->tariff_other;
	}
}

char *docnumber(const int number, const char *numbertemplate, const time_t currtime) {
	char tmp[51];

	regex_t regex;
	regmatch_t matches[2];
	regcomp(&regex, "%([0-9]*)N", REG_EXTENDED);
	if (!regexec(&regex, numbertemplate, 2, matches, 0)) {
		memcpy(tmp, numbertemplate + matches[1].rm_so, matches[1].rm_eo - matches[1].rm_so);
		tmp[matches[1].rm_eo - matches[1].rm_so] = 0;
		int digitcount = atoi(tmp);

		char tmp1[51], tmp2[51], tmp3[51];
		memcpy(tmp1, numbertemplate, matches[0].rm_so);
		tmp1[matches[0].rm_so] = 0;

		char format[51];
		strcat(format, "%");
		if (digitcount) {
			int i = strlen(format);
			format[i++] = '0';
			sprintf(format + i, "%d", digitcount);
		}
		strcat(format, "d");
		sprintf(tmp2, format, number);

		memcpy(tmp3, numbertemplate + matches[0].rm_eo, strlen(numbertemplate) - matches[0].rm_eo);
		tmp3[strlen(numbertemplate) - matches[0].rm_eo] = 0;
		sprintf(tmp, "%s%s%s", tmp1, tmp2, tmp3);
	}
	regfree(&regex);

	char data[51];
	strftime(data, 51, tmp, localtime(&currtime));

	char *result = malloc(strlen(data) + 1);
	memcpy(result, data, strlen(data) + 1);
	return result;
}

void reload(GLOBAL *g, struct payments_module *p)
{
	QueryHandle *res, *result;
	char *insert, *description, *invoiceid, *value, *taxid, *currtime;
	char *d_period, *w_period, *m_period, *q_period, *y_period, *h_period;
	int i, j, imonth, imday, today, n=2, k=2, m=2, o=2, pl=0;
	int docid=0, last_cid=0, last_paytype=0, last_plan=0, exec=0, suspended=0, itemid=0;

	time_t t;
	struct tm tt;
	char monthday[3], month[3], year[5], quarterday[4], weekday[2], yearday[4], halfday[4];
	char monthname[20], nextmon[8];

	char *nets = strdup(" AND EXISTS (SELECT 1 FROM vnodes, networks n "
				"WHERE ownerid = a.customerid "
				    "AND (%nets) "
	                "AND ((ipaddr > address AND ipaddr < broadcast(address, inet_aton(mask))) "
				        "OR (ipaddr_pub > address AND ipaddr_pub < broadcast(address, inet_aton(mask)))) )");

	char *netnames = strdup(p->networks);
	char *netname = strdup(netnames);
	char *netsql = strdup("");

	char *enets = strdup(" AND NOT EXISTS (SELECT 1 FROM vnodes, networks n "
				"WHERE ownerid = a.customerid "
				    "AND (%enets) "
	                "AND ((ipaddr > address AND ipaddr < broadcast(address, inet_aton(mask))) "
				        "OR (ipaddr_pub > address AND ipaddr_pub < broadcast(address, inet_aton(mask)))) )");

	char *enetnames = strdup(p->excluded_networks);
	char *enetname = strdup(enetnames);
	char *enetsql = strdup("");

	char *groups = strdup(" AND EXISTS (SELECT 1 FROM customergroups g, customerassignments ca "
				"WHERE ca.customerid = a.customerid "
				    "AND g.id = ca.customergroupid "
				    "AND (%groups)) ");

	char *groupnames = strdup(p->customergroups);
	char *groupname = strdup(groupnames);
	char *groupsql = strdup("");

	char *egroups = strdup(" AND NOT EXISTS (SELECT 1 FROM customergroups g, customerassignments ca "
				"WHERE ca.customerid = a.customerid "
				    "AND g.id = ca.customergroupid "
				    "AND (%egroups)) ");

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
	memcpy(&tt, localtime(&t), sizeof(tt));
	strftime(monthday, 	sizeof(monthday), 	"%d", &tt);
	strftime(weekday, 	sizeof(weekday), 	"%u", &tt);
	strftime(monthname, 	sizeof(monthname), 	"%B", &tt);
	strftime(month, 	sizeof(month), 		"%m", &tt);
	strftime(year, 		sizeof(year), 		"%Y", &tt);

	currtime = strdup(itoa(t));
	imday = tt.tm_mday;
	imonth = tt.tm_mon+1;

	// leap year fix
	if(is_leap_year(tt.tm_year+1900) && tt.tm_yday+1 > 31+28)
		strncpy(yearday, itoa(tt.tm_yday), sizeof(yearday));
	else
		strncpy(yearday, itoa(tt.tm_yday+1), sizeof(yearday));

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
		case 11:
			sprintf(quarterday, "%d", imday+100);
			break;
		default:
			sprintf(quarterday, "%d", imday+200);
			break;
	}

	// next month in YYYY/MM format
	if (imonth == 12)
		snprintf(nextmon, sizeof(nextmon), "%04d/%02d", tt.tm_year+1901, 1);
	else
		snprintf(nextmon, sizeof(nextmon), "%04d/%02d", tt.tm_year+1900, imonth+1);

	// time periods
	y_period = get_period(&tt, YEARLY, p->up_payments);
	h_period = get_period(&tt, HALFYEARLY, p->up_payments);
	q_period = get_period(&tt, QUARTERLY, p->up_payments);
	m_period = get_period(&tt, MONTHLY, p->up_payments);
	w_period = get_period(&tt, WEEKLY, p->up_payments);
	d_period = get_period(&tt, DAILY, p->up_payments);

	// today (for disposable liabilities)
	tt.tm_sec = 0;
	tt.tm_min = 0;
	tt.tm_hour = 0;
	today = mktime(&tt);

	/****** main payments *******/
	if( (res = g->db->pquery(g->db->conn, "SELECT * FROM payments "
		"WHERE value <> 0 AND (period="_DAILY_" OR (period="_WEEKLY_" AND at=?) "
			"OR (period="_MONTHLY_" AND at=?) "
			"OR (period="_QUARTERLY_" AND at=?) "
			"OR (period="_HALFYEARLY_" AND at=?) "
			"OR (period="_YEARLY_" AND at=?))",
			weekday, monthday, quarterday, halfday, yearday))!= NULL )
	{
		for(i=0; i<g->db->nrows(res); i++) 
		{
			exec = (g->db->pexec(g->db->conn, "INSERT INTO cash (time, type, value, customerid, comment, docid) "
				"VALUES (?, 1, ? * -1, 0, '? / ?', 0)",
					currtime,
					g->db->get_data(res,i,"value"),
					g->db->get_data(res,i,"name"),
					g->db->get_data(res,i,"creditor")
				) ? 1 : exec);
		}
		g->db->free(&res);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/payments] Main payments reloaded", p->base.instance);
#endif
	}
	else 
		syslog(LOG_ERR, "[%s/payments] Unable to read 'payments' table", p->base.instance);

	/****** customer payments *******/
	// let's create main query
	char *query = strdup("SELECT a.tariffid, a.customerid, a.period, t.period AS t_period, "
	    "a.at, a.suspended, a.invoice, a.id AS assignmentid, a.settlement, a.datefrom, a.pdiscount, a.vdiscount, "
		"c.paytype, a.paytype AS a_paytype, a.numberplanid, a.attribute, d.inv_paytype AS d_paytype, "
		"UPPER(c.lastname) AS lastname, c.name AS custname, c.address, c.zip, c.city, c.ten, c.ssn, "
		"c.countryid, c.divisionid, c.paytime, "
		"d.name AS div_name, d.shortname AS div_shortname, d.address AS div_address, d.city AS div_city, d.zip AS div_zip, "
		"d.countryid AS div_countryid, d.ten AS div_ten, d.regon AS div_regon, "
		"d.account AS div_account, d.inv_header AS div_inv_header, d.inv_footer AS div_inv_footer, "
		"d.inv_author AS div_inv_author, d.inv_cplace AS div_inv_cplace, "
		"(CASE WHEN a.liabilityid IS NULL THEN t.type ELSE -1 END) AS tarifftype, "
		"(CASE WHEN a.liabilityid IS NULL THEN t.name ELSE li.name END) AS name, "
		"(CASE WHEN a.liabilityid IS NULL THEN t.taxid ELSE li.taxid END) AS taxid, "
		"(CASE WHEN a.liabilityid IS NULL THEN t.prodid ELSE li.prodid END) AS prodid, "
		"(CASE WHEN a.liabilityid IS NULL THEN "
		    "ROUND((t.value - t.value * a.pdiscount / 100) - a.vdiscount, 2) "
			"ELSE ROUND((li.value - li.value * a.pdiscount / 100) - a.vdiscount, 2) "
			"END) AS value "
		"FROM assignments a "
		"JOIN customeraddressview c ON (a.customerid = c.id) "
		"LEFT JOIN tariffs t ON (a.tariffid = t.id) "
		"LEFT JOIN liabilities li ON (a.liabilityid = li.id) "
		"LEFT JOIN vdivisions d ON (d.id = c.divisionid) "
		"WHERE c.status = 3 AND c.deleted = 0 "
			"AND a.commited = 1 "
		    "AND ("
		        "(a.period="_DISPOSABLE_" AND at=?) "
		        "OR (("
		            "(a.period="_DAILY_") "
			        "OR (a.period="_WEEKLY_" AND at=?) "
			        "OR (a.period="_MONTHLY_" AND at=?) "
			        "OR (a.period="_QUARTERLY_" AND at=?) "
			        "OR (a.period="_HALFYEARLY_" AND at=?) "
			        "OR (a.period="_YEARLY_" AND at=?) "
			        ") "
			        "AND (a.datefrom <= ? OR a.datefrom = 0) "
			        "AND (a.dateto >= ? OR a.dateto = 0)"
			    ")"
			")"
		    "%nets"
		    "%enets"
		    "%groups"
		    "%egroups"
		" ORDER BY a.customerid, a.invoice DESC, a.paytype, a.numberplanid, value DESC");

	g->str_replace(&query, "%nets", strlen(netsql) ? nets : "");
	g->str_replace(&query, "%enets", strlen(enetsql) ? enets : "");
	g->str_replace(&query, "%groups", strlen(groupsql) ? groups : "");
	g->str_replace(&query, "%egroups", strlen(egroupsql) ? egroups : "");

	if( (res = g->db->pquery(g->db->conn, query,
		itoa(today), weekday, monthday, quarterday, halfday, yearday,  currtime, currtime)) != NULL)
	{
		struct plan *plans = (struct plan *) malloc(sizeof(struct plan));
		int invoice_number = 0;
		char *invoice_numbertemplate = "%N/LMS/%Y";

		if( g->db->nrows(res) )
		{
			if (!p->numberplanid)
			{
				// get numbering plans for all divisions
				result = g->db->query(g->db->conn, "SELECT n.id, n.period, template, COALESCE(a.divisionid, 0) AS divid, isdefault "
					"FROM numberplans n "
					"LEFT JOIN numberplanassignments a ON (a.planid = n.id) "
					"WHERE doctype = 1");

				for(i=0; i<g->db->nrows(result); i++) 
				{
					plans = (struct plan *) realloc(plans, (sizeof(struct plan) * (pl+1)));
					plans[pl].plan = atoi(g->db->get_data(result, i, "id"));
					plans[pl].period = atoi(g->db->get_data(result, i, "period"));
					plans[pl].division = atoi(g->db->get_data(result, i, "divid"));
					plans[pl].isdefault = atoi(g->db->get_data(result, i, "isdefault"));
					plans[pl].number = 0;
					plans[pl].numbertemplate = strdup(g->db->get_data(result, i, "template"));
					pl++;
				}
				g->db->free(&result);
			}
		}
#ifdef DEBUG1
		else
			syslog(LOG_INFO, "DEBUG: [%s/payments] Customer assignments not found", p->base.instance);
#endif
		// payments accounting and invoices writing
		for(i=0; i<g->db->nrows(res); i++)
		{
			char *cid_c         = g->db->get_data(res,i,"customerid");
			char *pdiscount     = g->db->get_data(res,i,"pdiscount");
			char *vdiscount     = g->db->get_data(res,i,"vdiscount");
			int cid             = atoi(cid_c);
			int s_state         = atoi(g->db->get_data(res,i,"suspended"));
			int period          = atoi(g->db->get_data(res,i,"period"));
			int settlement      = atoi(g->db->get_data(res,i,"settlement"));
			int datefrom        = atoi(g->db->get_data(res,i,"datefrom"));
			int t_period        = atoi(g->db->get_data(res,i,"t_period"));
			double val          = atof(g->db->get_data(res,i,"value"));
			int tarifftype_int  = atoi(g->db->get_data(res, i, "tarifftype"));
			char *tarifftype    = (tarifftype_int == -1 ? "" : get_tarifftype_str(p, tarifftype_int));

			if( !val ) continue;

			// assignments suspending check
			if( last_cid != cid )
			{
				result = g->db->pquery(g->db->conn, "SELECT 1 FROM assignments "
					"WHERE customerid = ? AND tariffid IS NULL AND liabilityid IS NULL "
					    "AND (datefrom <= ? OR datefrom = 0) AND (dateto >= ? OR dateto = 0)",
					cid_c, currtime, currtime);

				suspended = g->db->nrows(result) ? 1 : 0;
				g->db->free(&result);
			}

			if( suspended || s_state )
				val = val * p->suspension_percentage / 100;

			if( !val ) continue;

			// calculate assignment value according to tariff's period
			if (t_period && period != DISPOSABLE && t_period != period) {
				if (t_period == YEARLY)
					val = val / 12.0;
				else if (t_period == HALFYEARLY)
					val = val / 6.0;
				else if (t_period == QUARTERLY)
					val = val / 3.0;

				if (period == YEARLY)
					val = val * 12.0;
				else if (period == HALFYEARLY)
					val = val * 6.0;
				else if (period == QUARTERLY)
					val = val * 3.0;
				else if (period == WEEKLY)
					val = val / 4.0;
				else if (period == DAILY)
					val = val / 30.0;
			}

			value = ftoa(val);
			taxid = g->db->get_data(res,i,"taxid");

			// prepare insert to 'cash' table
			insert = strdup("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) "
				"VALUES (?, %value * -1, %taxid, %customerid, '?', %docid, %itemid)");
			g->str_replace(&insert, "%customerid", cid_c);
			g->str_replace(&insert, "%value", value);
			g->str_replace(&insert, "%taxid", taxid);

			if( period == DISPOSABLE )
				description = strdup(g->db->get_data(res,i,"name"));
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
			g->str_replace(&description, "%type", tarifftype);
			g->str_replace(&description, "%tariff", g->db->get_data(res,i,"name"));
			g->str_replace(&description, "%attribute", g->db->get_data(res,i,"attribute"));
			g->str_replace(&description, "%next_mon", nextmon);
			g->str_replace(&description, "%month", monthname);
			g->str_replace(&description, "%currentm", month);
			g->str_replace(&description, "%year", year);
			g->str_replace(&description, "%currenty", year);

			if( atoi(g->db->get_data(res,i,"invoice")) )
			{
				char *divisionid = g->db->get_data(res,i,"divisionid");
				int a_numberplan = atoi(g->db->get_data(res,i,"numberplanid"));
				int a_paytype = atoi(g->db->get_data(res,i,"a_paytype")); // assignment
				int c_paytype = atoi(g->db->get_data(res,i,"paytype"));   // customer
				int d_paytype = atoi(g->db->get_data(res,i,"d_paytype")); // division
				int paytype, numberplan, divid = atoi(divisionid);

				// paytype (by priority)
				if (a_paytype)
					paytype = a_paytype;
				else if (c_paytype)
					paytype = c_paytype;
				else if (d_paytype)
					paytype = d_paytype;
				else
					paytype = p->paytype;

				// select numberplan
				if (a_numberplan) {
					for (n=0; n<pl; n++)
						if (plans[n].plan == a_numberplan)
							break;
				} else {
					for (n=0; n<pl; n++)
						if (plans[n].division == divid && plans[n].isdefault)
							break;
				}

				numberplan = (n < pl) ? n : -1;

				if ( last_cid != cid || last_paytype != paytype || last_plan != numberplan)
				{
					char *countryid = g->db->get_data(res,i,"countryid");
					char *numberplanid, *paytime, *paytype_str = strdup(itoa(paytype));
					char *numbertemplate;
					int period, number = 0;

					last_paytype = paytype;
					last_plan = numberplan;

					// numberplan found
					if (numberplan >= 0) {
						numberplanid = strdup(itoa(plans[numberplan].plan));
						period = plans[numberplan].period;
						number = plans[numberplan].number;
						numbertemplate = plans[numberplan].numbertemplate;
					} else { // not found, use default/shared plan
						numberplanid = strdup(itoa(p->numberplanid));
						period = p->num_period;
						number = invoice_number;
						if (p->numberplanid)
							numbertemplate = NULL;
						else
							numbertemplate = invoice_numbertemplate;
					}

					if (!number && (numberplan >= 0 || !numbertemplate)) {
						char *start = get_num_period_start(&tt, period);
						char *end = get_num_period_end(&tt, period);

						// set invoice number
						result = g->db->pquery(g->db->conn, "SELECT MAX(number) AS number FROM documents "
							"WHERE cdate >= ? AND cdate < ? AND numberplanid = ? AND type = 1", 
							start, end, numberplanid);

						if( g->db->nrows(result) )
							number = atoi(g->db->get_data(result,0,"number"));
						g->db->free(&result);

						// search for number template
						for (j = 0; j < pl; j++)
							if (plans[j].plan == atoi(numberplanid))
								break;
						numbertemplate = plans[j].numbertemplate;
					}

					++number;

					if(n < pl)
					{
						// update number
						for(m=0; m<pl; m++)
							if(plans[m].plan == plans[n].plan)
								plans[m].number = number;
					} else {
						invoice_number = number;
						invoice_numbertemplate = numbertemplate;
					}

					// deadline
					if(atoi(g->db->get_data(res,i,"paytime")) < 0)
						paytime = p->deadline;
					else
						paytime = g->db->get_data(res,i,"paytime");

					char *fullnumber = docnumber(number, numbertemplate, (time_t) atoi(currtime));
					// prepare insert to 'invoices' table
					g->db->pexec(g->db->conn, "INSERT INTO documents (number, numberplanid, type, countryid, divisionid, "
						"customerid, name, address, zip, city, ten, ssn, cdate, sdate, paytime, paytype, "
						"div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon, "
						"div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber) "
						"VALUES (?, ?, 1, ?, ?, ?, '? ?', '?', '?', '?', '?', '?', ?, ?, ?, ?, "
						"'?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?')",
						itoa(number),
						numberplanid,
						countryid,
						divisionid,
						cid_c,
						g->db->get_data(res,i,"lastname"),
						g->db->get_data(res,i,"custname"),
						g->db->get_data(res,i,"address"),
						g->db->get_data(res,i,"zip"),
						g->db->get_data(res,i,"city"),
						g->db->get_data(res,i,"ten"),
						g->db->get_data(res,i,"ssn"),
						currtime,
						currtime,
						paytime,
						paytype_str,
						g->db->get_data(res, i, "div_name"),
						g->db->get_data(res, i, "div_shortname"),
						g->db->get_data(res, i, "div_address"),
						g->db->get_data(res, i, "div_city"),
						g->db->get_data(res, i, "div_zip"),
						g->db->get_data(res, i, "div_countryid"),
						g->db->get_data(res, i, "div_ten"),
						g->db->get_data(res, i, "div_regon"),
						g->db->get_data(res, i, "div_account"),
						g->db->get_data(res, i, "div_inv_header"),
						g->db->get_data(res, i, "div_inv_footer"),
						g->db->get_data(res, i, "div_inv_author"),
						g->db->get_data(res, i, "div_inv_cplace"),
						fullnumber
					);
					free(fullnumber);

					docid = g->db->last_insert_id(g->db->conn, "documents");
					itemid = 0;

					free(numberplanid);
					free(paytype_str);
				}

				invoiceid = strdup(itoa(docid));

				result = g->db->pquery(g->db->conn, "SELECT itemid FROM invoicecontents "
				    "WHERE tariffid = ? AND docid = ? AND description = '?' AND value = ? AND pdiscount = ? AND vdiscount = ?",
				    g->db->get_data(res,i,"tariffid"), invoiceid, description, value, pdiscount, vdiscount);

				if( g->db->nrows(result) ) 
				{
					g->db->pexec(g->db->conn, "UPDATE invoicecontents SET count = count+1 WHERE docid = ? AND itemid = ?",
						invoiceid,
						g->db->get_data(result,0,"itemid")
						);

					exec = g->db->pexec(g->db->conn, "UPDATE cash SET value = value + (? * -1) "
					    "WHERE docid = ? AND itemid = ?",
					    value, invoiceid, g->db->get_data(result,0,"itemid"));
				}
				else if (docid)
				{
					itemid++;

					g->db->pexec(g->db->conn,"INSERT INTO invoicecontents (docid, itemid, value, "
					        "taxid, prodid, content, count, description, tariffid, pdiscount, vdiscount) "
					        "VALUES (?, ?, ?, ?, '?', 'szt.', 1, '?', ?, ?, ?)",
						invoiceid,
						itoa(itemid),
						value,
						taxid,
						g->db->get_data(res,i,"prodid"),
						description,
						g->db->get_data(res,i,"tariffid"),
						pdiscount,
						vdiscount
						);

					g->str_replace(&insert, "%docid", invoiceid);
					g->str_replace(&insert, "%itemid", itoa(itemid));
					exec = g->db->pexec(g->db->conn, insert, currtime, description);
				}

				g->db->free(&result);
				free(invoiceid);
			}
			else
			{
				g->str_replace(&insert, "%docid", "0");
				g->str_replace(&insert, "%itemid", "0");
				exec = g->db->pexec(g->db->conn, insert, currtime, description) ? 1 : exec;
			}

			free(insert);
			free(description);

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
				g->str_replace(&description, "%type", tarifftype);
				g->str_replace(&description, "%tariff", g->db->get_data(res,i,"name"));
				g->str_replace(&description, "%attribute", g->db->get_data(res,i,"attribute"));
				g->str_replace(&description, "%month", monthname);
				g->str_replace(&description, "%year", year);

				// prepare insert to 'cash' table
				insert = strdup("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) "
					"VALUES (?, %value * -1, %taxid, %customerid, '?', %docid, %itemid)");

				g->str_replace(&insert, "%customerid", cid_c);
				g->str_replace(&insert, "%value", value);
				g->str_replace(&insert, "%taxid", taxid);

				// we're using transaction to not disable settlement flag
				// when something will goes wrong
				g->db->begin(g->db->conn);

				if( atoi(g->db->get_data(res,i,"invoice")) )
				{
					// oh, now we've got invoice id
					invoiceid = strdup(itoa(docid));

					result = g->db->pquery(g->db->conn, "SELECT itemid FROM invoicecontents "
					    "WHERE tariffid = ? AND docid = ? AND description = '?' AND value = ?",
					    g->db->get_data(res,i,"tariffid"), invoiceid, description, value);

					if( g->db->nrows(result) )
					{
						g->db->pexec(g->db->conn, "UPDATE invoicecontents SET count = count+1 WHERE docid = ? AND itemid = ?",
							invoiceid,
							g->db->get_data(result,0,"itemid")
						);

						exec = g->db->pexec(g->db->conn, "UPDATE cash SET value = value + (? * -1) "
						    "WHERE docid = ? AND itemid = ?",
						    value, invoiceid, g->db->get_data(result,0,"itemid"));
					}
					else if (docid)
					{
						itemid++;

						g->db->pexec(g->db->conn,"INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, "
						        "content, count, description, tariffid, pdiscount, vdiscount) "
						        "VALUES (?, ?, ?, ?, '?', 'szt.', 1, '?', ?, ?, ?)",
							invoiceid,
							itoa(itemid),
							value,
							taxid,
							g->db->get_data(res,i,"prodid"),
							description,
							g->db->get_data(res,i,"tariffid"),
							pdiscount,
							vdiscount
							);

						g->str_replace(&insert, "%docid", invoiceid);
						g->str_replace(&insert, "%itemid", itoa(itemid));
						g->str_replace(&insert, "%value", value);
						exec = g->db->pexec(g->db->conn, insert, currtime, description);
					}

					g->db->free(&result);
					free(invoiceid);
				}
				else
				{
					g->str_replace(&insert, "%docid", "0");
					g->str_replace(&insert, "%itemid", "0");
					g->str_replace(&insert, "%value", value);
					g->db->pexec(g->db->conn, insert, currtime, description) ? 1 : exec;
				}

				// uncheck settlement flag
				g->db->pexec(g->db->conn, "UPDATE assignments SET settlement = 0 WHERE id = ?", g->db->get_data(res,i,"assignmentid"));

				g->db->commit(g->db->conn);

				free(insert);
				free(description);
			}

			last_cid = cid;
		}

		g->db->free(&res);
		free(y_period);
		free(h_period);
		free(q_period);
		free(m_period);
		free(w_period);
		free(d_period);

		for (i = 0; i < pl; i++)
			free(plans[i].numbertemplate);
		free(plans);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/payments] Customer payments reloaded", p->base.instance);
#endif
	}
	else 
		syslog(LOG_ERR, "[%s/payments] Unable to read tariff assignments", p->base.instance);

	free(query);

	/****** invoices checking *******/
	if (p->check_invoices)
	{
		char *query = strdup(
			"UPDATE documents SET closed = 1 "
			"WHERE customerid IN ( "
				"SELECT a.customerid "
				"FROM cash a "
				"WHERE a.time <= %NOW% "
				"   %nets%enets%groups%egroups "
				"GROUP BY a.customerid "
				"HAVING SUM(a.value) >= 0) "
			"AND type IN (1, 3, 5) "
			"AND cdate <= %NOW% "
			"AND closed = 0");

		g->str_replace(&query, "%nets", strlen(netsql) ? nets : "");
		g->str_replace(&query, "%enets", strlen(enetsql) ? enets : "");
		g->str_replace(&query, "%groups", strlen(groupsql) ? groups : "");
		g->str_replace(&query, "%egroups", strlen(egroupsql) ? egroups : "");

		g->db->pexec(g->db->conn, query);

		free(query);
	}

	// remove old assignments
	if(p->expiry_days<0) p->expiry_days *= -1; // number of expiry days can't be negative

    char *exp_days = strdup(itoa(p->expiry_days));

	g->db->pexec(g->db->conn, "DELETE FROM liabilities "
	    "WHERE id IN ("
		    "SELECT liabilityid FROM assignments "
	        "WHERE dateto < ? - 86400 * ? AND dateto != 0 AND at < ? - 86400 * ? "
		        "AND liabilityid IS NOT NULL)",
	    currtime, exp_days, itoa(today), exp_days);
	g->db->pexec(g->db->conn, "DELETE FROM assignments "
	    "WHERE dateto < ? - 86400 * ? AND dateto != 0 AND at < ? - 86400 * ?",
	    currtime, exp_days, itoa(today), exp_days);

	// clean up
	free(exp_days);
	free(currtime);
	free(nets); free(enets);
	free(groups); free(egroups);
	free(netsql); free(enetsql);
	free(groupsql);	free(egroupsql);
	free(p->comment);
	free(p->s_comment);
	free(p->deadline);
	free(p->networks);
	free(p->customergroups);
	free(p->excluded_networks);
	free(p->excluded_customergroups);
}

struct payments_module * init(GLOBAL *g, MODULE *m)
{
	struct payments_module *p;
	QueryHandle *res;
	int i;

	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}

	p = (struct payments_module *) realloc(m, sizeof(struct payments_module));

	p->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	p->comment = strdup(g->config_getstring(p->base.ini, p->base.instance, "comment", "Subscription: %tariff - %attribute for period: %period"));
	p->s_comment = strdup(g->config_getstring(p->base.ini, p->base.instance, "settlement_comment", p->comment));
	p->deadline = strdup(g->config_getstring(p->base.ini, p->base.instance, "deadline", "14"));
	p->paytype = g->config_getint(p->base.ini, p->base.instance, "paytype", 2);
	p->up_payments = g->config_getbool(p->base.ini, p->base.instance, "up_payments", 1);
	p->expiry_days = g->config_getint(p->base.ini, p->base.instance, "expiry_days", 30);
	p->networks = strdup(g->config_getstring(p->base.ini, p->base.instance, "networks", ""));
	p->customergroups = strdup(g->config_getstring(p->base.ini, p->base.instance, "customergroups", ""));
	p->excluded_customergroups = strdup(g->config_getstring(p->base.ini, p->base.instance, "excluded_customergroups", ""));
	p->excluded_networks = strdup(g->config_getstring(p->base.ini, p->base.instance, "excluded_networks", ""));
	p->numberplanid = g->config_getint(p->base.ini, p->base.instance, "numberplan", 0);
	p->check_invoices = g->config_getbool(p->base.ini, p->base.instance, "check_invoices", 0);

	p->tariff_internet = _SERVICE_INTERNET_;
	p->tariff_hosting = _SERVICE_HOSTING_;
	p->tariff_service = _SERVICE_SERVICE_;
	p->tariff_phone = _SERVICE_PHONE_;
	p->tariff_tv = _SERVICE_TV_;
	p->tariff_other = _SERVICE_OTHER_;

	res = g->db->query(g->db->conn, "SELECT var, value FROM uiconfig WHERE section='tarifftypes' AND disabled=0");
	for (i = 0; i < g->db->nrows(res); i++) {
		char *val = g->db->get_data(res, i, "value");
		if (!strcmp(g->db->get_data(res, i, "var"), _SERVICE_INTERNET_))
			p->tariff_internet = strdup(val);
		else if (!strcmp(g->db->get_data(res, i, "var"), _SERVICE_HOSTING_))
			p->tariff_hosting = strdup(val);
		else if (!strcmp(g->db->get_data(res, i, "var"), _SERVICE_SERVICE_))
			p->tariff_service = strdup(val);
		else if (!strcmp(g->db->get_data(res, i, "var"), _SERVICE_PHONE_))
			p->tariff_phone = strdup(val);
		else if (!strcmp(g->db->get_data(res, i, "var"), _SERVICE_TV_))
			p->tariff_tv = strdup(val);
		else if (!strcmp(g->db->get_data(res, i, "var"), _SERVICE_OTHER_))
			p->tariff_other = strdup(val);
	}
	g->db->free(&res);

	p->num_period = YEARLY;

	res = g->db->query(g->db->conn, "SELECT value FROM uiconfig WHERE section='finances' AND var='suspension_percentage' AND disabled=0");
	if( g->db->nrows(res) )
		p->suspension_percentage = atof(g->db->get_data(res, 0, "value"));
	else
		p->suspension_percentage = 0;
	g->db->free(&res);

	if(p->numberplanid)
	{
		res = g->db->pquery(g->db->conn, "SELECT id, period FROM numberplans WHERE doctype=1 AND id=?", itoa(p->numberplanid));
		if(g->db->nrows(res))
			p->num_period = atoi(g->db->get_data(res, 0, "period"));
		else
			p->numberplanid = 0;

		g->db->free(&res);
	}

#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/payments] initialized", p->base.instance);
#endif	
	return(p);
}
