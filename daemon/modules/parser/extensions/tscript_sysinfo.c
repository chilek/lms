/*

T-Script - SYSINFO EXTENSION
Copyright (C) 2004, Adrian Smarzewski <adrian@kadu.net>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

#include "tscript_sysinfo.h"
#include "tscript_extensions.h"
#include <time.h>

#ifdef WIN32
#define SYSTYPE	"win32"
#else
#define SYSTYPE "unix"
#endif

tscript_value* tscript_ext_systype()
{
	return tscript_value_create(TSCRIPT_TYPE_STRING, SYSTYPE);
}

tscript_value* tscript_ext_date(tscript_value* arg)
{
	static char buf[255];
	tscript_value* arg_str;
	struct tm* tm;
	tscript_value* res;
	time_t t = time(NULL);
	tm = localtime(&t);
	if (arg->type == TSCRIPT_TYPE_NULL)
		strftime(buf, 255, "%Y/%m/%d", tm);
	else
	{
		arg_str = tscript_value_convert_to_string(arg);
		strftime(buf, 255, tscript_value_as_string(arg_str), tm);
		tscript_value_free(arg_str);
	}
	res = tscript_value_create_string(buf);
	*tscript_value_subvar_ref(res, "year") = tscript_value_create_number(1900 + tm->tm_year);
	*tscript_value_subvar_ref(res, "month") = tscript_value_create_number(tm->tm_mon + 1);
	*tscript_value_subvar_ref(res, "day") = tscript_value_create_number(tm->tm_mday);
	*tscript_value_subvar_ref(res, "hour") = tscript_value_create_number(tm->tm_hour);
	*tscript_value_subvar_ref(res, "minute") = tscript_value_create_number(tm->tm_min);
	*tscript_value_subvar_ref(res, "second") = tscript_value_create_number(tm->tm_sec);
	return res;
}

void tscript_ext_sysinfo_init(tscript_context* context)
{
	tscript_add_constant(context, "systype", tscript_ext_systype);
	tscript_add_extension(context, "date", tscript_ext_date);
}

void tscript_ext_sysinfo_close(tscript_context* context)
{
	tscript_remove_constant(context, "systype");
	tscript_remove_extension(context, "date");
}
