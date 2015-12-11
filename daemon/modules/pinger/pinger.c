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

#include <string.h>
#include <sys/types.h>
#include <unistd.h>
#include <stdio.h>

#include "lmsd.h"
#include "pinger.h"

struct host *hosts = NULL;
int nh = 0;

sig_atomic_t sigint = 0;
struct if_desc descs[MAXIFN];
int descs_count = 0;

void sig_int(int a) {  sigint = 1;  }

void get_iface_desc(char if_name[IFNAMSIZ], struct if_desc *desc) 
{
	int sock;
	struct ifreq interf; //man 7 netdevice

	if ((sock = socket(PF_PACKET, SOCK_RAW, htons(ETH_P_ARP))) == -1) {
		printf("get_iface_desc: socket: %s\n\n", strerror(errno));
		exit(1);
	}

	memset(interf.ifr_name, 0, IFNAMSIZ);
	memcpy(interf.ifr_name, if_name, strlen(if_name));

	if (ioctl(sock, SIOCGIFINDEX, &interf) == -1) {
		printf("get_iface_desc: ioctl (SIOCGIFINDEX): %s\n", strerror(errno));
		exit(1);
	}
	desc->index = interf.ifr_ifindex;   //(*par).index

	memset(interf.ifr_hwaddr.sa_data, 0, 14);
	if (ioctl(sock, SIOCGIFHWADDR, &interf) == -1) {
		printf("get_iface_desc: ioctl (SIOCGIFHWADDR): %s\n", strerror(errno));
		exit(1);
	}

	memcpy(desc->mac, interf.ifr_hwaddr.sa_data, 6);

	if (ioctl(sock, SIOCGIFADDR, &interf)) {
		printf("get_iface_desc: ioctl (SIOCGIFADDR): %s\n", strerror(errno));
		exit(1);
	}

	memcpy(&(desc->ip), (interf.ifr_addr.sa_data + 2), 4);

	if (ioctl(sock, SIOCGIFNETMASK, &interf)) {
		printf("get_iface_desc: ioctl (SIOCGIFNETMASK) %s\n", strerror(errno));
		exit(1);
	}

	memcpy(&(desc->netmask), interf.ifr_addr.sa_data + 2, 4);

	desc->network = desc->ip & desc->netmask;

	close(sock);
}

void get_ifaces(void) 
{
	struct ifconf ifc;
	int sock, i, j;
	struct ifreq ifr[MAXIFN];

	if ((sock = socket(PF_PACKET, SOCK_RAW, htons(ETH_P_ARP))) == -1) {
		printf("get_ifaces: socket: %s\n", strerror(errno));
		exit(1);
	}

	ifc.ifc_len = MAXIFN * sizeof(struct ifreq);
	ifc.ifc_req = ifr;

	if (ioctl(sock, SIOCGIFCONF, &ifc) == -1) {
		printf("get_ifaces: ioctl (SIOCGIFCONF): %s\n", strerror(errno));
		exit(1);
	}
	
	for(j=0; j < ifc.ifc_len/sizeof(struct ifreq); j++)
	{
		get_iface_desc(ifr[j].ifr_name, &descs[descs_count]);
	
		for (i = 0; i < descs_count; i++)
			if (descs[i].network == descs[descs_count].network)
				break;
		if (i == descs_count)
			descs_count++;
	}
}

int send_arp_req(int sock, in_addr_t ip) 
{
	unsigned char buf[2*KB] = {0};
	struct sockaddr_ll str;
	int r, index, roz_arpha, roz_etha;
	struct ethhdr etha;
	struct arphdr arpha;
	unsigned char broadcast[ETH_ALEN] = "\xFF\xFF\xFF\xFF\xFF\xFF";
	unsigned char ar_sha[ETH_ALEN];	// sender hardware address
	unsigned char ar_tha[ETH_ALEN];	// target hardware address
	struct timeval tv;

	// odnalezienie adresu IP z ktorego chcemy wysylac ramke
	for (index = 0; index < descs_count; index++)
		if (descs[index].network == (ip & descs[index].netmask))
			break;

	if (index == descs_count)
		return 1;

	//uzupelnienie struktury adresowej do sendto()
	memset(&str, 0, sizeof(str));
	str.sll_family = PF_PACKET;
	memcpy(str.sll_addr, descs[index].mac, ETH_ALEN);
	str.sll_halen = ETH_ALEN;
	str.sll_ifindex = descs[index].index;

	//.........budujemy naglowek Ethernetowy warstwy lacza danych...............
	memcpy(etha.h_dest, broadcast, ETH_ALEN);		// destination eth addr
	memcpy(etha.h_source, descs[index].mac, ETH_ALEN);	// source ether addr
	etha.h_proto = htons(ETH_P_ARP);

	//.........budujemy naglowek ARP warstwy sieciowej...............
	arpha.ar_hrd = htons(ARPHRD_ETHER);		// format of hardware address
	arpha.ar_pro = htons(0x0800); 			// format of protocol address
	arpha.ar_hln = ETH_ALEN;			// length of hardware address
	arpha.ar_pln = 4;				// length of protocol address
	arpha.ar_op = htons(ARPOP_REQUEST);

	memcpy(ar_sha, descs[index].mac, ETH_ALEN);	// sender hardware address
	memset(ar_tha, 0, ETH_ALEN);			// target hardware address

	// ...... budujemy pakiet ...............
	roz_arpha = sizeof(arpha);
	roz_etha = sizeof(etha);
	memcpy(buf, &etha, roz_etha);
	memcpy(buf + roz_etha, &arpha, roz_arpha);
	memcpy(buf + roz_etha + roz_arpha, ar_sha, ETH_ALEN);
	memcpy(buf + roz_etha + roz_arpha + ETH_ALEN + 4, ar_tha, ETH_ALEN);
	memcpy(buf + roz_etha + roz_arpha + ETH_ALEN, &(descs[index].ip), 4);// sender IP address
	tv.tv_sec = 0;
	tv.tv_usec = 2000;
	select(1, NULL, NULL, NULL, &tv);

//	ip = htonl(ip);
	memcpy(buf + roz_etha + roz_arpha + ETH_ALEN + 4 + ETH_ALEN, &ip, 4);
//	ip = ntohl(ip);

	if ((r = sendto(sock, buf, 42, 0, (struct sockaddr*)&str, sizeof(str))) == -1) {
		printf("send_arp_reqs: sendto: %s\n", strerror(errno));
		return 1;
	}

	return 0;
}

int send_arp_reqs() 
{
	int sock, i;

	if ((sock = socket(PF_PACKET, SOCK_RAW, htons(ETH_P_ARP))) == -1) {
		printf("send_arp_reqs: socket: %s\n", strerror(errno));
		return 1;
	}

	for (i = 0; i < nh; i++)
		send_arp_req(sock, hosts[i].ipaddr);

	close(sock);
	return 0;
}

int recv_arp_reply() 
{
	fd_set rd;
	unsigned char buf[2*KB] = {0};
	struct sockaddr_ll str;
	unsigned int sock, len, r, index, i, roz_arpha, roz_etha;
	unsigned int dstip, srcip;
	struct arphdr *arpha;
	struct timeval tt, acttime, oldtime;

	if ((sock = socket(PF_PACKET, SOCK_RAW, htons(ETH_P_ARP))) == -1) {
		printf("recv_arp_reply: socket: %s\n",strerror(errno));
		return 1;
	}

	roz_arpha = sizeof(struct arphdr);
	roz_etha = sizeof(struct ethhdr);

	str.sll_family = PF_PACKET;
	str.sll_protocol = htons(ETH_P_ARP);
	str.sll_hatype = ARPHRD_ETHER;
	str.sll_pkttype = PACKET_HOST;
	len = sizeof(buf);

	gettimeofday(&oldtime, NULL);

	while (1) {
		if (sigint) { // przerwanie programu
			printf("Exiting. Interrupt signal detected.");
			close(sock);
			exit(1);
		}

		FD_ZERO(&rd);
		FD_SET(sock, &rd);
		tt.tv_sec = 0;
		tt.tv_usec = 0;

		if (!select(sock + 1, &rd, NULL, NULL, &tt)) {
			gettimeofday(&acttime, NULL);
			if (acttime.tv_sec - oldtime.tv_sec >= 2)
				break;
			continue;
		}

		if ((r = recvfrom(sock, buf, 2 * KB, 0, (struct sockaddr*) &str, &len)) == -1) {
			printf("recv_arp_reply: recvfrom: %s\n", strerror(errno));
			return 1;
		}

		arpha = (struct arphdr *)(buf + sizeof(struct ethhdr));
		if (ntohs(arpha->ar_op) == ARPOP_REPLY) {

			memcpy(&dstip, buf + roz_etha + roz_arpha + ETH_ALEN + 4 + ETH_ALEN, 4);
			memcpy(&srcip, buf + roz_etha + roz_arpha + ETH_ALEN, 4);

			// odnalezienie adresu IP z ktorego chcemy wysylac ramke
			for (index = 0; index < descs_count; index++)
				if (descs[index].network == (dstip & descs[index].netmask))
					break;
			
			if (index < descs_count) {

				gettimeofday(&oldtime, NULL);
				
				for(i=0; i<nh; i++)
					if (hosts[i].ipaddr == srcip) {
						hosts[i].active = 1;
						break;
					}
			}
		}
	}

	close(sock);
	return 0;
}

void reload(GLOBAL *g, struct pinger_module *p)
{
	QueryHandle *res;
	int i, j, nc=0, n=2;
	char *hoststr;

	struct net *nets = (struct net *) malloc(sizeof(struct net));
	char *netnames = strdup(p->networks);	
	char *netname = strdup(netnames);

	while( n>1 ) 
	{
		n = sscanf(netnames, "%s %[._a-zA-Z0-9- ]", netname, netnames);

		if( strlen(netname) ) 
		{
			res = g->db->pquery(g->db->conn, "SELECT name, domain, address, INET_ATON(mask) AS mask, interface, gateway FROM networks WHERE UPPER(name)=UPPER('?')", netname);
			if(g->db->nrows(res))
			{
				nets = (struct net *) realloc(nets, (sizeof(struct net) * (nc+1)));
				nets[nc].address = inet_addr(g->db->get_data(res,0,"address"));
				nets[nc].mask = inet_addr(g->db->get_data(res,0,"mask"));
				nc++;
			}
	    		g->db->free(&res);
		}
	}
	free(netname); free(netnames);

	if(!nc)
	{
		res = g->db->query(g->db->conn, "SELECT name, domain, address, INET_ATON(mask) AS mask, interface, gateway FROM networks");

		for(nc=0; nc<g->db->nrows(res); nc++) 
		{
			nets = (struct net*) realloc(nets, (sizeof(struct net) * (nc+1)));
			nets[nc].address = inet_addr(g->db->get_data(res,nc,"address"));
			nets[nc].mask = inet_addr(g->db->get_data(res,nc,"mask"));
		}
		g->db->free(&res);
	}

	res = g->db->pquery(g->db->conn, "SELECT id, INET_NTOA(ipaddr) AS ip FROM vnodes");

	for(i=0; i<g->db->nrows(res); i++) 
	{
		unsigned long ip = inet_addr(g->db->get_data(res,i,"ip"));
			
		for(j=0; j<nc; j++)
			if((ip & nets[j].mask) == nets[j].address)
				break;
			
		if(j!=nc) 
		{
			hosts = (struct host*) realloc(hosts, sizeof(struct host) * (nh + 1));
			hosts[nh].id = strdup(g->db->get_data(res,i,"id"));
			hosts[nh].ipaddr = ip;
			hosts[nh].active = 0;
			nh++;
		}
	}
	g->db->free(&res);

	/***********************************************************/
	get_ifaces();

	// activate nodes with interface's IPs because module can't recive
	// "ping" response when source IP == destination IP
	for(j=0; j<descs_count; j++)
		for(i=0; i<nh; i++)
			if( hosts[i].ipaddr == descs[j].ip)
			{
				hosts[i].active = 1;
				break;
			}

	// run "pinger"
	switch (fork()) {
		case -1:
			syslog(LOG_CRIT,"[%s/pinger] Fork: %s", p->base.instance, strerror(errno));
		break;
		case 0:
			send_arp_reqs();
			exit(0);
		break;
		default:
			signal(SIGINT, sig_int);
			recv_arp_reply();

			hoststr = strdup("0");
			j = 0;

			for(i=0; i<nh; i++) 
				if(hosts[i].active)
				{ 
					hoststr = realloc(hoststr, sizeof(char *) * (strlen(hoststr) + strlen(hosts[i].id) + 1));
	                    		strcat(hoststr, ",");
					strcat(hoststr, hosts[i].id);
					j++;
				}	
			
			if(j)
			{
				if(p->use_secure_function)
					// works with postgres only
					g->db->pexec(g->db->conn, "SELECT set_lastonline(ARRAY[?])", hoststr);
				else
					g->db->pexec(g->db->conn, "UPDATE nodes SET lastonline=%NOW% WHERE id IN (?)", hoststr);
			}
			
			free(hoststr);
		break;
	}

#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/pinger] reloaded", p->base.instance);
#endif
	// cleanup
	for(i=0; i<nh; i++) 
		free(hosts[i].id);
	free(hosts);
	free(nets);
	free(p->networks);
}

struct pinger_module * init(GLOBAL *g, MODULE *m)
{
	struct pinger_module *p;
	
	if(g->api_version != APIVERSION) 
	{
		return(NULL);
	}

	p = (struct pinger_module *) realloc(m, sizeof(struct pinger_module));
	
	p->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	
	p->networks = strdup(g->config_getstring(p->base.ini, p->base.instance, "networks", ""));
	// on PostgreSQL we can use "security definer" function:
	// CREATE OR REPLACE FUNCTION set_lastonline(int[]) RETURNS void AS $$
	// UPDATE nodes SET lastonline = EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))
	// WHERE id = ANY($1);
	// $$ LANGUAGE SQL SECURITY DEFINER;
	p->use_secure_function = g->config_getbool(p->base.ini, p->base.instance, "use_secure_function", 0);
	
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/pinger] initialized", p->base.instance);
#endif
	return(p);
}
