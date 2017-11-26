#!/bin/sh

# IMSLU Services
. /usr/local/etc/imslu/config.sh

# Load peer IP addresses
/usr/local/etc/imslu/scripts/peer.sh

# Load Shaper
/usr/local/etc/imslu/scripts/shaper.sh

# Static IP addresses only
if [ ${USE_VLANS} -eq 0 ]; then
    # Static Routing
    /usr/local/etc/imslu/scripts/static_routing.sh
fi
