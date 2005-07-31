#include "sql.h"
#include "tscript_extensions.h"
#include "tscript_debug.h"

#include <stdio.h>
#include <string.h>

ConnHandle *conn = NULL;
int rows;

void tscript_ext_sql_exec(char* query)
{
	tscript_debug("Executing query %s\n", query);
	rows = db_exec(conn, query);
	tscript_debug("Query executed\n");
}

tscript_value tscript_ext_sql_rows(tscript_value arg)
{
	QueryHandle *q = NULL;
	char *query = tscript_value_convert_to_string(arg).data;
	
	rows = 0;
		
	tscript_debug("Executing SQL Extension: ROWS\n");

	if( (strncmp("SELECT", query+1, 6)==0) ||
	    (strncmp("select", query+1, 6)==0) ) //+1 because of space at the beginning of the query
	{
		q = db_query(conn, query);
		rows = db_nrows(q);
		db_free(&q);
	}
	else
		tscript_ext_sql_exec(query);

	tscript_debug("Finished executing SQL Extension: ROWS\n");
	return tscript_value_create_number(rows);
}

tscript_value tscript_ext_sql_create(tscript_value arg)
{
	char* query;

	tscript_debug("Executing SQL Extension: CREATE\n");
	asprintf(&query, "CREATE %s", tscript_value_convert_to_string(arg).data);
	tscript_ext_sql_exec(query);
	free(query);
	tscript_debug("Finished executing SQL Extension: CREATE\n");

	return tscript_value_create(TSCRIPT_TYPE_NULL, "");
}

tscript_value tscript_ext_sql_drop(tscript_value arg)
{
	char* query;

	tscript_debug("Executing SQL Extension: DROP\n");
	asprintf(&query, "DROP %s", tscript_value_convert_to_string(arg).data);
	tscript_ext_sql_exec(query);
	free(query);
	tscript_debug("Finished executing SQL Extension: DROP\n");

	return tscript_value_create(TSCRIPT_TYPE_NULL, "");
}

tscript_value tscript_ext_sql_insert(tscript_value arg)
{
	char* query;

	tscript_debug("Executing SQL Extension: INSERT\n");
	asprintf(&query, "INSERT %s", tscript_value_convert_to_string(arg).data);
	tscript_ext_sql_exec(query);
	free(query);
	tscript_debug("Finished executing SQL Extension: INSERT\n");

	return tscript_value_create(TSCRIPT_TYPE_NULL, "");
}

tscript_value tscript_ext_sql_delete(tscript_value arg)
{
	char* query;

	tscript_debug("Executing SQL Extension: DELETE\n");
	asprintf(&query, "DELETE %s", tscript_value_convert_to_string(arg).data);
	tscript_ext_sql_exec(query);
	free(query);
	tscript_debug("Finished executing SQL Extension: DELETE\n");

	return tscript_value_create(TSCRIPT_TYPE_NULL, "");
}

tscript_value tscript_ext_sql_update(tscript_value arg)
{
	char* query;
	
	tscript_debug("Executing SQL Extension: UPDATE\n");
	asprintf(&query, "UPDATE %s", tscript_value_convert_to_string(arg).data);
	tscript_ext_sql_exec(query);
	free(query);
	tscript_debug("Finished executing SQL Extension: UPDATE\n");
	
	return tscript_value_create(TSCRIPT_TYPE_NULL, "");
}

tscript_value tscript_ext_sql_select(tscript_value arg)
{
	tscript_value res;
	tscript_value *res_row;
	int r, c;
	char *query, *colname, *value;
	QueryHandle *q = NULL;
	
	tscript_debug("Executing SQL Extension: SELECT\n");
	asprintf(&query, "SELECT %s", tscript_value_convert_to_string(arg).data);
	q = db_query(conn, query);

	res = tscript_value_create(TSCRIPT_TYPE_ARRAY, "");

	for(r = 0; r<db_nrows(q); r++)
	{
		res_row = tscript_value_array_item_ref(&res, r);
		*res_row = tscript_value_create(TSCRIPT_TYPE_ARRAY, "");

		for(c = 0; c<db_ncols(q); c++)
		{
			colname = db_colname(q, c);
			value = db_get_data(q, r, colname);
			(*tscript_value_array_item_ref(res_row, c)) = tscript_value_create(TSCRIPT_TYPE_STRING, value);
			(*tscript_value_subvar_ref(res_row, colname)) = tscript_value_create(TSCRIPT_TYPE_STRING, value);
		}
	}
	db_free(&q);
	
	tscript_debug("Finished executing SQL Extension: SELECT\n");
	return res;
}

void tscript_ext_sql_init(ConnHandle *c)
{
	conn = c;
	tscript_add_extension("CREATE", tscript_ext_sql_create);
	tscript_add_extension("DROP", tscript_ext_sql_drop);
	tscript_add_extension("INSERT", tscript_ext_sql_insert);
	tscript_add_extension("DELETE", tscript_ext_sql_delete);
	tscript_add_extension("UPDATE", tscript_ext_sql_update);
	tscript_add_extension("SELECT", tscript_ext_sql_select);
	tscript_add_extension("ROWS", tscript_ext_sql_rows);
	tscript_add_extension("create", tscript_ext_sql_create);
	tscript_add_extension("drop", tscript_ext_sql_drop);
	tscript_add_extension("insert", tscript_ext_sql_insert);
	tscript_add_extension("delete", tscript_ext_sql_delete);
	tscript_add_extension("update", tscript_ext_sql_update);
	tscript_add_extension("select", tscript_ext_sql_select);
	tscript_add_extension("rows", tscript_ext_sql_rows);
}

void tscript_ext_sql_close()
{
	tscript_remove_extension("CREATE");
	tscript_remove_extension("DROP");
	tscript_remove_extension("INSERT");
	tscript_remove_extension("DELETE");
	tscript_remove_extension("UPDATE");
	tscript_remove_extension("SELECT");
	tscript_remove_extension("ROWS");
	tscript_remove_extension("create");
	tscript_remove_extension("drop");
	tscript_remove_extension("insert");
	tscript_remove_extension("delete");
	tscript_remove_extension("update");
	tscript_remove_extension("select");
	tscript_remove_extension("rows");
}
