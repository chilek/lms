/*

T-Script - Values
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

#include "tscript_values.h"
#include <string.h>
#include <stdarg.h>

#define str_constr(e) (strdup(e))
#define int_comp(a, b) (a == b)
#define str_comp(a, b) (strcmp(a, b) == 0)

map_implementation(tscript_values_array, int, tscript_value, no_constr, no_constr, no_destr, no_destr, int_comp);
map_implementation(tscript_values_list, char*, tscript_value, str_constr, no_constr, free, no_destr, str_comp);

tscript_value tscript_value_create(tscript_value_type type, char* data)
{
	tscript_value v;
	v.type = type;
	if (type != TSCRIPT_TYPE_NULL)
		asprintf(&v.data, "%s", data);
	else
		v.data = NULL;
	v.reference_data = NULL;
	if (type == TSCRIPT_TYPE_ARRAY)
	{
		v.array_data = (tscript_values_array*)malloc(sizeof(tscript_values_array));
		v.array_data->first = NULL;	
	}
	else
		v.array_data = NULL;
	v.sub_variables = (tscript_values_list*)malloc(sizeof(tscript_values_list));
	v.sub_variables->first = NULL;
	return v;
}

tscript_value tscript_value_create_error(const char* format, ...)
{
	char* msg;
	va_list va;
	
	va_start(va, format);
	vasprintf(&msg, format, va);
	va_end(va);

	return tscript_value_create(TSCRIPT_TYPE_ERROR, msg);
}

tscript_value tscript_value_create_null()
{
	return tscript_value_create(TSCRIPT_TYPE_NULL, "");
}

tscript_value tscript_value_create_number(double val)
{
	tscript_value v;
	char* tmp;
	asprintf(&tmp, "%g", val);
	v = tscript_value_create(TSCRIPT_TYPE_NUMBER, tmp);
	free(tmp);
	return v;
}

tscript_value tscript_value_array_count(tscript_value* val)
{
	int res;
	if (val->type != TSCRIPT_TYPE_ARRAY)
		return tscript_value_create_error("Cannot count items, value is not an array");
	tscript_debug("Counting array elements\n");
	res = tscript_values_array_count(val->array_data);
	tscript_debug("Array elements counted: %i\n", res);
	return tscript_value_create_number(res);
}

tscript_value* tscript_value_array_item_ref(tscript_value* val, int index)
{
	tscript_value* v;
	tscript_debug("Accessing array item, index: %i\n", index);
	if (val->type != TSCRIPT_TYPE_ARRAY)
	{
		tscript_debug("Converting variable to an array\n");
		val->type = TSCRIPT_TYPE_ARRAY;
		val->data = NULL;
		val->reference_data = NULL;
		val->array_data = (tscript_values_array*)malloc(sizeof(tscript_values_array));
		val->array_data->first = NULL;
	}
	v = tscript_values_array_ref(val->array_data, index, tscript_value_create(TSCRIPT_TYPE_NULL, NULL));
	tscript_debug("Accessing array item finished\n");
	return v;
}

tscript_value* tscript_value_subvar_ref(tscript_value* val, char* name)
{
	tscript_value* v;
	tscript_debug("Accessing subvariable, name: %s\n", name);
	v = tscript_values_list_ref(val->sub_variables, name, tscript_value_create(TSCRIPT_TYPE_NULL, NULL));
	tscript_debug("Accessing subvariables finished\n");
	return v;
}

tscript_value tscript_value_dereference(tscript_value val)
{
	tscript_value r;
	tscript_debug("Dereferencing value\n");
	if (val.type == TSCRIPT_TYPE_REFERENCE)
	{
		tscript_debug("Is a reference, dereferencing\n");
		if (val.reference_data == NULL)
			tscript_internal_error("Reference pointer is NULL!\n");
		r = *val.reference_data;
		return r;
	}
	else
	{
		tscript_debug("Is not a reference, returning original value\n");	
		return val;
	}
}

tscript_value tscript_value_convert_to_string(tscript_value val)
{
	tscript_value r;
	switch(val.type)
	{
		case TSCRIPT_TYPE_ERROR:
			r = val;
			break;
		case TSCRIPT_TYPE_NULL:
			r = tscript_value_create(TSCRIPT_TYPE_STRING, "");
			break;
		case TSCRIPT_TYPE_REFERENCE:
			r = tscript_value_create(TSCRIPT_TYPE_STRING, "(reference)");
			break;		
		case TSCRIPT_TYPE_NUMBER:
			r = tscript_value_create(TSCRIPT_TYPE_STRING, val.data);
			break;		
		case TSCRIPT_TYPE_STRING:
			r = val;
			break;
		case TSCRIPT_TYPE_ARRAY:
			r = tscript_value_create(TSCRIPT_TYPE_STRING, "(array)");
			break;
		default:
			tscript_internal_error("Incorrect type in tscript_value_convert_to_string()");
	}
	return r;
}

tscript_value tscript_value_convert_to_number(tscript_value val)
{
	tscript_value r;
	char* tmp;
	switch(val.type)
	{
		case TSCRIPT_TYPE_NULL:
			r =  tscript_value_create_error("Cannot convert null value to number");
			break;
		case TSCRIPT_TYPE_REFERENCE:
			r = tscript_value_create_error("Cannot convert reference to number");
			break;		
		case TSCRIPT_TYPE_NUMBER:
			r = val;
			break;		
		case TSCRIPT_TYPE_STRING:
			asprintf(&tmp, "%g", atof(val.data));
			r = tscript_value_create(TSCRIPT_TYPE_NUMBER, tmp);
			free(tmp);
			break;
		case TSCRIPT_TYPE_ARRAY:
			tscript_debug("Converting array to number\n");
			r = tscript_value_array_count(&val);
			tscript_debug("Converted array to number: %s\n", r.data);
			break;
		default:
			tscript_internal_error("Incorrect type in tscript_value_convert_to_string()");
	}
	return r;
}

