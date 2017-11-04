#!/bin/sh

# WAN Interface
IFACE_EXTERNAL=eth0
IFACE_IMQ0=imq0

# LAN Interface
IFACE_INTERNAL=eth1
IFACE_IMQ1=imq1

STEP=4000

# Log files location
LOG_DIR=/var/log/imslu/

IPTABLES=/sbin/iptables
IP=/sbin/ip
TC=/sbin/tc
IPSET=/sbin/ipset
ARP=/usr/sbin/arp
VTYSH=/usr/bin/vtysh

##### payments #####
# Monthly fee period
FEE_PERIOD="1 month"

##### PPPoE server settings #####
# /bin/false; echo $? # 1
# /bin/true; echo $?  # 0
# false=1
# true=0
USE_PPPoE=0

PPPOE_SERVER=/usr/sbin/pppoe-server
PPPOE_SERVER_NAME=imslu
# Default gateway IP for PPPoE session
PPPOE_DEFAULT_IP="10.0.2.1"

##### FreeRadius settings #####
# FreeRadius log file
FR_LOG_FILE="/var/log/freeradius/radius.log"

##### Traffic shaping settings #####

# tc filter rules work only with /16 subnets
NETWORKS="10.0.0.0/8 172.16.0.0/12 192.168.0.0/16"
# !!! Add all subnets that are used. !!!
SUBNETS="10.0.0.0/24 10.0.1.0/24 10.0.2.0/24 172.16.0.0/24 172.16.1.0/24 172.16.2.0/24 192.168.3.0/24 192.168.4.0/24 192.168.5.0/24"

##### Default routes for static IP addresses route #####
STATIC_ROUTES="10.0.0.1/32 10.0.1.1/32"

##### VLAN settings #####
# /bin/false; echo $? # 1
# /bin/true; echo $?  # 0
# false=1
# true=0
USE_VLANS=0

# VLAN ID range
VLAN_SEQ="2 10 11 $(seq 12 16) $(seq 17 20)"


##### MYSQL Settings #####
MYSQL=/usr/bin/mysql

# database: name of database
database=imslu
# user: database user
user=imslu
# password: database user password
password=imslu_password
# host: database host
host=127.0.0.1
# port: database port
port=3306


# Backup directory
SQL_BACKUP_DIR=/etc/imslu/backup/
# mysqldump location
MYSQLDUMP=/usr/bin/mysqldump
# gzip location
GZIP=/bin/gzip


##### BGP peer - national traffic #####
#PEER="http://ipacct.com/f/peers"
PEER="http://ip.ludost.net/cgi/process?country=1&country_list=bg&format_template=prefix&format_name=&format_target=&format_default="


##### rrdtool #####
RRDTOOL=/usr/bin/rrdtool
RRD_DIR=/var/lib/rrd
RRD_IMG=/tmp/rrd
