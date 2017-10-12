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

#include "lmsd.h"
#include "cutoff.h"
#include "defs.h"

char * itoa(int i)
{
        static char string[12];
	sprintf(string, "%d", i);
	return string;
}

void reload(GLOBAL *g, struct cutoff_module *c)
{
	QueryHandle *res;
	char *query;
	int i, execu=0, execn=0, u=0, n=0, x=2, o=2, k=2, m=2;
	int plimit=0, limit=0;
	char time_fmt[11];
	size_t tmax = 11;
	char fmt[] = "%Y/%m/%d";
	struct tm *wsk;
	time_t t;
	char *suspended;

	char *group = strdup(itoa(c->nodegroup_only));

	char *nets = strdup(" AND EXISTS (SELECT 1 FROM networks net "
				"WHERE (%nets) "
	                        "AND ((ipaddr > net.address AND ipaddr < broadcast(net.address, inet_aton(net.mask))) "
				"OR (ipaddr_pub > net.address AND ipaddr_pub < broadcast(net.address, inet_aton(net.mask)))) "
				")");

	char *nets_cust = strdup(" AND EXISTS (SELECT 1 FROM vnodes n, networks net "
				"WHERE n.ownerid = c.id "
				"AND (%nets) "
	                        "AND ((ipaddr > net.address AND ipaddr < broadcast(net.address, inet_aton(net.mask))) "
				"OR (ipaddr_pub > net.address AND ipaddr_pub < broadcast(net.address, inet_aton(net.mask)))) "
				")");
				
	char *netnames = strdup(c->networks);
	char *netname = strdup(netnames);
	char *netsql = strdup("");

	char *enets = strdup(" AND NOT EXISTS (SELECT 1 FROM networks net "
				"WHERE (%enets) "
	                        "AND ((ipaddr > net.address AND ipaddr < broadcast(net.address, inet_aton(net.mask))) "
				"OR (ipaddr_pub > net.address AND ipaddr_pub < broadcast(net.address, inet_aton(net.mask)))) "
				")");

	char *enets_cust = strdup(" AND NOT EXISTS (SELECT 1 FROM vnodes n, networks net "
				"WHERE n.ownerid = c.id "
				"AND (%enets) "
	                        "AND ((ipaddr > net.address AND ipaddr < broadcast(net.address, inet_aton(net.mask))) "
				"OR (ipaddr_pub > net.address AND ipaddr_pub < broadcast(net.address, inet_aton(net.mask)))) "
				")");
				
	char *enetnames = strdup(c->excluded_networks);
	char *enetname = strdup(enetnames);
	char *enetsql = strdup("");
			
	char *groups = strdup(" AND EXISTS (SELECT 1 FROM customergroups g, customerassignments a "
				"WHERE a.customerid = %ownerid "
				"AND g.id = a.customergroupid "
				"AND (%groups))"
				);
	
	char *groupnames = strdup(c->customergroups);
	char *groupname = strdup(groupnames);
	char *groupsql = strdup("");

	char *egroups = strdup(" AND NOT EXISTS (SELECT 1 FROM customergroups g, customerassignments a "
				"WHERE a.customerid = %ownerid "
				"AND g.id = a.customergroupid "
				"AND (%egroups))"
				);
	
	char *egroupnames = strdup(c->excluded_customergroups);
	char *egroupname = strdup(egroupnames);
	char *egroupsql = strdup("");

	while( x>1 )
	{
    		x = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )
		{
			netsql = realloc(netsql, sizeof(char *) * (strlen(netsql) + strlen(netname) + 30));
			if(strlen(netsql))
				strcat(netsql, " OR UPPER(net.name) = UPPER('");
			else
				strcat(netsql, "UPPER(net.name) = UPPER('");
			
			strcat(netsql, netname);
			strcat(netsql, "')");
		}
	}
	free(netname); free(netnames);
	
	if(strlen(netsql))
	{
		g->str_replace(&nets, "%nets", netsql);
		g->str_replace(&nets_cust, "%nets", netsql);
	}
	
	while( o>1 )
	{
    		o = sscanf(enetnames, "%s %[._a-zA-Z0-9- ]", enetname, enetnames);

		if( strlen(enetname) )
		{
			enetsql = realloc(enetsql, sizeof(char *) * (strlen(enetsql) + strlen(enetname) + 30));
			if(strlen(enetsql))
				strcat(enetsql, " OR UPPER(net.name) = UPPER('");
			else
				strcat(enetsql, "UPPER(net.name) = UPPER('");
			
			strcat(enetsql, enetname);
			strcat(enetsql, "')");
		}
	}
	free(enetname); free(enetnames);
	
	if(strlen(enetsql))
	{
		g->str_replace(&enets, "%enets", enetsql);
		g->str_replace(&enets_cust, "%enets", enetsql);
	}

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

	if(c->disable_suspended)
		suspended = " AND suspended != 1 "
		    		"AND NOT EXISTS (SELECT 1 FROM assignments "
		            		"WHERE customerid = a.customerid "
				        "AND (datefrom <= %NOW% OR datefrom = 0) "
					"AND (dateto >= %NOW% OR dateto = 0) "
					"AND tariffid IS NULL AND liabilityid IS NULL "
				")";   
	else
		suspended = "";

	// current date
	t = time(&t);
	wsk = localtime(&t);
	strftime(time_fmt, tmax, fmt, wsk);

	if(*c->warning)
		g->str_replace(&c->warning, "%time", time_fmt);
	if(*c->expwarning)
		g->str_replace(&c->expwarning, "%time", time_fmt);

        // limit option as percentage value
	if(g->str_replace(&c->limit, "%", ""))
	{
	        plimit = atoi(c->limit);
	        plimit = (plimit < 0 ? plimit*-1 : plimit);
	}
	else
	{
	        limit = atoi(c->limit);
//	        limit = (limit < 0 ? limit*-1 : limit);
	}

	// nodes without tariffs (or with expired assignments)
	if(c->nodeassignments)
	{
		query = strdup(
			"SELECT n.id, n.ownerid FROM vnodes n "
        		"WHERE n.ownerid IS NOT NULL AND n.access = 1 "
	                    	"AND NOT EXISTS "
				"(SELECT 1 FROM nodeassignments, assignments a "
					"WHERE nodeid = n.id AND assignmentid = a.id "
						"AND (datefrom <= %NOW% OR datefrom = 0) "
						"AND (dateto >= %NOW% OR dateto = 0) "
						"AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL) "
						"%suspended"
				")"
				"%groups%egroups%nets%enets"
		);

    		g->str_replace(&query, "%groups", strlen(groupsql) ? groups : "");
    		g->str_replace(&query, "%egroups", strlen(egroupsql) ? egroups : "");
		g->str_replace(&query, "%nets", strlen(netsql) ? nets : "");	
		g->str_replace(&query, "%enets", strlen(enetsql) ? enets : "");	
    		g->str_replace(&query, "%suspended", suspended);
    		g->str_replace(&query, "%ownerid", "n.ownerid");
	
		res = g->db->pquery(g->db->conn, query);

		for(i=0; i<g->db->nrows(res); i++) 
		{
			char *nodeid = g->db->get_data(res,i,"id");
			char *ownerid = g->db->get_data(res,i,"ownerid");
		
			n = g->db->pexec(g->db->conn, "UPDATE nodes SET access = 0 WHERE id = ?", nodeid);

			execn = 1;
			
			if(*c->expwarning && n)
			{
				u = g->db->pexec(g->db->conn, "UPDATE customers SET message = '?' WHERE id = ?", c->expwarning, ownerid);
				execu = 1;
			}
		}	
		g->db->free(&res);

		free(query);
	}
	else if(c->customerassignments)
	{
		// customers without tariffs (or with expired assignments)
		query = strdup(
			"SELECT DISTINCT c.id FROM customers c, vnodes n "
			"WHERE c.id = n.ownerid "
				"AND deleted = 0 "
				"AND access = 1 "
				"AND NOT EXISTS (SELECT 1 FROM assignments a "
					"WHERE a.customerid = c.id "
						"AND (datefrom <= %NOW% OR datefrom = 0) "
						"AND (dateto >= %NOW% OR dateto = 0) "
						"AND (tariffid IS NOT NULL OR liabilityid IS NOT NULL) "
						"%suspended"
					")"
				"%groups%egroups%nets%enets"
		);

    		g->str_replace(&query, "%groups", strlen(groupsql) ? groups : "");
    		g->str_replace(&query, "%egroups", strlen(egroupsql) ? egroups : "");
		g->str_replace(&query, "%nets", strlen(netsql) ? nets : "");	
		g->str_replace(&query, "%enets", strlen(enetsql) ? enets : "");	
    		g->str_replace(&query, "%suspended", suspended);
    		g->str_replace(&query, "%ownerid", "c.id");

		res = g->db->pquery(g->db->conn, query);

		for(i=0; i<g->db->nrows(res); i++) 
		{
			char *customerid = g->db->get_data(res,i,"id");

			n = g->db->pexec(g->db->conn, "UPDATE nodes SET access = 0 WHERE ownerid = ?", customerid);

			execn = 1;
			
			if(*c->expwarning && n)
			{
				u = g->db->pexec(g->db->conn, "UPDATE customers SET message = '?' WHERE id = ?", c->expwarning, customerid);
				execu = 1;
			}
		}	
		g->db->free(&res);

		free(query);
	}

	if(c->checkinvoices)
	{
		// not payed invoices
		query = strdup( 
			"SELECT DISTINCT d.customerid AS id "
			"FROM documents d "
			"JOIN customers c ON (d.customerid = c.id) "
			"WHERE d.type = 1 "
				"AND d.closed = 0 "
				"AND d.cdate + d.paytime * 86400 + 86400 * ? < %NOW% "
				"AND c.deleted = 0 "
				"AND c.cutoffstop < %NOW%"
				"%groups%egroups%nets%enets"
		);

    		g->str_replace(&query, "%groups", strlen(groupsql) ? groups : "");
    		g->str_replace(&query, "%egroups", strlen(egroupsql) ? egroups : "");
		g->str_replace(&query, "%nets", strlen(netsql) ? nets_cust : "");	
		g->str_replace(&query, "%enets", strlen(enetsql) ? enets_cust : "");	
    		g->str_replace(&query, "%ownerid", "d.customerid");

		res = g->db->pquery(g->db->conn, query, itoa(c->deadline)); 
	
		for(i=0; i<g->db->nrows(res); i++) 
		{
			char *customerid = g->db->get_data(res,i,"id");
		
			if(c->warn_only)
				n = g->db->pexec(g->db->conn, "UPDATE nodes SET warning = 1 WHERE ownerid = ? AND warning = 0", customerid);
			else if(c->nodegroup_only)
				n = g->db->pexec(g->db->conn, "INSERT INTO nodegroupassignments (nodegroupid, nodeid) "
			                "SELECT ?, n.id "
					"FROM vnodes n "
					"WHERE n.ownerid = ? "
					"AND NOT EXISTS ( "
						"SELECT 1 FROM nodegroupassignments na "
						"WHERE na.nodeid = n.id AND na.nodegroupid = ?)",
					group, customerid, group	
					);
			else 
				n = g->db->pexec(g->db->conn, "UPDATE nodes SET access = 0 ? WHERE ownerid = ? AND access = 1", (*c->warning ? ", warning = 1" : ""), customerid);

			execn = n ? 1 : execn;
			
			if(*c->warning && !c->nodegroup_only && n)
			{
				u = g->db->pexec(g->db->conn, "UPDATE customers SET message = '?' WHERE id = ?", c->warning, customerid);
				execu = u ? 1 : execu;
			}
		}	
		g->db->free(&res);

		free(query);
	}
	//Connect devices where no debt
	if(c->connect)
	            {
		    n = g->db->pexec(g->db->conn, "UPDATE nodes n1,"
			"(SELECT n.id FROM vnodes n LEFT JOIN nodeassignments ON n.id = nodeassignments.nodeid "
			"LEFT JOIN assignments ON nodeassignments.assignmentid=assignments.id "
			"WHERE (assignments.dateto > %NOW% or assignments.dateto='0') "
			"AND assignments.datefrom < %NOW% "
			"AND assignments.suspended = 0 "
			"AND access = 0 AND (SELECT SUM(value) FROM cash WHERE customerid = n.ownerid) >= 0) "
			"AS n2 SET n1.access=1, n1.warning=0 WHERE n1.id = n2.id");
		    execn = 1;
	            }

	// debtors
	if(plimit)
		query = strdup(
			"SELECT c.id, ca.balance "
			"FROM customers c "
			// balance
			"JOIN (SELECT SUM(value) AS balance, customerid "
				"FROM cash "
				"GROUP BY customerid "
				"HAVING SUM(value) < 0 "
			") ca ON (c.id = ca.customerid) "
			// monthly assignments sum
			"JOIN (SELECT "
			    "SUM(t.value * (CASE t.period "
			        "WHEN " _YEARLY_ " THEN 1/12.0 "
			        "WHEN " _HALFYEARLY_ " THEN 1/6.0 "
			        "WHEN " _QUARTERLY_ " THEN 1/3.0 "
			        "ELSE 1 END)) AS tariff, a.customerid "
				"FROM assignments a "
				"JOIN tariffs t ON (a.tariffid = t.id) "
				"WHERE a.period = 3 "
					"AND a.suspended = 0 "
					"AND (a.datefrom <= %NOW% OR a.datefrom = 0) "
					"AND (a.dateto >= %NOW% OR a.dateto = 0) "
				"GROUP BY a.customerid "
			") t ON (t.customerid = c.id) "
			"WHERE c.deleted = 0 "
				"AND c.cutoffstop < %NOW% "
				"AND balance * -1 > (?/100 * tariff) "
				"%groups%egroups%nets%enets" 
		);
	else
		query = strdup(
			"SELECT c.id, SUM(cash.value) AS balance "
			"FROM customers c "
			"JOIN cash ON (c.id = cash.customerid) "
			"WHERE c.deleted = 0 "
				"AND c.cutoffstop < %NOW% "
				"%groups%egroups%nets%enets" 
			" GROUP BY c.id "
			"HAVING SUM(cash.value) < ? "
		);

	g->str_replace(&query, "%groups", strlen(groupsql) ? groups : "");
	g->str_replace(&query, "%egroups", strlen(egroupsql) ? egroups : "");
	g->str_replace(&query, "%nets", strlen(netsql) ? nets_cust : "");	
	g->str_replace(&query, "%enets", strlen(enetsql) ? enets_cust : "");	
	g->str_replace(&query, "%ownerid", "c.id");

	if(plimit)
		res = g->db->pquery(g->db->conn, query, itoa(plimit)); 
	else
		res = g->db->pquery(g->db->conn, query, itoa(limit)); 
	
	for(i=0; i<g->db->nrows(res); i++) 
	{
		char *customerid = g->db->get_data(res,i,"id");
		
		if(c->warn_only)
			n = g->db->pexec(g->db->conn, "UPDATE nodes SET warning = 1 WHERE ownerid = ? AND warning = 0", customerid);
		else if(c->nodegroup_only)
			n = g->db->pexec(g->db->conn, "INSERT INTO nodegroupassignments (nodegroupid, nodeid) "
			                "SELECT ?, n.id "
					"FROM vnodes n "
					"WHERE n.ownerid = ? "
					"AND NOT EXISTS ( "
						"SELECT 1 FROM nodegroupassignments na "
						"WHERE na.nodeid = n.id AND na.nodegroupid = ?)",
					group, customerid, group	
					);
		else 
			n = g->db->pexec(g->db->conn, "UPDATE nodes SET access = 0 ? WHERE ownerid = ? AND access = 1", (*c->warning ? ", warning = 1" : ""), customerid);

		execn = n ? 1 : execn;
			
		if(*c->warning && !c->nodegroup_only && n)
		{
			char *warning = strdup(c->warning);
			char *balance = g->db->get_data(res,i,"balance");

			g->str_replace(&warning, "%B", balance);
			g->str_replace(&warning, "%b", balance[0] == '-' ? balance+1 : balance);

			u = g->db->pexec(g->db->conn, "UPDATE customers SET message = '?' WHERE id = ?", warning, customerid);
			execu = u ? 1 : execu;

			free(warning);
		}
	}	
	g->db->free(&res);

	free(query);

	if(execn || execu)
	{
		system(c->command);
	}
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/cutoff] reloaded", c->base.instance);
#endif
	free(group);
	free(groups); free(egroups);
	free(groupsql);	free(egroupsql);
	free(nets); free(enets);
	free(netsql); free(enetsql);
	free(nets_cust); free(enets_cust);
	
	free(c->limit);
	free(c->warning);
	free(c->expwarning);
	free(c->command);
	free(c->customergroups);
	free(c->excluded_customergroups);
	free(c->networks);
	free(c->excluded_networks);
}

struct cutoff_module * init(GLOBAL *g, MODULE *m)
{
	struct cutoff_module *c;
	char *nodegroup;
	QueryHandle *res;
	
	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	c = (struct cutoff_module *) realloc(m, sizeof(struct cutoff_module));
	
	c->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	c->limit = strdup(g->config_getstring(c->base.ini, c->base.instance, "limit", "0"));
	c->warning = strdup(g->config_getstring(c->base.ini, c->base.instance, "warning", "Blocked automatically due to payment deadline override at %time"));
	c->command = strdup(g->config_getstring(c->base.ini, c->base.instance, "command", ""));
	c->warn_only = g->config_getbool(c->base.ini, c->base.instance, "warnings_only", 0);
	c->expwarning = strdup(g->config_getstring(c->base.ini, c->base.instance, "expired_warning", "Blocked automatically due to tariff(s) expiration at %time"));
	c->nodeassignments = g->config_getbool(c->base.ini, c->base.instance, "use_nodeassignments", 0);
	c->customerassignments = g->config_getbool(c->base.ini, c->base.instance, "use_customerassignments", 1);
	c->disable_suspended = g->config_getbool(c->base.ini, c->base.instance, "disable_suspended", 0);

	c->checkinvoices = g->config_getbool(c->base.ini, c->base.instance, "check_invoices", 0);
	c->deadline = g->config_getint(c->base.ini, c->base.instance, "deadline", 0);

	c->customergroups = strdup(g->config_getstring(c->base.ini, c->base.instance, "customergroups", ""));
	c->excluded_customergroups = strdup(g->config_getstring(c->base.ini, c->base.instance, "excluded_customergroups", ""));
	c->networks = strdup(g->config_getstring(c->base.ini, c->base.instance, "networks", ""));
	c->excluded_networks = strdup(g->config_getstring(c->base.ini, c->base.instance, "excluded_networks", ""));
	c->connect = g->config_getbool(c->base.ini, c->base.instance, "connect", 0);

	c->nodegroup_only = 0;
	nodegroup = g->config_getstring(c->base.ini, c->base.instance, "setnodegroup_only", "");
	if(strlen(nodegroup))
	{
		res = g->db->pquery(g->db->conn, "SELECT id FROM nodegroups WHERE UPPER(name) = UPPER('?')", nodegroup);
		if(g->db->nrows(res))
			c->nodegroup_only = atoi(g->db->get_data(res,0,"id"));
	}
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/cutoff] initialized", c->base.instance);
#endif	
	return(c);
}
