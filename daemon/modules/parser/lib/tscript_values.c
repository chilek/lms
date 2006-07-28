/****************************************************************************
**
** T-Script - Values
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

#include "tscript_values.h"
#include <string.h>
#include <stdarg.h>

#define str_constr(e) (strdup(e))
#define str_comp(a, b) (strcmp(a, b) == 0)

map_implementation(tscript_values_array, tscript_value*, tscript_value*,
	tscript_value_duplicate, tscript_value_duplicate, tscript_value_free, tscript_value_free,
	tscript_value_equals);
map_implementation(tscript_values_list, char*, tscript_value*,
	str_constr, tscript_value_duplicate, free, tscript_value_free, str_comp);

tscript_value* tscript_value_create(tscript_value_type type, char* data)
{
	tscript_value* v = (tscript_value*)malloc(sizeof(tscript_value));
	v->type = type;
	if (type != TSCRIPT_TYPE_NULL)
		asprintf(&v->data, "%s", data);
	else
		v->data = NULL;
	v->reference_data = NULL;
	if (type == TSCRIPT_TYPE_ARRAY)
		v->array_data = tscript_values_array_create();
	else
		v->array_data = NULL;
	v->sub_variables = tscript_values_list_create();
	return v;
}

tscript_value* tscript_value_create_error(const char* format, ...)
{
	tscript_value* res;
	char* msg;
	va_list va;
	
	va_start(va, format);
	vasprintf(&msg, format, va);
	va_end(va);

	res = tscript_value_create(TSCRIPT_TYPE_ERROR, msg);
	free(msg);
	return res;
}

tscript_value* tscript_value_create_null()
{
	return tscript_value_create(TSCRIPT_TYPE_NULL, "");
}

tscript_value* tscript_value_create_number(double val)
{
	tscript_value* v;
	char* tmp;
	int len, i;

	asprintf(&tmp, "%f", val);
	len = strlen(tmp);
	for (i = len - 1; tmp[i] == '0'; i--)
		tmp[i] = '\0';
	if (tmp[i] == '.')
		tmp[i] = '\0';

	v = tscript_value_create(TSCRIPT_TYPE_NUMBER, tmp);
	free(tmp);
	return v;
}

tscript_value* tscript_value_create_string(char* str)
{
	return tscript_value_create(TSCRIPT_TYPE_STRING, str);
}

tscript_value* tscript_value_create_array()
{
	return tscript_value_create(TSCRIPT_TYPE_ARRAY, "");
}

tscript_value* tscript_value_create_reference(tscript_value** val)
{
	tscript_value* v = tscript_value_create(TSCRIPT_TYPE_REFERENCE, "");
	v->reference_data = val;
	return v;
}

void tscript_value_free(tscript_value* val)
{
	if (val->data != NULL)
		free(val->data);
	if (val->array_data != NULL)
		tscript_values_array_free(val->array_data);
	if (val->sub_variables != NULL)
		tscript_values_list_free(val->sub_variables);
	free(val);
}

double tscript_value_as_number(tscript_value* val)
{
	tscript_value* tmp;
	tmp = tscript_value_dereference(val);
	if (tmp->type != TSCRIPT_TYPE_NUMBER)
		return 0;
	return atof(tmp->data);
}

char* tscript_value_as_string(tscript_value* val)
{
	tscript_value* tmp;
	tmp = tscript_value_dereference(val);
	if (tmp->type != TSCRIPT_TYPE_STRING && tmp->type != TSCRIPT_TYPE_ERROR)
		return NULL;
	return tmp->data;
}

int tscript_value_as_bool(tscript_value* val)
{
	tscript_value* tmp;
	tmp = tscript_value_dereference(val);
	if (tmp->type == TSCRIPT_TYPE_STRING)
		return (strlen(tmp->data) > 0);
	if (tmp->type == TSCRIPT_TYPE_NUMBER)
		return (atof(tmp->data) > 0);
	return 0;
}

tscript_value* tscript_value_array_count(tscript_value* val)
{
	int res;
	if (val->type != TSCRIPT_TYPE_ARRAY)
		return tscript_value_create_error("Cannot count items, value is not an array");
	res = tscript_values_array_count(val->array_data);
	return tscript_value_create_number(res);
}

tscript_value** tscript_value_array_item_ref(tscript_value** val, tscript_value* index)
{
	tscript_value** v;
	if ((*val)->type != TSCRIPT_TYPE_ARRAY)
	{
		tscript_value_free(*val);
		*val = tscript_value_create_array();
	}
	v = tscript_values_array_ref((*val)->array_data, index, tscript_value_create_null());
	return v;
}

tscript_value* tscript_value_array_item_key(tscript_value* val, int index)
{
	return tscript_values_array_key(val->array_data, index);
}

tscript_value* tscript_value_array_item_get(tscript_value* val, int index)
{
	return tscript_values_array_get(val->array_data, index);
}

tscript_value** tscript_value_subvar_ref(tscript_value* val, char* name)
{
	tscript_value** v;
	v = tscript_values_list_ref(val->sub_variables, name, tscript_value_create_null());
	return v;
}

tscript_value* tscript_value_dereference(tscript_value* val)
{
	if (val->type == TSCRIPT_TYPE_REFERENCE)
	{
		if (val->reference_data == NULL)
			tscript_internal_error("Reference pointer is NULL!\n");
		return tscript_value_dereference(*val->reference_data);
	}
	else
		return val;
}

tscript_value* tscript_value_convert_to_string(tscript_value* val)
{
	tscript_value* r;
	switch(val->type)
	{
		case TSCRIPT_TYPE_ERROR:
			r = tscript_value_create_error(val->data);
			break;
		case TSCRIPT_TYPE_NULL:
			r = tscript_value_create_string("");
			break;
		case TSCRIPT_TYPE_REFERENCE:
			r = tscript_value_create_string("(reference)");
			break;		
		case TSCRIPT_TYPE_NUMBER:
			r = tscript_value_create_string(val->data);
			break;		
		case TSCRIPT_TYPE_STRING:
			r = tscript_value_create_string(val->data);
			break;
		case TSCRIPT_TYPE_ARRAY:
			r = tscript_value_create_string("(array)");
			break;
		default:
			tscript_internal_error("Incorrect type in tscript_value_convert_to_string(): %s",
				tscript_value_type_string(val)->data);
	}
	return r;
}

tscript_value* tscript_value_convert_to_number(tscript_value* val)
{
	tscript_value* r;
	char* tmp;
	switch(val->type)
	{
		case TSCRIPT_TYPE_NULL:
			r =  tscript_value_create_error("Cannot convert null value to number");
			break;
		case TSCRIPT_TYPE_REFERENCE:
			r = tscript_value_create_error("Cannot convert reference to number");
			break;		
		case TSCRIPT_TYPE_NUMBER:
			r = tscript_value_create_number(atof(val->data));
			break;		
		case TSCRIPT_TYPE_STRING:
			r = tscript_value_create_number(atof(val->data));
			break;
		case TSCRIPT_TYPE_ARRAY:
			r = tscript_value_array_count(val);
			break;
		default:
			tscript_internal_error("Incorrect type in tscript_value_convert_to_number(): %s",
				tscript_value_type_string(val)->data);
	}
	return r;
}

tscript_value* tscript_value_duplicate(tscript_value* val)
{
	tscript_value* res = (tscript_value*)malloc(sizeof(tscript_value));
	res->type = val->type;
	if (val->data == NULL)
		res->data = NULL;
	else
		res->data = strdup(val->data);
	res->reference_data = val->reference_data;
	if (val->array_data == NULL)
		res->array_data = NULL;
	else
		res->array_data = tscript_values_array_duplicate(val->array_data);
	if (val->sub_variables == NULL)
		res->sub_variables = NULL;
	else
		res->sub_variables = tscript_values_list_duplicate(val->sub_variables);
	return res;
}

tscript_value* tscript_value_add(tscript_value* val1, tscript_value* val2)
{
	tscript_value* res;
	char* s;
	if (val1->type == TSCRIPT_TYPE_NUMBER && val2->type == TSCRIPT_TYPE_NUMBER)
		return tscript_value_create_number(atof(val1->data) + atof(val2->data));
	else
	{
		asprintf(&s, "%s%s", val1->data, val2->data);
		res = tscript_value_create_string(s);
		free(s);
		return res;
	}
}

int tscript_value_equals(tscript_value* val1, tscript_value* val2)
{
	if (
		val1->type == TSCRIPT_TYPE_STRING && val2->type == TSCRIPT_TYPE_STRING ||
		val1->type == TSCRIPT_TYPE_STRING && val2->type == TSCRIPT_TYPE_NUMBER ||
		val1->type == TSCRIPT_TYPE_NUMBER && val2->type == TSCRIPT_TYPE_STRING
	)
		return (strcmp(val1->data, val2->data) == 0);
	else if (val1->type == TSCRIPT_TYPE_NUMBER && val2->type == TSCRIPT_TYPE_NUMBER)
		return (atof(val1->data) == atof(val2->data));
	else if (val1->type == TSCRIPT_TYPE_NULL && val2->type == TSCRIPT_TYPE_NULL)
		return 1;
	else
		return 0;
}

int tscript_value_less(tscript_value* val1, tscript_value* val2)
{
	if (
		val1->type == TSCRIPT_TYPE_STRING && val2->type == TSCRIPT_TYPE_STRING ||
		val1->type == TSCRIPT_TYPE_STRING && val2->type == TSCRIPT_TYPE_NUMBER ||
		val1->type == TSCRIPT_TYPE_NUMBER && val2->type == TSCRIPT_TYPE_STRING ||
		val1->type == TSCRIPT_TYPE_NUMBER && val2->type == TSCRIPT_TYPE_NUMBER
	)
		return (atof(val1->data) < atof(val2->data));
	else
		return 0;
}

int tscript_value_less_or_equals(tscript_value* val1, tscript_value* val2)
{
	if (
		val1->type == TSCRIPT_TYPE_STRING && val2->type == TSCRIPT_TYPE_STRING ||
		val1->type == TSCRIPT_TYPE_STRING && val2->type == TSCRIPT_TYPE_NUMBER ||
		val1->type == TSCRIPT_TYPE_NUMBER && val2->type == TSCRIPT_TYPE_STRING ||
		val1->type == TSCRIPT_TYPE_NUMBER && val2->type == TSCRIPT_TYPE_NUMBER
	)
		return (atof(val1->data) <= atof(val2->data));
	else
		return 0;
}

tscript_value* tscript_value_type_string(tscript_value* val)
{
	tscript_value* val_der = tscript_value_dereference(val);
	switch (val_der->type)
	{
		case TSCRIPT_TYPE_ERROR:
			return tscript_value_create_string("error");
		case TSCRIPT_TYPE_NULL:
			return tscript_value_create_string("null");
		case TSCRIPT_TYPE_NUMBER:
			return tscript_value_create_string("number");
		case TSCRIPT_TYPE_STRING:
			return tscript_value_create_string("string");
		case TSCRIPT_TYPE_ARRAY:
			return tscript_value_create_string("array");
		default:
			return tscript_value_create_string("unknown");
	}
}
