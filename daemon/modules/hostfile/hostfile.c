/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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
#include "hostfile.h"

unsigned long inet_addr(char *);

void reload(GLOBAL *g, struct hostfile_module *hm)
{
	FILE *fh;
	QUERY_HANDLE *res, *res1;
	unsigned char *query;
	int i, j, m, k=2, gc=0, nc=0, n=2;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(hm->networks);	
	char *netname = strdup(netnames);
	
	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(hm->usergroups);	
	char *groupname = strdup(groupnames);

	while( n>1 ) {
		
		n = sscanf(netnames, "%s %[.a-zA-Z0-9-_ ]", netname, netnames);

		if( strlen(netname) ) {

			if( (res = g->db_pquery("SELECT name, domain, address, INET_ATON(mask) AS mask, interface, gateway FROM networks WHERE UPPER(name)=UPPER('?')",netname)) ) {

				if(res->nrows) {

			    		nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
					nets[nc].name = strdup(g->db_get_data(res,0,"name"));
					nets[nc].domain = strdup(g->db_get_data(res,0,"domain"));
					nets[nc].interface = strdup(g->db_get_data(res,0,"interface"));
					nets[nc].gateway = strdup(g->db_get_data(res,0,"gateway"));
					nets[nc].address = inet_addr(g->db_get_data(res,0,"address"));
					nets[nc].mask = inet_addr(g->db_get_data(res,0,"mask"));
					nc++;
				}
	    			g->db_free(res);
			}				
		}
	}
	free(netname); free(netnames);

	if(!nc)
		if( (res = g->db_query("SELECT name, domain, address, INET_ATON(mask) AS mask, interface, gateway FROM networks"))!=NULL ) {

			for(nc=0; nc<res->nrows; nc++) {
				
				nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].name = strdup(g->db_get_data(res,nc,"name"));
				nets[nc].domain = strdup(g->db_get_data(res,nc,"domain"));
				nets[nc].interface = strdup(g->db_get_data(res,0,"interface"));
				nets[nc].gateway = strdup(g->db_get_data(res,0,"gateway"));
				nets[nc].address = inet_addr(g->db_get_data(res,nc,"address"));
				nets[nc].mask = inet_addr(g->db_get_data(res,nc,"mask"));
			}
			g->db_free(res);
		}

	while( k>1 ) {
		
		k = sscanf(groupnames, "%s %[.a-zA-Z0-9-_ ]", groupname, groupnames);

		if( strlen(groupname) ) {

			if( (res = g->db_pquery("SELECT name, id FROM usergroups WHERE UPPER(name)=UPPER('?')",groupname)) ) {

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

	if(!gc)
		if( (res = g->db_query("SELECT name, id FROM usergroups ORDER BY name"))!=NULL ) {

			for(gc=0; gc<res->nrows; gc++) {
				
				ugps = (struct group*) realloc(ugps, (sizeof(struct group) * (gc+1)));
				ugps[gc].name = strdup(g->db_get_data(res,gc,"name"));
				ugps[gc].id = atoi(g->db_get_data(res,gc,"id"));
			}
			g->db_free(res);
		}
	
	fh = fopen(hm->file, "w");
	if(fh)
	{
		fprintf(fh, "%s", hm->prefix);
		
		if(hm->skip_dev_ips)
			query = strdup("SELECT LOWER(name) AS name, mac, INET_NTOA(ipaddr) AS ip, ownerid, access FROM nodes WHERE ownerid<>0 ORDER BY ipaddr");
		else
			query = strdup("SELECT LOWER(name) AS name, mac, INET_NTOA(ipaddr) AS ip, ownerid, access FROM nodes ORDER BY ipaddr");
			
		if( (res = g->db_query(query))!=NULL ) {
		
			for(i=0; i<res->nrows; i++) {
				unsigned char *mac, *ip, *access, *name;
			
				mac 	= g->db_get_data(res,i,"mac");
				ip  	= g->db_get_data(res,i,"ip");
				access 	= g->db_get_data(res,i,"access");
				name 	= g->db_get_data(res,i,"name");

				if(ip && mac && access) {
					
					unsigned long inet = inet_addr(ip);
					int ownerid = atoi(g->db_get_data(res,i,"ownerid"));
					
					// networks test
					for(j=0; j<nc; j++)
						if(nets[j].address == (inet & nets[j].mask)) 
							break;
					
					// groups test
					m = gc;
					if(strlen(hm->usergroups)>0 && ownerid)
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
					
					if( j!=nc && (strlen(hm->usergroups)==0 || m!=gc) ) {

						unsigned char *pattern, *s;

						if(*access == '1')
							pattern = hm->grant;
						else
							pattern = hm->deny;
				
						s = strdup(pattern);
						g->str_replace(&s, "%domain", nets[j].domain);
						g->str_replace(&s, "%net", nets[j].name);
						g->str_replace(&s, "%if", nets[j].interface);
						g->str_replace(&s, "%gw", nets[j].gateway);
						g->str_replace(&s, "%i", ip);
						g->str_replace(&s, "%m", mac);
						g->str_replace(&s, "%n", name);
						
						fprintf(fh, "%s", s);
						free(s);
					}
				}
			}
		g->db_free(res);
		}		
		free(query);
		fprintf(fh, "%s", hm->append);
		fclose(fh);
		system(hm->command);
#ifdef DEBUG1
		syslog(LOG_INFO,"DEBUG: [%s/hostfile] reloaded",hm->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/hostfile] Unable to write a temporary file '%s'", hm->base.instance, hm->file);
	
	for(i=0;i<nc;i++) {
		free(nets[i].name);
		free(nets[i].domain);	
		free(nets[i].interface);
		free(nets[i].gateway);
	}
	free(nets);
	
	for(i=0;i<gc;i++) {
		free(ugps[i].name);
	}
	free(ugps);
	
	free(hm->prefix);
	free(hm->append);
	free(hm->grant);	
	free(hm->deny);
	free(hm->file);
	free(hm->command);
	free(hm->networks);
	free(hm->usergroups);
}

struct hostfile_module * init(GLOBAL *g, MODULE *m)
{
	struct hostfile_module *hm;
	unsigned char *instance, *s;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
		return(NULL);

	instance = m->instance;
	
	hm = (struct hostfile_module *) realloc(m, sizeof(struct hostfile_module));
	
	hm->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	hm->base.instance = strdup(instance);
	
	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance,":begin");
	hm->prefix = strdup(g->iniparser_getstring(ini, s, "/usr/sbin/iptables -F FORWARD\n"));
	free(s); s = g->str_concat(instance,":end");
	hm->append = strdup(g->iniparser_getstring(ini, s, "/usr/sbin/iptables -A FORWARD -j REJECT\n"));
	free(s); s = g->str_concat(instance,":grantedhost");	
	hm->grant = strdup(g->iniparser_getstring(ini, s, "/usr/sbin/iptables -A FORWARD -s %i -m mac --mac-source %m -j ACCEPT\n/usr/sbin/iptables -A FORWARD -d %i -j ACCEPT\n"));
	free(s); s = g->str_concat(instance,":deniedhost");
	hm->deny = strdup(g->iniparser_getstring(ini, s, "/usr/sbin/iptables -A FORWARD -s %i -m mac --mac-source %m -j REJECT\n"));
	free(s); s = g->str_concat(instance,":skip_dev_ips");
	hm->skip_dev_ips = g->iniparser_getboolean(ini, s, 1);
	free(s); s = g->str_concat(instance,":file");
	hm->file = strdup(g->iniparser_getstring(ini, s, "/tmp/hostfile"));
	free(s); s = g->str_concat(instance,":command");
	hm->command = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":networks");
	hm->networks = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":usergroups");
	hm->usergroups = strdup(g->iniparser_getstring(ini, s, ""));
	
	g->iniparser_freedict(ini);
	free(instance);
	free(s);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/hostfile] initialized", hm->base.instance);
#endif
	return(hm);
}
