<?php
# LAN Interface
$IFACE_INTERNAL = "igb1";
# WAN Interface
$IFACE_EXTERNAL = "igb0";

# Log files location
$LOG_DIR = "/var/log/imslu";

# FreeRadius log files location
$FR_LOG_FILE = "/var/log/freeradius/radius.log";

# str IMSLU scripts directory patch
$IMSLU_SCRIPTS = '/usr/local/etc/imslu/scripts';

# str sudo patch
$SUDO = '/usr/local/bin/sudo';

# str kill patch
$KILL = '/bin/kill';
# str ping patch
$PING = '/sbin/ping';
# str arping patch
$ARPING = '/usr/sbin/arping';

# Do you want to use PPPoE - Freeradius?
# boolean FALSE or TRUE
$USE_PPPoE = TRUE;

# Do you want to use VLANs?
# boolean FALSE or TRUE
$USE_VLANS = TRUE;

# int - Days for limited internet acces
$LIMITED_INTERNET_ACCESS = 3;
?>
