#!/bin/sh

. /etc/imslu/config.sh

if [ $USE_VLANS -eq 0 ]; then

    zebra="!\n! Zebra configuration saved from /etc/imslu/scripts/static_routing.sh\n!\n!\n"
    for VLAN_ID in ${VLAN_SEQ}; do

        zebra="${zebra}interface vlan${VLAN_ID}\n"
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
                # arp -i vlan10 -s 10.0.1.2 34:23:87:96:70:27
                ${ARP} -i ${vlan} -s ${ip} ${mac}
            fi
        fi
    done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF

    zebra="${zebra}!\nip forwarding\n!\n!\nline vty\n!"
    echo ${zebra} > /etc/quagga/zebra.conf
    systemctl restart zebra
else

    # Adding static MAC for IP addresses.
    query="SELECT ip, mac FROM ip WHERE userid != 0 AND protocol = 'IP' AND free_mac = 'n' AND mac NOT LIKE ''"
    while read -r ip mac ; do

        # arp -s 10.0.1.2 34:23:87:96:70:27
        ${ARP} -s ${ip} ${mac}
    done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF
fi
