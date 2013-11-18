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

import logging
import datetime
import time
import subprocess
from config import LOG_DIR, database, user, password, host, port, SQL_BACKUP_DIR, MYSQLDUMP, GZIP


"""Log settings"""
today = datetime.date.today()
now = time.strftime("%Y-%m-%d_%H-%M-%S")
#now = datetime.datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
log_file = LOG_DIR + str(today) + "_imslu.log"

logging.basicConfig(filename = log_file,
                    level=logging.INFO,
                    format='%(asctime)s %(name)s %(message)s',
                    datefmt='%b %d %H:%M:%S')

logger = logging.getLogger('mysqldump:')

args = [database, user, password, host, port, SQL_BACKUP_DIR, MYSQLDUMP, GZIP, now]

def mysqldump(tabe_name=None, info='_', *args):
    
    cmd = []
    
    if (len(tabe_name) == 1):
        backup_file = tabe_name + info + now +'.sql'
        cmd += [MYSQLDUMP +' -t --compact '+ database +' -u '+ user +' -p'+ password +' '+ tabe_name +' > '+ SQL_BACKUP_DIR + backup_file]
        cmd += ['cd '+ SQL_BACKUP_DIR +' && '+ GZIP +' '+ backup_file]
    
    if (len(tabe_name) > 1):
        backup_file = database + info + now +'.sql'
        cmd += [MYSQLDUMP +' --compact '+ database +' -u '+ user +' -p'+ password +' '+ tabe_name +' > '+ SQL_BACKUP_DIR + backup_file]
        cmd += ['cd '+ SQL_BACKUP_DIR +' && '+ GZIP +' '+ backup_file]

    else:
        backup_file = database + info + now +'.sql'
        cmd += [MYSQLDUMP +' -t --compact '+ database +' -u '+ user +' -p'+ password +' > '+ SQL_BACKUP_DIR + backup_file]
        cmd += ['cd '+ SQL_BACKUP_DIR +' && '+ GZIP +' '+ backup_file]
    
    for rule in cmd:
        p = subprocess.Popen(rule, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()

        if err:
            msg = 'Failed: \"'+ str(rule) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg) 
    
            print err