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
import logging
import datetime
import subprocess
from config import LOG_DIR, IPTABLES, IP, TC, FR_EXPIRED, FR_NETWORKS

"""Log settings"""
today = datetime.date.today()
log_file = LOG_DIR + str(today) + "_imslu.log"

logging.basicConfig(filename = log_file,
                    level=logging.INFO,
                    format='%(asctime)s %(name)s %(message)s',
                    datefmt='%b %d %H:%M:%S')

logger = logging.getLogger('pppd-kill:')

if len(sys.argv) > 1:
    cmd = IP +' address show | awk \'{ if ($4 == "'+ sys.argv[1] +'/32") print $7;}\' | (read IFACE; if [ -f /var/run/$IFACE.pid ]; then PID=`cat /var/run/$IFACE.pid`; kill -HUP $PID; fi;)'
    p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()
    
    if err:
        msg = 'Failed: \"'+ str(cmd) +'\" \"'+ str(err[0:-2]) +'\"'
        logger.info(msg)
        
else:
    print "Usage: \n /etc/imslu/scripts/pppd_kill.py IP_address"