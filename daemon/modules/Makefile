
MODULES = ethers hostfile dhcp oident dns cutoff payments traffic notify tc \
	    ggnotify

all:
	for i in `echo $(MODULES)`; do make -C $$i ; done
	
clean:
	for i in `echo $(MODULES)`; do make -C $$i clean; done
	