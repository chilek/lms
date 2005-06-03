
MODULES = ethers hostfile dhcp oident dns cutoff payments traffic notify tc \
	    system pinger parser ggnotify

all:
	for i in `echo $(MODULES)`; do make -C $$i ; done

clean:
	for i in `echo $(MODULES)`; do make -C $$i clean; done

install:
	for i in `echo $(MODULES)`; do make install -C $$i ; done