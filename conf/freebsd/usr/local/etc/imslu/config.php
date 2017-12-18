<?php
# LAN Interface
$IFACE_INTERNAL = "igb1";
# WAN Interface
$IFACE_EXTERNAL = "igb0";

# Log file
$LOG_FILE = "/var/log/imslu.log";

# FreeRadius log file
$FR_LOG_FILE = "/var/log/radius.log";

# str IMSLU scripts directory patch
$IMSLU_SCRIPTS = '/usr/local/etc/imslu/scripts';

# str sudo patch
$SUDO = '/usr/local/bin/sudo';

# str kill patch
$KILL = '/bin/kill';
# str ping patch
$PING = '/sbin/ping';
# str arping patch
$ARPING = '/usr/local/sbin/arping';

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
