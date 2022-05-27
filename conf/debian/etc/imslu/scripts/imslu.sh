#!/bin/sh

logger -p local7.notice -t imslu-scripts "Starting imslu."

# IMSLU Services
. /etc/imslu/config.sh
. /etc/imslu/scripts/functions.sh

# IMQ
# https://github.com/imq/linuximq/wiki

logger -p local7.notice -t imslu-scripts "Bring a IMQ interfaces up."
# modprobe imq
$IP link set dev imq0 up
$IP link set dev imq1 up

# Creating Ethernet VLANs
logger -p local7.notice -t imslu-scripts "Creating Ethernet VLANs."
vconfig_add

# ipset
# https://wiki.archlinux.org/index.php/Ipset
# http://blog.ls20.com/securing-your-server-using-ipset-and-dynamic-blocklists/

logger -p local7.notice -t imslu-scripts "Creating IP sets."
$IPSET create -exist allowed hash:ip
$IPSET create -exist lan hash:net --hashsize 64
$IPSET create -exist peers hash:net

# iptables
logger -p local7.notice -t imslu-scripts "Starting rc.firewall"
/etc/imslu/rc.firewall

# Load peer IP addresses
logger -p local7.notice -t imslu-scripts "Starting peer.sh"
/etc/imslu/scripts/peer.sh

# Load Shaper
logger -p local7.notice -t imslu-scripts "Starting shaper.sh"
/etc/imslu/scripts/shaper.sh

# Static Routing
logger -p local7.notice -t imslu-scripts "Starting static_routing.sh"
/etc/imslu/scripts/static_routing.sh

# Auto find a MAC address or VLAN
killall -q -s 9 imslu_find.sh
logger -p local7.notice -t imslu-scripts "Starting imslu_find.sh"
/etc/imslu/scripts/imslu_find.sh &

if [ ${USE_VLANS} -gt 0 ]; then
    echo 1 > /proc/sys/net/ipv4/conf/${IFACE_INTERNAL}/arp_accept
fi
