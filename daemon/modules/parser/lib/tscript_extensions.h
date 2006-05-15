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
tscript_value* tscript_run_extension(
	tscript_context* context, char* keyword, tscript_value* args);

/**
	Helper function for arguments array handling in extensions.
	Returns n-th argument, or NULL (null pointer not null value)
	if there is no n-th argument.
	You shouldn't free returned value.
**/
tscript_value* tscript_extension_arg(tscript_value* args, int n);

void tscript_add_constant(tscript_context* context, char* keyword, tscript_constant_func* func);
void tscript_remove_constant(tscript_context* context, char* keyword);
int tscript_has_constant(tscript_context* context, char* keyword);
tscript_value* tscript_run_constant(tscript_context* context, char* keyword);

#endif
