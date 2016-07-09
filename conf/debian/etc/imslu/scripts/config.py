"""IMSLU freeradius, static ip management, traffic control and Mysql configuration file"""

# LAN Interface
IFACE_INTERNAL = "eth0"
# WAN Interface
IFACE_EXTERNAL = "eth1"

# Log files location
LOG_DIR = "/var/log/imslu/"

IPTABLES = "/sbin/iptables"
# Iproute2 ip location
IP = "/sbin/ip"
# Iproute2 tc location
TC = "/sbin/tc"
ARP_SCAN = "/usr/bin/arp-scan"

# Do you want, to show warning page for expired users
# False = "ip route replace blackhole default table EXPIRED"
# True = "ip route replace default via IP_WARNING_PAGE dev IFACE_WARNING_PAGE table EXPIRED"
# Boolean: False or True
SHOW_WARNING_PAGE = False
# IP address for expired users who will use as default GW
IP_WARNING_PAGE = None
# Of which interface is IP address
IFACE_WARNING_PAGE = None

"""PPPoE server settings"""
# PPPoE server init.d scrip location
PPPoE_SCRIPT = "/etc/init.d/pppoe-server"

"""Freeradius settings"""
# Freeradius init.d scrip location
FR_SCRIPT = "/etc/init.d/freeradius"

# Freeradius networks for expired users
FR_EXPIRED = ['192.168.11.0/24']
#FR_EXPIRED += ['192.168.12.0/24']

# Freeradius networks for active users
FR_NETWORKS = ['10.111.1.0/24']
#FR_NETWORKS += ['10.111.3.0/24']

"""Static IP Address settings"""
ST_NETWORKS = ['10.111.2.0/24']
#ST_NETWORKS += ['10.111.4.0/24']

"""VLAN settings"""
# Boolean: False or True
USE_VLANS = False

# Vlan ID of internal interface
IFACE_INTERNAL_VLANS = []
# Example:
#IFACE_INTERNAL_VLANS += ['10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25']
#IFACE_INTERNAL_VLANS += ['26', '27', '28', '29', '30', '31', '32', '33']

# Vlan ID of external interface
IFACE_EXTERNAL_VLANS = []
# Example:
#IFACE_EXTERNAL_VLANS += ['47', '48']

"""They are used by functions in functions.py"""
# Integer - seconds
INTERVAL_FOR_CHECKING = 10

# Positive integer
CHECK_AGAIN = 1


""" MYSQL Settings"""
# database: name of database
database = "imslu"

# user: database user
user = "imslu"

# password: database user password
password = "imslu_password"

# host: database host
host = "127.0.0.1"

# port: database port
port = 3306

# Backup directory
SQL_BACKUP_DIR = "/etc/imslu/backup/"

# mysqldump location
MYSQLDUMP = "/usr/bin/mysqldump"

# gzip location
GZIP = "/bin/gzip"
