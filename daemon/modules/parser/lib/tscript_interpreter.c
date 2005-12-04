/****************************************************************************
**
** T-Script - Interpreter
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

#define interprete_arg_1()						\
	tmp1 = tscript_interprete_sub(context, ast->children[0],	\
		&sub_status);						\
	if (tmp1->type == TSCRIPT_TYPE_ERROR)				\
		return tmp1

#define interprete_arg_2()						\
	tmp2 = tscript_interprete_sub(context, ast->children[1],	\
		&sub_status);						\
	if (tmp2->type == TSCRIPT_TYPE_ERROR)				\
	{								\
		tscript_value_free(tmp1);				\
		return tmp2;						\
	}

#define interprete_2_args()						\
	interprete_arg_1();						\
	interprete_arg_2();

#define interprete_arg_1_der()						\
	interprete_arg_1();						\
	tmp1_der = tscript_value_dereference(tmp1)

#define interprete_arg_2_der()						\
	interprete_arg_2();						\
	tmp2_der = tscript_value_dereference(tmp2)

#define interprete_2_args_der()						\
	interprete_arg_1_der();						\
	interprete_arg_2_der();

#define free_2_args()							\
	tscript_value_free(tmp1);					\
	tscript_value_free(tmp2)

typedef enum interprete_status
{
	STATUS_NORMAL,
	STATUS_BREAK,
	STATUS_EXIT,
	STATUS_CONTINUE
} interprete_status;

// returns new, created value
static tscript_value* tscript_interprete_sub(tscript_context* context, tscript_ast_node* ast,
	interprete_status* status)
{
	interprete_status sub_status;
	tscript_value* res;
	tscript_value* tmp1;
	tscript_value* tmp2;
	tscript_value* tmp1_der;
	tscript_value* tmp2_der;
	tscript_value* tmp1_str;
	tscript_value* tmp2_str;
	tscript_value* tmp3;
	tscript_value* tmp4;
	tscript_value* tmp5;
	tscript_value* tmp4_der;
	tscript_value* tmp4_str;
	tscript_value* tmp5_der;
	tscript_value* tmp5_str;
	double tmp1_num;
	int i;

	tscript_debug(context, "Interpretting %s\n", ast->type);
	*status = STATUS_NORMAL;

	if (ast->type == TSCRIPT_AST_VALUE)
	{
		res = tscript_value_duplicate(ast->value);
		tscript_debug(context, "Value: %s\n", tscript_value_as_string(ast->value));
	}
	else if (ast->type == TSCRIPT_AST_VAR_GET)
	{
		interprete_arg_1_der();
		res = tscript_value_create_reference(
			tscript_variable_get_reference(context, tscript_value_as_string(tmp1_der)));
		tscript_value_free(tmp1);
	}
	else if (ast->type == TSCRIPT_AST_VAR_SET)
	{
		interprete_2_args();
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("you can only assign to reference!");
		else
		{
			tscript_debug(context, "Assigning to referenced variable\n");
			// TODO: implement reference counting - we cannot simple delete it
			//tscript_value_free(*tmp1->reference_data);
			*tmp1->reference_data = tmp2_der;
			tscript_value_free(tmp1);
			tscript_debug(context, "Assigned\n");
			res = tscript_value_create_string("");
		}
	}
	else if (ast->type == TSCRIPT_AST_INC)
	{
		double tmp;
		interprete_arg_1();
		if (tmp1->type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("reference expected!");
		else if ((*tmp1->reference_data)->type != TSCRIPT_TYPE_NUMBER)
			res = tscript_value_create_error("number type expected!");
		else
		{
			tscript_debug(context, "Incrementing referenced variable\n");
			res = tscript_value_duplicate(*tmp1->reference_data);
			tmp = tscript_value_as_number(*tmp1->reference_data);
			tmp++;
			tscript_value_free(*tmp1->reference_data);
			*tmp1->reference_data = tscript_value_create_number(tmp);
			tscript_value_free(tmp1);
			tscript_debug(context, "Incremented\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_DEC)
	{
		double tmp;
		interprete_arg_1();
		if (tmp1->type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("reference expected!");
		else if ((*tmp1->reference_data)->type != TSCRIPT_TYPE_NUMBER)
			res = tscript_value_create_error("number type expected!");
		else
		{
			tscript_debug(context, "Decrementing referenced variable\n");
			res = tscript_value_duplicate(*tmp1->reference_data);
			tmp = tscript_value_as_number(*tmp1->reference_data);
			tmp--;
			tscript_value_free(*tmp1->reference_data);
			*tmp1->reference_data = tscript_value_create_number(tmp);
			tscript_value_free(tmp1);
			tscript_debug(context, "Decremented\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_UN_INC)
	{
		double tmp;
		interprete_arg_1();
		if (tmp1->type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("reference expected!");
		else if ((*tmp1->reference_data)->type != TSCRIPT_TYPE_NUMBER)
			res = tscript_value_create_error("number type expected!");
		else
		{
			tscript_debug(context, "Incrementing referenced variable\n");
			tmp = tscript_value_as_number(*tmp1->reference_data);
			tmp++;
			tscript_value_free(*tmp1->reference_data);
			*tmp1->reference_data = tscript_value_create_number(tmp);
			res = tscript_value_duplicate(*tmp1->reference_data);
			tscript_value_free(tmp1);
			tscript_debug(context, "Incremented\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_UN_DEC)
	{
		double tmp;
		interprete_arg_1();
		if (tmp1->type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("reference expected!");
		else if ((*tmp1->reference_data)->type != TSCRIPT_TYPE_NUMBER)
			res = tscript_value_create_error("number type expected!");
		else
		{
			tscript_debug(context, "Decrementing referenced variable\n");
			tmp = tscript_value_as_number(*tmp1->reference_data);
			tmp--;
			tscript_value_free(*tmp1->reference_data);
			*tmp1->reference_data = tscript_value_create_number(tmp);
			res = tscript_value_duplicate(*tmp1->reference_data);
			tscript_value_free(tmp1);
			tscript_debug(context, "Incremented\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_INDEX)
	{
		interprete_2_args();
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type != TSCRIPT_TYPE_ARRAY && tmp1->type != TSCRIPT_TYPE_REFERENCE)
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
			free_2_args();
		}
	}
	else if (ast->type == TSCRIPT_AST_SUBVAR)
	{
		interprete_2_args();
		tmp2_der = tscript_value_dereference(tmp2);
		tmp2 = tscript_value_convert_to_string(tmp2_der);
		if (tmp1->type == TSCRIPT_TYPE_REFERENCE)
		{
			tscript_debug(context, "Left value is a reference, returning reference to subvariable\n");
			res = tscript_value_create_reference(
				tscript_value_subvar_ref(*tmp1->reference_data, tscript_value_as_string(tmp2_der)));
		}
		else
		{
			tscript_debug(context, "Left value is not a reference, returning copy of subvariable\n");
			res = tscript_value_duplicate(
				*tscript_value_subvar_ref(tmp1, tscript_value_as_string(tmp2_der)));
		}
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_EQUALS)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			tscript_value_equals(tmp1_der, tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_DIFFERS)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			!tscript_value_equals(tmp1_der, tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_LESS)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			tscript_value_less(tmp1_der, tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_GREATER)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			!tscript_value_less_or_equals(tmp1_der, tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_EQUALS_LESS)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			tscript_value_less_or_equals(tmp1_der, tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_EQUALS_GREATER)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			!tscript_value_less(tmp1_der, tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_NOT)
	{
		interprete_arg_1_der();
		res = tscript_value_create_number((!tscript_value_as_bool(tmp1_der)));
		tscript_value_free(tmp1);
	}
	else if (ast->type == TSCRIPT_AST_NEG)
	{
		interprete_arg_1_der();
		res = tscript_value_create_number(-tscript_value_as_number(tmp1_der));
		tscript_value_free(tmp1);
	}
	else if (ast->type == TSCRIPT_AST_OR)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			tscript_value_as_bool(tmp1_der) || tscript_value_as_bool(tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_AND)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			tscript_value_as_bool(tmp1_der) && tscript_value_as_bool(tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_BAND)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			(long)tscript_value_as_number(tmp1_der) & (long)tscript_value_as_number(tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_BOR)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			(long)tscript_value_as_number(tmp1_der) | (long)tscript_value_as_number(tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_LEFT)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			(long)tscript_value_as_number(tmp1_der) << (long)tscript_value_as_number(tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_RIGHT)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			(long)tscript_value_as_number(tmp1_der) >> (long)tscript_value_as_number(tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_PLUS)
	{
		interprete_2_args_der();
		res = tscript_value_add(tmp1_der, tmp2_der);
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_MINUS)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			tscript_value_as_number(tmp1_der) - tscript_value_as_number(tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_MUL)
	{
		interprete_2_args_der();
		res = tscript_value_create_number(
			tscript_value_as_number(tmp1_der) * tscript_value_as_number(tmp2_der));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_DIV)
	{
		interprete_2_args_der();
		if (tscript_value_as_number(tmp2) == 0)
			res = tscript_value_create_error("Division by zero!");
		else
		{
			res = tscript_value_create_number(
				tscript_value_as_number(tmp1_der) / tscript_value_as_number(tmp2_der));
			free_2_args();
		}
	}
	else if (ast->type == TSCRIPT_AST_MOD)
	{
		interprete_2_args_der();
		res = tscript_value_create_number((double)((int)tscript_value_as_number(tmp1_der) % (int)tscript_value_as_number(tmp2_der)));
		free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_IF)
	{
		interprete_arg_1();
		if (tscript_value_as_bool(tmp1))
		{
			tmp2 = tscript_interprete_sub(context, ast->children[1], &sub_status);
			res = tscript_value_duplicate(
				tscript_value_dereference(tmp2));
			tscript_value_free(tmp2);
			*status = sub_status;
		}
		else if(ast->children[2] != NULL)
		{
			tmp2 = tscript_interprete_sub(context, ast->children[2], &sub_status);
			res = tscript_value_duplicate(
				tscript_value_dereference(tmp2));
			tscript_value_free(tmp2);
			*status = sub_status;
		}
		else
			res = tscript_value_create_string("");
		tscript_value_free(tmp1);
	}
	else if (ast->type == TSCRIPT_AST_WHILE)
	{
		res = tscript_value_create_string("");
		for (;;)
		{
			interprete_arg_1_der();
			tmp1_num = tscript_value_as_bool(tmp1_der);
			tscript_value_free(tmp1);
			if (!tmp1_num)
				break;
			tmp1 = tscript_interprete_sub(context, ast->children[1], &sub_status);
			tmp1_der = tscript_value_dereference(tmp1);
			tmp2 = tscript_value_add(res, tmp1_der);
			tscript_value_free(res);
			tscript_value_free(tmp1);
			res = tmp2;
			if (sub_status == STATUS_BREAK || sub_status == STATUS_EXIT)
				break;
		}
	}
	else if (ast->type == TSCRIPT_AST_FOR)
	{
		tscript_value_free(tscript_interprete_sub(context, ast->children[0],
			&sub_status));
		res = tscript_value_create_string("");
		for (;;)
		{
			tmp1 = tscript_interprete_sub(context, ast->children[1], &sub_status);
			tmp1_der = tscript_value_dereference(tmp1);
			tmp1_num = tscript_value_as_bool(tmp1_der);
			tscript_value_free(tmp1);
			if (!tmp1_num)
				break;
			tmp1 = tscript_interprete_sub(context, ast->children[3], &sub_status);
			tmp1_der = tscript_value_dereference(tmp1);
			tmp2 = tscript_value_add(res, tmp1_der);
			tscript_value_free(res);
			tscript_value_free(tmp1);
			res = tmp2;
			if (sub_status == STATUS_EXIT)
				*status = sub_status;
			if (sub_status == STATUS_BREAK || sub_status == STATUS_EXIT)
				break;
			tscript_value_free(tscript_interprete_sub(context, ast->children[2],
				&sub_status));
		}
	}
	else if (ast->type == TSCRIPT_AST_FOREACH)
	{
		interprete_2_args();
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp1->type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("foreach iterator must be a reference!");
		if (tmp2_der->type != TSCRIPT_TYPE_ARRAY)
		{
			res = tscript_value_create_error(
				"foreach symbol must be an array");
		}
		else
		{
			res = tscript_value_create_string("");
			tmp3 = tscript_value_array_count(tmp2_der);
			for (i = 0; i < tscript_value_as_number(tmp3); i++)
			{
				// TODO: implement reference counting - we cannot simple delete it
				//tscript_value_free(*tmp1->reference_data);
				tmp4 = tscript_value_array_item_get(tmp2_der, i);
				*tmp1->reference_data =
					tscript_value_duplicate(tmp4);
				tmp4 = tscript_interprete_sub(context, ast->children[2], &sub_status);
				tmp4_der = tscript_value_dereference(tmp4);
				tmp5 = tscript_value_add(res, tmp4_der);
				tscript_value_free(res);
				tscript_value_free(tmp4);
				res = tmp5;
				if (sub_status == STATUS_EXIT)
					*status = sub_status;
				if (sub_status == STATUS_BREAK || sub_status == STATUS_EXIT)
					break;
			}
		}
		//free_2_args();
	}
	else if (ast->type == TSCRIPT_AST_SEQ)
	{
		interprete_arg_1_der();
		tmp1_str = tscript_value_convert_to_string(tmp1_der);
		if (sub_status != STATUS_NORMAL)
		{
			tscript_value_free(tmp1);
			*status = sub_status;
			return tmp1_str;
		}
		interprete_arg_2_der();
		tmp2_str = tscript_value_convert_to_string(tmp2_der);
		free_2_args();
		res = tscript_value_add(tmp1_str, tmp2_str);
		tscript_value_free(tmp1_str);
		tscript_value_free(tmp2_str);
		*status = sub_status;
	}
	else if (ast->type == TSCRIPT_AST_ARGS)
	{
		if (ast->children[1] == NULL)
			res = tscript_interprete_sub(context, ast->children[0], &sub_status);
		else
		{
			res = tscript_value_create_array();
			for (i = 0; ast->children[i] != NULL; i++)
			{
				tmp1 = tscript_interprete_sub(context, ast->children[i],
					&sub_status);
				if (tmp1->type == TSCRIPT_TYPE_ERROR)
				{
					tscript_value_free(res);
					res = tmp1;
					break;
				}
				tmp2 = tscript_value_create_number(i);
				*tscript_value_array_item_ref(&res, tmp2) = tmp1;
				tscript_value_free(tmp2);
			}
		}
	}
	else if (ast->type == TSCRIPT_AST_CONV)
	{
		interprete_arg_1_der();
		if (ast->value->type == TSCRIPT_TYPE_STRING)
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
		tmp1 = tscript_interprete_sub(context, ast->children[0], &sub_status);
		res = tscript_value_type_string(tmp1);
		tscript_value_free(tmp1);
	}
	else if (ast->type == TSCRIPT_AST_EXT)
	{
		interprete_arg_1_der();
		if (ast->children[1] == NULL)
			tmp2 = tscript_value_create_null();
		else
			tmp2 = tscript_interprete_sub(context, ast->children[1], &sub_status);
		tmp2_der = tscript_value_dereference(tmp2);
		if (tmp2->type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			tmp1_str = tscript_value_convert_to_string(tmp1_der);
			tmp2_str = tscript_value_convert_to_string(tmp2_der);
			tscript_debug(context, "Extension name: %s\n", tscript_value_as_string(tmp1_str));
			tscript_debug(context, "Extension param: %s\n", tscript_value_as_string(tmp2_str));
			res = tscript_run_extension(context, tscript_value_as_string(tmp1_str), tmp2_der);
			tscript_value_free(tmp1_str);
			tscript_value_free(tmp2_str);
			free_2_args();
		}
	}
	else if (ast->type == TSCRIPT_AST_CONST)
	{
		interprete_arg_1_der();
		tmp1_str = tscript_value_convert_to_string(tmp1_der);
		tscript_value_free(tmp1);
		tscript_debug(context, "Constant name: %s\n", tscript_value_as_string(tmp1_str));
		res = tscript_run_constant(context, tscript_value_as_string(tmp1_str));
		tscript_value_free(tmp1_str);
	}
	else if (ast->type == TSCRIPT_AST_MATCH)
	{
		interprete_2_args_der();
		tmp1_str = tscript_value_convert_to_string(tmp1_der);
		tmp2_str = tscript_value_convert_to_string(tmp2_der);
		free_2_args();
		tscript_debug(context, "Value to match: %s\n", tscript_value_as_string(tmp1_str));
		tscript_debug(context, "Regular expression: %s\n",tscript_value_as_string(tmp2_str));
		res = tscript_match_regexp(tscript_value_as_string(tmp1_str), tscript_value_as_string(tmp2_str));
		tscript_value_free(tmp1_str);
		tscript_value_free(tmp2_str);
	}
	else if (ast->type == TSCRIPT_AST_BREAK)
	{
		res = tscript_value_create_string("");
		*status = STATUS_BREAK;
	}
	else if (ast->type == TSCRIPT_AST_EXIT)
	{
		res = tscript_value_create_string("");
		*status = STATUS_EXIT;
	}
	else if (ast->type == TSCRIPT_AST_CONTINUE)
	{
		res = tscript_value_create_string("");
		*status = STATUS_CONTINUE;
	}
	else
		tscript_internal_error("Internal error: incorrect node type!\n");
	tscript_debug(context, "Interpreted %s\n", ast->type);
	return res;
}

tscript_value* tscript_interprete(tscript_context* context)
{
	interprete_status sub_status;
	tscript_value* res;
	tscript_debug(context, "Interpretting\n");
	res = tscript_interprete_sub(context, context->ast, &sub_status);
	tscript_debug(context, "Interpreted\n");
	return res;
}
