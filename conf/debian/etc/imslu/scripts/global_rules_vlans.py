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
#import time
import datetime
import subprocess
import functions
from MyConnector import _connect
from config import IFACE_INTERNAL, IFACE_EXTERNAL, LOG_DIR, IPTABLES, IP, TC, ARP_SCAN, SHOW_WARNING_PAGE, IP_WARNING_PAGE, IFACE_WARNING_PAGE, FR_SCRIPT, FR_EXPIRED, FR_NETWORKS, ST_NETWORKS, USE_VLANS, IFACE_INTERNAL_VLANS, IFACE_EXTERNAL_VLANS, PPPoE_SCRIPT


if not USE_VLANS:
    print "\nSet \n\"USE_VLANS = True\" in \"/etc/imslu/scripts/config.py\" \nor use \n\"/etc/imslu/scripts/global_rules.py\" \n"
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

cmd = [IPTABLES +' -F']
cmd += [IPTABLES +' -F -t raw']
cmd += [IPTABLES +' -F -t mangle']
#cmd += [IPTABLES +' -F -t nat']
cmd += [IPTABLES +' -X']
cmd += [IPTABLES +' -X -t raw']
cmd += [IPTABLES +' -X -t mangle']
#cmd += [IPTABLES +' -X -t nat']
cmd += [IPTABLES +' -Z']
cmd += [IPTABLES +' -X -t raw']
cmd += [IPTABLES +' -Z -t mangle']
#cmd += [IPTABLES +' -Z -t nat']
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


"""Checking for VLANs on internal interface and deleting."""
logger.info('Checking for VLANs on internal interface and deleting.')

cmd = IP +' link show dev vlan'+ IFACE_INTERNAL_VLANS[0]
p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
out, err = p.communicate()

if out:
    msg = 'Found VLAN: \"'+ str(out[:16]) +'\" \"DELETE all VLANs on internal interface!!!\"'
    logger.info(msg)
    
    for v in IFACE_INTERNAL_VLANS:
        cmd = IP +' link del vlan'+ v
        
        p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()
        
        if err:
            msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)        

else:
    msg = 'NOT found VLANs: \"'+ str(err[0:-2]) +'\"'
    logger.info(msg)


"""Clearing all traffic control (tc) rules for VLANs on external interface."""
logger.info('Clearing all traffic control (tc) rules for VLANs on external interface.')

if IFACE_EXTERNAL_VLANS:
    
    for v in IFACE_EXTERNAL_VLANS:
        cmd = TC +' qdisc del dev vlan'+ v +' root'
        
        p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()
        
        if err:
            msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)    

else:
    cmd = TC +' qdisc del dev '+ IFACE_EXTERNAL +' root'
    p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()
    
    if err:
        msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)


################################################ START ################################################

"""Create VLANs on internal interface."""
logger.info('Create VLANs on internal interface.')

cmd = ['modprobe 8021q']

for vlan_id in IFACE_INTERNAL_VLANS:

    cmd += [IP +' link add link '+ IFACE_INTERNAL +' name vlan'+ vlan_id +' type vlan id '+ vlan_id]
    cmd += [IP +' link set dev vlan'+ vlan_id +' up']
    
    for gw_ip in ST_NETWORKS:
        cmd += [IP +' address add '+ gw_ip.rsplit('.', 1)[0] +'.1/32 dev vlan'+ vlan_id]
    
    cmd += ['echo 1 > /proc/sys/net/ipv4/conf/vlan'+ vlan_id +'/proxy_arp']
    
for v in cmd:
    p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)


"""Create VLANs on external interface."""
logger.info('Create VLANs on external interface.')

if IFACE_EXTERNAL_VLANS:
    
    cmd = []
    for vlan_id in IFACE_EXTERNAL_VLANS:
        
        cmd += [IP +' link add link '+ IFACE_EXTERNAL +' name vlan'+ vlan_id +' type vlan id '+ vlan_id]
        cmd += [IP +' link set dev vlan'+ vlan_id +' up']
    
    for v in cmd:
        p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()

        if err:
            msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)


"""Creating a rules for EXPIRED Users."""
logger.info('Creating a rules for EXPIRED Users.')

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


"""DROP packets for Private Address Space networks, coming to external interface.
logger.info('DROP packets for Private Address Space networks, coming to external interface.')

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

if IFACE_EXTERNAL_VLANS:

    for v in IFACE_EXTERNAL_VLANS:
        #cmd += [IPTABLES +' -I INPUT 1 -i '+ v +' -j PAS_SRC']
        cmd += [IPTABLES +' -I FORWARD 1 -i '+ v +' -j PAS_SRC']
        
else:
    #cmd += [IPTABLES +' -I INPUT 1 -i '+ IFACE_EXTERNAL +' -j PAS_SRC']
    cmd += [IPTABLES +' -I FORWARD 1 -i '+ IFACE_EXTERNAL +' -j PAS_SRC']

cmd += [IPTABLES +' -A PAS_SRC -s 10.0.0.0/8 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 172.16.0.0/12 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 192.168.0.0/16 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 224.0.0.0/4 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 240.0.0.0/5 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 127.0.0.0/8 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 0.0.0.0/8 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -d 255.255.255.255 -j DROP']
cmd += [IPTABLES +' -A PAS_SRC -s 169.254.0.0/16 -j DROP']

cmd += [IPTABLES +' -I FORWARD 2 -d 224.0.0.0/4 -j DROP']

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
    out, err2 = p.communicate()

    if err2:
        msg2 = 'Failed: \"'+ str(ipmark_down) +'\" \"'+ str(err2[0:-2]) +'\"'
        logger.info(msg2)


"""Creating a globals traffic control (tc) rules for VLANs on internal interface."""
logger.info('Creating a globals traffic control (tc) rules for VLANs on internal interface.')

cmd = []
for vlan_id in IFACE_INTERNAL_VLANS:
    cmd += [TC +' qdisc add dev vlan'+ vlan_id +' root handle 1: htb']
    cmd += [TC +' filter add dev vlan'+ vlan_id +' parent 1:0 protocol ip prio 1 fw']
    
for v in cmd:
    p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)


"""Creating a globals traffic control (tc) rules for VLANs on external interface."""
logger.info('Creating a globals traffic control (tc) rules for VLANs on external interface.')

cmd = []
if IFACE_EXTERNAL_VLANS:
    
    for vlan_id in IFACE_EXTERNAL_VLANS:
        cmd += [TC +' qdisc add dev vlan'+ vlan_id +' root handle 1: htb']
        cmd += [TC +' filter add dev vlan'+ vlan_id +' parent 1:0 protocol ip prio 1 fw']

else:
    cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' root handle 1: htb']
    cmd += [TC +' filter add dev '+ IFACE_EXTERNAL +' parent 1:0 protocol ip prio 1 fw']
    
for v in cmd:
    p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()

    if err:
        msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)


"""Start Freeradius and PPPoE servers"""
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

# Add rules for users who have vlan, mac, free_mac=0
query = "SELECT static_ippool.ipaddress, static_ippool.subnet, static_ippool.vlan, static_ippool.mac, traffic.local_in, traffic.local_out \
FROM static_ippool LEFT JOIN traffic ON static_ippool.trafficid = traffic.trafficid \
WHERE static_ippool.userid != 0 AND static_ippool.trafficid != 0 AND static_ippool.vlan not like '' AND static_ippool.mac not like '' AND static_ippool.free_mac=0"
cursor.execute(query)
rows = cursor.fetchall()

if rows:

    cmd = []
    for row in rows:
        
        cmd += [IP +' route replace '+ row[0] +'/'+ row[1] +' dev '+ row[2] +' src '+ row[0].rsplit('.', 1)[0] +'.1']
        cmd += [IP +' neighbor replace '+ row[0] +' lladdr '+ row[3] +' dev '+ row[2] +' nud permanent']
        
        target_ip = ipcalc.IP(row[0])
        ipmark_hex = target_ip.hex()[4:8]
        
        # Iproute2 tc - download speed rule
        cmd += [TC +' class add dev '+ row[2] +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[4] +'Kbit']
        cmd += [TC +' qdisc add dev '+ row[2] +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
        
        if IFACE_EXTERNAL_VLANS:
            for vlan_id in IFACE_EXTERNAL_VLANS:
                # Iproute2 tc - upload speed rules for VLANs
                cmd += [TC +' class replace dev vlan'+ vlan_id +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[5] +'Kbit']
                cmd += [TC +' qdisc add dev vlan'+ vlan_id +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
            
        else:
            # Iproute2 tc - upload speed rule
            cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[5] +'Kbit']
            cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']

    for v in cmd:
        p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()

        if err:
            msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)

# Add rules for users who have vlan and Free MAC
query = "SELECT static_ippool.ipaddress, static_ippool.subnet, static_ippool.vlan, traffic.local_in, traffic.local_out \
FROM static_ippool LEFT JOIN traffic ON static_ippool.trafficid = traffic.trafficid \
WHERE static_ippool.userid != 0 AND static_ippool.trafficid != 0 AND static_ippool.vlan not like '' AND static_ippool.free_mac=1"
cursor.execute(query)
rows = cursor.fetchall()

cnx.close()

if rows:

    cmd = []
    for row in rows:
        
        cmd += [IP +' route replace '+ row[0] +'/'+ row[1] +' dev '+ row[2] +' src '+ row[0].rsplit('.', 1)[0] +'.1']
        
        target_ip = ipcalc.IP(row[0])
        ipmark_hex = target_ip.hex()[4:8]
        
        # Iproute2 tc - download speed rule
        cmd += [TC +' class add dev '+ row[2] +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
        cmd += [TC +' qdisc add dev '+ row[2] +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
        
        if IFACE_EXTERNAL_VLANS:
            for vlan_id in IFACE_EXTERNAL_VLANS:
                # Iproute2 tc - upload speed rules for VLANs
                cmd += [TC +' class replace dev vlan'+ vlan_id +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[4] +'Kbit']
                cmd += [TC +' qdisc add dev vlan'+ vlan_id +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
            
        else:
            # Iproute2 tc - upload speed rule
            cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[4] +'Kbit']
            cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']

    for v in cmd:
        p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()

        if err:
            msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)


# Checking for expired users
functions.check_expired()

# Searching for IP addresses who not have VLAN,MAC
functions.find_mac_in_vlan()
functions.find_vlan_for_mac()
functions.find_vlan_for_free_mac()
functions.find_vlan_mac()

print "Global rules for ISPs completed."
logger.info('######### End of globals rules. #########')