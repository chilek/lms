
LIBS = -lpq -ldl 
CFLAGS = -I/usr/local/pgsql/include -DUSE_PGSQL -DDEBUG0 -DDEBUG1 -I../..
LDFLAGS = -L/usr/local/pgsql/lib
INSTALLDIR = /usr/local/bin


SOURCES = ethers.c
OBJECTS = ${SOURCES:.c=.o}

OUT = ethers.so

all: $(OUT)

$(OUT): ${OBJECTS}
	gcc -shared $(OBJECTS) -o $(OUT) $(LDFLAGS) $(LIBS)
	rm -rf $(OBJECTS)

clean:
	rm -rf $(OUT)

