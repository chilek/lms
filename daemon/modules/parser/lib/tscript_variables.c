/*

T-Script - Variables
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

#include "tscript_variables.h"
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include "map.h"

#define str_constr(e) strdup(e)
#define str_comp(a, b) (strcmp(a, b) == 0)

map_declaration(variables_map, char*, tscript_value);
map_implementation(variables_map, char*, tscript_value, str_constr, no_constr, free, no_destr, str_comp);

static variables_map vars;

tscript_value* tscript_variable_get_reference(char* name)
{
	tscript_value* res;
	tscript_debug("Getting reference for variable %s\n", name);
	res = variables_map_ref(&vars, name, tscript_value_create_null());
	tscript_debug("Reference retrieved for variable %s\n", name);
	return res;
}
