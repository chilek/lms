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
#include "tscript_ast.h"
#include "tscript_variables.h"
#include "tscript_parser.h"
#include "tscript_extensions.h"

static tscript_value tscript_interprete_sub(tscript_ast_node* ast)
{
	tscript_value res;
	tscript_value tmp1;
	tscript_value tmp2;

	if (ast->type == TSCRIPT_AST_VALUE)
	{
		tscript_debug("Interpretting TSCRIPT_AST_VAR_VALUE\n");
		res = ast->value;
		tscript_debug("Value: %s\n", ast->value.data);
		tscript_debug("Interpreted TSCRIPT_AST_VAR_VALUE\n");
	}
	else if (ast->type == TSCRIPT_AST_VAR_GET)
	{
		tscript_debug("Interpretting TSCRIPT_AST_VAR_GET\n");
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		res.type = TSCRIPT_TYPE_REFERENCE;
		res.reference_data = tscript_variable_get_reference(tmp1.data);
		tscript_debug("Interpreted TSCRIPT_AST_VAR_GET\n");
	}
	else if (ast->type == TSCRIPT_AST_VAR_SET)
	{
		tscript_debug("Interpretting TSCRIPT_AST_VAR_SET\n");
		tmp1 = tscript_interprete_sub(ast->children[0]);
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else if (tmp1.type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("you can only assign tu reference!");
		else
		{
			tscript_debug("Assigning to referenced variable\n");
			*tmp1.reference_data = tmp2;
			tscript_debug("Assigned\n");
			res.type = TSCRIPT_TYPE_STRING;
			res.data = (char*)malloc(1);
			res.data[0] = 0;
			tscript_debug("Interpreted TSCRIPT_AST_VAR_SET\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_INC)
	{
		double tmp;
		tscript_debug("Interpretting TSCRIPT_AST_INC\n");
		tmp1 = tscript_interprete_sub(ast->children[0]);
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp1.type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("reference expected!");
		else if (tmp1.reference_data->type != TSCRIPT_TYPE_NUMBER)
			res = tscript_value_create_error("number type expected!");
		else
		{
			tscript_debug("Incrementing referenced variable\n");
			tmp = atof(tmp1.reference_data->data);
			tmp++;
			free(tmp1.reference_data->data);
			asprintf(&tmp1.reference_data->data, "%g", tmp);
			tscript_debug("Incremented\n");
			res = *tmp1.reference_data;
			tscript_debug("Interpreted TSCRIPT_AST_INC\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_DEC)
	{
		double tmp;
		tscript_debug("Interpretting TSCRIPT_AST_DEC\n");
		tmp1 = tscript_interprete_sub(ast->children[0]);
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp1.type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("reference expected!");
		else if (tmp1.reference_data->type != TSCRIPT_TYPE_NUMBER)
			res = tscript_value_create_error("number type expected!");
		else
		{
			tscript_debug("Decrementing referenced variable\n");
			tmp = atof(tmp1.reference_data->data);
			tmp--;
			free(tmp1.reference_data->data);
			asprintf(&tmp1.reference_data->data, "%g", tmp);
			tscript_debug("Decremented\n");
			res = *tmp1.reference_data;
			tscript_debug("Interpreted TSCRIPT_AST_DEC\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_INDEX)
	{
		tscript_debug("Interpretting TSCRIPT_AST_VAR_INDEX\n");
		tmp1 = tscript_interprete_sub(ast->children[0]);
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else if (tmp1.type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("Indexed symbol must be a reference");
		else
		{
			res.type = TSCRIPT_TYPE_REFERENCE;
			res.reference_data = 
				tscript_value_array_item_ref(tmp1.reference_data, atoi(tscript_value_dereference(tmp2).data));
			tscript_debug("Array variable: %s\n", tscript_value_convert_to_string(*tmp1.reference_data).data);
			tmp2 = tscript_value_dereference(res);
			tscript_debug("Indexed variable: %s\n", tscript_value_convert_to_string(tmp2).data);
			tscript_debug("Interpreted TSCRIPT_AST_VAR_INDEX\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_SUBVAR)
	{
		tscript_debug("Interpretting TSCRIPT_AST_SUBVAR\n");
		tmp1 = tscript_interprete_sub(ast->children[0]);
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else if (tmp1.type != TSCRIPT_TYPE_REFERENCE)
			res = tscript_value_create_error("Subvar must be extracted from a reference");
		else
		{
			res.type = TSCRIPT_TYPE_REFERENCE;
			res.reference_data = 
				tscript_value_subvar_ref(tmp1.reference_data, tscript_value_convert_to_string(tmp2).data);
			tmp2 = tscript_value_dereference(res);
			tscript_debug("Interpreted TSCRIPT_AST_SUBVAR\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_EQUALS)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			res.data = (char*)malloc(2);
			if (tmp1.type == TSCRIPT_TYPE_STRING && tmp2.type == TSCRIPT_TYPE_STRING)
				res.data[0] = (strcmp(tmp1.data, tmp2.data) == 0);
			else
				res.data[0] = (atof(tmp1.data) == atof(tmp2.data) ? '1' : '0');
			res.data[1] = 0;
		}
	}
	else if (ast->type == TSCRIPT_AST_DIFFERS)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			res.data = (char*)malloc(2);
			res.data[0] = (atof(tmp1.data) != atof(tmp2.data) ? '1' : '0');
			res.data[1] = 0;
		}
	}
	else if (ast->type == TSCRIPT_AST_LESS)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			res.data = (char*)malloc(2);
			res.data[0] = (atof(tmp1.data) < atof(tmp2.data) ? '1' : '0');
			res.data[1] = 0;
		}
	}
	else if (ast->type == TSCRIPT_AST_GREATER)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			res.data = (char*)malloc(2);
			res.data[0] = (atof(tmp1.data) > atof(tmp2.data) ? '1' : '0');
			res.data[1] = 0;
		}
	}
	else if (ast->type == TSCRIPT_AST_EQUALS_LESS)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			res.data = (char*)malloc(2);
			res.data[0] = (atof(tmp1.data) <= atof(tmp2.data) ? '1' : '0');
			res.data[1] = 0;
		}
	}
	else if (ast->type == TSCRIPT_AST_EQUALS_GREATER)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			res.data = (char*)malloc(2);
			res.data[0] = (atof(tmp1.data) >= atof(tmp2.data) ? '1' : '0');
			res.data[1] = 0;
		}
	}
	else if (ast->type == TSCRIPT_AST_OR)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			res.data = (char*)malloc(2);
			res.data[0] = (atof(tmp1.data) || atof(tmp2.data) ? '1' : '0');
			res.data[1] = 0;
		}
	}
	else if (ast->type == TSCRIPT_AST_AND)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			res.data = (char*)malloc(2);
			res.data[0] = (atof(tmp1.data) && atof(tmp2.data) ? '1' : '0');
			res.data[1] = 0;
		}
	}
	else if (ast->type == TSCRIPT_AST_PLUS)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else if (tmp1.type == TSCRIPT_TYPE_NUMBER && tmp2.type == TSCRIPT_TYPE_NUMBER)
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			asprintf(&res.data, "%g", atof(tmp1.data) + atof(tmp2.data));
		}
		else
		{
			res.type = TSCRIPT_TYPE_STRING;
			asprintf(&res.data, "%s%s", tmp1.data, tmp2.data);
		}
	}
	else if (ast->type == TSCRIPT_AST_MINUS)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			asprintf(&res.data, "%g", atof(tmp1.data) - atof(tmp2.data));
		}
	}
	else if (ast->type == TSCRIPT_AST_MUL)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			asprintf(&res.data, "%g", atof(tmp1.data) * atof(tmp2.data));
		}
	}
	else if (ast->type == TSCRIPT_AST_DIV)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else if (atof(tmp2.data) == 0)
			res = tscript_value_create_error("Division by zero!");
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			asprintf(&res.data, "%g", atof(tmp1.data) / atof(tmp2.data));
		}
	}
	else if (ast->type == TSCRIPT_AST_MOD)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			res.type = TSCRIPT_TYPE_NUMBER;
			asprintf(&res.data, "%g", (double)((int)atof(tmp1.data) % (int)atof(tmp2.data)));
		}
	}
	else if (ast->type == TSCRIPT_AST_IF)
	{
		if (atof(tscript_value_dereference(tscript_interprete_sub(ast->children[0])).data))
			res = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		else if(ast->children[2] != NULL)
		{
			res = tscript_value_dereference(tscript_interprete_sub(ast->children[2]));
		}
		else
		{
			res.type = TSCRIPT_TYPE_STRING;
			res.data = (char*)malloc(1);
			res.data[0] = 0;
		}
	}
	else if (ast->type == TSCRIPT_AST_FOR)
	{
		tscript_interprete_sub(ast->children[0]);
		res = tscript_value_create(TSCRIPT_TYPE_STRING, "");
		while (atof(tscript_value_dereference(tscript_interprete_sub(ast->children[1])).data))
		{
			tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[3]));
			res.data = (char*)realloc(res.data, strlen(res.data) + strlen(tmp1.data) + 1);
			strcat(res.data, tmp1.data);
			tscript_interprete_sub(ast->children[2]);
		}
	}
	else if (ast->type == TSCRIPT_AST_SEQ)
	{
		tscript_debug("Interpretting TSCRIPT_AST_SEQ\n");
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			tmp1 = tscript_value_convert_to_string(tmp1);
			tmp2 = tscript_value_convert_to_string(tmp2);
			res.type = TSCRIPT_TYPE_STRING;
			res.data = (char*)malloc(strlen(tmp1.data) + strlen(tmp2.data) + 1);
			strcpy(res.data, tmp1.data);
			strcat(res.data, tmp2.data);
			tscript_debug("Interpreted TSCRIPT_AST_SEQ\n");
		}
	}
	else if (ast->type == TSCRIPT_AST_CONV)
	{
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (ast->value.type == TSCRIPT_TYPE_STRING)
			res = tscript_value_convert_to_string(tmp1);
		else if (ast->value.type == TSCRIPT_TYPE_NUMBER)
			res = tscript_value_convert_to_number(tmp1);
		else
			tscript_internal_error("Incorrect conversion");
	}
	else if (ast->type == TSCRIPT_AST_EXT)
	{
		tscript_debug("Interpretting TSCRIPT_AST_EXT\n");
		tmp1 = tscript_value_dereference(tscript_interprete_sub(ast->children[0]));
		tmp2 = tscript_value_dereference(tscript_interprete_sub(ast->children[1]));
		if (tmp1.type == TSCRIPT_TYPE_ERROR)
			res = tmp1;
		else if (tmp2.type == TSCRIPT_TYPE_ERROR)
			res = tmp2;
		else
		{
			tscript_debug("Extension name: %s\n", tscript_value_convert_to_string(tmp1).data);
			tscript_debug("Extension param: %s\n", tscript_value_convert_to_string(tmp2).data);
			res = tscript_run_extension(tmp1.data, tmp2);
			tscript_debug("Interpreted TSCRIPT_AST_EXT\n");
		}
	}
	else
		tscript_internal_error("Internal error: incorrect node type!\n");
	return res;
}

tscript_value tscript_interprete()
{
	return tscript_interprete_sub(ast);
}
