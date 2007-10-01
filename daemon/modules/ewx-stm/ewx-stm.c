/*
 * LMS version 1.11-cvs
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
#include <math.h>
#include <netinet/in.h>
#include <arpa/inet.h>

#include "lmsd.h"
#include "ewx-stm.h"

int del_channel(GLOBAL *, struct ewx_module*, struct snmp_session*, struct channel*);
int add_channel(GLOBAL *, struct ewx_module*, struct snmp_session*, struct customer*);
int update_channel(GLOBAL *, struct ewx_module*, struct snmp_session*, struct channel*, struct customer*);
int del_node(GLOBAL *, struct ewx_module*, struct snmp_session*, struct host*);
int add_node(GLOBAL *, struct ewx_module*, struct snmp_session*, struct host*, int);
int update_node(GLOBAL *, struct ewx_module*, struct snmp_session*, struct host*);
int save_tables(GLOBAL *, struct ewx_module*, struct snmp_session*);

char * itoa(int i)
{
        static char string[15];
	sprintf(string, "%d", i);
	return string;
}

void reload(GLOBAL *g, struct ewx_module *ewx)
{
	struct snmp_session 	session, *sh=NULL;
	struct snmp_pdu 	*pdu, *response;

	int	pathuplink=0, pathdownlink=0;
	int 	globaluprate=0, globaldownrate=0; 
	int	maxupceil=0, maxdownceil=0;
	int 	status, i, j, k=2, n=2, cc=0, sc=0;
	int	nc=0, anc=0, mnc=0, inc=0;
	char 	*errstr;
	char 	*netnames;
	char	*netname;

	QueryHandle *res;
	
        struct customer *customers = (struct customer *) malloc(sizeof(struct customer));
        struct net *nets = (struct net *) malloc(sizeof(struct net));
        struct net *all_nets = (struct net *) malloc(sizeof(struct net));
        struct net *mac_nets = (struct net *) malloc(sizeof(struct net));
        struct net *ip_nets = (struct net *) malloc(sizeof(struct net));

	if(!ewx->path)
	{
	        syslog(LOG_ERR, "[%s/ewx-stm] Option 'path' not specified", ewx->base.instance);
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
	        syslog(LOG_ERR, "[%s/ewx-stm] SNMP ERROR: %s", ewx->base.instance, errstr);
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

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
		struct variable_list *vars;
		char buf[MAX_OID_LEN];

		for(vars = response->variables; vars; vars = vars->next_variable)
		{
			snprint_objid(buf, MAX_OID_LEN, vars->name, vars->name_length);
		
			if(vars->name[STM_OID_LEN-2]==3)
			{
				snprint_value(buf, MAX_OID_LEN, vars->name, vars->name_length, vars);
				pathuplink = atoi(buf);
			}
			else if(vars->name[STM_OID_LEN-2]==4)
			{
				snprint_value(buf, MAX_OID_LEN, vars->name, vars->name_length, vars);
				pathdownlink = atoi(buf);
			}
		}
	}
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot read path's data. %s", ewx->base.instance, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot read path's data. %s", ewx->base.instance, errstr);
			free(errstr);
		}
		return;
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);	

	snmp_close(sh);

	// If communication works, we can do the job...

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

	// get customers with tariffs rates summaries (ie. channels)
	res = g->db_query(g->conn, 
			"SELECT a.customerid AS id, SUM(uprate) AS uprate, SUM(upceil) AS upceil, "
				"SUM(downrate) AS downrate, SUM(downceil) AS downceil "
			"FROM assignments a "
			"LEFT JOIN tariffs ON (a.tariffid = tariffs.id) "
			"WHERE (datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) "
				"AND EXISTS (SELECT 1 FROM nodeassignments JOIN nodes n ON (n.id = nodeid) " 
					"WHERE a.id = assignmentid AND n.access = 1) "
			"GROUP BY a.customerid");

	for(i=0; i<g->db_nrows(res); i++) 
	{	
		int cid = atoi(g->db_get_data(res,i,"id"));

		int upceil 	= atoi(g->db_get_data(res,i,"upceil"));
		int downceil 	= atoi(g->db_get_data(res,i,"downceil"));

		if(upceil || downceil)
		{ 
			customers = (struct customer *) realloc(customers, (sizeof(struct customer) * (cc+1)));
			customers[cc].id = cid;
			customers[cc].upceil = upceil;
			customers[cc].downceil = downceil;
                        customers[cc].no = 0;
                        customers[cc].downratesum = 0;
			customers[cc].upratesum = 0;
                        customers[cc].hosts = NULL;
			customers[cc].status = UNKNOWN;
			cc++;
		}
	}
	g->db_free(&res);

	if(!cc)
	{
	        syslog(LOG_ERR, "[%s/ewx-stm] Customers table is empty. Exiting.", ewx->base.instance);
		return;
	}

	// hosts
	res = g->db_query(g->conn, 
		"SELECT downrate, downceil, uprate, upceil, mac, chkmac, "
			"nodes.id, ownerid, INET_NTOA(ipaddr) AS ip, halfduplex " 
	    		// subquery: number of enabled nodes in assignment
			",( "
			"SELECT count(*) "
			"FROM nodeassignments "
			"LEFT JOIN nodes ON (nodeid = nodes.id) "
			"WHERE nodes.access = 1 "
			"GROUP BY assignmentid "
			"HAVING assignmentid = a1.assignmentid "
			") AS cnt "
		"FROM nodeassignments a1 "
		"LEFT JOIN assignments ON (assignmentid = assignments.id)"
		"LEFT JOIN tariffs ON (tariffid = tariffs.id) "
		"LEFT JOIN nodes ON (nodeid = nodes.id) "
		"WHERE "
			"(datefrom <= %NOW% OR datefrom = 0) AND (dateto >= %NOW% OR dateto = 0) "
			"AND nodes.access = 1"
	);

	// adding hosts to customers array
	for(i=0; i<g->db_nrows(res); i++)
        {
		int ownerid = atoi(g->db_get_data(res,i,"ownerid"));
        	int hostid = atoi(g->db_get_data(res,i,"id"));
		char *ip = g->db_get_data(res,i,"ip");
		unsigned long inet = inet_addr(ip);

		// looking for customer
		for(j=0; j<cc; j++)
			if(customers[j].id == ownerid)
				break;
		
		if(j == cc) continue; // break loop if customer's not found

		// Networks test
		if(nc)
		{	
			for(n=0; n<nc; n++)
	            		if(nets[n].address == (inet & nets[n].mask))
	                    		break;
		
			if(n == nc) continue;
		}
		
		int cnt 	= atoi(g->db_get_data(res,i,"cnt"));
		int uprate 	= atoi(g->db_get_data(res,i,"uprate"));
		int downrate 	= atoi(g->db_get_data(res,i,"downrate"));
		int upceil 	= atoi(g->db_get_data(res,i,"upceil"));
		int downceil 	= atoi(g->db_get_data(res,i,"downceil"));
		
		// looking for host
		for(k=0; k<customers[j].no; k++)
			if(customers[j].hosts[k].id == hostid)
				break;
		
		if(k == customers[j].no) // host not exists
		{
	        	int dummy_ip = 0;
			int dummy_mac = 0;

			// Networks test for dummy_mac
			if(mnc)
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
			if(inc && !dummy_mac)
			{	
				for(n=0; n<inc; n++)
	            			if(ip_nets[n].address == (inet & ip_nets[n].mask))
	                    			break;
		
				if(n != inc) dummy_ip = 1;
			}

			customers[j].hosts = (struct host *) realloc(customers[j].hosts, (sizeof(struct host) * (k+1)));
			customers[j].hosts[k].id = hostid;
			customers[j].hosts[k].uprate = uprate;
			customers[j].hosts[k].upceil = upceil;
			customers[j].hosts[k].downrate = downrate;
			customers[j].hosts[k].downceil = downceil;
			customers[j].hosts[k].status = UNKNOWN;
			customers[j].hosts[k].halfduplex = atoi(g->db_get_data(res,i,"halfduplex"));
			customers[j].hosts[k].cnt = cnt;
			
			if(!dummy_ip)
				customers[j].hosts[k].ip = strdup(ip);
			else
				customers[j].hosts[k].ip = strdup(DUMMY_IP);
			
			if(!dummy_mac)
				customers[j].hosts[k].mac = strdup(g->db_get_data(res,i,"mac"));
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
			if(customers[j].hosts[k].cnt < cnt) 
				customers[j].hosts[k].cnt = cnt;
		}
		
		globaluprate += uprate;
		globaldownrate += downrate;
		maxupceil = maxupceil < customers[j].upceil ? customers[j].upceil : maxupceil;
		maxdownceil = maxdownceil < customers[j].downceil ? customers[j].downceil : maxdownceil;
	}
	g->db_free(&res);

	// path limits checking
	if(globaluprate>pathuplink || globaldownrate>pathdownlink)
	{
	        syslog(LOG_ERR, "[%s/ewx-stm] Path is too small. Need Uplink: %d, Downlink: %d. Exiting.", ewx->base.instance, globaluprate, globaldownrate);
		return;
	}
	if(maxupceil>pathuplink || maxdownceil>pathdownlink)
	{
	        syslog(LOG_ERR, "[%s/ewx-stm] Path is too small. Need Uplink: %d, Downlink: %d. Exiting.", ewx->base.instance, maxupceil, maxdownceil);
		return;
	}

	// Reading hosts/channels definitions from ewx_stm_* tables
	// NOTE: to re-create device configuration do DELETE FROM ewx_stm_nodes; DELETE FROM ewx_stm_channels;
	res = g->db_query(g->conn, "SELECT nodeid, mac, INET_NTOA(ipaddr) AS ip, channelid, halfduplex, "
					    "n.uprate, n.upceil, n.downrate, n.downceil, customerid, "
					    "c.upceil AS cupceil, c.downceil AS cdownceil "
				    "FROM ewx_stm_nodes n "
				    "LEFT JOIN ewx_stm_channels c ON (c.id = n.channelid) "
				    );

        struct channel *channels = (struct channel *) malloc(sizeof(struct channel));

	// Creating current config array
	for(i=0; i<g->db_nrows(res); i++)
        {
        	int channelid = atoi(g->db_get_data(res,i,"channelid"));
        	int hostid = atoi(g->db_get_data(res,i,"nodeid"));
		char *ip = g->db_get_data(res,i,"ip");
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
		
		// looking for channel
		for(j=0; j<sc; j++)
			if(channels[j].id == channelid)
				break;
		
		if(j == sc)
		{
			channels = (struct channel *) realloc(channels, (sizeof(struct channel) * (sc+1)));
			channels[sc].id = channelid;
			channels[sc].customerid = atoi(g->db_get_data(res,i,"customerid"));
			channels[sc].upceil = atoi(g->db_get_data(res,i,"cupceil"));
			channels[sc].downceil = atoi(g->db_get_data(res,i,"cdownceil"));
                        channels[sc].no = 0;
                        channels[sc].hosts = NULL;
			channels[sc].status = UNKNOWN;
			sc++;
		}

		k = channels[j].no;

		channels[j].hosts = (struct host *) realloc(channels[j].hosts, (sizeof(struct host) * (k+1)));
		channels[j].hosts[k].id = hostid;
		channels[j].hosts[k].uprate = atoi(g->db_get_data(res,i,"uprate"));
		channels[j].hosts[k].upceil = atoi(g->db_get_data(res,i,"upceil"));
		channels[j].hosts[k].downrate = atoi(g->db_get_data(res,i,"downrate"));
		channels[j].hosts[k].downceil = atoi(g->db_get_data(res,i,"downceil"));
		channels[j].hosts[k].ip = strdup(ip);
		channels[j].hosts[k].mac = strdup(g->db_get_data(res,i,"mac"));
		channels[j].hosts[k].halfduplex = atoi(g->db_get_data(res,i,"halfduplex"));
		channels[j].hosts[k].status = UNKNOWN;
		channels[j].hosts[k].cnt = 1; // pole nie jest istotne
		channels[j].no++;
	}
	g->db_free(&res);

	// Open the session again
	sh = snmp_open(&session);

	if(!sh)
	{
	        snmp_error(&session, NULL, NULL, &errstr);
	        syslog(LOG_ERR, "[%s/ewx-stm] SNMP ERROR: %s", ewx->base.instance, errstr);
		free(errstr);
		return;
	}

	// Main loop ****************************************************************
	for(i=0; i<cc; i++)
        {
		int upceil=0, downceil=0, needupdate=0, found=0, x;
		struct customer c = customers[i];

		if(!c.no) continue;

		// Seek channel
		for(x=0; x<sc; x++)
			if(channels[x].customerid == c.id)
				break;

		// Summary hosts limits
		for(k=0; k<c.no; k++)
		{
			upceil += c.hosts[k].upceil;
			downceil += c.hosts[k].downceil;
			
			// decrease node rates if sum of nodes rates
			// is greater than channel's ceil
			if(c.upratesum > c.upceil)
				c.hosts[k].uprate = ceil(c.hosts[k].uprate / c.hosts[k].cnt);
			if(c.downratesum > c.downceil)
				c.hosts[k].downrate = ceil(c.hosts[k].downrate / c.hosts[k].cnt);
		}

		// check that we need to create channel
		if(upceil > c.upceil || downceil > c.downceil)
		{
			// szukamy komputerow, moga nalezec do innego kanalu, dlatego przegladamy wszystkie
			for(j=0; j<sc; j++)
				for(k=0; k<channels[j].no; k++)
					for(n=0; n<c.no; n++)
						if(	(c.hosts[n].id == channels[j].hosts[k].id) ||
							(inet_addr(c.hosts[n].ip) == inet_addr(channels[j].hosts[k].ip) 
								&& inet_addr(c.hosts[n].ip) != inet_addr(DUMMY_IP)) ||
							!(strcmp(c.hosts[n].mac, channels[j].hosts[k].mac)))
						{
							if( // komputer nalezy do innego kanalu
							    ((x && channels[x].customerid != channels[j].customerid) || 
							    channels[j].customerid == 0)
							    || // limity ulegly zmianie
							    (c.hosts[n].uprate != channels[j].hosts[k].uprate ||
							    c.hosts[n].upceil != channels[j].hosts[k].upceil ||
							    c.hosts[n].downrate != channels[j].hosts[k].downrate ||
							    c.hosts[n].downceil != channels[j].hosts[k].downceil)
							    || // zmiana ID
							    (c.hosts[n].id != channels[j].hosts[k].id)
							)
							{
								del_node(g, ewx, sh, &channels[j].hosts[k]);
								needupdate = 1;
								continue;
							}
							
							// kanal sie zgadza, ID i limity tez, sprawdzamy jeszcze adresy i halfduplex
							if(inet_addr(c.hosts[n].ip) != inet_addr(channels[j].hosts[k].ip) || 
							    c.hosts[n].halfduplex != channels[j].hosts[k].halfduplex ||
							    strcmp(c.hosts[n].mac, channels[j].hosts[k].mac) != 0
							    )
							{
								update_node(g, ewx, sh, &c.hosts[n]);
								channels[j].hosts[k].status = STATUS_OK;
							}
							
							// wszystko sie zgadza, zmieniamy status
							channels[j].hosts[k].status = STATUS_OK;
							found++;
						}
			
			if(needupdate || found != c.no)
			{
				// channel exists
				if(x!=sc)
					update_channel(g, ewx, sh, &channels[x], &c);
				else
					add_channel(g, ewx, sh, &c);
			}
		}
		else // channel is not needed
		{
			// petla po wszystkich komputerach klienta
			for(k=0; k<c.no; k++)
			{
				found = 0;
				// looking for node in whole old config...
				for(j=0; j<sc; j++)
					if(channels[j].status == UNKNOWN)
						for(n=0; n<channels[j].no; n++)
							if(channels[j].hosts[n].status == UNKNOWN)
							{
								if(c.hosts[k].id == channels[j].hosts[n].id ||
								    (inet_addr(c.hosts[k].ip) == inet_addr(channels[j].hosts[n].ip)
									    && inet_addr(c.hosts[k].ip) != inet_addr(DUMMY_IP)) ||
								    !strcmp(c.hosts[k].mac, channels[j].hosts[n].mac)) 
								{
									// komputer byl w kanale
									if( (channels[j].customerid != 0) 
									    || // lub zmiana ID
									    (c.hosts[k].id != channels[j].hosts[n].id))
									{
										del_node(g, ewx, sh, &channels[j].hosts[n]);
										needupdate = 1;
										continue;
									}
							
									// jesli komputer nie byl w kanale, mozemy go zaktualizowac bez usuwania
									if( (channels[j].customerid == 0 &&
									    (c.hosts[k].uprate != channels[j].hosts[n].uprate ||
									    c.hosts[k].upceil != channels[j].hosts[n].upceil ||
									    c.hosts[k].downrate != channels[j].hosts[n].downrate ||
									    c.hosts[k].downceil != channels[j].hosts[n].downceil))
									    ||
									    (inet_addr(c.hosts[k].ip) != inet_addr(channels[j].hosts[n].ip) ||
									    c.hosts[k].halfduplex != channels[j].hosts[n].halfduplex ||
									    strcmp(c.hosts[k].mac, channels[j].hosts[n].mac) != 0))
									{
										update_node(g, ewx, sh, &c.hosts[k]);
									}

									// wszystko sie zgadza, zmieniamy status
									channels[j].hosts[n].status = STATUS_OK;
									found++;
								}
							}
							
				if(needupdate || !found)
				{
					// delete existing channel 
					if(x!=sc && channels[x].status == UNKNOWN)
						del_channel(g, ewx, sh, &channels[x]);
	
					// when all matching nodes were deleted, we can
					// add node with new config
					add_node(g, ewx, sh, &c.hosts[k], 0);
				}
			}
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
					del_node(g, ewx, sh, &channels[i].hosts[j]);
					deleted++;
				}

			if(deleted == channels[i].no && channels[i].customerid != 0)
				del_channel(g, ewx, sh, &channels[i]);
		}
	
	// Save device configuration changes
	save_tables(g, ewx, sh);

	snmp_close(sh);

        for(i=0; i<sc; i++)
	{
		for(j=0; j<channels[i].no; j++)
		{
			free(channels[i].hosts[j].ip);
			free(channels[i].hosts[j].mac);
		}
	        free(channels[i].hosts);
	}
        for(i=0; i<cc; i++)
	{
		for(j=0; j<customers[i].no; j++)
		{
			free(customers[i].hosts[j].ip);
			free(customers[i].hosts[j].mac);
		}
	        free(customers[i].hosts);
	}

        free(channels);
        free(customers);

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
	syslog(LOG_INFO, "DEBUG: [%s/ewx-stm] reloaded", ewx->base.instance);
#endif
	free(ewx->community);
	free(ewx->host);
	free(ewx->networks);
	free(ewx->dummy_mac_networks);
	free(ewx->dummy_ip_networks);
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
	ewx->dummy_mac_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "dummy_mac_networks", ""));
	ewx->dummy_ip_networks = strdup(g->config_getstring(ewx->base.ini, ewx->base.instance, "dummy_ip_networks", ""));

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
	syslog(LOG_INFO,"DEBUG: [%s/ewx-stm] initialized", ewx->base.instance);
#endif	
	return(ewx);
}

int del_channel(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct channel *ch)
{
	struct snmp_pdu 	*pdu, *response;
	char *errstr;
	int i, status, result = STATUS_ERROR;
	struct channel c = *ch;

//printf("[DELETE CHANNEL] %d\n", c.id);

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
//		struct variable_list 	*vars;
//    		for(vars = response->variables; vars; vars = vars->next_variable)
//    			print_variable(vars->name, vars->name_length, vars);

		g->db_pexec(g->conn, "DELETE FROM ewx_stm_channels WHERE id = ?", itoa(c.id));
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm] Deleted channel %d", ewx->base.instance, c.id);
#endif
		(*ch).status = result = DELETED;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot delete channel %d: %s", ewx->base.instance, c.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot delete channel %d: %s", ewx->base.instance, c.id, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	return result;
}

int add_channel(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct customer *cust)
{
	struct snmp_pdu	*pdu, *response;
	char *errstr;
	int i, status, channelid, result = STATUS_ERROR;
	QueryHandle *res;
	struct customer c = *cust;
	
	char *upceil = strdup(itoa(c.upceil));
	char *downceil = strdup(itoa(c.downceil));

//printf("[ADD CHANNEL] %d\n", c.id);

	if(!sh) return result;

	// Adding channel to database
	g->db_pexec(g->conn, "INSERT INTO ewx_stm_channels (customerid, upceil, downceil) "
			    "VALUES(?, ?, ?)", itoa(c.id), upceil, downceil);

	res = g->db_pquery(g->conn, "SELECT id FROM ewx_stm_channels WHERE customerid = ?", itoa(c.id));

	channelid = atoi(g->db_get_data(res, 0, "id"));
	g->db_free(&res);

	// Dodac kod pilnujacy aby channelid nie przekroczyl 99999
	if(channelid > MAX_ID)
	{
                int lastid = 0;
		int newid = 0;
		int row = 0;
		
		while(newid==0)
		{
			res = g->db_pquery(g->conn, "SELECT id FROM nodes ORDER BY id LIMIT 100 OFFSET ?", itoa(row));
		
			// break loop when there're no rows
			if(!g->db_nrows(res)) 
			{
				g->db_free(&res);
				break;
			}
			
			for(i=0; i<g->db_nrows(res); i++)
    			{
        			int cid = atoi(g->db_get_data(res,i,"id"));
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
			g->db_free(&res);
		}
		
		if(newid)
		{
			char *nid = strdup(itoa(newid));
			g->db_pexec(g->conn, "UPDATE ewx_stm_channels SET id = ? WHERE id = ?", itoa(channelid), nid);
		        free(nid);
			channelid = newid;
		}
		else
		{
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot add channel %d. ID is too big.", ewx->base.instance, channelid);
			return result;		
		}
	}

	// Create OID
	ChannelStatus[STM_OID_LEN-1] = channelid + ewx->offset;
	ChannelPathNo[STM_OID_LEN-1] = channelid + ewx->offset;
	ChannelUplink[STM_OID_LEN-1] = channelid + ewx->offset;
	ChannelDownlink[STM_OID_LEN-1] = channelid + ewx->offset;
//	ChannelHalfDuplex[STM_OID_LEN-1] = channelid + ewx->offset;

	// Create the PDU 
	pdu = snmp_pdu_create(SNMP_MSG_SET);

	snmp_add_var(pdu, ChannelPathNo, STM_OID_LEN, 'u', itoa(ewx->path));
	snmp_add_var(pdu, ChannelUplink, STM_OID_LEN, 'u', upceil);
	snmp_add_var(pdu, ChannelDownlink, STM_OID_LEN, 'u', downceil);
//	snmp_add_var(pdu, ChannelHalfDuplex, STM_OID_LEN, 'i', "2");
	snmp_add_var(pdu, ChannelStatus, STM_OID_LEN, 'i', CREATEANDGO);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
//		struct variable_list 	*vars;
//    		for(vars = response->variables; vars; vars = vars->next_variable)
//    			print_variable(vars->name, vars->name_length, vars);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm] Added channel %d", ewx->base.instance, channelid);
#endif
		(*cust).status = result = STATUS_OK;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot add channel %d: %s", ewx->base.instance, channelid, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot add channel %d: %s", ewx->base.instance, channelid, errstr);
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

int update_channel(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct channel *ch, struct customer *cust)
{
	struct snmp_pdu 	*pdu, *response;
	char *errstr;
	int i, status, result = STATUS_ERROR;

	struct channel c = *ch;
	struct customer cu = *cust;

	char *upceil = strdup(itoa(cu.upceil));
	char *downceil = strdup(itoa(cu.downceil));

//printf("[UPDATE CHANNEL] %d\n", c.id);

	// First we must delete all nodes in channel
	for(i=0; i<c.no; i++)
		if(c.hosts[i].status != DELETED)
			del_node(g, ewx, sh, &c.hosts[i]);

	if(!sh) return result;

	// Create OIDs
	ChannelUplink[STM_OID_LEN-1] = c.id + ewx->offset;
	ChannelDownlink[STM_OID_LEN-1] = c.id + ewx->offset;
//	ChannelPathNo[STM_OID_LEN-1] = c.id + ewx->offset;
//	ChannelHalfDuplex[STM_OID_LEN-1] = c.id + ewx->offset;
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
//		struct variable_list 	*vars;
//    		for(vars = response->variables; vars; vars = vars->next_variable)
//    			print_variable(vars->name, vars->name_length, vars);
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot update channel %d: %s", ewx->base.instance, c.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot update channel %d: %s", ewx->base.instance, c.id, errstr);
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
//	snmp_add_var(pdu, ChannelPathNo, STM_OID_LEN, 'u', itoa(ewx->path));
//	snmp_add_var(pdu, ChannelHalfDuplex, STM_OID_LEN, 'i', "2");
	snmp_add_var(pdu, ChannelStatus, STM_OID_LEN, 'i', ACTIVE);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
//		struct variable_list 	*vars;
//    		for(vars = response->variables; vars; vars = vars->next_variable)
//    			print_variable(vars->name, vars->name_length, vars);
		
		g->db_pexec(g->conn, "UPDATE ewx_stm_channels SET upceil = ?, downceil = ? "
			    "WHERE id = ?", upceil, downceil, itoa(c.id));
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm] Updated channel %d", ewx->base.instance, c.id);
#endif
		(*ch).status = (*cust).status = result = STATUS_OK;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot update channel %d: %s", ewx->base.instance, c.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot update channel %d: %s", ewx->base.instance, c.id, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	// Adding nodes to the channel
	if(result == STATUS_OK)
		for(i=0; i<cu.no; i++)
		{
			add_node(g, ewx, sh, &cu.hosts[i], c.id + ewx->offset);
		}

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

//printf("[DELETE NODE] %d\n", h.id);

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
//		struct variable_list 	*vars;
//    		for(vars = response->variables; vars; vars = vars->next_variable)
//    			print_variable(vars->name, vars->name_length, vars);

		g->db_pexec(g->conn, "DELETE FROM ewx_stm_nodes WHERE nodeid = ?", itoa(h.id));
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm] Deleted node %s (%05d)", ewx->base.instance, h.ip, h.id);
#endif
		(*ht).status = result = DELETED;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot delete node %s (%05d): %s", ewx->base.instance, h.ip, h.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot delete node %s (%05d): %s", ewx->base.instance, h.ip, h.id, errstr);
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

//printf("[ADD NODE] %d %s-%s\n", h.id, h.mac, h.ip);

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
	snmp_add_var(pdu, CustomerChannelNo, STM_OID_LEN, 'u', itoa(chid) + ewx->offset);
	snmp_add_var(pdu, CustomerIpAddr, STM_OID_LEN, 's', h.ip);
	snmp_add_var(pdu, CustomerMacAddr, STM_OID_LEN, 's', h.mac);
	snmp_add_var(pdu, CustomerUpMinSpeed, STM_OID_LEN, 'u', itoa(h.uprate));
	snmp_add_var(pdu, CustomerUpMaxSpeed, STM_OID_LEN, 'u', itoa(h.upceil));
	snmp_add_var(pdu, CustomerDownMinSpeed, STM_OID_LEN, 'u', itoa(h.downrate));
	snmp_add_var(pdu, CustomerDownMaxSpeed, STM_OID_LEN, 'u', itoa(h.downceil));
	if(h.halfduplex)
		snmp_add_var(pdu, CustomerHalfDuplex, STM_OID_LEN, 'i', itoa(h.halfduplex));
	snmp_add_var(pdu, CustomerStatus, STM_OID_LEN, 'i', CREATEANDGO);


	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
//		struct variable_list 	*vars;
//    		for(vars = response->variables; vars; vars = vars->next_variable)
//    			print_variable(vars->name, vars->name_length, vars);

		char *uprate = strdup(itoa(h.uprate));
		char *upceil = strdup(itoa(h.upceil));
		char *downrate = strdup(itoa(h.downrate));
		char *downceil = strdup(itoa(h.downceil));
		char *halfduplex = strdup(itoa(h.halfduplex));
		char *channelid = strdup(itoa(chid));

		g->db_pexec(g->conn, "INSERT INTO ewx_stm_nodes (nodeid, mac, ipaddr, channelid, uprate, upceil, downrate, downceil, halfduplex) "
				    "VALUES (?, '?', INET_ATON('?'), ?, ?, ?, ?, ?, ?)", 
				    itoa(h.id), h.mac, h.ip, channelid, uprate, upceil, downrate, downceil, halfduplex);

		free(uprate);
		free(upceil);
		free(downrate);
		free(downceil);
		free(halfduplex);
		free(channelid);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm] Added node %s (%05d)", ewx->base.instance, h.ip, h.id);
#endif
		(*ht).status = result = STATUS_OK;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot add node %s (%05d): %s", ewx->base.instance, h.ip, h.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot add node %s (%05d): %s", ewx->base.instance, h.ip, h.id, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	return result;
}

int update_node(GLOBAL *g, struct ewx_module *ewx, struct snmp_session *sh, struct host *ht)
{
	struct snmp_pdu 	*pdu, *response;
	char *errstr;
	int status, result = STATUS_ERROR;
	struct host h = *ht;

//printf("[UPDATE NODE] %d\n", h.id);

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
//		struct variable_list 	*vars;
//    		for(vars = response->variables; vars; vars = vars->next_variable)
//    			print_variable(vars->name, vars->name_length, vars);
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot update node %s (%05d): %s", ewx->base.instance, h.ip, h.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot update node %s (%05d): %s", ewx->base.instance, h.ip, h.id, errstr);
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
	snmp_add_var(pdu, CustomerIpAddr, STM_OID_LEN, 's', h.ip);
	snmp_add_var(pdu, CustomerMacAddr, STM_OID_LEN, 's', h.mac);
	snmp_add_var(pdu, CustomerUpMinSpeed, STM_OID_LEN, 'u', itoa(h.uprate));
	snmp_add_var(pdu, CustomerUpMaxSpeed, STM_OID_LEN, 'u', itoa(h.upceil));
	snmp_add_var(pdu, CustomerDownMinSpeed, STM_OID_LEN, 'u', itoa(h.downrate));
	snmp_add_var(pdu, CustomerDownMaxSpeed, STM_OID_LEN, 'u', itoa(h.downceil));
	if(h.halfduplex)
		snmp_add_var(pdu, CustomerHalfDuplex, STM_OID_LEN, 'i', itoa(h.halfduplex));
	else
		snmp_add_var(pdu, CustomerHalfDuplex, STM_OID_LEN, 'i', "2"); // full duplex
	snmp_add_var(pdu, CustomerStatus, STM_OID_LEN, 'i', ACTIVE);

	// Send the Request out
	status = snmp_synch_response(sh, pdu, &response);

	// Process the response
	if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR)
	{
//		struct variable_list 	*vars;
//    		for(vars = response->variables; vars; vars = vars->next_variable)
//    			print_variable(vars->name, vars->name_length, vars);

		char *uprate = strdup(itoa(h.uprate));
		char *upceil = strdup(itoa(h.upceil));
		char *downrate = strdup(itoa(h.downrate));
		char *downceil = strdup(itoa(h.downceil));
		char *halfduplex = strdup(itoa(h.halfduplex));

		g->db_pexec(g->conn, "UPDATE ewx_stm_nodes SET mac = '?', ipaddr = INET_ATON('?'), "
				"uprate = ?, downrate = ?, upceil = ?, downceil = ?, halfduplex = ? "
				"WHERE nodeid = ?",
				h.mac, h.ip, uprate, downrate, upceil, downceil, halfduplex, itoa(h.id));

		free(uprate);
		free(upceil);
		free(downrate);
		free(downceil);
		free(halfduplex);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm] Updated node %s (%05d)", ewx->base.instance, h.ip, h.id);
#endif
		(*ht).status = result = STATUS_OK;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot update node %s (%05d): %s", ewx->base.instance, h.ip, h.id, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot update node %s (%05d): %s", ewx->base.instance, h.ip, h.id, errstr);
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

//printf("[SAVE TABLES]\n");

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
//		struct variable_list 	*vars;
//    		for(vars = response->variables; vars; vars = vars->next_variable)
//    			print_variable(vars->name, vars->name_length, vars);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ewx-stm] Device configuration tables saved", ewx->base.instance);
#endif
		result = STATUS_OK;
	} 
	else // failure
	{
		if(status == STAT_SUCCESS)
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot save device configuration tables: %s", ewx->base.instance, snmp_errstring(response->errstat));
		else
		{
			snmp_error(sh, NULL, NULL, &errstr);
    			syslog(LOG_ERR, "[%s/ewx-stm] ERROR: Cannot save device configuration tables: %s", ewx->base.instance, errstr);
			free(errstr);
		}
	}

	// Clean up
	if(response)
		snmp_free_pdu(response);

	return result;
}
