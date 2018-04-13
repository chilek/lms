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
#include <netinet/in.h>
#include <arpa/inet.h>

#include "lmsd.h"
#include "ewx-stm-channels.h"

int del_channel(GLOBAL *, struct ewx_module*, struct snmp_session*, struct channel*);
int add_channel(GLOBAL *, struct ewx_module*, struct snmp_session*, struct channel*);
int update_channel(GLOBAL *, struct ewx_module*, struct snmp_session*, struct channel*, struct channel*);
int mod_channel(GLOBAL *, struct ewx_module*, struct snmp_session*, int, int, int, int);
int del_node(GLOBAL *, struct ewx_module*, struct snmp_session*, struct host*);
int add_node(GLOBAL *, struct ewx_module*, struct snmp_session*, struct host*, int);
int update_node(GLOBAL *, struct ewx_module*, struct snmp_session*, struct host*, struct host);
int save_tables(GLOBAL *, struct ewx_module*, struct snmp_session*);

int match_oids(oid *oid1, oid *oid2)
{
    int i;
    for (i=STM_OID_LEN; i>=0; i--) {
        if (oid1[i] != oid2[i])
            return 0;
    }

    return 1;
}

char * itoa(int i)
{
    static char string[15];
	sprintf(string, "%d", i);
	return string;
}

int find_asterisk(const char *str)
{
	int i, len;

	for(i=0, len = strlen(str); i<len; i++)
		if(str[i] == '*')
			return 1;

	return 0;
}

void reload(GLOBAL *g, struct ewx_module *ewx)
{
	struct snmp_session 	session, *sh=NULL;
	struct snmp_pdu 	*pdu, *response;

	int	pathuplink=0, pathdownlink=0, maxcustomers=0, maxchannels=0;
	int globaluprate=0, globaldownrate=0; 
	int	maxupceil=0, maxdownceil=0, savetables=0;
	int status, i, j, n, o, k=0, cc=0, sc=0, night=0, nodescount=0;
	int	nc=0, anc=0, mnc=0, inc=0, emnc=0, einc=0, macs_cnt=0;
	char *errstr, *query;
	char *netnames, *netname;
	char *enets, *enetsql;
	char **macs = NULL;

	QueryHandle *res;

    struct channel *customers;
    struct channel *channels;
	struct net *nets;
    struct net *all_nets;
    struct net *mac_nets;
    struct net *ip_nets;
    struct net *emac_nets;
    struct net *eip_nets;

	if(!ewx->path)
	{
        syslog(LOG_ERR, "[%s/ewx-stm-channels] Option 'path' not specified", ewx->base.instance);
		return;
	}

	// Initialize the SNMP library (reading lmsd-ewx-stm.conf and snmp.conf)
	init_snmp("lmsd-ewx-stm");

	// Initialize a "session" that defines who we're going to talk to
	snmp_sess_init(&session); 			// setting up defaults 
	session.version 	= SNMP_VERSION_2c; 	// SNMP version number
	session.peername 	= ewx->host;	 	// destination IP/name
	session.remote_port	= ewx->port; 		// destination port
//	session.timeout 	= 1000000; 		// timeout in microsec.
	session.community 	= (unsigned char *) ewx->community; 	// community name
	session.community_len 	= strlen(ewx->community);

	// man snmpcmd (-Oq)
	netsnmp_ds_toggle_boolean(NETSNMP_DS_LIBRARY_ID, NETSNMP_DS_LIB_QUICK_PRINT);

	// Open the session
	sh = snmp_open(&session);

	if(!sh)
	{
	        snmp_error(&session, NULL, NULL, &errstr);
	        syslog(LOG_ERR, "[%s/ewx-stm-channels] SNMP ERROR: %s", ewx->base.instance, errstr);
		free(errstr);
		return;
	}

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_GET);

	// Getting path's uplink and downlink values is good reason to check
	// communication with our device  
	PathUplink[STM_OID_LEN-1] = ewx->path;
	PathDownlink[STM_OID_LEN-1] = ewx->path;
	snmp_add_null_var(pdu, PathUplink, STM_OID_LEN);
	snmp_add_null_var(pdu, PathDownlink, STM_OID_LEN);
	snmp_add_null_var(pdu, MaxCustomers, STM_OID_LEN);
	snmp_add_null_var(pdu, MaxChannels, STM_OID_LEN);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
		struct variable_list *vars;
		char buf[MAX_OID_LEN];

#ifdef LMS_SNMP_DEBUG
   		for(vars = response->variables; vars; vars = vars->next_variable)
   			print_variable(vars->name, vars->name_length, vars);
#endif

		for(vars = response->variables; vars; vars = vars->next_variable)
		{
			snprint_objid(buf, MAX_OID_LEN, vars->name, vars->name_length);
			snprint_value(buf, MAX_OID_LEN, vars->name, vars->name_length, vars);

			if (match_oids(vars->name, PathUplink)) {
				pathuplink = atoi(buf);
			}
			else if (match_oids(vars->name, PathDownlink)) {
				pathdownlink = atoi(buf);
			}
			else if (match_oids(vars->name, MaxCustomers)) {
				maxcustomers = atoi(buf);
			}
			else if (match_oids(vars->name, MaxChannels)) {
				maxchannels = atoi(buf);
			}
		}
	}
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot read path's data. %s", ewx->base.instance, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot read path's data. %s", ewx->base.instance, errstr);
			free(errstr);
		}
		return;
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	snmp_close(sh);

	// If communication works, we can do the job...
        customers = (struct channel *) malloc(sizeof(struct channel));
        channels = (struct channel *) malloc(sizeof(struct channel));
        nets = (struct net *) malloc(sizeof(struct net));
        all_nets = (struct net *) malloc(sizeof(struct net));
        mac_nets = (struct net *) malloc(sizeof(struct net));
        ip_nets = (struct net *) malloc(sizeof(struct net));
        emac_nets = (struct net *) malloc(sizeof(struct net));
        eip_nets = (struct net *) malloc(sizeof(struct net));

	// get all networks params
        res = g->db->pquery(g->db->conn, "SELECT UPPER(name) AS name, address, "
			"INET_ATON(mask) AS mask, interface FROM networks");

	for(anc=0; anc<g->db->nrows(res); anc++)
	{
	        all_nets = (struct net*) realloc(all_nets, (sizeof(struct net) * (anc+1)));
		all_nets[anc].name = strdup(g->db->get_data(res, anc, "name"));
		all_nets[anc].address = inet_addr(g->db->get_data(res, anc, "address"));
	        all_nets[anc].mask = inet_addr(g->db->get_data(res, anc, "mask"));
	}
	g->db->free(&res);

	n = 2;
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

	// get networks for filter if any specified in 'dummy_mac_networks' 
	// option, use '*' for all networks
	if(find_asterisk(ewx->dummy_mac_networks))
	{
		for(i=0; i<anc; i++)
	    {
			mac_nets = (struct net *) realloc(mac_nets, (sizeof(struct net) * (mnc+1)));
			mac_nets[mnc].address = all_nets[i].address;
			mac_nets[mnc].mask = all_nets[i].mask;
			mac_nets[mnc].name = strdup(all_nets[i].name);
			mnc++;
		}
	}
	else while( n>1 )
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

	// get networks for filter if any specified in 'dummy_ip_networks'
	// option, use '*' for all networks
	if(find_asterisk(ewx->dummy_ip_networks))
	{
		for(i=0; i<anc; i++)
	    {
			ip_nets = (struct net *) realloc(ip_nets, (sizeof(struct net) * (inc+1)));
			ip_nets[inc].address = all_nets[i].address;
			ip_nets[inc].mask = all_nets[i].mask;
			ip_nets[inc].name = strdup(all_nets[i].name);
			inc++;
		}
	}
	else while( n>1 )
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
	    			syslog(LOG_ERR, "[%s/ewx-stm-channels] Network %s already included in 'dummy_mac_networks' option. Skipping.", all_nets[i].name, ewx->base.instance);
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

	n = 2;
	netnames = strdup(ewx->excluded_dummy_ip_networks);
	netname = strdup(netnames);
	
	// get networks for filter if any specified in 'excluded_dummy_ip_networks'
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
				eip_nets = (struct net *) realloc(eip_nets, (sizeof(struct net) * (einc+1)));
				eip_nets[einc].address = all_nets[i].address;
				eip_nets[einc].mask = all_nets[i].mask;
				eip_nets[einc].name = strdup(all_nets[i].name);
				einc++;
			}
		}
	}
	free(netname); free(netnames);

	n = 2;
	netnames = strdup(ewx->excluded_dummy_mac_networks);
	netname = strdup(netnames);
	
	// get networks for filter if any specified in 'excluded_dummy_mac_networks'
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
				emac_nets = (struct net *) realloc(emac_nets, (sizeof(struct net) * (emnc+1)));
				emac_nets[emnc].address = all_nets[i].address;
				emac_nets[emnc].mask = all_nets[i].mask;
				emac_nets[emnc].name = strdup(all_nets[i].name);
				emnc++;
			}
		}
	}
	free(netname); free(netnames);

	// excluded networks filter
	enets = strdup(" AND NOT EXISTS (SELECT 1 FROM networks net "
			"WHERE (%enets) "
	                "AND ((n.ipaddr > net.address AND n.ipaddr < broadcast(net.address, inet_aton(net.mask))) "
			"OR (n.ipaddr_pub > net.address AND n.ipaddr_pub < broadcast(net.address, inet_aton(net.mask)))) "
			")");

	netnames = strdup(ewx->excluded_networks);
	netname = strdup(netnames);
	enetsql = strdup("");
	o = 2;

	while( o>1 )
	{
    		o = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )
		{
			enetsql = realloc(enetsql, sizeof(char *) * (strlen(enetsql) + strlen(netname) + 30));
			if(strlen(enetsql))
				strcat(enetsql, " OR UPPER(net.name) = UPPER('");
			else
				strcat(enetsql, "UPPER(net.name) = UPPER('");
			
			strcat(enetsql, netname);
			strcat(enetsql, "')");
		}
	}
	free(netname); free(netnames);
	
	if(strlen(enetsql))
		g->str_replace(&enets, "%enets", enetsql);

        // handle night-time tariffs
	if (strlen(ewx->night_hours))
	{
	        int start_h, end_h;
			
	        if (sscanf(ewx->night_hours, "%d-%d", &start_h, &end_h) == 2)
	        {
			// get current date
    			time_t t = time(NULL);
	    		struct tm *tt = localtime(&t);
	                int hour = tt->tm_hour;

	                if (end_h < 18) end_h += 24;
	                if (start_h < 18) start_h += 24;
	                if (hour < 18) hour += 24;

	                if (start_h >= end_h)
	                        syslog(LOG_ERR, "[%s/ewx-stm-channels] Wrong 'night_hours' format: %s", ewx->base.instance, ewx->night_hours);
	                else if (hour >= start_h && hour < end_h)
	                        night = 1;
	        }
		else
		        syslog(LOG_ERR, "[%s/ewx-stm-channels] Wrong 'night_hours' format: %s", ewx->base.instance, ewx->night_hours);
	}

	// channels
	query = strdup("SELECT id, name, upceil, downceil, halfduplex FROM ewx_channels");

	if (night)
        {
	        g->str_replace(&query, "downceil", "(CASE WHEN downceil_n > 0 THEN downceil_n ELSE downceil END)");
	        g->str_replace(&query, "upceil", "(CASE WHEN upceil_n > 0 THEN upceil_n ELSE upceil END)");
	}
	
	res = g->db->query(g->db->conn, query);
	
	for(i=0; i<g->db->nrows(res); i++)
	{
		int cid 	= atoi(g->db->get_data(res,i,"id"));
		int upceil 	= atoi(g->db->get_data(res,i,"upceil"));
		int downceil 	= atoi(g->db->get_data(res,i,"downceil"));
		int halfduplex 	= atoi(g->db->get_data(res,i,"halfduplex"));

		customers = (struct channel *) realloc(customers, (sizeof(struct channel) * (cc+1)));
		customers[cc].cid = cid;
		customers[cc].upceil = upceil;
		customers[cc].downceil = downceil;
		customers[cc].halfduplex = halfduplex;
        customers[cc].no = 0;
        customers[cc].downratesum = 0;
		customers[cc].upratesum = 0;
        customers[cc].hosts = NULL;
		customers[cc].status = UNKNOWN;
		cc++;
	}
	g->db->free(&res);
	free(query);

	// hosts
	query = strdup("SELECT t.downrate, t.downceil, t.uprate, t.upceil, n.chkmac, "
			"n.id, INET_NTOA(n.ipaddr) AS ip, n.halfduplex, d.channelid, "
			"(SELECT m.mac FROM macs m WHERE m.nodeid = n.id ORDER BY m.id LIMIT 1) AS mac "
		"FROM nodeassignments na "
		"JOIN assignments a ON (na.assignmentid = a.id)"
		"JOIN tariffs t ON (a.tariffid = t.id) "
		"JOIN nodes n ON (na.nodeid = n.id) "
		"LEFT JOIN netdevices d ON (n.netdev = d.id) "
		"WHERE "
			"a.datefrom <= %NOW% AND (a.dateto >= %NOW% OR a.dateto = 0)"
			"%disabled"
			"%enets"
	);

	g->str_replace(&query, "%disabled", ewx->skip_disabled ? " AND n.access = 1" : "");
	g->str_replace(&query, "%enets", strlen(enetsql) ? enets : "");	

	if (night)
    {
	    g->str_replace(&query, "t.downrate", "(CASE WHEN t.downrate_n > 0 THEN t.downrate_n ELSE t.downrate END) AS downrate");
	    g->str_replace(&query, "t.downceil", "(CASE WHEN t.downceil_n > 0 THEN t.downceil_n ELSE t.downceil END) AS downceil");
	    g->str_replace(&query, "t.uprate", "(CASE WHEN t.uprate_n > 0 THEN t.uprate_n ELSE t.uprate END) AS uprate");
	    g->str_replace(&query, "t.upceil", "(CASE WHEN t.upceil_n > 0 THEN t.upceil_n ELSE t.upceil END) AS upceil");
	}

	res = g->db->query(g->db->conn,  query);

	// adding hosts to customers array
	for (i=0; i<g->db->nrows(res); i++)
    {
		int channelid   = atoi(g->db->get_data(res,i,"channelid"));
        int hostid      = atoi(g->db->get_data(res,i,"id"));
		char *ip        = g->db->get_data(res,i,"ip");
		unsigned long inet = inet_addr(ip);

		// looking for a channel
		for (j=0; j<cc; j++)
			if (customers[j].cid == channelid)
				break;

		if (j == cc) {
			// hosts without channel, create default channel or skip
			if (!channelid && ewx->default_upceil && ewx->default_downceil)
			{
				customers = (struct channel *) realloc(customers, (sizeof(struct channel) * (cc+1)));
				customers[cc].cid = 0;
				customers[cc].upceil = ewx->default_upceil;
				customers[cc].downceil = ewx->default_downceil;
				customers[cc].halfduplex = ewx->default_halfduplex;
            	customers[cc].no = 0;
            	customers[cc].downratesum = 0;
				customers[cc].upratesum = 0;
            	customers[cc].hosts = NULL;
				customers[cc].status = UNKNOWN;
				cc++;
			}
			else
				continue;
		}

		// Networks test
		if(nc)
		{
			for(n=0; n<nc; n++)
	            if(nets[n].address == (inet & nets[n].mask))
	                break;

			if(n == nc) continue;
		}

		int uprate 	    = atoi(g->db->get_data(res,i,"uprate"));
		int downrate 	= atoi(g->db->get_data(res,i,"downrate"));
		int upceil 	    = atoi(g->db->get_data(res,i,"upceil"));
		int downceil 	= atoi(g->db->get_data(res,i,"downceil"));

		// looking for host
		for (k=0; k<customers[j].no; k++)
			if (customers[j].hosts[k].id == hostid)
				break;

		if (k == customers[j].no) // host not exists
		{
	        int dummy_ip = 0;
			int dummy_mac = 0;
			char *mac = g->db->get_data(res,i,"mac");

			// Networks test for dummy_mac
			if(mnc)
			{
				for(n=0; n<mnc; n++)
           			if(mac_nets[n].address == (inet & mac_nets[n].mask))
						break;

				if(n != mnc) dummy_mac = 1;
			}

			if(dummy_mac && emnc)
			{
				for(n=0; n<emnc; n++)
           			if(emac_nets[n].address == (inet & emac_nets[n].mask))
						break;

				if(n != emnc) dummy_mac = 0;
			}

			// Networks test for dummy_ip
			if(inc && !dummy_mac)
			{
				for(n=0; n<inc; n++)
            		if(ip_nets[n].address == (inet & ip_nets[n].mask))
                    	break;

				if(n != inc) dummy_ip = 1;
			}

			if(dummy_ip && einc && !dummy_mac)
			{
				for(n=0; n<einc; n++)
	            	if(eip_nets[n].address == (inet & eip_nets[n].mask))
	                	break;

				if(n != einc) dummy_ip = 0;
			}

			if(!dummy_mac && !dummy_ip && !atoi(g->db->get_data(res,i,"chkmac"))) {
				dummy_mac = 1;
			}

			// data checking (MAC duplicates)
			if (!dummy_mac && strcmp(mac, "00:00:00:00:00:00")) {
			    for (n=0; n<macs_cnt; n++) {
			        if (!strcmp(mac, macs[n]))
			            break;
			    }
			    if (n<macs_cnt) {
    	            syslog(LOG_ERR, "[%s/ewx-stm-channels] Duplicated MAC %s (%05d). Skipped.",
	                    ewx->base.instance, mac, hostid);
		    	    continue;
			    }
			    macs = (char **) realloc(macs, (sizeof(char *) * (macs_cnt+1)));
			    macs[macs_cnt++] = strdup(mac);
			}
			// data checking ("empty" IP/MAC pairs)
			else if (dummy_ip) {
	            syslog(LOG_ERR, "[%s/ewx-stm-channels] Wrong node data 0.0.0.0/00:00:00:00:00:00 (%05d). Skipped.",
	                ewx->base.instance, hostid);
			    continue;
			}

            // add to hosts table
			customers[j].hosts = (struct host *) realloc(customers[j].hosts, (sizeof(struct host) * (k+1)));
			customers[j].hosts[k].id = hostid;
			customers[j].hosts[k].uprate = uprate;
			customers[j].hosts[k].upceil = upceil;
			customers[j].hosts[k].downrate = downrate;
			customers[j].hosts[k].downceil = downceil;
			customers[j].hosts[k].status = UNKNOWN;
			customers[j].hosts[k].halfduplex = atoi(g->db->get_data(res,i,"halfduplex"));

			if(!dummy_ip)
				customers[j].hosts[k].ip = strdup(ip);
			else
				customers[j].hosts[k].ip = strdup(DUMMY_IP);

			if(!dummy_mac)
				customers[j].hosts[k].mac = strdup(mac);
			else
				customers[j].hosts[k].mac = strdup(DUMMY_MAC);

			customers[j].downratesum += downrate;
			customers[j].upratesum += uprate;
			customers[j].no++;
		}
		else
		{
			customers[j].hosts[k].uprate += uprate;
			customers[j].hosts[k].upceil += upceil;
			customers[j].hosts[k].downrate += downrate;
			customers[j].hosts[k].downceil += downceil;
			customers[j].downratesum += downrate;
			customers[j].upratesum += uprate;
		}
	}
	g->db->free(&res);
	free(query);

	// Przelecmy po wszystkich kanalach i komputerach, zliczajac sumy rate i ceil
	// aby nie przekroczyc wartosci ustawionych dla sciezek na urzadzeniu.
	// Dodatkowo zmniejszymy rate komputerow w kanalach, jesli suma rate przekracza ceil kanalu
	for (i=0; i<cc; i++)
    {
		struct channel c = customers[i];

		if (!c.no) continue;

		if (c.upratesum > c.upceil) {
            syslog(LOG_WARNING, "[%s/ewx-stm-channels] The sum of nodes upload rate is too big for channel (%05d) [sum: %d, ceil: %d]. Reduced nodes rates.",
                ewx->base.instance, c.cid, c.upratesum, c.upceil);
        }
		if (c.downratesum > c.downceil) {
            syslog(LOG_WARNING, "[%s/ewx-stm-channels] The sum of nodes download rate is too big for channel (%05d) [sum: %d, ceil: %d]. Reduced nodes rates.",
                ewx->base.instance, c.cid, c.downratesum, c.downceil);
        }

		// Summary hosts limits
		for(k=0; k<c.no; k++)
		{
			// decrease (balance) node rates if sum of nodes rates
			// is greater than channel's ceil
			if(c.upratesum > c.upceil) {
				c.hosts[k].uprate = c.hosts[k].uprate / (double) c.upratesum * c.upceil;
			}
			if(c.downratesum > c.downceil) {
				c.hosts[k].downrate = c.hosts[k].downrate / (double) c.downratesum * c.downceil;
			}
			// make sure node ceil is not greater than channel ceil
			if (c.upceil < c.hosts[k].upceil) {
				c.hosts[k].upceil = c.upceil;
			}
			if (c.downceil < c.hosts[k].downceil) {
				c.hosts[k].downceil = c.downceil;
            }
			globaluprate += c.hosts[k].uprate;
			globaldownrate += c.hosts[k].downrate;
            nodescount++;
		}

		maxupceil = maxupceil < c.upceil ? c.upceil : maxupceil;
		maxdownceil = maxdownceil < c.downceil ? c.downceil : maxdownceil;
	}

#ifdef LMS_SNMP_DEBUG
    printf("LICENCES: %d/%d %d/%d\n", nodescount, maxcustomers, cc, maxchannels);
    printf("SUMMARY: %d/%d (%d/%d)\n", globaldownrate, globaluprate, pathdownlink, pathuplink);
    printf("SUMMARY: %d/%d (%d/%d)\n", maxdownceil, maxupceil, pathdownlink, pathuplink);
#endif

    // check if we have enough license points
    if (maxcustomers < nodescount)
    {
        syslog(LOG_ERR, "[%s/ewx-stm-channels] Lack of customer licences (limit %d). Need %d. Exiting.", ewx->base.instance, maxcustomers, nodescount);
		return;
	}
    if (maxchannels < cc)
    {
        syslog(LOG_ERR, "[%s/ewx-stm-channels] Lack of channel licences (limit %d). Need %d. Exiting.", ewx->base.instance, maxchannels, cc);
		return;
	}

	// path limits checking
	if(globaluprate>pathuplink || globaldownrate>pathdownlink)
	{
        syslog(LOG_ERR, "[%s/ewx-stm-channels] Path is too small. Need Uplink: %d, Downlink: %d. Exiting.", ewx->base.instance, globaluprate, globaldownrate);
		return;
	}

	if(maxupceil>pathuplink || maxdownceil>pathdownlink)
	{
        syslog(LOG_ERR, "[%s/ewx-stm-channels] Path is too small. Need Uplink: %d, Downlink: %d. Exiting.", ewx->base.instance, maxupceil, maxdownceil);
		return;
	}

	// Reading hosts/channels definitions from ewx_stm_* tables
	// NOTE: to re-create device configuration do DELETE FROM ewx_stm_nodes; DELETE FROM ewx_stm_channels;
	query = strdup("SELECT id, cid, upceil, downceil, halfduplex FROM ewx_stm_channels");

	res = g->db->query(g->db->conn, query);
	
	for(i=0; i<g->db->nrows(res); i++) 
	{
		int id 		= atoi(g->db->get_data(res,i,"id"));
		int cid 	= atoi(g->db->get_data(res,i,"cid"));
		int upceil 	= atoi(g->db->get_data(res,i,"upceil"));
		int downceil 	= atoi(g->db->get_data(res,i,"downceil"));
		int halfduplex 	= atoi(g->db->get_data(res,i,"halfduplex"));

		channels = (struct channel *) realloc(channels, (sizeof(struct channel) * (sc+1)));
		channels[sc].id = id;
		channels[sc].cid = cid;
		channels[sc].upceil = upceil;
		channels[sc].downceil = downceil;
		channels[sc].halfduplex = halfduplex;
		channels[sc].upratesum = 0;
		channels[sc].downratesum = 0;
        channels[sc].no = 0;
        channels[sc].hosts = NULL;
		channels[sc].status = UNKNOWN;
		sc++;
	}
	g->db->free(&res);
	free(query);

	query = strdup("SELECT nodeid, mac, INET_NTOA(ipaddr) AS ip, channelid, n.halfduplex, "
				"n.uprate, n.upceil, n.downrate, n.downceil, c.cid, "
				"c.upceil AS cupceil, c.downceil AS cdownceil, c.halfduplex AS cduplex "
			"FROM ewx_stm_nodes n "
			"LEFT JOIN ewx_stm_channels c ON (c.id = n.channelid)"
	);

	res = g->db->query(g->db->conn, query);

	// Creating current config array
	for(i=0; i<g->db->nrows(res); i++)
    {
        int channelid = atoi(g->db->get_data(res,i,"channelid"));
        int hostid = atoi(g->db->get_data(res,i,"nodeid"));
		char *ip = g->db->get_data(res,i,"ip");
		unsigned long inet = inet_addr(ip);

		// Networks test
		if(nc && inet)
		{
			for(j=0; j<nc; j++)
                if(nets[j].address == (inet & nets[j].mask))
                    break;
			if(j == nc)
				continue;
		}

		// looking for the channel
		for(j=0; j<sc; j++)
			if(channels[j].id == channelid)
				break;

		// this will happen if we've got nodes without a channel
		if(j == sc)
		{
			int cupceil = atoi(g->db->get_data(res,i,"cupceil"));
			int cdownceil = atoi(g->db->get_data(res,i,"cdownceil"));
			int cduplex = atoi(g->db->get_data(res,i,"cduplex"));

			channels = (struct channel *) realloc(channels, (sizeof(struct channel) * (sc+1)));
			channels[sc].id = channelid;
			channels[sc].cid = atoi(g->db->get_data(res,i,"cid"));
			channels[sc].upceil = cupceil ? cupceil : ewx->default_upceil;
			channels[sc].downceil = cdownceil ? cdownceil : ewx->default_downceil;
			channels[sc].halfduplex = cduplex ? cduplex : ewx->default_halfduplex;
			channels[sc].upratesum = 0;
			channels[sc].downratesum = 0;
            channels[sc].no = 0;
            channels[sc].hosts = NULL;
			channels[sc].status = UNKNOWN;
			sc++;
		}

		k = channels[j].no;

		channels[j].hosts = (struct host *) realloc(channels[j].hosts, (sizeof(struct host) * (k+1)));
		channels[j].hosts[k].id = hostid;
		channels[j].hosts[k].uprate = atoi(g->db->get_data(res,i,"uprate"));
		channels[j].hosts[k].upceil = atoi(g->db->get_data(res,i,"upceil"));
		channels[j].hosts[k].downrate = atoi(g->db->get_data(res,i,"downrate"));
		channels[j].hosts[k].downceil = atoi(g->db->get_data(res,i,"downceil"));
		channels[j].hosts[k].ip = strdup(ip);
		channels[j].hosts[k].mac = strdup(g->db->get_data(res,i,"mac"));
		channels[j].hosts[k].halfduplex = atoi(g->db->get_data(res,i,"halfduplex"));
		channels[j].hosts[k].status = UNKNOWN;
		channels[j].upratesum += channels[j].hosts[k].uprate;
		channels[j].downratesum += channels[j].hosts[k].downrate;
		channels[j].no++;
	}
	g->db->free(&res);
	free(query);

	// Open the session again
	sh = snmp_open(&session);

	if(!sh)
	{
        snmp_error(&session, NULL, NULL, &errstr);
        syslog(LOG_ERR, "[%s/ewx-stm-channels] SNMP ERROR: %s", ewx->base.instance, errstr);
		free(errstr);
		return;
	}

	// Main loop ****************************************************************
	for (i=0; i<cc; i++)
    {
		int found=0, x;
		struct channel c = customers[i];

		if (!c.no) continue;

		// Seek (old) channel
		for (x=0; x<sc; x++)
			if (channels[x].cid == c.cid)
				break;

#ifdef LMS_SNMP_DEBUG
        printf("%d [%d %d %d %d]\n", c.cid, c.upceil, c.downceil, c.upratesum, c.downratesum);
#endif
		// szukamy komputerow, moga nalezec do innego kanalu, dlatego przegladamy wszystkie
		for (n=0; n<c.no; n++)
		{
			struct host new = c.hosts[n];
		    int node_exists = 0;

            for (j=0; j<sc; j++)
	        {
			    for (k=0; k<channels[j].no; k++)
			    {
				    if (channels[j].hosts[k].status == STATUS_OK)
					    continue;

				    struct host old = channels[j].hosts[k];

					if(
						old.id == new.id
						||
						(inet_addr(old.ip) == inet_addr(new.ip) && inet_addr(new.ip) != inet_addr(DUMMY_IP)) 
						||
						(!strcmp(old.mac, new.mac) && strcmp(new.mac, DUMMY_MAC))
					) {
					    // komputer nalezy do innego kanalu lub zmiana ID
						if (x != j || (old.id != new.id)) {
						    // juz zostal usuniety
						    if (old.status == DELETED) {
						        continue;
						    }

							channels[j].upratesum -= old.uprate;
							channels[j].downratesum -= old.downrate;
                            // usuwamy komputer
							del_node(g, ewx, sh, &channels[j].hosts[k]);
							savetables = 1;
                            continue;
                        }

						// kanal sie zgadza, ID tez, sprawdzamy jeszcze...
						if( // adres IP
							strcmp(old.ip, new.ip)
							|| // limity
							(old.uprate != new.uprate || old.upceil != new.upceil ||
							old.downrate != new.downrate || old.downceil != new.downceil)
							|| // halfduplex
							old.halfduplex != new.halfduplex
							|| // MAC
							strcmp(old.mac, new.mac)
						) {
							// zwiekszenie limitow, trzeba podniesc parametry kanalu
       	    	        	if (new.upceil > channels[x].upceil || new.downceil > channels[x].downceil)
							{
								mod_channel(g, ewx, sh, channels[x].id, c.upceil, c.downceil, c.halfduplex);
								channels[x].upceil = c.upceil;
								channels[x].downceil = c.downceil;
								channels[x].status = STATUS_OK;
							}

							// moze wystapic (chwilowe) przekroczenie sumy rate
							if (old.uprate != new.uprate || old.downrate != new.downrate)
							{
								channels[x].upratesum += new.uprate - old.uprate;
								channels[x].downratesum += new.downrate - old.downrate;

								if (channels[x].upceil < channels[x].upratesum || channels[x].downceil < channels[x].downratesum)
								{
									mod_channel(g, ewx, sh, channels[x].id,
									    channels[x].upratesum, channels[x].downratesum, channels[x].halfduplex);
									channels[x].upceil = channels[x].upratesum;
									channels[x].downceil = channels[x].downratesum;
							    	channels[x].status = STATUS_OK;
								}
							}

							update_node(g, ewx, sh, &c.hosts[n], channels[j].hosts[k]);
							savetables = 1;
						}

						// wszystko sie zgadza, zmieniamy status
						channels[j].hosts[k].status = STATUS_OK;
                        node_exists = 1;
						found++;
					}
				}
			}

            // dodajemy komputer, jesli kanal juz istnieje i jest do czego dodawac
			if (!node_exists && x < sc) {
				// zwiekszenie limitow, trzeba podniesc parametry kanalu
                if (new.upceil > channels[x].upceil || new.downceil > c.downceil)
				{
					mod_channel(g, ewx, sh, channels[x].id, c.upceil, c.downceil, c.halfduplex);
					channels[x].upceil = c.upceil;
					channels[x].downceil = c.downceil;
					channels[x].status = STATUS_OK;
				}

				channels[x].upratesum += new.uprate;
				channels[x].downratesum += new.downrate;

				// moze wystapic (chwilowe) przekroczenie sumy rate
				if (channels[x].upceil < channels[x].upratesum || channels[x].downceil < channels[x].downratesum)
				{
					mod_channel(g, ewx, sh, channels[x].id,
					    channels[x].upratesum, channels[x].downratesum, channels[x].halfduplex);
					channels[x].upceil = channels[x].upratesum;
					channels[x].downceil = channels[x].downratesum;
					channels[x].status = STATUS_OK;
				}

                add_node(g, ewx, sh, &c.hosts[n], channels[x].id + ewx->offset);
			    savetables = 1;
                found++;
            }
		}

#ifdef LMS_SNMP_DEBUG
        printf("id:%d, x:%d, sc::%d, found:%d, no:%d-%d\n", c.cid, x, sc, found, c.no, channels[x].no);
#endif
        // usuwamy komputery z kanalu
        if (x < sc) {
			for (k=0; k<channels[x].no; k++) {
			    if (channels[x].hosts[k].status == DELETED)
			        continue;

				struct host old = channels[x].hosts[k];

			    for (n=0; n<c.no; n++) {
                    if (old.id == c.hosts[n].id)
                        break;
                }

                // komputer (old) nie znaleziony - do usuniecia
                if (n == c.no) {
					del_node(g, ewx, sh, &channels[x].hosts[k]);
				    channels[x].upratesum -= old.uprate;
				    channels[x].downratesum -= old.downrate;
				    savetables = 1;
                }
            }
        }

        // Dodajemy/aktualizujemy kanal
		if (found < c.no || x == sc)
		{
			savetables = 1;
			// channel exists
			if(x!=sc) {
				update_channel(g, ewx, sh, &channels[x], &c);
			}
			else
				add_channel(g, ewx, sh, &c);
		}
		else if (channels[x].upceil != c.upceil
		    || channels[x].downceil != c.downceil
		    || channels[x].halfduplex != c.halfduplex
		) {
			savetables = 1;
            mod_channel(g, ewx, sh, channels[x].id, c.upceil, c.downceil, c.halfduplex);
		}

	} // End of main loop **********************************************************

	// Remove the rest of old config
	for(i=0; i<sc; i++)
		if(channels[i].status == UNKNOWN)
		{
			int deleted = 0;
			for(j=0; j<channels[i].no; j++)
				if(channels[i].hosts[j].status == UNKNOWN)
				{
					savetables = 1;
					del_node(g, ewx, sh, &channels[i].hosts[j]);
					deleted++;
				}

			if(deleted == channels[i].no) {
				savetables = 1;
				del_channel(g, ewx, sh, &channels[i]);
			}
		}

	// Save device configuration changes
	if (savetables)
		save_tables(g, ewx, sh);

	snmp_close(sh);

#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/ewx-stm-channels] reloaded", ewx->base.instance);
#endif

    for (i=0; i<sc; i++) {
		for (j=0; j<channels[i].no; j++) {
			free(channels[i].hosts[j].ip);
			free(channels[i].hosts[j].mac);
		}
        free(channels[i].hosts);
	}
    free(channels);

    for (i=0; i<cc; i++) {
		for (j=0; j<customers[i].no; j++) {
			free(customers[i].hosts[j].ip);
			free(customers[i].hosts[j].mac);
		}
        free(customers[i].hosts);
	}
    free(customers);

	for (i=0;i<nc;i++) {
		free(nets[i].name);
	}
	free(nets);

	for (i=0;i<anc;i++) {
		free(all_nets[i].name);
	}
	free(all_nets);

	for(i=0;i<mnc;i++) {
		free(mac_nets[i].name);
	}
	free(mac_nets);

	for(i=0;i<inc;i++) {
		free(ip_nets[i].name);
	}
	free(ip_nets);

	for(i=0;i<emnc;i++) {
		free(emac_nets[i].name);
	}
	free(emac_nets);

	for(i=0;i<einc;i++) {
		free(eip_nets[i].name);
	}
	free(eip_nets);

	for(i=0;i<macs_cnt;i++) {
		free(macs[i]);
	}
	free(macs);

	free(enets);
	free(enetsql);
	free(ewx->community);
	free(ewx->host);
	free(ewx->networks);
	free(ewx->dummy_mac_networks);
	free(ewx->dummy_ip_networks);
	free(ewx->excluded_networks);
	free(ewx->excluded_dummy_mac_networks);
	free(ewx->excluded_dummy_ip_networks);
	free(ewx->night_hours);
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
	ewx->excluded_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "excluded_networks", ""));
	ewx->dummy_mac_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "dummy_mac_networks", ""));
	ewx->dummy_ip_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "dummy_ip_networks", ""));
	ewx->excluded_dummy_mac_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "excluded_dummy_mac_networks", ""));
	ewx->excluded_dummy_ip_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "excluded_dummy_ip_networks", ""));
	ewx->skip_disabled = g->config_getbool(ewx->base.ini, ewx->base.instance, "skip_disabled", 1);
	ewx->night_hours = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "night_hours", ""));

	// parametry domyslnego kanalu
	// ustawienie któregs z nich na zero, spowoduje pominiecie komputerów bez przypisanego kanlu
	ewx->default_upceil = g->config_getint(ewx->base.ini, ewx->base.instance, "default_upceil", 0);
	ewx->default_downceil = g->config_getint(ewx->base.ini, ewx->base.instance, "default_downceil", 0);
	ewx->default_halfduplex = g->config_getbool(ewx->base.ini, ewx->base.instance, "default_halfduplex", 0);

	// node/channel ID's offset, e.g. for testing
	ewx->offset = g->config_getint(ewx->base.ini, ewx->base.instance, "offset", 0);

	// TODO: dorobic zarzadzanie sciezkami w LMSie (Configuration -> Paths albo TC Bands)
	// [Parametry sciezki: nazwa, full/half duplex, min upload, max upload] 
	// bylyby pomocnie nie tylko dla EWX'a, sciezki przypisywaloby sie do 
	// seci IP, pozwoliloby to na dodanie sprawdzenia osiagniecia maksymalnej granicy
	// ruchu dla sciezki podczas dodawania nowej taryfy.
	// ...tymczasem mozemy obsluzyc tylko jedna sciezke
	ewx->path = g->config_getint(ewx->base.ini, ewx->base.instance, "path", 0);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/ewx-stm-channels] initialized", ewx->base.instance);
#endif
	return(ewx);
}

int del_channel(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct channel *ch)
{
	struct snmp_pdu 	*pdu, *response;
	char *errstr;
	int i, status, result = STATUS_ERROR;
	struct channel c = *ch;

#ifdef LMS_SNMP_DEBUG
    printf("[DELETE CHANNEL] %d\n", c.id);
#endif
	// First we must delete all nodes in channel
	for(i=0; i<c.no; i++)
		if(c.hosts[i].status == UNKNOWN)
			del_node(g, ewx, sh, &c.hosts[i]);

	if(!sh) return result;

	// Create OID
	ChannelStatus[STM_OID_LEN-1] = c.id;

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	snmp_add_var(pdu, ChannelStatus, STM_OID_LEN, 'i', DESTROY);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
		struct variable_list *vars;
    	for(vars = response->variables; vars; vars = vars->next_variable)
    		print_variable(vars->name, vars->name_length, vars);
#endif
		g->db->pexec(g->db->conn, "DELETE FROM ewx_stm_channels WHERE id = ?", itoa(c.id));
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm-channels] Deleted channel %d", ewx->base.instance, c.id);
#endif
		(*ch).status = result = DELETED;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot delete channel %d: %s", ewx->base.instance, c.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot delete channel %d: %s", ewx->base.instance, c.id, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	return result;
}

int add_channel(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct channel *cust)
{
	struct snmp_pdu	*pdu, *response;
	char *errstr;
	int i, status, channelid, result = STATUS_ERROR;
	QueryHandle *res;
	struct channel c = *cust;

	char *upceil = strdup(itoa(c.upceil));
	char *downceil = strdup(itoa(c.downceil));

#ifdef LMS_SNMP_DEBUG
    printf("[ADD CHANNEL] %d\n", c.cid);
#endif
	if(!sh) return result;

	// Adding channel to database
	g->db->pexec(g->db->conn, "INSERT INTO ewx_stm_channels (cid, upceil, downceil, halfduplex) "
			    "VALUES(?, ?, ?, ?)", itoa(c.cid), upceil, downceil, c.halfduplex ? "1" : "NULL");

	res = g->db->pquery(g->db->conn, "SELECT id FROM ewx_stm_channels WHERE cid = ?", itoa(c.cid));

	channelid = atoi(g->db->get_data(res, 0, "id"));
	g->db->free(&res);

	// Pilnujemy aby channelid nie przekroczyl 99999
	// @TODO: na postgresie mozna uzyc generate_series() i zalatwic to jednym zapytaniem
	if(channelid > MAX_ID)
	{
        int lastid = 0;
		int newid = 0;
		int row = 0;

		while(newid==0)
		{
			res = g->db->pquery(g->db->conn, "SELECT id FROM ewx_stm_channels ORDER BY id LIMIT 100 OFFSET ?", itoa(row));

			// break loop when there're no rows
			if(!g->db->nrows(res))
			{
				g->db->free(&res);
				break;
			}

			for(i=0; i<g->db->nrows(res); i++)
    			{
        			int cid = atoi(g->db->get_data(res,i,"id"));
		    		if(cid > lastid + 1)
		    		{
					// found first free ID
		            		newid = lastid + 1;
		            		break;
		    		}
		    		else
				{
		            		lastid = cid;
		            		row++;
		    		}
			}
			g->db->free(&res);
		}

		if(newid)
		{
			char *nid = strdup(itoa(newid));
			g->db->pexec(g->db->conn, "UPDATE ewx_stm_channels SET id = ? WHERE id = ?", itoa(channelid), nid);
		        free(nid);
			channelid = newid;
		}
		else
		{
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot add channel %d. ID is too big.", ewx->base.instance, channelid);
			return result;
		}
	}

	// Create OID
	ChannelStatus[STM_OID_LEN-1] = channelid + ewx->offset;
	ChannelPathNo[STM_OID_LEN-1] = channelid + ewx->offset;
	ChannelUplink[STM_OID_LEN-1] = channelid + ewx->offset;
	ChannelDownlink[STM_OID_LEN-1] = channelid + ewx->offset;
	ChannelHalfDuplex[STM_OID_LEN-1] = channelid + ewx->offset;

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	snmp_add_var(pdu, ChannelPathNo, STM_OID_LEN, 'u', itoa(ewx->path));
	snmp_add_var(pdu, ChannelUplink, STM_OID_LEN, 'u', upceil);
	snmp_add_var(pdu, ChannelDownlink, STM_OID_LEN, 'u', downceil);
	if (c.halfduplex)
	    snmp_add_var(pdu, ChannelHalfDuplex, STM_OID_LEN, 'i', HALFDUPLEX); // default is FD
	snmp_add_var(pdu, ChannelStatus, STM_OID_LEN, 'i', CREATEANDGO);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
	    struct variable_list *vars;
   		for(vars = response->variables; vars; vars = vars->next_variable)
   			print_variable(vars->name, vars->name_length, vars);
#endif
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm-channels] Added channel %d", ewx->base.instance, channelid);
#endif
		(*cust).status = result = STATUS_OK;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot add channel %d: %s", ewx->base.instance, channelid, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot add channel %d: %s", ewx->base.instance, channelid, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	// Adding nodes to the channel
	if(result == STATUS_OK)
		for(i=0; i<c.no; i++)
		{
			add_node(g, ewx, sh, &c.hosts[i], channelid + ewx->offset);
		}

	return result;
}

int update_channel(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct channel *ch, struct channel *cust)
{
	struct snmp_pdu 	*pdu, *response;
	char *errstr;
	int i, status, result = STATUS_ERROR;

	struct channel c = *ch;
	struct channel cu = *cust;

#ifdef LMS_SNMP_DEBUG
    printf("[UPDATE CHANNEL] %d\n", c.id);
#endif
	// First we must delete all nodes in channel
	for(i=0; i<c.no; i++)
		if(c.hosts[i].status != DELETED)
			del_node(g, ewx, sh, &c.hosts[i]);

	if(!sh) return result;

    // Update channel limits if we need to
    if (cu.upceil != c.upceil || cu.downceil != c.downceil || cu.halfduplex != c.halfduplex)
    {
   	    char *upceil = strdup(itoa(cu.upceil));
        char *downceil = strdup(itoa(cu.downceil));

	    // Create OIDs
	    ChannelUplink[STM_OID_LEN-1] = c.id + ewx->offset;
	    ChannelDownlink[STM_OID_LEN-1] = c.id + ewx->offset;
        ChannelPathNo[STM_OID_LEN-1] = c.id + ewx->offset;
        ChannelHalfDuplex[STM_OID_LEN-1] = c.id + ewx->offset;
	    ChannelStatus[STM_OID_LEN-1] = c.id + ewx->offset;

	    // Create the PDU 
	    pdu = snmp_pdu_create(SNMP_MSG_SET);

	    // NOTINSERVICE we must send in separate packet
        snmp_add_var(pdu, ChannelStatus, STM_OID_LEN, 'i', NOTINSERVICE);

	    // Send the Request out
	    status = snmp_synch_response(sh, pdu, &response);

	    // Process the response
	    if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	    {
#ifdef LMS_SNMP_DEBUG
	        struct variable_list *vars;
   		    for(vars = response->variables; vars; vars = vars->next_variable)
   			    print_variable(vars->name, vars->name_length, vars);
#endif
	    }
	    else // failure
	    {
		    if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot update channel %d: %s", ewx->base.instance, c.id, snmp_errstring(response->errstat));
		    else
		    {
			    snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot update channel %d: %s", ewx->base.instance, c.id, errstr);
			    free(errstr);
		    }

            free(upceil);
            free(downceil);

		    return result;
	    }

	    // Clean up
	    if(response)
		    snmp_free_pdu(response);

	    // Create the PDU 
	    pdu = snmp_pdu_create(SNMP_MSG_SET);

        if (cu.upceil != c.upceil)
            snmp_add_var(pdu, ChannelUplink, STM_OID_LEN, 'u', upceil);
        if (cu.downceil != c.downceil)
            snmp_add_var(pdu, ChannelDownlink, STM_OID_LEN, 'u', downceil);
        if (cu.halfduplex != c.halfduplex)
            snmp_add_var(pdu, ChannelHalfDuplex, STM_OID_LEN, 'i', cu.halfduplex ? HALFDUPLEX : FULLDUPLEX);
	    snmp_add_var(pdu, ChannelStatus, STM_OID_LEN, 'i', ACTIVE);
        // snmp_add_var(pdu, ChannelPathNo, STM_OID_LEN, 'u', itoa(ewx->path));

	    // Send the Request out
	    status = snmp_synch_response(sh, pdu, &response);

	    // Process the response
	    if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	    {

#ifdef LMS_SNMP_DEBUG
	        struct variable_list *vars;
	        for(vars = response->variables; vars; vars = vars->next_variable)
   			    print_variable(vars->name, vars->name_length, vars);
#endif
		    g->db->pexec(g->db->conn, "UPDATE ewx_stm_channels SET upceil = ?, downceil = ?, halfduplex = ? "
			    "WHERE id = ?", upceil, downceil, itoa(c.id), cu.halfduplex ? "1" : "NULL");
#ifdef DEBUG1
		    syslog(LOG_INFO, "DEBUG: [%s/ewx-stm-channels] Updated channel %d", ewx->base.instance, c.id);
#endif
		    (*ch).status = (*cust).status = result = STATUS_OK;
	    }
	    else // failure
	    {
		    if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot update channel %d: %s", ewx->base.instance, c.id, snmp_errstring(response->errstat));
		    else
		    {
			    snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot update channel %d: %s", ewx->base.instance, c.id, errstr);
			    free(errstr);
		    }
	    }

	    // Clean up
	    if(response)
		    snmp_free_pdu(response);

        free(upceil);
        free(downceil);
    }
    else
        result = STATUS_OK;

	// Adding nodes to the channel
	if(result == STATUS_OK)
		for(i=0; i<cu.no; i++) {
			add_node(g, ewx, sh, &cu.hosts[i], c.id + ewx->offset);
		}

	return result;
}

int mod_channel(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, int id, int up, int down, int halfduplex)
{
	struct snmp_pdu 	*pdu, *response;
	char *errstr;
	int status, result = STATUS_ERROR;

	char *upceil = strdup(itoa(up));
	char *downceil = strdup(itoa(down));

#ifdef LMS_SNMP_DEBUG
    printf("[MODIFY CHANNEL] %d [%d:%d:%d]\n", id, up, down, halfduplex);
#endif
	// Create OIDs
	ChannelUplink[STM_OID_LEN-1] = id + ewx->offset;
	ChannelDownlink[STM_OID_LEN-1] = id + ewx->offset;
    ChannelHalfDuplex[STM_OID_LEN-1] = id + ewx->offset;
	ChannelStatus[STM_OID_LEN-1] = id + ewx->offset;

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	// NOTINSERVICE we must send in separate packet
    snmp_add_var(pdu, ChannelStatus, STM_OID_LEN, 'i', NOTINSERVICE);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
	    struct variable_list *vars;
   		for(vars = response->variables; vars; vars = vars->next_variable)
   			print_variable(vars->name, vars->name_length, vars);
#endif
	}
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot modify channel %d: %s", ewx->base.instance, id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot modify channel %d: %s", ewx->base.instance, id, errstr);
			free(errstr);
		}

		free(upceil);
		free(downceil);
		return result;
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	snmp_add_var(pdu, ChannelUplink, STM_OID_LEN, 'u', upceil);
	snmp_add_var(pdu, ChannelDownlink, STM_OID_LEN, 'u', downceil);
    snmp_add_var(pdu, ChannelHalfDuplex, STM_OID_LEN, 'i', halfduplex ? HALFDUPLEX : FULLDUPLEX);
	snmp_add_var(pdu, ChannelStatus, STM_OID_LEN, 'i', ACTIVE);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
	    struct variable_list *vars;
	    for(vars = response->variables; vars; vars = vars->next_variable)
   			print_variable(vars->name, vars->name_length, vars);
#endif
		g->db->pexec(g->db->conn, "UPDATE ewx_stm_channels SET upceil = ?, downceil = ?, halfduplex = ? "
			    "WHERE id = ?", upceil, downceil, halfduplex ? "1" : "NULL", itoa(id));
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm-channels] Modified channel %d", ewx->base.instance, id);
#endif
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot modify channel %d: %s", ewx->base.instance, id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot modify channel %d: %s", ewx->base.instance, id, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	free(upceil);
	free(downceil);

	return result;
}

int del_node(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct host *ht)
{
	struct snmp_pdu 	*pdu, *response;
	char *errstr;
	int status, result = STATUS_ERROR;
	struct host h = *ht;

#ifdef LMS_SNMP_DEBUG
    printf("[DELETE NODE] %d\n", h.id);
#endif
	if(!sh) return result;

	// Create OID
	CustomerStatus[STM_OID_LEN-1] = h.id + ewx->offset;

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	snmp_add_var(pdu, CustomerStatus, STM_OID_LEN, 'i', DESTROY);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
	    struct variable_list *vars;
   		for(vars = response->variables; vars; vars = vars->next_variable)
   			print_variable(vars->name, vars->name_length, vars);
#endif
		g->db->pexec(g->db->conn, "DELETE FROM ewx_stm_nodes WHERE nodeid = ?", itoa(h.id));
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm-channels] Deleted node %s/%s (%05d)",
		    ewx->base.instance, h.ip, h.mac, h.id);
#endif
		(*ht).status = result = DELETED;
	}
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot delete node %s/%s (%05d): %s",
    			    ewx->base.instance, h.ip, h.mac, h.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot delete node %s/%s (%05d): %s",
    			    ewx->base.instance, h.ip, h.mac, h.id, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	return result;
}

int add_node(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct host *ht, int chid)
{
	struct snmp_pdu 	*pdu, *response;
	char *errstr;
	int status, result = STATUS_ERROR;
	struct host h = *ht;

#ifdef LMS_SNMP_DEBUG
    printf("[ADD NODE] %d %s/%s: %d\n", h.id, h.ip, h.mac, chid);
#endif
	if(!sh) return result;

	// Create OIDs
//	CustomerNo[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerPathNo[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerChannelNo[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerIpAddr[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerMacAddr[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerUpMinSpeed[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerUpMaxSpeed[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerDownMinSpeed[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerDownMaxSpeed[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerHalfDuplex[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerStatus[STM_OID_LEN-1] = h.id + ewx->offset;

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_SET);

//	snmp_add_var(pdu, CustomerNo, STM_OID_LEN, 'i', itoa(h.id));
	snmp_add_var(pdu, CustomerPathNo, STM_OID_LEN, 'u', itoa(ewx->path));
	snmp_add_var(pdu, CustomerChannelNo, STM_OID_LEN, 'u', itoa(chid));
	snmp_add_var(pdu, CustomerIpAddr, STM_OID_LEN, 's', h.ip);
	snmp_add_var(pdu, CustomerMacAddr, STM_OID_LEN, 's', h.mac);
	snmp_add_var(pdu, CustomerUpMinSpeed, STM_OID_LEN, 'u', itoa(h.uprate));
	snmp_add_var(pdu, CustomerUpMaxSpeed, STM_OID_LEN, 'u', itoa(h.upceil));
	snmp_add_var(pdu, CustomerDownMinSpeed, STM_OID_LEN, 'u', itoa(h.downrate));
	snmp_add_var(pdu, CustomerDownMaxSpeed, STM_OID_LEN, 'u', itoa(h.downceil));
	if (h.halfduplex)
		snmp_add_var(pdu, CustomerHalfDuplex, STM_OID_LEN, 'i', HALFDUPLEX);
	snmp_add_var(pdu, CustomerStatus, STM_OID_LEN, 'i', CREATEANDGO);


	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
	    struct variable_list *vars;
   		for(vars = response->variables; vars; vars = vars->next_variable)
   			print_variable(vars->name, vars->name_length, vars);
#endif
		char *uprate = strdup(itoa(h.uprate));
		char *upceil = strdup(itoa(h.upceil));
		char *downrate = strdup(itoa(h.downrate));
		char *downceil = strdup(itoa(h.downceil));
		char *halfduplex = strdup(itoa(h.halfduplex));
		char *channelid = strdup(itoa(chid));

		g->db->pexec(g->db->conn, "INSERT INTO ewx_stm_nodes (nodeid, mac, ipaddr, "
		        "channelid, uprate, upceil, downrate, downceil, halfduplex) "
				"VALUES (?, '?', INET_ATON('?'), ?, ?, ?, ?, ?, ?)", 
				itoa(h.id), h.mac, h.ip, chid ? channelid : "NULL", uprate, upceil, downrate, downceil, halfduplex);

		free(uprate);
		free(upceil);
		free(downrate);
		free(downceil);
		free(halfduplex);
		free(channelid);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm-channels] Added node %s/%s (%05d)",
		    ewx->base.instance, h.ip, h.mac, h.id);
#endif
		(*ht).status = result = STATUS_OK;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot add node %s/%s (%05d): %s",
    			    ewx->base.instance, h.ip, h.mac, h.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot add node %s/%s (%05d): %s",
    			    ewx->base.instance, h.ip, h.mac, h.id, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	return result;
}

int update_node(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct host *ht, struct host old)
{
	struct snmp_pdu 	*pdu, *response;
	char *errstr;
	int status, result = STATUS_ERROR;
	struct host h = *ht;

#ifdef LMS_SNMP_DEBUG
    printf("[UPDATE NODE] %d\n", h.id);
#endif
	if(!sh) return result;

	// Create OIDs
//	CustomerNo[STM_OID_LEN-1] = h.id + ewx->offset;
//	CustomerPathNo[STM_OID_LEN-1] = h.id + ewx->offset;
//	CustomerChannelNo[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerIpAddr[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerMacAddr[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerUpMinSpeed[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerUpMaxSpeed[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerDownMinSpeed[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerDownMaxSpeed[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerHalfDuplex[STM_OID_LEN-1] = h.id + ewx->offset;
	CustomerStatus[STM_OID_LEN-1] = h.id + ewx->offset;

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	// NOTINSERVICE we must send in separate packet
        snmp_add_var(pdu, CustomerStatus, STM_OID_LEN, 'i', NOTINSERVICE);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
   		struct variable_list *vars;
   		for(vars = response->variables; vars; vars = vars->next_variable)
   			print_variable(vars->name, vars->name_length, vars);
#endif
	}
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot update node %s/%s (%05d): %s",
    			    ewx->base.instance, h.ip, h.mac, h.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot update node %s/%s (%05d): %s",
    			    ewx->base.instance, h.ip, h.mac, h.id, errstr);
			free(errstr);
		}

		return result;
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_SET);

//	snmp_add_var(pdu, CustomerNo, STM_OID_LEN, 'i', itoa(h.id));
//	snmp_add_var(pdu, CustomerPathNo, STM_OID_LEN, 'u', itoa(ewx->path));
//	snmp_add_var(pdu, CustomerChannelNo, STM_OID_LEN, 'u', itoa(chid));
	if (strcmp(h.ip, old.ip) != 0)
		snmp_add_var(pdu, CustomerIpAddr, STM_OID_LEN, 's', h.ip);
	if (strcmp(h.mac, old.mac) != 0)
		snmp_add_var(pdu, CustomerMacAddr, STM_OID_LEN, 's', h.mac);
	if (h.uprate != old.uprate)
		snmp_add_var(pdu, CustomerUpMinSpeed, STM_OID_LEN, 'u', itoa(h.uprate));
	if (h.upceil != old.upceil)
		snmp_add_var(pdu, CustomerUpMaxSpeed, STM_OID_LEN, 'u', itoa(h.upceil));
	if (h.downrate != old.downrate)
		snmp_add_var(pdu, CustomerDownMinSpeed, STM_OID_LEN, 'u', itoa(h.downrate));
	if (h.downceil != old.downceil)
		snmp_add_var(pdu, CustomerDownMaxSpeed, STM_OID_LEN, 'u', itoa(h.downceil));
	if (h.halfduplex != old.halfduplex) {
		snmp_add_var(pdu, CustomerHalfDuplex, STM_OID_LEN, 'i', h.halfduplex ? HALFDUPLEX : FULLDUPLEX);
	}
	snmp_add_var(pdu, CustomerStatus, STM_OID_LEN, 'i', ACTIVE);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
	    struct variable_list *vars;
   		for(vars = response->variables; vars; vars = vars->next_variable)
   			print_variable(vars->name, vars->name_length, vars);
#endif
		char *uprate = strdup(itoa(h.uprate));
		char *upceil = strdup(itoa(h.upceil));
		char *downrate = strdup(itoa(h.downrate));
		char *downceil = strdup(itoa(h.downceil));
		char *halfduplex = strdup(itoa(h.halfduplex));

		g->db->pexec(g->db->conn, "UPDATE ewx_stm_nodes SET mac = '?', ipaddr = INET_ATON('?'), "
				"uprate = ?, downrate = ?, upceil = ?, downceil = ?, halfduplex = ? "
				"WHERE nodeid = ?",
				h.mac, h.ip, uprate, downrate, upceil, downceil, halfduplex, itoa(h.id));

		free(uprate);
		free(upceil);
		free(downrate);
		free(downceil);
		free(halfduplex);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm-channels] Updated node %s/%s (%05d)",
		    ewx->base.instance, h.ip, h.mac, h.id);
#endif
		(*ht).status = result = STATUS_OK;
	}
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot update node %s/%s (%05d): %s",
    			    ewx->base.instance, h.ip, h.mac, h.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot update node %s/%s (%05d): %s",
    			    ewx->base.instance, h.ip, h.mac, h.id, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	return result;
}

int save_tables(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh)
{
	struct snmp_pdu 	*pdu, *response;
	char *errstr;
	int status, result = STATUS_ERROR;

#ifdef LMS_SNMP_DEBUG
    printf("[SAVE TABLES]\n");
#endif
	if(!sh) return result;

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	snmp_add_var(pdu, CustomersTableSave, OID_LENGTH(CustomersTableSave), 'i', TABLESAVE);
	snmp_add_var(pdu, ChannelsTableSave, OID_LENGTH(ChannelsTableSave), 'i', TABLESAVE);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
#ifdef LMS_SNMP_DEBUG
	    struct variable_list *vars;
   		for(vars = response->variables; vars; vars = vars->next_variable)
   			print_variable(vars->name, vars->name_length, vars);
#endif
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm-channels] Device configuration tables saved", ewx->base.instance);
#endif
		result = STATUS_OK;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot save device configuration tables: %s", ewx->base.instance, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm-channels] ERROR: Cannot save device configuration tables: %s", ewx->base.instance, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	return result;
}
