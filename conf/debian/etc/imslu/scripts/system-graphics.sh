#!/bin/bash

# http://martybugs.net/linux/rrdtool/traffic.cgi
# http://oss.oetiker.ch/rrdtool/doc/
# http://kamenitza.org/archives/123
# https://wiki.alpinelinux.org/wiki/Setting_up_traffic_monitoring_using_rrdtool_(and_snmp)
# https://hookrace.net/blog/server-statistics/

. /etc/imslu/config.sh

declare -A bits
declare -A packets 
while read -r Interface RXbytes RXpackets RXerrs RXdrop RXfifo RXframe RXcompressed RXmulticast TXbytes TXpackets TXerrs TXdrop TXfifo TXcolls TXcarrier TXcompressed; do

    iface=${Interface:0:-1}

    if [[ $iface == $IFACE_IMQ0 ]]; then
        bits[${iface}]=$((${RXbytes}*8))
        packets[${iface}]=${RXpackets}
    elif [[ $iface == $IFACE_IMQ1 ]]; then
        bits[${iface}]=$((${TXbytes}*8))
        packets[${iface}]=${TXpackets}
    else
        bits[${iface}]=$((${RXbytes}*8)):$((${TXbytes}*8))
        packets[${iface}]=${RXpackets}:${TXpackets}
    fi
done < <(cat /proc/net/dev | grep ":")


####### IMQ GRAPHICS #######
create_imq () {

$RRDTOOL create ${RRD_DIR}/imq_traffic.rrd \
-s 300 \
DS:RX:DERIVE:600:0:U \
DS:RX1:DERIVE:600:0:U \
DS:RX2:DERIVE:600:0:U \
DS:TX:DERIVE:600:0:U \
DS:TX1:DERIVE:600:0:U \
DS:TX2:DERIVE:600:0:U \
RRA:AVERAGE:0.5:1:576 \
RRA:AVERAGE:0.5:6:672 \
RRA:AVERAGE:0.5:24:73 \
RRA:AVERAGE:0.5:144:1460

$RRDTOOL create ${RRD_DIR}/imq_packets.rrd \
-s 300 \
DS:RX:DERIVE:600:0:U \
DS:TX:DERIVE:600:0:U \
RRA:AVERAGE:0.5:1:576 \
RRA:AVERAGE:0.5:6:672 \
RRA:AVERAGE:0.5:24:73 \
RRA:AVERAGE:0.5:144:1460

chmod 755 ${RRD_DIR}/imq_traffic.rrd 
chmod 755 ${RRD_DIR}/imq_packets.rrd 
}

update_imq () {

declare -A in_bits
in_bits[2]=0
in_bits[3]=0

declare -A out_bits
out_bits[2]=0
out_bits[3]=0

local previous=''

while read -r str1 str2 str3 str4 str5 str6; do

    if [[ ${str1} == "class" ]]; then

        if [[ ${str3} == "1:2" ]]; then
            previous=2
        elif [[ ${str3} == "1:3" ]]; then
            previous=3
        fi

    elif [[ -n ${previous} && ${str1} == "period" ]]; then
        in_bits[${previous}]=$((${str4}*8))
        previous=''
    fi
done < <($TC -s class show dev ${IFACE_IMQ0})

while read -r str1 str2 str3 str4 str5 str6; do

    if [[ ${str1} == "class" ]]; then

        if [[ ${str3} == "1:2" ]]; then
            previous=2
        elif [[ ${str3} == "1:3" ]]; then
            previous=3
        fi

    elif [[ -n ${previous} && ${str1} == "period" ]]; then
        out_bits[${previous}]=$((${str4}*8))
        previous=''
    fi
done < <($TC -s class show dev ${IFACE_IMQ1})
# echo ${in_bits[@]}
# echo ${!in_bits[@]}
# echo ${out_bits[@]}
# echo ${!out_bits[@]}

if [ -f ${RRD_DIR}/imq_traffic.rrd ]; then
    $RRDTOOL update ${RRD_DIR}/imq_traffic.rrd -t RX:RX1:RX2:TX:TX1:TX2 N:${bits[${IFACE_IMQ0}]}:${in_bits[2]}:${in_bits[3]}:${bits[${IFACE_IMQ1}]}:${out_bits[2]}:${out_bits[3]}
    $RRDTOOL update ${RRD_DIR}/imq_packets.rrd N:${packets[${IFACE_IMQ0}]}:${packets[${IFACE_IMQ1}]}
else
    create_imq
    $RRDTOOL update ${RRD_DIR}/imq_traffic.rrd N:${bits[${IFACE_IMQ0}]}:${in_bits[2]}:${in_bits[3]}:${bits[${IFACE_IMQ1}]}:${out_bits[2]}:${out_bits[3]}
    $RRDTOOL update ${RRD_DIR}/imq_packets.rrd N:${packets[${IFACE_IMQ0}]}:${packets[${IFACE_IMQ1}]}
fi
}

graph_imq () {
# $1: interval (ie, day, week, month, year)

if [[ $1 == "day" ]]; then
    interval='172800'
elif [[ $1 == "week" ]]; then
    interval='604800'
elif [[ $1 == "month" ]]; then
    interval='2678400'
elif [[ $1 == "year" ]]; then
    interval='31536000'
fi

${RRDTOOL} graph ${RRD_IMG}/imq_traffic-$1.png \
-s -${interval} -e now \
-v "Bits per second" \
-w 800 -h 130 \
-l 0 \
--lazy \
-a PNG \
`# Fetch data from RRD file` \
DEF:RX=${RRD_DIR}/imq_traffic.rrd:RX:AVERAGE \
DEF:RX1=${RRD_DIR}/imq_traffic.rrd:RX1:AVERAGE \
DEF:RX2=${RRD_DIR}/imq_traffic.rrd:RX2:AVERAGE \
DEF:TX=${RRD_DIR}/imq_traffic.rrd:TX:AVERAGE \
DEF:TX1=${RRD_DIR}/imq_traffic.rrd:TX1:AVERAGE \
DEF:TX2=${RRD_DIR}/imq_traffic.rrd:TX2:AVERAGE \
CDEF:TX_neg=TX,-1,* \
CDEF:TX1_neg=TX1,-1,* \
CDEF:TX2_neg=TX2,-1,* \
`# Calculate aggregates based on data` \
VDEF:RX_a=RX,AVERAGE \
VDEF:RX_m=RX,MAXIMUM \
VDEF:RX_c=RX,LAST \
VDEF:RX1_a=RX1,AVERAGE \
VDEF:RX1_m=RX1,MAXIMUM \
VDEF:RX1_c=RX1,LAST \
VDEF:RX2_a=RX2,AVERAGE \
VDEF:RX2_m=RX2,MAXIMUM \
VDEF:RX2_c=RX2,LAST \
VDEF:TX_a=TX,AVERAGE \
VDEF:TX_m=TX,MAXIMUM \
VDEF:TX_c=TX,LAST \
VDEF:TX1_a=TX1,AVERAGE \
VDEF:TX1_m=TX1,MAXIMUM \
VDEF:TX1_c=TX1,LAST \
VDEF:TX2_a=TX2,AVERAGE \
VDEF:TX2_m=TX2,MAXIMUM \
VDEF:TX2_c=TX2,LAST \
`# Print RX` \
AREA:RX#32CD32:Incoming \
LINE1:RX#336600 \
GPRINT:RX_m:"Max\: %5.1lf %sb" \
GPRINT:RX_a:"Avg\: %5.1lf %sb" \
GPRINT:RX_c:"Cur\: %5.1lf %sb\n" \
AREA:RX1#00A100:"    peer" \
LINE1:RX1#336600 \
GPRINT:RX1_m:"Max\: %5.1lf %sb" \
GPRINT:RX1_a:"Avg\: %5.1lf %sb" \
GPRINT:RX1_c:"Cur\: %5.1lf %sb\n" \
AREA:RX2#007A00:"     int" \
LINE1:RX2#336600 \
GPRINT:RX2_m:"Max\: %5.1lf %sb" \
GPRINT:RX2_a:"Avg\: %5.1lf %sb" \
GPRINT:RX2_c:"Cur\: %5.1lf %sb\n" \
`# Print TX` \
AREA:TX_neg#4169E1:Outgoing \
LINE1:TX_neg#0033CC \
GPRINT:TX_m:"Max\: %5.1lf %sb" \
GPRINT:TX_a:"Avg\: %5.1lf %sb" \
GPRINT:TX_c:"Cur\: %5.1lf %sb\n" \
AREA:TX1_neg#3151B0:"    peer" \
LINE1:TX1_neg#0033CC \
GPRINT:TX1_m:"Max\: %5.1lf %sb" \
GPRINT:TX1_a:"Avg\: %5.1lf %sb" \
GPRINT:TX1_c:"Cur\: %5.1lf %sb\n" \
AREA:TX2_neg#203D90:"     int" \
LINE1:TX2_neg#0033CC \
GPRINT:TX2_m:"Max\: %5.1lf %sb" \
GPRINT:TX2_a:"Avg\: %5.1lf %sb" \
GPRINT:TX2_c:"Cur\: %5.1lf %sb\n" \
HRULE:0#000000

${RRDTOOL} graph ${RRD_IMG}/imq_packets-$1.png \
-s -${interval} -e now \
-v "Packets per second" \
-w 800 -h 130 \
-l 0 \
--lazy \
-a PNG \
`# Fetch data from RRD file` \
DEF:RX=${RRD_DIR}/imq_packets.rrd:RX:AVERAGE \
DEF:TX=${RRD_DIR}/imq_packets.rrd:TX:AVERAGE \
CDEF:TX_neg=TX,-1,* \
`# Calculate aggregates based on data` \
VDEF:RX_a=RX,AVERAGE \
VDEF:RX_m=RX,MAXIMUM \
VDEF:RX_c=RX,LAST \
VDEF:TX_a=TX,AVERAGE \
VDEF:TX_m=TX,MAXIMUM \
VDEF:TX_c=TX,LAST \
`# Print RX` \
AREA:RX#32CD32:Incoming \
LINE1:RX#336600 \
GPRINT:RX_m:"Max\: %5.1lf %s" \
GPRINT:RX_a:"Avg\: %5.1lf %s" \
GPRINT:RX_c:"Cur\: %5.1lf %s\n" \
`# Print TX` \
AREA:TX_neg#4169E1:Outgoing \
LINE1:TX_neg#0033CC \
GPRINT:TX_m:"Max\: %5.1lf %s" \
GPRINT:TX_a:"Avg\: %5.1lf %s" \
GPRINT:TX_c:"Cur\: %5.1lf %s\n" \
HRULE:0#000000
}

case "$1" in
update)
	update_imq
    graph_imq day
	;;

graph_imq)
	graph_imq day
	graph_imq week
	graph_imq month
	graph_imq year
	;;

*)
	echo "Usage: /etc/imslu/scripts/system-graphics.sh {update}"
	exit 1
	;;
esac




