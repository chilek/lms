# Przyk³adowy template do generowania pliku dhcpd.conf
# $Id$

ddns-update-style none;
shared-network LMS {
<? foreach from=$networks item=network ?>
	subnet <? $network.address ?> netmask <? $network.mask ?> {
		default-lease-time 86400;
		max-lease-time 86400;
		option subnet-mask <? $network.mask ?>;
<? if $network.gateway ?>
		option routers <? $network.gateway ?>;
<? /if ?>		
<? if $network.dns ?>
		option domain-name-servers <? $network.dns ?>;
<? /if ?>
<? if $network.domain ?>
		option domain-name "<? $network.domain ?>";
<? /if ?>
<? if $network.wins ?>
		option netbios-name-servers <? $network.wins ?>;
<? /if ?>
<? if $network.dhcpstart && $network.dhcpend ?>
		range <? $network.dhcpstart ?> <? $network.dhcpend ?>;
<? /if ?>
<? if $network.nodes ?>
<? foreach from=$network.nodes item=node ?>
		host <? $node.name ?> { 
			hardware ethernet <? $node.mac ?>; 
			fixed-address <? $node.ipaddr ?>;
		}
<? /foreach ?>
<? /if ?>
	}
<? /foreach ?>
}
	
