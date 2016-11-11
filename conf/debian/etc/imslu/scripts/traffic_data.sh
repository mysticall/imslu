#!/bin/bash

now=$(date +"%Y-%m-%d-%H:%M:%S")
LOG_DIR=/var/log/ulog
LOG_FILE=traffic_data.log
NEW_LOG_FILE=${now}_${LOG_FILE}

cd ${LOG_DIR}
cat ${LOG_FILE} > ${NEW_LOG_FILE}; cat /dev/null > ${LOG_FILE}
gzip -9 ${NEW_LOG_FILE}
