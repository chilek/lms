/*
 * LMS version 1.4-cvs
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
#include "traffic.h"

unsigned long inet_addr(unsigned char*);
unsigned char * inet_ntoa(unsigned long);

char * itoa(int i)
{
	static char string[15];
	sprintf(string, "%d", i);
	return string;
}

int is_host(HOSTS *hosts, int n, unsigned char *ip)
{
	int i;
	for(i=0; i<n; i++)
		if( strcmp(hosts[i].ipaddr, ip)==0 )
			return hosts[i].id;
	return 0;
} 

void reload(GLOBAL *g, struct traffic_module *traffic)
{
	QUERY_HANDLE *res, *result;
	int i, k, j=0;
	HOSTS *hosts = NULL;
	FILE *fh;
	
	// first get hosts data
	if( (res = g->db_query("SELECT id, ipaddr FROM nodes"))!=NULL) { 

		for(i=0; i<res->nrows; i++) {
		
			hosts = (HOSTS *) realloc(hosts, sizeof(HOSTS) * (j + 1));
			hosts[i].ipaddr = strdup(inet_ntoa(inet_addr(g->db_get_data(res,i,"ipaddr"))));
			hosts[i].id = atoi(g->db_get_data(res,i,"id"));
			j++;
		}	
		g->db_free(res);
		
		// open log file for reading
		fh = fopen(traffic->file,"r");
		if(fh) {
			unsigned char *buffer, *host, *download, *upload, *insert;
			
			buffer = (char *) malloc(100+1);
			host = (char *) malloc(100+1);
			download = (char *) malloc(100+1);
			upload = (char *) malloc(100+1);
			
			// read file line by line
			while( fgets(buffer, 100, fh)!=NULL ) {

				if ( sscanf(buffer, "%[^\t ] %[^\t ] %s", host, upload, download) !=3 )
					continue; //if invalid data format
					 
				if( k = is_host(hosts, j, host) ) { // host exists ?
					
					if( atoi(download) || atoi(upload) ) { // write not null data
					
						insert = strdup("INSERT INTO stats (nodeid, dt, download, upload) VALUES (%nodeid, %NOW%, %download, %upload)"); 
						g->str_replace(&insert, "%nodeid", itoa(k));
						g->str_replace(&insert, "%download", download);
						g->str_replace(&insert, "%upload", upload);
						g->db_exec(insert);
						free(insert);
					}
				}
			}
			free(buffer);
			free(host);
			free(download);
			free(upload);
		}
		else
			syslog(LOG_ERR, "[%s/traffic] Unable to read file '%s'",traffic->base.instance, traffic->file);
		
		for(i=0; i<j; i++) free(hosts[i].ipaddr);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/traffic] reloaded", traffic->base.instance);
#endif
	} 
	else 
		syslog(LOG_ERR, "[%s/traffic] Unable to read table 'nodes'", traffic->base.instance);

	free(hosts);
	free(traffic->file);
}

struct traffic_module * init(GLOBAL *g, MODULE *m)
{
	struct traffic_module *traffic;
	unsigned char *instance, *s;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
	    return (NULL);
	
	instance = m->instance;
	
	traffic = (struct traffic_module *) realloc(m, sizeof(struct traffic_module));
	
	traffic->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	traffic->base.instance = strdup(instance);

	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance, ":file");
	traffic->file = strdup(g->iniparser_getstring(ini, s, "/var/log/traffic.log"));
	
	g->iniparser_freedict(ini);
	free(s);
	free(instance);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/traffic] initialized", traffic->base.instance);
#endif	
	return(traffic);
}
	
