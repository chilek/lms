/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <errno.h>
#include <time.h>

#include "lmsd.h"
#include "ggnotify.h"
#include "libgadu.h"

#define BUFFERSIZE 1024

char * ftoa(double i)
{
	static char string[12];
	sprintf(string, "%.2f", i);
	return string;
}

char * load_file(char *name)
{
	char *ret = NULL;
	static char buffer[BUFFERSIZE];
	int fd, n, l = 0;
	
	fd = open(name, O_RDONLY);
	if(fd == -1) 
		return (NULL);

	//warning this could be done in a better way.
	while( (n = read(fd, buffer, BUFFERSIZE)) > 0 ) {
		char *ret0 =  (char *) realloc(ret, (n + l + 1));
		if(!ret0) { 
			free(ret); 
			return (NULL); 
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

char *utoc(unsigned long unixdate)
{
	time_t datevalue = (time_t) unixdate;
	char *text = (char *) malloc(11);

	strftime(text, 11, "%Y/%m/%d", localtime(&datevalue)); 
	return text;
}

void reload(GLOBAL *g, struct ggnotify_module *n)
{
	QueryHandle *res, *result;
	char *message = 0;
	int i, j;
	double balance;
	struct gg_session *sess;
	struct gg_login_params p;

	memset(&p, 0, sizeof(p));
	p.uin = n->uin;
	p.password = n->passwd;

	// Najpierw po³±czmy siê z serwerem GG	
	if( !(sess = gg_login(&p)) )
	{
		syslog(LOG_ERR, "[%s/ggnotify] Unable to connect to Gadu-Gadu server.", n->base.instance);
		gg_free_session(sess);
	} 
	else 
	{
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ggnotify] Connected to Gadu-Gadu server.",n->base.instance);
#endif
	
		res = g->db->query(g->db->conn, 
				"SELECT customers.id AS id, pin, name, lastname, "
				"SUM(cash.value) AS balance, customercontacts.contact AS im "
				"FROM customers "
				"LEFT JOIN customercontacts ON customers.id = customercontacts.customerid "
				"LEFT JOIN cash ON customers.id = cash.customerid "
				"WHERE deleted = 0 AND (customercontacts.type & 512) > 0 "
				"GROUP BY customers.id, customercontacts.contact, pin, name, lastname");

		if( g->db->nrows(res) )
		{
			for(i=0; i<g->db->nrows(res); i++)
			{
				if( atoi(g->db->get_data(res,i,"im")) )
				{
					balance = atof(g->db->get_data(res,i,"balance"));
			
					if( balance < n->limit )
					{
						message = load_file(n->ggtemplate);
				
						if( message )
						{
							if( strstr(message, "%last_10_in_a_table") )
							{
								char *date, *value, *comment, *last_ten, *temp, *temp2;
								
								last_ten = strdup("");
								
								result = g->db->pquery(g->db->conn, "SELECT value, comment, time FROM cash WHERE customerid = ? ORDER BY time DESC LIMIT 10", g->db->get_data(res,i,"id"));
							
								for(j=0; j<g->db->nrows(result); j++)
								{
									date = utoc(atof(g->db->get_data(result,j,"time")));
									value = g->db->get_data(result,j,"value");
									comment = g->db->get_data(result,j,"comment");
							
									temp = (char *) malloc(strlen(date)+strlen(value)+strlen(comment)+12);	
									sprintf(temp, "%s\t | %s\t\t | %s\n", date, value, comment);
							
									temp2 = g->str_concat(last_ten, temp);
									free(last_ten);
									last_ten = strdup(temp2);
									free(temp2);
									free(temp);
									free(date);
								}
						
								g->str_replace(&message, "%last_10_in_a_table", last_ten);
								g->db->free(&result);
								free(last_ten);
							}
						
							g->str_replace(&message, "%saldo", g->db->get_data(res,i,"balance"));
							g->str_replace(&message, "%B", g->db->get_data(res,i,"balance"));
							g->str_replace(&message, "%b", balance < 0 ? ftoa(balance * -1) : g->db->get_data(res,i,"balance"));
							g->str_replace(&message, "%pin", g->db->get_data(res,i,"pin"));
							g->str_replace(&message, "%name", g->db->get_data(res,i,"name"));
							g->str_replace(&message, "%lastname", g->db->get_data(res,i,"lastname"));

							// Konwersja na windows
								
							g->str_replace(&message, "\n", "\n\r");
							g->str_replace(&message, "\xA1", "\xA5");
							g->str_replace(&message, "\xA6", "\x8C");
							g->str_replace(&message, "\xAC", "\x8F");
							g->str_replace(&message, "\xB1", "\xB9");
							g->str_replace(&message, "\xB6", "\x9C");
							g->str_replace(&message, "\xBC", "\x9F");
							
							if( n->debuguin )
							{
								if (gg_send_message(sess, GG_CLASS_MSG, n->debuguin, message ) == -1)
								{
									syslog(LOG_INFO, "DEBUG: [%s/ggnotify] Connection broken..",n->base.instance);								
									gg_free_session(sess);
								}
							} else {
								if (gg_send_message(sess, GG_CLASS_MSG, atoi(g->db->get_data(res,i,"im")), message) == -1)
								{
									syslog(LOG_INFO, "DEBUG: [%s/ggnotify] Connection broken..",n->base.instance);								
									gg_free_session(sess);
								}
							}
						
						free(message);
					
						} 
					}
				}
			}
		
		} else 
			syslog(LOG_ERR, "[%s/ggnotify] Unable to read database", n->base.instance);
		
		g->db->free(&res);
		gg_logoff(sess);
		gg_free_session(sess);

#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ggnotify] reloaded",n->base.instance);
#endif
	}

	free(n->passwd);
	free(n->ggtemplate);
}

struct ggnotify_module * init(GLOBAL *g, MODULE *m)
{
	struct ggnotify_module *n;

	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	n = (struct ggnotify_module*) realloc(m, sizeof(struct ggnotify_module));
	
	n->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	n->ggtemplate = strdup(g->config_getstring(n->base.ini, n->base.instance, "template", ""));
	n->uin = g->config_getint(n->base.ini, n->base.instance, "uin", 0);
	n->passwd = strdup(g->config_getstring(n->base.ini, n->base.instance, "password", ""));
	n->limit = g->config_getint(n->base.ini, n->base.instance, "limit", 0);
	n->debuguin = g->config_getint(n->base.ini, n->base.instance, "debug_uin", 0);

#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/ggnotify] initialized",n->base.instance);		
#endif	
	return (n);
}
