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
import time
import subprocess
from MyConnector import _connect
from config import IFACE_INTERNAL, IFACE_EXTERNAL, LOG_DIR, IPTABLES, IP, TC, ARP_SCAN, FR_EXPIRED, IFACE_INTERNAL_VLANS, IFACE_EXTERNAL_VLANS, INTERVAL_FOR_CHECKING, CHECK_AGAIN

"""Log settings"""
today = datetime.date.today()
log_file = LOG_DIR + str(today) + "_imslu.log"

logging.basicConfig(filename = log_file,
                    level=logging.INFO,
                    format='%(asctime)s %(name)s %(message)s',
                    datefmt='%b %d %H:%M:%S')

logger = logging.getLogger('functions:')

""" !!! WARNING all funcions tested on Debian Wheezy with arp-scan verison 1.8.1!!!
    !!! If arp-scan output format are change the functions will not work. Please see: !!!
    if out[-12:-11] == "1":
        found = out.splitlines()[2:-3]
        found = found[0].split('\t', 2)
"""
args = [logger, datetime, time, subprocess, ipcalc, _connect, IFACE_INTERNAL, IFACE_EXTERNAL, LOG_DIR, IPTABLES, IP, TC, ARP_SCAN, INTERVAL_FOR_CHECKING, IFACE_INTERNAL_VLANS, IFACE_EXTERNAL_VLANS]

# Checking for users who are not excluded
def check_not_excluding(*args):
    
    print "Started checking for users who are not excluded."
    logger.info('######### Started checking for users who are not excluded. #########')
    
    cnx = _connect()
    cursor = cnx.cursor()
    
    # This query is tested on 5.5.30-MariaDB-mariadb1~wheezy
    query = "SELECT users.userid, users.name, users.pay, traffic.price, radcheck.username, payments.expires \
    FROM users \
    INNER JOIN (SELECT userid, expires FROM (SELECT userid, expires FROM payments GROUP BY userid, expires DESC) AS p GROUP BY userid) AS payments \
    ON users.userid = payments.userid AND payments.expires < '"+ time.strftime("%Y-%m-%d") +" 00:00:00'\
    LEFT JOIN (SELECT trafficid, price FROM traffic) AS traffic \
    ON users.trafficid = traffic.trafficid \
    LEFT JOIN (SELECT userid, username FROM radcheck GROUP BY username) AS radcheck \
    ON users.userid = radcheck.userid \
    WHERE users.free_access = 0 AND users.not_excluding = 1"
    
    cursor.execute(query)
    rows = cursor.fetchall()    
    #print str(rows)

    if rows:
        
        msg = 'Found: '+ str(rows)
        logger.info(msg)
        
        values = tuple()
        
        for row in rows:
            
            if (row[2] == '0.00'):
                pay = row[3]
            else:
                pay = row[2]
            
            if (row[4] == None):
                username = ''
            else:
                username = row[4]
            
            expires = time.strptime(row[5], "%Y-%m-%d %H:%M:%S")
            next_month = datetime.datetime(expires.tm_year + (expires.tm_mon / 12), ((expires.tm_mon % 12) + 1), expires.tm_mday, expires.tm_hour, expires.tm_min, 0)
            #print next_month.strftime("%Y-%m-%d %H:%M:%S")
            
            values += (row[0], row[1], username, 1, 'system', time.strftime("%Y-%m-%d %H:%M:%S"), next_month.strftime("%Y-%m-%d %H:%M:%S"), pay, 'This payment is added automatically by the system for users who are not excluded.'),
            
        stmt_insert = "INSERT INTO `payments` (`userid`, `name`, `username`, `unpaid`, `operator1`, `date_payment1`, `expires`, `sum`, `notes`) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)"
        cursor.executemany(stmt_insert, values)
        cnx.commit()
    
    cnx.close()
    print "End of checking for users who are not excluded."
    logger.info('######### End of checking for users who are not excluded. #########')

# Checking for expired users
def check_expired(*args):
    
    print "Started checking for expired users."
    logger.info('######### Started checking for expired users. #########')
    
    cnx = _connect()
    cursor = cnx.cursor()
    
    # This query is tested on 5.5.30-MariaDB-mariadb1~wheezy
    query = "SELECT static_ippool.ipaddress, static_ippool.subnet \
    FROM `static_ippool` \
    INNER JOIN (SELECT userid FROM `users` WHERE free_access = 0) AS users \
    ON static_ippool.userid = users.userid \
    INNER JOIN (SELECT userid, expires FROM (SELECT userid, expires FROM payments GROUP BY userid, expires DESC) AS p GROUP BY userid) AS payments \
    ON static_ippool.userid = payments.userid AND payments.expires < '"+ time.strftime("%Y-%m-%d") +" 00:00:00'"
    
    cursor.execute(query)
    rows = cursor.fetchall()
    #print str(rows)
    
    cmd = []
    # Delete all IP Addresses in RPDM, table EXPIRED
    exp = IP +' rule show | awk \'{ if ($5 == "EXPIRED") print substr($1,1,length($1)-1);}\''
    p = subprocess.Popen(exp, shell=True, stdout=subprocess.PIPE)
    out = p.communicate()[0].split('\n')[0:-1]
    
    if out:
        for v in out:
            cmd += [IP +' rule del prio '+ v]
    
    for v in FR_EXPIRED:
        cmd += [IP +' rule add from '+ v +' lookup EXPIRED']
        
    if rows:
        msg = 'Found: '+ str(rows)
        logger.info(msg)
     
        for row in rows:
            cmd += [IP +' rule add from '+ row[0] +'/'+ row[1] +' lookup EXPIRED']
    
    cmd += [IP +' route flush cache']
    for rule in cmd:
        p = subprocess.Popen(rule, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = p.communicate()
        
        if err:
            msg = 'Failed: \"'+ str(rule) +'\" \"'+ str(err[0:-2]) +'\"'
            logger.info(msg)
    
    cnx.close()
    print "End of checking for expired users."
    logger.info('######### End of checking for expired users. #########')


# Check only for MAC, USE_VLANS = False
def find_mac(CHECK_AGAIN = CHECK_AGAIN, *args):

    check_again = CHECK_AGAIN - 1
    check_again_rows=False
    
    print "Started searching for MAC address."
    logger.info('######### Started searching for MAC address. #########')
    
    cnx = _connect()
    cursor = cnx.cursor()
        
    # Search for users who no MAC Address but free_mac=0
    query = "SELECT static_ippool.ipaddress, static_ippool.subnet, traffic.local_in, traffic.local_out \
    FROM static_ippool LEFT JOIN traffic ON static_ippool.trafficid = traffic.trafficid \
    WHERE static_ippool.userid != 0 AND static_ippool.trafficid != 0 AND static_ippool.mac like '' AND static_ippool.free_mac=0"
    
    cursor.execute(query)
    rows = cursor.fetchall()
    #print str(rows)

    if rows:
        for row in rows:
            
            #arp-scan --interface=eth0 192.168.1.1
            arp_scan = ARP_SCAN +' --interface='+ IFACE_INTERNAL +' '+ row[0]
            p = subprocess.Popen(arp_scan, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            out, err = p.communicate()
            
            #print 'row: '+ str(row)
            #print 'ERR: '+ err
            #print 'OUT: '+ out
            
            if err:
                msg = 'Failed: \"' + str(arp_scan) + '\" \"' + str(err[0:-2]) +'\"'
                logger.info(msg)
            
            if out[-12:-11] == "1":
                found = out.splitlines()[2:-3]
                found = found[0].split('\t', 2)
        
                msg = 'Found: '+ str(found)
                logger.info(msg)
                
                cmd = []
                cmd += [IP +' route replace '+ row[0] +'/'+ row[1] +' dev '+ IFACE_INTERNAL +' src '+ row[0].rsplit('.', 1)[0] +'.1']
                cmd += [IP +' neighbor replace '+ row[0] +' lladdr '+ found[1] +' dev '+ IFACE_INTERNAL +' nud permanent']
        
                target_ip = ipcalc.IP(row[0])
                ipmark_hex = target_ip.hex()[4:8]
        
                # Iproute2 tc - download speed rule
                cmd += [TC +' class replace dev '+ IFACE_INTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[2] +'Kbit']
                cmd += [TC +' qdisc add dev '+ IFACE_INTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
        
                if IFACE_EXTERNAL_VLANS:
                    for vlan_id in IFACE_EXTERNAL_VLANS:
                        # Iproute2 tc - upload speed rules for VLANs
                        cmd += [TC +' class replace dev vlan'+ vlan_id +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
                        cmd += [TC +' qdisc add dev vlan'+ vlan_id +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
                    
                else:
                    # Iproute2 tc - upload speed rule
                    cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
                    cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
        
                for rule in cmd:
                    p = subprocess.Popen(rule, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                    out, err = p.communicate()
    
                    if err:
                        msg = 'Failed: \"'+ str(rule) +'\" \"'+ str(err[0:-2]) +'\"'
                        logger.info(msg)            

                query = 'UPDATE `static_ippool` SET mac = \"'+ found[1] +'\", mac_info = \"'+ found[2] +'\" WHERE ipaddress = \"'+ row[0] +'\"'

                cursor.execute(query)
                cnx.commit()
                
                #print cmd
                break
            else:
                check_again_rows=True
            
        if check_again_rows and check_again != 0:
            time.sleep(INTERVAL_FOR_CHECKING)
            find_mac(check_again)
            
        else:
            cnx.close()
            print "End of searching for MAC address."
            logger.info('######### End of searching for MAC address. #########')
    
    else:
        cnx.close()
        print "End of searching for MAC address."
        logger.info('######### End of searching for MAC address. #########')


def find_mac_in_vlan(CHECK_AGAIN = CHECK_AGAIN, *args):

    check_again = CHECK_AGAIN - 1
    check_again_rows=False
    
    print "Started searching for VLAN based MAC address."
    logger.info('######### Started searching for VLAN based MAC address. #########')
    
    cnx = _connect()
    cursor = cnx.cursor()
        
    # Search for users who no MAC Address but free_mac=0
    query = "SELECT static_ippool.ipaddress, static_ippool.subnet, static_ippool.vlan, traffic.local_in, traffic.local_out \
    FROM static_ippool LEFT JOIN traffic ON static_ippool.trafficid = traffic.trafficid \
    WHERE static_ippool.userid != 0 AND static_ippool.trafficid != 0 AND static_ippool.vlan not like '' AND static_ippool.mac like '' AND static_ippool.free_mac=0"
    
    cursor.execute(query)
    rows = cursor.fetchall()
    #print str(rows)

    if rows:
        for row in rows:
            
            #arp-scan --interface=vlan10 192.168.1.1
            arp_scan = ARP_SCAN +' --interface='+ row[2] +' '+ row[0]
            p = subprocess.Popen(arp_scan, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            out, err = p.communicate()
            
            #print 'row: '+ str(row)
            #print 'ERR: '+ err
            #print 'OUT: '+ out
            
            if err:
                msg = 'Failed: \"' + str(arp_scan) + '\" \"' + str(err[0:-2]) +'\"'
                logger.info(msg)
            
            if out[-12:-11] == "1":
                found = out.splitlines()[2:-3]
                found = found[0].split('\t', 2)
        
                msg = 'Found: '+ str(found)  +' '+ str(row[2])
                logger.info(msg)
                
                cmd = []
                cmd += [IP +' route replace '+ row[0] +'/'+ row[1] +' dev '+ row[2] +' src '+ row[0].rsplit('.', 1)[0] +'.1']
                cmd += [IP +' neighbor replace '+ row[0] +' lladdr '+ found[1] +' dev '+ row[2] +' nud permanent']
        
                target_ip = ipcalc.IP(row[0])
                ipmark_hex = target_ip.hex()[4:8]
        
                # Iproute2 tc - download speed rule
                cmd += [TC +' class replace dev '+ row[2] +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
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
        
                for rule in cmd:
                    p = subprocess.Popen(rule, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                    out, err = p.communicate()
    
                    if err:
                        msg = 'Failed: \"'+ str(rule) +'\" \"'+ str(err[0:-2]) +'\"'
                        logger.info(msg)            

                query = 'UPDATE `static_ippool` SET mac = \"'+ found[1] +'\", mac_info = \"'+ found[2] +'\" WHERE ipaddress = \"'+ row[0] +'\"'

                cursor.execute(query)
                cnx.commit()
                
                #print cmd
                break
            else:
                check_again_rows=True
            
        if check_again_rows and check_again != 0:
            time.sleep(INTERVAL_FOR_CHECKING)
            find_mac_in_vlan(check_again)
            
        else:
            cnx.close()
            print "End of searching for VLAN based MAC address."
            logger.info('######### End of searching for VLAN based MAC address. #########')
    
    else:
        cnx.close()
        print "End of searching for VLAN based MAC address."
        logger.info('######### End of searching for VLAN based MAC address. #########')


def find_vlan_for_mac(CHECK_AGAIN = CHECK_AGAIN, *args):
    
    check_again = CHECK_AGAIN - 1
    check_again_rows=False
    
    print "Started searching for MAC address based VLAN."
    logger.info('######### Started searching for MAC address based VLAN. #########')
    
    cnx = _connect()
    cursor = cnx.cursor()
        
    # Search for users who no VLAN but have MAC Address and free_mac=0
    query = "SELECT static_ippool.ipaddress, static_ippool.subnet, static_ippool.mac, traffic.local_in, traffic.local_out \
    FROM static_ippool LEFT JOIN traffic ON static_ippool.trafficid = traffic.trafficid \
    WHERE static_ippool.userid != 0 AND static_ippool.trafficid != 0 AND static_ippool.vlan like '' AND static_ippool.mac not like '' AND static_ippool.free_mac=0"
    
    cursor.execute(query)
    rows = cursor.fetchall()
    #print str(rows)
    
    if rows:
        for row in rows:
            for vlan in IFACE_INTERNAL_VLANS:
                
                #arp-scan --interface=vlan10 --destaddr=64:70:02:42:72:34 192.168.1.1
                arp_scan = ARP_SCAN +' --interface=vlan'+ vlan +' --destaddr='+ row[2] +' '+ row[0]
                p = subprocess.Popen(arp_scan, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                out, err = p.communicate()
                
                #print 'row: '+ str(row)
                #print 'ERR: '+ err
                #print 'OUT: '+ out
                
                if err:
                    msg = 'Failed: \"' + str(arp_scan) + '\" \"' + str(err[0:-2]) +'\"'
                    logger.info(msg)
                
                if out[-12:-11] == "1":
                    found = out.splitlines()[2:-3]
                    found = found[0].split('\t', 2)
                    
                    msg = 'Found: '+ str(found) +' vlan'+ str(vlan)
                    logger.info(msg)
                    
                    cmd = []
                    cmd += [IP +' route replace '+ row[0] +'/'+ row[1] +' dev vlan'+ vlan +' src '+ row[0].rsplit('.', 1)[0] +'.1']
                    cmd += [IP +' neighbor replace '+ row[0] +' lladdr '+ row[2] +' dev vlan'+ vlan +' nud permanent']
            
                    target_ip = ipcalc.IP(row[0])
                    ipmark_hex = target_ip.hex()[4:8]
            
                    # Iproute2 tc - download speed rule
                    cmd += [TC +' class replace dev vlan'+ vlan +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
                    cmd += [TC +' qdisc add dev vlan'+ vlan +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +':0 sfq perturb 10']
                    
                    if IFACE_EXTERNAL_VLANS:
                        for vlan_id in IFACE_EXTERNAL_VLANS:
                            # Iproute2 tc - upload speed rules for VLANs
                            cmd += [TC +' class replace dev vlan'+ vlan_id +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[4] +'Kbit']
                            cmd += [TC +' qdisc add dev vlan'+ vlan_id +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
                        
                    else:
                        # Iproute2 tc - upload speed rule
                        cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[4] +'Kbit']
                        cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +':0 sfq perturb 10']
            
                    for rule in cmd:
                        p = subprocess.Popen(rule, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                        out, err = p.communicate()
        
                        if err:
                            msg = 'Failed: \"'+ str(rule) +'\" \"'+ str(err[0:-2]) +'\"'
                            logger.info(msg)            

                    query = 'UPDATE `static_ippool` SET vlan = \"vlan'+ vlan +'\", mac_info = \"'+ found[2] +'\" WHERE ipaddress = \"'+ row[0] +'\"'

                    cursor.execute(query)
                    cnx.commit()
                    
                    #print cmd
                    break
                else:
                    check_again_rows=True
            
        if check_again_rows and check_again != 0:
            time.sleep(INTERVAL_FOR_CHECKING)
            find_vlan_for_mac(check_again)
            
        else:
            cnx.close()
            print "End of searching for MAC address based VLAN."
            logger.info('######### End of searching for MAC address based VLAN. #########')
    
    else:
        cnx.close()
        print "End of searching for MAC address based VLAN."
        logger.info('######### End of searching for MAC address based VLAN. #########')        


def find_vlan_for_free_mac(CHECK_AGAIN = CHECK_AGAIN, *args):
    
    check_again = CHECK_AGAIN - 1
    check_again_rows=False
    
    print "Started searching VLAN for Free MACs."
    logger.info('######### Started searching VLAN for Free MACs. #########')
    
    cnx = _connect()
    cursor = cnx.cursor()
        
    # Search for users who no VLAN but have Free MAC Address = 1
    query = "SELECT static_ippool.ipaddress, static_ippool.subnet, traffic.local_in, traffic.local_out \
    FROM static_ippool LEFT JOIN traffic ON static_ippool.trafficid = traffic.trafficid \
    WHERE static_ippool.userid != 0 AND static_ippool.trafficid != 0 AND static_ippool.vlan like '' AND static_ippool.free_mac=1"
    
    cursor.execute(query)
    rows = cursor.fetchall()
    #print str(rows)
    
    if rows:
        for row in rows:
            for vlan in IFACE_INTERNAL_VLANS:
                
                #arp-scan --interface=vlan10 192.168.1.1
                arp_scan = ARP_SCAN +' --interface=vlan'+ vlan +' '+ row[0]
                p = subprocess.Popen(arp_scan, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                out, err = p.communicate()
                
                #print 'row: '+ str(row)
                #print 'ERR: '+ err
                #print 'OUT: '+ out
                
                if err:
                    msg = 'Failed: \"' + str(arp_scan) + '\" \"' + str(err[0:-2]) +'\"'
                    logger.info(msg)
                
                if out[-12:-11] == "1":
                    found = out.splitlines()[2:-3]
                    found = found[0].split('\t', 2)
            
                    msg = 'Found: '+ str(found) +' vlan'+ str(vlan)
                    logger.info(msg)
                    
                    cmd = []
                    cmd += [IP +' route replace '+ row[0] +'/'+ row[1] +' dev vlan'+ vlan +' src '+ row[0].rsplit('.', 1)[0] +'.1']
            
                    target_ip = ipcalc.IP(row[0])
                    ipmark_hex = target_ip.hex()[4:8]
            
                    # Iproute2 tc - download speed rule
                    cmd += [TC +' class replace dev vlan'+ vlan +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[2] +'Kbit']
                    cmd += [TC +' qdisc add dev vlan'+ vlan +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +':0 sfq perturb 10']
                    
                    if IFACE_EXTERNAL_VLANS:
                        for vlan_id in IFACE_EXTERNAL_VLANS:
                            # Iproute2 tc - upload speed rules for VLANs
                            cmd += [TC +' class replace dev vlan'+ vlan_id +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
                            cmd += [TC +' qdisc add dev vlan'+ vlan_id +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
                        
                    else:
                        # Iproute2 tc - upload speed rule
                        cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
                        cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +':0 sfq perturb 10']
            
                    for rule in cmd:
                        p = subprocess.Popen(rule, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                        out, err = p.communicate()
        
                        if err:
                            msg = 'Failed: \"'+ str(rule) +'\" \"'+ str(err[0:-2]) +'\"'
                            logger.info(msg)            

                    query = 'UPDATE `static_ippool` SET vlan = \"vlan'+ vlan +'\", mac = \"'+ found[1] +'\", mac_info = \"'+ found[2] +'\" WHERE ipaddress = \"'+ row[0] +'\"'

                    cursor.execute(query)
                    cnx.commit()
                    
                    #print cmd
                    break
                else:
                    check_again_rows=True
            
        if check_again_rows and check_again != 0:
            time.sleep(INTERVAL_FOR_CHECKING)
            find_vlan_for_free_mac(check_again)
            
        else:
            cnx.close()
            print "End of searching VLAN for Free MACs."
            logger.info('######### End of searching VLAN for Free MACs. #########')
    
    else:
        cnx.close()
        print "End of searching VLAN for Free MACs."
        logger.info('######### End of searching VLAN for Free MACs. #########')        


def find_vlan_mac(CHECK_AGAIN = CHECK_AGAIN, *args):
    
    check_again = CHECK_AGAIN - 1
    check_again_rows=False
    
    print "Started searching for VLAN and MAC address."
    logger.info('######### Started searching for VLAN and MAC address. #########')
    
    cnx = _connect()
    cursor = cnx.cursor()
        
    # Search for users who no VLAN and no MAC
    query = "SELECT static_ippool.ipaddress, static_ippool.subnet, traffic.local_in, traffic.local_out \
    FROM static_ippool LEFT JOIN traffic ON static_ippool.trafficid = traffic.trafficid \
    WHERE static_ippool.userid != 0 AND static_ippool.trafficid != 0 AND static_ippool.vlan like '' AND static_ippool.mac like '' AND static_ippool.free_mac=0"
    
    cursor.execute(query)
    rows = cursor.fetchall()
    #print str(rows)
    
    if rows:
        for row in rows:
            for vlan in IFACE_INTERNAL_VLANS:
                
                #arp-scan --interface=vlan10 192.168.1.1
                arp_scan = ARP_SCAN +' --interface=vlan'+ vlan +' '+ row[0]
                p = subprocess.Popen(arp_scan, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                out, err = p.communicate()
                
                #print 'row: '+ str(row)
                #print 'ERR: '+ err
                #print 'OUT: '+ out
                
                if err:
                    msg = 'Failed: \"' + str(arp_scan) + '\" \"' + str(err[0:-2]) +'\"'
                    logger.info(msg)
                
                if out[-12:-11] == "1":
                    found = out.splitlines()[2:-3]
                    found = found[0].split('\t', 2)
            
                    msg = 'Found: '+ str(found) +' vlan'+ str(vlan)
                    logger.info(msg)
                    
                    cmd = []
                    cmd += [IP +' route replace '+ row[0] +'/'+ row[1] +' dev vlan'+ vlan +' src '+ row[0].rsplit('.', 1)[0] +'.1']
                    cmd += [IP +' neighbor replace '+ row[0] +' lladdr '+ found[1] +' dev vlan'+ vlan +' nud permanent']
            
                    target_ip = ipcalc.IP(row[0])
                    ipmark_hex = target_ip.hex()[4:8]
            
                    # Iproute2 tc - download speed rule
                    cmd += [TC +' class replace dev vlan'+ vlan +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[2] +'Kbit']
                    cmd += [TC +' qdisc add dev vlan'+ vlan +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +':0 sfq perturb 10']
                    
                    if IFACE_EXTERNAL_VLANS:
                        for vlan_id in IFACE_EXTERNAL_VLANS:
                            # Iproute2 tc - upload speed rules for VLANs
                            cmd += [TC +' class replace dev vlan'+ vlan_id +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
                            cmd += [TC +' qdisc add dev vlan'+ vlan_id +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +' sfq perturb 10']
                        
                    else:
                        # Iproute2 tc - upload speed rule
                        cmd += [TC +' class replace dev '+ IFACE_EXTERNAL +' parent 1: classid 1:'+ ipmark_hex +' htb rate '+ row[3] +'Kbit']
                        cmd += [TC +' qdisc add dev '+ IFACE_EXTERNAL +' parent 1:'+ ipmark_hex +' handle '+ ipmark_hex +':0 sfq perturb 10']
                        
                    for rule in cmd:
                        p = subprocess.Popen(rule, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                        out, err = p.communicate()
        
                        if err:
                            msg = 'Failed: \"'+ str(rule) +'\" \"'+ str(err[0:-2]) +'\"'
                            logger.info(msg)            

                    query = 'UPDATE `static_ippool` SET vlan = \"vlan'+ vlan +'\", mac = \"'+ found[1] +'\", mac_info = \"'+ found[2] +'\" WHERE ipaddress = \"'+ row[0] +'\"'

                    cursor.execute(query)
                    cnx.commit()
                    
                    #print cmd
                    break
                else:
                    check_again_rows=True
            
        if check_again_rows and check_again != 0:
            time.sleep(INTERVAL_FOR_CHECKING)
            find_vlan_mac(check_again)
            
        else:
            cnx.close()
            print "End of searching for VLAN and MAC address."
            logger.info('######### End of searching for VLAN and MAC address. #########')
    
    else:
        cnx.close()
        print "End of searching for VLAN and MAC address."
        logger.info('######### End of searching for VLAN and MAC address. #########')        
