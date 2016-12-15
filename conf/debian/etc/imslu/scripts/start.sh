#!/bin/bash

# http://tldp.org/LDP/Bash-Beginners-Guide/html/sect_10_02.html
# http://tldp.org/LDP/abs/html/arrays.html
# http://wiki.bash-hackers.org/syntax/arrays?s[]=array
# http://wiki.bash-hackers.org/commands/builtin/read

. /etc/imslu/config.sh

# remove any existing qdiscs
$TC qdisc del dev $IFACE_IMQ0 root 2> /dev/null
$TC qdisc del dev $IFACE_IMQ0 ingress 2> /dev/null
$TC qdisc del dev $IFACE_IMQ1 root 2> /dev/null
$TC qdisc del dev $IFACE_IMQ1 ingress 2> /dev/null

$TC qdisc del dev $IFACE_IMQ0 root 2> /dev/null
$TC qdisc del dev $IFACE_IMQ0 ingress 2> /dev/null
$TC qdisc del dev $IFACE_IMQ1 root 2> /dev/null
$TC qdisc del dev $IFACE_IMQ1 ingress 2> /dev/null

# install HFSC under WAN to limit download
$TC qdisc add dev $IFACE_IMQ0 root handle 1: hfsc default 9
# root class :1
$TC class add dev $IFACE_IMQ0 parent 1: classid 1:1 hfsc sc m1 0bit d 0us m2 1000mbit ul m1 0bit d 0us m2 1000mbit
# BGP peer - national traffic class 1:2
$TC class add dev $IFACE_IMQ0 parent 1:1 classid 1:2 hfsc sc m1 0bit d 0us m2 600mbit ul m1 0bit d 0us m2 600mbit
# International traffic class 1:3
$TC class add dev $IFACE_IMQ0 parent 1:1 classid 1:3 hfsc sc m1 0bit d 0us m2 300mbit ul m1 0bit d 0us m2 300mbit

# ADD HERE kind traffic: three four five ...

# default
$TC class add dev $IFACE_IMQ0 parent 1:1 classid 1:9 hfsc sc m1 0bit d 0us m2 1mbit ul m1 0bit d 0us m2 100mbit

# install HFSC under LAN to limit upload
$TC qdisc add dev $IFACE_IMQ1 root handle 1: hfsc default 9
# root class :1
$TC class add dev $IFACE_IMQ1 parent 1: classid 1:1 hfsc sc m1 0bit d 0us m2 1000mbit ul m1 0bit d 0us m2 1000mbit
# BGP peer - national traffic class 1:2
$TC class add dev $IFACE_IMQ1 parent 1:1 classid 1:2 hfsc sc m1 0bit d 0us m2 600mbit ul m1 0bit d 0us m2 600mbit
# International traffic class 1:3
$TC class add dev $IFACE_IMQ1 parent 1:1 classid 1:3 hfsc sc m1 0bit d 0us m2 300mbit ul m1 0bit d 0us m2 300mbit

# ADD HERE kind traffic: three four five ...

# default
$TC class add dev $IFACE_IMQ1 parent 1:1 classid 1:9 hfsc sc m1 0bit d 0us m2 1mbit ul m1 0bit d 0us m2 100mbit


#declare variables
now=$(date +%Y%m%d%H%M%S)
declare -a services users payments ipaddresses
declare -i kind_traffic userid serviceid
declare -i a b c d a2 b2 c2 d2
kind_traffic=$(echo "SELECT COUNT(id) FROM kind_traffic" | mysql $database -u $user -p${password} -s)

####### Services #######
query="SELECT serviceid, in_min0, in_max0, out_min0, out_max0, in_min1, in_max1, out_min1, out_max1, in_min2, in_max2, out_min2, out_max2, in_min3, in_max3, out_min3, out_max3, in_min4, in_max4, out_min4, out_max4 FROM services"
while read -r row; do
    read -r serviceid tmp <<< "${row}"
    services[${serviceid}]=$row

done < <(echo $query | mysql $database -u $user -p${password} -s)
#echo ${services[@]}
#echo ${!services[@]}


####### Users #######
query="SELECT userid, serviceid, free_access, expires FROM users"
while read -r userid serviceid free_access expires; do
  users[${userid}]="${userid} ${serviceid}"
  payments[${userid}]="${free_access} ${expires}"

done < <(echo $query | mysql $database -u $user -p${password} -s)
#echo ${users[@]}
#echo ${!users[@]}
#echo ${payments[@]}
#echo ${!payments[@]}

### Add tc class rules for users ###
for row in "${users[@]}"; do
  read -r userid serviceid tmp <<< "${row}"

  if [ -n "${services[${serviceid}]}" ]; then
    read -r serviceid in_min0 in_max0 out_min0 out_max0 in_min1 in_max1 out_min1 out_max1 in_min2 in_max2 out_min2 out_max2 in_min3 in_max3 out_min3 out_max3 in_min4 in_max4 out_min4 out_max4 <<< "${services[${serviceid}]}"

$TC class add dev ${IFACE_IMQ0} parent 1:2 classid 1:$(printf '%x' ${userid}) hfsc sc m1 0bit d 0us m2 ${in_min0} ul m1 0bit d 0us m2 ${in_max0}
$TC class add dev ${IFACE_IMQ1} parent 1:2 classid 1:$(printf '%x' ${userid}) hfsc sc m1 0bit d 0us m2 ${out_min0} ul m1 0bit d 0us m2 ${out_max0}

    if [[ $in_max1 != "NULL" && $out_max1 != "NULL" ]]; then
$TC class add dev ${IFACE_IMQ0} parent 1:3 classid 1:$(printf '%x' $((${userid}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${in_min1} ul m1 0bit d 0us m2 ${in_max1}
$TC class add dev ${IFACE_IMQ1} parent 1:3 classid 1:$(printf '%x' $((${userid}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${out_min1} ul m1 0bit d 0us m2 ${out_max1}
    fi
    if [[ $in_max2 != "NULL" && $out_max2 != "NULL" ]]; then
$TC class add dev ${IFACE_IMQ0} parent 1:4 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${in_min2} ul m1 0bit d 0us m2 ${in_max2}
$TC class add dev ${IFACE_IMQ1} parent 1:4 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${out_min2} ul m1 0bit d 0us m2 ${out_max2}
    fi
    if [[ $in_max3 != "NULL" && $out_max3 != "NULL" ]]; then
$TC class add dev ${IFACE_IMQ0} parent 1:5 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${in_min3} ul m1 0bit d 0us m2 ${in_max3}
$TC class add dev ${IFACE_IMQ1} parent 1:5 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${out_min3} ul m1 0bit d 0us m2 ${out_max3}
    fi
    if [[ $in_max4 != "NULL" && $out_max4 != "NULL" ]]; then
$TC class add dev ${IFACE_IMQ0} parent 1:6 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${in_min4} ul m1 0bit d 0us m2 ${in_max4}
$TC class add dev ${IFACE_IMQ1} parent 1:6 classid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}+${STEP}))) hfsc sc m1 0bit d 0us m2 ${out_min4} ul m1 0bit d 0us m2 ${out_max4}
    fi
  fi
done


### Add tc u32 hashing filter rules ###

# root filters
$TC filter add dev ${IFACE_IMQ0} parent 1:0 prio 1 protocol ip u32
$TC filter add dev ${IFACE_IMQ1} parent 1:0 prio 1 protocol ip u32

for row in "${!NETWORKS[@]}"; do
    IFS=\. read -r a b c d <<< "$row"
#    echo $a $b $c $d

# master filters for /16 subnet
# tc filter rules work only with /16 subnets
$TC filter add dev ${IFACE_IMQ0} parent 1: prio 1 handle $(printf '%x' $a): protocol ip u32 divisor 256
$TC filter add dev ${IFACE_IMQ0} protocol ip parent 1:0 prio 1 u32 ht 800:: match ip dst ${row} hashkey mask 0x0000ff00 at 16 link $(printf '%x' $a):
$TC filter add dev ${IFACE_IMQ1} parent 1: prio 1 handle $(printf '%x' $a): protocol ip u32 divisor 256
$TC filter add dev ${IFACE_IMQ1} protocol ip parent 1:0 prio 1 u32 ht 800:: match ip src ${row} hashkey mask 0x0000ff00 at 12 link $(printf '%x' $a):

    for row2 in ${NETWORKS[${row}]}; do
        IFS=\. read -r a2 b2 c2 d2 <<< "$row2"
#        echo $a2 $b2 $c2 $d2

        if [ $c2 -eq 0 ]; then
            hash_table=$((${a2}-1))
        else
            hash_table=$((${a2}+${c2}))
        fi

# secondary filters for /24 subnet
$TC filter add dev ${IFACE_IMQ0} parent 1: prio 1 handle $(printf '%x' $hash_table): protocol ip u32 divisor 256
$TC filter add dev ${IFACE_IMQ0} protocol ip parent 1:0 prio 1 u32 ht $(printf '%x' $a):$(printf '%x' $c2): match ip dst ${row2} hashkey mask 0x000000ff at 16 link $(printf '%x' $hash_table):
$TC filter add dev ${IFACE_IMQ1} parent 1: prio 1 handle $(printf '%x' $hash_table): protocol ip u32 divisor 256
$TC filter add dev ${IFACE_IMQ1} protocol ip parent 1:0 prio 1 u32 ht $(printf '%x' $a):$(printf '%x' $c2): match ip src ${row2} hashkey mask 0x000000ff at 12 link $(printf '%x' $hash_table):
    done
done

i=0
query="SELECT userid, ip, stopped FROM ip WHERE userid != 0"
while read -r row; do
  ipaddresses[${i}]=${row}
  ((i++))

done < <(echo $query | mysql $database -u $user -p${password} -s)
#echo ${ipaddresses[@]}
#echo ${!ipaddresses[@]}

if [ -f /tmp/allowed ]; then
  rm /tmp/allowed
fi

for row in "${ipaddresses[@]}"; do
  read -r userid ip stopped <<< "${row}"
  IFS=\. read -r a b c d <<< "${ip}"
  read -r free_access expires expires2 <<< "${payments[${userid}]}"

  if [ $c -eq 0 ]; then
    hash_table=$((${a}-1))
  else
    hash_table=$((${a}+${c}))
  fi

  # The filter rules for IP address should start with checking the TOS field in the IPv4 header ( backwards )
  if [ $kind_traffic -ge 5 ]; then
$TC filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 match ip tos 0x5 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}+${STEP})))
$TC filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 match ip tos 0x5 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP}+${STEP})))
  fi
  if [ $kind_traffic -ge 4 ]; then
$TC filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 match ip tos 0x4 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP})))
$TC filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 match ip tos 0x4 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP}+${STEP})))
  fi
  if [ $kind_traffic -ge 3 ]; then
$TC filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 match ip tos 0x3 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP})))
$TC filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 match ip tos 0x3 0xff flowid 1:$(printf '%x' $((${userid}+${STEP}+${STEP})))
  fi
  if [ $kind_traffic -ge 2 ]; then
$TC filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 match ip tos 0x2 0xff flowid 1:$(printf '%x' $((${userid}+${STEP})))
$TC filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 match ip tos 0x2 0xff flowid 1:$(printf '%x' $((${userid}+${STEP})))
  fi
$TC filter add dev ${IFACE_IMQ0} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip dst ${ip}/32 flowid 1:$(printf '%x' ${userid})
$TC filter add dev ${IFACE_IMQ1} parent 1: protocol ip prio 1 u32 ht $(printf '%x' $hash_table):$(printf '%x' $d): match ip src ${ip}/32 flowid 1:$(printf '%x' ${userid})


  # Allow internet access
  if [[ ${stopped} == "n" && (${free_access} == "y" || $(date -d "${expires} ${expires2}" +"%Y%m%d%H%M%S") -gt ${now}) ]]; then
    echo "add allowed ${ip}" >> /tmp/allowed
  fi
done

$IPSET flush allowed
$IPSET restore -file /tmp/allowed

if [ $USE_VLANS -eq 0 ]; then

  # Adding routing and static MAC for IP addresses who have vlan.
  query="SELECT ip, vlan, free_mac, mac FROM ip WHERE userid != 0 AND protocol != 'PPPoE' AND vlan NOT LIKE ''"
  while read -r ip vlan free_mac mac; do

    if [ -f /proc/net/vlan/${vlan} ]; then
      IFS=\. read -r a b c d <<< "${ip}"
#     ip route add 10.0.1.2 dev eth1.0010 src 10.0.1.1
      ip route add ${ip} dev ${vlan} src ${a}.${b}.${c}.1

      if [[ "${free_mac}" == "n" && -n "${mac}" ]]; then
#       arp -i eth1.0010 -s 10.0.1.2 34:23:87:96:70:27
        arp -i ${vlan} -s ${ip} ${mac}
      fi
    fi
  done < <(echo $query | mysql $database -u $user -p${password} -s)
else

  # Adding static MAC for IP addresses.
  query="SELECT ip, mac, free_mac FROM ip WHERE userid != 0 AND protocol != 'PPPoE' AND mac NOT LIKE '' AND free_mac='n'"
  while read -r ip mac free_mac; do

    IFS=\. read -r a b c d <<< "${ip}"
#   ip route add 10.0.1.2 dev eth1 src 10.0.1.1
    ip route add ${ip} dev $IFACE_INTERNAL src ${a}.${b}.${c}.1

#   arp -s 10.0.1.2 34:23:87:96:70:27
    arp -s ${ip} ${mac}
  done < <(echo $query | mysql $database -u $user -p${password} -s)
fi
