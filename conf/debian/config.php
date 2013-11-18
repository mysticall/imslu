<?php
# LAN Interface
$IFACE_INTERNAL = "eth0";
# WAN Interface
$IFACE_EXTERNAL = "eth1";

# Log files location
$LOG_DIR = "/var/log/imslu";

# FreeRadius log files location
$FR_LOG_FILE = "/var/log/freeradius/radius.log";

# str IMSLU scripts directory patch
$IMSLU_SCRIPTS = '/etc/imslu/scripts';

# str sudo patch
$SUDO = '/usr/bin/sudo';
# str python patch
$PYTHON = '/usr/bin/python';
# str ipset patch
$IP = '/sbin/ip';
# str kill patch
$KILL = '/bin/kill';
# str ping patch
$PING = '/bin/ping';
# str arping patch
$ARPING = '/usr/sbin/arping';

# Do you want to use PPPoE - Freeradius?
# boolean FALSE or TRUE
$USE_PPPoE = TRUE;

# Do you want to use VLANs?
# boolean FALSE or TRUE
$USE_VLANS = FALSE;

# int - Days for limited internet acces
$LIMITED_INTERNET_ACCESS = 3;
?>
