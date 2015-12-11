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

#include "lmsd.h"
#include "traffic.h"

unsigned long inet_addr(char *);
char * inet_ntoa(unsigned long);

char * itoa(int i)
{
	static char string[15];
	sprintf(string, "%d", i);
	return string;
}

int is_host(HOSTS *hosts, int n, char *ip)
{
	int i;
	for(i=0; i<n; i++)
		if( strcmp(hosts[i].ipaddr, ip)==0 )
			return hosts[i].id;
	return 0;
} 

void reload(GLOBAL *g, struct traffic_module *traffic)
{
	QueryHandle *res;
	int i, k, j=0;
	HOSTS *hosts = NULL;
	FILE *fh;
	
	// first get hosts data
	res = g->db->query(g->db->conn, "SELECT id, ipaddr FROM vnodes");

	if( g->db->nrows(res) )
	{
		if(*traffic->begin_command)
		{
			system(traffic->begin_command);
		}
		
		for(i=0; i<g->db->nrows(res); i++)
		{
			hosts = (HOSTS *) realloc(hosts, sizeof(HOSTS) * (j + 1));
			hosts[i].ipaddr = strdup(inet_ntoa(inet_addr(g->db->get_data(res,i,"ipaddr"))));
			hosts[i].id = atoi(g->db->get_data(res,i,"id"));
			j++;
		}	
		
		// open log file for reading
		fh = fopen(traffic->file,"r");
		if(fh)
		{
			char *buffer, *host, *download, *upload;
			
			buffer = (char *) malloc(100+1);
			host = (char *) malloc(100+1);
			download = (char *) malloc(100+1);
			upload = (char *) malloc(100+1);
			
			// read file line by line
			while( fgets(buffer, 100, fh)!=NULL )
			{
				if ( sscanf(buffer, "%[^\t ] %[^\t ] %s", host, upload, download) !=3 )
					continue; //if invalid data format
					 
				if( (k = is_host(hosts, j, host)) ) // host exists ?
				{
					if( atoi(download) || atoi(upload) ) // write not null data
					{
						g->db->pexec(g->db->conn, "INSERT INTO stats (nodeid, dt, download, upload) VALUES (?, %NOW%, ?, ?)", itoa(k), download, upload);
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

	if(*traffic->end_command)
	{
		system(traffic->end_command);
	}

	g->db->free(&res);
	free(hosts);
	free(traffic->begin_command);
	free(traffic->end_command);
	free(traffic->file);
}

struct traffic_module * init(GLOBAL *g, MODULE *m)
{
	struct traffic_module *traffic;
	
	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	traffic = (struct traffic_module *) realloc(m, sizeof(struct traffic_module));
	
	traffic->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	traffic->file = strdup(g->config_getstring(traffic->base.ini, traffic->base.instance, "file", "/var/log/traffic.log"));
	
	traffic->begin_command = strdup(g->config_getstring(traffic->base.ini, traffic->base.instance, "begin_command", ""));
	traffic->end_command = strdup(g->config_getstring(traffic->base.ini, traffic->base.instance, "end_command", ""));

#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/traffic] initialized", traffic->base.instance);
#endif	
	return(traffic);
}
	
