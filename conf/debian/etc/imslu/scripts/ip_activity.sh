#!/bin/sh

. /etc/imslu/config.sh

# PPPoE IP activity
if [ $USE_PPPoE -eq 0 ]; then

    > /tmp/ip_activity_pppoe
    while read -r ip dev; do
        echo ${ip} >> /tmp/ip_activity_pppoe
    done <<EOF
$(${IP} route show | grep ppp)
EOF
fi
