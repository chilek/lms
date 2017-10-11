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
#include "hostfile.h"

char * itoha(int i)
{
    static char string[8];
	sprintf(string, "%02X", i);
	return string;
}

void addrule(GLOBAL *g, FILE *fh, char *rule, struct host h)
{
	unsigned long inet_pub = inet_addr(h.ip_pub);
	char *s = strdup(rule);

	g->str_replace(&s, "%customer", h.customer);
	g->str_replace(&s, "%cid", h.cid);
	g->str_replace(&s, "%maskpub", inet_pub ? inet_ntoa(inet_makeaddr(htonl(h.pubnet.mask),0)) : "");
	g->str_replace(&s, "%prefixpub", inet_pub ? h.pubnet.prefix : "");
	g->str_replace(&s, "%addrpub", inet_pub ? inet_ntoa(inet_makeaddr(htonl(h.pubnet.address),0)) : "");
	g->str_replace(&s, "%domainpub", inet_pub ? h.pubnet.domain : "");
	g->str_replace(&s, "%netpub", inet_pub ? h.pubnet.name : "");
	g->str_replace(&s, "%ifpub", inet_pub ? h.pubnet.interface : "");
	g->str_replace(&s, "%gwpub", inet_pub ? h.pubnet.gateway : "");
	g->str_replace(&s, "%dns2pub", inet_pub ? h.pubnet.dns2 : "");
	g->str_replace(&s, "%dnspub", inet_pub ? h.pubnet.dns : "");
	g->str_replace(&s, "%winspub", inet_pub ? h.pubnet.wins : "");
	g->str_replace(&s, "%dhcpspub", inet_pub ? h.pubnet.dhcpstart : "");
	g->str_replace(&s, "%dhcpepub", inet_pub ? h.pubnet.dhcpend : "");
	g->str_replace(&s, "%prefix", h.net.prefix);
	g->str_replace(&s, "%mask", inet_ntoa(inet_makeaddr(htonl(h.net.mask),0)));
	g->str_replace(&s, "%addr", inet_ntoa(inet_makeaddr(htonl(h.net.address),0)));
	g->str_replace(&s, "%domain", h.net.domain);
	g->str_replace(&s, "%net", h.net.name);
	g->str_replace(&s, "%if", h.net.interface);
	g->str_replace(&s, "%gw", h.net.gateway);
	g->str_replace(&s, "%dns2", h.net.dns2);
	g->str_replace(&s, "%dns", h.net.dns);
	g->str_replace(&s, "%wins", h.net.wins);
	g->str_replace(&s, "%dhcps", h.net.dhcpstart);
	g->str_replace(&s, "%dhcpe", h.net.dhcpend);
	g->str_replace(&s, "%info", h.info);
	g->str_replace(&s, "%ipub", h.ip_pub);
	g->str_replace(&s, "%id", h.id);
	g->str_replace(&s, "%i16pub", h.i16_pub);
	g->str_replace(&s, "%i16", h.i16);
	g->str_replace(&s, "%i", h.ip);
	g->str_replace(&s, "%ms", h.macs);
	g->str_replace(&s, "%m", h.mac ? h.mac : "00:00:00:00:00:00");
	g->str_replace(&s, "%n", h.name);
	g->str_replace(&s, "%l", h.location);
	g->str_replace(&s, "%devl", h.devlocation);
	g->str_replace(&s, "%port", h.port);
	g->str_replace(&s, "%p", h.passwd);

	fprintf(fh, "%s", s);
	free(s);
}

void reload(GLOBAL *g, struct hostfile_module *hm)
{
	FILE *fh;
	QueryHandle *res;
	char *query;
	int i, j, k, nc=0, n=2, en=2, c=2, ec=2, ng=2, eng=2;

	struct net *networks = (struct net *) malloc(sizeof(struct net));

	char *nets = strdup("AND EXISTS (SELECT 1 FROM networks net "
				"WHERE (%nets) "
	                	"AND ((n.ipaddr > net.address AND n.ipaddr < broadcast(net.address, inet_aton(net.mask))) "
				"OR (%pubip > net.address AND %pubip < broadcast(net.address, inet_aton(net.mask)))) "
				")");

	char *netnames = strdup(hm->networks);
	char *netname = strdup(netnames);
	char *netsql = strdup("");

	char *enets = strdup("AND NOT EXISTS (SELECT 1 FROM networks net "
				"WHERE (%nets) "
	                	"AND ((n.ipaddr > net.address AND n.ipaddr < broadcast(net.address, inet_aton(net.mask))) "
				"OR (%pubip > net.address AND %pubip < broadcast(net.address, inet_aton(net.mask)))) "
				")");

	char *enetnames = strdup(hm->excluded_networks);
	char *enetname = strdup(enetnames);
	char *enetsql = strdup("");

	char *groups = strdup("AND EXISTS (SELECT 1 FROM customergroups g, customerassignments a "
				"WHERE a.customerid = n.ownerid "
				"AND g.id = a.customergroupid "
				"AND (%groups)) ");

	char *groupnames = strdup(hm->customergroups);
	char *groupname = strdup(groupnames);
	char *groupsql = strdup("");

	char *egroups = strdup("AND NOT EXISTS (SELECT 1 FROM customergroups g, customerassignments a "
				"WHERE a.customerid = n.ownerid "
				"AND g.id = a.customergroupid "
				"AND (%groups)) ");

	char *egroupnames = strdup(hm->excluded_customergroups);
	char *egroupname = strdup(egroupnames);
	char *egroupsql = strdup("");

	char *ngroups = strdup("AND EXISTS (SELECT 1 FROM nodegroups g, nodegroupassignments na "
				"WHERE na.nodeid = n.id "
				"AND g.id = na.nodegroupid "
				"AND (%groups)) ");

	char *ngroupnames = strdup(hm->nodegroups);
	char *ngroupname = strdup(ngroupnames);
	char *ngroupsql = strdup("");

	char *engroups = strdup("AND NOT EXISTS (SELECT 1 FROM nodegroups g, nodegroupassignments na "
				"WHERE na.nodeid = n.id "
				"AND g.id = na.nodegroupid "
				"AND (%groups)) ");

	char *engroupnames = strdup(hm->excluded_nodegroups);
	char *engroupname = strdup(engroupnames);
	char *engroupsql = strdup("");

	while( n>1 )
	{
    		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

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
		g->str_replace(&nets, "%nets", netsql);

	while( en>1 )
	{
    		en = sscanf(enetnames, "%s %[._a-zA-Z0-9- ]", enetname, enetnames);

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
		g->str_replace(&enets, "%nets", enetsql);

	while( c>1 )
	{
		c = sscanf(groupnames, "%s %[._a-zA-Z0-9- ]", groupname, groupnames);

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

	while( ec>1 )
	{
		ec = sscanf(egroupnames, "%s %[._a-zA-Z0-9- ]", egroupname, egroupnames);

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
		g->str_replace(&egroups, "%groups", egroupsql);

	while( ng>1 )
	{
		ng = sscanf(ngroupnames, "%s %[._a-zA-Z0-9- ]", ngroupname, ngroupnames);

		if( strlen(ngroupname) )
		{
			ngroupsql = realloc(ngroupsql, sizeof(char *) * (strlen(ngroupsql) + strlen(ngroupname) + 30));
			if(strlen(ngroupsql))
				strcat(ngroupsql, " OR UPPER(g.name) = UPPER('");
			else
				strcat(ngroupsql, "UPPER(g.name) = UPPER('");

			strcat(ngroupsql, ngroupname);
			strcat(ngroupsql, "')");
		}
	}
	free(ngroupname); free(ngroupnames);

	if(strlen(ngroupsql))
		g->str_replace(&ngroups, "%groups", ngroupsql);

	while( eng>1 )
	{
		eng = sscanf(engroupnames, "%s %[._a-zA-Z0-9- ]", engroupname, engroupnames);

		if( strlen(engroupname) )
		{
			engroupsql = realloc(engroupsql, sizeof(char *) * (strlen(engroupsql) + strlen(engroupname) + 30));
			if(strlen(engroupsql))
				strcat(engroupsql, " OR UPPER(g.name) = UPPER('");
			else
				strcat(engroupsql, "UPPER(g.name) = UPPER('");

			strcat(engroupsql, engroupname);
			strcat(engroupsql, "')");
		}
	}
	free(engroupname); free(engroupnames);

	if(strlen(engroupsql))
		g->str_replace(&engroups, "%groups", engroupsql);

	// all networks data
	res = g->db->query(g->db->conn, "SELECT name, domain, address, inet_aton(mask) AS mask, "
				"mask2prefix(inet_aton(mask)) AS prefix, dhcpstart, dhcpend, "
				"interface, gateway, dns, dns2, wins FROM networks");

	for(nc=0; nc<g->db->nrows(res); nc++)
	{
		networks = (struct net*) realloc(networks, (sizeof(struct net) * (nc+1)));
		networks[nc].name = strdup(g->db->get_data(res,nc,"name"));
		networks[nc].domain = strdup(g->db->get_data(res,nc,"domain"));
		networks[nc].interface = strdup(g->db->get_data(res,nc,"interface"));
		networks[nc].gateway = strdup(g->db->get_data(res,nc,"gateway"));
		networks[nc].dns = strdup(g->db->get_data(res,nc,"dns"));
		networks[nc].dns2 = strdup(g->db->get_data(res,nc,"dns2"));
		networks[nc].wins = strdup(g->db->get_data(res,nc,"wins"));
		networks[nc].prefix = strdup(g->db->get_data(res,nc,"prefix"));
		networks[nc].dhcpstart = strdup(g->db->get_data(res,nc,"dhcpstart"));
		networks[nc].dhcpend = strdup(g->db->get_data(res,nc,"dhcpend"));
		networks[nc].address = inet_addr(g->db->get_data(res,nc,"address"));
		networks[nc].mask = inet_addr(g->db->get_data(res,nc,"mask"));
	}
	g->db->free(&res);

	fh = fopen(hm->file, "w");
	if(fh)
	{
		fprintf(fh, "%s", hm->prefix);

		if(hm->share_netdev_pubip && !hm->skip_dev_ips)
			query = strdup(
				"SELECT n.id, LOWER(n.name) AS name, n.mac, INET_NTOA(n.ipaddr) AS ip, "
				"(CASE WHEN n.ipaddr_pub != 0 THEN INET_NTOA(n.ipaddr_pub) "
					"ELSE INET_NTOA(COALESCE(s.ipaddr_pub, 0)) END) AS ip_pub, " 
				"n.port, n.passwd, n.access, n.info, n.warning, n.location, "
				"%devloc AS devlocation %custcols"
				"FROM %nodes n "
				"LEFT JOIN (SELECT netdev, MIN(ipaddr_pub) AS ipaddr_pub "
					"FROM nodes "
					"WHERE ownerid IS NULL AND ipaddr_pub != 0 AND netdev IS NOT NULL "
					"GROUP BY netdev "
				") s ON (s.netdev = n.netdev AND n.ownerid IS NULL) "
				"%custjoin"
				"%devjoin"
				"WHERE %where "
				"%nets %enets %groups %egroups %ngroups %engroups"
				"ORDER BY ipaddr");
		else
			query = strdup(
				"SELECT n.id, LOWER(n.name) AS name, n.mac, INET_NTOA(n.ipaddr) AS ip, "
				"INET_NTOA(n.ipaddr_pub) AS ip_pub, n.passwd, n.access, n.info, n.warning, "
				"n.port, n.location, %devloc AS devlocation %custcols"
				"FROM %nodes n "
				"%custjoin"
				"%devjoin"
				"WHERE %where "
				"%nets %enets %groups %egroups %ngroups %engroups"
				"ORDER BY ipaddr");

		if(hm->skip_dev_ips)
			g->str_replace(&query, "%where", "n.ownerid IS NOT NULL");
		else if(hm->skip_host_ips)
			g->str_replace(&query, "%where", "n.ownerid IS NULL");
		else
			g->str_replace(&query, "%where", "1 = 1");

		g->str_replace(&query, "%nodes", hm->multi_mac ? "vmacs" : "vnodes");
		g->str_replace(&query, "%nets", strlen(netsql) ? nets : "");
		g->str_replace(&query, "%enets", strlen(enetsql) ? enets : "");
		g->str_replace(&query, "%groups", strlen(groupsql) ? groups : "");
		g->str_replace(&query, "%egroups", strlen(egroupsql) ? egroups : "");
		g->str_replace(&query, "%ngroups", strlen(ngroupsql) ? ngroups : "");
		g->str_replace(&query, "%engroups", strlen(engroupsql) ? engroups : "");
		g->str_replace(&query, "%custjoin", hm->join_customers ?
			"LEFT JOIN customers c ON (c.id = n.ownerid) " : "");
		g->str_replace(&query, "%devjoin", hm->join_devices ? 
			"LEFT JOIN netdevices d ON (d.id = n.netdev) LEFT JOIN vaddresses va ON va.id = d.address_id " : "");
		g->str_replace(&query, "%devloc", hm->join_devices ? "va.location" : "''");
		g->str_replace(&query, "%custcols", hm->join_customers ?
			", c.id AS cid, TRIM(%cfullname) AS customer " : "");

		char * cfullname = g->db->concat(3, "UPPER(c.lastname)", "' '", "c.name");
		g->str_replace(&query, "%cfullname", cfullname);
		free(cfullname);

		g->str_replace(&query, "%pubip", hm->share_netdev_pubip && !hm->skip_dev_ips ? 
			"(CASE WHEN n.ipaddr_pub != 0 THEN n.ipaddr_pub "
				"ELSE COALESCE(s.ipaddr_pub, 0) END)" : "n.ipaddr_pub");

		res = g->db->query(g->db->conn, query);

		for(i=0; i<g->db->nrows(res); i++)
		{
			unsigned long inet, inet_pub;
			struct host h;
			char *pattern, *mac;

			h.ip 		= g->db->get_data(res,i,"ip");
			h.ip_pub 	= g->db->get_data(res,i,"ip_pub");
			inet 		= inet_addr(h.ip);
			inet_pub 	= inet_addr(h.ip_pub);

			// networks
			for(j=0; j<nc; j++)
				if(networks[j].address == (inet & networks[j].mask))
					break;

			h.net 		= networks[j];
			// initialize pubnet, we'll overwrite it if node has public IP
			h.pubnet	= networks[j];

			if(inet_pub)
			{
				for(k=0; k<nc; k++)
					if(networks[k].address == (inet_pub & networks[k].mask))
					{
						h.pubnet = networks[k];
						break;
					}
			}

			h.access 	= g->db->get_data(res,i,"access");
	    	h.warning	= g->db->get_data(res,i,"warning");
			h.name 		= g->db->get_data(res,i,"name");
			h.info 		= g->db->get_data(res,i,"info");
			h.passwd 	= g->db->get_data(res,i,"passwd");
			h.id  		= g->db->get_data(res,i,"id");
			h.macs 		= g->db->get_data(res,i,"mac");
			h.port 		= g->db->get_data(res,i,"port");
			h.location 	= g->db->get_data(res,i,"location");
			h.devlocation 	= g->db->get_data(res,i,"devlocation");
			h.customer	= hm->join_customers ? g->db->get_data(res,i,"customer") : "";
			h.cid  		= hm->join_customers ? g->db->get_data(res,i,"cid") : "0";
			// IP's last octet in hex
       		h.i16 		= strdup(itoha((ntohl(inet) & 0xff)));
			h.i16_pub 	= strdup(inet_pub ? itoha((ntohl(inet_pub) & 0xff)) : "");

            // get first mac from the list
            mac = strdup(h.macs);
            if (!hm->multi_mac) {
                mac = strtok(mac, ",");
            }
            h.mac = mac;

            // parse rules
			addrule(g, fh, hm->host_prefix, h);

			if(*h.access == '1')
				pattern = inet_pub && hm->pub_replace ? hm->grant_pub : hm->grant;
			else
				pattern = inet_pub && hm->pub_replace ? hm->deny_pub : hm->deny;

			if(*h.warning == '1' && hm->warn_replace)
				pattern = inet_pub ? hm->warn_pub : hm->warn;

			addrule(g, fh, pattern, h);

			if(!hm->warn_replace && *h.warning == '1' && (!hm->pub_replace || !inet_pub))
			{
				addrule(g, fh, hm->warn, h);
			}

			if(!hm->pub_replace && inet_pub)
			{
				pattern = *h.access == '1' ? hm->grant_pub : hm->deny_pub;
				if(*h.warning == '1' && hm->warn_replace)
					pattern = hm->warn_pub;

		    	addrule(g, fh, pattern, h);
			}

			if(!hm->warn_replace && *h.warning == '1' && inet_pub)
			{
				addrule(g, fh, hm->warn_pub, h);
			}

			addrule(g, fh, hm->host_append, h);

            free(mac);
			free(h.i16);
			free(h.i16_pub);
		}

		fprintf(fh, "%s", hm->append);

		g->db->free(&res);
		free(query);
		fclose(fh);

		system(hm->command);
#ifdef DEBUG1
		syslog(LOG_INFO,"DEBUG: [%s/hostfile] reloaded",hm->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/hostfile] Unable to write a temporary file '%s'", hm->base.instance, hm->file);

	for(i=0;i<nc;i++)
	{
		free(networks[i].name);
		free(networks[i].domain);
		free(networks[i].interface);
		free(networks[i].gateway);
		free(networks[i].dns);
		free(networks[i].dns2);
		free(networks[i].wins);
		free(networks[i].prefix);
		free(networks[i].dhcpstart);
		free(networks[i].dhcpend);
	}
	free(networks);

	free(nets);
	free(groups);
	free(ngroups);
	free(enets);
	free(egroups);
	free(engroups);
	free(netsql);
	free(groupsql);
	free(ngroupsql);
	free(enetsql);
	free(egroupsql);
	free(engroupsql);

	free(hm->prefix);
	free(hm->append);
	free(hm->host_prefix);
	free(hm->host_append);
	free(hm->grant);
	free(hm->deny);
	free(hm->warn);
	free(hm->warn_pub);
	free(hm->grant_pub);
	free(hm->deny_pub);
	free(hm->file);
	free(hm->command);
	free(hm->networks);
	free(hm->customergroups);
	free(hm->nodegroups);
	free(hm->excluded_networks);
	free(hm->excluded_nodegroups);	
	free(hm->excluded_customergroups);	
}

struct hostfile_module * init(GLOBAL *g, MODULE *m)
{
	struct hostfile_module *hm;

	if(g->api_version != APIVERSION)
	{
		return(NULL);
	}

	hm = (struct hostfile_module *) realloc(m, sizeof(struct hostfile_module));

	hm->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	hm->prefix = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "begin", "/usr/sbin/iptables -F FORWARD\n"));
	hm->append = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "end", "/usr/sbin/iptables -A FORWARD -j REJECT\n"));
	hm->host_prefix = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "host_begin", ""));
	hm->host_append = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "host_end", ""));

	hm->grant = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "grantedhost", "/usr/sbin/iptables -A FORWARD -s %i -m mac --mac-source %m -j ACCEPT\n/usr/sbin/iptables -A FORWARD -d %i -j ACCEPT\n"));
	hm->deny = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "deniedhost", "/usr/sbin/iptables -A FORWARD -s %i -m mac --mac-source %m -j REJECT\n"));

	hm->grant_pub = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "public_grantedhost", hm->grant));
	hm->deny_pub = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "public_deniedhost", hm->deny));
	hm->pub_replace = g->config_getbool(hm->base.ini, hm->base.instance, "public_replace", 1);

	hm->warn = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "warnedhost", ""));
	hm->warn_pub = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "public_warnedhost", hm->warn));
	hm->warn_replace = g->config_getbool(hm->base.ini, hm->base.instance, "warn_replace", 0);

	hm->file = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "file", "/tmp/hostfile"));
	hm->command = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "command", ""));
	hm->skip_dev_ips = g->config_getbool(hm->base.ini, hm->base.instance, "skip_dev_ips", 1);
	hm->skip_host_ips = g->config_getbool(hm->base.ini, hm->base.instance, "skip_host_ips", 0);

	hm->networks = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "networks", ""));
	hm->customergroups = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "customergroups", ""));
	hm->nodegroups = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "nodegroups", ""));
	hm->excluded_networks = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "excluded_networks", ""));
	hm->excluded_customergroups = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "excluded_customergroups", ""));
	hm->excluded_nodegroups = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "excluded_nodegroups", ""));

	hm->share_netdev_pubip = g->config_getbool(hm->base.ini, hm->base.instance, "share_netdev_pubip", 0);
	hm->multi_mac = g->config_getbool(hm->base.ini, hm->base.instance, "multi_mac", 0);

	// looking for %customer or %cid variables, if not found we'll not join 
	// with customers table 
	if(!hm->skip_host_ips && (
		strstr(hm->host_prefix, "%customer")!=NULL
		|| strstr(hm->host_append, "%customer")!=NULL
		|| strstr(hm->grant, "%customer")!=NULL
		|| strstr(hm->deny, "%customer")!=NULL 
		|| strstr(hm->grant_pub, "%customer")!=NULL
		|| strstr(hm->deny_pub, "%customer")!=NULL
		|| strstr(hm->warn, "%customer")!=NULL 
		|| strstr(hm->warn_pub, "%customer")!=NULL
		|| strstr(hm->host_prefix, "%cid")!=NULL
		|| strstr(hm->host_append, "%cid")!=NULL
		|| strstr(hm->grant, "%cid")!=NULL
		|| strstr(hm->deny, "%cid")!=NULL
		|| strstr(hm->grant_pub, "%cid")!=NULL
		|| strstr(hm->deny_pub, "%cid")!=NULL
		|| strstr(hm->warn, "%cid")!=NULL
		|| strstr(hm->warn_pub, "%cid")!=NULL))
	{
		hm->join_customers = 1;
	}
	else
		hm->join_customers = 0;

	// looking for %devl variable, if not found we'll not join 
	// with netdevices table
	if(	strstr(hm->host_prefix, "%devl")!=NULL
		|| strstr(hm->host_append, "%devl")!=NULL
		|| strstr(hm->grant, "%devl")!=NULL
		|| strstr(hm->deny, "%devl")!=NULL 
		|| strstr(hm->grant_pub, "%devl")!=NULL
		|| strstr(hm->deny_pub, "%devl")!=NULL
		|| strstr(hm->warn, "%devl")!=NULL 
		|| strstr(hm->warn_pub, "%devl")!=NULL)
	{
		hm->join_devices = 1;
	}
	else
		hm->join_devices = 0;

#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/hostfile] initialized", hm->base.instance);
#endif
	return(hm);
}
