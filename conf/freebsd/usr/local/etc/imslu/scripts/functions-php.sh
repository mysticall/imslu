#!/bin/sh

. /usr/local/etc/imslu/config.sh

kind_traffic=$(echo "SELECT COUNT(id) FROM kind_traffic" | ${MYSQL} $database -u $user -p${password} -s)

ip_add() {

    if [ $USE_VLANS -eq 0 ]; then
        if [ -n $(ifconfig -g vlan | grep ${2}) ]; then
            ${VTYSH} -d zebra -c 'enable' -c 'configure terminal' -c "ip route ${1}/32 ${2}" -c 'exit' -c 'exit'
        fi
    else
        if [ -n "${3}" ] && [ "${4}" == "n" ]; then
            # arp -S 10.0.1.2 34:23:87:96:70:27
            ${ARP} -S ${1} ${3}
        fi
  fi
}

ip_rem() {

    if [ $USE_VLANS -eq 0 ] && [ -n "${1}" ]; then
        # arp -d 10.0.1.2
        ${ARP} -d ${1}
        ${VTYSH} -d zebra -c 'enable' -c 'configure terminal' -c "no ip route ${1}/32 ${2}" -c 'exit' -c 'exit'
    else
        # arp -d 10.0.1.2
        ${ARP} -d ${1}
    fi
}

mac_add() {

    # arp -S 10.0.1.2 34:23:87:96:70:27
    ${ARP} -S ${1} ${4}
}

mac_rem() {

    # arp -d 10.0.1.2
    ${ARP} -d ${1}
}

iface_rem() {

    if [ -n "${1}" ]; then
        # arp -a -d -i vlan12
        ${ARP} -a -d -i ${1}
    fi

    if [ $USE_VLANS -eq 0 ] && [ -n "${1}" ]; then
        query="SELECT ip FROM ip WHERE vlan='${1}'"
        while read -r ip; do
            if [ $(expr "${ip}" : ".*") -gt 0 ]; then
                ${VTYSH} -d zebra -c 'enable' -c 'configure terminal' -c "no ip route ${ip}/32 ${1}" -c 'exit' -c 'exit'
            fi
        done <<EOF
$(echo ${query} | ${MYSQL} $database -u $user -p${password} -s)
EOF

        ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE ip SET vlan='' WHERE vlan='${1}';"
    fi
}

ip_allow() {

    ${IPFW} -q table 1 add ${1}/32 ${2}1
    ${IPFW} -q table 2 add ${1}/32 ${2}11

    if [ ${kind_traffic} -ge 2 ]; then
        ${IPFW} -q table 3 add ${1}/32 ${2}2
        ${IPFW} -q table 4 add ${1}/32 ${2}22
    fi
    if [ ${kind_traffic} -ge 3 ]; then
        ${IPFW} -q table 5 add ${1}/32 ${2}3
        ${IPFW} -q table 6 add ${1}/32 ${2}33
    fi
    if [ ${kind_traffic} -ge 4 ]; then
        ${IPFW} -q table 7 add ${1}/32 ${2}4
        ${IPFW} -q table 8 add ${1}/32 ${2}44
    fi
    if [ ${kind_traffic} -ge 5 ]; then
        ${IPFW} -q table 9 add ${1}/32 ${2}5
        ${IPFW} -q table 10 add ${1}/32 ${2}55
    fi
}

ip_stop() {

    ${IPFW} -q table 1 delete ${1}/32
    ${IPFW} -q table 2 delete ${1}/32

    if [ ${kind_traffic} -ge 2 ]; then
        ${IPFW} -q table 3 delete ${1}/32
        ${IPFW} -q table 4 delete ${1}/32
    fi
    if [ ${kind_traffic} -ge 3 ]; then
        ${IPFW} -q table 5 delete ${1}/32
        ${IPFW} -q table 6 delete ${1}/32
    fi
    if [ ${kind_traffic} -ge 4 ]; then
        ${IPFW} -q table 7 delete ${1}/32
        ${IPFW} -q table 8 delete ${1}/32
    fi
    if [ ${kind_traffic} -ge 5 ]; then
        ${IPFW} -q table 9 delete ${1}/32
        ${IPFW} -q table 10 delete ${1}/32
    fi
}


case "${1}" in
pppd_kill)
    ${IMSLU_SCRIPTS}/mpd.sh "close_session" "${2}"
    ;;

ip_add)
    ip_add "${2}" "${3}" "${4}" "${5}"
    sleep 10 && mac_add "${2}" "${3}" "${4}" "${5}"
    ;;

ip_rem)
    ip_rem "${2}" "${3}"
    ;;

mac_add)
    mac_add "${2}" "${3}" "${4}" "${5}"
    ;;

mac_rem)
    mac_rem "${2}" "${3}"
    ;;

iface_rem)
    iface_rem "${2}"
    ;;

ip_allow)
    ip_allow "${2}" "${3}"
    ;;

ip_stop)
    ip_stop "${2}"
    ;;

show_freeradius_log)
    awk '{ if ($7 == "Auth:") print $0}' ${FR_LOG_FILE} 2>&1
    ;;

*)
    echo "Usage: /usr/local/etc/imslu/scripts/functions-php.sh {pppd_kill|ip_add|ip_rem}"
    exit 1
    ;;
esac

exit 0
