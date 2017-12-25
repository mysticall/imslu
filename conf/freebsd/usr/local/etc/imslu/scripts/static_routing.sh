#!/bin/sh

. /usr/local/etc/imslu/config.sh
. /etc/rc.conf

arp_entries=""

# DHCP
dhcpd=""
dhcpd_subnets=""
if [ ${USE_DHCPD} -eq 0 ] && [ -f ${DHCPD_CONF} ]; then
    # Reading subnets
    while read -r a b c d; do
        if [ "${a}" == "subnet" ]; then
            dhcpd_subnets="${dhcpd_subnets} ${b}"
        fi
    done <"${DHCPD_CONF}"
fi

if [ ${USE_VLANS} -eq 0 ]; then

    zebra="!\n! Zebra configuration saved from /usr/local/etc/imslu/scripts/static_routing.sh\n!\n!\n"

    for VLAN in $(eval echo \$vlans_${IFACE_INTERNAL}); do

        zebra="${zebra}interface ${VLAN}\n"
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
    echo -e "${zebra}" > /usr/local/etc/quagga/zebra.conf
    service quagga restart

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
# arp entries must be set after starting the zebra
echo -e "${arp_entries}" > ${ARP_ENTRIES}
${ARP} -f ${ARP_ENTRIES}

# DHCP
if [ ${USE_DHCPD} -eq 0 ]; then
    # Delete Fixed IP addresses
    sed -i '' -e '/^host /,/^}$/d' ${DHCPD_CONF}
    sed -i '' -e '/^$/d' ${DHCPD_CONF}

    echo -e "${dhcpd}" >> ${DHCPD_CONF}
    service isc-dhcpd restart
fi
