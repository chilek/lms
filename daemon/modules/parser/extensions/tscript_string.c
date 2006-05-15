/****************************************************************************
**
** T-Script - STRING extension
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

#include "tscript_string.h"
#include "tscript_extensions.h"
#include "tscript_values.h"

#include <string.h>
#include <ctype.h>
#include <sys/types.h>
#include <regex.h>

tscript_value* tscript_ext_trim(tscript_value* arg)
{
	const char* s;
	char* tmp;
	int i;
	tscript_value* val = tscript_value_convert_to_string(arg);
	s = tscript_value_as_string(val);
	for (i = 0; isspace(s[i]); i++) {};
	tmp = strdup(&s[i]);
	for (i = strlen(tmp) - 1; i >= 0 && isspace(tmp[i]); i--)
		tmp[i] = 0;
	val = tscript_value_create_string(tmp);
	free(tmp);
	return val;
}

tscript_value* tscript_ext_len(tscript_value* arg)
{
	tscript_value* res;
	tscript_value* val = tscript_value_convert_to_string(arg);
	res = tscript_value_create_number(strlen(tscript_value_as_string(val)));
	tscript_value_free(val);
	return res;
}

tscript_value* tscript_ext_replace(tscript_value* args)
{
	regex_t* reg;
	regmatch_t match;
	int res;
	char* buf;
	tscript_value* str;
	tscript_value* regexp;
	tscript_value* dst;
	regexp = tscript_extension_arg(args, 0);
	dst = tscript_extension_arg(args, 1);
	str = tscript_value_duplicate(tscript_extension_arg(args, 2));
	reg = (regex_t *)calloc(1, sizeof(regex_t));
	res = regcomp(reg, tscript_value_as_string(regexp), REG_EXTENDED);
	if (res != 0)
		return tscript_value_create_error("incorrect regexp");
	while (regexec(reg, tscript_value_as_string(str), 1, &match, 0) == 0)
	{
		buf = (char*)malloc(match.rm_so + strlen(tscript_value_as_string(dst)) +
			strlen(&tscript_value_as_string(str)[match.rm_eo]) + 1);
		if (match.rm_so > 0)
			strncpy(buf, tscript_value_as_string(str), match.rm_so);
		buf[match.rm_so] = 0;
		strcat(buf, tscript_value_as_string(dst));
		strcat(buf, &tscript_value_as_string(str)[match.rm_eo]);
		tscript_value_free(str);
		str = tscript_value_create_string(buf);
		free(buf);
	}
	regfree(reg);
	return str;
}

tscript_value* tscript_ext_explode(tscript_value* args)
{
	regex_t* reg;
	regmatch_t match;
	int res;
	char* buf;
	tscript_value* str;
	tscript_value* regexp;
	tscript_value* index;
	tscript_value* array;
	regexp = tscript_extension_arg(args, 0);
	str = tscript_value_duplicate(tscript_extension_arg(args, 1));
	array = tscript_value_create_array();
	reg = (regex_t *)calloc(1, sizeof(regex_t));
	res = regcomp(reg, tscript_value_as_string(regexp), REG_EXTENDED);
	if (res != 0)
		return tscript_value_create_error("incorrect regexp");
	res = 0;
	while (regexec(reg, tscript_value_as_string(str), 1, &match, 0) == 0)
	{
		index = tscript_value_create_number(res);
		buf = (char*)malloc(strlen(tscript_value_as_string(str)) + 1);
		strncpy(buf, tscript_value_as_string(str), match.rm_so);
		buf[match.rm_so] = 0;
		*tscript_value_array_item_ref(&array, index) =
			tscript_value_create_string(buf);
		free(buf);
		tscript_value_free(index);
		res++;
		buf = strdup(&tscript_value_as_string(str)[match.rm_eo]);
		tscript_value_free(str);
		str = tscript_value_create_string(buf);
		free(buf);
	}
	if (tscript_value_as_string(str)[0] != 0)
	{
		index = tscript_value_create_number(res);
		*tscript_value_array_item_ref(&array, index) =
			tscript_value_create_string(tscript_value_as_string(str));
		tscript_value_free(index);
	}
	tscript_value_free(str);
	regfree(reg);
	return array;
}

void tscript_ext_string_init(tscript_context* context)
{
	tscript_add_extension(context, "trim", tscript_ext_trim, 1, 1);
	tscript_add_extension(context, "len", tscript_ext_len, 1, 1);
	tscript_add_extension(context, "replace", tscript_ext_replace, 3, 3);
	tscript_add_extension(context, "explode", tscript_ext_explode, 2, 2);
}

void tscript_ext_string_close(tscript_context* context)
{
	tscript_remove_extension(context, "trim");
	tscript_remove_extension(context, "len");
	tscript_remove_extension(context, "replace");
	tscript_remove_extension(context, "explode");
}
