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
 */

#define REVISION "$Id$"

#include <time.h>
#include <stdlib.h>
#include <unistd.h>
#include <getopt.h>
#include <signal.h>		
#include <syslog.h>
#include <stdio.h>     
#include <string.h>
#include <locale.h>
#include <stdarg.h>
#include <dlfcn.h>
#include <sys/types.h>
#include <sys/wait.h>
  
#include "lmsd.h"

int quit = 0, runall = 0, port = 0, dontfork = 0, ssl = 0;
char *db, *user, *passwd;
char host[255], dhost[255];
char *pidfile = NULL;
char *command = NULL;
char *iopt = NULL;
struct sigaction sa, orig;

static char **Argv = NULL;
static char *LastArgv = NULL;
extern char **environ;

static void parse_command_line(int argc, char **argv);
static void free_module(MODULE *module);
static void init_set_proc_title(int argc, char **argv, char **envp);
static void set_proc_title(const char *fmt, ...);
static int crontab_match(time_t tt, char *crontab);
void sig_child(int signum);
void termination_handler(int signum);

int main(int argc, char *argv[], char **envp)
{
	QueryHandle *res;
	time_t tt;
	GLOBAL *g;
	INSTANCE *instances;
	int fval = 0, i = 0, reload = 0;
	char *inst, *instance; 
	FILE *pidf;
#ifdef CONFIGFILE
	Config *ini;
#endif
	openlog(PROGNAME, 0, LOG_INFO | LOG_CRIT | LOG_ERR);
    	syslog(LOG_INFO, "LMS Daemon started.");

	// initialize proces name change 
	init_set_proc_title(argc, argv, envp);

    	// read environment and command line
	passwd = ( getenv("LMSDBPASS") ? getenv("LMSDBPASS") : "" );
	db = ( getenv("LMSDBNAME") ? getenv("LMSDBNAME") : "lms" );
	user = ( getenv("LMSDBUSER") ? getenv("LMSDBUSER") : "lms" );
	port = ( getenv("LMSDBPORT") ? atoi(getenv("LMSDBPORT")) : 0 );
	if( getenv("LMSDBHOST") ) strcpy(host, getenv("LMSDBHOST")); else strcpy(host, "localhost");
	gethostname(dhost, 255);

	// date/time localization according to environement settings
	setlocale(LC_TIME, "");
	
	// command line arguments
	parse_command_line(argc, argv);

	// change process name (hide command line args)
	set_proc_title(PROGNAME);

	// initialize global structure
	g = (GLOBAL *) realloc(NULL, sizeof(GLOBAL));
	g->api_version = APIVERSION;
	g->conn = NULL;
	
	g->db_connect = &db_connect;
	g->db_disconnect = &db_disconnect;
   	g->db_query = &db_query;
	g->db_pquery = &db_pquery;
    	g->db_exec = &db_exec;
	g->db_pexec = &db_pexec;
	g->db_last_insert_id = &db_last_insert_id;
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
	g->str_lwc = &str_lwc;
	g->str_upc = &str_upc;

    	g->config_getstring = &config_getstring;
	g->config_getint = &config_getint;
	g->config_getbool = &config_getbool;
	g->config_getdouble = &config_getdouble;

	// catch SIGCHLD to catch zombies
	sa.sa_handler = sig_child;
	sigemptyset(&sa.sa_mask);
	sa.sa_flags = 0;
	sigaction(SIGCHLD, &sa, &orig);

    	// daemonize
    	if ( !quit && !dontfork )
	{
		fval = fork();
        	switch(fval) 
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
				if (pidfile != NULL && (pidf = fopen(pidfile, "w")) != NULL)
				{
				    fprintf(pidf, "%d", fval);
				    fclose(pidf);
				}
            			exit(0); // parent exits
        	}
    	}

    	// termination signals handling
    	signal(SIGINT, termination_handler);
    	signal(SIGTERM, termination_handler);

    	// main loop ****************************************************
    	for(;;)
	{
		int i_no = 0;
		
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
		if( command!=NULL )
		{
#ifdef DEBUG1
			syslog(LOG_INFO, "DEBUG: [lmsd] Executing command: %s.", command);
#endif
			system(command);
		}

		// try to connect to database
		if( !(g->conn = db_connect(db,user,passwd,host,port,ssl)) )
		{
			if( quit ) termination_handler(1);
			continue;
		}

		if( !reload )
		{
			// check reload order
			res = db_pquery(g->conn, "SELECT reload FROM hosts WHERE name = '?' AND reload != 0", dhost);
			if( db_nrows(res) )
			{
				reload = 1;
			}
			db_free(&res);
		}
		
		instances = (INSTANCE *) malloc(sizeof(INSTANCE));
		
		// get instances list even if reload == 0
		// maybe we should do that once before main loop, but in
		// this way we can change configuration without daemon restart
#ifndef CONFIGFILE
		if( iopt ) // from command line...
		{
			inst = strdup(iopt);
			for( instance=strtok(inst," "); instance!=NULL; instance=strtok(NULL, " ") )
			{
				char *name = strdup(instance);
				str_replace(&name, "\\s", " "); // instance name with spaces
				
				res = db_pquery(g->conn, "SELECT module, crontab FROM daemoninstances, hosts WHERE hosts.id = hostid AND disabled = 0 AND hosts.name = '?' AND daemoninstances.name = '?'", dhost, name);
				if( db_nrows(res) )
				{
					char *crontab = db_get_data(res, 0, "crontab");
					if( runall || (reload && !strlen(crontab)) || (!quit && crontab_match(tt, crontab)) )
					{
						instances = (INSTANCE *) realloc(instances, sizeof(INSTANCE)*(i_no+1));
						instances[i_no].name = strdup(name);
						instances[i_no].module = strdup(db_get_data(res, 0, "module"));
						instances[i_no].crontab = strdup(crontab);
						i_no++;
					}
				}
				db_free(&res);
				free(name);
			}
			free(inst);	
		}		
		else // ... or from database
		{
			res = db_pquery(g->conn, "SELECT module, crontab, daemoninstances.name AS name FROM daemoninstances, hosts WHERE hosts.id = hostid AND disabled = 0 AND hosts.name = '?' ORDER BY priority", dhost);
			for(i=0; i<db_nrows(res); i++)
			{
				char *crontab = db_get_data(res, i, "crontab");
				if( runall || (reload && !strlen(crontab)) || (!quit && crontab_match(tt, crontab)) )
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
		if( iopt ) // from command line...
		{
			inst = strdup(iopt);
			for( instance=strtok(inst," "); instance!=NULL; instance=strtok(NULL, " ") )
			{
				char *name = strdup(instance);
				str_replace(&name, "\\s", " ");
				
				char *crontab = config_getstring(ini, name, "crontab", "");
				if( runall || (reload && !strlen(crontab)) || (!quit && crontab_match(tt, crontab)) )
				{
					instances = (INSTANCE *) realloc(instances, sizeof(INSTANCE)*(i_no+1));
					instances[i_no].name = strdup(name);
					instances[i_no].module = strdup(config_getstring(ini, name, "module", ""));
					instances[i_no].crontab = strdup(crontab);
					i_no++;
				}
				free(name);
			}
			free(inst);	
		}		
		else // ... or from file
		{
			inst = strdup(config_getstring(ini, "lmsd", "instances", ""));
			for( instance=strtok(inst," "); instance!=NULL; instance=strtok(NULL, " ") )
			{
				char *crontab = config_getstring(ini, instance, "crontab", "");
				if( runall || (reload && !strlen(crontab)) || (!quit && crontab_match(tt, crontab)) )
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
			if( quit )
				fval = 0; // don't fork in "quit mode"
			else
				fval = fork();
			
			if( fval < 0 ) 
			{
        			syslog(LOG_CRIT, "Fork error. Can't reload.");
				if ( quit ) termination_handler(1);
			}
			else if( fval == 0 ) // child or "quit mode"
			{
				set_proc_title(PROGNAME": reload");

				// restore old handler so we can wait for childs executed by modules
				if( !quit )
					sigaction(SIGCHLD, &orig, NULL);
#ifdef DEBUG1
				syslog(LOG_INFO, "DEBUG: [lmsd] Reloading...");
#endif
				// try to connect to database again
				if( !(g->conn = db_connect(db,user,passwd,host,port,ssl)) )
				{
					if( quit ) 
						termination_handler(1);
					else 
						exit(1);
				}
				
				// write reload timestamp and disable reload order
				if( reload )
					db_pexec(g->conn, "UPDATE hosts SET lastreload = %NOW%, reload = 0 WHERE name = '?'", dhost);
				
				for(i=0; i<i_no; i++)
				{
					MODULE *m;
					MODULE *mod = (MODULE*) malloc(sizeof(MODULE));
					MODULE * (*init)(GLOBAL *, MODULE *);

					char path[strlen(LMS_LIB_DIR) + strlen(instances[i].module) + 4];
			
					// get instance configuration and members
					mod->ini = config_load(g->conn, dhost, instances[i].name);
					mod->instance = strdup(instances[i].name);
					
					// set path to module if not specified
					// be sure that it has .so extension
					str_replace(&instances[i].module, ".so", "");
					
					if( instances[i].module[0] == '/' )
						sprintf(path, "%s.so", instances[i].module);
					else
						sprintf(path, LMS_LIB_DIR "/%s.so", instances[i].module);
					
					mod->file = strdup(path);

					// try to load module
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
				
					if( !(m = init(g, mod)))
					{
						syslog(LOG_CRIT, "Unable to initialize module '%s'. Perhaps there is a version mismatch?", mod->file);
						free_module(mod);
						continue;
					}

					// now run module
					m->reload(g, m);
					
					// cleanup
					free_module(m);
				}
				
				db_disconnect(g->conn);
	
				// exit child (reload) thread
				if( !quit ) 
				{
#ifdef DEBUG1
					syslog(LOG_INFO, "DEBUG: [lmsd] Reload finished. Exiting child.");
#endif
					exit(0);
				}
			}
			else 
				sleep(10); // it's important to sleep parent for some time
			
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
	int opt, option_index = 0;
	char revision[10];

	static struct option options[] = {
   	    { "dbhost", 1, 0, 'h' },
	    { "dbname", 1, 0, 'd' },
        { "dbuser", 1, 0, 'u' },
  	    { "dbpass", 1, 0, 'p' },
	    { "hostname", 1, 0, 'H' },
   	    { "pidfile", 1, 0, 'P' },
   	    { "command", 2, 0, 'c' },
   	    { "reload", 0, 0, 'q' },
	    { "reload-all", 0, 0, 'r' },
	    { "foreground", 0, 0, 'f' },
        { "instance", 2, 0, 'i' },
  	    { "version", 0, 0, 'v' },
   	    { "ssl", 0, 0, 's' },
   	    { "help", 0, 0, 'x' },
   	    { 0, 0, 0, 0 }
	};

	sscanf(REVISION, "$Id: lmsd.c,v %s", revision);

	while( (opt = getopt_long(argc, argv, "xsqrfvi:h:p:d:u:H:c:P:", options, &option_index)) != -1 )
	{
		switch(opt) 
		{
    		case 'v':
            		printf("LMS Daemon version 1.11-git (%s)\nCopyright (c) 2001-2013 LMS Developers\n", revision);
            		exit(0);
    		case 's':
            		ssl = 1;
            		break;
		case 'q':
    			quit = 1;
            		break;
		case 'r':
    			runall = 1;
			quit = 1;
            		break;
		case 'f':
    			dontfork = 1;
            		break;
		case 'i':
        		iopt = strdup(optarg);
                	break;
		case 'h':
			sscanf(optarg, "%[^:]:%d", host, &port);
			break;
		case 'p':
			passwd = strdup(optarg);
			break;
		case 'd':
			db = strdup(optarg);
			break;
		case 'u':
			user = strdup(optarg);
			break;
		case 'H':
			strcpy(dhost, optarg);
			break;
		case 'c':
			command = strdup(optarg);
			break;
		case 'P':
			pidfile = strdup(optarg);
			break;
		case 'x':
        default:
			printf("LMS Daemon version 1.11-git (%s). Command line options:\n", revision);
        	printf(" --dbhost -h host[:port]\tdatabase host (default: 'localhost')\n");
        	printf(" --dbname -d db_name\t\tdatabase name (default: 'lms')\n");
        	printf(" --dbuser -u db_user\t\tdatabase user (default: 'lms')\n");
        	printf(" --dbpass -p password\t\tdatabase password (default: '')\n");
        	printf(" --ssl -s\t\t\tuse SSL connection (default: disabled)\n");
        	printf(" --hostname -H daemon_host\thost name where runs daemon (default: `hostname`)\n");
        	printf(" --pidfile -P pid_file\t\tpidfile where daemon write pid (default: none)\n");
        	printf(" --command -c command\t\tshell command to run before database connecting\n\t\t\t\t(default: empty)\n");
        	printf(" --reload -q \t\t\tdo a reload and quit\n");
			printf(" --reload-all -r \t\tdo a reload of all instances and quit\n");
        	printf(" --foreground -f \t\trun in foreground (don't fork)\n");
        	printf(" --instance -i \"instance[ ...]\"\tlist of instances to reload\n");
        	printf(" --version -v \t\t\tprint version and copyright info\n");
        	exit(1);
		}
    }
}

static void free_module(MODULE *mod)
{
	free(mod->instance);
	free(mod->file);
	config_free(mod->ini);
	if(mod->dlh) dlclose(mod->dlh);
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

/* signal handler for SIGCHLD reaps zombie children */
void sig_child(int signum)
{
	if( signum != SIGCHLD ) return;
	
	while( waitpid(-1, NULL, WNOHANG) > 0 )	continue;
}

/* termination signals handling */
void termination_handler(int signum)
{
	if( signum )
		syslog(LOG_ERR, "LMS Daemon exited abnormally.");
	else
		syslog(LOG_INFO, "LMS Daemon exited.");
		
	exit(signum);
}

/* Initialize environement before setting process name */
static void init_set_proc_title(int argc, char **argv, char **envp)
{
    register int i, envpsize;
    char **p;

    // move the environment so setproctitle can use the space
    for(i = envpsize = 0; envp[i] != NULL; i++)
	    envpsize += strlen(envp[i]) + 1;

    if( (p = (char **)malloc((i + 1) * sizeof(char *))) != NULL )
    {
	environ = p;

	for(i=0; envp[i] != NULL; i++)
    	    if( (environ[i] = malloc(strlen(envp[i]) + 1)) != NULL )
    		strcpy(environ[i], envp[i]);

	environ[i] = NULL;
    }

    Argv = argv;

    for(i=0; i<argc; i++)
        if( !i || (LastArgv + 1 == argv[i]) )
    	    LastArgv = argv[i] + strlen(argv[i]);

    for(i=0; envp[i] != NULL; i++)
	if( LastArgv + 1 == envp[i] )
    	    LastArgv = envp[i] + strlen(envp[i]);
}

/* Set daemon processes names (hide command line arguments) */
static void set_proc_title(const char *fmt, ...)
{
    va_list msg;
    static char statbuf[BUFSIZ];
    char *p;
    int i, maxlen = (LastArgv - Argv[0]) - 2;

    va_start(msg,fmt);

    memset(statbuf, 0, sizeof(statbuf));
    vsnprintf(statbuf, sizeof(statbuf), fmt, msg);

    va_end(msg);

    i = strlen(statbuf);
    snprintf(Argv[0], maxlen, "%s", statbuf);
    p = &Argv[0][i];

    while(p < LastArgv)
	*p++ = '\0';
    Argv[1] = NULL;
}
