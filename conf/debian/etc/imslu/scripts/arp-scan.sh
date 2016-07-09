#!/bin/bash

check_status_vlan () {

    local status
    # Search for users who no VLAN and no MAC
    query="SELECT ip FROM ip WHERE userid != 0 AND (vlan like '' OR (mac like '' AND free_mac=0)) AND protocol='IP' LIMIT 1"
    status=$(echo $query | mysql $database -u $user -p${password} -s)

    if [ -n "${status}" ]; then
        return 0
    else
        return 1
    fi
}

scan_ip_mac_vlan () {

    local padding="0000"
    local VLAN_ID
    local IFACE
    local SUBNET=${NETWORKS[@]/$FR_NETWORKS}
    local NET

    for NET in ${SUBNET}; do

        for VLAN_ID in ${VLAN_SEQ}; do

            IFACE=${IFACE_INTERNAL}.${padding:${#VLAN_ID}}${VLAN_ID}
            $ARP_SCAN -I ${IFACE} ${NET} | grep ${NET:0:-5} >/tmp/arp-scan/${IFACE}-${NET:0:-3}&
        done
    done
}

find_ip_mac_vlan () {

    local row
    local id
    local ip
    local vlan
    local mac
    local mac_info
    local free_mac

    # Users who not have a VLAN and MAC
    declare -A ip_vlan_mac
    # Users who have a MAC, but not have VLAN
    declare -A mac_vlan
    # Users who have a VLAN, but not have MAC
    declare -A vlan_mac

    for row in $(ls -x /tmp/arp-scan); do

        if [ -s /tmp/arp-scan/${row} ]; then

            vlan=${row:0:9}
            src="${row:10:-1}1"
            while read -r ip mac mac_info; do

                ip_vlan_mac[${ip}]="${vlan} ${mac} ${mac_info} ${src}"
                mac_vlan[${mac}]="${vlan} ${mac} ${mac_info}"
                vlan_mac[${vlan}_${ip}]="${vlan} ${mac} ${mac_info} ${src}"
            done < /tmp/arp-scan/${row}
        fi
    done
#    echo "ip_vlan_mac: ${!ip_vlan_mac[@]}"
#    echo "ip_vlan_mac: ${ip_vlan_mac[@]}"
#    echo "mac_vlan: ${!mac_vlan[@]}"
#    echo "mac_vlan: ${mac_vlan[@]}"
#    echo "vlan_mac: ${!vlan_mac[@]}"
#    echo "vlan_mac: ${vlan_mac[@]}"

    # Search for users who not have a VLAN and MAC
    query="SELECT id, ip, free_mac FROM ip WHERE userid != 0 AND vlan like '' AND mac like '' AND free_mac=0 AND protocol='IP' LIMIT 1"

    while read -r id ip free_mac; do

        if [ -n "${ip_vlan_mac[${ip}]}" ]; then
            read -r vlan mac mac_info src <<< "${ip_vlan_mac[${ip}]}"

            ip route replace ${ip} dev ${vlan} src ${src}
        
        fi
    done < <(echo $query | mysql $database -u $user -p${password} -s)

}

. /etc/imslu/config.sh
if [ ! -d /tmp/arp-scan ]; then
    mkdir -p /tmp/arp-scan
fi

if [[ $USE_VLANS -eq 0 && check_status_vlan ]]; then

    scan_ip_mac_vlan
    sleep 30
    find_ip_mac_vlan
else
    echo "USE_VLANS=false in /etc/imslu/config.sh" 
fi
