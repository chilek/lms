/*
 * LMS version 1.5-cvs
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
#include <errno.h>
#include <time.h>

#include "almsd.h"
#include "ggnotify.h"
#include "libgadu.h"

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

unsigned char *utoc(unsigned long unixdate)
{
	time_t datevalue = (time_t) unixdate;
	unsigned char *text = (unsigned char *) malloc(11);

	strftime(text, 11, "%d.%m.%Y", localtime(&datevalue)); 
	return text;
}

void reload(GLOBAL *g, struct ggnotify_module *n)
{
	QUERY_HANDLE *res, *result;
	unsigned char *message = 0;
	int i, j, balance;
	struct gg_session *sess;
	struct gg_login_params p;

	memset(&p, 0, sizeof(p));
	p.uin = n->uin;
	p.password = n->passwd;

	// Najpierw po³±czmy siê z serwerem GG	
	if (!(sess = gg_login(&p))) {
		syslog(LOG_ERR, "[%s/ggnotify] Unable to connect to Gadu-Gadu server.", n->base.instance);
		gg_free_session(sess);
	} else {
		
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/ggnotify] Connectet to Gadu-Gadu server.",n->base.instance);
#endif
	
		if ( (res = g->db_query("SELECT users.id AS id, gguin, name, lastname, SUM((type * -2 +7) * cash.value) AS balance FROM users LEFT JOIN cash ON users.id = cash.userid AND (cash.type = 3 OR cash.type = 4) WHERE deleted = 0 GROUP BY users.id, gguin, name, lastname"))!=NULL ) {

			for(i=0; i<res->nrows; i++) {
		        
				if( atoi(g->db_get_data(res,i,"gguin")) ) {
	
					balance = atoi(g->db_get_data(res,i,"balance"));
			
					if( balance < n->limit ) {
						
						message = load_file(n->ggtemplate);
				
						if( message ) {
						
							if( strstr(message, "%last_10_in_a_table") ) {
						
								unsigned char *select, *date, *value, *comment, *last_ten, *temp, *temp2;
					
								select = strdup("SELECT CASE WHEN type=4 THEN value*-1 ELSE value END AS value, comment, time FROM cash WHERE userid = %id ORDER BY time LIMIT 10");
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
								g->str_replace(&message, "%last_10_in_a_table", last_ten);
								free(last_ten);
								free(select);
							}
							g->str_replace(&message, "%saldo", g->db_get_data(res,i,"balance"));
							g->str_replace(&message, "%name", g->db_get_data(res,i,"name"));
							g->str_replace(&message, "%lastname", g->db_get_data(res,i,"lastname"));

							// Konwersja na windows
							
							g->str_replace(&message, "\n", "\n\r");
							g->str_replace(&message, "\xA1", "\xA5");
							g->str_replace(&message, "\xA6", "\x8C");
							g->str_replace(&message, "\xAC", "\x8F");
							g->str_replace(&message, "\xB1", "\xB9");
							g->str_replace(&message, "\xB6", "\x9C");
							g->str_replace(&message, "\xBC", "\x9F");
					
							if( n->debuguin ) {
								if (gg_send_message(sess, GG_CLASS_MSG, n->debuguin, message ) == -1) {
									syslog(LOG_INFO, "DEBUG: [%s/ggnotify] Connection broken..",n->base.instance);								
									gg_free_session(sess);
								}
							} else {
								 if (gg_send_message(sess, GG_CLASS_MSG, atoi(g->db_get_data(res,i,"gguin")), message) == -1) {
									syslog(LOG_INFO, "DEBUG: [%s/ggnotify] Connection broken..",n->base.instance);								
									gg_free_session(sess);
								}
						
							}
							free(message);
					
						} 
					}
				}
			}
			g->db_free(res);
		} else 

			syslog(LOG_ERR, "[%s/ggnotify] Unable to read database", n->base.instance);
		
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
	unsigned char *instance, *s;
	dictionary *ini;

	if(g->api_version != APIVERSION) 
		return (NULL);
	
	instance = m->instance;
	
	n = (struct ggnotify_module*) realloc(m, sizeof(struct ggnotify_module));
	
	n->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	n->base.instance = strdup(instance);
	
	ini = g->iniparser_load(g->inifile);

	s = g->str_concat(instance, ":template");
	n->ggtemplate = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":uin");
	n->uin = g->iniparser_getint(ini, s, 0);
	free(s); s = g->str_concat(instance, ":password");
	n->passwd = strdup(g->iniparser_getstring(ini, s, ""));
	free(s); s = g->str_concat(instance, ":limit");
	n->limit = g->iniparser_getint(ini, s, 0);
	free(s); s = g->str_concat(instance, ":debug_uin");
	n->debuguin = g->iniparser_getint(ini, s, 0);

	g->iniparser_freedict(ini);
	free(instance);
	free(s);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/ggnotify] initialized",n->base.instance);		
#endif	
	return (n);
}
