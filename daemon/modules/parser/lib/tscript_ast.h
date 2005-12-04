#ifndef TSCRIPT_AST_H
#define TSCRIPT_AST_H

#include "tscript_context.h"
#include "tscript_values.h"

extern const char* TSCRIPT_AST_VALUE;
extern const char* TSCRIPT_AST_VAR_SET;
extern const char* TSCRIPT_AST_VAR_GET;
extern const char* TSCRIPT_AST_INDEX;
extern const char* TSCRIPT_AST_SUBVAR;
extern const char* TSCRIPT_AST_EQUALS;
extern const char* TSCRIPT_AST_DIFFERS;
extern const char* TSCRIPT_AST_LESS;
extern const char* TSCRIPT_AST_GREATER;
extern const char* TSCRIPT_AST_EQUALS_LESS;
extern const char* TSCRIPT_AST_EQUALS_GREATER;
extern const char* TSCRIPT_AST_NOT;
extern const char* TSCRIPT_AST_NEG;
extern const char* TSCRIPT_AST_OR;
extern const char* TSCRIPT_AST_AND;
extern const char* TSCRIPT_AST_BAND;
extern const char* TSCRIPT_AST_BOR;
extern const char* TSCRIPT_AST_PLUS;
extern const char* TSCRIPT_AST_MINUS;
extern const char* TSCRIPT_AST_MUL;
extern const char* TSCRIPT_AST_DIV;
extern const char* TSCRIPT_AST_MOD;
extern const char* TSCRIPT_AST_INC;
extern const char* TSCRIPT_AST_DEC;
extern const char* TSCRIPT_AST_UN_INC;
extern const char* TSCRIPT_AST_UN_DEC;
extern const char* TSCRIPT_AST_LEFT;
extern const char* TSCRIPT_AST_RIGHT;
extern const char* TSCRIPT_AST_MATCH;
extern const char* TSCRIPT_AST_IF;
extern const char* TSCRIPT_AST_WHILE;
extern const char* TSCRIPT_AST_FOR;
extern const char* TSCRIPT_AST_FOREACH;
extern const char* TSCRIPT_AST_SEQ;
extern const char* TSCRIPT_AST_ARGS;
extern const char* TSCRIPT_AST_CONV;
extern const char* TSCRIPT_AST_TYPEOF;
extern const char* TSCRIPT_AST_EXT;
extern const char* TSCRIPT_AST_CONST;
extern const char* TSCRIPT_AST_BREAK;
extern const char* TSCRIPT_AST_EXIT;
extern const char* TSCRIPT_AST_CONTINUE;

typedef struct tscript_ast_node
{
	const char* type;
	tscript_value* value;
	struct tscript_ast_node** children;
} tscript_ast_node;

/**
	Create AST-node without children, containing specified value.
	Value object is not duplicated.
**/
tscript_ast_node* tscript_ast_node_val(const char* type, tscript_value* val);
tscript_ast_node* tscript_ast_node_0(const char* type);
tscript_ast_node* tscript_ast_node_1(const char* type, tscript_ast_node* child);
tscript_ast_node* tscript_ast_node_2(const char* type, tscript_ast_node* child1,
	tscript_ast_node* child2);
tscript_ast_node* tscript_ast_node_3(const char* type, tscript_ast_node* child1, 	
	tscript_ast_node* child2, tscript_ast_node* child3);
tscript_ast_node* tscript_ast_node_4(const char* type, tscript_ast_node* child1, 	
	tscript_ast_node* child2, tscript_ast_node* child3, tscript_ast_node* child4);
void tscript_ast_node_add_child(tscript_ast_node* node, tscript_ast_node* child);

/**
	Deletes specified AST node and frees all allocated resources.
	Deletes all children of the node.
	Pointed structure is also destroyed.
**/
void tscript_ast_node_free(tscript_ast_node* node);

void tscript_print_ast(tscript_context* context);

#endif
