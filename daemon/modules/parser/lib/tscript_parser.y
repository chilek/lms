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

	// http://lists.gnu.org/archive/html/bug-bison/2003-04/msg00045.html
	#define YYLEX_PARAM context

	int i;
%}

%error-verbose
%locations
%parse-param { tscript_context* context }
%lex-param { tscript_context* context }

%nonassoc ERROR IF ELSE END_IF FOR END_FOR
%left OR AND
%left EQUALS '<' '>' EQUALS_LESS EQUALS_GREATER DIFFERS
%left '!'
%left '+' '-'
%left NEG
%left '*' '/' '%' '&' '|'
%nonassoc MATCH
%nonassoc INC DEC
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
				if (strcmp($1->value->data, $6->value->data) != 0)
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
				if (strcmp($1->value->data, $4->value->data) != 0)
				{
					yyerror(context,
						"block extension begin and end mismatch");
					YYERROR;
				}
				$$ = tscript_ast_node_2(TSCRIPT_AST_EXT, $1, $3);
			}
		|	BLOCK expressions '}' statements END_BLOCK
			{
				if (strcmp($1->value->data, $5->value->data) != 0)
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

postfix_expression:	primary_expression
		|	sub_variable
		|	chng_operator
		|	call_expression

unary_expression:	postfix_expression
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
		|	unary_expression '*' unary_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_MUL, $1, $3);
			}
		|	unary_expression '/' unary_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_DIV, $1, $3);
			}
		|	unary_expression '%' unary_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_MOD, $1, $3);
			}

additive_expression:
			multiplicative_expression
		|	multiplicative_expression '+' multiplicative_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_PLUS, $1, $3);
			}
		|	multiplicative_expression '-' multiplicative_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_MINUS, $1, $3);
			}

relational_expression:
			additive_expression
		|	additive_expression '<' additive_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_LESS, $1, $3);
			}
		|	additive_expression '>' additive_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_GREATER, $1, $3);
			}
		|	additive_expression EQUALS_LESS additive_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_EQUALS_LESS, $1, $3);
			}
		|	additive_expression EQUALS_GREATER additive_expression
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
		|	equality_expression OR equality_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_OR, $1, $3);
			}
		|	equality_expression AND equality_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_AND, $1, $3);
			}
		|	equality_expression '&' equality_expression
			{
				$$ = tscript_ast_node_2(TSCRIPT_AST_BAND, $1, $3);
			}
		|	equality_expression '|' equality_expression
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

iteration_statement:	FOR '(' statement ';' expression ';' statement ')' statements END_FOR
			{
				$$ = tscript_ast_node_4(TSCRIPT_AST_FOR, $3, $5, $7, $9);
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
	|	iteration_statement
	|	selection_statement
	|	expression

statements:	statements statement
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SEQ, $1, $2);
		}
	|	statement
		{
			$$ = $1;
		}

template: 	statements
		{
			context->ast = $1;
		}

%%

