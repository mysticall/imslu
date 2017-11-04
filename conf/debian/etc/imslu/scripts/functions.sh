#!/bin/sh

# Start PPPoE server
pppoe_start () {

    ${PPPOE_SERVER} -I $1 -C ${PPPOE_SERVER_NAME} -S ${PPPOE_SERVER_NAME} -L ${PPPOE_DEFAULT_IP} -N 1000 -k -r
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

        modprobe 8021q
        vconfig set_name_type VLAN_PLUS_VID_NO_PAD

        local VLAN_ID

        for VLAN_ID in ${VLAN_SEQ}; do

            vconfig add ${IFACE_INTERNAL} ${VLAN_ID}
            echo 1 > /proc/sys/net/ipv4/conf/vlan${VLAN_ID}/proxy_arp
            echo 1 > /proc/sys/net/ipv4/conf/vlan${VLAN_ID}/arp_accept
            $IP link set dev vlan${VLAN_ID} up

            # Start PPPoE server
            if [ $USE_PPPoE -eq 0 ]; then

                pppoe_start "vlan${VLAN_ID}"
            fi
        done
    else
        echo "USE_VLANS=false in /etc/imslu/config.sh"
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

            vconfig rem vlan${VLAN_ID}
        done
    else
        echo "USE_VLANS=false in /etc/imslu/config.sh"
    fi
}
