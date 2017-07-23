#!/bin/sh

. /usr/local/etc/imslu/config.sh

now=$(date +%Y%m%d%H%M%S)
kind_traffic=$(echo "SELECT COUNT(id) FROM kind_traffic" | ${MYSQL} $database -u $user -p${password} -s)


####### Services #######

query="SELECT serviceid, in_max0, out_max0, in_max1, out_max1, in_max2, out_max2, in_max3, out_max3, in_max4, out_max4 FROM services"

# Reading services from database and add dynamic pipes 
while read -r serviceid in_max0 out_max0 in_max1 out_max1 in_max2 out_max2 in_max3 out_max3 in_max4 out_max4; do

${IPFW} pipe ${serviceid}1 config mask dst-ip 0xffffffff bw ${in_max0}
${IPFW} pipe ${serviceid}11 config mask src-ip 0xffffffff bw ${out_max0}

  if [ "${in_max1}" != "NULL" ] && [ "${out_max1}" != "NULL" ]; then
${IPFW} pipe ${serviceid}2 config mask dst-ip 0xffffffff bw ${in_max1}
${IPFW} pipe ${serviceid}22 config mask src-ip 0xffffffff bw ${out_max1}
  fi
  if [ "${in_max2}" != "NULL" ] && [ "${out_max2}" != "NULL" ]; then
${IPFW} pipe ${serviceid}3 config mask dst-ip 0xffffffff bw ${in_max2}
${IPFW} pipe ${serviceid}33 config mask src-ip 0xffffffff bw ${out_max2}
  fi
  if [ "${in_max3}" != "NULL" ] && [ "${out_max3}" != "NULL" ]; then
${IPFW} pipe ${serviceid}4 config mask dst-ip 0xffffffff bw ${in_max3}
${IPFW} pipe ${serviceid}44 config mask src-ip 0xffffffff bw ${out_max3}
  fi
  if [ "${in_max4}" != "NULL" ] && [ "${out_max4}" != "NULL" ]; then
${IPFW} pipe ${serviceid}5 config mask dst-ip 0xffffffff bw ${in_max4}
${IPFW} pipe ${serviceid}55 config mask src-ip 0xffffffff bw ${out_max4}
  fi

done <<EOF
$(echo ${query} | ${MYSQL} $database -u $user -p${password} -s)
EOF


####### Users #######

query="SELECT userid, serviceid, free_access, expires FROM users"

# Reading users from database and add export user info
while read -r userid serviceid free_access expires; do

export users${userid}="${serviceid} ${free_access} ${expires}"
done <<EOF
$(echo ${query} | ${MYSQL} $database -u $user -p${password} -s)
EOF


####### IP addresses #######

query="SELECT userid, ip, stopped FROM ip WHERE userid != 0"

while read -r userid ip stopped; do

# get user info
read -r serviceid free_access expires expires2 <<EOF
$(eval echo \$users${userid})
EOF

  # Prevent error: If mysql return empty result
  if [ -n "${userid}" ]; then

    # Prevent error: Failed conversion of ``0000-00-00 00:00:00'' using format ``%Y-%m-%d %H:%M:%S''
    if [ "${expires}" != "0000-00-00" ]; then
      d=$(date -j -f "%Y-%m-%d %H:%M:%S" "${expires} ${expires2}" +"%Y%m%d%H%M%S")
    else
      d=0
    fi
    if [ "${stopped}" == "n" ] && ([ "${free_access}" == "y" ] || [ ${d} -gt ${now} ]); then

      ${IPFW} table 1 add ${ip}/32 ${serviceid}1
      ${IPFW} table 2 add ${ip}/32 ${serviceid}11

      if [ ${kind_traffic} -ge 2 ]; then
        ${IPFW} table 3 add ${ip}/32 ${serviceid}2
        ${IPFW} table 4 add ${ip}/32 ${serviceid}22
      fi
      if [ ${kind_traffic} -ge 3 ]; then
        ${IPFW} table 5 add ${ip}/32 ${serviceid}3
        ${IPFW} table 6 add ${ip}/32 ${serviceid}33
      fi
      if [ ${kind_traffic} -ge 4 ]; then
        ${IPFW} table 7 add ${ip}/32 ${serviceid}4
        ${IPFW} table 8 add ${ip}/32 ${serviceid}44
      fi
      if [ ${kind_traffic} -ge 5 ]; then
        ${IPFW} table 9 add ${ip}/32 ${serviceid}5
        ${IPFW} table 10 add ${ip}/32 ${serviceid}55
      fi
    fi
  fi
done <<EOF
$(echo ${query} | ${MYSQL} $database -u $user -p${password} -s)
EOF


if [ $USE_VLANS -eq 0 ]; then

  # Adding routing and static MAC for IP addresses who have vlan.
  query="SELECT ip, vlan, free_mac, mac FROM ip WHERE userid != 0 AND protocol = 'IP' AND vlan NOT LIKE ''"
  while read -r ip vlan free_mac mac; do

    if [ -n $(ifconfig -g vlan | grep ${vlan}) ]; then

#     route add 10.0.1.2 -iface igb1.0010
      ${ROUTE} add ${ip}/32 -iface ${vlan}

      if [ "${free_mac}" == "n" ] && [ -n "${mac}" ]; then
#       arp -S 10.0.1.2 34:23:87:96:70:27
        ${ARP} -S ${ip} ${mac}
      fi
    fi
  done <<EOF
$(echo ${query} | ${MYSQL} $database -u $user -p${password} -s)
EOF
else

  # Adding routing and static MAC for IP addresses.
  query="SELECT ip, free_mac, mac FROM ip WHERE userid != 0 AND protocol = 'IP'"
  while read -r ip free_mac mac ; do

#   route add 10.0.1.2 -iface igb1
    ${ROUTE} add ${ip}/32 -iface ${IFACE_INTERNAL}
    
    if [ "${free_mac}" == "n" ] && [ -n "${mac}" ]; then
#     arp -S 10.0.1.2 34:23:87:96:70:27
      ${ARP} -S ${ip} ${mac}
    fi
  done <<EOF
$(echo ${query} | ${MYSQL} $database -u $user -p${password} -s)
EOF
fi
