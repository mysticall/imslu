#!/bin/sh

# http://martybugs.net/linux/rrdtool/traffic.cgi
# http://oss.oetiker.ch/rrdtool/doc/
# http://kamenitza.org/archives/123
# https://wiki.alpinelinux.org/wiki/Setting_up_traffic_monitoring_using_rrdtool_(and_snmp)
# https://hookrace.net/blog/server-statistics/

. /etc/imslu/config.sh

####### GRAPHICS #######
create () {

# Bits per second (bps)
$RRDTOOL create ${RRD_DIR}/bps.rrd \
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

# Packets per second (pps)
$RRDTOOL create ${RRD_DIR}/pps.rrd \
-s 300 \
DS:RX:DERIVE:600:0:U \
DS:TX:DERIVE:600:0:U \
RRA:AVERAGE:0.5:1:576 \
RRA:AVERAGE:0.5:6:672 \
RRA:AVERAGE:0.5:24:73 \
RRA:AVERAGE:0.5:144:1460

chmod 755 ${RRD_DIR}/bps.rrd 
chmod 755 ${RRD_DIR}/pps.rrd 
}

update () {

while read -r Interface RXbytes RXpackets RXerrs RXdrop RXfifo RXframe RXcompressed RXmulticast TXbytes TXpackets TXerrs TXdrop TXfifo TXcolls TXcarrier TXcompressed; do

    iface=$(expr "${Interface}" : "\(imq[0-9]\)")

    if [ ${iface} = ${IFACE_IMQ0} ]; then
        export in_total_bps=${RXbytes}
        export in_total_pps=${RXpackets}
    elif [ ${iface} = ${IFACE_IMQ1} ]; then
        export out_total_bps=${TXbytes}
        export out_total_pps=${TXpackets}
    fi

done <<EOF
$(cat /proc/net/dev | grep "${IFACE_IMQ0}:\|${IFACE_IMQ1}:")
EOF

local previous=''

while read -r str1 str2 str3 str4 str5 str6; do

    if [ ${str1} = "class" ]; then
        if [ ${str3} = "1:2" ]; then
            previous=2
        elif [ ${str3} = "1:3" ]; then
            previous=3
        fi
    elif [ -n ${previous} ] && [ ${str1} = "period" ]; then
        if [ ${previous} -eq 2 ]; then
            export in_peer_bps=${str4}
        else
            export in_int_bps=${str4}
        fi
        previous=''
    fi
done <<EOF
$(${TC} -s class show dev ${IFACE_IMQ0} | grep -A 3 "1:2 parent 1:1\|1:3 parent 1:1")
EOF

while read -r str1 str2 str3 str4 str5 str6; do

    if [ ${str1} = "class" ]; then
        if [ ${str3} = "1:2" ]; then
            previous=2
        elif [ ${str3} = "1:3" ]; then
            previous=3
        fi
    elif [ -n ${previous} ] && [ ${str1} = "period" ]; then
        if [ ${previous} -eq 2 ]; then
            export out_peer_bps=${str4}
        else
            export out_int_bps=${str4}
        fi
        previous=''
    fi
done <<EOF
$(${TC} -s class show dev ${IFACE_IMQ1} | grep -A 3 "1:2 parent 1:1\|1:3 parent 1:1")
EOF


if [ ! -d ${RRD_DIR} ]; then
    mkdir -p ${RRD_DIR}
    chmod 755 ${RRD_DIR}
fi

if [ ! -f ${RRD_DIR}/bps.rrd ]; then
    create
fi

${RRDTOOL} update ${RRD_DIR}/bps.rrd N:`expr ${in_total_bps} \* 8`:`expr ${in_peer_bps} \* 8`:`expr ${in_int_bps} \* 8`:`expr ${out_total_bps} \* 8`:`expr ${out_peer_bps} \* 8`:`expr ${out_int_bps} \* 8`
${RRDTOOL} update ${RRD_DIR}/pps.rrd N:${in_total_pps}:${out_total_pps}
}

graph () {
# $1: interval (ie, day, week, month, year)

if [ $1 = "day" ]; then
    interval='172800'
elif [ $1 = "week" ]; then
    interval='604800'
elif [ $1 = "month" ]; then
    interval='2678400'
elif [ $1 = "year" ]; then
    interval='31536000'
fi

if [ ! -d ${RRD_IMG} ]; then
    mkdir ${RRD_IMG}
    chmod 777 ${RRD_IMG}
fi

${RRDTOOL} graph ${RRD_IMG}/bps-${1}.png \
-s -${interval} -e now \
-v "Bits per second" \
-w 800 -h 130 \
-l 0 \
--lazy \
-a PNG \
`# Fetch data from RRD file` \
DEF:RX=${RRD_DIR}/bps.rrd:RX:AVERAGE \
DEF:RX1=${RRD_DIR}/bps.rrd:RX1:AVERAGE \
DEF:RX2=${RRD_DIR}/bps.rrd:RX2:AVERAGE \
DEF:TX=${RRD_DIR}/bps.rrd:TX:AVERAGE \
DEF:TX1=${RRD_DIR}/bps.rrd:TX1:AVERAGE \
DEF:TX2=${RRD_DIR}/bps.rrd:TX2:AVERAGE \
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
AREA:RX#01CC01:Incoming \
LINE1:RX#336600 \
GPRINT:RX_m:"Max\: %5.1lf %sb" \
GPRINT:RX_a:"Avg\: %5.1lf %sb" \
GPRINT:RX_c:"Cur\: %5.1lf %sb\n" \
AREA:RX1#00AA00:"    peer" \
LINE1:RX1#336600 \
GPRINT:RX1_m:"Max\: %5.1lf %sb" \
GPRINT:RX1_a:"Avg\: %5.1lf %sb" \
GPRINT:RX1_c:"Cur\: %5.1lf %sb\n" \
AREA:RX2#007700:"     int" \
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

${RRDTOOL} graph ${RRD_IMG}/pps-$1.png \
-s -${interval} -e now \
-v "Packets per second" \
-w 800 -h 130 \
-l 0 \
--lazy \
-a PNG \
`# Fetch data from RRD file` \
DEF:RX=${RRD_DIR}/pps.rrd:RX:AVERAGE \
DEF:TX=${RRD_DIR}/pps.rrd:TX:AVERAGE \
CDEF:TX_neg=TX,-1,* \
`# Calculate aggregates based on data` \
VDEF:RX_a=RX,AVERAGE \
VDEF:RX_m=RX,MAXIMUM \
VDEF:RX_c=RX,LAST \
VDEF:TX_a=TX,AVERAGE \
VDEF:TX_m=TX,MAXIMUM \
VDEF:TX_c=TX,LAST \
`# Print RX` \
AREA:RX#01CC01:Incoming \
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
    update
    graph day
    ;;

graph)
    graph day
    graph week
    graph month
    graph year
    ;;

*)
    echo "Usage: /etc/imslu/scripts/system-graphics.sh {update}"
    exit 1
    ;;
esac




