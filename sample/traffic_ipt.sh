#! /bin/bash
# ------ Przyk�adowy skrypt dla lms-traffic --------
# Skrypt zliczaj�cy ruch dla ka�dego usera na podstawie
# licznik�w iptables. Nale�y go uruchamia� z crona 
# na przyk�ad co 10 minut.
# Na firewallu nale�y utworzy� �a�cuchy zliczaj�ce ruch:
# iptables -N RECV
# iptables -N SEND
# i nast�pnie dla ka�dego komputera regu�ki:
# iptables -A RECV -d <IP komputera w sieci LAN> -j RETURN
# iptables -A SEND -s <IP komputera w sieci LAN> -j RETURN
# i na koncu:
# iptables -A FORWARD -j RECV
# iptables -A FORWARD -j SEND

#--------Ustawienie sta�ych u�ytkownika----------------
hosty=" \		# ko�c�wki adres�w sieciowych
0.1 0.2 0.3 0.4 1.5 1.6 1.7 1.8 1.9 1.10 \
1.1 1.2 1.3 1.4 1.5 1.6 1.7 1.8 1.9 1.10 \
1.11 1.12 "
net='192.168'			# sie�
log='/var/log/traffic.log'	# katalog ze statystyk�
ipt='/usr/sbin/iptables'	
#--------------------------------------------------------

# odczyt licznik�w firewalla i zapis do pliku. 
$ipt -L RECV -v -x -n > /tmp/recv.stat
$ipt -L SEND -v -x -n > /tmp/send.stat

# usuni�cie poprzedniego loga
if [ -e $log ]
then
    rm $log
fi

# zapis aktualnych danych dla ka�dego IP osobno
# do pliku odczytywanego przez skrypt lms-traffic 
# w formacie: <adres IP> <upload> <download>
for host in $hosty
    do
    recv=`cat /tmp/recv.stat | grep $net.$host' ' | cut -c10-18`
    send=`cat /tmp/send.stat | grep $net.$host' ' | cut -c10-18`
    echo -e $net.$host '\t' $send '\t' $recv >> $log
done

# zerowanie licznik�w iptables
$ipt -Z

# usuni�cie plik�w tymczasowych
rm /tmp/recv.stat
rm /tmp/send.stat

# $Id$