#!/bin/bash
. /etc/imslu/config.sh

ip_add () {

  if [[ $USE_VLANS -eq 0 ]]; then
    if [ -f /proc/net/vlan/${2} ]; then
      IFS=\. read -r a b c d <<< "${1}"
#     ip route add 10.0.1.2 dev eth1.0010 src 10.0.1.1
      ip route add ${1} dev ${2} src ${a}.${b}.${c}.1

      if [[ "${3}" == "n" && -n "${4}" ]]; then
#       arp -i eth1.0010 -s 10.0.1.2 34:23:87:96:70:27
        arp -i ${2} -s ${1} ${4}
      fi
    fi
  else
    if [ -n "${1}" ]; then
#     IFS=\. read -r a b c d <<< "${1}"
#     ip route add 10.0.1.2 dev eth1 src 10.0.1.1
#     ip route add ${1} dev ${IFACE_INTERNAL} src ${a}.${b}.${c}.1

      if [[ "${3}" == "n" && -n "${4}" ]]; then
#       arp -s 10.0.1.2 34:23:87:96:70:27
        arp -s ${1} ${4}
      fi
    fi
  fi
}

ip_rem () {

  if [[ $USE_VLANS -eq 0 && -n "${1}" ]]; then
#   ip route del 10.0.1.2
    ip route del ${1}
#   arp -i eth1.0011 -d 10.0.1.2
    arp -i ${2} -d ${1}
  else
#   ip route del 10.0.1.2
#   ip route del ${1}
#   arp -i eth1 -d 10.0.1.2
    arp -i ${IFACE_INTERNAL} -d ${1}
  fi
}

mac_add () {

  if [ $USE_VLANS -eq 0 ]; then
    if [[ -f /proc/net/vlan/${2} && -n "${4}" ]]; then

#     arp -i eth1.0010 -s 10.0.1.2 34:23:87:96:70:27
      arp -i ${2} -s ${1} ${4}
    fi
  else
    if [[ -n "${1}" && "${3}" == "n" && -n "${4}" ]]; then

#       arp -i eth1 -s 10.0.1.2 34:23:87:96:70:27
        arp -i ${IFACE_INTERNAL} -s ${1} ${4}
    fi
  fi
}

mac_rem () {

  if [[ $USE_VLANS -eq 0 && -n "${1}" ]]; then
#   arp -i eth1.0011 -d 10.0.1.2
    arp -i ${2} -d ${1}
  else
#   arp -i eth1 -d 10.0.1.2
    arp -i ${IFACE_INTERNAL} -d ${1}
  fi
}

ip_allow () {

  ipset add allowed ${1}
}

ip_stop () {

  ipset del allowed ${1}
}

if [[ "${1}" == "tc_class_add" || "${1}" == "tc_class_delete" || "${1}" == "tc_class_replace" || "${1}" == "tc_filter_add" || "${1}" == "tc_filter_replace" || "${1}" == "tc_filter_delete" ]]; then

  . /etc/imslu/config.sh

  #declare variables
  declare -a services
  kind_traffic=$(echo "SELECT COUNT(id) FROM kind_traffic" | mysql $database -u $user -p${password} -s)

  ####### Services #######
  query="SELECT serviceid, in_min0, in_max0, out_min0, out_max0, in_min1, in_max1, out_min1, out_max1, in_min2, in_max2, out_min2, out_max2, in_min3, in_max3, out_min3, out_max3, in_min4, in_max4, out_min4, out_max4 FROM services"
  while read -r row; do
    read -r serviceid tmp <<< "${row}"
    services[${serviceid}]=$row

  done < <(echo $query | mysql $database -u $user -p${password} -s)
  # echo ${services[@]}
  # echo ${!services[@]}
fi

tc_class_replace () {

  userid=${1}
  serviceid=${2}

  if [ -n "${services[${serviceid}]}" ]; then
  read -r serviceid in_min0 in_max0 out_min0 out_max0 in_min1 in_max1 out_min1 out_max1 in_min2 in_max2 out_min2 out_max2 in_min3 in_max3 out_min3 out_max3 in_min4 in_max4 out_min4 out_max4 <<< "${services[${serviceid}]}"

${TC} class replace dev ${IFACE_IMQ0} parent 1:2 classid 1:$(printf '%x' ${userid}) hfsc sc m1 0bit d 0us m2 ${in_min0} ul m1 0bit d 0us m2 ${in_max0}
${TC} class replace dev ${IFACE_IMQ1} parent 1:2 classid 1:$(printf '%x' ${userid}) hfsc sc m1 0bit d 0us m2 ${out_min0} ul m1 0bit d 0us m2 ${out_max0}

  if [[ $in_max1 != "NULL" && $out_max1 != "NULL" ]]; then
${TC} class replace dev ${IFACE_IMQ0} parent 1:3 classid 1:$(printf '%x' $((${userid}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${in_min1} ul m1 0bit d 0us m2 ${in_max1}
${TC} class replace dev ${IFACE_IMQ1} parent 1:3 classid 1:$(printf '%x' $((${userid}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${out_min1} ul m1 0bit d 0us m2 ${out_max1}
  fi
  if [[ $in_max2 != "NULL" && $out_max2 != "NULL" ]]; then
${TC} class replace dev ${IFACE_IMQ0} parent 1:4 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${in_min2} ul m1 0bit d 0us m2 ${in_max2}
${TC} class replace dev ${IFACE_IMQ1} parent 1:4 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${out_min2} ul m1 0bit d 0us m2 ${out_max2}
  fi
  if [[ $in_max3 != "NULL" && $out_max3 != "NULL" ]]; then
${TC} class replace dev ${IFACE_IMQ0} parent 1:5 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${in_min3} ul m1 0bit d 0us m2 ${in_max3}
${TC} class replace dev ${IFACE_IMQ1} parent 1:5 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${out_min3} ul m1 0bit d 0us m2 ${out_max3}
  fi
  if [[ $in_max4 != "NULL" && $out_max4 != "NULL" ]]; then
${TC} class replace dev ${IFACE_IMQ0} parent 1:6 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${in_min4} ul m1 0bit d 0us m2 ${in_max4}
${TC} class replace dev ${IFACE_IMQ1} parent 1:6 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${out_min4} ul m1 0bit d 0us m2 ${out_max4}
  fi
  fi
}

tc_class_delete () {

  userid=${1}

${TC} class delete dev ${IFACE_IMQ0} parent 1:2 classid 1:$(printf '%x' ${userid})
${TC} class delete dev ${IFACE_IMQ1} parent 1:2 classid 1:$(printf '%x' ${userid})

  if [[ $in_max1 != "NULL" && $out_max1 != "NULL" ]]; then
${TC} class delete dev ${IFACE_IMQ0} parent 1:3 classid 1:$(printf '%x' $((${userid}+${STEP})))
${TC} class delete dev ${IFACE_IMQ1} parent 1:3 classid 1:$(printf '%x' $((${userid}+${STEP})))
  fi
  if [[ $in_max2 != "NULL" && $out_max2 != "NULL" ]]; then
${TC} class delete dev ${IFACE_IMQ0} parent 1:4 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP})))
${TC} class delete dev ${IFACE_IMQ1} parent 1:4 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP})))
  fi
  if [[ $in_max3 != "NULL" && $out_max3 != "NULL" ]]; then
${TC} class delete dev ${IFACE_IMQ0} parent 1:5 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP})))
${TC} class delete dev ${IFACE_IMQ1} parent 1:5 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP})))
  fi
  if [[ $in_max4 != "NULL" && $out_max4 != "NULL" ]]; then
${TC} class delete dev ${IFACE_IMQ0} parent 1:6 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}+${STEP})))
${TC} class delete dev ${IFACE_IMQ1} parent 1:6 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}+${STEP})))
  fi
}

tc_filter_replace () {

  ip=${1}
  userid=${2}
  IFS=\. read -r a b c d <<< "${ip}"

  if [ $c -eq 0 ]; then
  hash_table=$((${a}-1))
  else
  hash_table=$((${a}+${c}))
  fi

  # The filter rules for IP address should start with checking the TOS field in the IPv4 header ( backwards )
  if [ $kind_traffic -ge 5 ]; then
${TC} filter replace dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 match ip tos 0x5 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}+${STEP})))
${TC} filter replace dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 match ip tos 0x5 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}+${STEP})))
  fi
  if [ $kind_traffic -ge 4 ]; then
${TC} filter replace dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 match ip tos 0x4 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP})))
${TC} filter replace dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 match ip tos 0x4 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP})))
  fi
  if [ $kind_traffic -ge 3 ]; then
${TC} filter replace dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 match ip tos 0x3 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP})))
${TC} filter replace dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 match ip tos 0x3 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP})))
  fi
  if [ $kind_traffic -ge 2 ]; then
${TC} filter replace dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 match ip tos 0x2 0xff flowid 1:$(printf '%x' $((${userid}+${STEP})))
${TC} filter replace dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 match ip tos 0x2 0xff flowid 1:$(printf '%x' $((${userid}+${STEP})))
  fi
${TC} filter replace dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 flowid 1:$(printf '%x' ${userid})
${TC} filter replace dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 flowid 1:$(printf '%x' ${userid})

}

tc_filter_delete () {

  ip=${1}
  IFS=\. read -r a b c d <<< "${ip}"

  if [ $c -eq 0 ]; then
    hash_table=$((${a}-1))
  else
    hash_table=$((${a}+${c}))
  fi

   LINK=$(${TC} -g filter ls dev ${IFACE_IMQ0} | grep $(printf '%x' $hash_table):$(printf '%x' $d):[0-9]*)

  for row in ${LINK}; do
${TC} filter delete dev ${IFACE_IMQ0} parent 1: prio 1 handle ${row} protocol ip u32
${TC} filter delete dev ${IFACE_IMQ1} parent 1: prio 1 handle ${row} protocol ip u32
  done
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
	ip_add "${2}" "${3}" "${4}" "${5}"
	;;

ip_rem)
	ip_rem "${2}" "${3}"
	;;

mac_add)
	mac_add "${2}" "${3}" "${4}" "${5}"
	;;

mac_rem)
	mac_rem "${2}" "${3}"
	;;

ip_allow)
	ip_allow "${2}"
	;;

ip_stop)
	ip_stop "${2}"
	;;

tc_class_add|tc_class_replace)
	tc_class_replace "${2}" "${3}"
	;;

tc_class_delete)
	tc_class_delete "${2}"
	;;

tc_filter_add|tc_filter_replace)
	tc_filter_replace "${2}" "${3}"
	;;

tc_filter_delete)
	tc_filter_delete "${2}"
	;;

*)
	echo "Usage: /etc/imslu/scripts/functions-php.sh {pppd_kill|ip_add|ip_rem}"
	exit 1
	;;
esac

exit 0
