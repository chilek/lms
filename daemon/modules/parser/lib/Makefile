CC=gcc
AR=ar
FLEX=flex
BISON=bison
STRIP=strip

CFLAGS=-O2 -fPIC

DEBUG=0

#######################################################################

### MINGW
ifneq ($(findstring mingw,$(CC)),)
LDFLAGS		+= -liberty -lrxspencer
SOPRE		=
SOEXT		= .dll
else
SOPRE		= lib
SOEXT		= .so
endif

ifeq ($(DEBUG),1)
CFLAGS += -g -O0
STRIP = touch
endif

HEADERS=tscript_ast.h tscript_context.h tscript_compiler.h tscript_interpreter.h tscript_variables.h tscript_parser.h tscript_values.h tscript_extensions.h tscript_debug.h list.h map.h
OBJECTS=tscript_ast.o tscript_context.o tscript_compiler.o tscript_interpreter.o tscript_variables.o tscript_parser.o tscript_values.o tscript_extensions.o tscript_debug.o tscript_lexical.o

all: libtscript.a $(SOPRE)tscript$(SOEXT)

$(SOPRE)tscript$(SOEXT): $(OBJECTS)
	$(CC) -shared -o $(SOPRE)tscript$(SOEXT) $(OBJECTS) $(LDFLAGS)
	$(STRIP) $(SOPRE)tscript$(SOEXT)

libtscript.a: $(OBJECTS)
	$(AR) rs libtscript.a $(OBJECTS)

$(OBJECTS): %.o: %.c $(HEADERS)
	$(CC) -c $< $(CFLAGS)

tscript_lexical.c: tscript_lexical.yy $(HEADERS)
	$(FLEX) -otscript_lexical.c -Ptscript_yy tscript_lexical.yy 

tscript_parser.h: tscript_parser.y $(HEADERS)
	$(BISON) -o tscript_parser.c -d tscript_parser.y -p tscript_yy -r lookahead

clean:
	rm -f libtscript.a libtscript.so tscript.dll *.o tscript_lexical.c tscript_parser.c tscript_parser.h tscript_parser.output
