/*
 * LMS version 1.7-cvs
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

#include "lmsd.h"
#include "parser.h"

#include "lib/interpreter.h"
#include "lib/tscript_compiler.h"
#include "extensions/exec.h"
#include "extensions/sql.h"

void reload(GLOBAL *g, struct parser_module *p)
{
	FILE * fh;
	unsigned char *out;

	tscript_ext_exec_init();
	tscript_ext_sql_init(g->conn);

	if(!strlen(p->script))
		syslog(LOG_ERR, "ERROR: [%s/parser] empty 'script' option", p->base.instance);
	else
	{
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/parser] compiling...", p->base.instance);
#endif
		if( tscript_compile_string(p->script) != 0 )
		{
			syslog(LOG_ERR, "ERROR: [%s/parser] compile error", p->base.instance);
		}

		out = tscript_value_convert_to_string(tscript_interprete()).data;

		if(strlen(p->file))
		{
			fh = fopen(p->file, "w");
			if(!fh)
				syslog(LOG_ERR, "ERROR: [%s/parser] unable to open '%s' file for writing", p->base.instance, p->file);
			else
			{
			        fprintf(fh, "%s", out);
				fclose(fh);
			}
		}

		if(strlen(p->command))
		{
#ifdef DEBUG1
			syslog(LOG_INFO, "DEBUG: [%s/parser] executing command...", p->base.instance);
#endif
			system(p->command);
		}
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/parser] reloaded", p->base.instance);
#endif
	}

	tscript_ext_exec_close();
	tscript_ext_sql_close();
	
	free(p->command);
	free(p->script);
	free(p->file);
}

struct parser_module * init(GLOBAL *g, MODULE *m)
{
	struct parser_module *p;
	
	if(g->api_version != APIVERSION) 
	{
	        return (NULL);
	}
	
	p = (struct parser_module *) realloc(m, sizeof(struct parser_module));
	
	p->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	p->command = strdup(g->config_getstring(p->base.ini, p->base.instance, "command", ""));
	p->script = strdup(g->config_getstring(p->base.ini, p->base.instance, "script", ""));
	p->file = strdup(g->config_getstring(p->base.ini, p->base.instance, "file", ""));
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/parser] initialized", p->base.instance);
#endif	
	return(p);
}
