#ifndef TSCRIPT_CONTEXT
#define TSCRIPT_CONTEXT

#include <stdarg.h>
#include "tscript_values.h"
#include "tscript_variables_private.h"
#include "tscript_extensions_private.h"

struct tscript_ast_node;
typedef void tscript_debug_callback(const char* format, va_list ap);

typedef struct tscript_context
{
	struct tscript_ast_node* ast;
	tscript_variables_map* vars;
	tscript_extension_map* extensions;
	tscript_constant_map* constants;
	char* error;
	tscript_debug_callback* debug_callback;
} tscript_context;

#include "tscript_ast.h"

/**
	Creates new independend context with byte-code space and data space,
	list of connected extensions and so on.
**/
tscript_context* tscript_context_create();

/**
	Deletes specified context and frees all allocated resources.
	Pointed structure is also destroyed.
**/
void tscript_context_free(tscript_context* context);

#endif
