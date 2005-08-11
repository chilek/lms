#include <stdio.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <string.h>

#include "net.h"
#include "tscript_extensions.h"
#include "tscript_debug.h"


int mask2prefix(const char *mask)
{
	int m[4], i, j, result = 0;
	
	if(sscanf(mask, "%d.%d.%d.%d", &m[0], &m[1], &m[2], &m[3])!=4)
		return 0;
	
	for(i=0; i<4; i++)
		for(j=0; j<8; j++)
		{
			if( m[i] & 0x80 ) //1
				result++;
			else //0
				return result;
			
			m[i] <<= 1;
		}
	
	return result;
}

unsigned long ip2long(const char *addr)
{
	return ntohl(inet_addr(addr));
}

char *long2ip(const unsigned long ip)
{
	struct in_addr addr;

	addr.s_addr = htonl(ip);
	return inet_ntoa(addr);
}

char *broadcast(const char *addr, const char *mask)
{
	unsigned long a, m;
	int bits;
	
	if(strlen(mask)<3) 
	{
		bits = 32 - atoi(mask);
		m = (~0 << bits) & 0xffffffff;
	}
	else
		m = ip2long(mask);
		
	a = ip2long(addr);
	

	return long2ip(a | (~ m));
}

tscript_value tscript_ext_net_ip2long(tscript_value arg)
{
	tscript_value res;
	char *tmp;
	tscript_debug("Executing NET Extension: ip2long\n");
	
	asprintf(&tmp, "%lu", ip2long(tscript_value_convert_to_string(arg).data));
	res = tscript_value_create(TSCRIPT_TYPE_NUMBER, tmp);
	free(tmp);
	
	tscript_debug("Finished executing NET Extension: ip2long\n");
	return res;
}

tscript_value tscript_ext_net_long2ip(tscript_value arg)
{
	tscript_value res;
	unsigned long n;
	tscript_debug("Executing NET Extension: long2ip\n");

	n = strtoul(tscript_value_convert_to_string(arg).data, NULL, 0);
	res = tscript_value_create_string(long2ip(n));
	
	tscript_debug("Finished executing NET Extension: long2ip\n");
	return res;
}

tscript_value tscript_ext_net_broadcast(tscript_value arg)
{
	tscript_value res;
	char addr[255], mask[255];
	tscript_debug("Executing NET Extension: broadcast\n");

	sscanf(tscript_value_convert_to_string(arg).data, "%[^/]/%s", addr, mask);

	res = tscript_value_create_string(broadcast(addr, mask));
	
	tscript_debug("Finished executing NET Extension: broadcast\n");
	return res;
}

tscript_value tscript_ext_net_mask2prefix(tscript_value arg)
{
	tscript_value res;
	tscript_debug("Executing NET Extension: mask2prefix\n");

	res = tscript_value_create_number(mask2prefix(tscript_value_convert_to_string(arg).data));
	
	tscript_debug("Finished executing NET Extension: mask2prefix\n");
	return res;
}

void tscript_ext_net_init()
{
	tscript_add_extension("mask2prefix", tscript_ext_net_mask2prefix);
	tscript_add_extension("ip2long", tscript_ext_net_ip2long);
	tscript_add_extension("long2ip", tscript_ext_net_long2ip);
	tscript_add_extension("broadcast", tscript_ext_net_broadcast);
}

void tscript_ext_net_close()
{
	tscript_remove_extension("mask2prefix");
	tscript_remove_extension("ip2long");
	tscript_remove_extension("long2ip");
	tscript_remove_extension("broadcast");
}
