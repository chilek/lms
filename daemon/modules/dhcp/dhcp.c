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
#include <syslog.h>
#include <string.h>

#include "almsd.h"
#include "dhcp.h"

unsigned long inet_addr(char *);
unsigned char * inet_ntoa(unsigned long);

void reload(GLOBAL *g, struct dhcp_module *dhcp)
{
	FILE *fh;
	QUERY_HANDLE *res, *res1;
	int i, j, m, k=2, gc=0, nc=0, nh=0, n=2;
	struct hostcache
	{
		unsigned char *name;
		unsigned char *mac;
		unsigned long ipaddr;
	} *hosts = NULL;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(dhcp->networks);	
	char *netname = strdup(netnames);
    
	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(dhcp->usergroups);	
	char *groupname = strdup(groupnames);

	while( n>1 ) {
		
    		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )

		        if( (res = g->db_pquery("SELECT name, address, INET_ATON(mask) AS mask  FROM networks WHERE UPPER(name)=UPPER('?')",netname))!=NULL) {

				if(res->nrows) {
					nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
					nets[nc].name = strdup(g->db_get_data(res,0,"name"));
					nets[nc].address = inet_addr(g->db_get_data(res,0,"address"));
					nc++;
				}
	    			g->db_free(res);
			}				
	}
	free(netname); free(netnames);

	while( k>1 ) {
		
		k = sscanf(groupnames, "%s %[._a-zA-Z0-9- ]", groupname, groupnames);

		if( strlen(groupname) ) {

			if( (res = g->db_pquery("SELECT name, id FROM usergroups WHERE UPPER(name)=UPPER('?')",groupname))!=NULL) {

				if(res->nrows) {

			    		ugps = (struct group *) realloc(ugps, (sizeof(struct group) * (gc+1)));
					ugps[gc].name = strdup(g->db_get_data(res,0,"name"));
					ugps[gc].id = atoi(g->db_get_data(res,0,"id"));
					gc++;
				}
	    			g->db_free(res);
			}		
		}		
	}
	free(groupname); free(groupnames);


	fh = fopen(dhcp->file, "w");
	if(fh) {

		if( (res = g->db_query("SELECT name, mac, ipaddr, ownerid FROM nodes ORDER BY ipaddr"))!=NULL ) {

			for(i=0; i<res->nrows; i++) {
				
				int ownerid = atoi(g->db_get_data(res,i,"ownerid"));
				char *name = g->db_get_data(res,i,"name");
				char *mac = g->db_get_data(res,i,"mac");
				char *ipaddr = g->db_get_data(res,i,"ipaddr");
		
				if(name && mac && ipaddr) {

					// groups test
					if(gc) {
						if(ownerid==0)
							continue;
						m = gc;
						if( (res1 = g->db_pquery("SELECT usergroupid FROM userassignments WHERE userid=?", g->db_get_data(res,i,"ownerid"))) ) {
							for(k=0; k<res1->nrows; k++) {
								int groupid = atoi(g->db_get_data(res1, k, "usergroupid"));
								for(m=0; m<gc; m++) 
									if(ugps[m].id==groupid) 
										break;
								if(m!=gc) break;
							}
							g->db_free(res1);
						}
						if(m==gc)
							continue;
					}

					hosts = (struct hostcache*) realloc(hosts, sizeof(struct hostcache) * (nh + 1));
					hosts[nh].name = strdup(name);
					hosts[nh].mac = strdup(mac);
					hosts[nh].ipaddr = inet_addr(ipaddr);
					nh++;
				}
			}
			g->db_free(res);
		}
		fprintf(fh, "%s\n", dhcp->prefix);
		
		if( (res = g->db_query("SELECT inet_ntoa(address) AS address, mask, gateway, dns, dns2, domain, wins, dhcpstart, dhcpend FROM networks"))!=NULL ) {

			for(i=0; i<res->nrows; i++) {
			
				unsigned char *s, *d, *d2, *e;
				unsigned long netmask, network;
				
				e = g->db_get_data(res,i,"address");
				d = g->db_get_data(res,i,"mask");
				network = inet_addr(e);
				netmask = inet_addr(d);
				
				// networks test
				if(nc) {
					for(j=0; j<nc; j++)
						if(nets[j].address==network) 
							break;
					if(j==nc)
						continue;
				}
								
				s = strdup(dhcp->subnetstart);
				g->str_replace(&s, "%m", d);
				g->str_replace(&s, "%a", e);
				fprintf(fh, "%s\n", s);
				free(s); 

				if( (d = g->db_get_data(res,i,"dhcpstart")) && ((e = g->db_get_data(res,i,"dhcpend"))) ) {
					if( strlen(d) && strlen(e) ) {
						s = strdup(dhcp->rangeline);
						g->str_replace(&s, "%s", d);
						g->str_replace(&s, "%e", e);
						fprintf(fh, "%s\n", s);
						free(s);
					}
				}
			
				if( (d = g->db_get_data(res,i,"gateway")) ) {
					if( strlen(d) ) {
						s = strdup(dhcp->gateline);
						g->str_replace(&s, "%i", d);
						fprintf(fh, "%s\n", s);
						free(s);
					}
				}
				if( (d = g->db_get_data(res,i,"dns")) ) {
					if( (d2 = g->db_get_data(res,i,"dns2")) ) {
						if( strlen(d) && strlen(d2) ) {
							e = (unsigned char*) malloc(strlen(d)+strlen(d2)+2);
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
				if( (d = g->db_get_data(res,i,"domain")) ) {
					if( strlen(d) ) {
						s = strdup(dhcp->domainline);
						g->str_replace(&s, "%n", d);
						fprintf(fh, "%s\n", s);
						free(s);
					}
				}
				if( (d = g->db_get_data(res,i,"wins")) ) {
					if( strlen(d) ) {
						s = strdup(dhcp->winsline);
						g->str_replace(&s, "%i", d);
						fprintf(fh, "%s\n", s);
						free(s);
					}
				}
				for(j=0; j<nh; j++) {
					if( (hosts[j].ipaddr & netmask) == network ) {
						s = strdup(dhcp->host);
						g->str_replace(&s, "%i", inet_ntoa(hosts[j].ipaddr));
						g->str_replace(&s, "%n", hosts[j].name);
						g->str_replace(&s, "%m", hosts[j].mac);
						fprintf(fh, "%s\n", s);
						free(s);
					}
				}
				fprintf(fh, "%s\n", dhcp->subnetend);
			}
			g->db_free(res);
		}
		fprintf(fh, "%s", dhcp->append);
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
	free(dhcp->usergroups);
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
	unsigned char *instance, *s;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
		return (NULL);

	instance = m->instance;

	dhcp = (struct dhcp_module*) realloc(m, sizeof(struct dhcp_module));
	
	dhcp->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	dhcp->base.instance = strdup(instance);

	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance, ":begin"); 
	dhcp->prefix = strdup(g->iniparser_getstring(ini, s, "shared-network LMS {"));
	free(s); s = g->str_concat(instance, ":end"); 
	dhcp->append = strdup(g->iniparser_getstring(ini, s, "}"));
	free(s); s = g->str_concat(instance, ":subnet_start");
	dhcp->subnetstart = strdup(g->iniparser_getstring(ini, s, "subnet %a netmask %m {\ndefault-lease-time 86400;\nmax-lease-time 86400;"));
	free(s); s = g->str_concat(instance, ":subnet_end");
	dhcp->subnetend = strdup(g->iniparser_getstring(ini, s, "}"));
	free(s); s = g->str_concat(instance, ":subnet_gateway");
	dhcp->gateline = strdup(g->iniparser_getstring(ini, s, "option routers %i;"));
	free(s); s = g->str_concat(instance, ":subnet_dns");
	dhcp->dnsline = strdup(g->iniparser_getstring(ini, s, "option domain-name-servers %i;"));
	free(s); s = g->str_concat(instance, ":subnet_domain");
	dhcp->domainline = strdup(g->iniparser_getstring(ini, s, "option domain-name %n;"));
	free(s); s = g->str_concat(instance, ":subnet_wins");
	dhcp->winsline = strdup(g->iniparser_getstring(ini, s, "option netbios-name-servers %i;"));
	free(s); s = g->str_concat(instance, ":subnet_range");
	dhcp->rangeline = strdup(g->iniparser_getstring(ini, s, "range %s %e;"));
	free(s); s = g->str_concat(instance, ":host");
	dhcp->host = strdup(g->iniparser_getstring(ini, s, "\thost %n {\n\t\thardware ethernet %m; fixed-address %i; \n\t}"));
	free(s); s = g->str_concat(instance, ":file");
	dhcp->file = strdup(g->iniparser_getstring(ini, s, "/etc/dhcpd.conf"));
	free(s); s = g->str_concat(instance, ":command");
	dhcp->command = strdup(g->iniparser_getstring(ini, s, "killall dhcpd; /usr/sbin/dhcpd"));
	free(s); s = g->str_concat(instance, ":networks");
	dhcp->networks = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":usergroups");
	dhcp->usergroups = strdup(g->iniparser_getstring(ini, s, ""));

	g->iniparser_freedict(ini);
	free(instance);
	free(s);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/dhcp] initialized", dhcp->base.instance);
#endif	
	return (dhcp);
}


