#!/bin/sh

# Auto find a MAC address or VLAN when you add a static IP address for the device.

ARP_EXPIRES=/tmp/arp_expires

arp_expires() {

  arp=""
  while read -r ip dev interface lladdr mac state; do

    arp="${arp}${ip} ${interface} ${mac}\n"
  done <<EOF
$(${IP} neighbour show | grep -v "PERMANENT\|incomplete")
EOF

  echo ${arp} > ${ARP_EXPIRES}
}


##### USE_VLANS=true #####
check_status_vlan() {

    local status
    # Search for users who not have a VLAN and MAC
    query="SELECT id FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND (vlan LIKE '' OR (mac LIKE '' AND free_mac='n')) LIMIT 1"
    status=$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)

    if [ ${#status} -gt 0 ]; then
        return 0
    else
        return 1
    fi
}

find_mac_vlan() {

  local id
  local ip
  local vlan
  local mac
  local free_mac
  dhcp_subnets

  # Search for users who not have a VLAN and MAC
  query="SELECT id, ip, free_mac, protocol FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND vlan LIKE '' AND mac LIKE ''"

  while read -r id ip free_mac protocol; do

    if [ ${#id} -gt 0 ]; then

      found=$(cat ${ARP_EXPIRES} | grep "${ip} ")
      if [ ${#found} -gt 0 ]; then

        read -r ip vlan mac <<EOF
$(echo ${found})
EOF

        ip_add ${ip} ${vlan}
        sleep 10 && mac_add ${ip} ${vlan} ${mac} ${free_mac} &

        if [ "${protocol}" = "DHCP" ]; then
            dhcp_add ${ip} ${mac}
        fi

        ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE ip SET vlan='${vlan}', mac='${mac}' WHERE id='${id}';"
        unset found id
      fi
    fi

  done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF


  # Search for users who have a MAC, but not have VLAN
  query="SELECT id, ip, mac, free_mac FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND vlan LIKE '' AND mac NOT LIKE ''"

  while read -r id ip mac free_mac; do

    if [ ${#id} -gt 0 ]; then

      found=$(cat ${ARP_EXPIRES} | grep -o -E "${ip} .* ${mac}")
      if [ ${#found} -gt 0 ]; then

        read -r ip vlan mac <<EOF
$(echo ${found})
EOF

        ip_add ${ip} ${vlan}
        sleep 10 && mac_add ${ip} ${vlan} ${mac} ${free_mac} &

        ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE ip SET vlan='${vlan}' WHERE id='${id}';"
        unset found id
      fi
    fi

  done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF


  # Search for users who have a VLAN, but not have MAC
  query="SELECT id, ip, vlan, protocol FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND vlan NOT LIKE '' AND mac LIKE '' AND free_mac='n'"

  while read -r id ip vlan protocol; do

    if [ ${#id} -gt 0 ]; then

      found=$(cat ${ARP_EXPIRES} | grep "${ip} ${vlan}")
      if [ ${#found} -gt 0 ]; then

        read -r ip vlan mac <<EOF
$(echo ${found})
EOF

        mac_add ${ip} ${vlan} ${mac} 'n'

        if [ "${protocol}" = "DHCP" ]; then
            dhcp_add ${ip} ${mac}
        fi

        ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE ip SET mac='${mac}' WHERE id='${id}';"
        unset found id
      fi
    fi

  done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF
}

##### USE_VLANS=false #####
check_status() {

    local status
    # Search for users who not have a MAC
    query="SELECT id FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND mac LIKE '' AND free_mac='n' LIMIT 1"
    status=$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)

    if [ ${#status} -gt 0 ]; then
        return 0
    else
        return 1
    fi
}

find_mac() {

  local id
  local ip
  local mac
  local vlan
  dhcp_subnets

  # Search for users who not have MAC
  query="SELECT id, ip, protocol FROM ip WHERE userid != 0 AND (protocol = 'IP' OR protocol = 'DHCP') AND mac LIKE '' AND free_mac='n'"

  while read -r id ip protocol; do

    if [ ${#id} -gt 0 ]; then

      found=$(cat ${ARP_EXPIRES} | grep "${ip} ")
      if [ ${#found} -gt 0 ]; then

        read -r ip vlan mac <<EOF
$(echo ${found})
EOF

        mac_add ${ip} ${vlan} ${mac} 'n' &

        if [ "${protocol}" = "DHCP" ]; then
            dhcp_add ${ip} ${mac}
        fi

        ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE ip SET mac='${mac}' WHERE id='${id}';"
        unset found id
      fi
    fi

  done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF
}


while true; do

  . /etc/imslu/config.sh
  . /etc/imslu/scripts/functions.sh

  if [ $USE_VLANS -eq 0 ]; then

    if [ check_status_vlan ]; then
      arp_expires
      find_mac_vlan
      # Clearing arp cache
      #${IP} neighbour flush all
    fi

  else

    if [ check_status ]; then
      arp_expires
      find_mac
      # Clearing arp cache
      #${IP} neighbour flush all
    fi

  fi

  sleep 300
done
