#!/bin/bash

TC=tc
ECHO=true
BANDWIDTH=12600kbit
ETH_LAN=eth3
ETH_WAN=eth1

$TC qdisc del dev $ETH_LAN root
$TC qdisc add dev $ETH_LAN root handle 1: htb default 1
$TC class add dev $ETH_LAN parent 1: classid 1:1 htb rate 100mbit ceil 100mbit
$TC class add dev $ETH_LAN parent 1: classid 1:2 htb rate $BANDWIDTH ceil $BANDWIDTH

$TC qdisc del dev $ETH_WAN root
$TC qdisc add dev $ETH_WAN root handle 1: htb default 1
$TC class add dev $ETH_WAN parent 1: classid 1:1 htb rate $BANDWIDTH ceil $BANDWIDTH

n=3 # Class ID
x=3000
y=7000

$TC filter add dev $ETH_LAN protocol ip pref 50 parent 1: u32 match ip src 10.0.0.0/8 flowid 1:
$TC filter add dev $ETH_LAN protocol ip pref 50 parent 1: u32 match ip src 217.98.242.0/24 flowid 1:
$TC filter add dev $ETH_LAN protocol ip pref 50 parent 1: u32 match ip src 213.25.115.0/24 flowid 1:

while read qos; do
downstream=`echo $qos | cut -d' ' -f 1`
upstream=`echo $qos | cut -d' ' -f 2`
iplist=`echo $qos | cut -d' ' -f 3-`

mass=$(($downstream * 7/8))
inte=$(($upstream * 1/8))
#upceil=$(($upstream * 3/2))
upceil=$upstream;

$TC class add dev $ETH_LAN parent 1:2 classid 1:$n htb rate $downstream\kbit ceil $downstream\kbit

$TC class add dev $ETH_LAN parent 1:$n classid 1:$x htb rate $inte\kbit ceil $downstream\kbit
$TC class add dev $ETH_LAN parent 1:$n classid 1:$y htb rate $mass\kbit ceil $downstream\kbit

$TC qdisc add dev $ETH_LAN parent 1:$x handle $x: sfq perturb 10

$TC qdisc add dev $ETH_LAN parent 1:$y handle $y: sfq perturb 10

$TC filter add dev $ETH_LAN protocol ip pref 20 parent 1:$n u32 match u16 0x0000 0xfe00 at 2 flowid 1:$x
$TC filter add dev $ETH_LAN protocol ip pref 30 parent 1:$n u32 match u8 0 0 at 0 flowid 1:$y

$TC class add dev $ETH_WAN parent 1:1 classid 1:$n htb rate $upstream\kbit ceil $upceil\kbit

$TC qdisc add dev $ETH_WAN parent 1:$n handle $n: sfq perturb 10

for i in `echo $iplist`; do

$TC filter add dev $ETH_LAN protocol ip pref 100 parent 1: u32 match ip dst $i flowid 1:$n
$TC filter add dev $ETH_WAN protocol ip pref 100 parent 1: u32 match ip src $i flowid 1:$n
#$TC filter add dev $ETH_LAN protocol ip pref 40 parent 1: u32 match ip src 213.25.115.253 ip dst $i flowid 1:$n
#$TC filter add dev $ETH_LAN protocol ip pref 40 parent 1: u32 match ip src 217.98.242.254 ip dst $i flowid 1:$n
done

n=$(($n + 1))
x=$(($x + 1))
y=$(($y + 1))
done

exit
