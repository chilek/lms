/*

T-Script - Lexical Analizer
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
	
	#define YY_BREAK set_yylloc(); break;
	#define YY_RETURN(res) { set_yylloc(); return res; }
%}

%option stack
%option noyywrap

%x commands ext_arg

%%

\{					{
						state_stack_level++;
						yy_push_state(commands);
					}

[^{}]+					{
						tscript_yylval = (tscript_ast_node*)malloc(sizeof(tscript_ast_node));
						tscript_yylval->type = TSCRIPT_AST_VALUE;
						tscript_yylval->value = tscript_value_create(TSCRIPT_TYPE_STRING, yytext);
						tscript_yylval->children = NULL;
						YY_RETURN(TEXT);
					}

<commands>\}|\}\\\n			{
						if (state_stack_level < 1)
							YY_RETURN(ERROR);
						state_stack_level--;
						yy_pop_state();
					}

\}|\}\\\n				{ // hack for end of ext param
						if (state_stack_level < 1)
							YY_RETURN(ERROR);
						state_stack_level -= 2;
						yy_pop_state();
						yy_pop_state();
						YY_RETURN('}');
					}

<commands>\"[^"]*\"			{
						char* tmp_str;
						tscript_yylval = (tscript_ast_node*)malloc(sizeof(tscript_ast_node));
						tscript_yylval->type = TSCRIPT_AST_VALUE;
						tmp_str = (char*)malloc(strlen(yytext) - 2 + 1);
						strncpy(tmp_str, &yytext[1], strlen(yytext) - 2);
						tmp_str[strlen(yytext) - 2] = 0;
						tscript_yylval->value = tscript_value_create(TSCRIPT_TYPE_STRING, tmp_str);
						free(tmp_str);
						tscript_yylval->children = NULL;
						YY_RETURN(LITERAL);
					}

<commands>string			YY_RETURN(TO_STRING);

<commands>number			YY_RETURN(TO_NUMBER);

<commands>for				YY_RETURN(FOR);

<commands>\/for				YY_RETURN(END_FOR);

<commands>if				YY_RETURN(IF);

<commands>else				YY_RETURN(ELSE);

<commands>\/if				YY_RETURN(END_IF);

<commands>file				YY_RETURN(WFILE);

<commands>\/file			YY_RETURN(END_WFILE);

<commands>==				YY_RETURN(EQUALS);

<commands>!=				YY_RETURN(DIFFERS);

<commands>\<=				YY_RETURN(EQUALS_LESS);

<commands>\>=				YY_RETURN(EQUALS_GREATER);

<commands>\|\|				YY_RETURN(OR);

<commands>&&				YY_RETURN(AND);

<commands>\+\+				YY_RETURN(INC);

<commands>--				YY_RETURN(DEC);

<commands>=~				YY_RETURN(MATCH);

<commands>\/\*([^\*]|[\r\n]|(\*+([^\*\/]|[\r\n])))*\*+\/	/* C-style comments */

<commands>[[:digit:]]+(\.[[:digit:]]+)?	{
						tscript_yylval = (tscript_ast_node*)malloc(sizeof(tscript_ast_node));
						tscript_yylval->type = TSCRIPT_AST_VALUE;
						tscript_yylval->value = tscript_value_create(TSCRIPT_TYPE_NUMBER, yytext);
						tscript_yylval->children = NULL;
						YY_RETURN(NUMBER);
					}

<commands>[[:alpha:]][[:alnum:]_]*	{
						tscript_yylval = (tscript_ast_node*)malloc(sizeof(tscript_ast_node));
						tscript_yylval->type = TSCRIPT_AST_VALUE;
						tscript_yylval->value = tscript_value_create(TSCRIPT_TYPE_STRING, yytext);
						tscript_yylval->children = NULL;
						if (tscript_has_extension(yytext))
						{
							state_stack_level++;
							yy_push_state(ext_arg);
							YY_RETURN(EXT);
						}
						if (tscript_has_constant(yytext))
							YY_RETURN(CONST);
						YY_RETURN(NAME);
					}

<commands>[[:space:]]+

<commands>.				YY_RETURN(*yytext);

<ext_arg>\(				{
						yy_pop_state();
						YY_RETURN('(');
					}

<ext_arg>[[:space:]]+

<ext_arg>.				{
						BEGIN(INITIAL);
						yyless(0);
					}	

%%

void* tscript_yy_setup_scanner(const char *s)
{
	return yy_scan_string(s);
}
		
void tscript_yy_cleanup_scanner(void* ptr)
{
	yy_delete_buffer( (YY_BUFFER_STATE) ptr );
}
