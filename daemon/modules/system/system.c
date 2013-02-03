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
#include <syslog.h>
#include <string.h>

#include "lmsd.h"
#include "system.h"

void reload(GLOBAL *g, struct system_module *s)
{
	if(*s->sql)
	{
		g->db_exec(g->conn, s->sql);
	}

	if(*s->command)
	{
		system(s->command);
	}
#ifdef DEBUG1
	syslog(LOG_INFO, "DEBUG: [%s/system] reloaded", s->base.instance);
#endif
	free(s->command);
	free(s->sql);
}

struct system_module * init(GLOBAL *g, MODULE *m)
{
	struct system_module *s;
	
	if(g->api_version != APIVERSION) 
	{
	        return (NULL);
	}
	
	s = (struct system_module *) realloc(m, sizeof(struct system_module));
	
	s->base.reload = (void (*)(GLOBAL *, MODULE *)) &reload;

	s->command = strdup(g->config_getstring(s->base.ini, s->base.instance, "command", ""));
	s->sql = strdup(g->config_getstring(s->base.ini, s->base.instance, "sql", ""));
#ifdef DEBUG1
	syslog(LOG_INFO,"DEBUG: [%s/system] initialized", s->base.instance);
#endif	
	return(s);
}
