/*

T-Script - Interpreter
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

#include "tscript_interpreter.h"
#include <string.h>
#include <stdio.h>
#include <stdlib.h>
#include <regex.h>
#include "tscript_ast.h"
#include "tscript_variables.h"
#include "tscript_parser.h"
#include "tscript_extensions.h"

static tscript_value* tscript_match_regexp(char* str, char* regexp)
{
	regex_t* reg;
	int res;
	reg = (regex_t *)calloc(1, sizeof(regex_t));
	res = regcomp(reg, regexp, REG_EXTENDED | REG_NOSUB);
	if (res != 0)
		return tscript_value_create_error("incorrect regexp");
	res = regexec(reg, str, 0, NULL, 0);
	regfree(reg);
	if (res == 0)
		return tscript_value_create_number(1);
	else if (res == REG_NOMATCH)
		return tscript_value_create_number(0);
	else
		return tscript_value_create_error("unknown regexp error");
}

static tscript_value* tscript_save_to_file(char* filename, char* str)
{
	int len;
	FILE* f = fopen(filename, "a");
	if (f == NULL)
		return tscript_value_create_error("error opening file %s", filename);
	len = strlen(str);
	if (fwrite(str, 1, len, f) != len)
	{
		fclose(f);
		return tscript_value_create_error("error writting file %s", filename);
	}
	if (fclose(f) != 0)
		return tscript_value_create_error("error closing file %s", filename);
	return tscript_value_create_string("");
}

// returns new, created value
static tscript_value* tscript_interprete_sub(tscript_context* context, tscript_ast_node* ast)
{
	tscript_value* res;
	tscript_value* tmp1;
	tscript_value* tmp2;
	tscript_value* tmp1_der;
	tscript_value* tmp2_der;
	tscript_value* tmp1_str;
	tscript_value* tmp2_str;
	double tmp1_num;
	int i;

	if (ast->type == TSCRIPT_AST_VALUE)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_VAR_VALUE\n");
		res = tscript_value_duplicate(ast->value);
		tscript_debug(context, "Value: %s\n", ast->value->data);
		tscript_debug(context, "Interpreted TSCRIPT_AST_VAR_VALUE\n");
	}
	else if (ast->type == TSCRIPT_AST_VAR_GET)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_VAR_GET\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp1_der = tscript_value_dereference(tmp1);
		res = tscript_value_create_reference(
			tscript_variable_get_reference(context, tmp1_der->data));
		tscript_value_free(tmp1);
		tscript_debug(context, "Interpreted TSCRIPT_AST_VAR_GET\n");
	}
	else if (ast->type == TSCRIPT_AST_VAR_SET)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_VAR_SET\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else if (tmp1->type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("you can only assign tu reference!");
		else
		{
			tscript_debug(context, "Assigning to referenced variable\n");
			tscript_value_free(*tmp1->reference_data);
			*tmp1->reference_data = tmp2_der;
			tscript_value_free(tmp1);
			tscript_debug(context, "Assigned\n");
			res = tscript_value_create_string("");
			tscript_debug(context, "Interpreted TSCRIPT_AST_VAR_SET\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_INC)
	{
		double tmp;
		tscript_debug(context, "Interpretting TSCRIPT_AST_INC\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp1->type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("reference expected!");
		else if ((*tmp1->reference_data)->type != TSCRIPT_TYPE_NUMBER)
			res = tscript_value_create_error("number type expected!");
		else
		{
			tscript_debug(context, "Incrementing referenced variable\n");
			res = tscript_value_duplicate(*tmp1->reference_data);
			tmp = atof((*tmp1->reference_data)->data);
			tmp++;
			tscript_value_free(*tmp1->reference_data);
			*tmp1->reference_data = tscript_value_create_number(tmp);
			tscript_value_free(tmp1);
			tscript_debug(context, "Incremented\n");
			tscript_debug(context, "Interpreted TSCRIPT_AST_INC\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_DEC)
	{
		double tmp;
		tscript_debug(context, "Interpretting TSCRIPT_AST_DEC\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp1->type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("reference expected!");
		else if ((*tmp1->reference_data)->type != TSCRIPT_TYPE_NUMBER)
			res = tscript_value_create_error("number type expected!");
		else
		{
			tscript_debug(context, "Decrementing referenced variable\n");
			res = tscript_value_duplicate(*tmp1->reference_data);
			tmp = atof((*tmp1->reference_data)->data);
			tmp--;
			tscript_value_free(*tmp1->reference_data);
			*tmp1->reference_data = tscript_value_create_number(tmp);
			tscript_value_free(tmp1);
			tscript_debug(context, "Decremented\n");
			tscript_debug(context, "Interpreted TSCRIPT_AST_DEC\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_INDEX)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_VAR_INDEX\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else if (tmp1->type != TSCRIPT_TYPE_ARRAY && tmp1->type != TSCRIPT_TYPE_REFERENCE)
		{
			res = tscript_value_create_error(
				"Indexed symbol must be array or reference");
		}
		else
		{
			if (tmp1->type == TSCRIPT_TYPE_REFERENCE)
			{
				tscript_debug(context, "Left value is a reference, returning reference to array cell\n");
				res = tscript_value_create_reference(
					tscript_value_array_item_ref(
						tmp1->reference_data,
						tmp2_der));
			}
			else
			{
				tscript_debug(context, "Left value is an array, returning copy of array cell\n");
				res = tscript_value_duplicate(*tscript_value_array_item_ref(
						&tmp1,
						tmp2_der));
			}
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
			tscript_debug(context, "Interpreted TSCRIPT_AST_VAR_INDEX\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_SUBVAR)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_SUBVAR\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			tmp2 = tscript_value_convert_to_string(tmp2_der);
			if (tmp1->type == TSCRIPT_TYPE_REFERENCE)
			{
				tscript_debug(context, "Left value is a reference, returning reference to subvariable\n");
				res = tscript_value_create_reference(
					tscript_value_subvar_ref(*tmp1->reference_data, tmp2_der->data));
			}
			else
			{
				tscript_debug(context, "Left value is not a reference, returning copy of subvariable\n");
				res = tscript_value_duplicate(
					*tscript_value_subvar_ref(tmp1, tmp2_der->data));
			}
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
			tscript_debug(context, "Interpreted TSCRIPT_AST_SUBVAR\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_EQUALS)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				tscript_value_compare(tmp1_der, tmp2_der));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_DIFFERS)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				!tscript_value_compare(tmp1_der, tmp2_der));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_LESS)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				atof(tmp1_der->data) < atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_GREATER)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				atof(tmp1_der->data) > atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_EQUALS_LESS)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				atof(tmp1_der->data) <= atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_EQUALS_GREATER)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				atof(tmp1_der->data) >= atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_NOT)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_NOT\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp1_der = tscript_value_dereference(tmp1);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else
		{
			res = tscript_value_create_number((!atof(tmp1_der->data)));
			tscript_value_free(tmp1);
		}
		tscript_debug(context, "Interpretting TSCRIPT_AST_NOT\n");
	}
	else if (ast->type == TSCRIPT_AST_NEG)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_NEG\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp1_der = tscript_value_dereference(tmp1);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else
		{
			res = tscript_value_create_number(-atof(tmp1_der->data));
			tscript_value_free(tmp1);
		}
		tscript_debug(context, "Interpretting TSCRIPT_AST_NEG\n");
	}
	else if (ast->type == TSCRIPT_AST_OR)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				atof(tmp1_der->data) || atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_AND)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				atof(tmp1_der->data) && atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_BAND)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_BAND\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				(long)atof(tmp1_der->data) & (long)atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
		tscript_debug(context, "Interpretting TSCRIPT_AST_BAND\n");
	}
	else if (ast->type == TSCRIPT_AST_BOR)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_BOR\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				(long)atof(tmp1_der->data) | (long)atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
		tscript_debug(context, "Interpretting TSCRIPT_AST_BOR\n");
	}
	else if (ast->type == TSCRIPT_AST_PLUS)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_add(tmp1_der, tmp2_der);
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_MINUS)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				atof(tmp1_der->data) - atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_MUL)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number(
				atof(tmp1_der->data) * atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_DIV)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else if (atof(tmp2->data) == 0)
			res = tscript_value_create_error("Division by zero!");
		else
		{
			res = tscript_value_create_number(
				atof(tmp1_der->data) / atof(tmp2_der->data));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_MOD)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res = tscript_value_create_number((double)((int)atof(tmp1_der->data) % (int)atof(tmp2_der->data)));
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
		}
	}
	else if (ast->type == TSCRIPT_AST_IF)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		if (atof(tscript_value_dereference(tmp1)->data))
		{
			tmp2 = tscript_interprete_sub(context, ast->children[1]);
			res = tscript_value_duplicate(
				tscript_value_dereference(tmp2));
			tscript_value_free(tmp2);
		}
		else if(ast->children[2] != NULL)
		{
			tmp2 = tscript_interprete_sub(context, ast->children[2]);
			res = tscript_value_duplicate(
				tscript_value_dereference(tmp2));
			tscript_value_free(tmp2);
		}
		else
			res = tscript_value_create_string("");
		tscript_value_free(tmp1);
	}
	else if (ast->type == TSCRIPT_AST_FOR)
	{
		tscript_value_free(tscript_interprete_sub(context, ast->children[0]));
		res = tscript_value_create_string("");
		for (;;)
		{
			tmp1 = tscript_interprete_sub(context, ast->children[1]);
			tmp1_der = tscript_value_dereference(tmp1);
			tmp1_num = atof(tmp1_der->data);
			tscript_value_free(tmp1);
			if (!tmp1_num)
				break;
			tmp1 = tscript_interprete_sub(context, ast->children[3]);
			tmp1_der = tscript_value_dereference(tmp1);
			tmp2 = tscript_value_add(res, tmp1_der);
			tscript_value_free(res);
			tscript_value_free(tmp1);
			res = tmp2;
			tscript_value_free(tscript_interprete_sub(context, ast->children[2]));
		}
	}
	else if (ast->type == TSCRIPT_AST_FILE)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_FILE\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			tmp1_str = tscript_value_convert_to_string(tmp1_der);
			tmp2_str = tscript_value_convert_to_string(tmp2_der);
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
			tscript_debug(context, "File name: %s\n", tmp1_str->data);
			res = tscript_save_to_file(tmp1_str->data, tmp2_str->data);
			tscript_value_free(tmp1_str);
			tscript_value_free(tmp2_str);
			tscript_debug(context, "Interpreted TSCRIPT_AST_FILE\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_SEQ)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_SEQ\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			tmp1_str = tscript_value_convert_to_string(tmp1_der);
			tmp2_str = tscript_value_convert_to_string(tmp2_der);
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
			res = tscript_value_add(tmp1_str, tmp2_str);
			tscript_value_free(tmp1_str);
			tscript_value_free(tmp2_str);
			tscript_debug(context, "Interpreted TSCRIPT_AST_SEQ\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_ARGS)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_ARGS\n");
		if (ast->children[1] == NULL)
			res = tscript_interprete_sub(context, ast->children[0]);
		else
		{
			res = tscript_value_create_array();
			for (i = 0; ast->children[i] != NULL; i++)
			{
				tmp1 = tscript_interprete_sub(context, ast->children[i]);
				tmp1_der = tscript_value_dereference(tmp1);
				if (tmp1->type == TSCRIPT_TYPE_ERROR)
				{
					tscript_value_free(res);
					res = tmp1;
					break;
				}
				tmp2 = tscript_value_create_number(i);
				*tscript_value_array_item_ref(&res, tmp2) = tmp1_der;
				tscript_value_free(tmp2);
			}
		}
		tscript_debug(context, "Interpreted TSCRIPT_AST_ARGS\n");
	}
	else if (ast->type == TSCRIPT_AST_CONV)
	{
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp1_der = tscript_value_dereference(tmp1);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (ast->value->type == TSCRIPT_TYPE_STRING)
		{
			res = tscript_value_convert_to_string(tmp1_der);
			tscript_value_free(tmp1);
		}
		else if (ast->value->type == TSCRIPT_TYPE_NUMBER)
		{
			res = tscript_value_convert_to_number(tmp1_der);
			tscript_value_free(tmp1);
		}
		else
			tscript_internal_error("Incorrect conversion");
	}
	else if (ast->type == TSCRIPT_AST_TYPEOF)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_TYPEOF\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		res = tscript_value_type_string(tmp1);
		tscript_value_free(tmp1);
		tscript_debug(context, "Interpreted TSCRIPT_AST_TYPEOF\n");
	}
	else if (ast->type == TSCRIPT_AST_EXT)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_EXT\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		if (ast->children[1] == NULL)
			tmp2 = tscript_value_create_null();
		else
			tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			tmp1_str = tscript_value_convert_to_string(tmp1_der);
			tmp2_str = tscript_value_convert_to_string(tmp2_der);
			tscript_debug(context, "Extension name: %s\n", tmp1_str->data);
			tscript_debug(context, "Extension param: %s\n", tmp2_str->data);
			res = tscript_run_extension(context, tmp1_str->data, tmp2_der);
			tscript_value_free(tmp1_str);
			tscript_value_free(tmp2_str);
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
			tscript_debug(context, "Interpreted TSCRIPT_AST_EXT\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_CONST)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_CONST\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp1_der = tscript_value_dereference(tmp1);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else
		{
			tmp1_str = tscript_value_convert_to_string(tmp1_der);
			tscript_value_free(tmp1);
			tscript_debug(context, "Constant name: %s\n", tmp1_str->data);
			res = tscript_run_constant(context, tmp1_str->data);
			tscript_value_free(tmp1_str);
			tscript_debug(context, "Interpreted TSCRIPT_AST_CONST\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_MATCH)
	{
		tscript_debug(context, "Interpretting TSCRIPT_AST_MATCH\n");
		tmp1 = tscript_interprete_sub(context, ast->children[0]);
		tmp2 = tscript_interprete_sub(context, ast->children[1]);
		tmp1_der = tscript_value_dereference(tmp1);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			tmp1_str = tscript_value_convert_to_string(tmp1_der);
			tmp2_str = tscript_value_convert_to_string(tmp2_der);
			tscript_value_free(tmp1);
			tscript_value_free(tmp2);
			tscript_debug(context, "Value to match: %s\n", tmp1_str->data);
			tscript_debug(context, "Regular expression: %s\n",tmp2_str->data);
			res = tscript_match_regexp(tmp1_str->data, tmp2_str->data);
			tscript_value_free(tmp1_str);
			tscript_value_free(tmp2_str);
			tscript_debug(context, "Interpreted TSCRIPT_AST_MATCH\n");
		}
	}
	else
		tscript_internal_error("Internal error: incorrect node type!\n");
	return res;
}

tscript_value* tscript_interprete(tscript_context* context)
{
	tscript_value* res;
	tscript_debug(context, "Interpretting\n");
	res = tscript_interprete_sub(context, context->ast);
	tscript_debug(context, "Interpreted\n");
	return res;
}
