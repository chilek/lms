/*
 *  LMS version 1.11-git
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

#include "syslog.h"
#include "tscript_extensions.h"
#include "tscript_variables.h"

tscript_value * tscript_ext_syslog_syslog(tscript_value *arg)
{
	tscript_value *log;
	int n = LOG_INFO;

	if( arg->type == TSCRIPT_TYPE_ARRAY )
	{
		tscript_value *index, *level;
		
		index = tscript_value_create_number(0);
		log = tscript_value_convert_to_string(tscript_value_dereference(
			*tscript_value_array_item_ref(&arg, index)));
		tscript_value_free(index);

		index = tscript_value_create_number(1);
		level = tscript_value_convert_to_number(tscript_value_dereference(
			*tscript_value_array_item_ref(&arg, index)));
		tscript_value_free(index);
		
		n = tscript_value_as_number(level);

		tscript_value_free(level);
	}
	else
	{
		log = tscript_value_convert_to_string(arg);
	}

	syslog(n, "%s", tscript_value_as_string(log));

	tscript_value_free(log);

	return tscript_value_create_null();
}

void tscript_ext_syslog_init(tscript_context *context)
{
	tscript_add_extension(context, "syslog", tscript_ext_syslog_syslog, 1, 2);

	*tscript_variable_get_reference(context, "LOG_EMERG") = tscript_value_create_number(LOG_EMERG);
	*tscript_variable_get_reference(context, "LOG_ALERT") = tscript_value_create_number(LOG_ALERT);
	*tscript_variable_get_reference(context, "LOG_CRIT") = tscript_value_create_number(LOG_CRIT);
	*tscript_variable_get_reference(context, "LOG_ERR") = tscript_value_create_number(LOG_ERR);
	*tscript_variable_get_reference(context, "LOG_WARNING") = tscript_value_create_number(LOG_WARNING);
	*tscript_variable_get_reference(context, "LOG_NOTICE") = tscript_value_create_number(LOG_NOTICE);
	*tscript_variable_get_reference(context, "LOG_INFO") = tscript_value_create_number(LOG_INFO);
	*tscript_variable_get_reference(context, "LOG_DEBUG") = tscript_value_create_number(LOG_DEBUG);
}

void tscript_ext_syslog_close(tscript_context *context)
{
	tscript_remove_extension(context, "syslog");
}
