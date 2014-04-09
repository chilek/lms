pkglib_LIBRARIES = libtscript.a


BUILT_SOURCES = tscript_parser.h tscript_lexical.c
AM_YFLAGS = -d -p tscript_yy -r lookahead
AM_LFLAGS = -o$(LEX_OUTPUT_ROOT).c -Ptscript_yy 


libtscript_a_SOURCES = tscript_parser.y tscript_ast.c tscript_context.c tscript_compiler.c tscript_interpreter.c tscript_variables.c tscript_values.c tscript_extensions.c tscript_debug.c  tscript_lexical.l 
