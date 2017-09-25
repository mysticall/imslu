#!/bin/sh -

# WAN Interface
IFACE_EXTERNAL=re0

# LAN Interface
IFACE_INTERNAL=re1

STEP=4000

IMSLU_SCRIPTS=/usr/local/etc/imslu/scripts
# Log files location
LOG_DIR=/var/log/imslu/

IFCONFIG=/sbin/ifconfig
ROUTE=/sbin/route
IPFW=/sbin/ipfw
ARP=/usr/sbin/arp

##### VLAN settings #####
# /bin/false; echo $? # 1
# /bin/true; echo $?  # 0
# false=1
# true=0
USE_VLANS=0

####### payments #######
#see: https://www.freebsd.org/cgi/man.cgi?query=date
# Monthly fee period
FEE_PERIOD=1m

##### MYSQL Settings #####
MYSQL=/usr/local/bin/mysql

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
SQL_BACKUP_DIR=/usr/local/etc/imslu/backup

# mysqldump location
MYSQLDUMP=/usr/local/bin/mysqldump

# gzip location
GZIP=/bin/gzip


##### BGP peer - national traffic #####
# see: https://ip.ludost.net/ for other country or use another config file
PEER="http://ip.ludost.net/cgi/process?country=1&country_list=bg&format_template=prefix&format_name=&format_target=&format_default="
#PEER="http://ipacct.com/f/peers"

##### rrdtool #####
RRDTOOL=/usr/local/bin/rrdtool
RRD_DIR=/var/lib/rrd
RRD_IMG=/tmp/rrd
