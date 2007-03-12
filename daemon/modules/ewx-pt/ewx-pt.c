/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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
#include <netinet/in.h>
#include <arpa/inet.h>

#include "lmsd.h"
#include "ewx-pt.h"

char * itoa(int i)
{
        static char string[15];
	sprintf(string, "%d", i);
	return string;
}

void reload(GLOBAL *g, struct ewx_module *ewx)
{
	struct snmp_session 	session, *sh;
	struct snmp_pdu 	*pdu, *response;

	int 	status, i, j, n=2, nc=0;
	char 	*errstr;

	QueryHandle *res;
	
        struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(ewx->networks);
	char *netname = strdup(netnames);

	while( n>1 )
	{
        	n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);
	        if(strlen(netname))
		{
			res = g->db_pquery(g->conn, "SELECT address, INET_ATON(mask) AS mask FROM networks WHERE UPPER(name)=UPPER('?')", netname);
			if(g->db_nrows(res))
			{
				nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].address = inet_addr(g->db_get_data(res,0,"address"));
				nets[nc].mask = inet_addr(g->db_get_data(res,0,"mask"));
				nc++;
			}
			g->db_free(&res);
		}
	}
	free(netname); free(netnames);

	// Reading nodes and ewx_pt_config tables
	// NOTE: to re-create terminator's configuration do DELETE FROM ewx_pt_config;

				    // first query: existing nodes with current config for insert, update or delete (if access=0)
	res = g->db_query(g->conn, "SELECT n.id, n.mac, INET_NTOA(n.ipaddr) AS ip, LOWER(n.name) AS name, n.passwd, n.chkmac, "
					    "e.nodeid, e.mac AS oldmac, INET_NTOA(e.ipaddr) AS oldip, "
					    "e.name AS oldname, e.passwd AS oldpasswd, n.ipaddr, n.access "
				    "FROM nodes n "
				    "LEFT JOIN ewx_pt_config e ON (n.id = e.nodeid) "
				    // skip disabled nodes when aren't in ewx_pt_config
				    "WHERE NOT (e.nodeid IS NULL AND n.access = 0) "
				    // UNION ALL is quicker than just UNION
				    "UNION ALL "
				    // second query: nodes existing in config 
				    // but not existing in nodes table (for delete)
				    "SELECT n.id, n.mac, INET_NTOA(n.ipaddr) AS ip, LOWER(n.name) AS name, n.passwd, n.chkmac, "
					    "e.nodeid, e.mac AS oldmac, INET_NTOA(e.ipaddr) AS oldip, "
					    "e.name AS oldname, e.passwd AS oldpasswd, e.ipaddr, n.access "    
				    "FROM ewx_pt_config e "
				    "LEFT JOIN nodes n ON (n.id = e.nodeid) "
				    "WHERE n.id IS NULL"
				    );
	
	if(!g->db_nrows(res))
	{
	        syslog(LOG_ERR, "[%s/ewx-pt] Unable to read database or nodes table is empty", ewx->base.instance);
		return;
	}

	// Initialize the SNMP library (reading lmsd-ewx-pt.conf and snmp.conf)
	init_snmp("lmsd-ewx-pt");

	// Initialize a "session" that defines who we're going to talk to
	snmp_sess_init(&session); // set up defaults 
	session.version 	= SNMP_VERSION_2c; 	// SNMP version number
	session.peername 	= ewx->host;	 	// destination IP/name
	session.remote_port	= ewx->port; 		// destination port
//	session.timeout 	= 1000000; 		// timeout in microsec.
	session.community 	= (unsigned char *) ewx->community; 	// community name
	session.community_len 	= strlen(ewx->community);

	// Open the session
	sh = snmp_open(&session);

	if(!sh)
	{
	        snmp_error(&session, NULL, NULL, &errstr);
	        syslog(LOG_ERR, "[%s/ewx-pt] SNMP ERROR: %s", ewx->base.instance, errstr);
		free(errstr);
		return;
	}

	// Nodes main loop
	for(i=0; i<g->db_nrows(res); i++)
        {
		unsigned long inet = inet_addr(g->db_get_data(res,i,"ipaddr"));
		
		// Networks test
		if(nc)
		{
			for(j=0; j<nc; j++)
		                if(nets[j].address == (inet & nets[j].mask))
		                        break;
			if(j == nc)
				continue;
		}
		
        	char *id = g->db_get_data(res,i,"id");
        	char *ip = g->db_get_data(res,i,"ip");
        	char *mac = g->db_get_data(res,i,"mac");
        	char *name = g->db_get_data(res,i,"name");
        	char *passwd = g->db_get_data(res,i,"passwd");
        	char *nodeid = g->db_get_data(res,i,"nodeid");
        	char *oldip = g->db_get_data(res,i,"oldip");
        	char *oldmac = g->db_get_data(res,i,"oldmac");
        	char *oldname = g->db_get_data(res,i,"oldname");
		char *oldpasswd = g->db_get_data(res,i,"oldpasswd");
        	int chkmac = atoi(g->db_get_data(res,i,"chkmac"));
        	int access = atoi(g->db_get_data(res,i,"access"));

		int n_id = atoi(id);
		int n_nodeid = atoi(nodeid);
		int node = n_nodeid ? n_nodeid : n_id;
		char *nodename = strlen(name) ? name : oldname;
		char *type;

		// Setting OIDs
		UserStatus[PT_OID_LEN-1] = node + ewx->offset;
		UserNo[PT_OID_LEN-1] = node + ewx->offset;
		UserName[PT_OID_LEN-1] = node + ewx->offset;
		UserPassword[PT_OID_LEN-1] = node + ewx->offset;
		UserIpAddr[PT_OID_LEN-1] = node + ewx->offset;
		UserAllowedMacAddr[PT_OID_LEN-1] = node + ewx->offset;

		// Create the PDU 
		pdu = snmp_pdu_create(SNMP_MSG_SET);

		// Working...
		if(!n_nodeid && access)
		{
			// new node
			type = "add";
			
			snmp_add_var(pdu, UserName, PT_OID_LEN, 's', name);
			snmp_add_var(pdu, UserPassword, PT_OID_LEN, 's', passwd);
			snmp_add_var(pdu, UserIpAddr, PT_OID_LEN, 's', ip);
			if(chkmac)
				snmp_add_var(pdu, UserAllowedMacAddr, PT_OID_LEN, 's', mac);

			snmp_add_var(pdu, UserStatus, PT_OID_LEN, 'i', CREATEANDGO);
		}
		else if(!access) // || !n_id
		{
			// deleted node
			type = "delete";

			snmp_add_var(pdu, UserStatus, PT_OID_LEN, 'i', DESTROY);
		}
		else
		{
			// existing node (something has changed?)
			int cname = (strcmp(name,oldname)!=0);
			int cip = (strcmp(ip,oldip)!=0);
			int cpasswd = (strcmp(passwd,oldpasswd)!=0);
			int cmac = (chkmac && (strcmp(oldmac, DUMMY_MAC)==0 || strcmp(oldmac, mac)!=0));
			int dmac = (!chkmac && strcmp(oldmac, DUMMY_MAC)!=0);
			
			type = "update";

			if(!cname && !cip && !cpasswd && !cmac && !dmac)
			{
				// we have nothing to update
				snmp_free_pdu(pdu);
				continue;
			}

			// NOTINSERVICE we must send in separate packet
			snmp_add_var(pdu, UserStatus, PT_OID_LEN, 'i', NOTINSERVICE);

			// Send the Request out
			status = snmp_synch_response(sh, pdu, &response);

			// Process the response
			if(status != STAT_SUCCESS || response->errstat != SNMP_ERR_NOERROR)
			{
				if(status == STAT_SUCCESS)
	    				syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot %s node %s (%05d): %s", ewx->base.instance, type, nodename, node, snmp_errstring(response->errstat));
    				else
    				{
					snmp_error(sh, NULL, NULL, &errstr);
	    				syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot %s node %s (%05d): %s", ewx->base.instance, type, nodename, node, errstr);
					free(errstr);
				}

				// Clean up
				if(response)
    					snmp_free_pdu(response);
			
				continue;
			}
			
			// Create the PDU again
			pdu = snmp_pdu_create(SNMP_MSG_SET);

			if(cname)
				snmp_add_var(pdu, UserName, PT_OID_LEN, 's', name);
			if(cpasswd)
				snmp_add_var(pdu, UserPassword, PT_OID_LEN, 's', passwd);
			if(cip)
				snmp_add_var(pdu, UserIpAddr, PT_OID_LEN, 's', ip);
			if(cmac)
				snmp_add_var(pdu, UserAllowedMacAddr, PT_OID_LEN, 's', mac);
			else if(dmac)
				snmp_add_var(pdu, UserAllowedMacAddr, PT_OID_LEN, 's', DUMMY_MAC);

			snmp_add_var(pdu, UserStatus, PT_OID_LEN, 'i', ACTIVE);
		}

		// Continue loop iteration if we've got nothing to do
		if(!pdu->variables) 
		{
			snmp_free_pdu(pdu);
			continue;
		}
		
		// Send the Request out
		status = snmp_synch_response(sh, pdu, &response);

		// Process the response
		if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
		{
//			struct variable_list 	*vars;
//    			for(vars = response->variables; vars; vars = vars->next_variable)
//    				print_variable(vars->name, vars->name_length, vars);

			if(!n_nodeid && access)
			{
				// insert config
    				g->db_pexec(g->conn, "INSERT INTO ewx_pt_config (nodeid, name, passwd, ipaddr, mac) "
						    "VALUES (?, '?', '?', INET_ATON('?'), '?')",
						    id, name, passwd, ip, (chkmac ? mac : DUMMY_MAC));
#ifdef DEBUG1
				syslog(LOG_INFO, "DEBUG: [%s/ewx-pt] Added node %s (%05d)", ewx->base.instance, nodename, node);
#endif
			}
			else if(!access)
			{
				// delete config
				g->db_pexec(g->conn, "DELETE FROM ewx_pt_config WHERE nodeid = ?", nodeid);
#ifdef DEBUG1
				syslog(LOG_INFO, "DEBUG: [%s/ewx-pt] Deleted node %s (%05d)", ewx->base.instance, nodename, node);
#endif
			}
			else
			{
				// update config
				g->db_pexec(g->conn, "UPDATE ewx_pt_config SET name = '?', passwd = '?', "
						    "ipaddr = INET_ATON('?'), mac = '?' WHERE nodeid = ?",
						    name, passwd, ip, (chkmac ? mac : DUMMY_MAC), id);
#ifdef DEBUG1
				syslog(LOG_INFO, "DEBUG: [%s/ewx-pt] Updated node %s (%05d)", ewx->base.instance, nodename, node);
#endif
			}
		} 
		else // failure
		{
			if(status == STAT_SUCCESS)
	    			syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot %s node %s (%05d): %s", ewx->base.instance, type, nodename, node, snmp_errstring(response->errstat));
    			else
    			{
				snmp_error(sh, NULL, NULL, &errstr);
	    			syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot %s node %s (%05d): %s", ewx->base.instance, type, nodename, node, errstr);
				free(errstr);
			}
		}

		// Clean up
		if(response)
    			snmp_free_pdu(response);
	}

	// Saving users table 
	pdu = snmp_pdu_create(SNMP_MSG_SET);
	snmp_add_var(pdu, UsersTableSave, OID_LENGTH(UsersTableSave), 'i', TABLESAVE);
	status = snmp_synch_response(sh, pdu, &response);

	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-pt] Users table saved", ewx->base.instance);
#endif
	}
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot save users table: %s", ewx->base.instance, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot save users table: %s", ewx->base.instance, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);
	
	snmp_close(sh);

	g->db_free(&res);
	free(nets);
	
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/ewx-pt] reloaded", ewx->base.instance);
#endif
	free(ewx->community);
	free(ewx->host);
	free(ewx->networks);
}

struct ewx_module * init(GLOBAL *g, MODULE *m)
{
	struct ewx_module *ewx;
	
	if(g->api_version != APIVERSION) 
	{
	        return (NULL);
	}
	
	ewx = (struct ewx_module *) realloc(m, sizeof(struct ewx_module));
	
	ewx->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	ewx->community = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "community", "private"));
	ewx->host = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "snmp_host", ""));
	ewx->port = g->config_getint(ewx->base.ini, ewx->base.instance, "snmp_port", 161);
	ewx->networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "networks", ""));
	ewx->offset = g->config_getint(ewx->base.ini, ewx->base.instance, "offset", 0);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/ewx-pt] initialized", ewx->base.instance);
#endif	
	return(ewx);
}
