/****************************************************************************
**
** T-Script - Variables
** Copyright (C) 2004-2005, SILVERCODERS Adrian Smarzewski
** http://silvercoders.com
**
** Project homepage: http://silvercoders.com/index.php?page=T_Script
** Project authors:  Adrian Smarzewski
**
** This program may be distributed and/or modified under the terms of the
** GNU General Public License version 2 as published by the Free Software
** Foundation and appearing in the file COPYING.GPL included in the
** packaging of this file.
**
** Please remember that any attempt to workaround the GNU General Public
** License using wrappers, pipes, client/server protocols, and so on
** is considered as license violation. If your program, published on license
** other than GNU General Public License version 2, calls some part of this
** code directly or indirectly, you have to buy commerial license.
** If you do not like our point of view, simply do not use the product.
**
** Licensees holding valid commercial license for this product
** may use this file in accordance with the license published by
** Silvercoders and appearing in the file COPYING.COM
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
**
*****************************************************************************/

#include "tscript_variables.h"
#include "tscript_variables_private.h"
#include <stdlib.h>
#include <stdio.h>
#include <string.h>

#define str_constr(e) strdup(e)
#define str_comp(a, b) (strcmp(a, b) == 0)

map_implementation(tscript_variables_map, char*, tscript_value*, str_constr, no_constr, free, /*tscript_value_free*/no_destr, str_comp);

tscript_value** tscript_variable_get_reference(tscript_context* context, char* name)
{
	tscript_value** res;
	tscript_value* def;
	tscript_debug(context, "Getting reference for variable %s\n", name);
	if (tscript_variables_map_contains(context->vars, name))
	{
		def = tscript_value_create_null();
		res = tscript_variables_map_ref(context->vars, name, def);
		tscript_value_free(def);
	}
	else
		res = tscript_variables_map_add(context->vars, name, tscript_value_create_null());
	tscript_debug(context, "Reference retrieved for variable %s\n", name);
	return res;
}
