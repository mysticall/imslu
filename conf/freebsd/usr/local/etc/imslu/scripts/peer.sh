#!/bin/sh

. /usr/local/etc/imslu/config.sh

rm -f /tmp/peers
rm -f /tmp/peers_temp

fetch -q "${PEER}" -o /tmp/peers

if [ -f /tmp/peers ] && [ -s /tmp/peers ]; then
  sed -i -e '/#/d' /tmp/peers

  for subnet in $(cat /tmp/peers); do

    echo "table 11 add ${subnet}" >> /tmp/peers_temp
  done

${IPFW} table 11 flush
${IPFW} -q /tmp/peers_temp
fi
