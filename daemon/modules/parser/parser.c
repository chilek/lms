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
#include <syslog.h>
#include <string.h>

#include "lmsd.h"
#include "parser.h"

#include "tscript_context.h"
#include "tscript_interpreter.h"
#include "tscript_compiler.h"
#include "tscript_debug.h"
#include "extensions/tscript_exec.h"
#include "extensions/tscript_string.h"
#include "extensions/tscript_file.h"
#include "extensions/tscript_sysinfo.h"
#include "extensions/sql.h"
#include "extensions/net.h"
#include "extensions/syslog.h"

void debug_callback(const char* format, va_list ap)
{
	vfprintf(stderr, format, ap);
}

void reload(GLOBAL *g, struct parser_module *p)
{
	FILE *fh;
	tscript_value *res, *out;
	tscript_context *context = tscript_context_create();
	
	tscript_ext_exec_init(context);
	tscript_ext_file_init(context);
	tscript_ext_net_init(context);
	tscript_ext_sysinfo_init(context);
	tscript_ext_string_init(context);
	tscript_ext_syslog_init(context);
	tscript_ext_sql_init(context, g->conn);

//	tscript_set_debug_callback(context, debug_callback);

	if(!strlen(p->script))
		syslog(LOG_ERR, "ERROR: [%s/parser] empty 'script' option", p->base.instance);
	else
	{
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/parser] compiling...", p->base.instance);
#endif
		if( tscript_compile_string(context, p->script) == 0 )
		{
			res = tscript_interprete(context);
		
			if( res->type != TSCRIPT_TYPE_ERROR )
			{
				out = tscript_value_convert_to_string(res);
				
				if(strlen(p->file))
				{
					fh = fopen(p->file, "w");
					if(!fh)
						syslog(LOG_ERR, "ERROR: [%s/parser] unable to open '%s' file for writing", p->base.instance, p->file);
					else
					{
				    		fprintf(fh, "%s", out->data);
						fclose(fh);
					}
				}
				tscript_value_free(out);

				if(strlen(p->command))
				{
#ifdef DEBUG1
					syslog(LOG_INFO, "DEBUG: [%s/parser] executing command...", p->base.instance);
#endif
					system(p->command);
				}
			} 
			else
				syslog(LOG_ERR, "ERROR: [%s/parser] interprete error: %s", p->base.instance, res->data);

			tscript_value_free(res);
		}
		else
			syslog(LOG_ERR, "ERROR: [%s/parser] compile error: %s", p->base.instance, tscript_compile_error(context));
#ifdef DEBUG1
		syslog(LOG_INFO, "DEBUG: [%s/parser] reloaded", p->base.instance);
#endif
	}

	tscript_ext_exec_close(context);
	tscript_ext_file_close(context);
	tscript_ext_net_close(context);
	tscript_ext_sysinfo_close(context);
	tscript_ext_string_close(context);
	tscript_ext_syslog_close(context);
	tscript_ext_sql_close(context);
	
	tscript_context_free(context);

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
