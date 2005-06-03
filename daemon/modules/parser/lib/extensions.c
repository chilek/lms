/*

T-Script - Extensions
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

#include "extensions.h"
#include <stdlib.h>
#include <string.h>

#define str_constr(e) strdup(e)
#define str_comp(a, b) (strcmp(a, b) == 0)

map_implementation(tscript_extension_map, char*, tscript_extension_func*, str_constr, no_constr, free, no_destr, str_comp);

static tscript_extension_map tscript_extensions;

void tscript_add_extension(char* keyword, tscript_extension_func* func)
{
	tscript_extension_map_add(&tscript_extensions, keyword, func);
}

void tscript_remove_extension(char* keyword)
{
	tscript_extension_map_remove(&tscript_extensions, keyword);
}

int tscript_has_extension(char* keyword)
{
	return tscript_extension_map_contains(&tscript_extensions, keyword);
}

tscript_value tscript_run_extension(char* keyword, tscript_value arg)
{
	if (!tscript_extension_map_contains(&tscript_extensions, keyword))
		tscript_internal_error("Cannot find extension\n");
	tscript_extension_func* f = *tscript_extension_map_ref(&tscript_extensions, keyword, NULL);
	return f(arg);
}
