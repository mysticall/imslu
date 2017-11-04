#!/bin/sh
export PATH=/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/sbin:/usr/local/bin

. /usr/local/etc/imslu/config.sh

now=$(date -j +%Y%m%d%H%M%S)
kind_traffic=$(echo "SELECT COUNT(id) FROM kind_traffic" | ${MYSQL} ${database} -u ${user} -p${password} -s)


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

rm -f /tmp/allowed_temp
echo "-q table 1 flush
-q table 2 flush
-q table 3 flush
-q table 4 flush
-q table 5 flush
-q table 6 flush
-q table 7 flush
-q table 8 flush
-q table 9 flush
-q table 10 flush" > /tmp/allowed_temp

query="SELECT userid, ip, stopped FROM ip WHERE userid != 0"

while read -r userid ip stopped; do

  found=$(eval echo \$users${userid})
  if [ -n "${found}" ]; then

    read -r serviceid pay free_access not_excluding expires expires2 name <<EOF
$(echo ${found})
EOF

    # Prevent error: Failed conversion of ``0000-00-00 00:00:00'' using format ``%Y-%m-%d %H:%M:%S''
    if [ "${expires}" != "0000-00-00" ]; then
      d=$(date -j -f "%Y-%m-%d %H:%M:%S" "${expires} ${expires2}" +"%Y%m%d%H%M%S")
    else
      d=0
    fi

    if [ "${stopped}" == "n" ] && ([ "${free_access}" == "y" ] || [ ${d} -gt ${now} ]); then

      echo "table 1 add ${ip}/32 ${serviceid}1" >> /tmp/allowed_temp
      echo "table 2 add ${ip}/32 ${serviceid}11" >> /tmp/allowed_temp

      if [ ${kind_traffic} -ge 2 ]; then
        echo "table 3 add ${ip}/32 ${serviceid}2" >> /tmp/allowed_temp
        echo "table 4 add ${ip}/32 ${serviceid}22" >> /tmp/allowed_temp
      fi
      if [ ${kind_traffic} -ge 3 ]; then
        echo "table 5 add ${ip}/32 ${serviceid}3" >> /tmp/allowed_temp
        echo "table 6 add ${ip}/32 ${serviceid}33" >> /tmp/allowed_temp
      fi
      if [ ${kind_traffic} -ge 4 ]; then
        echo "table 7 add ${ip}/32 ${serviceid}4" >> /tmp/allowed_temp
        echo "table 8 add ${ip}/32 ${serviceid}44" >> /tmp/allowed_temp
      fi
      if [ ${kind_traffic} -ge 5 ]; then
        echo "table 9 add ${ip}/32 ${serviceid}5" >> /tmp/allowed_temp
        echo "table 10 add ${ip}/32 ${serviceid}55" >> /tmp/allowed_temp
      fi

    elif [ "${stopped}" == "n" ] && [ "${not_excluding}" == "y" ] && [ ${d} -lt ${now} ]; then

      echo "table 1 add ${ip}/32 ${serviceid}1" >> /tmp/allowed_temp
      echo "table 2 add ${ip}/32 ${serviceid}11" >> /tmp/allowed_temp

      if [ ${kind_traffic} -ge 2 ]; then
        echo "table 3 add ${ip}/32 ${serviceid}2" >> /tmp/allowed_temp
        echo "table 4 add ${ip}/32 ${serviceid}22" >> /tmp/allowed_temp
      fi
      if [ ${kind_traffic} -ge 3 ]; then
        echo "table 5 add ${ip}/32 ${serviceid}3" >> /tmp/allowed_temp
        echo "table 6 add ${ip}/32 ${serviceid}33" >> /tmp/allowed_temp
      fi
      if [ ${kind_traffic} -ge 4 ]; then
        echo "table 7 add ${ip}/32 ${serviceid}4" >> /tmp/allowed_temp
        echo "table 8 add ${ip}/32 ${serviceid}44" >> /tmp/allowed_temp
      fi
      if [ ${kind_traffic} -ge 5 ]; then
        echo "table 9 add ${ip}/32 ${serviceid}5" >> /tmp/allowed_temp
        echo "table 10 add ${ip}/32 ${serviceid}55" >> /tmp/allowed_temp
      fi

      if [ "${expires}" != "0000-00-00" ]; then
        # payments.sh start next day at 00:01h and gives one day bonus to not_excluding users
        next_month=$(expr $(date -j -v +${FEE_PERIOD} +"%Y%m%d") - 1)
        expires=$(date -j -f "%Y%m%d" "${next_month}" +"%Y-%m-%d 23:59:00")
      else
        expires="$(date -j -v +${FEE_PERIOD} +"%Y-%m-%d 23:59:00")"
      fi

      date_payment1=$(date -j +"%Y-%m-%d %H:%M:%S")

      if [ ${pay} != "0.00" ]; then
        sum=${pay}
      else
        sum=$(eval echo \$services${serviceid})
      fi

      ${MYSQL} ${database} -u ${user} -p${password} -e "INSERT INTO payments (userid, name, unpaid, operator1, date_payment1, expires, sum, notes) VALUES ( '${userid}', '${name}', '1', 'system', '${date_payment1}', '${expires}', '${sum}', 'This payment is added automatically by the system for users who are not excluded.');"
      ${MYSQL} ${database} -u ${user} -p${password} -e "UPDATE users SET expires='${expires}' WHERE userid='${userid}';"

      export users${userid}="${serviceid} ${pay} ${free_access} ${not_excluding} ${expires} ${expires2} ${name}"
    fi

    unset found
  fi
done <<EOF
$(echo ${query} | ${MYSQL} ${database} -u ${user} -p${password} -s)
EOF


${IPFW} -q /tmp/allowed_temp

cd ${SQL_BACKUP_DIR}; ${MYSQLDUMP} ${database} -u ${user} -p${password} > $(date -j +"%Y-%m-%d-%H:%M:%S")_${database}_full-dump.sql
