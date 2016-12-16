#!/bin/bash

# Start PPPoE server
pppoe_add () {

    ${PPPOE_SERVER} -I $1 -C ${PPPOE_SERVER_NAME} -S ${PPPOE_SERVER_NAME} -L ${PPPOE_DEFAULT_IP} -N 1000 -k -r
}

# Stop PPPoE sessions and PPPoE servers
pppoe_rem () {

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

        modprobe 8021q
        vconfig set_name_type DEV_PLUS_VID

        local DEFAULT_GATEWAYS=""
        local padding="0000"
        local VLAN_ID
        local IFACE
        local DEFAULT_IP

        for row in "${!NETWORKS[@]}"; do
            for row2 in ${NETWORKS[${row}]/$FR_NETWORKS}; do
                IFS=\. read -r a b c d <<< "$row2"
#               echo $a $b $c $d

                DEFAULT_GATEWAYS+="${a}.${b}.${c}.1/32 "
            done
        done

        for VLAN_ID in ${VLAN_SEQ}; do

            IFACE=${IFACE_INTERNAL}.${padding:${#VLAN_ID}}${VLAN_ID}
            vconfig add ${IFACE_INTERNAL} ${VLAN_ID}
            echo 1 > /proc/sys/net/ipv4/conf/${IFACE}/proxy_arp
            $IP link set dev ${IFACE} up

            for DEFAULT_IP in ${DEFAULT_GATEWAYS}; do
                $IP address add ${DEFAULT_IP} dev ${IFACE}
            done

            # Start PPPoE servers
            if [[ $USE_PPPoE -eq 0 ]]; then

                pppoe_add ${IFACE}
            fi
        done
    else
        echo "USE_VLANS=false in /etc/imslu/config.sh"
    fi
}

vconfig_rem () {

    if [ $USE_VLANS -eq 0 ]; then

        local padding="0000"
        local VLAN_ID
        local IFACE

        # Stop PPPoE sessions and PPPoE servers
        if [[ $USE_PPPoE -eq 0 ]]; then

            pppoe_rem
        fi

        for VLAN_ID in ${VLAN_SEQ}; do

            IFACE=${IFACE_INTERNAL}.${padding:${#VLAN_ID}}${VLAN_ID}
            vconfig rem ${IFACE}
        done
    else
        echo "USE_VLANS=false in /etc/imslu/config.sh"
    fi
}
