#!/bin/sh

. /etc/imslu/config.sh

arp_entries=""

# DHCP
dhcpd=""
dhcpd_subnets=""
if [ ${USE_DHCPD} -eq 0 ] && [ -f ${DHCPD_CONF} ]; then
    # Reading subnets
    while read -r a b c d; do
        if [ "${a}" = "subnet" ]; then
            dhcpd_subnets="${dhcpd_subnets} ${b}"
        fi
    done <"${DHCPD_CONF}"
fi

if [ ${USE_VLANS} -eq 0 ]; then

    zebra="!\n! Zebra configuration saved from /etc/imslu/scripts/static_routing.sh\n!\n!\n"
    for VLAN_ID in ${VLAN_SEQ}; do

        zebra="${zebra}interface vlan${VLAN_ID}\n"
        for ip in ${STATIC_ROUTES}; do

            zebra="${zebra} ip address ${ip}\n"
        done
    done
    zebra="${zebra}!\n"

    # Adding routing and static MAC for IP addresses who have vlan.
    query="SELECT ip, vlan, protocol, free_mac, mac FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND vlan NOT LIKE ''"
    while read -r ip vlan protocol free_mac mac; do
        if [ ${#ip} -gt 0 ]; then
            zebra="${zebra}ip route ${ip}/32 ${vlan}\n"

            if [ "${free_mac}" = "n" ] && [ ${#mac} -gt 0 ]; then
                arp_entries="${arp_entries}${ip} ${mac}\n"
            fi

            # DHCP
            if [ "${protocol}" = "DHCP" ] && [ ${#mac} -gt 0 ] && [ ${USE_DHCPD} -eq 0 ]; then

                if [ $(expr "${dhcpd_subnets}" : ".*${ip%[.:]*}") -gt 0 ]; then
                    dhcpd="${dhcpd}host ${ip} {\n  hardware ethernet ${mac};\n  fixed-address ${ip};\n}\n"
                else
                    logger -p local7.notice -t imslu-scripts "Missing subnet for ${ip} in ${DHCPD_CONF}"
                fi
            fi
        fi
    done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF

    # Static Routing
    zebra="${zebra}!\nip forwarding\n!\n!\nline vty\n!"
    echo ${zebra} > /etc/quagga/zebra.conf
    systemctl restart zebra

    # DHCP
    if [ ${USE_DHCPD} -eq 0 ]; then
        query="SELECT ip, mac FROM ip WHERE userid != 0 AND protocol = 'DHCP' AND vlan LIKE ''"
        while read -r ip mac; do
            if [ ${#ip} -gt 0 ] && [ ${#mac} -gt 0 ]; then
                if [ $(expr "${dhcpd_subnets}" : ".*${ip%[.:]*}") -gt 0 ]; then
                    dhcpd="${dhcpd}host ${ip} {\n  hardware ethernet ${mac};\n  fixed-address ${ip};\n}\n"
                else
                    logger -p local7.notice -t imslu-scripts "Missing subnet for ${ip} in ${DHCPD_CONF}"
                fi
            fi
        done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF
    fi
    sleep 10
else

    # Adding static MAC for IP addresses.
    query="SELECT ip, protocol, free_mac, mac FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND mac NOT LIKE ''"
    while read -r ip protocol free_mac mac ; do

        if [ "${free_mac}" = "n" ]; then
            arp_entries="${arp_entries}${ip} ${mac}\n"
        fi

        # DHCP
        if [ "${protocol}" = "DHCP" ] && [ ${USE_DHCPD} -eq 0 ]; then
            if [ $(expr "${dhcpd_subnets}" : ".*${ip%[.:]*}") -gt 0 ]; then
                dhcpd="${dhcpd}host ${ip} {\n  hardware ethernet ${mac};\n  fixed-address ${ip};\n}\n"
            else
                logger -p local7.notice -t imslu-scripts "Missing subnet for ${ip} in ${DHCPD_CONF}"
            fi
        fi
    done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF
fi

# ARP
# Clearing arp cache
while read -r tmp1 tmp_ip tmp2 mac tmp3 tmp4 interface; do

    ip=$(expr "${tmp_ip}" : '[\(\)]*\([0-9a-f.:]*\)')
    ${ARP} -d ${ip}
done <<EOF
$(${ARP} -a | grep -v "incomplete")
EOF

# arp entries must be set after starting the zebra
echo ${arp_entries} > ${ARP_ENTRIES}
${ARP} -f ${ARP_ENTRIES}

# DHCP
if [ ${USE_DHCPD} -eq 0 ]; then
    # Delete Fixed IP addresses
    sed -i "/^host /,/^}$/d" ${DHCPD_CONF}
    sed -i "/^$/d" ${DHCPD_CONF}

    echo ${dhcpd} >> ${DHCPD_CONF}
    systemctl restart isc-dhcp-server
fi
