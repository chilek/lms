
LIBS = -lpq -ldl 
CFLAGS = -I/usr/local/pgsql/include  -DUSE_PGSQL -I../..
LDFLAGS = -L/usr/local/pgsql/lib


SOURCES = dhcp.c
OBJECTS = ${SOURCES:.c=.o}

OUT = dhcp.so

all: $(OUT)

$(OUT): ${OBJECTS}
	gcc -shared $(OBJECTS) -o $(OUT) $(LDFLAGS) $(LIBS)
	rm -rf $(OBJECTS)

clean:
	rm -rf $(OUT)

