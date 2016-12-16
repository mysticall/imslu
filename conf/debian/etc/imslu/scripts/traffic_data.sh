#!/bin/bash

now=$(date +"%Y-%m-%d-%H:%M:%S")
LOG_DIR=/var/log/ulog
LOG_FILE=traffic_data.log
NEW_LOG_FILE=${now}_${LOG_FILE}

cd ${LOG_DIR}
mv ${LOG_FILE} ${NEW_LOG_FILE}; /etc/init.d/ulogd2 restart
gzip -9 ${NEW_LOG_FILE}
