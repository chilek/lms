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
#include "ethers.h"

unsigned long inet_addr(unsigned char*);
char * inet_ntoa(unsigned long);

void reload(GLOBAL *g, struct ethers_module *fm)
{
	FILE *fh;
	QUERY_HANDLE *res, *res1;
	int i, j, m, k=2, gc=0, nc=0, n=2;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(fm->networks);	
	char *netname = strdup(netnames);
    
	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(fm->usergroups);	
	char *groupname = strdup(groupnames);

	while( n>1 ) {
		
    		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )

		        if( (res = g->db_pquery("SELECT name, address, INET_ATON(mask) AS mask  FROM networks WHERE UPPER(name)=UPPER('?')",netname))!=NULL) {

				if(res->nrows) {
					nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
					nets[nc].name = strdup(g->db_get_data(res,0,"name"));
					nets[nc].address = inet_addr(g->db_get_data(res,0,"address"));
					nets[nc].mask = inet_addr(g->db_get_data(res,0,"mask"));
					nc++;
				}
	    			g->db_free(res);
			}				
	}
	free(netname); free(netnames);

	if(!nc)
		if( (res = g->db_query("SELECT name, address, INET_ATON(mask) AS mask FROM networks"))!=NULL ) {

			for(nc=0; nc<res->nrows; nc++) {
				
				nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].name = strdup(g->db_get_data(res,nc,"name"));
				nets[nc].address = inet_addr(g->db_get_data(res,nc,"address"));
				nets[nc].mask = inet_addr(g->db_get_data(res,nc,"mask"));
			}
			g->db_free(res);
		}

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

	fh = fopen(fm->file, "w");
	if(fh) {
	
		if( (res = g->db_query("SELECT mac, ipaddr, access, ownerid FROM nodes ORDER BY ipaddr"))!= NULL) {
	
			for(i=0; i<res->nrows; i++) {
		
				unsigned long inet = inet_addr(g->db_get_data(res,i,"ipaddr"));
				int ownerid = atoi(g->db_get_data(res,i,"ownerid"));
				
				// networks test
				for(j=0; j<nc; j++)
					if(nets[j].address == (inet & nets[j].mask)) 
						break;
				
				// groups test
				m = gc;
				if(gc && ownerid)
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
					
				if( j!=nc && (gc==0 || m!=gc) ) {

					if( atoi(g->db_get_data(res,i,"access")) )
						fprintf(fh, "%s\t%s\n", inet_ntoa(inet), g->db_get_data(res,i,"mac"));
					else
						if( fm->dummy_macs )
							fprintf(fh, "%s\t00:00:00:00:00:00\n", inet_ntoa(inet));	
				}
			}
    			g->db_free(res);
		}	
	
		fclose(fh);
		system(fm->command);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ethers] reloaded", fm->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/ethers] Unable to write a temporary file '%s'", fm->base.instance, fm->file);

	for(i=0;i<nc;i++)
		free(nets[i].name);
	free(nets);
	
	for(i=0;i<gc;i++)
		free(ugps[i].name);
	free(ugps);
	
	free(fm->networks);
	free(fm->usergroups);
	free(fm->file);
	free(fm->command);
}

struct ethers_module * init(GLOBAL *g, MODULE *m)
{
	struct ethers_module *fm;
	unsigned char *instance, *s;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
	    return(NULL);
	
	instance = m->instance;
	
	fm = (struct ethers_module *) realloc(m, sizeof(struct ethers_module));
	
	fm->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	fm->base.instance = strdup(instance);

	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance, ":file");
	fm->file = strdup(g->iniparser_getstring(ini, s, "/etc/ethers"));
	free(s); s = g->str_concat(instance, ":command");
	fm->command = strdup(g->iniparser_getstring(ini, s, "arp -f /etc/ethers"));
	free(s); s = g->str_concat(instance, ":dummy_macs");	
	fm->dummy_macs = g->iniparser_getboolean(ini,s,0);
	free(s); s = g->str_concat(instance, ":networks");
	fm->networks = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":usergroups");
	fm->usergroups = strdup(g->iniparser_getstring(ini, s, ""));
	
	g->iniparser_freedict(ini);
	free(instance);
	free(s);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/ethers] initialized", fm->base.instance);
#endif	
	return(fm);
}
