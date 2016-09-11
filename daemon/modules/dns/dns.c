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

unsigned long inet_addr(char*);
char * inet_ntoa(unsigned long);
unsigned long ntohl(unsigned long);

char *load_file(char *name)
{
    char *ret = NULL;
    static char buffer[BUFFERSIZE];
    int fd, n, l = 0;

    fd = open(name, O_RDONLY);
    if (fd == -1) 
        return(NULL);

    //warning this could be done in a better way.
    while ((n = read(fd, buffer, BUFFERSIZE)) > 0) {
        char *ret0 =  (char *) realloc(ret, (n + l + 1));
        if (!ret0) {
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

int write_file(char *name, char *text)
{
    int fd, n, l = strlen(text);

    fd = open(name, O_WRONLY | O_CREAT | O_TRUNC, S_IRUSR | S_IWUSR | S_IRGRP | S_IROTH);
    if (fd == -1) 
        return (-1);

    //warning this could be done in a better way.
    while ((n = write(fd, text, l)) > 0) {
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
    char *configfile = 0;
    char *configentries = strdup("");
    QueryHandle *res, *res1;
    int i, j, m, k=2, gc=0, nc=0, nh=0, dc=0, n=2;
    struct hostcache
    {
        char *name;
        unsigned long ipaddr;
    } *hosts = NULL;

    char **domains = (char **) malloc(sizeof(char*));

    struct net *nets = (struct net *) malloc(sizeof(struct net));
    char *netnames = strdup(dns->networks);
    char *netname = strdup(netnames);

    struct group *ugps = (struct group *) malloc(sizeof(struct group));
    char *groupnames = strdup(dns->customergroups);
    char *groupname = strdup(groupnames);

    while (n>1) {
        n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

        if (strlen(netname)) {
            res = g->db->pquery(g->db->conn, "SELECT name, address FROM networks WHERE UPPER(name)=UPPER('?')",netname);
            if (g->db->nrows(res)) {
                nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
                nets[nc].name = strdup(g->db->get_data(res,0,"name"));
                nets[nc].address = inet_addr(g->db->get_data(res,0,"address"));
                nc++;
            }
            g->db->free(&res);
        }
    }
    free(netname); free(netnames);

    while (k>1) {
        k = sscanf(groupnames, "%s %[._a-zA-Z0-9- ]", groupname, groupnames);

        if (strlen(groupname)) {
            res = g->db->pquery(g->db->conn, "SELECT name, id FROM customergroups WHERE UPPER(name)=UPPER('?')",groupname);

            if (g->db->nrows(res)) {
                ugps = (struct group *) realloc(ugps, (sizeof(struct group) * (gc+1)));
                ugps[gc].name = strdup(g->db->get_data(res,0,"name"));
                ugps[gc].id = atoi(g->db->get_data(res,0,"id"));
                gc++;
            }
            g->db->free(&res);
        }
    }
    free(groupname); free(groupnames);

    res = g->db->query(g->db->conn, "SELECT LOWER(name) AS name, ipaddr, ipaddr_pub, ownerid FROM vnodes ORDER BY ipaddr");

    for (i=0; i<g->db->nrows(res); i++) {
        int ownerid      = atoi(g->db->get_data(res,i,"ownerid"));
        char *name       = g->db->get_data(res,i,"name");
        char *ipaddr     = g->db->get_data(res,i,"ipaddr");
        char *ipaddr_pub = g->db->get_data(res,i,"ipaddr_pub");

        // groups test
        if (gc) {
            if (!ownerid)
                continue;
            m = gc;
            res1 = g->db->pquery(g->db->conn, "SELECT customergroupid FROM customerassignments WHERE customerid=?", g->db->get_data(res,i,"ownerid"));
            for (k=0; k<g->db->nrows(res1); k++) {
                int groupid = atoi(g->db->get_data(res1, k, "customergroupid"));
                for(m=0; m<gc; m++)
                    if(ugps[m].id==groupid)
                        break;
                if(m!=gc) break;
            }
            g->db->free(&res1);
            if (m==gc)
                continue;
        }

        hosts = (struct hostcache*) realloc(hosts, sizeof(struct hostcache) * (nh + 1));
        hosts[nh].name = strdup(name);
        hosts[nh].ipaddr = inet_addr(ipaddr);
        nh++;

        if (ipaddr_pub) {
            hosts = (struct hostcache*) realloc(hosts, sizeof(struct hostcache) * (nh + 1));
            hosts[nh].name = strdup(name);
            hosts[nh].ipaddr = inet_addr(ipaddr_pub);
            nh++;
        }
    }
    g->db->free(&res);

    res = g->db->query(g->db->conn, "SELECT inet_ntoa(address) AS address, mask, domain, dns "
        "FROM networks WHERE domain <> ''");

    if (g->db->nrows(res)) {
        // Load main config file pattern
        configfile = load_file(dns->confpattern);

        for (i=0; i<g->db->nrows(res); i++) {
            int domainmatch  = 0;
            int reversematch = 0;

            char *e       = g->db->get_data(res,i,"address");
            char *d       = g->db->get_data(res,i,"mask");
            char *name    = g->db->get_data(res,i,"domain");
            char *dnsserv = g->db->get_data(res,i,"dns");

            unsigned long network = inet_addr(e);
            unsigned long netmask = inet_addr(d);

            // networks test
            if(nc) {
                for(j=0; j<nc; j++)
                    if(nets[j].address==network)
                        break;
                if(j==nc)
                    continue;
            }

            int prefixlen = 1; // in bytes!
            char *finfile, *ftmpfile;
            char *rinfile, *rtmpfile;
            char *forwardzone;
            char *reversezone;
            char *forwardhosts = strdup("");
            char *reversehosts = strdup("");
            char netpart[30];

            unsigned long ip = ntohl(network);
            unsigned long host_netmask = ntohl(netmask);

            if (host_netmask & 0x0001ffff) prefixlen = 2;
            if (host_netmask & 0x000001ff) prefixlen = 3;

            // Set netpart string for reverse zone
            switch (prefixlen) {
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

            // check if domain was processed yet
            for (j=0; j<dc; j++)
                if (strcmp(name, domains[j])==0) {
                    domainmatch = 1;
                    break;
                }

            if (!domainmatch) {
                // add domain to table
                dc++;
                domains = realloc(domains, sizeof(char*) * dc);
                domains[dc - 1] = strdup(name);

                finfile = dns->fpatterns;
            }
            else {
                finfile = dns->fzones;
            }

            // check if reverse-domain was processed yet
            for (j=0; j<dc; j++)
                if (strcmp(netpart, domains[j])==0) {
                    reversematch = 1;
                    break;
                }

            if (!reversematch) {
                // add domain to table
                dc++;
                domains = realloc(domains, sizeof(char*) * dc);
                domains[dc - 1] = strdup(netpart);

                rinfile = dns->rpatterns;
            }
            else {
                rinfile = dns->rzones;
            }

            // Create paths to zone patterns
            if (finfile[strlen(finfile) - 1] != '/')
                ftmpfile = g->str_concat(finfile, "/");
            else
                ftmpfile = strdup(finfile);

            if (rinfile[strlen(rinfile) - 1] != '/')
                rtmpfile = g->str_concat(rinfile, "/");
            else
                rtmpfile = strdup(rinfile);

            finfile = g->str_concat(ftmpfile, name);
            rinfile = g->str_concat(rtmpfile, netpart);

            // Load pattern files
            forwardzone = load_file(finfile);
            reversezone = load_file(rinfile);

            if (!forwardzone) forwardzone = load_file(dns->fgeneric);
            if (!reversezone) reversezone = load_file(dns->rgeneric);
            if (!forwardzone) syslog(LOG_WARNING, "[%s/dns] Unable to open file '%s'.", dns->base.instance, dns->fgeneric);
            if (!forwardzone) syslog(LOG_WARNING, "[%s/dns] Unable to open file '%s'.", dns->base.instance, dns->rgeneric);

            free(finfile);
            free(rinfile);
            free(ftmpfile);
            free(rtmpfile);

            // Patterns loaded, loop over hosts
            if (forwardzone && reversezone) {
                char serial[12];

                for (j=0; j<nh; j++) {
                    if ((hosts[j].ipaddr & netmask) == network) {
                        char hostpart[30];
                        char *tmphosts;

                        char *forwardhost = strdup(dns->forward);
                        char *reversehost = strdup(dns->reverse);

                        g->str_replace(&forwardhost, "%n", hosts[j].name);
                        g->str_replace(&reversehost, "%n", hosts[j].name);
                        g->str_replace(&forwardhost, "%i", inet_ntoa(hosts[j].ipaddr));
                        g->str_replace(&reversehost, "%i", inet_ntoa(hosts[j].ipaddr));
                        g->str_replace(&forwardhost, "%d", name);
                        g->str_replace(&reversehost, "%d", name);

                        ip = ntohl(hosts[j].ipaddr);

                        switch (prefixlen) {
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

                        tmphosts = strdup(forwardhosts);
                        free(forwardhosts);
                        forwardhosts = g->str_concat(tmphosts, forwardhost);
                        free(tmphosts);

                        tmphosts = strdup(reversehosts);
                        free(reversehosts);
                        reversehosts = g->str_concat(tmphosts, reversehost);
                        free(tmphosts);

                        free(forwardhost);
                        free(reversehost);
                    }
                }

                // Add hosts to zone file
                if (domainmatch) {
                    // Add existing domain content
                    char *tmphosts = strdup(forwardzone);
                    g->str_replace(&tmphosts, "%h", "");
                    free(forwardzone);
                    forwardzone = g->str_concat(tmphosts, forwardhosts);
                    free(tmphosts);
                }
                else {
                    g->str_replace(&forwardzone, "%h", forwardhosts);
                }

                // Add hosts to reverse-zone file
                if (reversematch) {
                    // Add existing domain content
                    char *tmphosts = strdup(reversezone);
                    g->str_replace(&tmphosts, "%h", "");
                    free(reversezone);
                    reversezone = g->str_concat(tmphosts, reversehosts);
                    free(tmphosts);
                }
                else {
                    g->str_replace(&reversezone, "%h", reversehosts);
                }

                free(forwardhosts);
                free(reversehosts);

                g->str_replace(&forwardzone, "%d", name);
                g->str_replace(&reversezone, "%d", name);

                snprintf(serial, 12, "%d", (int) time(NULL));

                g->str_replace(&forwardzone, "%s", serial);
                g->str_replace(&reversezone, "%s", serial);

                g->str_replace(&forwardzone, "%c", netpart);
                g->str_replace(&reversezone, "%c", netpart);

                g->str_replace(&forwardzone, "%v", dnsserv ? (dnsserv) : ((char*) "127.0.0.1"));
                g->str_replace(&reversezone, "%v", dnsserv ? (dnsserv) : ((char*) "127.0.0.1"));

                // Set output files paths
                finfile = dns->fzones;
                rinfile = dns->rzones;

                if (finfile[strlen(finfile) - 1] != '/')
                    ftmpfile = g->str_concat(finfile, "/");
                else
                    ftmpfile = finfile;

                if (rinfile[strlen(rinfile) - 1] != '/')
                    rtmpfile = g->str_concat(rinfile, "/");
                else
                    rtmpfile = rinfile;

                finfile = g->str_concat(ftmpfile, name);
                rinfile = g->str_concat(rtmpfile, netpart);

                // Write to output files
                if (write_file(finfile, forwardzone) < 0) {
                    syslog(LOG_WARNING, "[%s/dns] Unable to open output forward zone file '%s' for domain '%s', skipping forward zone for this domain.", dns->base.instance, finfile, name);
                }
                else if (!domainmatch) {
                    char *zone, *tmpconf;
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

                if (write_file(rinfile, reversezone) < 0)
                {
                    syslog(LOG_WARNING, "[%s/dns] Unable to open output reverse zone file '%s' for domain '%s', skipping reverse zone for this domain.", dns->base.instance, rinfile, name);
                }
                else if (!reversematch) {
                    char *zone, *tmpconf;
                    zone = strdup(dns->confreverse);
                    g->str_replace(&zone, "%n", name);
                    g->str_replace(&zone, "%c", netpart);
                    // Don't support %i here, it will break bind config if you have
                    // many networks with small mask that are subclasses of bigger one
                    // e.g. 192.168.0.0/27 and 192.168.0.32/27
                    // g->str_replace(&zone, "%i", inet_ntoa(network));
                    g->str_replace(&zone, "%i", netpart);
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

        // Write config to main BIND file
        if (configfile) {
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

    g->db->free(&res);

    //cleanup
    for(i = 0; i<nh; i++)
        free(hosts[i].name);
    free(hosts);

    for(i = 0; i<dc; i++)
        free(domains[i]);
    free(domains);

    for(i=0; i<nc; i++)
        free(nets[i].name);
    free(nets);

    for(i=0; i<gc; i++)
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
    free(dns->customergroups);
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
    dns->fgeneric = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "generic-forward", LMS_CONF_DIR "/daemon/dns/sample/forward/generic"));
    dns->rgeneric = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "generic-reverse", LMS_CONF_DIR "/daemon/dns/sample/reverse/generic"));
    dns->fzones = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "forward-zones", LMS_CONF_DIR "/daemon/dns/sample/out/forward"));
    dns->rzones = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "reverse-zones", LMS_CONF_DIR "/daemon/dns/sample/out/reverse"));    dns->forward = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "host-forward", "%n IN A %i\n"));
    dns->reverse= strdup(g->config_getstring(dns->base.ini, dns->base.instance, "host-reverse", "%c IN PTR %n.%d.\n"));
    dns->command = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "command", ""));
    dns->confpattern = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "conf-pattern", LMS_CONF_DIR "/daemon/dns/sample/named.conf"));
    dns->confout = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "conf-output", "/tmp/named.conf"));   // FIXME: shoud be created with random file name
    dns->confforward = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "conf-forward-entry", "zone \"%n\" {\ntype master;\nfile \"forward/%n\";\nnotify yes;\n};\n"));
    dns->confreverse = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "conf-reverse-entry", "zone \"%c.in-addr.arpa\" {\ntype master;\nfile \"reverse/%c\";\nnotify yes;\n};\n"));
    dns->networks = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "networks", ""));
    dns->customergroups = strdup(g->config_getstring(dns->base.ini, dns->base.instance, "customergroups", ""));

#ifdef DEBUG1
    syslog(LOG_INFO, "DEBUG: [%s/dns] Initialized",dns->base.instance);
#endif
    return (dns);
}
