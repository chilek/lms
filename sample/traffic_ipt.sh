#! /bin/bash
# ------ Przyk³adowy skrypt dla lms-traffic --------
# Skrypt zliczaj±cy ruch dla ka¿dego usera na podstawie
# liczników iptables. Nale¿y go uruchamiaæ z crona 
# na przyk³ad co 10 minut.
# Na firewallu nale¿y utworzyæ ³añcuchy zliczaj±ce ruch:
# iptables -N RECV
# iptables -N SEND
# i nastêpnie dla ka¿dego komputera regu³ki:
# iptables -A RECV -d <IP komputera w sieci LAN> -j RETURN
# iptables -A SEND -s <IP komputera w sieci LAN> -j RETURN
# i na koncu:
# iptables -A FORWARD -j RECV
# iptables -A FORWARD -j SEND

#--------Ustawienie sta³ych u¿ytkownika----------------
hosty=" \		# koñcówki adresów sieciowych
0.1 0.2 0.3 0.4 1.5 1.6 1.7 1.8 1.9 1.10 \
1.1 1.2 1.3 1.4 1.5 1.6 1.7 1.8 1.9 1.10 \
1.11 1.12 "
net='192.168'			# sieæ
log='/var/log/traffic.log'	# katalog ze statystyk±
ipt='/usr/sbin/iptables'	
#--------------------------------------------------------

# odczyt liczników firewalla i zapis do pliku. 
$ipt -L RECV -v -x -n > /tmp/recv.stat
$ipt -L SEND -v -x -n > /tmp/send.stat

# usuniêcie poprzedniego loga
if [ -e $log ]
then
    rm $log
fi

# zapis aktualnych danych dla ka¿dego IP osobno
# do pliku odczytywanego przez skrypt lms-traffic 
# w formacie: <adres IP> <upload> <download>
for host in $hosty
    do
    recv=`cat /tmp/recv.stat | grep $net.$host' ' | cut -c10-18`
    send=`cat /tmp/send.stat | grep $net.$host' ' | cut -c10-18`
    echo -e $net.$host '\t' $send '\t' $recv >> $log
done

# zerowanie liczników iptables
$ipt -Z

# usuniêcie plików tymczasowych
rm /tmp/recv.stat
rm /tmp/send.stat

# $Id$