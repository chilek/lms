/*
 * LMS version 1.5-cvs
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
#include <syslog.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <time.h>

#include "almsd.h"
#include "notify.h"

#define BUFFERSIZE 1024

unsigned char * load_file(unsigned char *name)
{
	unsigned char *ret = NULL;
	static unsigned char buffer[BUFFERSIZE];
	int fd, n, l = 0;
	
	fd = open(name, O_RDONLY);
	if(fd == -1) 
		return (NULL);

//warning this could be done in a better way.
	while( (n = read(fd, buffer, BUFFERSIZE)) > 0 ) {
		unsigned char *ret0 =  (unsigned char *) realloc(ret, (n + l + 1));
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

unsigned char *utoc(unsigned long unixdate)
{
	time_t datevalue = (time_t) unixdate;
	unsigned char *text = (unsigned char *) malloc(11);

	strftime(text, 11, "%d.%m.%Y", localtime(&datevalue)); 
	return text;
}

void reload(GLOBAL *g, struct notify_module *n)
{
	QUERY_HANDLE *res, *result;
	unsigned char *mailfile = 0;
	unsigned char *command;
	int i, j, balance;

	if ( (res = g->db_query("SELECT users.id AS id, email, name, lastname, SUM((type * -2 +7) * cash.value) AS balance FROM users LEFT JOIN cash ON users.id = cash.userid AND (cash.type = 3 OR cash.type = 4) WHERE deleted = 0 GROUP BY users.id, name, lastname, email"))!=NULL ) {
	
		for(i=0; i<res->nrows; i++) {
			if( strlen(g->db_get_data(res,i,"email")) > 0 ) {
				balance = atoi(g->db_get_data(res,i,"balance"));
			
				if( balance < n->limit ) {
			
					command = strdup(n->command);
					mailfile = load_file(n->mailtemplate);
				
					if( mailfile ) {
						
						if( strstr(mailfile, "%last_10_in_a_table") ) {
						
							unsigned char *select, *date, *value, *comment, *last_ten, *temp, *temp2;
					
							select = strdup("SELECT comment, time, CASE WHEN type=4 THEN value*-1 ELSE value END AS value FROM cash WHERE userid = %id ORDER BY time LIMIT 10");
							g->str_replace(&select, "%id", g->db_get_data(res,i,"id"));
						
							if( (result = g->db_query(select))!=NULL ) {
							
								if( result->nrows )
									last_ten = strdup("Data\t\t | Warto¶æ\t | Opis\n");
								
								for(j=0; j<result->nrows; j++) {
								
									date = utoc(atof(g->db_get_data(result,j,"time")));
									value = g->db_get_data(result,j,"value");
									comment = g->db_get_data(result,j,"comment");
								
									temp = (unsigned char *) malloc(strlen(date)+strlen(value)+strlen(comment)+12);	
									sprintf(temp, "%s\t | %s\t\t | %s\n", date, value, comment);
								
									temp2 = g->str_concat(last_ten, temp);
									free(last_ten);
									last_ten = strdup(temp2);
									free(temp2);
									free(temp);
									free(date);
								}
							
								g->db_free(result);
							}
							g->str_replace(&mailfile, "%last_10_in_a_table", last_ten);
							free(last_ten);
							free(select);
						}
						g->str_replace(&mailfile, "%saldo", g->db_get_data(res,i,"balance"));
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
		}
		g->db_free(res);
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/notify] reloaded",n->base.instance);
#endif
	}
	else
		syslog(LOG_ERR, "[%s/notify] Unable to read database", n->base.instance);

	free(n->command);
	free(n->file);
	free(n->mailtemplate);
	free(n->debugmail);
}

struct notify_module * init(GLOBAL *g, MODULE *m)
{
	struct notify_module *n;
	unsigned char *instance, *s;
	dictionary *ini;

	if(g->api_version != APIVERSION) 
		return (NULL);
	
	instance = m->instance;
	
	n = (struct notify_module*) realloc(m, sizeof(struct notify_module));
	
	n->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	n->base.instance = strdup(instance);
	
	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance, ":template");
	n->mailtemplate = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":file");
	n->file = strdup(g->iniparser_getstring(ini, s, "/tmp/mail"));
	free(s); s = g->str_concat(instance, ":command");
	n->command = strdup(g->iniparser_getstring(ini, s, "mail %address -s \"Inf. o zaleg³o¶ciach w op³atach za Internet\" -a \"Content-Type: text/plain; charset=iso-8859-2\" < /tmp/mail"));
	free(s); s = g->str_concat(instance, ":limit");
	n->limit = g->iniparser_getint(ini, s, 0);
	free(s); s = g->str_concat(instance, ":debug_mail");
	n->debugmail = strdup(g->iniparser_getstring(ini, s, ""));

	g->iniparser_freedict(ini);
	free(instance);
	free(s);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/notify] initialized",n->base.instance);		
#endif	
	return (n);
}
