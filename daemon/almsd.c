/***************************************************************
*	              A.L.E.C's LMS Daemon
*
****************************************************************/
/* $Id$ */

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
    MODULE *m;
    int opt;				//command line args
    int background = 0;			//
    int sleeptime = 0;			//
    int quit = 0;			//
    unsigned char *ini_file="/etc/lms/lms.ini";
    unsigned char *db, *user, *passwd, *host; //db connection params
    int port = 0;			//
    int reload, query, test, i, j;	//staff
    dictionary *ini;			//config
    
    // read command line args
    while ( (opt = getopt(argc, argv, "hc:bs:q")) != -1 ) {
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
	case 'h':
        default:
		printf("A.L.E.C's LMS Daemon. Command line options:");
		printf("-c \tpath to config file (default: /etc/lms/lms.ini)\n");
                printf("-b \tfork in background\n");
                printf("-s \tthe time the run sleeps for (seconds)\n");
                printf("-q \tdo a reload and quit\n");
                exit(1);
	}
    }
    
    // start logging 
    syslog(LOG_INFO, "A.L.E.C's LMS Daemon started.");
    
    // initialize global structure 
    g = (GLOBAL *) realloc(NULL,sizeof(GLOBAL));
    g->api_version = APIVERSION;
    g->modules = NULL;
    g->inifile = ini_file;
    
    g->db_query = &db_query;
    g->db_free = &db_free;
    g->db_begin = &db_begin;
    g->db_commit = &db_commit;
    g->db_abort = &db_abort;
    g->db_get_data = &db_get_data;
    
    g->iniparser_getstr = &iniparser_getstr;
    g->iniparser_getstring = &iniparser_getstring;
    g->iniparser_getint = &iniparser_getint;
    g->iniparser_getdouble = &iniparser_getdouble;
    g->iniparser_load = &iniparser_load;
    g->iniparser_freedict = &iniparser_freedict;
    
    g->str_replace = &str_replace;
    
    // daemonize
    if ( background ) {
	int fval = fork();
        if ( fval < 0 ) {
    	    syslog(LOG_CRIT,"Fork error");;
            exit(1); /* fork error */
        } else if ( fval > 0 ) {
#ifdef DEBUG
	    syslog(LOG_INFO, "Daemonize. Forked child %d", fval);
#endif
            exit(0); /* parent exits */
        }
        setsid();
    }

    // termination signals
    signal(SIGINT, termination_handler);
    signal(SIGTERM, termination_handler);
    
    // read configuration from lms.ini
    ini = iniparser_load(ini_file);
    
    if( ini==NULL ) {
	syslog(LOG_CRIT, "Unable to load configuration file '%s'", ini_file);
	exit(1);
    }
    db = strdup(iniparser_getstring(ini,"database:database","lms"));
    host = strdup(iniparser_getstring(ini,"database:host","localhost"));
    user = strdup(iniparser_getstring(ini,"database:user","lms"));
    passwd = strdup(iniparser_getstring(ini,"database:password",""));
    
    // set sleeptime
    if( !sleeptime )
	sleeptime = iniparser_getint(ini,"lmsd:sleeptime",30);
    
    
    // let's initialize modules...
    for( i = 0; ;i++ ) {
	unsigned char key[25];
	unsigned char *modfile;
	unsigned char *args;
	MODULE *mod;
	MODULE * (*init)(GLOBAL *, MODULE *);
	
	// get module file name with path
	snprintf(key, 25, "lmsd:load%d", i);
	modfile = iniparser_getstr(ini, key);
	if(!modfile) 
	    break;
	
	// get args for module
	snprintf(key, 25, "lmsd:args%d", i);
	args = iniparser_getstring(ini, key, "");
	
	// create module
	mod = (MODULE*) malloc(sizeof(MODULE));
	
	mod->filename = strdup(modfile);
	
	// parse module args
	mod->args = parse_module_argstring(args);
	
	mod->dlh = dlopen(mod->filename, RTLD_NOW);
	if(!mod->dlh) {
		syslog(LOG_CRIT, "Unable to load module '%s': %s", modfile, dlerror());
		exit(1);
	}

	init = dlsym(mod->dlh, "init");
	if(!init) {
		syslog(LOG_CRIT, "Unable to find initialization function in module '%s'. Is that file really a lmsd module?", modfile);
		exit(1);
	}
		
	// initialize module	
	if(! (mod = init(g, mod))) {
		syslog(LOG_CRIT, "Unable to initialize module '%s'. Perhaps there is a version mismatch?", modfile);
		exit(1);
	}
	mod->next = g->modules;
	g->modules = mod;
		
	//syslog(LOG_INFO, "Succefully initialized module '%s'.", modfile);
    }
	if(! g->modules)
		syslog(LOG_WARNING, "No modules specified in configuration.");
	else
		syslog(LOG_NOTICE, "%d modules initialized.", i);
		
    // free ini 
    iniparser_freedict(ini);
    
    // main loop *********************************
    for (;;) {
	reload = 0;
	
	// try to connect to database
	if( !db_connect(db,user,passwd,host,port) ) {
	    if( quit ) termination_handler(0);
	    sleep(sleeptime);
	    continue;
	}        
	
        // need reload?
        if( quit )
    	    reload = 1;
	else {
	    if( (res =  db_query("SELECT COUNT(*) AS number FROM reload"))!=NULL ) {
		if( atoi(db_get_data(res,0,"number"))>0 )
	    	    reload = 1;
		db_free(res);
	    }
	}
	
	if( reload ) {
	    syslog(LOG_INFO, "Reload signal detected, calling modules...");
	    
	    // calling modules
	    for(m = g->modules; m; m = m->next) {
		m->reload(g, m);
	    }
	    
	    // empty reload table 
	    db_exec("DELETE FROM reload"); 
	}
	db_disconnect();	  
        if (quit) termination_handler(0);
        sleep(sleeptime);
    }
    return 0;
}
