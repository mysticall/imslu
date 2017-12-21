#!/bin/sh

# Auto find a MAC address or VLAN when you add a static IP address for the device.
#
# Add the following lines to /etc/rc.conf to enable imslu_find:
#
#imslu_find_enable="YES"
#

ARP_EXPIRES=/tmp/arp_expires

arp_expires() {

    arp=""
    while read -r tmp1 tmp_ip tmp2 mac tmp3 interface tmp4; do

        ip=$(expr "${tmp_ip}" : '[\(\)]*\([0-9a-f.:]*\)')
        arp="${arp}${ip} ${mac} ${interface}\n"
    done <<EOF
$(${ARP} -a | grep expires)
EOF

    echo -e "${arp}" > ${ARP_EXPIRES}
}

set_arp_entries() {
    # arp entries must be set after starting the zebra
    ${ARP} -f ${ARP_ENTRIES}_find
}

##### USE_VLANS=true #####
check_status_vlan() {

    local status
    # Search for users who not have a VLAN and MAC
    query="SELECT id FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND (vlan LIKE '' OR (mac LIKE '' AND free_mac='n')) LIMIT 1"
    status=$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)

    if [ ${#status} -gt 0 ]; then
        return 0
    else
        return 1
    fi
}

find_mac_vlan() {

    local id
    local ip
    local vlan
    local mac
    local free_mac
    local arp_entries=""

    # Search for users who not have a VLAN and MAC
    query="SELECT id, ip, free_mac FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND vlan LIKE '' AND mac LIKE ''"

    while read -r id ip free_mac; do
        if [ ${#id} -gt 0 ]; then

            found=$(cat ${ARP_EXPIRES} | grep "${ip} ")
            if [ ${#found} -gt 0 ]; then

                read -r ip mac vlan <<EOF
$(echo ${found})
EOF

                ${VTYSH} -d zebra -c 'enable' -c 'configure terminal' -c "ip route ${ip}/32 ${vlan}" -c 'exit' -c 'exit'

                if [ "${free_mac}" = "n" ]; then
                    arp_entries="${arp_entries}${ip} ${mac}\n"
                fi

                ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE ip SET vlan='${vlan}', mac='${mac}' WHERE id='${id}';"
                unset found id
            fi
        fi
    done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF


    # Search for users who have a MAC, but not have VLAN
    query="SELECT id, ip, mac, free_mac FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND vlan LIKE '' AND mac NOT LIKE ''"

    while read -r id ip mac free_mac; do
        if [ ${#id} -gt 0 ]; then

            found=$(cat ${ARP_EXPIRES} | grep "${ip} ${mac} ")
            if [ ${#found} -gt 0 ]; then

                read -r ip mac vlan <<EOF
$(echo ${found})
EOF

                ${VTYSH} -d zebra -c 'enable' -c 'configure terminal' -c "ip route ${ip}/32 ${vlan}" -c 'exit' -c 'exit'

                if [ "${free_mac}" = "n" ]; then
                    arp_entries="${arp_entries}${ip} ${mac}\n"
                fi

                ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE ip SET vlan='${vlan}' WHERE id='${id}';"
                unset found id
            fi
        fi
    done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF


    # Search for users who have a VLAN, but not have MAC
    query="SELECT id, ip, vlan FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND vlan NOT LIKE '' AND mac LIKE '' AND free_mac='n'"

    while read -r id ip vlan; do
        if [ ${#id} -gt 0 ]; then

            found=$(cat ${ARP_EXPIRES} | grep -oE "${ip} ([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2}) ${vlan}")
            if [ ${#found} -gt 0 ]; then

                read -r ip mac vlan <<EOF
$(echo ${found})
EOF

                arp_entries="${arp_entries}${ip} ${mac}\n"

                ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE ip SET mac='${mac}' WHERE id='${id}';"
                unset found id
            fi
        fi
    done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF

    echo -e "${arp_entries}" > ${ARP_ENTRIES}_find
    sleep 10 && set_arp_entries &
}

##### USE_VLANS=false #####
check_status() {

        local status
        # Search for users who not have a MAC
        query="SELECT id FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND mac LIKE '' AND free_mac='n' LIMIT 1"
        status=$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)

        if [ ${#status} -gt 0 ]; then
            return 0
        else
            return 1
        fi
}

find_mac() {

    local id
    local ip
    local mac
    local interface
    local arp_entries=""

    # Search for users who not have MAC
    query="SELECT id, ip FROM ip WHERE userid != 0 AND protocol = 'IP' AND mac LIKE '' AND free_mac='n'"

    while read -r id ip; do
        if [ ${#id} -gt 0 ]; then

            found=$(cat ${ARP_EXPIRES} | grep "${ip} ")
            if [ ${#found} -gt 0 ]; then

                read -r ip mac interface <<EOF
$(echo ${found})
EOF

                arp_entries="${arp_entries}${ip} ${mac}\n"

                ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE ip SET mac='${mac}' WHERE id='${id}';"
                unset found id
            fi
        fi
    done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF

    echo -e "${arp_entries}" > ${ARP_ENTRIES}_find
    set_arp_entries
}


while true; do

    . /usr/local/etc/imslu/config.sh

    if [ ${USE_VLANS} -eq 0 ]; then
        if [ check_status_vlan ]; then
            arp_expires
            find_mac_vlan
        fi
    else
        if [ check_status ]; then
            arp_expires
            find_mac
        fi
    fi
    sleep 300
done
