#ifndef TSCRIPT_EXTENSIONS_H
#define TSCRIPT_EXTENSIONS_H

#include "tscript_extensions_private.h"
#include "tscript_context.h"
#include "tscript_values.h"

/**
	Adds new extension.
	min_args - minimum numer of arguments (0..MAX_INT)
	max_args - maximum numer of arguments (0..MAX_INT), -1 = no limit
**/
void tscript_add_extension(tscript_context* context, char* keyword, tscript_extension_func* func,
	int min_args, int max_args);

void tscript_remove_extension(tscript_context* context, char* keyword);
void tscript_extension_set_block(tscript_context* context, char* keyword);
int tscript_extension_is_block(tscript_context* context, char* keyword);
int tscript_has_extension(tscript_context* context, char* keyword);
tscript_value* tscript_run_extension(tscript_context* context, char* keyword, tscript_value* arg);

void tscript_add_constant(tscript_context* context, char* keyword, tscript_constant_func* func);
void tscript_remove_constant(tscript_context* context, char* keyword);
int tscript_has_constant(tscript_context* context, char* keyword);
tscript_value* tscript_run_constant(tscript_context* context, char* keyword);

#endif
