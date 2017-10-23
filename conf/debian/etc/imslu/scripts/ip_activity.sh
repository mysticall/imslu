#!/bin/bash

. /etc/imslu/config.sh

# Static IP activity
awk '{ if ($1 == "tcp" && $4 == "ESTABLISHED" && !a[$5]++) print substr($5,5); else if ($1 == "udp" && !a[$4]++) print substr($4,5); else if ($1 == "icmp" && !a[$4]++) print substr($4,5); }' /proc/net/ip_conntrack > /tmp/ip_activity

# PPPoE IP activity
if [[ $USE_PPPoE -eq 0 ]]; then

    cat /dev/null > /tmp/ip_activity_pppoe
    while read -r ip dev; do
        echo ${ip} >> /tmp/ip_activity_pppoe
    done < <($IP route show | grep ppp)
fi
