#!/bin/sh

. /usr/local/etc/imslu/config.sh
. /etc/rc.conf

set_arp_entries() {
    # arp entries must be set after starting the zebra
    ${ARP} -f ${ARP_ENTRIES}
}

if [ ${USE_VLANS} -eq 0 ]; then

    arp_entries=""
    zebra="!\n! Zebra configuration saved from /usr/local/etc/imslu/scripts/static_routing.sh\n!\n!\n"

    for VLAN in $(eval echo \$vlans_${IFACE_INTERNAL}); do

        zebra="${zebra}interface ${VLAN}\n"
        for ip in ${STATIC_ROUTES}; do

            zebra="${zebra} ip address ${ip}\n"
        done
    done
    zebra="${zebra}!\n"

    # Adding routing and static MAC for IP addresses who have vlan.
    query="SELECT ip, vlan, free_mac, mac FROM ip WHERE userid != 0 AND protocol = 'IP' AND vlan NOT LIKE ''"
    while read -r ip vlan free_mac mac; do
        if [ $(expr "${ip}" : ".*") -gt 0 ]; then
            zebra="${zebra}ip route ${ip}/32 ${vlan}\n"

            if [ "${free_mac}" = "n" ] && [ -n "${mac}" ]; then
                arp_entries="${arp_entries}${ip} ${mac}\n"
            fi
        fi
    done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF

    zebra="${zebra}!\nip forwarding\n!\n!\nline vty\n!"
    echo -e "${zebra}" > /usr/local/etc/quagga/zebra.conf
    echo -e "${arp_entries}" > ${ARP_ENTRIES}

    service quagga restart
    sleep 10 && set_arp_entries &
else

    # Adding static MAC for IP addresses.
    query="SELECT ip, mac FROM ip WHERE userid != 0 AND protocol = 'IP' AND free_mac = 'n' AND mac NOT LIKE ''"
    while read -r ip mac ; do

        # arp -S 10.0.1.2 34:23:87:96:70:27
        ${ARP} -S ${ip} ${mac}
    done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF
fi
