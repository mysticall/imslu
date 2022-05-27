#!/bin/sh

. /etc/imslu/config.sh

# ARP
ip_neighbour="#!/bin/sh\n\n"

# DHCP
dhcpd=""
dhcpd_subnets=""

if [ ${USE_DHCPD} -eq 0 ] && [ -f ${DHCPD_CONF} ]; then
    # Reading subnets
    while read -r a b c d; do
        if [ "${a}" = "subnet" ]; then
            dhcpd_subnets="${dhcpd_subnets} ${b}"
        fi
    done <<EOF
$(cat ${DHCPD_CONF} | grep 'subnet')
EOF
fi

if [ ${USE_VLANS} -eq 0 ]; then

    zebra="!\n! Zebra configuration saved from /etc/imslu/scripts/static_routing.sh\n!\n!\n"
    for VLAN_ID in ${VLAN_SEQ}; do

        zebra="${zebra}interface vlan${VLAN_ID}\n"
        for ip in ${STATIC_ROUTES}; do

            zebra="${zebra} ip address ${ip}\n"
        done
    done
    zebra="${zebra}!\n\n!\nline vty\n!"
    echo ${zebra} > /etc/frr/zebra.conf

    staticd="!\n! Staticd configuration saved from /etc/imslu/scripts/static_routing.sh\n!\n!\n"
    # Adding routing and static MAC for IP addresses who have vlan.
    query="SELECT ip, vlan, free_mac, protocol, mac FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND vlan NOT LIKE ''"
    while read -r ip vlan free_mac protocol mac; do
        if [ ${#ip} -gt 0 ]; then
            staticd="${staticd}ip route ${ip}/32 ${vlan}\n"

            if [ "${free_mac}" = "n" ] && [ ${#mac} -gt 16 ] && [ ${#vlan} -gt 0 ]; then
                ip_neighbour="${ip_neighbour}${IP} neighbor replace ${ip} lladdr ${mac} dev ${vlan} nud permanent\n"
            fi

            # DHCP
            if [ "${protocol}" = "DHCP" ] && [ ${#mac} -gt 16 ] && [ ${USE_DHCPD} -eq 0 ]; then

                if [ $(expr "${dhcpd_subnets}" : ".*${1%[.:]*[.:]*}") -gt 0 ]; then
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
    staticd="${staticd}!\nip forwarding\n!\n!\nline vty\n!"
    echo ${staticd} > /etc/frr/staticd.conf
    systemctl restart frr

    # DHCP
    if [ ${USE_DHCPD} -eq 0 ]; then
        query="SELECT ip, mac FROM ip WHERE userid != 0 AND protocol = 'DHCP' AND vlan LIKE '' AND mac NOT LIKE ''"
        while read -r ip mac; do
            if [ ${#ip} -gt 0 ] && [ ${#mac} -gt 16 ]; then
                if [ $(expr "${dhcpd_subnets}" : ".*${1%[.:]*[.:]*}") -gt 0 ]; then
                    dhcpd="${dhcpd}host ${ip} {\n  hardware ethernet ${mac};\n  fixed-address ${ip};\n}\n"
                else
                    logger -p local7.notice -t imslu-scripts "Missing subnet for ${ip} in ${DHCPD_CONF}"
                fi
            fi
        done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF
    fi

else
    # No VLANs

    # Adding static MAC for IP addresses.
    query="SELECT ip, free_mac, protocol, mac FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND mac NOT LIKE ''"
    while read -r ip free_mac protocol mac; do

        if [ "${free_mac}" = "n" ]; then
            ip_neighbour="${ip_neighbour}${IP} neighbor replace ${ip} lladdr ${mac} dev ${IFACE_INTERNAL} nud permanent\n"
        fi

        # DHCP
        if [ "${protocol}" = "DHCP" ] && [ ${#mac} -gt 16 ] && [ ${USE_DHCPD} -eq 0 ]; then
            if [ $(expr "${dhcpd_subnets}" : ".*${1%[.:]*[.:]*}") -gt 0 ]; then
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
${IP} neighbour flush all

while read -r ip dev iface lladdr mac state; do

    ${IP} neighbor del ${ip} lladdr ${mac} dev ${iface}
done <<EOF
$(${IP} neighbour show)
EOF

sleep 10

# ip monitor all
# arp entries must be set after starting the Zebra, Staticd
echo ${ip_neighbour} > ${ARP_ENTRIES}.sh
chmod a+x ${ARP_ENTRIES}.sh
${ARP_ENTRIES}.sh &

# DHCP
if [ ${USE_DHCPD} -eq 0 ]; then
    # Delete Fixed IP addresses
    sed -i "/^host /,/^}$/d" ${DHCPD_CONF}
    sed -i "/^$/d" ${DHCPD_CONF}

    echo ${dhcpd} >> ${DHCPD_CONF}
    systemctl restart isc-dhcp-server
fi
