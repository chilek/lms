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
#include <stdlib.h>
#include <syslog.h>
#include <string.h>
#include <netinet/in.h>
#include <arpa/inet.h>

#include "lmsd.h"
#include "ewx-pt.h"

int del_node(GLOBAL*, struct ewx_module*, struct snmp_session*, struct host*);
int add_node(GLOBAL*, struct ewx_module*, struct snmp_session*, struct host*);
int update_node(GLOBAL*, struct ewx_module*, struct snmp_session*, struct host*, struct host*);
int save_tables(GLOBAL*, struct ewx_module*, struct snmp_session*);

char * itoa(int i)
{
    static char string[15];
	sprintf(string, "%d", i);
	return string;
}

void reload(GLOBAL *g, struct ewx_module *ewx)
{
	struct snmp_session session, *sh;

	int savetables=0, i, j, n=2;
	int	nc=0, hc=0, anc=0, mnc=0, inc=0;
	char *netnames;
	char *netname;
	char *errstr;
	char *query, *noa;

	QueryHandle *res;

    struct net *nets = (struct net *) malloc(sizeof(struct net));
    struct net *all_nets = (struct net *) malloc(sizeof(struct net));
    struct net *mac_nets = (struct net *) malloc(sizeof(struct net));
    struct net *ip_nets = (struct net *) malloc(sizeof(struct net));
    struct host *hosts = (struct host *) malloc(sizeof(struct host));

	// get all networks params
    res = g->db->pquery(g->db->conn, "SELECT UPPER(name) AS name, address, INET_ATON(mask) AS mask, interface FROM networks");

	for(anc=0; anc<g->db->nrows(res); anc++)
	{
	        all_nets = (struct net*) realloc(all_nets, (sizeof(struct net) * (anc+1)));
		all_nets[anc].name = strdup(g->db->get_data(res, anc, "name"));
		all_nets[anc].address = inet_addr(g->db->get_data(res, anc, "address"));
	        all_nets[anc].mask = inet_addr(g->db->get_data(res, anc, "mask"));
	}
	g->db->free(&res);

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
	    				syslog(LOG_ERR, "[%s/ewx-pt] Network %s already included in 'dummy_mac_networks' option. Skipping.", all_nets[i].name, ewx->base.instance);
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

    // nodes existing in config
	res = g->db->query(g->db->conn, "SELECT id, nodeid, name, mac, passwd, ipaddr, INET_NTOA(ipaddr) AS ip FROM ewx_pt_config");

	for (hc=0; hc<g->db->nrows(res); hc++)
	{
	    hosts = (struct host*) realloc(hosts, (sizeof(struct host) * (hc+1)));
        hosts[hc].id     = atoi(g->db->get_data(res, hc, "nodeid"));
        hosts[hc].nodeid = atoi(g->db->get_data(res, hc, "id"));
		hosts[hc].name   = strdup(g->db->get_data(res, hc, "name"));
		hosts[hc].mac    = strdup(g->db->get_data(res, hc, "mac"));
		hosts[hc].passwd = strdup(g->db->get_data(res, hc, "passwd"));
	    hosts[hc].ip     = strdup(g->db->get_data(res, hc, "ip"));
	    hosts[hc].ipaddr = inet_addr(g->db->get_data(res, hc, "ipaddr"));
	    hosts[hc].status = UNKNOWN;
	}
	g->db->free(&res);

    // existing nodes with current config for insert, update or delete (if access=0)
	query = strdup("SELECT n.id, n.ipaddr, n.access, "
		"(SELECT m.mac FROM macs m WHERE m.nodeid = n.id ORDER BY m.id LIMIT 1) AS mac, "
		"INET_NTOA(n.ipaddr) AS ip, LOWER(n.name) AS name, n.passwd, n.chkmac "
		"FROM nodes n "
		"WHERE 1=1 "
		// skip disabled nodes when aren't in ewx_pt_config
		"%disabled"
		// skip nodes without active assignment
		"%noa");

    noa = "AND EXISTS (SELECT 1 "
        "FROM nodeassignments na "
        "JOIN assignments a ON (na.assignmentid = a.id) "
        "WHERE na.nodeid = n.id "
            "AND (a.datefrom <= %NOW% OR a.datefrom = 0) "
            "AND (a.dateto >= %NOW% OR a.dateto = 0)) ";

	g->str_replace(&query, "%disabled", ewx->skip_disabled ? "AND n.access = 1 " : "");
	g->str_replace(&query, "%noa", ewx->skip_noa ? noa : "");

	res = g->db->query(g->db->conn, query);

	free(query);

	if(!g->db->nrows(res))
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
	for (i=0; i<g->db->nrows(res); i++)
    {
		unsigned long inet = inet_addr(g->db->get_data(res,i,"ipaddr"));

		// Networks test
		if (nc && inet)
		{
			for (j=0; j<nc; j++)
		        if (nets[j].address == (inet & nets[j].mask))
		            break;
			if (j == nc)
				continue;
		}

		int found = -1;
        int dummy_ip = 0;
		int dummy_mac = 0;
		struct host h;

       	h.id = atoi(g->db->get_data(res,i,"id"));
       	h.ip = g->db->get_data(res,i,"ip");
       	h.mac = g->db->get_data(res,i,"mac");
        h.name = g->db->get_data(res,i,"name");
       	h.passwd = g->db->get_data(res,i,"passwd");
       	h.ipaddr = inet;

		// Networks test for dummy_mac
		if (mnc && inet)
		{
			for (n=0; n<mnc; n++)
	            if (mac_nets[n].address == (inet & mac_nets[n].mask))
	            	break;

			if (n != mnc) dummy_mac = 1;
		}

		if (!atoi(g->db->get_data(res,i,"chkmac")))
        {
		        dummy_mac = 1;
		}

		// Networks test for dummy_ip
		if (inc && inet && !dummy_mac)
		{
			for (n=0; n<inc; n++)
	            if (ip_nets[n].address == (inet & ip_nets[n].mask))
	            	break;

			if(n != inc) dummy_ip = 1;
		}

        // Set dummy IP/MAC
        if (dummy_ip) {
            h.ip = DUMMY_IP;
            h.ipaddr = inet_addr(DUMMY_IP);
        }
        if (dummy_mac) {
            h.mac = DUMMY_MAC;
        }

        // Let's find existing node entry
        for (n=0; n<hc; n++)
        {
            // Node with the same ID
            if (hosts[n].id == h.id) {
                found = n;
            }
            // Other node with matching credentials (name, ip, mac)
            else if (hosts[n].status == UNKNOWN) {
                if (!strcmp(hosts[n].name, h.name)
                ) {
                    del_node(g, ewx, sh, &hosts[n]);
                    savetables = 1;
                }
            }
        }

        if (found != -1) {
            if (h.ipaddr != hosts[found].ipaddr
                || strcmp(h.name, hosts[found].name) != 0
                || strcmp(h.passwd, hosts[found].passwd) != 0
                || strcmp(h.mac, hosts[found].mac) != 0
            ) {
                update_node(g, ewx, sh, &h, &hosts[found]);
                savetables = 1;
            }
            else
            	hosts[found].status = STATUS_OK;
        }
        else {
            add_node(g, ewx, sh, &h);
            savetables = 1;
        }
    }

    // Remove not found nodes
    for (i=0; i<hc; i++)
    {
        if (hosts[i].status == UNKNOWN) {
		unsigned long inet = hosts[i].ipaddr;

		// Networks test
		if (nc && inet)
		{
			for (j=0; j<nc; j++)
		        if (nets[j].address == (inet & nets[j].mask))
		            break;
			if (j == nc)
				continue;
		}

            del_node(g, ewx, sh, &hosts[i]);
            savetables = 1;
        }
    }

    // Confirm changes
    if (savetables)
        save_tables(g, ewx, sh);

	snmp_close(sh);

	g->db->free(&res);

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

	for(i=0;i<hc;i++)
	{
        free(hosts[i].name);
        free(hosts[i].mac);
        free(hosts[i].ip);
        free(hosts[i].passwd);
	}
	free(hosts);

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
	ewx->skip_noa = g->config_getbool(ewx->base.ini, ewx->base.instance, "skip_noa", 1);
	ewx->dummy_mac_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "dummy_mac_networks", ""));
	ewx->dummy_ip_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "dummy_ip_networks", ""));
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/ewx-pt] initialized", ewx->base.instance);
#endif
	return(ewx);
}

int del_node(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct host *ht)
{
    struct snmp_pdu *pdu, *response;
    char *errstr;
    int status, result = STATUS_ERROR;
    struct host h = *ht;

#ifdef LMS_SNMP_DEBUG
    printf("[DELETE NODE] %d\n", h.id);
#endif
    if (!sh) return result;

	// Setting OIDs
	UserStatus[PT_OID_LEN-1] = h.id + ewx->offset;

	// Create the PDU
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	snmp_add_var(pdu, UserStatus, PT_OID_LEN, 'i', DESTROY);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if (status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
		struct variable_list 	*vars;
		for (vars = response->variables; vars; vars = vars->next_variable)
			print_variable(vars->name, vars->name_length, vars);
#endif
		if (h.id)
			g->db->pexec(g->db->conn, "DELETE FROM ewx_pt_config WHERE nodeid = ?", itoa(h.id));
		else
			g->db->pexec(g->db->conn, "DELETE FROM ewx_pt_config WHERE nodeid IS NULL");
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-pt] Deleted node %s (%05d)", ewx->base.instance, h.name, h.id);
#endif
        (*ht).status = result = DELETED;
    }
	else // failure
	{
		if (status == STAT_SUCCESS)
			syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot delete node %s (%05d): %s", ewx->base.instance, h.name, h.id, snmp_errstring(response->errstat));
    	else {
		    snmp_error(sh, NULL, NULL, &errstr);
	    	syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot delete node %s (%05d): %s", ewx->base.instance, h.name, h.id, errstr);
			free(errstr);
		}
	}

	// Clean up
	if (response)
		snmp_free_pdu(response);

    return result;
}

int add_node(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct host *ht)
{
    struct snmp_pdu *pdu, *response;
    char *errstr;
    int status, result = STATUS_ERROR;
    struct host h = *ht;

#ifdef LMS_SNMP_DEBUG
    printf("[ADD NODE] %d %s/%s\n", h.id, h.ip, h.mac);
#endif
    if (!sh) return result;

	// Setting OIDs
	UserStatus[PT_OID_LEN-1] = h.id + ewx->offset;
	UserNo[PT_OID_LEN-1] = h.id + ewx->offset;
	UserName[PT_OID_LEN-1] = h.id + ewx->offset;
	UserPassword[PT_OID_LEN-1] = h.id + ewx->offset;
	UserIpAddr[PT_OID_LEN-1] = h.id + ewx->offset;
	UserAllowedMacAddr[PT_OID_LEN-1] = h.id + ewx->offset;

	// Create the PDU
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	snmp_add_var(pdu, UserName, PT_OID_LEN, 's', h.name);
	snmp_add_var(pdu, UserPassword, PT_OID_LEN, 's', h.passwd);
	if (strcmp(h.ip, DUMMY_IP) != 0)
		snmp_add_var(pdu, UserIpAddr, PT_OID_LEN, 's', h.ip);
	if (strcmp(h.mac, DUMMY_MAC) != 0)
		snmp_add_var(pdu, UserAllowedMacAddr, PT_OID_LEN, 's', h.mac);
	snmp_add_var(pdu, UserStatus, PT_OID_LEN, 'i', CREATEANDGO);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if (status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
		struct variable_list *vars;
		for (vars = response->variables; vars; vars = vars->next_variable)
			print_variable(vars->name, vars->name_length, vars);
#endif

		g->db->pexec(g->db->conn, "INSERT INTO ewx_pt_config (nodeid, name, passwd, ipaddr, mac) "
		    "VALUES (?, '?', '?', INET_ATON('?'), '?')",
		    itoa(h.id), h.name, h.passwd, h.ip, h.mac);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-pt] Added node %s (%05d)", ewx->base.instance, h.name, h.id);
#endif
        (*ht).status = result = STATUS_OK;
	}
	else // failure
	{
		if (status == STAT_SUCCESS)
   			syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot add node %s (%05d): %s", ewx->base.instance, h.name, h.id, snmp_errstring(response->errstat));
		else {
			snmp_error(sh, NULL, NULL, &errstr);
	    	syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot add node %s (%05d): %s", ewx->base.instance, h.name, h.id, errstr);
			free(errstr);
		}
	}

	// Clean up
	if (response)
    	snmp_free_pdu(response);

    return result;
}

int update_node(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct host *ht, struct host *old)
{
    struct snmp_pdu *pdu, *response;
    char *errstr;
    int status, result = STATUS_ERROR;
    struct host h = *ht;
    struct host o = *old;

#ifdef LMS_SNMP_DEBUG
    printf("[UPDATE NODE] %d %s/%s\n", h.id, h.ip, h.mac);
#endif
    if (!sh) return result;

	// Setting OIDs
	UserStatus[PT_OID_LEN-1] = h.id + ewx->offset;
	UserNo[PT_OID_LEN-1] = h.id + ewx->offset;
	UserName[PT_OID_LEN-1] = h.id + ewx->offset;
	UserPassword[PT_OID_LEN-1] = h.id + ewx->offset;
	UserIpAddr[PT_OID_LEN-1] = h.id + ewx->offset;
	UserAllowedMacAddr[PT_OID_LEN-1] = h.id + ewx->offset;

	// Create the PDU
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	// NOTINSERVICE we must send in separate packet
	snmp_add_var(pdu, UserStatus, PT_OID_LEN, 'i', NOTINSERVICE);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if (status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
		struct variable_list *vars;
		for (vars = response->variables; vars; vars = vars->next_variable)
			print_variable(vars->name, vars->name_length, vars);
#endif
	}
	else // failure
	{
		if (status == STAT_SUCCESS)
			syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot update node %s (%05d): %s", ewx->base.instance, h.name, h.id, snmp_errstring(response->errstat));
    	else {
			snmp_error(sh, NULL, NULL, &errstr);
	    	syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot update node %s (%05d): %s", ewx->base.instance, h.name, h.id, errstr);
			free(errstr);
		}

    	// Clean up
	    if (response)
   		    snmp_free_pdu(response);

        return result;
    }

	// Clean up
	if (response)
   		snmp_free_pdu(response);

	// Create the PDU again
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	if (strcmp(h.name, o.name) != 0) {
    	snmp_add_var(pdu, UserName, PT_OID_LEN, 's', h.name);
		free((*old).name);
		(*old).name = strdup(h.name);
    }
	if (strcmp(h.passwd, o.passwd) != 0) {
	    snmp_add_var(pdu, UserPassword, PT_OID_LEN, 's', h.passwd);
		free((*old).passwd);
		(*old).passwd = strdup(h.passwd);
    }
	if (strcmp(h.ip, o.ip) != 0) {
		snmp_add_var(pdu, UserIpAddr, PT_OID_LEN, 's', h.ip);
		free((*old).ip);
		(*old).ip = strdup(h.ip);
		(*old).ipaddr = h.ipaddr;
    }
	if (strcmp(h.mac, o.mac) != 0) {
		snmp_add_var(pdu, UserAllowedMacAddr, PT_OID_LEN, 's', h.mac);
		memcpy((*old).mac, h.mac, strlen(h.mac));
    }
	snmp_add_var(pdu, UserStatus, PT_OID_LEN, 'i', ACTIVE);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if (status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
		struct variable_list *vars;
		for (vars = response->variables; vars; vars = vars->next_variable)
			print_variable(vars->name, vars->name_length, vars);
#endif
		g->db->pexec(g->db->conn, "UPDATE ewx_pt_config SET "
		    "name = '?', passwd = '?', ipaddr = INET_ATON('?'), mac = '?' "
		    "WHERE nodeid = ?", h.name, h.passwd, h.ip, h.mac, itoa(h.id));
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-pt] Updated node %s (%05d)", ewx->base.instance, h.name, h.id);
#endif
        (*ht).status = (*old).status = result = STATUS_OK;
	}
	else // failure
	{
		if (status == STAT_SUCCESS)
   			syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot update node %s (%05d): %s", ewx->base.instance, h.name, h.id, snmp_errstring(response->errstat));
    	else {
			snmp_error(sh, NULL, NULL, &errstr);
	    	syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot update node %s (%05d): %s", ewx->base.instance, h.name, h.id, errstr);
			free(errstr);
	    }
    }

	// Clean up
	if (response)
		snmp_free_pdu(response);

    return result;
}

int save_tables(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh)
{
    struct snmp_pdu *pdu, *response;
    char *errstr;
    int status, result = STATUS_ERROR;

	// Saving users table
	pdu = snmp_pdu_create(SNMP_MSG_SET);
	snmp_add_var(pdu, UsersTableSave, OID_LENGTH(UsersTableSave), 'i', TABLESAVE);
	status = snmp_synch_response(sh, pdu, &response);

	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
		struct variable_list *vars;
		for (vars = response->variables; vars; vars = vars->next_variable)
			print_variable(vars->name, vars->name_length, vars);
#endif
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-pt] Users table saved", ewx->base.instance);
#endif
        result = STATUS_OK;
	}
	else // failure
	{
		if (status == STAT_SUCCESS)
    		syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot save users table: %s", ewx->base.instance, snmp_errstring(response->errstat));
		else {
			snmp_error(sh, NULL, NULL, &errstr);
    		syslog(LOG_ERR, "[%s/ewx-pt] ERROR: Cannot save users table: %s", ewx->base.instance, errstr);
			free(errstr);
		}
	}

	// Clean up
	if (response)
		snmp_free_pdu(response);

    return result;
}
