/*
 * LMS version 1.4-cvs
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

#include "almsd.h"
#include "system.h"

void reload(GLOBAL *g, struct system_module *s)
{
	system(s->command);
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/system] reloaded", s->base.instance);
#endif
	free(s->command);
}

struct system_module * init(GLOBAL *g, MODULE *m)
{
	struct system_module *s;
	unsigned char *instance, *a;
	dictionary *ini;
	
	if(g->api_version != APIVERSION) 
	    return (NULL);
	
	instance = m->instance;
	
	s = (struct system_module *) realloc(m, sizeof(struct system_module));
	
	s->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;
	s->base.instance = strdup(instance);

	ini = g->iniparser_load(g->inifile);

	a = g->str_concat(instance, ":command");
	s->command = strdup(g->iniparser_getstring(ini, a, ""));
	
	g->iniparser_freedict(ini);
	free(a);
	free(instance);
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/system] initialized", s->base.instance);
#endif	
	return(s);
}
