 /******************************************************************
 *	              A.L.E.C's LMS Daemon
 *******************************************************************
 *  LMS version 1.5-cvs
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
 * $Id$ 
 */

#include <unistd.h>
#include <signal.h>		
#include <syslog.h>
#include <stdio.h>     
#include <string.h>
#include <dlfcn.h>  
  
#include "util.h"
#include "almsd.h"

int main(int argc, char *argv[])
{
    QUERY_HANDLE *res;  		//db query results
    GLOBAL *g;				//globals
    int opt;				//command line args
    int background = 0;			//
    int sleeptime = 0;			//
    int quit = 0;			//
    unsigned char *ini_file="/etc/lms/lms.ini";
    unsigned char *db, *user, *passwd, *host; 	//db connection params
    int port;					//
    dictionary *ini;				//config
    int reload = 0, counter = 0, reload_t = 0;	    
    unsigned char *instance, *instances, *iopt=NULL;
    unsigned char *command;

    	// read command line args
    	while ( (opt = getopt(argc, argv, "hc:bs:qi:")) != -1 ) {
		switch (opt) {
        	case 'b':
                	background = 1;
                	break;
		case 'c':
			ini_file = strdup(optarg);
			break;
        	case 's':
			sleeptime = atoi(optarg);
                	break;
		case 'q':
        		quit = 1;
                	break;
		case 'i':
        		iopt = strdup(optarg);
                	break;
		case 'h':
        	default:
			printf("A.L.E.C's LMS Daemon v.1.5-cvs. Command line options:\n");
			printf(" -c \tpath to config file (default: /etc/lms/lms.ini)\n");
                	printf(" -i \tlist of instances to reload\n");
			printf(" -b \tfork in background\n");
                	printf(" -s \tthe time the run sleeps for (seconds)\n");
                	printf(" -q \tdo a reload and quit\n");
                	exit(1);
		}
    	}

    	// start logging 
#ifdef DEBUG1
    	syslog(LOG_INFO, "A.L.E.C's LMS Daemon started.");
#endif
    
    	// initialize global structure 
    	g = (GLOBAL *) realloc(NULL,sizeof(GLOBAL));
    	g->api_version = APIVERSION;
    	g->inifile = ini_file;
    
   	g->db_query = &db_query;
	g->db_pquery = &db_pquery;
    	g->db_exec = &db_exec;
	g->db_pexec = &db_pexec;
	g->db_free = &db_free;
    	g->db_begin = &db_begin;
    	g->db_commit = &db_commit;
	g->db_abort = &db_abort;
    	g->db_get_data = &db_get_data;
    
    	g->iniparser_getstr = &iniparser_getstr;
    	g->iniparser_getstring = &iniparser_getstring;
    	g->iniparser_getint = &iniparser_getint;
    	g->iniparser_getdouble = &iniparser_getdouble;
    	g->iniparser_getboolean = &iniparser_getboolean;
	g->iniparser_load = &iniparser_load;
    	g->iniparser_freedict = &iniparser_freedict;
    
    	g->str_replace = &str_replace;
    	g->str_save = &str_save;
    	g->str_concat = &str_concat;
	g->str_lwc = &strlwc;
 
    	// daemonize
    	if ( background ) {
		int fval = fork();
        	if ( fval < 0 ) {
    	    		syslog(LOG_CRIT,"Fork error");
            		exit(1); /* fork error */
        	} else if ( fval > 0 ) {
#ifdef DEBUG1
	    		syslog(LOG_INFO, "Daemonize. Forked child %d", fval);
#endif
            		exit(0); /* parent exits */
        	}
        	setsid();
    	}

    	// termination signals handling
    	signal(SIGINT, termination_handler);
    	signal(SIGTERM, termination_handler);
    
    	// read main configuration from lms.ini
    	ini = iniparser_load(ini_file);
    
    	if( ini==NULL ) {
		syslog(LOG_CRIT, "Unable to load configuration file '%s'", ini_file);
		exit(1);
    	}
    	db = strdup(iniparser_getstring(ini,"database:database","lms"));
    	host = strdup(iniparser_getstring(ini,"database:host","localhost"));
    	user = strdup(iniparser_getstring(ini,"database:user","lms"));
    	passwd = strdup(iniparser_getstring(ini,"database:password",""));
    	port = iniparser_getint(ini,"database:port",0);
    	command = strdup(iniparser_getstring(ini,"lmsd:command",""));
    	
	// set sleeptime
    	if( !sleeptime )
		sleeptime = iniparser_getint(ini,"lmsd:sleeptime",30);

	// free ini
	iniparser_freedict(ini);    

    	// main loop ****************************************************
    	for (;;) {
		int time = 0;
		reload = 0;
		
		// don't reload while daemon starting in background mode
		if( !quit && !counter ) {
			counter++;
			sleep(sleeptime);
			continue;
		}
		
		// run shell command, i.e. secure connections tuneling
		system(command);
	
		// try to connect to database
		if( !db_connect(db,user,passwd,host,port) ) {
	    		if( quit ) termination_handler(0);
	    		sleep(sleeptime);
	    		continue;
		}        
	
        	// need reload?
        	if( quit )
   	    		reload = 1;
		else 
	    		if( (res = db_query("SELECT time FROM timestamps WHERE tablename = '_force'"))!=NULL ) 
			{
				if(res->nrows)
					time = atoi(db_get_data(res,0,"time"));
				if( time>0 && time!=reload_t ) 
				{
					reload = 1;
					reload_t = time;
				}
				db_free(res);
	    		}

		if( reload ) { // **********************************************
#ifdef DEBUG1
	    		syslog(LOG_INFO, "Reload signal detected, calling modules...");
#endif
			// read configuration from lms.ini
    			ini = iniparser_load(ini_file);

    			if( ini==NULL ) {
				syslog(LOG_ERR, "Unable to load configuration file '%s'", ini_file);
				if( quit ) termination_handler(0);
				sleep(sleeptime);
				continue;
    			}
			
			// get instances list for reload
			if(iopt)
				instances = strdup(iopt);
	    		else 	
				instances = strdup(iniparser_getstring(ini, "lmsd:instances",""));
			
			// let's initialize and reload instances/modules...
	    		for( instance=strtok(instances," "); instance!=NULL; instance=strtok(NULL, " ") ) {	
    
				unsigned char *modfile;
				unsigned char *key;
				MODULE *mod;
				MODULE * (*init)(GLOBAL *, MODULE *);

				// get module file name
				key = str_concat(instance, ":module");
				modfile = iniparser_getstr(ini, key);
				free(key);

				if( !modfile ) {
					syslog(LOG_ERR, "Can't find module for instance '%s'",instance);
					continue;
				}

				// create module and open 
				mod = (MODULE*) malloc(sizeof(MODULE));
				mod->filename = strdup(modfile);
				mod->instance = strdup(instance);

				mod->dlh = dlopen(mod->filename, RTLD_NOW);
				if( !mod->dlh ) {
					syslog(LOG_ERR, "Unable to load module '%s': %s", mod->filename, dlerror());
					free(mod->filename); free(mod->instance); free(mod);
					continue;
				}

				// initialize module
				init = dlsym(mod->dlh, "init");
				if( !init ) {
					syslog(LOG_CRIT, "Unable to find initialization function in module '%s'. Is that file really a lmsd module?", modfile);
					free(mod->filename); free(mod->instance); free(mod);
					continue;
				}
				if( !(mod = init(g, mod))) {
					syslog(LOG_CRIT, "Unable to initialize module '%s'. Perhaps there is a version mismatch?", modfile);
					free(mod->filename); free(mod->instance); free(mod);
					continue;
				}

				// now reload module
				mod->reload(g, mod);
				// close and free memory
				dlclose(mod->dlh);
				free(mod->filename); free(mod->instance); free(mod);
	    		}
  	   		// clean up
			free(instances);
			iniparser_freedict(ini);

		} // end of reload *****************************************
		db_disconnect();	  
		if (quit) termination_handler(0);
		sleep(sleeptime);    
		counter++;
    	} // end of main loop **********************************************
	return 0;
}
