#!/bin/sh

. /usr/local/etc/imslu/config.sh

kind_traffic=$(echo "SELECT COUNT(id) FROM kind_traffic" | ${MYSQL} $database -u $user -p${password} -s)

ip_add () {

  if [ $USE_VLANS -eq 0 ]; then
    if [ -n $(ifconfig -g vlan | grep ${2}) ]; then

#     route add 10.0.1.2 -iface igb1.0010
      ${ROUTE} add ${1}/32 -iface ${2}

      if [ "${3}" == "n" ] && [ -n "${4}" ]; then

#       arp -S 10.0.1.2 34:23:87:96:70:27
        ${ARP} -S ${1} ${4}
      fi
    fi
  else
    if [ -n "${1}" ]; then

#     route add 10.0.1.2 -iface igb0
      ${ROUTE} add ${1}/32 -iface ${IFACE_INTERNAL}

      if [ -n "${3}" ] && [ "${4}" == "n" ]; then
#       arp -S 10.0.1.2 34:23:87:96:70:27
        ${ARP} -S ${1} ${3}
      fi
    fi
  fi
}

ip_rem () {

# arp -d 10.0.1.2
  ${ARP} -d ${1}
# route del 10.0.1.2
  ${ROUTE} del ${1}/32
}

mac_add () {

# arp -S 10.0.1.2 34:23:87:96:70:27
  ${ARP} -S ${1} ${4}
}

mac_rem () {

# arp -d 10.0.1.2
  ${ARP} -d ${1}
}

ip_allow () {

  ${IPFW} -q table 1 add ${1}/32 ${2}1
  ${IPFW} -q table 2 add ${1}/32 ${2}11

  if [ ${kind_traffic} -ge 2 ]; then
    ${IPFW} -q table 3 add ${1}/32 ${2}2
    ${IPFW} -q table 4 add ${1}/32 ${2}22
  fi
  if [ ${kind_traffic} -ge 3 ]; then
    ${IPFW} -q table 5 add ${1}/32 ${2}3
    ${IPFW} -q table 6 add ${1}/32 ${2}33
  fi
  if [ ${kind_traffic} -ge 4 ]; then
    ${IPFW} -q table 7 add ${1}/32 ${2}4
    ${IPFW} -q table 8 add ${1}/32 ${2}44
  fi
  if [ ${kind_traffic} -ge 5 ]; then
    ${IPFW} -q table 9 add ${1}/32 ${2}5
    ${IPFW} -q table 10 add ${1}/32 ${2}55
  fi
}

ip_stop () {

  ${IPFW} -q table 1 delete ${1}/32
  ${IPFW} -q table 2 delete ${1}/32

  if [ ${kind_traffic} -ge 2 ]; then
    ${IPFW} -q table 3 delete ${1}/32
    ${IPFW} -q table 4 delete ${1}/32
  fi
  if [ ${kind_traffic} -ge 3 ]; then
    ${IPFW} -q table 5 delete ${1}/32
    ${IPFW} -q table 6 delete ${1}/32
  fi
  if [ ${kind_traffic} -ge 4 ]; then
    ${IPFW} -q table 7 delete ${1}/32
    ${IPFW} -q table 8 delete ${1}/32
  fi
  if [ ${kind_traffic} -ge 5 ]; then
    ${IPFW} -q table 9 delete ${1}/32
    ${IPFW} -q table 10 delete ${1}/32
  fi
}


case "${1}" in
pppd_kill)
  ip=${2}
  IFACE=$(ip route show | grep "${ip}" | grep -o "ppp\w*")
  if [ -f /var/run/${IFACE}.pid ]; then
    PID=$(cat /var/run/${IFACE}.pid)
    kill -9 $PID
    sed -i "/${ip}/d" /tmp/ip_activity_pppoe
    sed -i "/${ip}/d" /tmp/ip_activity
  fi
	;;

ip_add)
	ip_add ${2} ${3} ${4} ${5}
	;;

ip_rem)
	ip_rem ${2} ${3}
	;;

mac_add)
	mac_add ${2} ${3} ${4} ${5}
	;;

mac_rem)
	mac_rem ${2} ${3}
	;;

ip_allow)
	ip_allow ${2} ${3}
	;;

ip_stop)
	ip_stop ${2}
	;;

*)
	echo "Usage: /usr/local/etc/imslu/scripts/functions-php.sh {pppd_kill|ip_add|ip_rem}"
	exit 1
	;;
esac

exit 0
