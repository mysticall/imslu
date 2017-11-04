#!/bin/sh

. /etc/imslu/config.sh

now=$(date +%Y%m%d%H%M%S)

# services
query="SELECT serviceid, price FROM services"

while read -r serviceid price; do

    export services${serviceid}="${price}"

done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF


# payments
query="SELECT userid, serviceid, pay, free_access, not_excluding, expires, name FROM users"

# Reading users from database and export user info
while read -r userid tmp; do

    export users${userid}="${tmp}"

done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF

> /tmp/allowed_temp

query="SELECT userid, ip, stopped FROM ip WHERE userid != 0"

while read -r userid ip stopped; do

  found=$(eval echo \$users${userid})
  if [ -n "${found}" ]; then

    read -r serviceid pay free_access not_excluding expires expires2 name <<EOF
$(echo ${found})
EOF

    if [ "${expires}" != "0000-00-00" ]; then
      d=$(date -d "${expires} ${expires2}" +"%Y%m%d%H%M%S")
    else
      d=0
    fi
  
    if [ "${stopped}" = "n" ] && ([ "${free_access}" = "y" ] || [ ${d} -gt ${now} ]); then

        echo "add allowed_temp ${ip}" >> /tmp/allowed_temp

    elif [ "${stopped}" = "n" ] && [ "${not_excluding}" = "y" ] && [ ${d} -lt ${now} ]; then

        echo "add allowed_temp ${ip}" >> /tmp/allowed_temp

        if [ "${expires}" != "0000-00-00" ]; then
            expires="$(date +%Y-%m-%d -d "${expires} + ${FEE_PERIOD}") 23:59:00"
        else
            expires="$(date +%Y-%m-%d -d "+ ${FEE_PERIOD}") 23:59:00"
        fi

        date_payment1=$(date +"%Y-%m-%d %H:%M:%S")

        if [ "${pay}" != "0.00" ]; then
            sum=${pay}
        else
            sum=$(eval echo \${services${serviceid}})
        fi

        ${MYSQL} ${database} -u ${user} -p${password} -e "INSERT INTO payments (userid, name, unpaid, operator1, date_payment1, expires, sum, notes) VALUES ( '${userid}', '${name}', '1', 'system', '${date_payment1}', '${expires}', '${sum}', 'This payment is added automatically by the system for users who are not excluded.');"
        ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE users SET expires='${expires}' WHERE userid='${userid}';"

        export users${userid}="${userid} ${serviceid} ${pay} ${free_access} ${not_excluding} ${expires}"
    fi

    unset found
  fi
done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF


${IPSET} create -exist allowed_temp hash:ip
${IPSET} restore -file /tmp/allowed_temp
${IPSET} swap allowed_temp allowed
${IPSET} destroy allowed_temp

cd ${SQL_BACKUP_DIR}; ${MYSQLDUMP} ${database} -u ${user} -p${password} > $(date +"%Y-%m-%d-%H:%M:%S")_${database}_full-dump.sql
