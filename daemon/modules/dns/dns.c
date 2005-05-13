/*
 * LMS version 1.7-cvs
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
#include <unistd.h>
#include <stdlib.h>
#include <syslog.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

#include "lmsd.h"
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
	unsigned char *configfile = 0;
	unsigned char *configentries = strdup("");
	QueryHandle *res, *res1;
	int i, j, m, k=2, gc=0, nc=0, nh=0, n=2;
	struct hostcache
	{
		unsigned char *name;
		unsigned char *mac;
		unsigned long ipaddr;
	} *hosts = NULL;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(dns->networks);	
	char *netname = strdup(netnames);
    
	struct group *ugps = (struct group *) malloc(sizeof(struct group));
	char *groupnames = strdup(dns->usergroups);	
	char *groupname = strdup(groupnames);

	while( n>1 )
	{
    		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) )
		{
		        res = g->db_pquery(g->conn, "SELECT name, address, INET_ATON(mask) AS mask  FROM networks WHERE UPPER(name)=UPPER('?')",netname);
			if( g->db_nrows(res) )
			{
				nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].name = strdup(g->db_get_data(res,0,"name"));
				nets[nc].address = inet_addr(g->db_get_data(res,0,"address"));
				nc++;
			}
	    		g->db_free(&res);
		}				
	}
	free(netname); free(netnames);

	while( k>1 )
	{
		k = sscanf(groupnames, "%s %[._a-zA-Z0-9- ]", groupname, groupnames);

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

	res = g->db_query(g->conn, "SELECT name, mac, ipaddr, ownerid FROM nodes ORDER BY ipaddr");

	for(i=0; i<g->db_nrows(res); i++)
	{
		int ownerid = atoi(g->db_get_data(res,i,"ownerid"));
		unsigned char *name = g->db_get_data(res,i,"name");
		char *mac = g->db_get_data(res,i,"mac");
		char *ipaddr = g->db_get_data(res,i,"ipaddr");
		
		if(name && mac && ipaddr)
		{
			// groups test
			if(gc)
			{
				if( ownerid==0 )
					continue;
				m = gc;
				res1 = g->db_pquery(g->conn, "SELECT usergroupid FROM userassignments WHERE userid=?", g->db_get_data(res,i,"ownerid"));
				for(k=0; k<g->db_nrows(res1); k++)
				{
					int groupid = atoi(g->db_get_data(res1, k, "usergroupid"));
					for(m=0; m<gc; m++) 
						if(ugps[m].id==groupid) 
							break;
					if(m!=gc) break;
				}
				g->db_free(&res1);
				if( m==gc )
					continue;
			}
		
			hosts = (struct hostcache*) realloc(hosts, sizeof(struct hostcache) * (nh + 1));
			hosts[nh].name = strdup(name);
			hosts[nh].mac = strdup(mac);
			hosts[nh].ipaddr = inet_addr(ipaddr);
			nh++;
		}
	}
	g->db_free(&res);
	
	res = g->db_query(g->conn, "SELECT inet_ntoa(address) AS address, mask, domain, dns FROM networks");

	if( g->db_nrows(res) )
	{
		configfile = load_file(dns->confpattern);
	
		for (i=0; i<g->db_nrows(res); i++)
		{
			unsigned char *d, *e, *name, *dnsserv;
			unsigned long netmask, network;
		
			e = g->db_get_data(res,i,"address");
			d = g->db_get_data(res,i,"mask");
			name = g->db_get_data(res,i,"domain");
			dnsserv = g->db_get_data(res,i,"dns");

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

			if ( d && e && name )
			{
				int prefixlen; // in bytes! 
				unsigned char *finfile, *ftmpfile;
				unsigned char *rinfile, *rtmpfile;
			
				unsigned char *forwardzone;
				unsigned char *reversezone;
			
				if( strlen(d) && strlen(e) && strlen(name) )
				{
					unsigned long host_netmask;
					unsigned char *forwardhosts = strdup("");
					unsigned char *reversehosts = strdup("");
				
					host_netmask = ntohl(netmask);
					prefixlen = 1; // in bytes! 
					if(host_netmask & 0x0001ffff) prefixlen = 2;
					if(host_netmask & 0x000001ff) prefixlen = 3;

					finfile = dns->fpatterns;//strdup
					rinfile = dns->rpatterns;//strdup

					if(finfile[strlen(finfile) - 1] != '/')
						ftmpfile = g->str_concat(finfile, "/");
					else
						ftmpfile = strdup(finfile);
					
					if(rinfile[strlen(rinfile) - 1] != '/')
						rtmpfile = g->str_concat(rinfile, "/");
					else 
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
				
					if(forwardzone && reversezone)
					{
						unsigned char serial[12];
						unsigned char netpart[30];
						unsigned long ip;
												
						for(j = 0; j<nh; j++)
						{
							unsigned char *forwardhost;
							unsigned char *reversehost;
							unsigned char hostpart[30];
							unsigned char *tmphosts;
						
							if( (hosts[j].ipaddr & netmask) == network)
							{
								forwardhost = strdup(dns->forward);
								reversehost = strdup(dns->reverse);
					
								g->str_replace(&forwardhost, "%n", hosts[j].name);
								g->str_replace(&reversehost, "%n", hosts[j].name);
								g->str_replace(&forwardhost, "%i", inet_ntoa(hosts[j].ipaddr));
								g->str_replace(&reversehost, "%i", inet_ntoa(hosts[j].ipaddr));
								g->str_replace(&forwardhost, "%d", name);
								g->str_replace(&reversehost, "%d", name);
					
								ip = ntohl(hosts[j].ipaddr);
					
								switch(prefixlen)
								{
									case 1:
									snprintf(hostpart, 30, "%d.%d.%d", (int)(ip & 0xff),(int)((ip >> 8) & 0xff),(int)((ip >> 24) & 0xff));
									break;
									case 2:
									snprintf(hostpart, 30, "%d.%d", (int)(ip & 0xff),(int)((ip >> 8) & 0xff));
									break;					
									case 3:
									default:
									snprintf(hostpart, 30, "%d", (int)(ip & 0xff));
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
					
						snprintf(serial, 12, "%d", (int) time(NULL));
						
						g->str_replace(&forwardzone, "%s", serial);
						g->str_replace(&reversezone, "%s", serial);

						ip = ntohl(network);
	
						switch(prefixlen)
						{
							case 1:
							snprintf(netpart, 30, "%d", (int)((ip >> 24) & 0xff));
							break;
							case 2:
							snprintf(netpart, 30, "%d.%d", (int)((ip >> 16) & 0xff),(int)((ip >> 24) & 0xff));
							break;
							case 3:
							default:
							snprintf(netpart, 30, "%d.%d.%d", (int)((ip >> 8) & 0xff),(int)((ip >> 16) & 0xff),(int)((ip >> 24) & 0xff));
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
	
		if(configfile) 
		{
			g->str_replace(&configfile, "%z", configentries);
			if(write_file(dns->confout, configfile) < 0)
				syslog(LOG_ERR, "[%s/dns] Unable to write DNS configuration file '%s'", dns->base.instance, dns->confout);
			free(configfile);
			system(dns->command);
		}
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/dns] reloaded",dns->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/dns] Unable to open DNS pattern file '%s'", dns->base.instance, dns->confpattern);

	g->db_free(&res);

	//cleanup	
	for(i = 0; i<nh; i++) {
		free(hosts[i].name);
		free(hosts[i].mac);
	}
	free(hosts);

	for(i=0;i<nc;i++)
		free(nets[i].name);
	free(nets);
	
	for(i=0;i<gc;i++)
		free(ugps[i].name);
	free(ugps);

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
	free(dns->networks);
	free(dns->usergroups);
}

struct dns_module * init(GLOBAL *g, MODULE *m)
{
	struct dns_module *dns;

	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	dns = (struct dns_module*) realloc(m, sizeof(struct dns_module));
	
	dns->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	dns->fpatterns = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "forward-patterns", "forward"));
	dns->rpatterns = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "reverse-patterns", "reverse"));
	dns->fgeneric = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "generic-forward", "modules/dns/sample/forward/generic"));
	dns->rgeneric = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "generic-reverse", "modules/dns/sample/reverse/generic"));
	dns->fzones = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "forward-zones", "modules/dns/sample/out/forward"));
	dns->rzones = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "reverse-zones", "modules/dns/sample/out/reverse"));
	dns->forward = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "host-forward", "%n IN A %i\n"));
	dns->reverse= strdup(g->config_getstring(dns->base.ini, dns->base.instance, "host-reverse", "%c IN PTR %n.%d.\n"));
	dns->command = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "command", ""));
	dns->confpattern = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "conf-pattern", "modules/dns/sample/named.conf"));
	dns->confout = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "conf-output", "/tmp/named.conf"));
	dns->confforward = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "conf-forward-entry", "zone \"%n\" {\ntype master;\nfile \"forward/%n\";\nnotify yes;\n};\n"));
	dns->confreverse = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "conf-reverse-entry", "zone \"%c.in-addr.arpa\" {\ntype master;\nfile \"reverse/%i\";\nnotify yes;\n};\n"));
	dns->networks = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "networks", ""));
	dns->usergroups = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "usergroups", ""));

#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/dns] Initialized",dns->base.instance);		
#endif	
	return (dns);
}
