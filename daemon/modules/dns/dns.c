/*
 * LMS version 1.1-cvs
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
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

#include "almsd.h"
#include "dns.h"

#define BUFFERSIZE 1024

unsigned long inet_addr(unsigned char*);
unsigned char * inet_ntoa(unsigned long);
unsigned long ntohl(unsigned long);

unsigned char *load_file(unsigned char *name)
{
	unsigned char *ret = NULL;
	static unsigned char buffer[BUFFERSIZE];
	int fd, n, l = 0;
	
	fd = open(name, O_RDONLY);
	if(fd == -1) 
		return(NULL);

//warning this could be done in a better way.
	while( (n = read(fd, buffer, BUFFERSIZE)) > 0 ) {
		unsigned char *ret0 =  (unsigned char *) realloc(ret, (n + l + 1));
		if(!ret0) { 
			free(ret); 
			return(NULL); 
		}
		ret = ret0;
		memcpy(ret + l, buffer, n);
		l += n;
		ret[l] = 0;
	}
	close(fd);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: File '%s' loaded", name);
#endif
	return(ret);
}

int write_file(unsigned char *name, unsigned char *text)
{
	int fd, n, l = strlen(text);
	
	fd = open(name, O_WRONLY | O_CREAT | O_TRUNC, S_IRUSR | S_IWUSR | S_IRGRP | S_IROTH);
	if(fd == -1) 
		return (-1);

//warning this could be done in a better way.
	while( (n = write(fd, text, l)) > 0 ) {
		l -= n;
		text += n;
		if(l <= 0) break;
	}
	close(fd);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: File '%s' writed", name);
#endif
	return (0);
}

void reload(GLOBAL *g, struct dns_module *dns)
{
	QUERY_HANDLE *res;
	unsigned char *configfile = 0;
	unsigned char *configentries = strdup("");
	
	struct hostcache
	{
		unsigned char *name;
		unsigned char *mac;
		unsigned long ipaddr;
	} *hosts = NULL;
	int i, nh = 0;

	if ( (res = g->db_query("SELECT name, mac, ipaddr FROM nodes ORDER BY name"))!=NULL ) {
	
		for(i=0; i<res->nrows; i++) {
			
			unsigned char *name, *mac, *ipaddr;
			
			name = g->db_get_data(res,i,"name");
			mac = g->db_get_data(res,i,"mac");
			ipaddr = g->db_get_data(res,i,"ipaddr");
			
			if(name && mac && ipaddr) {
				hosts = (struct hostcache*) realloc(hosts, sizeof(struct hostcache) * (nh + 1));
				hosts[nh].name = strdup(g->str_lwc(name));
				hosts[nh].mac = strdup(mac);
				hosts[nh].ipaddr = inet_addr(ipaddr);
				nh++;
			}
		}
		g->db_free(res);
	}
	
	if ( (res = g->db_query("SELECT inet_ntoa(address) AS address, mask, domain, dns FROM networks"))!=NULL) {

		configfile = load_file(dns->confpattern);
	
		for (i=0; i<res->nrows; i++) {
			unsigned char *s, *d, *e, *name, *dnsserv;
			unsigned long netmask, network;
		
			e = g->db_get_data(res,i,"address");
			d = g->db_get_data(res,i,"mask");
			name = g->db_get_data(res,i,"domain");
			dnsserv = g->db_get_data(res,i,"dns");
		
			if ( d && e && name ) {
				int prefixlen; // in bytes! 
				unsigned char *finfile, *ftmpfile;
				unsigned char *rinfile, *rtmpfile;
			
				unsigned char *forwardzone;
				unsigned char *reversezone;
			
				unsigned char *outfile;
			
				if( strlen(d) && strlen(e) && strlen(name) ) {
					unsigned long host_netmask;
					unsigned char *forwardhosts = strdup("");
					unsigned char *reversehosts = strdup("");
				
					int j;
				
					network = inet_addr(e);
					netmask = inet_addr(d);

					host_netmask = ntohl(netmask);
					prefixlen = 1; // in bytes! 
					if(host_netmask & 0x0001ffff) prefixlen = 2;
					if(host_netmask & 0x000001ff) prefixlen = 3;


					finfile = dns->fpatterns;//strdup
					rinfile = dns->rpatterns;//strdup

					if(finfile[strlen(finfile) - 1] != '/') {
						ftmpfile = g->str_concat(finfile, "/");
					} else
						ftmpfile = strdup(finfile);
					
					if(rinfile[strlen(rinfile) - 1] != '/') {
						rtmpfile = g->str_concat(rinfile, "/");
					} else 
						rtmpfile = rinfile;
				
					finfile = g->str_concat(ftmpfile, name);
					rinfile = g->str_concat(rtmpfile, inet_ntoa(network));
									
					forwardzone = load_file(finfile);
					reversezone = load_file(rinfile);
				
					free(finfile);
					free(rinfile);
					free(rtmpfile);
					free(ftmpfile);
				
					if(!forwardzone) forwardzone = load_file(dns->fgeneric);
					if(!reversezone) reversezone = load_file(dns->rgeneric);
				
					if(forwardzone && reversezone) {
						unsigned char serial[12];
						unsigned char netpart[30];
						unsigned long ip;
												
						for(j = 0; j<nh; j++) {
							unsigned char *forwardhost;
							unsigned char *reversehost;
							unsigned char hostpart[30];
							unsigned char *tmphosts;
							unsigned long ip;
						
							if( (hosts[i].ipaddr & netmask) == network) {
								forwardhost = strdup(dns->forward);
								reversehost = strdup(dns->reverse);
					
								g->str_replace(&forwardhost, "%n", hosts[j].name);
								g->str_replace(&reversehost, "%n", hosts[j].name);
								g->str_replace(&forwardhost, "%i", inet_ntoa(hosts[j].ipaddr));
								g->str_replace(&reversehost, "%i", inet_ntoa(hosts[j].ipaddr));
								g->str_replace(&forwardhost, "%d", name);
								g->str_replace(&reversehost, "%d", name);
					
								ip = ntohl(hosts[j].ipaddr);
					
								switch(prefixlen) {
								case 1:
									snprintf(hostpart, 30, "%d.%d.%d", ip & 0xff, (ip >> 8) & 0xff, (ip >> 24) & 0xff);
									break;
								case 2:
									snprintf(hostpart, 30, "%d.%d", ip & 0xff, (ip >> 8) & 0xff);
									break;					
								case 3:
								default:
									snprintf(hostpart, 30, "%d", ip & 0xff);
									break;
								}
						
								g->str_replace(&forwardhost, "%c", hostpart);
								g->str_replace(&reversehost, "%c", hostpart);
							
								tmphosts = strdup(forwardhosts); free(forwardhosts);
								forwardhosts = g->str_concat(tmphosts, forwardhost);
								free(tmphosts);
								
								tmphosts = strdup(reversehosts); free(reversehosts);
								reversehosts = g->str_concat(tmphosts, reversehost);
								free(tmphosts);
								
								free(forwardhost);
								free(reversehost);
							}
						}
			
						g->str_replace(&forwardzone, "%h", forwardhosts);
						g->str_replace(&reversezone, "%h", reversehosts);
						free(forwardhosts);
						free(reversehosts);
						g->str_replace(&forwardzone, "%d", name);
						g->str_replace(&reversezone, "%d", name);
					
						snprintf(serial, 12, "%d", time(NULL));
						
						g->str_replace(&forwardzone, "%s", serial);
						g->str_replace(&reversezone, "%s", serial);

						ip = ntohl(network);
	
						switch(prefixlen) {
						case 1:
							snprintf(netpart, 30, "%d", (ip >> 24) & 0xff);
							break;
						case 2:
							snprintf(netpart, 30, "%d.%d", (ip >> 16) & 0xff, (ip >> 24) & 0xff);
							break;
						case 3:
						default:
							snprintf(netpart, 30, "%d.%d.%d", (ip >> 8) & 0xff, (ip >> 16) & 0xff, (ip >> 24) & 0xff);
							break;
						}

						g->str_replace(&forwardzone, "%c", netpart);
						g->str_replace(&reversezone, "%c", netpart);
					
						g->str_replace(&forwardzone, "%v", dnsserv ? (dnsserv) : ((unsigned char*) "127.0.0.1"));
						g->str_replace(&reversezone, "%v", dnsserv ? (dnsserv) : ((unsigned char*) "127.0.0.1"));
				
						finfile = dns->fzones;//strdup
						rinfile = dns->rzones;//strdup
		
						if(finfile[strlen(finfile) - 1] != '/')
							ftmpfile = g->str_concat(finfile, "/");
						else
							ftmpfile = finfile;
							
						if(rinfile[strlen(rinfile) - 1] != '/')
							rtmpfile = g->str_concat(rinfile, "/");
						else
							rtmpfile = rinfile;
						
						finfile = g->str_concat(ftmpfile, name);
						rinfile = g->str_concat(rtmpfile, inet_ntoa(network));
				
						if(write_file(finfile, forwardzone) < 0)
							syslog(LOG_WARNING, "[%s/dns] Unable to open output forward zone file '%s' for domain '%s', skipping forward zone for this domain.", dns->base.instance, finfile, name);
						else {
							unsigned char *zone, *tmpconf;
							zone = strdup(dns->confforward);
							g->str_replace(&zone, "%n", name);
							g->str_replace(&zone, "%c", netpart);
							g->str_replace(&zone, "%i", inet_ntoa(network));
							tmpconf = strdup(configentries);
							free(configentries);
							configentries = g->str_concat(tmpconf, zone);
							free(tmpconf);
							free(zone);
						}

						if(write_file(rinfile, reversezone) < 0)
							syslog(LOG_WARNING, "[%s/dns] Unable to open output reverse zone file '%s' for domain '%s', skipping reverse zone for this domain.", dns->base.instance, rinfile, name);
						else {
							unsigned char *zone, *tmpconf;
							zone = strdup(dns->confreverse);
							g->str_replace(&zone, "%n", name);
							g->str_replace(&zone, "%c", netpart);
							g->str_replace(&zone, "%i", inet_ntoa(network));
							tmpconf = strdup(configentries);
							free(configentries);
							configentries = g->str_concat(tmpconf, zone);
							free(tmpconf);
							free(zone);
						}	
						
						free(rtmpfile);
						free(ftmpfile);
						free(finfile);
						free(rinfile);
						free(forwardzone);
						free(reversezone);
					}
					else
						syslog(LOG_WARNING, "[%s/dns] Unable to open one of the templates for domain '%s', skipping this domain. Set at least 'generic-forward' and 'generic-reverse' properly.", dns->base.instance, name);
				}
			}	
		}
		g->db_free(res);
	}
	
	for(i = 0; i<nh; i++) {
		free(hosts[i].name);
		free(hosts[i].mac);
	}
	free(hosts);
	
	if(configfile) {
		g->str_replace(&configfile, "%z", configentries);
		if(write_file(dns->confout, configfile) < 0)
			syslog(LOG_ERR, "[%s/dns] Unable to write DNS configuration file '%s'", dns->base.instance, dns->confout);
		free(configfile);
		system(dns->command);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/dns] reloaded",dns->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/dns] Unable to open DNS pattern file '%s'", dns->base.instance, dns->confpattern);

	free(configentries);
	free(dns->fpatterns);
	free(dns->rpatterns);
	free(dns->fgeneric);
	free(dns->rgeneric);
	free(dns->fzones);
	free(dns->rzones);
	free(dns->forward);
	free(dns->reverse);
	free(dns->command);
	free(dns->confpattern);
	free(dns->confout);
	free(dns->confforward);
	free(dns->confreverse);
}

struct dns_module * init(GLOBAL *g, MODULE *m)
{
	struct dns_module *dns;
	unsigned char *instance, *s;
	dictionary *ini;
	int i;

	if(g->api_version != APIVERSION) 
		return (NULL);
	
	instance = m->instance;
	
	dns = (struct dns_module*) realloc(m, sizeof(struct dns_module));
	
	dns->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	dns->base.instance = strdup(instance);
	
	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance, ":forward-patterns");
	dns->fpatterns = strdup(g->iniparser_getstring(ini, s, "forward"));
	free(s); s = g->str_concat(instance, ":reverse-patterns");
	dns->rpatterns = strdup(g->iniparser_getstring(ini, s, "reverse"));
	free(s); s = g->str_concat(instance, ":generic-forward");
	dns->fgeneric = strdup(g->iniparser_getstring(ini, s, "modules/dns/sample/forward/generic"));
	free(s); s = g->str_concat(instance, ":generic-reverse");	
	dns->rgeneric = strdup(g->iniparser_getstring(ini, s, "modules/dns/sample/reverse/generic"));
	free(s); s = g->str_concat(instance, ":forward-zones");
	dns->fzones = strdup(g->iniparser_getstring(ini, s, "modules/dns/sample/out/forward"));
	free(s); s = g->str_concat(instance, ":reverse-zones");
	dns->rzones = strdup(g->iniparser_getstring(ini, s, "modules/dns/sample/out/reverse"));
	free(s); s = g->str_concat(instance, ":host-forward");
	dns->forward = strdup(g->iniparser_getstring(ini, s, "%n IN A %i\n"));
	free(s); s = g->str_concat(instance, ":host-reverse");
	dns->reverse= strdup(g->iniparser_getstring(ini, s, "%c IN PTR %n.%d.\n"));
	free(s); s = g->str_concat(instance, ":command");
	dns->command = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":conf-pattern");
	dns->confpattern = strdup(g->iniparser_getstring(ini, s, "modules/dns/sample/named.conf"));
	free(s); s = g->str_concat(instance, ":conf-output");
	dns->confout = strdup(g->iniparser_getstring(ini, s, "/tmp/named.conf"));
	free(s); s = g->str_concat(instance, ":conf-forward-entry");
	dns->confforward = strdup(g->iniparser_getstring(ini, s, "zone \"%n\" {\ntype master;\nfile \"forward/%n\";\nnotify yes;\n};\n"));
	free(s); s = g->str_concat(instance, ":conf-reverse-entry");
	dns->confreverse = strdup(g->iniparser_getstring(ini, s, "zone \"%c.in-addr.arpa\" {\ntype master;\nfile \"reverse/%i\";\nnotify yes;\n};\n"));

	g->iniparser_freedict(ini);
	free(instance);
	free(s);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/dns] Initialized",dns->base.instance);		
#endif	
	return (dns);
}

