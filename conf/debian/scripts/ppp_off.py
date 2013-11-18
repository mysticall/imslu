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
#logger.info('######### Start on clear rules for PPPoE user. #########')

cmd = []
    
if FR_EXPIRED[0].rsplit('.', 1)[0] != sys.argv[2].rsplit('.', 1)[0]:

    target_ip = ipcalc.IP(sys.argv[2])
    ipmark_hex = target_ip.hex()[4:8]
    
    if IFACE_EXTERNAL_VLANS:
        for vlan_id in IFACE_EXTERNAL_VLANS:
            # Iproute2 tc - upload speed rules for VLANs
            cmd += [TC +' class del dev vlan'+ vlan_id +' parent 1: classid 1:'+ ipmark_hex]
        
    else:
        # CLEAR Iproute2 tc - upload speed rule
        cmd += [TC +' class del dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex]
#print cmd
if cmd:
    
    for v in cmd:
        p = subprocess.Popen(v, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()
    
        if err:
            msg = 'Failed: \"'+ str(v) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)
            
#logger.info('######### End of clear rules for PPPoE user. #########')