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
#include "dhcp.h"

void reload(GLOBAL *g, struct dhcp_module *dhcp)
{
	FILE *fh;
	QueryHandle *res, *res1;
	int i, j, m, k=2, gc=0, nc=0, nh=0, n=2;
	char lastif[MAXIFN] = "";
	struct hostcache
	{
		char *name;
		char *mac;
		unsigned long ipaddr;
	} *hosts = NULL;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(dhcp->networks);	
	char *netname = strdup(netnames);
    
	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(dhcp->customergroups);	
	char *groupname = strdup(groupnames);

	while( n>1 )
	{
    		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )
		{
		        res = g->db->pquery(g->db->conn, "SELECT name, address, INET_ATON(mask) AS mask  FROM networks WHERE UPPER(name)=UPPER('?')",netname);
			if( g->db->nrows(res) ) 
			{
				nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].name = strdup(g->db->get_data(res,0,"name"));
				nets[nc].address = inet_addr(g->db->get_data(res,0,"address"));
				nc++;
			}
	    		g->db->free(&res);
		}
	}
	free(netname); free(netnames);

	while( k>1 )
	{
		k = sscanf(groupnames, "%s %[._a-zA-Z0-9- ]", groupname, groupnames);

		if( strlen(groupname) )
		{
			res = g->db->pquery(g->db->conn, "SELECT name, id FROM customergroups WHERE UPPER(name)=UPPER('?')",groupname);
			if( g->db->nrows(res) )
			{
		    		ugps = (struct group *) realloc(ugps, (sizeof(struct group) * (gc+1)));
				ugps[gc].name = strdup(g->db->get_data(res,0,"name"));
				ugps[gc].id = atoi(g->db->get_data(res,0,"id"));
				gc++;
			}
	    		g->db->free(&res);
		}		
	}
	free(groupname); free(groupnames);

	fh = fopen(dhcp->file, "w");
	if(fh)
	{
		res = g->db->query(g->db->conn, "SELECT name, mac, ipaddr, ipaddr_pub, ownerid FROM vnodes ORDER BY ipaddr");

		for(i=0; i<g->db->nrows(res); i++)
		{
			int ownerid = atoi(g->db->get_data(res,i,"ownerid"));
			char *name = g->db->get_data(res,i,"name");
			char *mac = g->db->get_data(res,i,"mac");
			char *ipaddr = g->db->get_data(res,i,"ipaddr");
			char *ipaddr_pub = g->db->get_data(res,i,"ipaddr_pub");
		
			if(name && mac && ipaddr)
			{
				// groups test
				if(gc)
				{
					if( !ownerid ) continue;
					m = gc;
					
					res1 = g->db->pquery(g->db->conn, "SELECT customergroupid FROM customerassignments WHERE customerid=?", g->db->get_data(res,i,"ownerid"));
					for(k=0; k<g->db->nrows(res1); k++)
					{
						int groupid = atoi(g->db->get_data(res1, k, "customergroupid"));
						for(m=0; m<gc; m++) 
							if(ugps[m].id==groupid) 
								break;
						if(m!=gc) break;
					}
					g->db->free(&res1);
					if( m==gc )
						continue;
				}
				
				hosts = (struct hostcache*) realloc(hosts, sizeof(struct hostcache) * (nh + 1));
				hosts[nh].name = strdup(name);
				hosts[nh].mac = strdup(mac);
				hosts[nh].ipaddr = inet_addr(ipaddr);
				nh++;
				
				if(atoi(ipaddr_pub))
				{
					hosts = (struct hostcache*) realloc(hosts, sizeof(struct hostcache) * (nh + 1));
					hosts[nh].name = g->str_concat(name, "_pub");
					hosts[nh].mac = strdup(mac);
					hosts[nh].ipaddr = inet_addr(ipaddr_pub);
					nh++;
				}
			}
		}
		g->db->free(&res);
	
		fprintf(fh, "%s\n", dhcp->prefix);
		
		res = g->db->query(g->db->conn, "SELECT inet_ntoa(address) AS address, mask, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend, interface FROM networks ORDER BY interface");

		for(i=0; i<g->db->nrows(res); i++)
		{
			char *s, *d, *d2, *e, *b;
			unsigned long netmask, network, broadcast;
			char iface[MAXIFN] = "";
			
			e = g->db->get_data(res,i,"address");
			d = g->db->get_data(res,i,"mask");
			
			network = inet_addr(e);
			netmask = inet_addr(d);
			
			// networks test
			if(nc)
			{
				for( j=0; j<nc; j++ )
					if( nets[j].address==network )
						break;
				if( j==nc )
					continue;
			}
			
			// shared (interface) network ?
			sscanf(g->db->get_data(res,i,"interface"), "%[a-zA-Z0-9.]", iface);
			
			if( strlen(lastif) && strlen(iface) && strcmp(iface, lastif)!=0 )
			{
				fprintf(fh, "}\n");
			}
			
			if( strlen(iface) && strcmp(iface, lastif)!=0 )
			{
				fprintf(fh, "\nshared-network LMS-%s {\n", iface);
				strcpy(lastif, iface);
			}

			// broadcast address
			broadcast = network | (~netmask);
			b = inet_ntoa(inet_makeaddr(htonl(broadcast), 0));
			
			// start subnet record				
			s = strdup(dhcp->subnetstart);
			g->str_replace(&s, "%m", d);
			g->str_replace(&s, "%a", e);
			g->str_replace(&s, "%b", b);
			fprintf(fh, "%s\n", s);
			free(s); 

			if( (d = g->db->get_data(res,i,"dhcpstart")) && ((e = g->db->get_data(res,i,"dhcpend"))) )
			{
				if( strlen(d) && strlen(e) ) {
					s = strdup(dhcp->rangeline);
					g->str_replace(&s, "%s", d);
					g->str_replace(&s, "%e", e);
					fprintf(fh, "%s\n", s);
					free(s);
				}
			}
			
			if( (d = g->db->get_data(res,i,"gateway")) )
			{
				if( strlen(d) ) {
					s = strdup(dhcp->gateline);
					g->str_replace(&s, "%i", d);
					fprintf(fh, "%s\n", s);
					free(s);
				}
			}

			if( (d = g->db->get_data(res,i,"dns")) )
			{
				if( (d2 = g->db->get_data(res,i,"dns2")) )
				{
					if( strlen(d) && strlen(d2) ) {
						e = (char*) malloc(strlen(d)+strlen(d2)+2);
						sprintf(e,"%s,%s",d,d2);
						s = strdup(dhcp->dnsline);
						g->str_replace(&s, "%i", e);
						fprintf(fh, "%s\n", s);
						free(s); free(e);
					} else if (strlen(d)) {
						s = strdup(dhcp->dnsline);
						g->str_replace(&s, "%i", d);
						fprintf(fh, "%s\n", s);
						free(s);
					}
				}
			}

			if( (d = g->db->get_data(res,i,"domain")) )
			{
				if( strlen(d) ) {
					s = strdup(dhcp->domainline);
					g->str_replace(&s, "%n", d);
					fprintf(fh, "%s\n", s);
					free(s);
				}
			}

			if( (d = g->db->get_data(res,i,"wins")) )
			{
				if( strlen(d) ) {
					s = strdup(dhcp->winsline);
					g->str_replace(&s, "%i", d);
					fprintf(fh, "%s\n", s);
					free(s);
				}
			}
			
			for(j=0; j<nh; j++) 
			{
				char *mac;
				if( (hosts[j].ipaddr & netmask) == network ) {
					for(mac = strtok(hosts[j].mac, ","), m = 0; mac != NULL; mac = strtok(NULL, ","), m++)
					{
						static char name_suffix[12];
						char *name;
						name_suffix[0] = 0;
						if (m > 0)
							sprintf(name_suffix, "-%d", m);
						name = g->str_concat(hosts[j].name, name_suffix);
						s = strdup(dhcp->host);
						g->str_replace(&s, "%i", inet_ntoa(inet_makeaddr(htonl(hosts[j].ipaddr), 0)));
						g->str_replace(&s, "%n", name);
						g->str_replace(&s, "%m", mac);
						fprintf(fh, "%s\n", s);
						free(s);
						free(name);
					}
				}
			}
			
			fprintf(fh, "%s\n", dhcp->subnetend);
		}
		
		if( strlen(lastif) )
			fprintf(fh, "}\n");
		
		fprintf(fh, "%s", dhcp->append);
		
		g->db->free(&res);
		fclose(fh);
		
		system(dhcp->command);
		
		// cleanup
		for(i=0; i<nh; i++) {
			free(hosts[i].name);
			free(hosts[i].mac);
		}
		free(hosts);
#ifdef DEBUG1
		syslog(LOG_INFO,"DEBUG: [%s/dhcp] reloaded", dhcp->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/dhcp] Unable to write a temporary file '%s'", dhcp->base.instance, dhcp->file);

	//more cleanup
	for(i=0;i<nc;i++)
		free(nets[i].name);
	free(nets);
	
	for(i=0;i<gc;i++)
		free(ugps[i].name);
	free(ugps);
	
	free(dhcp->networks);
	free(dhcp->customergroups);
	free(dhcp->prefix);
	free(dhcp->append);
	free(dhcp->subnetstart);
	free(dhcp->subnetend);
	free(dhcp->gateline);
	free(dhcp->dnsline);
	free(dhcp->domainline);
	free(dhcp->winsline);
	free(dhcp->rangeline);
	free(dhcp->host);
	free(dhcp->file);
	free(dhcp->command);
}

struct dhcp_module * init(GLOBAL *g, MODULE *m)
{
	struct dhcp_module *dhcp;
	
	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}

	dhcp = (struct dhcp_module*) realloc(m, sizeof(struct dhcp_module));
	
	dhcp->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	dhcp->prefix = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "begin", ""));
	dhcp->append = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "end", ""));
	dhcp->subnetstart = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "subnet_start", "subnet %a netmask %m {\ndefault-lease-time 86400;\nmax-lease-time 86400;"));
	dhcp->subnetend = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "subnet_end", "}"));
	dhcp->gateline = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "subnet_gateway", "option routers %i;"));
	dhcp->dnsline = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "subnet_dns", "option domain-name-servers %i;"));
	dhcp->domainline = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "subnet_domain", "option domain-name \"%n\";"));
	dhcp->winsline = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "subnet_wins", "option netbios-name-servers %i;"));
	dhcp->rangeline = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "subnet_range", "range %s %e;"));
	dhcp->host = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "host", "\thost %n {\n\t\thardware ethernet %m; fixed-address %i; \n\t}"));
	dhcp->file = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "file", "/etc/dhcpd.conf"));
	dhcp->command = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "command", "killall dhcpd; /usr/sbin/dhcpd"));
	dhcp->networks = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "networks", ""));
	dhcp->customergroups = strdup(g->config_getstring(dhcp->base.ini, dhcp->base.instance, "customergroups", ""));
	
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/dhcp] initialized", dhcp->base.instance);
#endif	
	return (dhcp);
}


