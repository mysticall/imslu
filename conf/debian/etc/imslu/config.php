<?php
# LAN Interface
$IFACE_INTERNAL = "eth1";
# WAN Interface
$IFACE_EXTERNAL = "eth0";

# Log directory
$LOG_DIR = "/var/log/imslu";

# FreeRadius log file
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
$USE_VLANS = TRUE;

####### Payments #######
#see: http://www.php.net/manual/en/function.strtotime.php
// int Days for temporary internet access
$TEMPORARY_INTERNET_ACCESS = 3;
// str Monthly fee period
$FEE_PERIOD = '1 month';

?>
