#!/bin/bash

ip_add () {

    if [[ $USE_VLANS -eq 0 ]]; then
        if [ -f /proc/net/vlan/${2} ]; then
            IFS=\. read -r a b c d <<< "$1"
#           ip route add 10.0.1.2 dev eth1.0010 src 10.0.1.1
            ip route add $1 dev $2 src ${a}.${b}.${c}.1

            if [[ -n "$3"  && "$4" == "n" ]]; then
#               arp -i eth1.0010 -s 10.0.1.2 34:23:87:96:70:27
                arp -i $2 -s $1 $3
            fi
#        else
#            echo -e "USE:\n/etc/imslu/scripts/functions-php.sh ip_add '10.0.1.2' 'eth1.0011' '34:23:87:96:70:27' 'n'"
        fi
    else
        if [ -n "$1" ]; then
            IFS=\. read -r a b c d <<< "$1"
#           ip route add 10.0.1.2 dev eth1 src 10.0.1.1
            ip route add $1 dev $IFACE_INTERNAL src ${a}.${b}.${c}.1

            if [[ -n "$3" && "$4" == "n" ]]; then
#               arp -s 10.0.1.2 34:23:87:96:70:27
                arp -s $1 $3
            fi
        fi
    fi
}

ip_rem () {

    if [[ $USE_VLANS -eq 0 && -n "$1" ]]; then
#       ip route del 10.0.1.2
        ip route del $1
#       arp -i eth1.0011 -d 10.0.1.2
        arp -i $2 -d $1
    else
#       ip route del 10.0.1.2
        ip route del $1
#       arp -i eth1 -d 10.0.1.2
        arp -i $IFACE_INTERNAL -d $1
    fi
}

mac_add () {

    if [ $USE_VLANS -eq 0 ]; then
        if [[ -f /proc/net/vlan/${2} && -n "$3" ]]; then

#           arp -i eth1.0010 -s 10.0.1.2 34:23:87:96:70:27
            arp -i $2 -s $1 $3
#        else
#            echo -e "USE:\n/etc/imslu/scripts/functions-php.sh mac_add '10.0.1.2' 'eth1.0011' '34:23:87:96:70:27' 'n'"
        fi
    else
        if [[ -n "$1" && -n "$3" && "$4" == "n" ]]; then

#               arp -i eth1 -s 10.0.1.2 34:23:87:96:70:27
                arp -i $IFACE_INTERNAL -s $1 $3
        fi
    fi
}

mac_rem () {

    if [[ $USE_VLANS -eq 0 && -n "$1" ]]; then
#       arp -i eth1.0011 -d 10.0.1.2
        arp -i $2 -d $1
    else
#       arp -i eth1 -d 10.0.1.2
        arp -i $IFACE_INTERNAL -d $1
    fi
}

ip_allow () {

    ipset add allowed $1
}

ip_stop () {

    ipset del allowed $1
}

if [[ "$1" == "tc_class_add" || "$1" == "tc_class_delete" || "$1" == "tc_class_replace" || "$1" == "tc_filter_add" || "$1" == "tc_filter_replace" || "$1" == "tc_filter_delete" ]]; then

    . /etc/imslu/config.sh

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
fi

tc_class_replace () {

    userid=$1
    service=$2
    i=2
    s=$STEP
    # if you are using one kind of traffic, you do not need this loop
    for kind_trafficid in ${kind_traffic[@]}; do

        read -r kind_trafficid name in_min in_max out_min out_max <<< "${services[${kind_trafficid}${service}]}"
        if [ $i -eq 2 ]; then
$TC class replace dev ${IFACE_IMQ0} parent 1:${i} classid 1:$(printf '%x' ${userid}) hfsc sc m1 0bit d 0us m2 ${in_min} ul m1 0bit d 0us m2 ${in_max}
$TC class replace dev ${IFACE_IMQ1} parent 1:${i} classid 1:$(printf '%x' ${userid}) hfsc sc m1 0bit d 0us m2 ${out_min} ul m1 0bit d 0us m2 ${out_max}
        else
$TC class replace dev ${IFACE_IMQ0} parent 1:${i} classid 1:$(printf '%x' $((${userid}+${s}))) hfsc sc m1 0bit d 0us m2 ${in_min} ul m1 0bit d 0us m2 ${in_max}
$TC class replace dev ${IFACE_IMQ1} parent 1:${i} classid 1:$(printf '%x' $((${userid}+${s}))) hfsc sc m1 0bit d 0us m2 ${out_min} ul m1 0bit d 0us m2 ${out_max}
        fi
        ((i++))
        s=$((s+$STEP))
    done
}

tc_class_delete () {

    userid=$1
    i=2
    s=$STEP
    # if you are using one kind of traffic, you do not need this loop
    for kind_trafficid in ${kind_traffic[@]}; do

        if [ $i -eq 2 ]; then
$TC class delete dev ${IFACE_IMQ0} parent 1:${i} classid 1:$(printf '%x' ${userid})
$TC class delete dev ${IFACE_IMQ1} parent 1:${i} classid 1:$(printf '%x' ${userid})
        else
$TC class delete dev ${IFACE_IMQ0} parent 1:${i} classid 1:$(printf '%x' $((${userid}+${s})))
$TC class delete dev ${IFACE_IMQ1} parent 1:${i} classid 1:$(printf '%x' $((${userid}+${s})))
        fi
        ((i++))
        s=$((s+$STEP))
    done
    
}

tc_filter_replace () {

    ip=$1
    userid=$2
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
$TC filter replace dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 flowid 1:$(printf '%x' ${userid})
$TC filter replace dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 flowid 1:$(printf '%x' ${userid})
        else
$TC filter replace dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 match ip tos 0x${i} 0xff flowid 1:$(printf '%x' $((${userid}+${s})))
$TC filter replace dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 match ip tos 0x${i} 0xff flowid 1:$(printf '%x' $((${userid}+${s})))
        fi
        ((i--))
        s=$((s-$STEP))
    done
}

tc_filter_delete () {

    ip=$1
    IFS=\. read -r a b c d <<< "${ip}"

    if [ $c -eq 0 ]; then
        hash_table=$((${a}-1))
    else
        hash_table=$((${a}+${c}))
    fi

    i=0
    # if you are using one kind of traffic, you do not need this loop
    for kind_trafficid in ${kind_traffic[@]}; do

        tc filter delete dev ${IFACE_IMQ0} parent 1: prio 1 handle $(printf '%x' $hash_table):$(printf '%x' $d):80${i} protocol ip u32
        tc filter delete dev ${IFACE_IMQ1} parent 1: prio 1 handle $(printf '%x' $hash_table):$(printf '%x' $d):80${i} protocol ip u32
        ((i++))
    done
}

case "$1" in
pppd_kill)
    ip=$2
    IFACE=$(ip route show | grep "${ip}" | grep -o "ppp\w*")
    if [ -f /var/run/${IFACE}.pid ]; then
        PID=$(cat /var/run/${IFACE}.pid)
        kill -9 $PID
        sed -i "/${ip}/d" /tmp/ip_activity_pppoe
        sed -i "/${ip}/d" /tmp/ip_activity
    fi
	;;

ip_add)
	ip_add $2 $3 $4 $5
	;;

ip_rem)
	ip_rem $2 $3
	;;

mac_add)
	mac_add $2 $3 $4 $5
	;;

mac_rem)
	mac_rem $2 $3
	;;

ip_allow)
	ip_allow $2
	;;

ip_stop)
	ip_stop $2
	;;

tc_class_add|tc_class_replace)
	tc_class_replace $2 $3
	;;

tc_class_delete)
	tc_class_delete $2
	;;

tc_filter_add|tc_filter_replace)
	tc_filter_replace $2 $3
	;;

tc_filter_delete)
	tc_filter_delete $2
	;;

*)
	echo "Usage: /etc/imslu/scripts/functions-php.sh {pppd_kill|ip_add|ip_rem}"
	exit 1
	;;
esac

exit 0
