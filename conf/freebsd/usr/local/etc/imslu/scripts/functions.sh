#!/bin/sh

# Start PPPoE server
pppoe_add () {

  ${PPPOE_SERVER} -I $1 -C ${PPPOE_SERVER_NAME} -S ${PPPOE_SERVER_NAME} -L ${PPPOE_DEFAULT_IP} -N 1000 -k -r
}

# Stop PPPoE sessions and PPPoE servers
pppoe_rem () {

  local PID

  for PID in $(ps -C pppd -o pid=); do

    kill -9 ${PID}
  done
  sleep 5
  for PID in $(ps -C pppoe-server -o pid=); do

  kill -9 ${PID}
  done
}

create_vlan () {

  if [ $USE_VLANS -eq 0 ]; then

    local VLAN_ID
    local IFACE
    VLAN_SEQ=$(echo $VLAN_SEQ | tr '\n' ' ')

    for VLAN_ID in ${VLAN_SEQ}; do

      IFACE=${IFACE_INTERNAL}.$(printf %04d ${VLAN_ID})

      $IFCONFIG ${IFACE} create vlan ${VLAN_ID} vlandev ${IFACE_INTERNAL}
      $IFCONFIG ${IFACE} up

      for DEFAULT_IP in ${DEFAULT_GATEWAYS}; do
        $IFCONFIG ${IFACE} inet ${DEFAULT_IP} add
      done

      # Start PPPoE servers
      if [ $USE_PPPoE -eq 0 ]; then

        pppoe_add ${IFACE}
      fi
    done
  else
    echo "USE_VLANS=false in /usr/local/etc/imslu/config.sh"
  fi
}

remove_vlan () {

  if [ $USE_VLANS -eq 0 ]; then

    local VLAN_ID
    local IFACE
    VLAN_SEQ=$(echo $VLAN_SEQ | tr '\n' ' ')

    # Stop PPPoE sessions and PPPoE servers
    if [ $USE_PPPoE -eq 0 ]; then

      pppoe_rem
    fi

    for VLAN_ID in ${VLAN_SEQ}; do

      IFACE=${IFACE_INTERNAL}.$(printf %04d ${VLAN_ID})
      $IFCONFIG ${IFACE} destroy
    done
  else
    echo "USE_VLANS=false in /usr/local/etc/imslu/config.sh"
  fi
}
