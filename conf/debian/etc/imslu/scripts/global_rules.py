#!/usr/bin/env python

# Copyright (c) 2013 IMSLU Developers
	
# This program is free software; you can redistribute it and/or modify it 
# under the terms of the GNU General Public License as published by the 
# Free Software Foundation; either version 2 of the License, or (at you option) 
# any later version.

# This program is distributed in the hope that it will be useful, but 
# WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY 
# or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License 
# for more details.

# You should have received a copy of the GNU General Public License along 
# with this program; if not, write to the Free Software Foundation, Inc., 
# 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

import ipcalc
import logging
import datetime
import subprocess
import functions
from MyConnector import _connect
from config import IFACE_INTERNAL, IFACE_EXTERNAL, LOG_DIR, IPTABLES, IP, TC, ARP_SCAN, SHOW_WARNING_PAGE, IP_WARNING_PAGE, IFACE_WARNING_PAGE, FR_SCRIPT, FR_EXPIRED, FR_NETWORKS, ST_NETWORKS, USE_VLANS, PPPoE_SCRIPT


if USE_VLANS:
    print "\nSet \n\"USE_VLANS = False\" in \"/etc/imslu/scripts/config.py\" \nor use \n\"/etc/imslu/scripts/global_rules_vlans.py\" \n"
    exit()


"""Log settings"""
today = datetime.date.today()
log_file = LOG_DIR + str(today) + "_imslu.log"

logging.basicConfig(filename = log_file,
                    level=logging.INFO,
                    format='%(asctime)s %(name)s %(message)s',
                    datefmt='%b %d %H:%M:%S')

logger = logging.getLogger('global-rules:')
logger.info('######### Start on global rules. #########')


################################################ STOP ################################################
print "Global rules for ISPs started."

"""Stopping PPPoE and Freeradius servers. PPPoE server must stopped first."""
cmd = [PPPoE_SCRIPT +' stop 2>&1']
cmd += [FR_SCRIPT +' stop 2>&1']
for v in cmd:
    p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()
    
    logger.info(str(out[0:-2]))

"""Clearing all IPTABLES rules and user-defined chains."""
logger.info('Clearing all IPTABLES rules and user-defined chains.')

cmd = []
cmd += [IPTABLES +' -F']
cmd += [IPTABLES +' -F -t raw']
cmd += [IPTABLES +' -F -t mangle']
cmd += [IPTABLES +' -F -t nat']
cmd += [IPTABLES +' -X']
cmd += [IPTABLES +' -X -t raw']
cmd += [IPTABLES +' -X -t mangle']
cmd += [IPTABLES +' -X -t nat']
cmd += [IPTABLES +' -Z']
cmd += [IPTABLES +' -Z -t raw']
cmd += [IPTABLES +' -Z -t mangle']
cmd += [IPTABLES +' -Z -t nat']
cmd += [IPTABLES +' -P INPUT ACCEPT']
cmd += [IPTABLES +' -P OUTPUT ACCEPT']
cmd += [IPTABLES +' -P FORWARD ACCEPT']
cmd += [IP +' route flush table EXPIRED']

for v in cmd:
    p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)


"""Clearing all traffic control (tc) rules."""
logger.info('Clearing all IPROUTE2 traffic control (tc) rules.')
        
cmd = [TC +' qdisc del dev '+ IFACE_INTERNAL +' root']
cmd += [TC +' qdisc del dev '+ IFACE_EXTERNAL +' root']
for v in cmd:
    p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)


################################################ START ################################################ 

"""Add IP addresses to internal interface."""
logger.info('Add IP addresses to internal interface.')

cmd = []
cmd += [IP +' link set dev '+ IFACE_INTERNAL +' up']

for gw_ip in ST_NETWORKS:
    cmd += [IP +' address add '+ gw_ip.rsplit('.', 1)[0] +'.1/32 dev '+ IFACE_INTERNAL]

for v in cmd:
    p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)


"""Create a rules for EXPIRED Users."""
logger.info('Create a rules for EXPIRED Users.')

if SHOW_WARNING_PAGE and IP_WARNING_PAGE and IFACE_WARNING_PAGE:
    cmd = [IP +' route replace default via '+ IP_WARNING_PAGE +' dev '+ IFACE_WARNING_PAGE +' table EXPIRED']
    
else:
    cmd = [IP +' route replace blackhole default table EXPIRED']

p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
out, err = p.communicate()

if err:
    msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
    logger.info(msg)


""""Creating a rules for IP status."""
logger.info('Creating a rules for IP status.')

for v in ST_NETWORKS:
    cmd = IPTABLES +' -A FORWARD -s '+ v +' -m limit --limit 1/s --limit-burst 1 -j LOG --log-level info --log-prefix "IP_STATUS: "'
    p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)


"""DROP packets from Private Address Space networks.
logger.info('DROP packets from Private Address Space networks.')

cmd = [IPTABLES +' -N PAS_SRC']

# Rules relating to the Linux server
cmd += [IPTABLES +' -A INPUT -s 127.0.0.1 -d 127.0.0.1 -j ACCEPT']
cmd += [IPTABLES +' -A INPUT -p icmp --icmp-type echo-reply -j ACCEPT']
cmd += [IPTABLES +' -A INPUT -p icmp --icmp-type echo-request -m limit --limit 1/s -j ACCEPT']
cmd += [IPTABLES +' -A INPUT -p tcp --dport 22 -j ACCEPT']
cmd += [IPTABLES +' -A INPUT -p tcp --sport 22 -j ACCEPT']
cmd += [IPTABLES +' -A INPUT -p udp --dport 53 -j ACCEPT']
cmd += [IPTABLES +' -A INPUT -p udp --sport 53 -j ACCEPT']
cmd += [IPTABLES +' -A INPUT -p tcp --dport 80 -j ACCEPT']
cmd += [IPTABLES +' -A INPUT -p tcp --sport 80 -j ACCEPT']
cmd += [IPTABLES +' -A INPUT -p tcp --dport 443 -j ACCEPT']
cmd += [IPTABLES +' -A INPUT -p tcp --sport 443 -j ACCEPT']
cmd += [IPTABLES +' -P INPUT DROP']
# End of rules relating to the Linux server

#cmd += [IPTABLES +' -I INPUT 1 -i '+ IFACE_EXTERNAL +' -j PAS_SRC']
cmd += [IPTABLES +' -A FORWARD -i '+ IFACE_EXTERNAL +' -j PAS_SRC']

cmd += [IPTABLES +' -A PAS_SRC -s 10.0.0.0/8 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 172.16.0.0/12 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 192.168.0.0/16 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 224.0.0.0/4 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 240.0.0.0/5 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 127.0.0.0/8 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 0.0.0.0/8 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -d 255.255.255.255 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 169.254.0.0/16 -j DROP']

cmd += [IPTABLES +' -I FORWARD 1 -d 224.0.0.0/4 -j DROP']

for v in cmd:
    p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)
"""


"""Creating a IPTABLES IPMARK rules. IPMARK extension for IPTABLES must be installed."""
logger.info('Creating a IPTABLES IPMARK rules.')

ipmark = []
ipmark += FR_NETWORKS
ipmark += ST_NETWORKS
for v in ipmark:
    ipmark_up = IPTABLES +' -t mangle -A FORWARD -s '+ v +' -j IPMARK --addr=src --and-mask=0xffff --or-mask=0x10000'
    p = subprocess.Popen(ipmark_up, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(ipmark_up) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)
    
    ipmark_down = IPTABLES +' -t mangle -A FORWARD -d '+ v +' -j IPMARK --addr=dst --and-mask=0xffff --or-mask=0x10000'
    p = subprocess.Popen(ipmark_down, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out2, err2 = p.communicate()

    if err2:
        msg2 = 'Failed: \"'+ str(ipmark_down) +'\" \"'+ str(err2[0:-2]) +'\"'
        logger.info(msg2)

"""Create a globals IPROUTE2 traffic control (tc) rules."""
logger.info('Create a globals IPROUTE2 traffic control (tc) rules.')

cmd = []
cmd += [TC +' qdisc add dev '+ IFACE_INTERNAL +' root handle 1: htb']
cmd += [TC +' filter add dev '+ IFACE_INTERNAL +' parent 1:0 protocol ip prio 1 fw']
cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' root handle 1: htb']
cmd += [TC +' filter add dev '+ IFACE_EXTERNAL +' parent 1:0 protocol ip prio 1 fw']
for v in cmd:
    p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)


"""Create a MASQUERADE rules. Not recommended for networks with many IP addresses!"""
masq = ipmark[:]
logger.info('Create a IPTABLES MASQUERADE rule for networks '+ str(masq) +'.')

for v in masq:

    cmd = IPTABLES +' -t nat -A POSTROUTING -s '+ v +' -o '+ IFACE_EXTERNAL +' -j MASQUERADE'
    p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)


"""Start Freeradius and PPPoE servers."""
cmd = [FR_SCRIPT +' start 2>&1']
cmd += [PPPoE_SCRIPT +' start 2>&1']
for v in cmd:
    p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()
    
    logger.info(str(out[0:-2]))


"""Start on routing and traffic control rules for clients."""
logger.info('Start on routing and traffic control rules for clients.')

cnx = _connect()
cursor = cnx.cursor()

# Add rules for users who have mac, free_mac=0
query = "SELECT static_ippool.ipaddress, static_ippool.subnet, static_ippool.mac, traffic.local_in, traffic.local_out \
FROM static_ippool LEFT JOIN traffic ON static_ippool.trafficid = traffic.trafficid \
WHERE static_ippool.userid != 0 AND static_ippool.trafficid != 0 AND static_ippool.mac not like '' AND static_ippool.free_mac=0"
cursor.execute(query)
rows = cursor.fetchall()

if rows:

    cmd = []
    for row in rows:
        
        cmd += [IP +' route replace '+ row[0] +'/'+ row[1] +' dev '+ IFACE_INTERNAL +' src '+ row[0].rsplit('.', 1)[0] +'.1']
        cmd += [IP +' neighbor replace '+ row[0] +' lladdr '+ row[2] +' dev '+ IFACE_INTERNAL +' nud permanent']
        
        target_ip = ipcalc.IP(row[0])
        ipmark_hex = target_ip.hex()[4:8]
        
        # Iproute2 tc - download speed rule
        cmd += [TC +' class add dev '+ IFACE_INTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
        cmd += [TC +' qdisc add dev '+ IFACE_INTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
        
        # Iproute2 tc - upload speed rule
        cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[4] +'Kbit']
        cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']

    for v in cmd:
        p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()

        if err:
            msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)

# Add rules for users who have Free MAC
query = "SELECT static_ippool.ipaddress, static_ippool.subnet, traffic.local_in, traffic.local_out \
FROM static_ippool LEFT JOIN traffic ON static_ippool.trafficid = traffic.trafficid \
WHERE static_ippool.userid != 0 AND static_ippool.trafficid != 0 AND static_ippool.free_mac=1"
cursor.execute(query)
rows = cursor.fetchall()

cnx.close()

if rows:

    cmd = []
    for row in rows:
        
        cmd += [IP +' route replace '+ row[0] +'/'+ row[1] +' dev '+ IFACE_INTERNAL +' src '+ row[0].rsplit('.', 1)[0] +'.1']
        
        target_ip = ipcalc.IP(row[0])
        ipmark_hex = target_ip.hex()[4:8]
        
        # Iproute2 tc - download speed rule
        cmd += [TC +' class add dev '+ IFACE_INTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[2] +'Kbit']
        cmd += [TC +' qdisc add dev '+ IFACE_INTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
        
        # Iproute2 tc - upload speed rule
        cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
        cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']

    for v in cmd:
        p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()

        if err:
            msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)


# Checking for expired users
functions.check_expired()

# Searching for IP addresses who not have MAC
functions.find_mac()

print "Global rules for ISPs completed."
logger.info('######### End of globals rules. #########')
