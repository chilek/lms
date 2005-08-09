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

#include "tscript_extensions.h"
#include <stdlib.h>
#include <string.h>

#define str_constr(e) strdup(e)
#define str_comp(a, b) (strcmp(a, b) == 0)

map_implementation(tscript_extension_map, char*, tscript_extension_func*, str_constr, no_constr, free, no_destr, str_comp);
map_implementation(tscript_constant_map, char*, tscript_constant, str_constr, no_constr, free, no_destr, str_comp);

static tscript_extension_map tscript_extensions;
static tscript_constant_map tscript_constants;

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
	tscript_extension_func* f;
	if (!tscript_extension_map_contains(&tscript_extensions, keyword))
		tscript_internal_error("Cannot find extension\n");
	f = *tscript_extension_map_ref(&tscript_extensions, keyword, NULL);
	return f(arg);
}

void tscript_add_constant(char* keyword, tscript_constant_func* func)
{
	tscript_constant c;
	c.func = func;
	c.cached = 0;
	c.value = tscript_value_create_null();
	tscript_constant_map_add(&tscript_constants, keyword, c);
}

void tscript_remove_constant(char* keyword)
{
	tscript_constant_map_remove(&tscript_constants, keyword);
}

int tscript_has_constant(char* keyword)
{
	return tscript_constant_map_contains(&tscript_constants, keyword);
}

tscript_value tscript_run_constant(char* keyword)
{
	tscript_constant* c;
	// TODO: passing cn as default is not very nice
	tscript_constant cn;
	if (!tscript_constant_map_contains(&tscript_constants, keyword))
		tscript_internal_error("Cannot find constant\n");
	c = tscript_constant_map_ref(&tscript_constants, keyword, cn);
	if (!c->cached)
	{
		c->value = c->func();
		c->cached = 1;
	}
	return c->value;
}
