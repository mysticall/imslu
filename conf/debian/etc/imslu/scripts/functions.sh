#!/bin/sh

. /etc/imslu/config.sh


pppoe_status () {

    PPPoE_STATUS=""
    if [ ${USE_PPPoE} -eq 0 ]; then

        while read -r PID TTY STAT TIME COMMAND OPT1 OPT2 OPT_TMP; do

            if [ ${#OPT2} -gt 0 ]; then

                PPPoE_STATUS="${PPPoE_STATUS}${OPT2} "
            fi
        done <<EOF
$(ps ax | grep pppoe-server)
EOF

        export PPPoE_STATUS
    fi
}

# Start PPPoE server
pppoe_start () {

    if [ $(expr "${PPPoE_STATUS}" : ".*${1} ") -eq 0 ]; then

        ${PPPOE_SERVER} -I ${1} -C ${PPPOE_SERVER_NAME} -S ${PPPOE_SERVER_NAME} -L ${PPPOE_DEFAULT_IP} -N 1000 -k -r
    else

        echo "PPPoE server on ${1} is running."
    fi
}

# Stop PPPoE sessions and PPPoE servers
pppoe_stop () {

    local PID

    for PID in $(ps -C pppd -o pid=); do

        kill -9 ${PID}
    done
    sleep 5
    for PID in $(ps -C pppoe-server -o pid=); do

        kill -9 ${PID}
    done
}

vconfig_add () {

    if [ $USE_VLANS -eq 0 ]; then

        pppoe_status
        local VLAN_ID

        for VLAN_ID in ${VLAN_SEQ}; do

            if [ ! -f /proc/net/vlan/vlan${VLAN_ID} ]; then

                ${IP} link add link ${IFACE_INTERNAL} name vlan${VLAN_ID} type vlan id ${VLAN_ID}
                echo 1 > /proc/sys/net/ipv4/conf/vlan${VLAN_ID}/proxy_arp
                echo 1 > /proc/sys/net/ipv4/conf/vlan${VLAN_ID}/arp_accept
                ${IP} link set dev vlan${VLAN_ID} up
            else

                echo "vlan${VLAN_ID} exists"
            fi

            # Start PPPoE server
            if [ $USE_PPPoE -eq 0 ]; then

                pppoe_start "vlan${VLAN_ID}"
            fi
        done
    else
        echo "vconfig_add: USE_VLANS=false in /etc/imslu/config.sh"
    fi
}

vconfig_rem () {

    if [ $USE_VLANS -eq 0 ]; then

        local VLAN_ID

        # Stop PPPoE server
        if [ $USE_PPPoE -eq 0 ]; then

            pppoe_stop
        fi

        for VLAN_ID in ${VLAN_SEQ}; do

            ${IP} link delete vlan${VLAN_ID}
        done
    else
        echo "vconfig_rem: USE_VLANS=false in /etc/imslu/config.sh"
    fi

    pppoe_status
}

#1 - IP
#2 - Iface
ip_add () {

    if [ ${USE_VLANS} -eq 0 ] && [ ${#1} -gt 0 ] && [ ${#2} -gt 0 ]; then
        if [ -f /proc/net/vlan/${2} ]; then
            ${VTYSH} -d staticd -c 'enable' -c 'configure terminal' -c "ip route ${1}/32 ${2}" -c 'exit' -c 'exit'
        fi
    fi
}

#1 - IP
#2 - Iface
#3 - MAC
ip_rem () {

  if [ $USE_VLANS -eq 0 ]; then
    if [ ${#1} -gt 0 ] && [ ${#2} -gt 0 ]; then
        ${VTYSH} -d staticd -c 'enable' -c 'configure terminal' -c "no ip route ${1}/32 ${2}" -c 'exit' -c 'exit'
        if [ ${#3} -gt 0 ]; then
            # ip neighbor del 10.0.0.2 lladdr 34:23:87:96:70:27 dev vlan4
            ${IP} neighbor del ${1} lladdr ${3} dev ${2}
        fi
    fi
  else
    # ip neighbor del 10.0.0.2 lladdr 34:23:87:96:70:27 dev eth1
    ${IP} neighbor del ${1} lladdr ${3} dev ${2} ${IFACE_INTERNAL}
  fi
}

#1 - IP
#2 - Iface
#3 - MAC
#4 - free_mac
mac_add () {

    if [ ${USE_VLANS} -eq 0 ]; then
        if [ ${#1} -gt 0 ] && [ -f /proc/net/vlan/${2} ] && [ ${#3} -gt 0 ] && [ "${4}" = "n" ]; then
            # ip neigh replace 10.0.0.2 lladdr 34:23:87:96:70:27 dev vlan14 nud permanent
            ${IP} neighbor replace ${1} lladdr ${3} dev ${2} nud permanent
        fi
    else
        if [ ${#1} -gt 0 ] && [ ${#4} -gt 0 ] && [ "${4}" = "n" ]; then
            # ip neigh replace 10.0.0.2 lladdr 34:23:87:96:70:27 dev eth1 nud permanent
            ${IP} neighbor replace ${1} lladdr ${3} dev ${IFACE_INTERNAL} nud permanent
        fi
    fi
}

#1 - IP
#2 - Iface
#3 - MAC
mac_rem () {

  if [ $USE_VLANS -eq 0 ] && [ ${#1} -gt 0 ] && [ ${#2} -gt 0 ] && [ ${#3} -gt 0 ]; then
    # ip neighbor del 10.0.0.2 lladdr 34:23:87:96:70:27 dev vlan4
    ${IP} neighbor del ${1} lladdr ${3} dev ${2}
  else
    # ip neighbor del 10.0.0.2 lladdr 34:23:87:96:70:27 dev eth1
    ${IP} neighbor del ${1} lladdr ${3} dev ${2} ${IFACE_INTERNAL}
  fi
}

# DHCP
dhcp_subnets() {

    if [ -f ${DHCPD_CONF} ]; then
        # Reading subnets
        while read -r a b c d; do
            if [ "${a}" = "subnet" ]; then
                dhcpd_subnets="${dhcpd_subnets} ${b}"
            fi
        done <<EOF
$(cat ${DHCPD_CONF} | grep 'subnet')
EOF
    fi
    export dhcpd_subnets
}

#1 - IP
#2 - MAC
dhcp_add() {

    if [ ${USE_DHCPD} -eq 0 ] && [ ${#1} -gt 0  ] && [ ${#2} -gt 0 ]; then

        if [ $(expr "${dhcpd_subnets}" : ".*${1%[.:]*[.:]*}") -gt 0 ]; then
            echo "host ${1} {\n  hardware ethernet ${2};\n  fixed-address ${1};\n}\n" >> ${DHCPD_CONF}
        else
            logger -p local7.notice -t imslu-scripts "Missing subnet for ${1} in ${DHCPD_CONF}"
            echo 1
        fi
    fi
}

#1 - IP
dhcp_rem() {

    if [ ${#1} -gt 0 ]; then
        sed -i "/^host ${1} {/,/^}$/d" ${DHCPD_CONF}
        sed -i "/^$/d" ${DHCPD_CONF}
    fi
}
