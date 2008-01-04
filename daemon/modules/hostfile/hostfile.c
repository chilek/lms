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
#include <netinet/in.h>
#include <arpa/inet.h>

#include "lmsd.h"
#include "hostfile.h"

char * itoha(int i)
{
        static char string[8];
	sprintf(string, "%x", i);
	return string;
}

void addrule(GLOBAL *g, FILE *fh, char *rule, struct host h)
{
	char *s = strdup(rule);

	g->str_replace(&s, "%domain", h.net.domain);
	g->str_replace(&s, "%net", h.net.name);
	g->str_replace(&s, "%if", h.net.interface);
	g->str_replace(&s, "%gw", h.net.gateway);
	g->str_replace(&s, "%dns2", h.net.dns2);
	g->str_replace(&s, "%dns", h.net.dns);
	g->str_replace(&s, "%wins", h.net.wins);
	g->str_replace(&s, "%mask", inet_ntoa(inet_makeaddr(htonl(h.net.mask),0)));
	g->str_replace(&s, "%addr", inet_ntoa(inet_makeaddr(htonl(h.net.address),0)));
	g->str_replace(&s, "%info", h.info);
	g->str_replace(&s, "%ipub", h.ip_pub);
	g->str_replace(&s, "%id", h.id);
	g->str_replace(&s, "%i16pub", h.i16_pub);
	g->str_replace(&s, "%i16", h.i16);
	g->str_replace(&s, "%i", h.ip);
	g->str_replace(&s, "%m", h.mac);
	g->str_replace(&s, "%n", h.name);
	g->str_replace(&s, "%p", h.passwd);

	fprintf(fh, "%s", s);
	free(s);
}

void reload(GLOBAL *g, struct hostfile_module *hm)
{
	FILE *fh;
	QueryHandle *res;
	char *query;
	int i, j, k=2, nc=0, n=2;

	char *netnames = strdup(hm->networks);	
	char *netname = strdup(netnames);
	struct net *nets = (struct net *) malloc(sizeof(struct net));

	char *groups = strdup("EXISTS (SELECT 1 FROM customergroups g, customerassignments a "
				"WHERE a.customerid = ownerid "
				"AND g.id = a.customergroupid "
				"AND (%groups)) ");
	
	char *groupnames = strdup(hm->customergroups);
	char *groupname = strdup(groupnames);
	char *groupsql = strdup("");
	
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

	while( n>1 )
	{
		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )
		{
			res = g->db_pquery(g->conn, "SELECT name, domain, address, INET_ATON(mask) AS mask, interface, gateway, dns, dns2, wins FROM networks WHERE UPPER(name)=UPPER('?')",netname);

			if( g->db_nrows(res) )
			{
		    		nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].name = strdup(g->db_get_data(res,0,"name"));
				nets[nc].domain = strdup(g->db_get_data(res,0,"domain"));
				nets[nc].interface = strdup(g->db_get_data(res,0,"interface"));
				nets[nc].gateway = strdup(g->db_get_data(res,0,"gateway"));
				nets[nc].dns = strdup(g->db_get_data(res,0,"dns"));
				nets[nc].dns2 = strdup(g->db_get_data(res,0,"dns2"));
				nets[nc].wins = strdup(g->db_get_data(res,0,"wins"));
				nets[nc].address = inet_addr(g->db_get_data(res,0,"address"));
				nets[nc].mask = inet_addr(g->db_get_data(res,0,"mask"));
				nc++;
			}
    			g->db_free(&res);
		}				
	}
	free(netname); free(netnames);

	if(!nc)
	{
		res = g->db_query(g->conn, "SELECT name, domain, address, INET_ATON(mask) AS mask, interface, gateway, dns, dns2, wins FROM networks");

		for(nc=0; nc<g->db_nrows(res); nc++)
		{
			nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
			nets[nc].name = strdup(g->db_get_data(res,nc,"name"));
			nets[nc].domain = strdup(g->db_get_data(res,nc,"domain"));
			nets[nc].interface = strdup(g->db_get_data(res,nc,"interface"));
			nets[nc].gateway = strdup(g->db_get_data(res,nc,"gateway"));
			nets[nc].dns = strdup(g->db_get_data(res,nc,"dns"));
			nets[nc].dns2 = strdup(g->db_get_data(res,nc,"dns2"));
			nets[nc].wins = strdup(g->db_get_data(res,nc,"wins"));
			nets[nc].address = inet_addr(g->db_get_data(res,nc,"address"));
			nets[nc].mask = inet_addr(g->db_get_data(res,nc,"mask"));
		}
		g->db_free(&res);
	}

	fh = fopen(hm->file, "w");
	if(fh)
	{
		fprintf(fh, "%s", hm->prefix);
		
		if(hm->skip_dev_ips)
			query = strdup(
				"SELECT id, LOWER(name) AS name, mac, INET_NTOA(ipaddr) AS ip, "
				"INET_NTOA(ipaddr_pub) AS ip_pub, passwd, access, info, warning "
				"FROM nodes "
				"WHERE ownerid<>0 AND %groups "
				"ORDER BY ipaddr");
		else
			query = strdup(
				"SELECT id, LOWER(name) AS name, mac, INET_NTOA(ipaddr) AS ip, "
				"INET_NTOA(ipaddr_pub) AS ip_pub, passwd, access, info, warning "
				"FROM nodes "
				"WHERE ownerid = 0 AND %groups "
				"ORDER BY ipaddr");
			
		g->str_replace(&query, "%groups", strlen(groupsql) ? groups : "1=1");	
		res = g->db_query(g->conn, query);

		for(i=0; i<g->db_nrows(res); i++)
		{
			unsigned long inet, inet_pub;
			struct host h;
			
			h.ip 		= g->db_get_data(res,i,"ip");
			h.ip_pub 	= g->db_get_data(res,i,"ip_pub");
			inet 		= inet_addr(h.ip);
			inet_pub 	= inet_addr(h.ip_pub);

			// networks test
			for(j=0; j<nc; j++)
				if(nets[j].address == (inet & nets[j].mask))
					break;
			if( j==nc )
				for(j=0; j<nc; j++)
					if(nets[j].address == (inet_pub & nets[j].mask))
						break;

			if( j!=nc )
			{
				char *pattern;

				h.access 	= g->db_get_data(res,i,"access");
			    	h.warning	= g->db_get_data(res,i,"warning");
				h.name 		= g->db_get_data(res,i,"name");
				h.info 		= g->db_get_data(res,i,"info");
				h.passwd 	= g->db_get_data(res,i,"passwd");
				h.id  		= g->db_get_data(res,i,"id");
				h.mac 		= g->db_get_data(res,i,"mac");
				h.net 		= nets[j];
				// IP's last octet in hex
                    		h.i16 		= strdup(itoha((ntohl(inet) & 0xff)));
				h.i16_pub 	= strdup(inet_pub ? itoha((ntohl(inet_pub) & 0xff)) : "");

				addrule(g, fh, hm->host_prefix, h);

				if(*h.access == '1')
					pattern = ( inet_pub && hm->pub_replace ? hm->grant_pub : hm->grant );
				else
					pattern = ( inet_pub && hm->pub_replace ? hm->deny_pub : hm->deny );

				if(*h.warning == '1' && hm->warn_replace)
					pattern = ( inet_pub ? hm->warn_pub : hm->warn );

				addrule(g, fh, pattern, h);

				if(!hm->warn_replace && *h.warning == '1' && (!hm->pub_replace || !inet_pub))
				{
					addrule(g, fh, hm->warn, h);
				}			
				
				if(!hm->pub_replace && inet_pub)
				{
					pattern = ( *h.access == '1' ? hm->grant_pub : hm->deny_pub );

					if(*h.warning == '1' && hm->warn_replace)
						pattern = hm->warn_pub;

					addrule(g, fh, pattern, h);
				}			

				if(!hm->warn_replace && *h.warning == '1' && inet_pub)
				{
					addrule(g, fh, hm->warn_pub, h);
				}			

				addrule(g, fh, hm->host_append, h);

				free(h.i16);
				free(h.i16_pub);
			}
		}
	
		fprintf(fh, "%s", hm->append);
		
		g->db_free(&res);
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
		free(nets[i].name);
		free(nets[i].domain);	
		free(nets[i].interface);
		free(nets[i].gateway);
		free(nets[i].dns);
		free(nets[i].dns2);
		free(nets[i].wins);
	}
	free(nets);
	
	free(groups);
	free(groupsql);
	
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

	hm->skip_dev_ips = g->config_getbool(hm->base.ini, hm->base.instance, "skip_dev_ips", 1);
	hm->file = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "file", "/tmp/hostfile"));
	hm->command = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "command", ""));

	hm->networks = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "networks", ""));
	hm->customergroups = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "customergroups", ""));
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/hostfile] initialized", hm->base.instance);
#endif
	return(hm);
}
