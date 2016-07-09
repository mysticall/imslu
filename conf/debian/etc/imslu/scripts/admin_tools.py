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
from MyConnector import _connect
from config import IFACE_INTERNAL, IFACE_EXTERNAL, LOG_DIR, IPTABLES, IP, TC, ARP_SCAN, FR_EXPIRED, FR_NETWORKS, ST_NETWORKS, USE_VLANS, IFACE_INTERNAL_VLANS, IFACE_EXTERNAL_VLANS

"""Log settings"""
today = datetime.date.today()
log_file = LOG_DIR + str(today) + "_imslu.log"

logging.basicConfig(filename = log_file,
                    level=logging.INFO,
                    format='%(asctime)s %(name)s %(message)s',
                    datefmt='%b %d %H:%M:%S')

logger = logging.getLogger('admin-tools:')

args = [logger, subprocess, ipcalc, IFACE_INTERNAL, IFACE_EXTERNAL, LOG_DIR, IPTABLES, IP, TC, ARP_SCAN, IFACE_INTERNAL_VLANS, IFACE_EXTERNAL_VLANS]

def add_ip_rules(ip=None, vlan=None, mac=None, free_mac='0', donw_speed=None, up_speed=None, *args):
    
    # If you use VLANs
    if USE_VLANS and ip and vlan and donw_speed and up_speed:
        
        cmd = []
        cmd += [IP +' route replace '+ ip +'/32 dev '+ vlan +' src '+ ip.rsplit('.', 1)[0] +'.1']
        
        if mac and free_mac == '0':
            cmd += [IP +' neighbor replace '+ ip +' lladdr '+ mac +' dev '+ vlan +' nud permanent']
        
        target_ip = ipcalc.IP(ip)
        ipmark_hex = target_ip.hex()[4:8]
        
        # Iproute2 tc - download speed rule
        cmd += [TC +' class add dev '+ vlan +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ donw_speed +'Kbit']
        cmd += [TC +' qdisc add dev '+ vlan +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
        
        if IFACE_EXTERNAL_VLANS:
            for vlan_id in IFACE_EXTERNAL_VLANS:
                # Iproute2 tc - upload speed rules for VLANs
                cmd += [TC +' class replace dev vlan'+ vlan_id +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ up_speed +'Kbit']
                cmd += [TC +' qdisc add dev vlan'+ vlan_id +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
            
        else:
            # Iproute2 tc - upload speed rule
            cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ up_speed +'Kbit']
            cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']

        for v in cmd:
            p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            out, err = p.communicate()
    
            if err:
                msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
                logger.info(msg)
    
    # If you not use VLANs
    if USE_VLANS == False and ip and donw_speed and up_speed:
        
        cmd = []
        cmd += [IP +' route replace '+ ip +'/32 dev '+ IFACE_INTERNAL +' src '+ ip.rsplit('.', 1)[0] +'.1']
        
        if mac and free_mac == '0':
            cmd += [IP +' neighbor replace '+ ip +' lladdr '+ mac +' dev '+ IFACE_INTERNAL +' nud permanent']
        
        target_ip = ipcalc.IP(ip)
        ipmark_hex = target_ip.hex()[4:8]
        
        # Iproute2 tc - download speed rule
        cmd += [TC +' class replace dev '+ IFACE_INTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ donw_speed +'Kbit']
        cmd += [TC +' qdisc add dev '+ IFACE_INTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']

        # Iproute2 tc - upload speed rule
        cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ up_speed +'Kbit']
        cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']

        for v in cmd:
            p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            out, err = p.communicate()
    
            if err:
                msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
                logger.info(msg)


def del_ip_rules(ip=None, vlan=None, mac=None, *args):
    
    # If you use VLANs
    if USE_VLANS and ip and vlan:
        
        cmd = []
        cmd += [IP +' route del '+ ip +'/32 dev '+ vlan]
        
        if mac:
            cmd += [IP +' neighbor del '+ ip +' lladdr '+ mac +' dev '+ vlan]
        
        target_ip = ipcalc.IP(ip)
        ipmark_hex = target_ip.hex()[4:8]
        
        # Iproute2 tc - clear download speed rule
        cmd += [TC +' class del dev '+ vlan +' parent 1: classid 1:'+ ipmark_hex]
        
        if IFACE_EXTERNAL_VLANS:
            for vlan_id in IFACE_EXTERNAL_VLANS:
                # Iproute2 tc - clear upload speed rules for VLANs
                cmd += [TC +' class del dev vlan'+ vlan_id +' parent 1: classid 1:'+ ipmark_hex]
            
        else:
            # Iproute2 tc - clear upload speed rule
            cmd += [TC +' class del dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex]

        for v in cmd:
            p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            out, err = p.communicate()
    
            if err:
                msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
                logger.info(msg)


    # If you not use VLANs
    if USE_VLANS == False and ip:
        
        cmd = []
        cmd += [IP +' route del '+ ip +'/32 dev '+ IFACE_INTERNAL]
        
        if mac:
            cmd += [IP +' neighbor del '+ ip +' lladdr '+ mac +' dev '+ IFACE_INTERNAL]
        
        target_ip = ipcalc.IP(ip)
        ipmark_hex = target_ip.hex()[4:8]
        
        # Iproute2 tc - clear download speed rule
        cmd += [TC +' class del dev '+ IFACE_INTERNAL +' parent 1: classid 1:'+ ipmark_hex]

        # Iproute2 tc - upload speed rule
        cmd += [TC +' class del dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex]

        for v in cmd:
            p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            out, err = p.communicate()
    
            if err:
                msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
                logger.info(msg)


def replace_mac(ip=None, vlan=None, mac=None, free_mac='0', *args):
    # If you use VLANs
    if USE_VLANS and ip and vlan and mac and free_mac == '0':
        
        cmd = IP +' neighbor replace '+ ip +' lladdr '+ mac +' dev '+ vlan +' nud permanent'
        
        p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()

        if err:
            msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)
       
    # If you not use VLANs
    if USE_VLANS == False and ip and mac and free_mac == '0':
        
        cmd = IP +' neighbor replace '+ ip +' lladdr '+ mac +' dev '+ IFACE_INTERNAL +' nud permanent'
        
        p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()

        if err:
            msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)


def del_mac(ip=None, vlan=None, mac=None, *args):
    
    # If you use VLANs
    if USE_VLANS and ip and vlan and mac:
        
        cmd = IP +' neighbor del '+ ip +' lladdr '+ mac +' dev '+ vlan
        
        p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()

        if err:
            msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)
    
    # If you not use VLANs
    if USE_VLANS == False and ip and mac:
        
        cmd = IP +' neighbor del '+ ip +' lladdr '+ mac +' dev '+ IFACE_INTERNAL
        
        p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()

        if err:
            msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)        
