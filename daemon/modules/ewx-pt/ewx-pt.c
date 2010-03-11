/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2010 LMS Developers
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

	int 	status, i, j, n=2;
	int	nc=0, anc=0, mnc=0, inc=0;
	char 	*netnames;
	char	*netname;
	char 	*errstr;
	char	*query;

	QueryHandle *res;
	
        struct net *nets = (struct net *) malloc(sizeof(struct net));
        struct net *all_nets = (struct net *) malloc(sizeof(struct net));
        struct net *mac_nets = (struct net *) malloc(sizeof(struct net));
        struct net *ip_nets = (struct net *) malloc(sizeof(struct net));

	// get all networks params
        res = g->db_pquery(g->conn, "SELECT UPPER(name) AS name, address, INET_ATON(mask) AS mask, interface FROM networks");
	
	for(anc=0; anc<g->db_nrows(res); anc++)
	{
	        all_nets = (struct net*) realloc(all_nets, (sizeof(struct net) * (anc+1)));
		all_nets[anc].name = strdup(g->db_get_data(res, anc, "name"));
		all_nets[anc].address = inet_addr(g->db_get_data(res, anc, "address"));
	        all_nets[anc].mask = inet_addr(g->db_get_data(res, anc, "mask"));
	}
	g->db_free(&res);
																												 
	netnames = strdup(ewx->networks);
	netname = strdup(netnames);
	// get networks for filter if any specified in 'networks' option
	while( n>1 )
	{
        	n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);
	        if(strlen(netname))
		{
			for(i=0; i<anc; i++)
	            		if(strcmp(all_nets[i].name, g->str_upc(netname))==0)
	                    		break;

			if(i != anc)
			{
				nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].address = all_nets[i].address;
				nets[nc].mask = all_nets[i].mask;
				nets[nc].name = strdup(all_nets[i].name);
				nc++;
			}
		}
	}
	free(netname); free(netnames);

	n = 2;
	netnames = strdup(ewx->dummy_mac_networks);
	netname = strdup(netnames);
	// get networks for filter if any specified in 'dummy_mac_networks' option
	while( n>1 )
	{
        	n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);
	        if(strlen(netname))
		{
			for(i=0; i<anc; i++)
	            		if(strcmp(all_nets[i].name, g->str_upc(netname))==0)
	                    		break;
		
			if(i != anc)
			{
				mac_nets = (struct net *) realloc(mac_nets, (sizeof(struct net) * (mnc+1)));
				mac_nets[mnc].address = all_nets[i].address;
				mac_nets[mnc].mask = all_nets[i].mask;
				mac_nets[mnc].name = strdup(all_nets[i].name);
				mnc++;
			}
		}
	}
	free(netname); free(netnames);

	n = 2;
	netnames = strdup(ewx->dummy_ip_networks);
	netname = strdup(netnames);
	// get networks for filter if any specified in 'dummy_ip_networks' option
	while( n>1 )
	{
        	n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);
	        if(strlen(netname))
		{
			for(i=0; i<anc; i++)
	            		if(strcmp(all_nets[i].name, g->str_upc(netname))==0)
	                    		break;
		
			if(i != anc)
			{
				// same networks can't be included in both dummy_* options
				for(j=0; j<mnc; j++)
	            			if(mac_nets[j].address == all_nets[i].address)
	                    			break;

				if(j != mnc)
				{
	    				syslog(LOG_ERR, "[%s/ewx-stm] Network %s already included in 'dummy_mac_networks' option. Skipping.", all_nets[i].name, ewx->base.instance);
					continue;
				}

				ip_nets = (struct net *) realloc(ip_nets, (sizeof(struct net) * (inc+1)));
				ip_nets[inc].address = all_nets[i].address;
				ip_nets[inc].mask = all_nets[i].mask;
				ip_nets[inc].name = strdup(all_nets[i].name);
				inc++;
			}
		}
	}
	free(netname); free(netnames);

	// Reading nodes and ewx_pt_config tables
	// NOTE: to re-create terminator's configuration do DELETE FROM ewx_pt_config;

				    // first query: existing nodes with current config for insert, update or delete (if access=0)
	query = strdup("SELECT n.id, n.mac, INET_NTOA(n.ipaddr) AS ip, LOWER(n.name) AS name, n.passwd, n.chkmac, "
			"e.nodeid, e.mac AS oldmac, INET_NTOA(e.ipaddr) AS oldip, "
			"e.name AS oldname, e.passwd AS oldpasswd, n.ipaddr, n.access "
		"FROM nodes n "
		"LEFT JOIN ewx_pt_config e ON (n.id = e.nodeid) "
		// skip disabled nodes when aren't in ewx_pt_config
		"%disabled"
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
	
	g->str_replace(&query, "%disabled", ewx->skip_disabled ? "WHERE NOT (e.nodeid IS NULL AND n.access = 0) " : "");
	
	res = g->db_query(g->conn, query);

	free(query);
	
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
		if(nc && inet)
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
        	int access = ewx->skip_disabled ? atoi(g->db_get_data(res,i,"access")) : 1;

		int n_id = atoi(id);
		int n_nodeid = atoi(nodeid);
		int node = n_nodeid ? n_nodeid : n_id;
		char *nodename = strlen(name) ? name : oldname;
		char *type;

        	int dummy_ip = 0;
		int dummy_mac = 0;

		// Networks test for dummy_mac
		if(mnc && inet)
		{	
			for(n=0; n<mnc; n++)
	            		if(mac_nets[n].address == (inet & mac_nets[n].mask))
	            			break;
		
			if(n != mnc) dummy_mac = 1;
		}

		if(!atoi(g->db_get_data(res,i,"chkmac")))
                {
		        dummy_mac = 1;
		}

		// Networks test for dummy_ip
		if(inc && inet && !dummy_mac)
		{	
			for(n=0; n<inc; n++)
	        		if(ip_nets[n].address == (inet & ip_nets[n].mask))
	            			break;
		
			if(n != inc) dummy_ip = 1;
		}

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
			if(!dummy_ip)
				snmp_add_var(pdu, UserIpAddr, PT_OID_LEN, 's', ip);
			if(!dummy_mac)
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
			int cpasswd = (strcmp(passwd,oldpasswd)!=0);
			int cip = 0, dip = 0, cmac = 0, dmac = 0;

                        if (!dummy_ip)
			        cip = (strcmp(oldip, ip)!=0);
			else
			        dip = (strcmp(oldip, DUMMY_IP)!=0);
			
			if (!dummy_mac)
			        cmac = (strcmp(oldmac, mac)!=0);
			else
			        dmac = (strcmp(oldmac, DUMMY_MAC)!=0);
			
			type = "update";

			if(!cname && !cip && !dip && !cpasswd && !cmac && !dmac)
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
			else if(dip)
				snmp_add_var(pdu, UserIpAddr, PT_OID_LEN, 's', DUMMY_IP);
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
						    id, name, passwd, 
						    (dummy_ip ? DUMMY_IP : ip), 
						    (dummy_mac ? DUMMY_MAC : mac)
						    );
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
						    name, passwd, 
						    (dummy_ip ? DUMMY_IP : ip), 
						    (dummy_mac ? DUMMY_MAC : mac), 
						    id);
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

        for(i=0;i<nc;i++)
	{
	        free(nets[i].name);
	}
	free(nets);

        for(i=0;i<anc;i++)
	{
	        free(all_nets[i].name);
	}
	free(all_nets);

	for(i=0;i<mnc;i++)
	{
	        free(mac_nets[i].name);
	}
	free(mac_nets);
	
	for(i=0;i<inc;i++)
	{
	        free(ip_nets[i].name);
	}
	free(ip_nets);
	
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/ewx-pt] reloaded", ewx->base.instance);
#endif
	free(ewx->community);
	free(ewx->host);
	free(ewx->networks);
	free(ewx->dummy_ip_networks);
	free(ewx->dummy_mac_networks);
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
	ewx->skip_disabled = g->config_getbool(ewx->base.ini, ewx->base.instance, "skip_disabled", 1);
	ewx->dummy_mac_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "dummy_mac_networks", ""));
	ewx->dummy_ip_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "dummy_ip_networks", ""));
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/ewx-pt] initialized", ewx->base.instance);
#endif	
	return(ewx);
}
