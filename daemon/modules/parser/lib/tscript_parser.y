/*

T-Script - Parser
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

%{
	#include <stdlib.h>
	#include "tscript_ast.h"

	#define YYSTYPE tscript_ast_node*

	int i;
%}

%error-verbose
%locations

%nonassoc ERROR IF ELSE END_IF FOR END_FOR
%left OR AND
%left EQUALS '<' '>' EQUALS_LESS EQUALS_GREATER DIFFERS
%left '!'
%left '+' '-'
%left NEG
%left '*' '/' '%'
%nonassoc INC DEC
%nonassoc EXT
%nonassoc LITERAL NUMBER TEXT NAME TO_STRING TO_NUMBER

%%

template: 	commands
		{
			ast = $1;
		}

commands:	commands command
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SEQ, $1, $2);
		}
	|	command
		{
			$$ = $1;
		}

command:	statement
	|	expression

statement:	set_stmt
	|	for_stmt
	|	if_stmt

set_stmt:	reference '=' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_VAR_SET, $1, $3);
		}

for_stmt:	FOR '(' command ';' expression ';' command ')' commands END_FOR
		{
			$$ = tscript_ast_node_4(TSCRIPT_AST_FOR, $3, $5, $7, $9);
		}

if_stmt:	IF '(' expression ')' commands END_IF
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_IF, $3, $5);
		}
	|	IF '(' expression ')' commands ELSE commands END_IF
		{
			$$ = tscript_ast_node_3(TSCRIPT_AST_IF, $3, $5, $7);
		}

expressions:	expressions expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SEQ, $1, $2);
		}
	|	expression
		{
			$$ = $1;
		}

expression:	TEXT
	|	LITERAL
	|	NUMBER
	|	EXT expressions '}'
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_EXT, $1, $2);
		}
	|	'-' expression %prec NEG
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_NEG, $2);
		}
	|	'!' expression
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_NOT, $2);
		}
	|	'(' expression ')'
		{
			$$ = $2;
		}
	|	expression EQUALS expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_EQUALS, $1, $3);
		}
	|	expression DIFFERS expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_DIFFERS, $1, $3);
		}
	|	expression '<' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_LESS, $1, $3);
		}
	|	expression '>' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_GREATER, $1, $3);
		}
	|	expression EQUALS_LESS expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_EQUALS_LESS, $1, $3);
		}
	|	expression EQUALS_GREATER expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_EQUALS_GREATER, $1, $3);
		}
	|	expression OR expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_OR, $1, $3);
		}
	|	expression AND expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_AND, $1, $3);
		}
	|	expression '+' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_PLUS, $1, $3);
		}
	|	expression '-' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_MINUS, $1, $3);
		}
	|	expression '*' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_MUL, $1, $3);
		}
	|	expression '/' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_DIV, $1, $3);
		}
	|	expression '%' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_MOD, $1, $3);
		}
	|	reference INC
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_INC, $1);
		}
	|	reference DEC
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_DEC, $1);
		}
	|	reference
	|	type_conv

reference:	NAME
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_VAR_GET, $1);
		}
	|	reference '[' expression ']'
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_INDEX, $1, $3);
		}
	|	reference '.' NAME
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SUBVAR, $1, $3);
		}

type_conv:	TO_STRING '(' expression ')'
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_CONV, $3);
			$$->value.type = TSCRIPT_TYPE_STRING;
		}
	|	TO_NUMBER '(' expression ')'
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_CONV, $3);
			$$->value.type = TSCRIPT_TYPE_NUMBER;
		}

%%

