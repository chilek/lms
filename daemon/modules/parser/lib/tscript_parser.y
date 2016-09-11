/****************************************************************************
**
** T-Script - Parser
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

%{
	#include <stdlib.h>
	#include <stdio.h>
	#include "tscript_ast.h"

	#define YYSTYPE tscript_ast_node*

	// http://lists.gnu.org/archive/html/bug-bison/2003-04/msg00045.html
	#define YYLEX_PARAM context

	int i;
%}

%error-verbose
%locations
%parse-param { tscript_context* context }
%lex-param { tscript_context* context }

%nonassoc ';'
%nonassoc ERROR IF ELSE END_IF FOR END_FOR FOREACH END_FOREACH IN WHILE END_WHILE
%nonassoc BREAK EXIT CONTINUE
%left OR AND
%left EQUALS '<' '>' EQUALS_LESS EQUALS_GREATER DIFFERS
%left '!'
%left '+' '-'
%left NEG
%left '*' '/' '%' '&' '|'
%nonassoc MATCH
%nonassoc INC DEC LEFT RIGHT
%nonassoc '.'
%nonassoc '['
%nonassoc EXT BLOCK END_BLOCK CONST
%nonassoc LITERAL NUMBER TEXT NAME NULL_CONST TO_STRING TO_NUMBER TYPEOF

%start template

%%

value:		TEXT
	|	LITERAL
	|	NUMBER
	|	NULL_CONST
		{
			$$ = tscript_ast_node_val(TSCRIPT_AST_VALUE,
				tscript_value_create_null());
		}

type_conv:	TO_STRING '(' expression ')'
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_CONV, $3);
			$$->value = tscript_value_create_null();
			$$->value->type = TSCRIPT_TYPE_STRING;
		}
	|	TO_NUMBER '(' expression ')'
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_CONV, $3);
			$$->value = tscript_value_create_null();
			$$->value->type = TSCRIPT_TYPE_NUMBER;
		}

type_of:	TYPEOF '(' expression ')'
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_TYPEOF, $3);
		}

type_operator:	type_conv
	|	type_of

primary_expression:	value
		|	'(' expression ')'
			{
				$$ = $2;
			}

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

chng_operator:	reference
	|	reference INC
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_INC, $1);
		}
	|	reference DEC
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_DEC, $1);
		}

sub_variable:		postfix_expression '[' expression ']'
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_INDEX, $1, $3);
			}
		|	postfix_expression '.' NAME
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_SUBVAR, $1, $3);
			}

argument_expression_list:
			logical_expression
			{
				$$ = tscript_ast_node_1(TSCRIPT_AST_ARGS, $1);
			}
		|	argument_expression_list ',' logical_expression
			{
				tscript_ast_node_add_child($1, $3);
				$$ = $1;
			}

call_expression:	BLOCK '(' argument_expression_list ')' statements END_BLOCK
			{
				if (strcmp(tscript_value_as_string($1->value),
					tscript_value_as_string($6->value)) != 0)
				{
					yyerror(context,
						"block extension begin and end mismatch");
					YYERROR;
				}
				tscript_ast_node_add_child($3, $5);
				$$ = tscript_ast_node_2(TSCRIPT_AST_EXT, $1, $3);
			}
		|	BLOCK '}' statements END_BLOCK
			{
				if (strcmp(tscript_value_as_string($1->value),
					tscript_value_as_string($4->value)) != 0)
				{
					yyerror(context,
						"block extension begin and end mismatch");
					YYERROR;
				}
				$$ = tscript_ast_node_2(TSCRIPT_AST_EXT, $1, $3);
			}
		|	BLOCK expressions '}' statements END_BLOCK
			{
				if (strcmp(tscript_value_as_string($1->value),
					tscript_value_as_string($5->value)) != 0)
				{
					yyerror(context,
						"block extension begin and end mismatch");
					YYERROR;
				}
				$$ = tscript_ast_node_1(TSCRIPT_AST_ARGS, $2);
				tscript_ast_node_add_child($$, $4);
				$$ = tscript_ast_node_2(TSCRIPT_AST_EXT, $1, $$);
			}
		|	CONST
			{
				$$ = tscript_ast_node_1(TSCRIPT_AST_CONST, $1);
			}
		|	EXT '(' argument_expression_list ')'
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_EXT, $1, $3);
			}
		|	EXT '(' ')'
			{
				$$ = tscript_ast_node_1(TSCRIPT_AST_EXT, $1);
			}
		|	EXT expressions '}'
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_EXT, $1, $2);
			}
		|	EXT expressions
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_EXT, $1, $2);
			}

postfix_expression:	primary_expression
		|	sub_variable
		|	chng_operator
		|	call_expression

unary_expression:	postfix_expression
		|	INC reference
			{
				$$ = tscript_ast_node_1(TSCRIPT_AST_UN_INC, $2);
			}
		|	DEC reference
			{
				$$ = tscript_ast_node_1(TSCRIPT_AST_UN_DEC, $2);
			}
		|	type_operator
		|	'-' postfix_expression %prec NEG
			{
				$$ = tscript_ast_node_1(TSCRIPT_AST_NEG, $2);
			}
		|	'!' postfix_expression
			{
				$$ = tscript_ast_node_1(TSCRIPT_AST_NOT, $2);
			}

multiplicative_expression:
			unary_expression
		|	multiplicative_expression '*' unary_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_MUL, $1, $3);
			}
		|	multiplicative_expression '/' unary_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_DIV, $1, $3);
			}
		|	unary_expression '%' unary_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_MOD, $1, $3);
			}

additive_expression:
			multiplicative_expression
		|	additive_expression '+' multiplicative_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_PLUS, $1, $3);
			}
		|	additive_expression '-' multiplicative_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_MINUS, $1, $3);
			}

shift_expression:
			additive_expression
		|	additive_expression LEFT additive_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_LEFT, $1, $3);
			}
		|	additive_expression RIGHT additive_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_RIGHT, $1, $3);
			}

relational_expression:
			shift_expression
		|	shift_expression '<' shift_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_LESS, $1, $3);
			}
		|	shift_expression '>' shift_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_GREATER, $1, $3);
			}
		|	shift_expression EQUALS_LESS shift_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_EQUALS_LESS, $1, $3);
			}
		|	shift_expression EQUALS_GREATER shift_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_EQUALS_GREATER, $1, $3);
			}

equality_expression:
			relational_expression
		|	relational_expression EQUALS relational_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_EQUALS, $1, $3);
			}
		|	relational_expression DIFFERS relational_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_DIFFERS, $1, $3);
			}
		|	relational_expression MATCH relational_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_MATCH, $1, $3);
			}

logical_expression:
			equality_expression
		|	logical_expression OR equality_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_OR, $1, $3);
			}
		|	logical_expression AND equality_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_AND, $1, $3);
			}
		|	logical_expression '&' equality_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_BAND, $1, $3);
			}
		|	logical_expression '|' equality_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_BOR, $1, $3);
			}

expression:	logical_expression

expressions:	expressions expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SEQ, $1, $2);
		}
	|	expression
		{
			$$ = $1;
		}

assignment_statement:	reference '=' expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_VAR_SET, $1, $3);
			}

jump_statement:		BREAK
			{
				$$ = tscript_ast_node_0(TSCRIPT_AST_BREAK);
			}
		|	EXIT
			{
				$$ = tscript_ast_node_0(TSCRIPT_AST_EXIT);
			}
		|	CONTINUE
			{
				$$ = tscript_ast_node_0(TSCRIPT_AST_CONTINUE);
			}

iteration_statement:	WHILE '(' expression ')' statements END_WHILE
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_WHILE, $3, $5);
			}
		|	FOR '(' statement ';' expression ';' statement ')' statements END_FOR
			{
				$$ = tscript_ast_node_4(TSCRIPT_AST_FOR, $3, $5, $7, $9);
			}
		|	FOREACH '(' reference IN expression ')' statements END_FOREACH
			{
				$$ = tscript_ast_node_3(TSCRIPT_AST_FOREACH, $3, $5, $7);
			}

selection_statement:	IF '(' expression ')' statements END_IF
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_IF, $3, $5);
			}
		|	IF '(' expression ')' statements ELSE statements END_IF
			{
				$$ = tscript_ast_node_3(TSCRIPT_AST_IF, $3, $5, $7);
			}

statement:	assignment_statement
	|	jump_statement
	|	iteration_statement
	|	selection_statement
	|	expression

statements:	statements statement
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SEQ, $1, $2);
		}
	|	statements ';' statement
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SEQ, $1, $3);
		}
	|	statements ';'
	|	statement
		{
			$$ = $1;
		}
	|	statement ';'

template: 	statements
		{
			context->ast = $1;
		}

%%

