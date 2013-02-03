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
#include <unistd.h>
#include <syslog.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <time.h>

#include "lmsd.h"
#include "notify.h"

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

int write_file(char *name, char *text)
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

char *utoc(unsigned long unixdate)
{
	time_t datevalue = (time_t) unixdate;
	char *text = (char *) malloc(11);

	strftime(text, 11, "%Y/%m/%d", localtime(&datevalue)); 
	return text;
}

void reload(GLOBAL *g, struct notify_module *n)
{
	QueryHandle *res, *result;
	char *mailfile = 0;
	char *command;
	int i, j; 
	double balance;

	res = g->db_query(g->conn, "SELECT customers.id AS id, email, pin, name, lastname, SUM(cash.value) AS balance FROM customers LEFT JOIN cash ON customers.id = cash.customerid WHERE deleted = 0 AND email!='' GROUP BY customers.id, name, lastname, email, pin");
	
	if( g->db_nrows(res) )
	{
		for(i=0; i<g->db_nrows(res); i++) 
		{
			balance = atof(g->db_get_data(res,i,"balance"));
			
			if( balance < n->limit ) 
			{
				command = strdup(n->command);
				mailfile = load_file(n->mailtemplate);
			
				if( mailfile ) 
				{
					if( strstr(mailfile, "%last_10_in_a_table") )
					{
						char *date, *value, *comment, *temp, *temp2;
						char *last_ten = strdup("");
							
						result = g->db_pquery(g->conn, "SELECT comment, time, value FROM cash WHERE customerid = ? ORDER BY time DESC LIMIT 10", g->db_get_data(res,i,"id"));
						
						for(j=0; j<g->db_nrows(result); j++) 
						{
							date = utoc(atof(g->db_get_data(result,j,"time")));
							value = g->db_get_data(result,j,"value");
							comment = g->db_get_data(result,j,"comment");
						
							temp = (char *) malloc(strlen(date)+strlen(value)+strlen(comment)+12);	
							sprintf(temp, "%s\t | %s\t\t | %s\n", date, value, comment);
						
							temp2 = g->str_concat(last_ten, temp);
							free(last_ten);
							last_ten = strdup(temp2);
							free(temp2);
							free(temp);
							free(date);
						}
															
						g->str_replace(&mailfile, "%last_10_in_a_table", last_ten);
						
						g->db_free(&result);
						free(last_ten);
					}
					
					g->str_replace(&mailfile, "%saldo", g->db_get_data(res,i,"balance"));
					g->str_replace(&mailfile, "%B", g->db_get_data(res,i,"balance"));
					g->str_replace(&mailfile, "%b", balance < 0 ? ftoa(balance * -1) : g->db_get_data(res,i,"balance"));
					g->str_replace(&mailfile, "%pin", g->db_get_data(res,i,"pin"));
					g->str_replace(&mailfile, "%name", g->db_get_data(res,i,"name"));
					g->str_replace(&mailfile, "%lastname", g->db_get_data(res,i,"lastname"));
				
					if( write_file(n->file, mailfile) < 0 )
						syslog(LOG_ERR, "[%s/notify] Unable to write temporary file '%s' for message", n->base.instance, n->file);
					free(mailfile);
				
					if( strlen(n->debugmail) < 1 )
						g->str_replace(&command, "%address", g->db_get_data(res,i,"email"));
					else
						g->str_replace(&command, "%address", n->debugmail);
					system(command); 
				}
				free(command);
			}
		}
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/notify] reloaded",n->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/notify] Unable to read database", n->base.instance);

	g->db_free(&res);
	free(n->command);
	free(n->file);
	free(n->mailtemplate);
	free(n->debugmail);
}

struct notify_module * init(GLOBAL *g, MODULE *m)
{
	struct notify_module *n;

	if(g->api_version != APIVERSION)
	{
		return (NULL);
	}
	
	n = (struct notify_module*) realloc(m, sizeof(struct notify_module));
	
	n->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	n->mailtemplate = strdup(g->config_getstring(n->base.ini, n->base.instance, "template", ""));
	n->file = strdup(g->config_getstring(n->base.ini, n->base.instance, "file", "/tmp/mail"));
	n->command = strdup(g->config_getstring(n->base.ini, n->base.instance, "command", "mail -s \"Liabilities information\" %address < /tmp/mail"));
	n->limit = g->config_getint(n->base.ini, n->base.instance, "limit", 0);
	n->debugmail = strdup(g->config_getstring(n->base.ini, n->base.instance, "debug_mail", ""));

#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/notify] initialized",n->base.instance);		
#endif	
	return (n);
}
