#!/bin/bash

. /etc/imslu/config.sh
#declare variables
now=$(date +%Y%m%d%H%M%S)
declare -a services payments


####### Services #######
query="SELECT serviceid, price FROM services"
while read -r serviceid price; do
    services[${serviceid}]=$price

done < <(echo $query | mysql $database -u $user -p${password} -s)
#echo ${services[@]}
#echo ${!services[@]}


####### payments #######
query="SELECT userid, serviceid, pay, free_access, not_excluding, expires, name FROM users"
while read -r userid tmp; do
    payments[${userid}]="${tmp}"

done < <(echo $query | mysql $database -u $user -p${password} -s)
#echo ${payments[@]}
#echo ${!payments[@]}

i=0
query="SELECT userid, ip FROM ip WHERE userid != 0"
while read -r row; do
  ipaddresses[${i}]=${row}
  ((i++))

done < <(echo $query | mysql $database -u $user -p${password} -s)
#echo ${ipaddresses[@]}
#echo ${!ipaddresses[@]}

if [ -f /tmp/allowed_temp ]; then
  rm /tmp/allowed_temp
fi

for row in "${ipaddresses[@]}"; do

    read -r userid ip <<< "${row}"
    # Allow internet access
    read -r serviceid pay free_access not_excluding expires expires2 name <<< "${payments[${userid}]}"

    if [[ ${free_access} == "y" || $(date -d "${expires} ${expires2}" +"%Y%m%d%H%M%S") -gt ${now} ]]; then
        echo "add allowed_temp ${ip}" >> /tmp/allowed_temp
    elif [ ${not_excluding} == "y" ]; then
        echo "add allowed_temp ${ip}" >> /tmp/allowed_temp

        if [ ${expires} != "0000-00-00" ]; then
            expires="$(date +%Y-%m-%d -d "${expires} + 1 month") 23:59:00"
        else
            expires="$(date +%Y-%m-%d -d "+ 1 month") 23:59:00"
        fi
        date_payment1=$(date +"%Y-%m-%d %H:%M:%S")

        if [ ${pay} != "0.00" ]; then
            sum=${pay}
        else
            sum=${services[${serviceid}]}
        fi

        mysql $database -u $user -p${password} -e "INSERT INTO payments (userid, name, unpaid, operator1, date_payment1, expires, sum, notes) VALUES ( '${userid}', '${name}', '1', 'system', '${date_payment1}', '${expires}', '${sum}', 'This payment is added automatically by the system for users who are not excluded.');"
        mysql $database -u $user -p${password} -e "UPDATE users SET expires='${expires}' WHERE userid='${userid}';"

        payments[${userid}]="${userid} ${serviceid} ${pay} ${free_access} ${not_excluding} ${expires}"
    fi
done


$IPSET create -exist allowed_temp hash:ip
$IPSET restore -file /tmp/allowed_temp
$IPSET swap allowed_temp allowed
$IPSET destroy allowed_temp

cd $SQL_BACKUP_DIR; $MYSQLDUMP $database -u $user -p${password} > $(date +"%Y-%m-%d-%H:%M:%S")_${database}_full-dump.sql
