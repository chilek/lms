/*

T-Script - STRING EXTENSION
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

#include "tscript_string.h"
#include "tscript_extensions.h"
#include "tscript_values.h"

#include <string.h>
#include <ctype.h>

tscript_value* tscript_ext_trim(tscript_value* arg)
{
	char* tmp;
	int i;
	tscript_value* val = tscript_value_convert_to_string(arg);
	for (i = 0; isspace(val->data[i]); i++) {};
	tmp = strdup(&val->data[i]);
	for (i = strlen(tmp) - 1; i >= 0 && isspace(tmp[i]); i--)
		tmp[i] = 0;
	val = tscript_value_create_string(tmp);
	free(tmp);
	return val;
}

void tscript_ext_string_init(tscript_context* context)
{
	tscript_add_extension(context, "trim", tscript_ext_trim);
}

void tscript_ext_string_close(tscript_context* context)
{
	tscript_remove_extension(context, "trim");
}
