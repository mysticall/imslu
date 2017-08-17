#!/bin/sh

ip=$4
sed -i '' -e "/${ip}/d" /tmp/ip_activity_pppoe
sed -i '' -e "/${ip}/d" /tmp/ip_activity
exit 0
