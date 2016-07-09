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

import sys
import os
import ipcalc
import logging
import datetime
import subprocess
from config import LOG_DIR, TC, FR_EXPIRED, IFACE_EXTERNAL, IFACE_EXTERNAL_VLANS

"""Log settings"""
today = datetime.date.today()
log_file = LOG_DIR + str(today) + "_imslu.log"

logging.basicConfig(filename = log_file,
                    level=logging.INFO,
                    format='%(asctime)s %(name)s %(message)s',
                    datefmt='%b %d %H:%M:%S')

logger = logging.getLogger('ppp-daemon:')
#logger.info('######### Start on rules for PPPoE user. #########')

cmd = []
speed = []
radattr_file = '/var/run/radattr.'+ sys.argv[1]

if os.path.isfile(radattr_file):
    
    if FR_EXPIRED[0].rsplit('.', 1)[0] != sys.argv[2].rsplit('.', 1)[0]:

        with open(radattr_file,"r") as ppp:
            for row in [v.split() for v in ppp]:
                
                if row[0] == "PPPD-Downstream-Speed-Limit":
                    speed += [row[1]]
                if row[0] == "PPPD-Upstream-Speed-Limit":
                    speed += [row[1]]

if speed:
    target_ip = ipcalc.IP(sys.argv[2])
    ipmark_hex = target_ip.hex()[4:8]

    # Proxy ARP
    #cmd += ['echo 1 > /proc/sys/net/ipv4/conf/'+ sys.argv[1] +'/proxy_arp']
    
    # Iproute2 tc - download speed rule
    cmd += [TC +' qdisc add dev '+ sys.argv[1] +' root handle 1: htb']
    cmd += [TC +' filter add dev '+ sys.argv[1] +' parent 1:0 protocol ip prio 1 fw']    
    cmd += [TC +' class replace dev '+ sys.argv[1] +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ speed[0] +'Kbit']
    cmd += [TC +' qdisc add dev '+ sys.argv[1] +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
    
    if IFACE_EXTERNAL_VLANS:
        for vlan_id in IFACE_EXTERNAL_VLANS:
            # Iproute2 tc - upload speed rules for VLANs
            cmd += [TC +' class replace dev vlan'+ vlan_id +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ speed[1] +'Kbit']
            cmd += [TC +' qdisc add dev vlan'+ vlan_id +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
        
    else:
        # Iproute2 tc - upload speed rule
        cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ speed[1] +'Kbit']
        cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
#print cmd
if cmd:
    
    for v in cmd:
        p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()
    
        if err:
            msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)
            
#logger.info('######### End of rules for PPPoE user. #########')