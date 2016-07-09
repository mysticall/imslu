#!/bin/bash

# http://tldp.org/LDP/Bash-Beginners-Guide/html/sect_10_02.html
# http://tldp.org/LDP/abs/html/arrays.html
# http://wiki.bash-hackers.org/syntax/arrays?s[]=array
# http://wiki.bash-hackers.org/commands/builtin/read

. /etc/imslu/config.sh
#. /etc/imslu/scripts/functions.sh

# remove any existing qdiscs
$TC qdisc del dev $IFACE_IMQ0 root 2> /dev/null
$TC qdisc del dev $IFACE_IMQ0 ingress 2> /dev/null
$TC qdisc del dev $IFACE_IMQ1 root 2> /dev/null
$TC qdisc del dev $IFACE_IMQ1 ingress 2> /dev/null

$TC qdisc del dev $IFACE_IMQ0 root 2> /dev/null
$TC qdisc del dev $IFACE_IMQ0 ingress 2> /dev/null
$TC qdisc del dev $IFACE_IMQ1 root 2> /dev/null
$TC qdisc del dev $IFACE_IMQ1 ingress 2> /dev/null

# install HFSC under WAN to limit download
$TC qdisc add dev $IFACE_IMQ0 root handle 1: hfsc default 9
# root class :1
$TC class add dev $IFACE_IMQ0 parent 1: classid 1:1 hfsc sc m1 0bit d 0us m2 1000mbit ul m1 0bit d 0us m2 1000mbit
# BGP peer - national traffic class 1:2
$TC class add dev $IFACE_IMQ0 parent 1:1 classid 1:2 hfsc sc m1 0bit d 0us m2 600mbit ul m1 0bit d 0us m2 600mbit
# International traffic class 1:3
$TC class add dev $IFACE_IMQ0 parent 1:1 classid 1:3 hfsc sc m1 0bit d 0us m2 300mbit ul m1 0bit d 0us m2 300mbit

# ADD HERE kind traffic: three four five ...

# default
$TC class add dev $IFACE_IMQ0 parent 1:1 classid 1:9 hfsc sc m1 0bit d 0us m2 1mbit ul m1 0bit d 0us m2 100mbit

# install HFSC under LAN to limit upload
$TC qdisc add dev $IFACE_IMQ1 root handle 1: hfsc default 9
# root class :1
$TC class add dev $IFACE_IMQ1 parent 1: classid 1:1 hfsc sc m1 0bit d 0us m2 1000mbit ul m1 0bit d 0us m2 1000mbit
# BGP peer - national traffic class 1:2
$TC class add dev $IFACE_IMQ1 parent 1:1 classid 1:2 hfsc sc m1 0bit d 0us m2 600mbit ul m1 0bit d 0us m2 600mbit
# International traffic class 1:3
$TC class add dev $IFACE_IMQ1 parent 1:1 classid 1:3 hfsc sc m1 0bit d 0us m2 300mbit ul m1 0bit d 0us m2 300mbit

# ADD HERE kind traffic: three four five ...

# default
$TC class add dev $IFACE_IMQ1 parent 1:1 classid 1:9 hfsc sc m1 0bit d 0us m2 1mbit ul m1 0bit d 0us m2 100mbit


####### Kinds of traffic #######
query="SELECT kind_trafficid FROM kind_traffic"
declare -a kind_traffic

i=0
while read -r row; do
    kind_traffic[$i]=$row
    ((i++))
done < <(echo $query | mysql $database -u $user -p${password} -s)
# echo ${kind_traffic[@]}
# echo ${!kind_traffic[@]}

####### Services #######
query="SELECT kind_trafficid, name, in_min, in_max, out_min, out_max FROM services"
declare -A services

while read -r row; do
    read -r kind_trafficid name in_min <<< "$row"
    services[${kind_trafficid}${name}]=$row

done < <(echo $query | mysql $database -u $user -p${password} -s)
# echo ${services[@]}
# echo ${!services[@]}

####### Users #######
query="SELECT userid, service FROM users"
declare -a users

i=0
while read -r row; do
    users[$i]=$row
    ((i++))
done < <(echo $query | mysql $database -u $user -p${password} -s)

### Add tc class rules for users ###
for row in "${users[@]}"; do
    read -r userid service <<< "$row"

    i=2
    s=$STEP
    # if you are using one kind of traffic, you do not need this loop
    for kind_trafficid in ${kind_traffic[@]}; do
        # if each kind traffic is added to all services, you do not need this check
        if [ -n "${services[${kind_trafficid}${service}]}" ]; then

            read -r kind_trafficid name in_min in_max out_min out_max <<< "${services[${kind_trafficid}${service}]}"
            if [ $i -eq 2 ]; then
$TC class add dev ${IFACE_IMQ0} parent 1:${i} classid 1:$(printf '%x' ${userid}) hfsc sc m1 0bit d 0us m2 ${in_min} ul m1 0bit d 0us m2 ${in_max}
$TC class add dev ${IFACE_IMQ1} parent 1:${i} classid 1:$(printf '%x' ${userid}) hfsc sc m1 0bit d 0us m2 ${out_min} ul m1 0bit d 0us m2 ${out_max}
            else
$TC class add dev ${IFACE_IMQ0} parent 1:${i} classid 1:$(printf '%x' $((${userid}+${s}))) hfsc sc m1 0bit d 0us m2 ${in_min} ul m1 0bit d 0us m2 ${in_max}
$TC class add dev ${IFACE_IMQ1} parent 1:${i} classid 1:$(printf '%x' $((${userid}+${s}))) hfsc sc m1 0bit d 0us m2 ${out_min} ul m1 0bit d 0us m2 ${out_max}
            fi
        fi
        ((i++))
        s=$((s+$STEP))
    done
done

### Add tc u32 hashing filter rules ###
declare -i a b c a2 b2 c2
$IPSET flush lan

# root filters
$TC filter add dev ${IFACE_IMQ0} parent 1:0 prio 1 protocol ip u32
$TC filter add dev ${IFACE_IMQ1} parent 1:0 prio 1 protocol ip u32

for row in "${!NETWORKS[@]}"; do
    IFS=\. read -r a b c d <<< "$row"
#    echo $a $b $c $d

# master filters for /16 subnet
$TC filter add dev ${IFACE_IMQ0} parent 1: prio 1 handle $(printf '%x' $a): protocol ip u32 divisor 256
$TC filter add dev ${IFACE_IMQ0} protocol ip parent 1:0 prio 1 u32 ht 800:: match ip dst ${row} hashkey mask 0x0000ff00 at 16 link $(printf '%x' $a):
$TC filter add dev ${IFACE_IMQ1} parent 1: prio 1 handle $(printf '%x' $a): protocol ip u32 divisor 256
$TC filter add dev ${IFACE_IMQ1} protocol ip parent 1:0 prio 1 u32 ht 800:: match ip src ${row} hashkey mask 0x0000ff00 at 12 link $(printf '%x' $a):

    for row2 in ${NETWORKS[${row}]}; do
        IFS=\. read -r a2 b2 c2 d2 <<< "$row2"
#        echo $a2 $b2 $c2 $d2

        $IPSET add lan ${row2}

        if [ $c2 -eq 0 ]; then
            hash_table=$((${a2}-1))
        else
            hash_table=$((${a2}+${c2}))
        fi

# secondary filters for /24 subnet
$TC filter add dev ${IFACE_IMQ0} parent 1: prio 1 handle $(printf '%x' $hash_table): protocol ip u32 divisor 256
$TC filter add dev ${IFACE_IMQ0} protocol ip parent 1:0 prio 1 u32 ht $(printf '%x' $a):$(printf '%x' $c2): match ip dst ${row2} hashkey mask 0x000000ff at 16 link $(printf '%x' $hash_table):
$TC filter add dev ${IFACE_IMQ1} parent 1: prio 1 handle $(printf '%x' $hash_table): protocol ip u32 divisor 256
$TC filter add dev ${IFACE_IMQ1} protocol ip parent 1:0 prio 1 u32 ht $(printf '%x' $a):$(printf '%x' $c2): match ip src ${row2} hashkey mask 0x000000ff at 12 link $(printf '%x' $hash_table):
    done
done

query="SELECT userid, ip, vlan, mac FROM ip WHERE userid != 0"
while read -r userid ip vlan mac; do
    IFS=\. read -r a b c d <<< "${ip}"

    if [ $c -eq 0 ]; then
        hash_table=$((${a}-1))
    else
        hash_table=$((${a}+${c}))
    fi

    # The filter rules for IP address should start with checking the TOS field in the IPv4 header ( backwards )
    i=$((${#kind_traffic[@]}))
    s=$((STEP*${#kind_traffic[@]}))

    # if you are using one kind of traffic, you do not need this loop
    for kind_trafficid in ${kind_traffic[@]}; do

        # if $i is first element in ${kind_traffic[@]}
        if [ $i -eq 1 ]; then
$TC filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 flowid 1:$(printf '%x' ${userid})
$TC filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 flowid 1:$(printf '%x' ${userid})
        else
$TC filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 match ip tos 0x${i} 0xff flowid 1:$(printf '%x' $((${userid}+${s})))
$TC filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 match ip tos 0x${i} 0xff flowid 1:$(printf '%x' $((${userid}+${s})))
        fi
        ((i--))
        s=$((s-$STEP))
    done
done < <(echo $query | mysql $database -u $user -p${password} -s)


