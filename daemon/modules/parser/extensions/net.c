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
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <string.h>

#include "net.h"
#include "tscript_extensions.h"

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

tscript_value * tscript_ext_net_ip2long(tscript_value *arg)
{
	tscript_value *res;
	char *tmp;
	
	asprintf(&tmp, "%lu", ip2long(tscript_value_as_string(tscript_value_convert_to_string(arg))));
	res = tscript_value_create(TSCRIPT_TYPE_NUMBER, tmp);
	free(tmp);
	
	return res;
}

tscript_value * tscript_ext_net_long2ip(tscript_value *arg)
{
	tscript_value *res;
	unsigned long n;

	n = strtoul(tscript_value_as_string(tscript_value_convert_to_string(arg)), NULL, 0);
	res = tscript_value_create_string(long2ip(n));
	
	return res;
}

tscript_value * tscript_ext_net_broadcast(tscript_value *args)
{
        tscript_value *addr = tscript_extension_arg(args, 0);
	tscript_value *mask = tscript_extension_arg(args, 1);

	return tscript_value_create_string(broadcast(tscript_value_as_string(addr), tscript_value_as_string(mask)));
}

tscript_value * tscript_ext_net_mask2prefix(tscript_value *arg)
{
	return tscript_value_create_number(mask2prefix(
		tscript_value_as_string(tscript_value_convert_to_string(arg))));
}

void tscript_ext_net_init(tscript_context *context)
{
	tscript_add_extension(context, "mask2prefix", tscript_ext_net_mask2prefix, 1, 1);
	tscript_add_extension(context, "ip2long", tscript_ext_net_ip2long, 1, 1);
	tscript_add_extension(context, "long2ip", tscript_ext_net_long2ip, 1, 1);
	tscript_add_extension(context, "broadcast", tscript_ext_net_broadcast, 2, 2);
}

void tscript_ext_net_close(tscript_context *context)
{
	tscript_remove_extension(context, "mask2prefix");
	tscript_remove_extension(context, "ip2long");
	tscript_remove_extension(context, "long2ip");
	tscript_remove_extension(context, "broadcast");
}
