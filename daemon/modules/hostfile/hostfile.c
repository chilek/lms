/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

#include "lmsd.h"
#include "hostfile.h"

unsigned long inet_addr(char *);

void reload(GLOBAL *g, struct hostfile_module *hm)
{
	FILE *fh;
	QueryHandle *res, *res1;
	unsigned char *query;
	int i, j, m, k=2, gc=0, nc=0, n=2;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(hm->networks);	
	char *netname = strdup(netnames);
	
	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(hm->usergroups);	
	char *groupname = strdup(groupnames);

	while( n>1 )
	{
		n = sscanf(netnames, "%s %[.a-zA-Z0-9-_ ]", netname, netnames);

		if( strlen(netname) )
		{
			res = g->db_pquery(g->conn, "SELECT name, domain, address, INET_ATON(mask) AS mask, interface, gateway FROM networks WHERE UPPER(name)=UPPER('?')",netname);

			if( g->db_nrows(res) )
			{
		    		nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].name = strdup(g->db_get_data(res,0,"name"));
				nets[nc].domain = strdup(g->db_get_data(res,0,"domain"));
				nets[nc].interface = strdup(g->db_get_data(res,0,"interface"));
				nets[nc].gateway = strdup(g->db_get_data(res,0,"gateway"));
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
		res = g->db_query(g->conn, "SELECT name, domain, address, INET_ATON(mask) AS mask, interface, gateway FROM networks");

		for(nc=0; nc<g->db_nrows(res); nc++)
		{
			nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
			nets[nc].name = strdup(g->db_get_data(res,nc,"name"));
			nets[nc].domain = strdup(g->db_get_data(res,nc,"domain"));
			nets[nc].interface = strdup(g->db_get_data(res,0,"interface"));
			nets[nc].gateway = strdup(g->db_get_data(res,0,"gateway"));
			nets[nc].address = inet_addr(g->db_get_data(res,nc,"address"));
			nets[nc].mask = inet_addr(g->db_get_data(res,nc,"mask"));
		}
		g->db_free(&res);
	}

	while( k>1 )
	{
		k = sscanf(groupnames, "%s %[.a-zA-Z0-9-_ ]", groupname, groupnames);

		if( strlen(groupname) )
		{
			res = g->db_pquery(g->conn, "SELECT name, id FROM usergroups WHERE UPPER(name)=UPPER('?')",groupname);

			if( g->db_nrows(res) )
			{
		    		ugps = (struct group *) realloc(ugps, (sizeof(struct group) * (gc+1)));
				ugps[gc].name = strdup(g->db_get_data(res,0,"name"));
				ugps[gc].id = atoi(g->db_get_data(res,0,"id"));
				gc++;
			}
    			g->db_free(&res);
		}				
	}
	free(groupname); free(groupnames);

	if(!gc)
	{
		res = g->db_query(g->conn, "SELECT name, id FROM usergroups ORDER BY name");

		for(gc=0; gc<g->db_nrows(res); gc++)
		{
			ugps = (struct group*) realloc(ugps, (sizeof(struct group) * (gc+1)));
			ugps[gc].name = strdup(g->db_get_data(res,gc,"name"));
			ugps[gc].id = atoi(g->db_get_data(res,gc,"id"));
		}
		g->db_free(&res);
	}
	
	fh = fopen(hm->file, "w");
	if(fh)
	{
		fprintf(fh, "%s", hm->prefix);
		
		if(hm->skip_dev_ips)
			query = strdup("SELECT LOWER(name) AS name, mac, INET_NTOA(ipaddr) AS ip, INET_NTOA(ipaddr_pub) AS ip_pub, passwd, ownerid, access, info FROM nodes WHERE ownerid<>0 ORDER BY ipaddr");
		else
			query = strdup("SELECT LOWER(name) AS name, mac, INET_NTOA(ipaddr) AS ip, INET_NTOA(ipaddr_pub) AS ip_pub, passwd, ownerid, access, info FROM nodes ORDER BY ipaddr");
			
		res = g->db_query(g->conn, query);
		
		for(i=0; i<g->db_nrows(res); i++)
		{
			unsigned char *mac, *ip, *ip_pub, *access, *name, *info, *passwd;
	
			mac 	= g->db_get_data(res,i,"mac");
			ip  	= g->db_get_data(res,i,"ip");
			ip_pub 	= g->db_get_data(res,i,"ip_pub");
			access 	= g->db_get_data(res,i,"access");
			name 	= g->db_get_data(res,i,"name");
			info 	= g->db_get_data(res,i,"info");
			passwd 	= g->db_get_data(res,i,"passwd");

			if(ip && mac && access)
			{
				unsigned long inet = inet_addr(ip);
				unsigned long inet_pub = inet_addr(ip_pub);
				int ownerid = atoi(g->db_get_data(res,i,"ownerid"));
				
				// networks test
				for(j=0; j<nc; j++)
					if(nets[j].address == (inet & nets[j].mask))
						break;
								
				// groups test
				m = gc;
				if( strlen(hm->usergroups)>0 && ownerid )
				{
					res1 = g->db_pquery(g->conn, "SELECT usergroupid FROM userassignments WHERE userid=?", g->db_get_data(res,i,"ownerid"));
					for(k=0; k<g->db_nrows(res1); k++)
					{
						int groupid = atoi(g->db_get_data(res1, k, "usergroupid"));
						for(m=0; m<gc; m++) 
							if( ugps[m].id==groupid ) 
								break;
						if( m!=gc ) break;
					}
					g->db_free(&res1);
				}
				
				if( j!=nc && (strlen(hm->usergroups)==0 || m!=gc) )
				{
					unsigned char *pattern, *s;

					if(*access == '1')
						pattern = ( inet_pub ? hm->grant_pub : hm->grant );
					else
						pattern = ( inet_pub ? hm->deny_pub : hm->deny );
			
					s = strdup(pattern);
					g->str_replace(&s, "%domain", nets[j].domain);
					g->str_replace(&s, "%net", nets[j].name);
					g->str_replace(&s, "%if", nets[j].interface);
					g->str_replace(&s, "%gw", nets[j].gateway);
					g->str_replace(&s, "%info", info);
					g->str_replace(&s, "%ipub", ip_pub);
					g->str_replace(&s, "%i", ip);
					g->str_replace(&s, "%m", mac);
					g->str_replace(&s, "%n", name);
					g->str_replace(&s, "%p", passwd);
					
					fprintf(fh, "%s", s);
					free(s);
				}
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
	}
	free(nets);
	
	for(i=0;i<gc;i++)
		free(ugps[i].name);
	free(ugps);
	
	free(hm->prefix);
	free(hm->append);
	free(hm->grant);
	free(hm->deny);
	free(hm->grant_pub);
	free(hm->deny_pub);
	free(hm->file);
	free(hm->command);
	free(hm->networks);
	free(hm->usergroups);
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
	hm->grant = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "grantedhost", "/usr/sbin/iptables -A FORWARD -s %i -m mac --mac-source %m -j ACCEPT\n/usr/sbin/iptables -A FORWARD -d %i -j ACCEPT\n"));
	hm->deny = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "deniedhost", "/usr/sbin/iptables -A FORWARD -s %i -m mac --mac-source %m -j REJECT\n"));
	hm->grant_pub = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "public_grantedhost", hm->grant));
	hm->deny_pub = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "public_deniedhost", hm->deny));
	hm->skip_dev_ips = g->config_getbool(hm->base.ini, hm->base.instance, "skip_dev_ips", 1);
	hm->file = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "file", "/tmp/hostfile"));
	hm->command = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "command", ""));
	hm->networks = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "networks", ""));
	hm->usergroups = strdup(g->config_getstring(hm->base.ini, hm->base.instance, "usergroups", ""));
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/hostfile] initialized", hm->base.instance);
#endif
	return(hm);
}
