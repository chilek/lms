
LIBS = -lpq -ldl 
CFLAGS = -I/usr/local/pgsql/include  -DUSE_PGSQL -I../..
LDFLAGS = -L/usr/local/pgsql/lib


SOURCES= hostfile.c
OBJECTS = ${SOURCES:.c=.o}

OUT= hostfile.so

all: $(OUT)

$(OUT): ${OBJECTS}
	gcc -shared $(OBJECTS) -o $(OUT) $(LDFLAGS) $(LIBS)

clean:
	rm -rf $(OBJECTS) $(OUT)

