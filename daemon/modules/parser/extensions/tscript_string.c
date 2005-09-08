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
#include <regex.h>

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

tscript_value* tscript_ext_replace(tscript_value* arg)
{
	regex_t* reg;
	regmatch_t match;
	int res;
	char* buf;
	tscript_value* tmp;
	tscript_value* str;
	tscript_value* regexp;
	tscript_value* dst;
	tscript_value* index;
	if (arg->type != TSCRIPT_TYPE_ARRAY)
		return tscript_value_create_error("replace: 3 arguments required");
	tmp = tscript_value_array_count(arg);
	res = atof(tmp->data);
	tscript_value_free(tmp);
	if (res != 3)
		return tscript_value_create_error("replace: 3 arguments required");
	index = tscript_value_create_number(0);
	regexp = *tscript_value_array_item_ref(&arg, index);
	tscript_value_free(index);
	index = tscript_value_create_number(1);
	dst = *tscript_value_array_item_ref(&arg, index);
	tscript_value_free(index);
	index = tscript_value_create_number(2);
	str = tscript_value_duplicate(*tscript_value_array_item_ref(&arg, index));
	tscript_value_free(index);
	reg = (regex_t *)calloc(1, sizeof(regex_t));
	res = regcomp(reg, regexp->data, REG_EXTENDED);
	if (res != 0)
		return tscript_value_create_error("incorrect regexp");
	while (regexec(reg, str->data, 1, &match, 0) == 0)
	{
		buf = (char*)malloc(match.rm_so + strlen(dst->data) +
			strlen(&str->data[match.rm_eo]) + 1);
		if (match.rm_so > 0)
			strncpy(buf, str->data, match.rm_so);
		buf[match.rm_so] = 0;
		strcat(buf, dst->data);
		strcat(buf, &str->data[match.rm_eo]);
		tscript_value_free(str);
		str = tscript_value_create_string(buf);
		free(buf);
	}
	regfree(reg);
	return str;
}

void tscript_ext_string_init(tscript_context* context)
{
	tscript_add_extension(context, "trim", tscript_ext_trim);
	tscript_add_extension(context, "replace", tscript_ext_replace);
}

void tscript_ext_string_close(tscript_context* context)
{
	tscript_remove_extension(context, "trim");
	tscript_remove_extension(context, "replace");
}
