/*
 * LMS version 1.3-cvs
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

unsigned long htonl(unsigned long);
unsigned long inet_addr(char *);

void reload(GLOBAL *g, struct hostfile_module *hm)
{
	FILE *fh;
	QUERY_HANDLE *res;
	int i;
	
	fh = fopen(hm->file, "w");
	if(fh)
	{
		fprintf(fh, "%s", hm->prefix);
		
		if( (res = g->db_pquery("SELECT name, mac, INET_NTOA(ipaddr) AS ip, access FROM nodes ? ORDER BY ipaddr",(hm->skip_dev_ips ? "WHERE ownerid<>0" : "")))!=NULL) {
		
			for(i=0; i<res->nrows; i++) {
				unsigned char *mac, *ip, *access, *name;
			
				mac 	= g->db_get_data(res,i,"mac");
				ip  	= g->db_get_data(res,i,"ip");
				access 	= g->db_get_data(res,i,"access");
				name 	= (unsigned char *) g->str_lwc(g->db_get_data(res,i,"name"));

				if(ip && mac && access) {
					
					unsigned long inet = inet_addr(ip);
					int j;
					
					for(j=0; j<hm->netcount; j++)
						if(hm->networks[j].network == (inet & hm->networks[j].netmask)) 
							break;
					
					if( j != hm->netcount ) {

						unsigned char *pattern, *s;
				
						if(*access == '1')
							pattern = hm->grant;
						else
							pattern = hm->deny;
				
						s = strdup(pattern);
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
		fprintf(fh, "%s", hm->append);
		fclose(fh);
		system(hm->command);
#ifdef DEBUG1
		syslog(LOG_INFO,"DEBUG: [%s/hostfile] reloaded",hm->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/hostfile] Unable to write a temporary file '%s'", hm->base.instance, hm->file);
	
	free(hm->prefix);
	free(hm->append);
	free(hm->grant);	
	free(hm->deny);
	free(hm->file);
	free(hm->command);
	free(hm->networks);
}

struct hostfile_module * init(GLOBAL *g, MODULE *m)
{
	struct hostfile_module *hm;
	unsigned char *instance, *s;
	unsigned char *networks, *net;
	dictionary *ini;
	int nc = 0;
	
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
	networks = strdup(g->iniparser_getstring(ini, s, "192.168.0.0/16 10.0.0.0/8"));
	hm->networks = NULL;

	g->iniparser_freedict(ini);
	free(instance);
	free(s);

	for( net=strtok(networks," "); net!=NULL; net=strtok(NULL," ") ) { 
		unsigned char *prefixlen;
		unsigned long network;
		unsigned long netmask;
		unsigned char netmask_valid;
		prefixlen = index(net, '/');

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
	
		network = inet_addr(net);
		if( !netmask_valid ) { // network mask autosense 
		
			if(! (network & 0x000000ff)) netmask = 0xffffff00;
			if(! (network & 0x0000ffff)) netmask = 0xffff0000;
			if(! (network & 0x00ffffff)) netmask = 0xff000000;
		}
		
		hm->networks = realloc(hm->networks, (sizeof(struct hosts_net) * (nc+1)));
		hm->networks[nc].network = network;
		hm->networks[nc].netmask = netmask;
		nc++;
	}
	free(networks);

	if( !(hm->netcount = nc) ) {
		syslog(LOG_ERR, "[%s/hostfile] No networks specified. Set 'networks' in lms.ini section [%s]", hm->base.instance, hm->base.instance);
		return(NULL);
	}
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/hostfile] initialized", hm->base.instance);
#endif
	return(hm);
}
