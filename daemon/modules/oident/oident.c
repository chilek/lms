/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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
#include "oident.h"

unsigned long inet_addr(char *);
unsigned long htonl(unsigned long);
char * inet_ntoa(unsigned long);

void reload(GLOBAL *g, struct oident_module *o)
{
	FILE * fh;
	QUERY_HANDLE *res;
	int i;
		
	fh = fopen(o->file, "w");
	if(fh) {
		fprintf(fh, "%s\n", o->prefix);

		if( (res = g->db_query("SELECT name, mac, ipaddr FROM nodes"))!=NULL ) {
		
			for(i=0; i<res->nrows; i++) {
				unsigned char *name, *mac, *ipaddr;
				unsigned char *s;
			
				name = g->db_get_data(res,i,"name");
				mac = g->db_get_data(res,i,"mac");
				ipaddr = g->db_get_data(res,i,"ipaddr");
				
				if( name && mac && ipaddr ) {
					unsigned long inet = inet_addr(ipaddr);
					int j;
					for(j=0; j<o->netcount; j++)
						if(o->networks[j].network == (inet & o->networks[j].netmask)) 
							break;
					
					if( i != o->netcount ) {
						unsigned char my_mac[13];
						if( strlen(mac) >= 17 )
							snprintf(my_mac, 13, "%c%c%c%c%c%c%c%c%c%c%c%c", mac[0], mac[1], mac[3], mac[4], mac[6], mac[7], mac[9], mac[10], mac[12], mac[13], mac[15], mac[16]);
						else
							snprintf(my_mac, 13, "unknownmac");
						
						s = strdup(o->host);
						g->str_replace(&s, "%n", name);
						g->str_replace(&s, "%m", my_mac);
						g->str_replace(&s, "%i", inet_ntoa(inet));
						fprintf(fh, "%s\n", s);
						free(s);
					}
				}
			}
			g->db_free(res);
		}
		fprintf(fh, "%s", o->append);
		fclose(fh);
		system(o->command);
#ifdef DEBUG1
		syslog(LOG_INFO,"DEBUG: [%s/oident] Reload finished", o->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/oident] Unable to write a temporary file '%s'", o->base.instance, o->file);
	
	free(o->file);
	free(o->command);
	free(o->prefix);
	free(o->append);
	free(o->host);
	free(o->networks);
}

struct oident_module * init(GLOBAL *g, MODULE *m)
{
	struct oident_module *o;
	unsigned char *networks, *nextnet;
	unsigned char *instance, *s;
	dictionary *ini;
	int i, nc = 0;
	
	if(g->api_version != APIVERSION) 
		return (NULL);
	
	instance = strdup(m->instance);
	
	o = (struct oident_module*) realloc(m, sizeof(struct oident_module));
	
	o->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	o->base.instance = strdup(instance);
	
	ini = g->iniparser_load(g->inifile);
	
	s = g->str_concat(instance, ":start");
	o->prefix = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":end");
	o->append = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":host");
	o->host = strdup(g->iniparser_getstring(ini, s, "%i\t%n\tUNIX"));
	free(s); s = g->str_concat(instance, ":file");
	o->file = strdup(g->iniparser_getstring(ini, s, "/tmp/oidentd.conf"));
	free(s); s = g->str_concat(instance, ":command");
	o->command = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":networks");
	networks = strdup(g->iniparser_getstring(ini, s, "192.168.0.0/16 10.0.0.0/8"));
	o->networks = NULL;
	
	g->iniparser_freedict(ini);
	free(instance);
	free(s);
	
	while( *networks ) {
		unsigned char *prefixlen;
		unsigned long network;
		unsigned long netmask;
		unsigned char netmask_valid;
		
		nextnet = index(networks, ' ');

		if( nextnet ) *nextnet = 0;
		prefixlen = index(networks, '/');
		
		netmask_valid = 0;

		if( prefixlen ) {
			*prefixlen = 0;
			prefixlen ++;
			if(index(prefixlen, '.')) {
				netmask = inet_addr(prefixlen);
				netmask_valid = 1;
			}
			else {
				int len = atoi(prefixlen);
				if( len >= 0 && len <= 32 ) {
					netmask = 0xffffffff;
					len = 32 - len;
					while( len ) {
						netmask = netmask << 1;
						len--;
					}
					netmask = htonl(netmask);
					netmask_valid = 1;
				}
			}
		}
	
		network = inet_addr(networks);
		if( !netmask_valid ) { /* network mask autosense */
		
			if(! (network & 0x000000ff)) netmask = 0xffffff00;
			if(! (network & 0x0000ffff)) netmask = 0xffff0000;
			if(! (network & 0x00ffffff)) netmask = 0xff000000;
		}
		
		o->networks = realloc(o->networks, (sizeof(struct oident_net) * (nc + 1)));
		o->networks[nc].network = network;
		o->networks[nc].netmask = netmask;
		nc++;
//		fprintf(stderr, "Network: %s\n", inet_ntoa(network));
//		fprintf(stderr, "Netmask: %s\n", inet_ntoa(netmask));

		if( nextnet ) {
			networks = nextnet;
			networks ++;
		}
		else
			break;	
	}
	
	if( !(o->netcount = nc) ) {
		syslog(LOG_ERR, "[%s/oident] No networks for oidentd. Set 'networks' in lms.ini section [%s]", o->base.instance, o->base.instance);
		return(NULL);
	}

#ifdef DEBUG1
	syslog(LOG_INFO, "[%s/oident] Initialized",o->base.instance);
#endif
	return (o);
}

