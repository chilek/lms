/****************************************************************************
**
** T-Script - Extensions support
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

#include "tscript_extensions.h"
#include <stdlib.h>
#include <string.h>

#define str_constr(e) strdup(e)
#define str_comp(a, b) (strcmp(a, b) == 0)
#define tscript_constant_destr(e) tscript_value_free(e.value)

map_implementation(tscript_extension_map, char*, tscript_extension, str_constr, no_constr, free, no_destr, str_comp);
map_implementation(tscript_constant_map, char*, tscript_constant, str_constr, no_constr, free, tscript_constant_destr, str_comp);

void tscript_add_extension(tscript_context* context, char* keyword, tscript_extension_func* func,
	int min_args, int max_args)
{
	tscript_extension e;
	e.func = func;
	e.block = 0;
	e.min_args = min_args;
	e.max_args = max_args;
	tscript_extension_map_add(context->extensions, keyword, e);
}

void tscript_remove_extension(tscript_context* context, char* keyword)
{
	tscript_extension_map_remove(context->extensions, keyword);
}

void tscript_extension_set_block(tscript_context* context, char* keyword)
{
	// TODO: passing en as default is not very nice
	tscript_extension en;
	tscript_extension_map_ref(context->extensions, keyword, en)->block = 1;
}

int tscript_extension_is_block(tscript_context* context, char* keyword)
{
	// TODO: passing en as default is not very nice
	tscript_extension en;
	return tscript_extension_map_ref(context->extensions, keyword, en)->block;
}

int tscript_has_extension(tscript_context* context, char* keyword)
{
	return tscript_extension_map_contains(context->extensions, keyword);
}

static int tscript_extension_check_min_args(tscript_extension* e, tscript_value* arg)
{
	tscript_value* tmp;
	int count;
	if (arg->type == TSCRIPT_TYPE_NULL)
		return (e->min_args <= 0);
	if (arg->type != TSCRIPT_TYPE_ARRAY)
		return (e->min_args <= 1);
	tmp = tscript_value_array_count(arg);
	count = tscript_value_as_number(tmp);
	tscript_value_free(tmp);
	return (e->min_args <= count);
}

static int tscript_extension_check_max_args(tscript_extension* e, tscript_value* arg)
{
	tscript_value* tmp;
	int count;
	if (arg->type == TSCRIPT_TYPE_NULL || e->max_args < 0)
		return 1;
	if (arg->type != TSCRIPT_TYPE_ARRAY)
		return (e->max_args >= 1);
	tmp = tscript_value_array_count(arg);
	count = tscript_value_as_number(tmp);
	tscript_value_free(tmp);
	return (e->max_args >= count);
}

tscript_value* tscript_run_extension(
	tscript_context* context, char* keyword, tscript_value* args)
{
	tscript_extension* e;
	// TODO: passing en as default is not very nice
	tscript_extension en;
	if (!tscript_extension_map_contains(context->extensions, keyword))
		tscript_internal_error("Cannot find extension\n");
	e = tscript_extension_map_ref(context->extensions, keyword, en);
	if (!tscript_extension_check_min_args(e, args))
		return tscript_value_create_error("%s: too small number of arguments, minimum %i required", keyword, e->min_args);
	if (!tscript_extension_check_max_args(e, args))
		return tscript_value_create_error("%s: too many arguments, maximum %i allowed", keyword, e->max_args);
	return e->func(args);
}

tscript_value* tscript_extension_arg(tscript_value* args, int n)
{
	tscript_value* count;
	int count_num;
	tscript_value* index;
	tscript_value* result;

	if (args->type == TSCRIPT_TYPE_ARRAY)
	{
		count = tscript_value_array_count(args);
		count_num = tscript_value_as_number(count);
		tscript_value_free(count);
		if (n >= count_num)
			return NULL;
		index = tscript_value_create_number(n);
		result = *tscript_value_array_item_ref(&args, index);
		tscript_value_free(index);
		return result;
	}
	else
	{
		if (n != 0)
			return NULL;
		return args;
	}
}

void tscript_add_constant(tscript_context* context, char* keyword, tscript_constant_func* func)
{
	tscript_constant c;
	c.func = func;
	c.cached = 0;
	c.value = tscript_value_create_null();
	tscript_constant_map_add(context->constants, keyword, c);
}

void tscript_remove_constant(tscript_context* context, char* keyword)
{
	tscript_constant_map_remove(context->constants, keyword);
}

int tscript_has_constant(tscript_context* context, char* keyword)
{
	return tscript_constant_map_contains(context->constants, keyword);
}

tscript_value* tscript_run_constant(tscript_context* context, char* keyword)
{
	tscript_constant* c;
	// TODO: passing cn as default is not very nice
	tscript_constant cn;
	if (!tscript_constant_map_contains(context->constants, keyword))
		tscript_internal_error("Cannot find constant\n");
	c = tscript_constant_map_ref(context->constants, keyword, cn);
	if (!c->cached)
	{
		c->value = c->func();
		c->cached = 1;
	}
	return tscript_value_create_reference(&c->value);
}
