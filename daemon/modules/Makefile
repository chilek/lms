
MODULES = ethers hostfile dhcp oident dns cutoff payments traffic notify tc \
	    system pinger ggnotify

all:
	for i in `echo $(MODULES)`; do $(MAKE) -C $$i ; done

clean:
	for i in `echo $(MODULES)`; do $(MAKE) -C $$i clean; done

install:
	for i in `echo $(MODULES)`; do $(MAKE) install -C $$i ; done