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
 */

#define REVISION "$Id$"

#include <time.h>
#include <stdlib.h>
#include <unistd.h>
#include <signal.h>		
#include <syslog.h>
#include <stdio.h>     
#include <string.h>
#include <dlfcn.h>  
  
#include "lmsd.h"

int quit = 0, port = 0, dontfork = 0;
char *db, *user, *passwd, *host, *dhost;
unsigned char *command = NULL;
unsigned char *iopt = NULL;

static void parse_command_line(int argc, char **argv);
static void free_module(MODULE *module);
static int crontab_match(time_t tt, char *crontab);

int main(int argc, char *argv[])
{
	QueryHandle *res;
	time_t tt;
	GLOBAL *g;
	INSTANCE *instances;
	int fval = 0, i = 0, reload = 0, i_no = 0;
	unsigned char *inst, *instance; 
#ifdef CONFIGFILE
	Config *ini;
#endif
	host = (char *) malloc(sizeof(char)*255);  	//database
	dhost = (char *) malloc(sizeof(char)*255);	//daemon

    	syslog(LOG_INFO, "LMS Daemon started.");

    	// check environment	
	if( getenv("LMSDBPASS") ) passwd = getenv("LMSDBPASS"); else passwd = "";
	if( getenv("LMSDBNAME") ) db = getenv("LMSDBNAME"); else db = "lms";
	if( getenv("LMSDBUSER") ) user = getenv("LMSDBUSER"); else user = "lms";
	if( getenv("LMSDBHOST") ) host = getenv("LMSDBHOST"); else host = "localhost";
	if( getenv("LMSDBPORT") ) port = atoi(getenv("LMSDBPORT"));
	gethostname(dhost, 255);

    	// read command line args
	parse_command_line(argc, argv);

	// initialize global structure
	g = (GLOBAL *) realloc(NULL, sizeof(GLOBAL));
	g->api_version = APIVERSION;
	g->conn = NULL;
	
   	g->db_query = &db_query;
	g->db_pquery = &db_pquery;
    	g->db_exec = &db_exec;
	g->db_pexec = &db_pexec;
	g->db_free = &db_free;
    	g->db_begin = &db_begin;
    	g->db_commit = &db_commit;
	g->db_abort = &db_abort;
    	g->db_get_data = &db_get_data;
	g->db_nrows = &db_nrows;
	g->db_ncols = &db_ncols;
    
    	g->str_replace = &str_replace;
    	g->str_save = &str_save;
    	g->str_concat = &str_concat;

    	g->config_getstring = &config_getstring;
	g->config_getint = &config_getint;
	g->config_getbool = &config_getbool;
	g->config_getdouble = &config_getdouble;

    	// daemonize
    	if ( !quit && !dontfork )
	{
		fval = fork();
        	switch (fval) 
		{
			case -1:
    	    			fprintf(stderr, "Fork error. Exiting.");
            			termination_handler(1);
        		case 0:
				setsid();
				break;
			default:
#ifdef DEBUG1	
	    			syslog(LOG_INFO, "DEBUG: [lmsd] Daemonize. Forked child %d.", fval);
#endif
            			exit(0); // parent exits
        	}
    	}

    	// termination signals handling
    	signal(SIGINT, termination_handler);
    	signal(SIGTERM, termination_handler);

    	// main loop ****************************************************
    	for (;;)
	{
		i_no = 0;
		
		if( quit ) 
		{
			reload = 1;
			tt = time(0);
		}
		else // daemon mode
		{
			reload = 0;
			tt = cron_sync_sleep();
		}

		// run shell command, i.e. secure connections tuneling
		if(command!=NULL)
		{
#ifdef DEBUG1
			syslog(LOG_INFO, "DEBUG: [lmsd] Executing command: %s.", command);
#endif
			system(command);
		}

		// try to connect to database
		if( !(g->conn = db_connect(db,user,passwd,host,port)) )
		{
			if( quit ) termination_handler(1);
			continue;
		}

		if( !reload )
		{
			// check reload order
			res = db_pquery(g->conn, "SELECT reload FROM daemonhosts WHERE name = '?' AND reload != 0", dhost);
			if( db_nrows(res) )
			{
				reload = 1;
			}
			db_free(&res);
		}
		
		instances = (INSTANCE *) malloc(sizeof(INSTANCE));
		
		// get instances list even if reload == 0
		// maybe we should do that once before main loop, but
		// now we can change configuration without daemon restart
#ifndef CONFIGFILE
		if(iopt) // from command line...
		{
			inst = strdup(iopt);
			for( instance=strtok(inst," "); instance!=NULL; instance=strtok(NULL, " ") )
			{
				res = db_pquery(g->conn, "SELECT module, crontab FROM daemoninstances, daemonhosts WHERE daemonhosts.id=hostid AND disabled=0 AND daemonhosts.name = '?' AND daemoninstances.name='?'", dhost, instance);
				if( db_nrows(res) )
				{
					char *crontab = db_get_data(res, 0, "crontab");
					if( crontab_match(tt, crontab) || (!strlen(crontab) && reload) )
					{
						instances = (INSTANCE *) realloc(instances, sizeof(INSTANCE)*(i_no+1));
						instances[i_no].name = strdup(instance);
						instances[i_no].module = strdup(db_get_data(res, 0, "module"));
						instances[i_no].crontab = strdup(crontab);
						i_no++;
					}
				}
				db_free(&res);
			}
			free(inst);	
		}		
		else // ... or from database
		{
			res = db_pquery(g->conn, "SELECT module, crontab, daemoninstances.name AS name FROM daemoninstances, daemonhosts WHERE daemonhosts.id=hostid AND disabled=0 AND daemonhosts.name = '?' ORDER BY priority", dhost);
			for(i=0; i<db_nrows(res); i++)
			{
				char *crontab = db_get_data(res, i, "crontab");
				if( crontab_match(tt, crontab) || (!strlen(crontab) && reload) )
				{
					instances = (INSTANCE *) realloc(instances, sizeof(INSTANCE)*(i_no+1));
					instances[i_no].name = strdup(db_get_data(res, i, "name"));
					instances[i_no].module = strdup(db_get_data(res, i, "module"));
					instances[i_no].crontab = strdup(crontab);
					i_no++;
				}
			}
			db_free(&res);
		}
#else 
		// read config from ini file
		ini = config_load(g->conn, dhost, NULL);
		if(iopt) // from command line...
		{
			inst = strdup(iopt);
			for( instance=strtok(inst," "); instance!=NULL; instance=strtok(NULL, " ") )
			{
				char *crontab = config_getstring(ini, instance, "crontab", "");
				if( crontab_match(tt, crontab) || (!strlen(crontab) && reload) )
				{
					instances = (INSTANCE *) realloc(instances, sizeof(INSTANCE)*(i_no+1));
					instances[i_no].name = strdup(instance);
					instances[i_no].module = strdup(config_getstring(ini, instance, "module", ""));
					instances[i_no].crontab = strdup(crontab);
					i_no++;
				}
			}
			free(inst);	
		}		
		else // ... or from file
		{
			inst = strdup(config_getstring(ini, "lmsd", "instances", ""));
			for( instance=strtok(inst," "); instance!=NULL; instance=strtok(NULL, " ") )
			{
				char *crontab = config_getstring(ini, instance, "crontab", "");
				if( crontab_match(tt, crontab) || (!strlen(crontab) && reload) )
				{
					instances = (INSTANCE *) realloc(instances, sizeof(INSTANCE)*(i_no+1));
					instances[i_no].name = strdup(instance);
					instances[i_no].module = strdup(config_getstring(ini, instance, "module", ""));
					instances[i_no].crontab = strdup(crontab);
					i_no++;
				}
			}
		}
		config_free(ini);
#endif
		db_disconnect(g->conn);

		if( i_no )
		{
			// forking reload - we can do a job for longer than one minute
			// don't fork in "quit mode"
			fval = quit ? 1 : fork();
			if( fval < 0 ) 
			{
        			syslog(LOG_CRIT, "Fork error. Can't reload.");
				if ( quit ) termination_handler(1);
			}
			else if( fval > 0 ) //child or "quit mode"
			{
#ifdef DEBUG1
				syslog(LOG_INFO, "DEBUG: [lmsd] Reloading...");
#endif
				// try to connect to database again
				if( !(g->conn = db_connect(db,user,passwd,host,port)) )
				{
					termination_handler(1);
				}

				for(i=0; i<i_no; i++)
				{
					MODULE *mod = (MODULE*) malloc(sizeof(MODULE));
					MODULE * (*init)(GLOBAL *, MODULE *);
					
					// get instance configuration and members
					mod->ini = config_load(g->conn, dhost, instances[i].name);
					mod->file = strdup(instances[i].module);
					mod->instance = strdup(instances[i].name);
					mod->dlh = dlopen(mod->file, RTLD_NOW);

					if( !mod->dlh ) 
					{
						syslog(LOG_ERR, "Unable to load module '%s': %s", mod->file, dlerror());
						free_module(mod);
						continue;
					}

					// initialize module
					init = dlsym(mod->dlh, "init");
					if( !init ) 
					{
						syslog(LOG_CRIT, "Unable to find initialization function in module '%s'. Is that file really a lmsd module?", mod->file);
						free_module(mod);
						continue;
					}
				
					if( !(mod = init(g, mod)))
					{
						syslog(LOG_CRIT, "Unable to initialize module '%s'. Perhaps there is a version mismatch?", mod->file);
						free_module(mod);
						continue;
					}

					// now run module
					mod->reload(g, mod);
					
					// close and free memory
					dlclose(mod->dlh);
					free_module(mod);
				}
				
				// write reload timestamp
				if( reload )
					db_pexec(g->conn, "UPDATE daemonhosts SET lastreload=%NOW%, reload=0 WHERE name='?'", dhost);
				db_disconnect(g->conn);
	
				// exit child (reload) thread
				if( !quit ) 
				{
#ifdef DEBUG1
					syslog(LOG_INFO, "DEBUG: [lmsd] Reload child exited.");
#endif
					exit(0);
				}
				//if( quit ) termination_handler(0);
			}
			
			for(i=0; i<i_no; i++)
			{ 
				free(instances[i].name);
				free(instances[i].module);
				free(instances[i].crontab);
			}
		}
		
		if( quit ) termination_handler(0);
		
		free(instances);
		
    	} // end of loop **********************************************
	return 0;
}

/* command line options parsing */
static void parse_command_line(int argc, char **argv)
{
	int opt;
	char revision[10];
	
	sscanf(REVISION, "$Id: lmsd.c,v %s", revision);
	
	while ( (opt = getopt(argc, argv, "qfvi:h:p:d:u:H:c:")) != -1 ) 
	{
		switch (opt) 
		{
    		case 'v':
            		printf("LMS Daemon version 1.5-cvs (%s)\nCopyright (c) 2001-2005 LMS Developers\n", revision);
            		exit(0);
		case 'q':
    			quit = 1;
            		break;
		case 'f':
    			dontfork = 1;
            		break;
		case 'i':
        		iopt = optarg;
                	break;
		case 'h':
			sscanf(optarg, "%[^:]:%d", host, &port);
			break;
		case 'p':
			passwd = optarg;
			break;
		case 'd':
			db = optarg;
			break;
		case 'u':
			user = optarg;
			break;
		case 'H':
			dhost = optarg;
			break;
		case 'c':
			command = optarg;
			break;
        	default:
			printf("LMS Daemon version 1.5-cvs (%s). Command line options:\n", revision);
			printf(" -h host[:port]\t\tdatabase host (default: 'localhost')\n");
			printf(" -d db_name\t\tdatabase name (default: 'lms')\n");
			printf(" -u db_user\t\tdatabase user (default: 'lms')\n");
			printf(" -p password\t\tdatabase password (default: '')\n");
			printf(" -H daemon_host\t\thost name where runs daemon (default: `hostname`)\n");
			printf(" -c command\t\tshell command to run before database connecting (default: empty)\n");
                	printf(" -q \t\t\tdo a reload and quit\n");
			printf(" -f \t\t\trun in foreground (don't fork)\n");
			printf(" -i \"instance[ ...]\" list of instances to reload\n");
			printf(" -v \t\t\tprint version and copyright info\n");
                	exit(1);
		}
    	}
}

static void free_module(MODULE *mod)
{
	free(mod->instance);
	free(mod->file);
	config_free(mod->ini);
	free(mod);
}

static int crontab_match(time_t tt, char *crontab)
{
	CronTime ct;
	memset(&ct, 0, sizeof(CronTime));

	if( strlen(crontab) )
	{
		if( cron_parse_time(&ct, crontab) != PARSE_OK )
			return 0;
		if( cron_match_time(&ct, &tt) )
			return 1;
	}
	return 0;
}
