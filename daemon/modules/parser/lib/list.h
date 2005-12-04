#ifndef LIST_H
#define LIST_H

#include <stdlib.h>

/**
	list_declaration(T)
		T - type of value

	list_implementation(T, CONSTR, DESTR, CMP)
		T - type of value
		CMP - value comparision macro
	
	T* T_list_add(T_list* list, T value)
		list - list
		value - value to insert
		returns: pointer to new value in the list

	int T_list_remove(T_list* list, T value)
		Removes value from list
		list - list
		value - value to remove
		returns: 1 if removed, 0 if not found

	T* T_list_find(T_list* list, T value)
		list - list
		value - value to find
		returns: pointer to value in the list or NULL if not found
		
	T* T_list_ref(T_list* list, T value)
		Finds a value or add it to list if not found.
		list - list
		value - value to find
		returns: pointer to value in the list

	T* T_list_get(T_list* list, int index)
		list - list
		index - value index
		returns: pointer to value in the list or NULL if not found

	int T_list_contains(T_list* list, T value)
		Checks if a map contains a specified value.
		list - list
		value - value to find
		returns: 1 if found, 0 if not
		
	int T_list_count(T_list* list)
		Counts number of elements in the list.
		list - list
		returns: number of elements
**/

#define list_declaration_1(T)						\
	struct T##_list_elem;						\
	typedef struct T##_list_elem T##_list_elem;			\
	struct T##_list;						\
	typedef struct T##_list T##_list;

#define list_declaration_2(T) 						\
									\
	struct T##_list_elem						\
	{								\
		T value;						\
		struct T##_list_elem* next;				\
	};								\
									\
	struct T##_list							\
	{								\
		struct T##_list_elem* first;				\
		struct T##_list_elem* last;				\
	};								\
									\
	T##_list* T##_list_create();					\
	void T##_list_free(T##_list* list);				\
	T* T##_list_add(T##_list* list, T value);			\
	int T##_list_remove(T##_list* list, T value);			\
	T* T##_list_find(T##_list* list, T value);			\
	T* T##_list_ref(T##_list* list, T value);			\
	T* T##_list_get(T##_list* list, int index);			\
	int T##_list_contains(T##_list* list, T value);			\
	int T##_list_count(T##_list* list);				\
	T##_list* T##_list_duplicate(T##_list* list);

#define list_declaration(T) 						\
	list_declaration_1(T);						\
	list_declaration_2(T);

#define list_implementation(T, CONSTR, DESTR, CMP)			\
									\
	T##_list* T##_list_create()					\
	{								\
		T##_list* l = (T##_list*)malloc(sizeof(T##_list));	\
		l->first = NULL;					\
		l->last = NULL;						\
		return l;						\
	}								\
									\
	void T##_list_free(T##_list* list)				\
	{								\
		T##_list_elem* e = list->first;				\
		T##_list_elem* next;					\
		while (e != NULL)					\
		{							\
			next = e->next;					\
			DESTR(e->value);				\
			free(e);					\
			e = next;					\
		}							\
		free(list);						\
	}								\
									\
	T* T##_list_add(T##_list* list, T value)			\
	{								\
		T##_list_elem* e =					\
			(T##_list_elem*)malloc(sizeof(T##_list_elem));	\
		e->value = CONSTR(value);				\
		e->next = NULL;						\
		if (list->last != NULL)					\
			list->last->next = e;				\
		else							\
			list->first = e;				\
		list->last = e;						\
		return &e->value;					\
	}								\
									\
	int T##_list_remove(T##_list* list, T value)			\
	{								\
		T##_list_elem* e = list->first;				\
		T##_list_elem* prev = NULL;				\
		while (e != NULL)					\
		{							\
			if (CMP(e->value, value))			\
			{						\
				if (prev != NULL)			\
					prev->next = e->next;		\
				else					\
					list->first = e->next;		\
				if (list->last == e)			\
					list->last = prev;		\
				DESTR(e->value);			\
				free(e);				\
				return 1;				\
			}						\
			prev = e;					\
			e = e->next;					\
		}							\
		return 0;						\
	}								\
									\
	T* T##_list_find(T##_list* list, T value)			\
	{								\
		T##_list_elem* e = list->first;				\
		while (e != NULL)					\
		{							\
			if (CMP(e->value, value))			\
				return &e->value;			\
			e = e->next;					\
		}							\
		return NULL;						\
	}								\
									\
	T* T##_list_ref(T##_list* list, T value)			\
	{								\
		T* v = T##_list_find(list, value);			\
		if (v != NULL)						\
			return v;					\
		return T##_list_add(list, value);			\
	}								\
									\
	T* T##_list_get(T##_list* list, int index)			\
	{								\
		int i;							\
		T##_list_elem* e = list->first;				\
		i = 0;							\
		while (e != NULL)					\
		{							\
			if (i == index)					\
				return &e->value;			\
			e = e->next;					\
			i++;						\
		}							\
		return NULL;						\
	}								\
									\
	int T##_list_contains(T##_list* list, T value)			\
	{								\
		return (T##_list_find(list, value) != NULL);		\
	}								\
									\
	int T##_list_count(T##_list* list)				\
	{								\
		int r;							\
		T##_list_elem* e = list->first;				\
		r = 0;							\
		while (e != NULL)					\
		{							\
			r++;						\
			e = e->next;					\
		}							\
		return r;						\
	}								\
									\
	T##_list* T##_list_duplicate(T##_list* list)			\
	{								\
		T##_list* r = T##_list_create();			\
		T##_list_elem* e = list->first;				\
		while (e != NULL)					\
		{							\
			T##_list_add(r, e->value);			\
			e = e->next;					\
		}							\
		return r;						\
	}								\

#define no_constr(e) e
#define no_destr(e)

#endif
