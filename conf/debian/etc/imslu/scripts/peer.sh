#!/bin/bash

. /etc/imslu/config.sh

$IPSET create peers_temp hash:net
rm -f /tmp/peers
wget -O /tmp/peers "${PEER}"

if [[ -f /tmp/peers && -s /tmp/peers ]]; then
    sed -i '/#/d' /tmp/peers

    for subnet in $(cat /tmp/peers); do
    $IPSET add peers_temp ${subnet}
    done
    rm /tmp/peers

    $IPSET swap peers_temp peers
    $IPSET destroy peers_temp
fi