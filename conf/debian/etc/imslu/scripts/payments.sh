#!/bin/bash

. /etc/imslu/config.sh
now=$(date +%Y%m%d%H%M%S)

####### Services #######
query="SELECT name, price FROM services GROUP BY name"
declare -A services

while read -r name price; do
    services[${name}]=$price

done < <(echo $query | mysql $database -u $user -p${password} -s)
# echo ${services[@]}
# echo ${!services[@]}


####### payments #######
query="SELECT users.userid, users.service, users.pay, users.free_access, users.not_excluding, TEMP.expires 
       FROM users
       LEFT JOIN (SELECT payments.userid, payments.expires FROM payments ORDER BY payments.expires DESC LIMIT 18446744073709551615) AS TEMP
       ON users.userid=TEMP.userid GROUP BY users.userid"
declare -A payments

while read -r row; do
    read -r userid tmp <<< "${row}"
    payments[${userid}]="${row}"

done < <(echo $query | mysql $database -u $user -p${password} -s)
# echo ${!payments[@]}
# echo ${payments[@]}

$IPSET create -exist allowed_temp hash:ip

query="SELECT userid, ip FROM ip WHERE userid != 0"
while read -r userid ip; do

    # Allow internet access
    read -r userid service pay free_access not_excluding expires expires2 <<< "${payments[${userid}]}"

    if [[ ${free_access} == "y" || (${expires} != "NULL" && $(date -d "${expires} ${expires2}" +"%Y%m%d%H%M%S") -gt ${now}) ]]; then
        $IPSET add allowed_temp ${ip}
    elif [ ${not_excluding} == "y" ]; then
        $IPSET add allowed_temp ${ip}

        if [ ${expires} != "NULL" ]; then
            expires="$(date +%Y-%m-%d -d "${expires} + 1 month") 23:59:00"
        else
            expires="$(date +%Y-%m-%d -d "+ 1 month") 23:59:00"
        fi
        date_payment1=$(date +"%Y-%m-%d %H:%M:%S")

        if [ ${pay} != "0.00" ]; then
            sum=${pay}
        else
            sum=${services[${service}]}
        fi

        mysql $database -u $user -p${password} -e "INSERT INTO payments (userid, unpaid, operator1, date_payment1, expires, sum, notes) VALUES ( '${userid}', '1', 'system', '${date_payment1}', '${expires}', '${sum}', 'This payment is added automatically by the system for users who are not excluded.');"

        payments[${userid}]="${userid} ${service} ${pay} ${free_access} ${not_excluding} ${expires}"
    fi

done < <(echo $query | mysql $database -u $user -p${password} -s)

$IPSET swap allowed_temp allowed
$IPSET destroy allowed_temp

cd $SQL_BACKUP_DIR; $MYSQLDUMP $database -u $user -p${password} > $(date +"%Y-%m-%d-%H:%M:%S")_${database}_full-dump.sql
