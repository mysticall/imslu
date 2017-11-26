#!/bin/sh

NC=/usr/bin/nc
MPD_CONF=/usr/local/etc/mpd5/mpd.conf
#  host=localhost
#  port=5005
#  user=imslu
#  passwd=imslu

# Reading the settings from mpd.conf
while read -r a b c d e; do
    if [ "${a}" == "set" ] && [ "${b}" == "user" ]; then
        user=${c}
        passwd=${d}
        export user passwd
    elif [ "${a}" == "set" ] && [ "${b}" == "console" ] && [ "${c}" == "self" ]; then
        host=${d}
        port=${e}
        export host port
    fi

    if [ -n "${user}" ] && [ -n "${host}" ]; then
        break
    fi
done <"${MPD_CONF}"

case "${1}" in
close_session)
    ip=${2}
    # Closing the session
    while read -r Iface IP Bundle MultiSessionId Link Id SessionId PeerAuthname MAC; do
        if [ -n "${SessionId}" ]; then
            echo -e "${user}\n${passwd}\nsession ${SessionId}\nclose\nexit\n" | ${NC} ${host} ${port}
        fi
    done <<EOF
$(echo -e "${user}\n${passwd}\nshow session ip ${ip}\nexit\n" | ${NC} ${host} ${port} | grep ng)
EOF
	;;

*)
    echo "Usage: /usr/local/etc/imslu/scripts/mpd.sh {'close_session' 'ip'|}"
    exit 1
    ;;
esac

exit 0
