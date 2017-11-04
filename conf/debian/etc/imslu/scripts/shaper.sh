#!/bin/sh

. /etc/imslu/config.sh

# remove any existing qdiscs
${TC} qdisc del dev ${IFACE_IMQ0} root 2> /dev/null
${TC} qdisc del dev ${IFACE_IMQ0} ingress 2> /dev/null
${TC} qdisc del dev ${IFACE_IMQ1} root 2> /dev/null
${TC} qdisc del dev ${IFACE_IMQ1} ingress 2> /dev/null


# install HFSC under WAN to limit download
${TC} qdisc add dev ${IFACE_IMQ0} root handle 1: hfsc default 9
# root class :1
${TC} class add dev ${IFACE_IMQ0} parent 1: classid 1:1 hfsc sc m1 0bit d 0us m2 1000mbit ul m1 0bit d 0us m2 1000mbit
# BGP peer - national traffic class 1:2
${TC} class add dev ${IFACE_IMQ0} parent 1:1 classid 1:2 hfsc sc m1 0bit d 0us m2 600mbit ul m1 0bit d 0us m2 600mbit
# International traffic class 1:3
${TC} class add dev ${IFACE_IMQ0} parent 1:1 classid 1:3 hfsc sc m1 0bit d 0us m2 300mbit ul m1 0bit d 0us m2 300mbit

# ADD HERE kind traffic: three four five ...

# default
${TC} class add dev ${IFACE_IMQ0} parent 1:1 classid 1:9 hfsc sc m1 0bit d 0us m2 1mbit ul m1 0bit d 0us m2 100mbit

# install HFSC under LAN to limit upload
${TC} qdisc add dev ${IFACE_IMQ1} root handle 1: hfsc default 9
# root class :1
${TC} class add dev ${IFACE_IMQ1} parent 1: classid 1:1 hfsc sc m1 0bit d 0us m2 1000mbit ul m1 0bit d 0us m2 1000mbit
# BGP peer - national traffic class 1:2
${TC} class add dev ${IFACE_IMQ1} parent 1:1 classid 1:2 hfsc sc m1 0bit d 0us m2 600mbit ul m1 0bit d 0us m2 600mbit
# International traffic class 1:3
${TC} class add dev ${IFACE_IMQ1} parent 1:1 classid 1:3 hfsc sc m1 0bit d 0us m2 300mbit ul m1 0bit d 0us m2 300mbit

# ADD HERE kind traffic: three four five ...

# default
${TC} class add dev ${IFACE_IMQ1} parent 1:1 classid 1:9 hfsc sc m1 0bit d 0us m2 1mbit ul m1 0bit d 0us m2 100mbit


now=$(date +%Y%m%d%H%M%S)
kind_traffic=$(echo "SELECT COUNT(id) FROM kind_traffic" | ${MYSQL} ${database} -u ${user} -p${password} -s)

# Services
query="SELECT serviceid, in_min0, in_max0, out_min0, out_max0, in_min1, in_max1, out_min1, out_max1, in_min2, in_max2, out_min2, out_max2, in_min3, in_max3, out_min3, out_max3, in_min4, in_max4, out_min4, out_max4 FROM services"
while read -r serviceid tmp; do

  export services${serviceid}="${serviceid} ${tmp}"
done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF


# Users

### Adding tc classes for users ###
query="SELECT userid, serviceid, free_access, expires FROM users"
while read -r userid serviceid free_access expires; do

  export payments${userid}="${free_access} ${expires}"

  service=$(eval echo \$services${serviceid})

  if [ -n "${service}" ]; then
    read -r serviceid in_min0 in_max0 out_min0 out_max0 in_min1 in_max1 out_min1 out_max1 in_min2 in_max2 out_min2 out_max2 in_min3 in_max3 out_min3 out_max3 in_min4 in_max4 out_min4 out_max4 <<EOF
$(echo ${service})
EOF

    ${TC} class add dev ${IFACE_IMQ0} parent 1:2 classid 1:$(printf '%x' ${userid}) hfsc sc m1 0bit d 0us m2 ${in_min0} ul m1 0bit d 0us m2 ${in_max0}
    ${TC} class add dev ${IFACE_IMQ1} parent 1:2 classid 1:$(printf '%x' ${userid}) hfsc sc m1 0bit d 0us m2 ${out_min0} ul m1 0bit d 0us m2 ${out_max0}

    if [ $in_max1 != "NULL" ] && [ $out_max1 != "NULL" ]; then
      ${TC} class add dev ${IFACE_IMQ0} parent 1:3 classid 1:$(printf '%x' $(expr ${userid} + ${STEP})) hfsc sc m1 0bit d 0us m2 ${in_min1} ul m1 0bit d 0us m2 ${in_max1}
      ${TC} class add dev ${IFACE_IMQ1} parent 1:3 classid 1:$(printf '%x' $(expr ${userid} + ${STEP})) hfsc sc m1 0bit d 0us m2 ${out_min1} ul m1 0bit d 0us m2 ${out_max1}
    fi
    if [ $in_max2 != "NULL" ] && [ $out_max2 != "NULL" ]; then
      ${TC} class add dev ${IFACE_IMQ0} parent 1:4 classid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP})) hfsc sc m1 0bit d 0us m2 ${in_min2} ul m1 0bit d 0us m2 ${in_max2}
      ${TC} class add dev ${IFACE_IMQ1} parent 1:4 classid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP})) hfsc sc m1 0bit d 0us m2 ${out_min2} ul m1 0bit d 0us m2 ${out_max2}
    fi
    if [ $in_max3 != "NULL" ] && [ $out_max3 != "NULL" ]; then
      ${TC} class add dev ${IFACE_IMQ0} parent 1:5 classid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP} + ${STEP})) hfsc sc m1 0bit d 0us m2 ${in_min3} ul m1 0bit d 0us m2 ${in_max3}
      ${TC} class add dev ${IFACE_IMQ1} parent 1:5 classid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP} + ${STEP})) hfsc sc m1 0bit d 0us m2 ${out_min3} ul m1 0bit d 0us m2 ${out_max3}
    fi
    if [ $in_max4 != "NULL" ] && [ $out_max4 != "NULL" ]; then
      ${TC} class add dev ${IFACE_IMQ0} parent 1:6 classid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP} + ${STEP} + ${STEP})) hfsc sc m1 0bit d 0us m2 ${in_min4} ul m1 0bit d 0us m2 ${in_max4}
      ${TC} class add dev ${IFACE_IMQ1} parent 1:6 classid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP} + ${STEP} + ${STEP})) hfsc sc m1 0bit d 0us m2 ${out_min4} ul m1 0bit d 0us m2 ${out_max4}
    fi
  fi
done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF


### Adding u32 - universal 32bit traffic control filters ###

# root filters
${TC} filter add dev ${IFACE_IMQ0} parent 1:0 prio 1 protocol ip u32
${TC} filter add dev ${IFACE_IMQ1} parent 1:0 prio 1 protocol ip u32

for row in ${NETWORKS}; do
  IFS=\. read -r a b c d <<EOF
$(echo ${row})
EOF

  htid=$(printf '%x' ${a})

  # filters for /16 subnets
  # tc filter rules work only with /16 subnets
  ${TC} filter add dev ${IFACE_IMQ0} parent 1: prio 1 handle ${htid}: protocol ip u32 divisor 256
  ${TC} filter add dev ${IFACE_IMQ0} protocol ip parent 1:0 prio 1 u32 ht 800:: match ip dst ${row} hashkey mask 0x0000ff00 at 16 link ${htid}:
  ${TC} filter add dev ${IFACE_IMQ1} parent 1: prio 1 handle ${htid}: protocol ip u32 divisor 256
  ${TC} filter add dev ${IFACE_IMQ1} protocol ip parent 1:0 prio 1 u32 ht 800:: match ip src ${row} hashkey mask 0x0000ff00 at 12 link ${htid}:

  for row2 in ${SUBNETS}; do
    IFS=\. read -r a2 b2 c2 d2 <<EOF
$(echo ${row2})
EOF

    if [ "${a}${b}" = "${a2}${b2}" ]; then

      subnet_hash=$(printf '%x' ${c2})

      if [ $c2 -eq 0 ]; then
        subnet_htid=$(printf '%x' $(expr ${a2} - 1))
      else
        subnet_htid=$(printf '%x' $(expr ${a2} + ${c2}))
      fi

      # filters for /24 subnets
      ${TC} filter add dev ${IFACE_IMQ0} parent 1: prio 1 handle ${subnet_htid}: protocol ip u32 divisor 256
      ${TC} filter add dev ${IFACE_IMQ0} protocol ip parent 1:0 prio 1 u32 ht ${htid}:${subnet_hash}: match ip dst ${row2} hashkey mask 0x000000ff at 16 link ${subnet_htid}:
      ${TC} filter add dev ${IFACE_IMQ1} parent 1: prio 1 handle ${subnet_htid}: protocol ip u32 divisor 256
      ${TC} filter add dev ${IFACE_IMQ1} protocol ip parent 1:0 prio 1 u32 ht ${htid}:${subnet_hash}: match ip src ${row2} hashkey mask 0x000000ff at 12 link ${subnet_htid}:
    fi
  done
done

if [ -f /tmp/allowed ]; then
  rm /tmp/allowed
fi

query="SELECT userid, ip, stopped FROM ip WHERE userid != 0"
while read -r userid ip stopped; do

  IFS=\. read -r a b c d <<EOF
$(echo ${ip})
EOF

  read -r free_access expires expires2 <<EOF
$(eval echo \$payments${userid})
EOF

  ip_hash=$(printf '%x' ${d})

  if [ $c -eq 0 ]; then
    htid=$(printf '%x' $(expr ${a} - 1))
  else
    htid=$(printf '%x' $(expr ${a} + ${c}))
  fi

  # The filter rules for IP address should start with checking the TOS field in the IPv4 header ( backwards )
  if [ ${kind_traffic} -ge 5 ]; then
    ${TC} filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht ${htid}:${ip_hash}: match ip dst ${ip}/32 match ip tos 0x5 0xff flowid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP} + ${STEP} + ${STEP}))
    ${TC} filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht ${htid}:${ip_hash}: match ip src ${ip}/32 match ip tos 0x5 0xff flowid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP} + ${STEP} + ${STEP}))
  fi
  if [ ${kind_traffic} -ge 4 ]; then
    ${TC} filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht ${htid}:${ip_hash}: match ip dst ${ip}/32 match ip tos 0x4 0xff flowid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP} + ${STEP}))
    ${TC} filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht ${htid}:${ip_hash}: match ip src ${ip}/32 match ip tos 0x4 0xff flowid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP} + ${STEP}))
  fi
  if [ ${kind_traffic} -ge 3 ]; then
    ${TC} filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht ${htid}:${ip_hash}: match ip dst ${ip}/32 match ip tos 0x3 0xff flowid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP}))
    ${TC} filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht ${htid}:${ip_hash}: match ip src ${ip}/32 match ip tos 0x3 0xff flowid 1:$(printf '%x' $(expr ${userid} + ${STEP} + ${STEP}))
  fi
  if [ ${kind_traffic} -ge 2 ]; then
    ${TC} filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht ${htid}:${ip_hash}: match ip dst ${ip}/32 match ip tos 0x2 0xff flowid 1:$(printf '%x' $(expr ${userid} + ${STEP}))
    ${TC} filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht ${htid}:${ip_hash}: match ip src ${ip}/32 match ip tos 0x2 0xff flowid 1:$(printf '%x' $(expr ${userid} + ${STEP}))
  fi
  ${TC} filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht ${htid}:${ip_hash}: match ip dst ${ip}/32 flowid 1:$(printf '%x' ${userid})
  ${TC} filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht ${htid}:${ip_hash}: match ip src ${ip}/32 flowid 1:$(printf '%x' ${userid})

  if [ "${expires}" != "0000-00-00" ]; then
    d=$(date -d "${expires} ${expires2}" +"%Y%m%d%H%M%S")
  else
    d=0
  fi

  # Allow internet access
  if [ "${stopped}" = "n" ] && ([ "${free_access}" = "y" ] || [ ${d} -gt ${now} ]); then
    echo "add allowed ${ip}" >> /tmp/allowed
  fi
done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF

$IPSET flush allowed
$IPSET restore -file /tmp/allowed
