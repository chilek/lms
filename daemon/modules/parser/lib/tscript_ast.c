/****************************************************************************
**
** T-Script - Abstract Syntax Tree
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

#include "tscript_ast.h"
#include <stdio.h>

const char* TSCRIPT_AST_VALUE = "VALUE";
const char* TSCRIPT_AST_VAR_SET = "=";
const char* TSCRIPT_AST_VAR_GET = "VAR_GET";
const char* TSCRIPT_AST_INDEX = "[]";
const char* TSCRIPT_AST_SUBVAR = ".";
const char* TSCRIPT_AST_EQUALS = "==";
const char* TSCRIPT_AST_DIFFERS = "!=";
const char* TSCRIPT_AST_LESS = "<";
const char* TSCRIPT_AST_GREATER = ">";
const char* TSCRIPT_AST_EQUALS_LESS = "<=";
const char* TSCRIPT_AST_EQUALS_GREATER = ">=";
const char* TSCRIPT_AST_NOT = "!";
const char* TSCRIPT_AST_NEG = "NEG";
const char* TSCRIPT_AST_OR = "||";
const char* TSCRIPT_AST_AND = "&&";
const char* TSCRIPT_AST_BOR = "|";
const char* TSCRIPT_AST_BAND = "&";
const char* TSCRIPT_AST_PLUS = "+";
const char* TSCRIPT_AST_MINUS = "-";
const char* TSCRIPT_AST_MUL = "*";
const char* TSCRIPT_AST_DIV = "/";
const char* TSCRIPT_AST_MOD = "%";
const char* TSCRIPT_AST_INC = "x++";
const char* TSCRIPT_AST_DEC = "x--";
const char* TSCRIPT_AST_UN_INC = "++x";
const char* TSCRIPT_AST_UN_DEC = "--x";
const char* TSCRIPT_AST_LEFT = "<<";
const char* TSCRIPT_AST_RIGHT = ">>";
const char* TSCRIPT_AST_MATCH = "=~";
const char* TSCRIPT_AST_IF = "IF";
const char* TSCRIPT_AST_WHILE = "WHILE";
const char* TSCRIPT_AST_FOR = "FOR";
const char* TSCRIPT_AST_FOREACH = "FOREACH";
const char* TSCRIPT_AST_SEQ = "SEQ";
const char* TSCRIPT_AST_ARGS = "ARGS";
const char* TSCRIPT_AST_CONV = "CONV";
const char* TSCRIPT_AST_TYPEOF = "TYPEOF";
const char* TSCRIPT_AST_EXT = "EXT";
const char* TSCRIPT_AST_CONST = "CONST";
const char* TSCRIPT_AST_BREAK = "BREAK";
const char* TSCRIPT_AST_EXIT = "EXIT";
const char* TSCRIPT_AST_CONTINUE = "CONTINUE";

tscript_ast_node* tscript_ast_node_val(const char* type, tscript_value* val)
{
	tscript_ast_node* n = (tscript_ast_node*)malloc(sizeof(tscript_ast_node));
	n->type = type;
	n->value = val;
	n->children = NULL;
	return n;
}

tscript_ast_node* tscript_ast_node_0(const char* type)
{
	tscript_ast_node* n = (tscript_ast_node*)malloc(sizeof(tscript_ast_node));
	n->type = type;
	n->value = tscript_value_create_null();
	n->children = NULL;
	return n;
}

tscript_ast_node* tscript_ast_node_1(const char* type, tscript_ast_node* child)
{
	tscript_ast_node* n = (tscript_ast_node*)malloc(sizeof(tscript_ast_node));
	n->type = type;
	n->value = tscript_value_create_null();
	n->children = (tscript_ast_node**)malloc(2 * sizeof(tscript_ast_node*));
	n->children[0] = child;
	n->children[1] = NULL;
	return n;
}

tscript_ast_node* tscript_ast_node_2(const char* type, tscript_ast_node* child1, tscript_ast_node* child2)
{
	tscript_ast_node* n = (tscript_ast_node*)malloc(sizeof(tscript_ast_node));
	n->type = type;
	n->value = tscript_value_create_null();
	n->children = (tscript_ast_node**)malloc(3 * sizeof(tscript_ast_node*));
	n->children[0] = child1;
	n->children[1] = child2;
	n->children[2] = NULL;
	return n;
}

tscript_ast_node* tscript_ast_node_3(const char* type, tscript_ast_node* child1, tscript_ast_node* child2,
	tscript_ast_node* child3)
{
	tscript_ast_node* n = (tscript_ast_node*)malloc(sizeof(tscript_ast_node));
	n->type = type;
	n->value = tscript_value_create_null();
	n->children = (tscript_ast_node**)malloc(4 * sizeof(tscript_ast_node*));
	n->children[0] = child1;
	n->children[1] = child2;
	n->children[2] = child3;
	n->children[3] = NULL;
	return n;
}

tscript_ast_node* tscript_ast_node_4(const char* type, tscript_ast_node* child1, tscript_ast_node* child2,
	tscript_ast_node* child3, tscript_ast_node* child4)
{
	tscript_ast_node* n = (tscript_ast_node*)malloc(sizeof(tscript_ast_node));
	n->type = type;
	n->value = tscript_value_create_null();
	n->children = (tscript_ast_node**)malloc(5 * sizeof(tscript_ast_node*));
	n->children[0] = child1;
	n->children[1] = child2;
	n->children[2] = child3;
	n->children[3] = child4;
	n->children[4] = NULL;
	return n;
}

void tscript_ast_node_add_child(tscript_ast_node* node, tscript_ast_node* child)
{
	int i;
	for (i = 0; node->children != NULL && node->children[i] != NULL; i++)
	{
	}
	node->children = (tscript_ast_node**)realloc(node->children,
		(i + 2) * sizeof(tscript_ast_node*));
	node->children[i] = child;
	node->children[i + 1] = NULL;
}

static void tscript_print_ast_sub(tscript_ast_node* ast, int indent)
{
	int i;
	for (i = 0; i < indent; i++)
		printf(" ");
	printf("%s", ast->type);
	if (ast->value->type != TSCRIPT_TYPE_NULL)
		printf(": %s", tscript_value_as_string(ast->value));
	printf("\n");
	if (ast->children != NULL)
		for (i = 0; ast->children[i] != NULL; i++)
			tscript_print_ast_sub(ast->children[i], indent + 1);
}

void tscript_ast_node_free(tscript_ast_node* node)
{
	int i;
	if (node->children != NULL)
	{
		for (i = 0; node->children[i] != NULL; i++)
			tscript_ast_node_free(node->children[i]);
		free(node->children);
	}
	tscript_value_free(node->value);
	free(node);
}

void tscript_print_ast(tscript_context* context)
{
	tscript_print_ast_sub(context->ast, 0);
}
