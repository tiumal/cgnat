#!/bin/bash
clear
iCgnat=$1
iPublic=$2
fPublic=$3
razao=$4
poct3=$(echo $iPublic | cut -d. -f3)

# Crie um array associativo
declare -A ranges
# Defina os intervalos
ranges[0]="0-8191"
ranges[1]="8192-16383"
ranges[2]="16384-24575"
ranges[3]="24576-32767"
ranges[4]="32768-40959"
ranges[5]="40960-49151"
ranges[6]="49152-57343"
ranges[7]="57344-65535"

# Percorra o array associativo
for sub in {156..157}; do
    
    for ((poct4=$(echo $iPublic | cut -d. -f4); poct4<=255; poct4++)); do
        for key in {0..7}; do
            echo /sbin/iptables -w -t nat -N CGNAT_${key}_${sub}_${poct4}_OUT
            echo /sbin/iptables -w -t nat -N CGNAT_${key}_${sub}_${poct4}_IN
            echo /sbin/iptables -w -t nat -F CGNAT_${key}_${sub}_${poct4}_OUT
            echo /sbin/iptables -w -t nat -F CGNAT_${key}_${sub}_${poct4}_IN
            echo /sbin/iptables -w -t nat -A CGNAT_${key}_${sub}_${poct4}_OUT -s 100.10${key}.${sub}.${poct4} -p tcp -j SNAT --to 191.241.${sub}.${poct4}:"${ranges[$key]}"
            echo /sbin/iptables -w -t nat -A CGNAT_${key}_${sub}_${poct4}_OUT -s 100.10${key}.${sub}.${poct4} -p udp -j SNAT --to 191.241.${sub}.${poct4}:"${ranges[$key]}"
            echo /sbin/iptables -w -t nat -A CGNAT_${key}_${sub}_${poct4}_IN -d 191.241.${sub}.${poct4} -p tcp --dport "${ranges[$key]}" -j DNAT --to 100.10${key}.${sub}.${poct4}
            echo /sbin/iptables -w -t nat -A CGNAT_${key}_${sub}_${poct4}_IN -d 191.241.${sub}.${poct4} -p udp --dport "${ranges[$key]}" -j DNAT --to 100.10${key}.${sub}.${poct4}
            echo /sbin/iptables -w -t nat -A CGNAT_${key}_${sub}_${poct4}_OUT -j SNAT --to 191.241.${sub}.${poct4}
            echo /sbin/iptables -w -t nat -A POSTROUTING -s 100.10${key}.${sub}.${poct4}/32 -j CGNAT_${key}_${sub}_${poct4}_OUT
            
        done
        echo /sbin/iptables -w -t nat -A PREROUTING -d 191.241.${sub}.${poct4}/32 -j CGNAT_${key}_${sub}_${poct4}_IN
    done
done
