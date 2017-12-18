#!/bin/sh

logger -p local7.notice -t imslu-scripts "Starting imslu."

# IMSLU Services
. /usr/local/etc/imslu/config.sh

# Load peer IP addresses
logger -p local7.notice -t imslu-scripts "Starting peer.sh"
/usr/local/etc/imslu/scripts/peer.sh

# Load Shaper
logger -p local7.notice -t imslu-scripts "Starting shaper.sh"
/usr/local/etc/imslu/scripts/shaper.sh

# Static Routing
logger -p local7.notice -t imslu-scripts "Starting static_routing.sh"
/usr/local/etc/imslu/scripts/static_routing.sh &
