/****************************************************************************
**
** T-Script - Lexical Analizer
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
	#define __USE_GNU
	#include <string.h>
	
	#include "tscript_ast.h"
	#define YYSTYPE tscript_ast_node*
	#include "tscript_parser.h"
	
	static int col;
	static int line;
	static int state_stack_level;
	
	void tscript_init_lexical()
	{
		col = 0;
		line = 1;
		state_stack_level = 0;
	}

	void set_yylloc()
	{
		int i;
		tscript_yylloc.first_column = col;
		tscript_yylloc.first_line = line;
		for (i = 0; yytext[i] != 0; i++)
		{
			tscript_yylloc.last_column = col;
			tscript_yylloc.last_line = line;
			if (yytext[i] == '\n')
			{
				col = 0;
				line++;
			}
			else
				col++;
		}
/*		fprintf(stderr, "TOKEN: %ix%i - %ix%i\n",
			yylloc.first_column,
			yylloc.first_line,
			yylloc.ltscript_ast_column,
			yylloc.ltscript_ast_line);*/
	}

	#define YY_DECL int tscript_yylex(tscript_context* context)
	#define YY_BREAK set_yylloc(); break;
	#define YY_RETURN(res) { set_yylloc(); return res; }

	static char* tscript_unescape_text(const char* text)
	{
		char* res;
		int i, j;
		res = (char*)malloc(strlen(text) + 1);
		for (i = 0, j = 0; text[i] != '\0'; i++, j++)
			if (text[i] == '\\')
			{
				i++;
				switch (text[i])
				{
					case 't':
						res[j] = '\t';
						break;
					default:
						res[j] = text[i];
				}
			}
			else
				res[j] = text[i];
		res[j] = '\0';
		return res;
	}

	static char* tscript_unescape_literal(const char* literal)
	{
		char* res;
		int i, j;
		res = (char*)malloc(strlen(literal) - 2 + 1);
		for (i = 1, j = 0; literal[i] != '"'; i++, j++)
			if (literal[i] == '\\')
			{
				i++;
				switch (literal[i])
				{
					case 'n':
						res[j] = '\n';
						break;
					case 'r':
						res[j] = '\r';
						break;
					case 't':
						res[j] = '\t';
						break;
					default:
						res[j] = literal[i];
				}
			}
			else
				res[j] = literal[i];
		res[j] = '\0';
		return res;
	}

%}

%option stack
%option noyywrap

%x commands ext_arg ext_arg_2

%%

<INITIAL,ext_arg,ext_arg_2>\{		{
						state_stack_level++;
						yy_push_state(commands);
					}

^\t+

([^{}\t]|\\[{}])+			{
						char* unescaped = tscript_unescape_text(yytext);
						tscript_yylval = tscript_ast_node_val(
							TSCRIPT_AST_VALUE,
							tscript_value_create_string(unescaped));
						free(unescaped);
						YY_RETURN(TEXT);
					}

<ext_arg>\(				{
						yy_pop_state();
						YY_RETURN('(');
					}

<ext_arg>[[:space:]]+

<ext_arg>.				{
						BEGIN(ext_arg_2);	
						yyless(0);
					}

<ext_arg_2>([^{};]|\\[{};])+		{
						char* unescaped = tscript_unescape_text(yytext);
						tscript_yylval = tscript_ast_node_val(
							TSCRIPT_AST_VALUE,
							tscript_value_create_string(unescaped));
						free(unescaped);
						YY_RETURN(TEXT);
					}

<ext_arg_2>;				{
						if (state_stack_level < 1)
							YY_RETURN(ERROR);
						state_stack_level -= 1;
						yy_pop_state();
						YY_RETURN(';');
					}

<ext_arg_2>\}|\}\\\n			{
						if (state_stack_level < 2)
							YY_RETURN(ERROR);
						state_stack_level -= 2;
						yy_pop_state();
						yy_pop_state();
						YY_RETURN('}');
					}

<commands>\}|\}\\\n			{
						if (state_stack_level < 1)
							YY_RETURN(ERROR);
						state_stack_level--;
						yy_pop_state();
					}

<commands>\"([^"\\]|\\[rnt"\\])*\"	{
						char* unescaped = tscript_unescape_literal(yytext);
						tscript_yylval = tscript_ast_node_val(
							TSCRIPT_AST_VALUE,
							tscript_value_create_string(unescaped));
						free(unescaped);
						YY_RETURN(LITERAL);
					}

<commands>string			YY_RETURN(TO_STRING);

<commands>number			YY_RETURN(TO_NUMBER);

<commands>null				YY_RETURN(NULL_CONST);

<commands>typeof			YY_RETURN(TYPEOF);

<commands>while				YY_RETURN(WHILE);

<commands>\/while			YY_RETURN(END_WHILE);

<commands>for				YY_RETURN(FOR);

<commands>\/for				YY_RETURN(END_FOR);

<commands>foreach			YY_RETURN(FOREACH);

<commands>\/foreach			YY_RETURN(END_FOREACH);

<commands>in				YY_RETURN(IN);

<commands>if				YY_RETURN(IF);

<commands>else				YY_RETURN(ELSE);

<commands>\/if				YY_RETURN(END_IF);

<commands>break				YY_RETURN(BREAK);

<commands>exit				YY_RETURN(EXIT);

<commands>continue			YY_RETURN(CONTINUE);

<commands>==				YY_RETURN(EQUALS);

<commands>!=				YY_RETURN(DIFFERS);

<commands>\<=				YY_RETURN(EQUALS_LESS);

<commands>\>=				YY_RETURN(EQUALS_GREATER);

<commands>\|\|				YY_RETURN(OR);

<commands>&&				YY_RETURN(AND);

<commands>\+\+				YY_RETURN(INC);

<commands>--				YY_RETURN(DEC);

<commands>\<\<				YY_RETURN(LEFT);

<commands>\>\>				YY_RETURN(RIGHT);

<commands>=~				YY_RETURN(MATCH);

<commands>\/\*([^\*]|[\r\n]|(\*+([^\*\/]|[\r\n])))*\*+\/	/* C-style comments */

<commands>[[:digit:]]+(\.[[:digit:]]+)?	{
						tscript_yylval = tscript_ast_node_val(
							TSCRIPT_AST_VALUE,
							tscript_value_create_number(atof(yytext)));
						YY_RETURN(NUMBER);
					}

<commands>[[:alpha:]][[:alnum:]_]*	{
						tscript_yylval = tscript_ast_node_val(
							TSCRIPT_AST_VALUE,
							tscript_value_create_string(yytext));
						if (tscript_has_extension(context, yytext))
						{
							state_stack_level++;
							yy_push_state(ext_arg);
							if (tscript_extension_is_block(context,
								yytext))
								YY_RETURN(BLOCK);
							YY_RETURN(EXT);
						}
						if (tscript_has_constant(context, yytext))
							YY_RETURN(CONST);
						YY_RETURN(NAME);
					}

<commands>\/[[:alpha:]][[:alnum:]_]*	{
						if (tscript_has_extension(context, &yytext[1]) &&
							tscript_extension_is_block(context,
								&yytext[1]))
						{
							tscript_yylval = tscript_ast_node_val(
								TSCRIPT_AST_VALUE,
								tscript_value_create_string(
									&yytext[1]));
							YY_RETURN(END_BLOCK);
						}
						yyless(0);
					}

<commands>[[:space:]]+

<commands>.				YY_RETURN(*yytext);

%%

void* tscript_yy_setup_scanner(const char *s)
{
	return yy_scan_string(s);
}
		
void tscript_yy_cleanup_scanner(void* ptr)
{
	yy_delete_buffer( (YY_BUFFER_STATE) ptr );
}
