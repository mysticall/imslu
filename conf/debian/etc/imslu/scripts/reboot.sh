#!/bin/sh

. /etc/imslu/config.sh
. /etc/imslu/scripts/functions.sh

if [ ${USE_VLANS} -eq 0 ]; then

    vconfig_rem
elif [ ${USE_PPPoE} -eq 0 ]; then

    pppoe_stop
fi

logger -p local7.notice -t imslu-scripts "The server is rebooting."
reboot
