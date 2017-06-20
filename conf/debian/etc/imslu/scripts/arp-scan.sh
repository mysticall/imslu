#!/bin/bash

##### USE_VLANS=true #####
check_status_vlan () {

    local status
    # Search for users who not have a VLAN and MAC
    query="SELECT id FROM ip WHERE userid != 0 AND protocol = 'IP' AND (vlan LIKE '' OR (mac LIKE '' AND free_mac='n')) LIMIT 1"
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

    i=0
    for VLAN_ID in ${VLAN_SEQ}; do

      IFACE=${IFACE_INTERNAL}.${padding:${#VLAN_ID}}${VLAN_ID}

      #reducing the load
      if [ $((i%2)) -eq 0 ]; then
        $ARP_SCAN -I ${IFACE} ${NET} -q | grep ${NET:0:-5} >/tmp/arp-scan/${IFACE}-${NET:0:-3}
      else
        $ARP_SCAN -I ${IFACE} ${NET} -q | grep ${NET:0:-5} >/tmp/arp-scan/${IFACE}-${NET:0:-3}&
      fi

      ((i++))
    done
  done
}

find_ip_mac_vlan () {

    local row
    local id
    local ip
    local vlan
    local mac
    local free_mac

    # Users who not have a VLAN and MAC
    declare -A ip_vlan_mac
    # Users who have a VLAN, but not have MAC
    declare -A ip_vlan

    for row in $(ls -x /tmp/arp-scan); do

        if [ -s /tmp/arp-scan/${row} ]; then

            vlan=${row:0:9}
            src="${row:10:-1}1"
            while read -r ip mac; do

                ip_vlan_mac[${ip}]="${vlan} ${mac} ${src}"
                ip_vlan[${vlan}_${ip}]="${vlan} ${mac} ${src}"
            done < /tmp/arp-scan/${row}
        fi
    done
#    echo "ip_vlan_mac: ${!ip_vlan_mac[@]}"
#    echo "ip_vlan_mac: ${ip_vlan_mac[@]}"
#    echo "ip_vlan: ${!ip_vlan[@]}"
#    echo "ip_vlan: ${ip_vlan[@]}"

    # Search for users who not have a VLAN and MAC
    query="SELECT id, ip, free_mac FROM ip WHERE userid != 0 AND protocol = 'IP' AND vlan LIKE '' AND mac LIKE ''"
    while read -r id ip free_mac; do

        if [ -n "${ip_vlan_mac[${ip}]}" ]; then
            read -r vlan mac src <<< "${ip_vlan_mac[${ip}]}"

#           ip route replace 10.0.1.2 dev eth1.0010 src 10.0.1.1
            ip route replace ${ip} dev ${vlan} src ${src}
            if [[ "${free_mac}" == "n" ]]; then
#               arp -i eth1.0010 -s 10.0.1.2 34:23:87:96:70:27
                arp -i ${vlan} -s ${ip} ${mac}
            fi
            mysql $database -u $user -p${password} -e "UPDATE ip SET vlan='${vlan}', mac='${mac}' WHERE id='${id}';"
        fi
    done < <(echo $query | mysql $database -u $user -p${password} -s)


    # Search for users who have a MAC, but not have VLAN
    query="SELECT id, ip, mac, free_mac FROM ip WHERE userid != 0 AND protocol = 'IP' AND vlan LIKE '' AND mac NOT LIKE ''"
    while read -r id ip mac free_mac; do

        if [ -n "${ip_vlan_mac[${ip}]}" ]; then
            read -r vlan mac src <<< "${ip_vlan_mac[${ip}]}"

#           ip route replace 10.0.1.2 dev eth1.0010 src 10.0.1.1
            ip route replace ${ip} dev ${vlan} src ${src}
            if [[ "${free_mac}" == "n" ]]; then
#               arp -i eth1.0010 -s 10.0.1.2 34:23:87:96:70:27
                arp -i ${vlan} -s ${ip} ${mac}
            fi
            mysql $database -u $user -p${password} -e "UPDATE ip SET vlan='${vlan}' WHERE id='${id}';"
        fi
    done < <(echo $query | mysql $database -u $user -p${password} -s)


    # Search for users who have a VLAN, but not have MAC
    query="SELECT id, ip, vlan FROM ip WHERE userid != 0 AND protocol = 'IP' AND vlan NOT LIKE '' AND mac LIKE '' AND free_mac='n'"
    while read -r id ip free_mac; do

        if [ -n "${ip_vlan[${vlan}_${ip}]}" ]; then
            read -r vlan mac src <<< "${ip_vlan[${vlan}_${ip}]}"

#           arp -i eth1.0010 -s 10.0.1.2 34:23:87:96:70:27
            arp -i ${vlan} -s ${ip} ${mac}
            mysql $database -u $user -p${password} -e "UPDATE ip SET mac='${mac}' WHERE id='${id}';"
        fi
    done < <(echo $query | mysql $database -u $user -p${password} -s)
    unset ip_vlan_mac
    unset ip_vlan
}

##### USE_VLANS=false #####
check_status() {

    local status
    # Search for users who not have a MAC
    query="SELECT id FROM ip WHERE userid != 0 AND protocol = 'IP' AND mac LIKE '' AND free_mac='n' LIMIT 1"
    status=$(echo $query | mysql $database -u $user -p${password} -s)

    if [ -n "${status}" ]; then
        return 0
    else
        return 1
    fi
}

scan_ip_mac () {

    local SUBNET=${NETWORKS[@]/$FR_NETWORKS}
    local NET

    for NET in ${SUBNET}; do
        $ARP_SCAN -I ${IFACE_INTERNAL} ${NET}/24 -q | grep ${NET:0:-5} >/tmp/arp-scan/${NET:0:-3}&
    done
}

find_ip_mac () {

    local row
    local id
    local ip
    local mac
    local free_mac

    # Users who not have a MAC
    declare -A ip_mac

    for row in $(ls -x /tmp/arp-scan); do

        if [ -s /tmp/arp-scan/${row} ]; then

            src="${row:0:-1}1"
            while read -r ip mac; do

                ip_mac[${ip}]="${mac} ${src}"
            done < /tmp/arp-scan/${row}
        fi
    done


    # Search for users who not have MAC
    query="SELECT id, ip FROM ip WHERE userid != 0 AND protocol = 'IP' AND mac LIKE '' AND free_mac='n'"

    while read -r id ip free_mac; do

        if [ -n "${ip_mac[${ip}]}" ]; then
            read -r mac src <<< "${ip_mac[${ip}]}"

#           arp -i eth1 -s 10.0.1.2 34:23:87:96:70:27
            arp -i ${IFACE_INTERNAL} -s ${ip} ${mac}
            mysql $database -u $user -p${password} -e "UPDATE ip SET mac='${mac}' WHERE id='${id}';"
        fi
    done < <(echo $query | mysql $database -u $user -p${password} -s)
    unset ip_mac
}

while true; do

. /etc/imslu/config.sh
if [ ! -d /tmp/arp-scan ]; then
    mkdir -p /tmp/arp-scan
fi

if [ $USE_VLANS -eq 0 ]; then
    if [ check_status_vlan ]; then
        rm -f /tmp/arp-scan/*
        scan_ip_mac_vlan
        sleep 30
        find_ip_mac_vlan
        sleep 250
    else
        sleep 300
    fi
else
    if [ check_status ]; then
        rm -f /tmp/arp-scan/*
        scan_ip_mac
        sleep 30
        find_ip_mac
        sleep 250
    else
        sleep 300
    fi
fi
done
