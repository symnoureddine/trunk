#!/bin/sh

BASH_PATH=$(dirname $0)
. $BASH_PATH/utils.sh

########
# Uptime logger for server load analysis
########

announce_script "Uptime logger"

if [ "$#" -lt 1 ]
then 
  echo "Usage: $0 <file>"
  echo "  <file> is the target for log, ie /var/log/uptime.log"
  exit 1
fi
   
file=$1

## Make the log line
dt=$(date '+%Y-%m-%dT%H:%M:%S'); 
up=$(uptime | sed 's/\(.*\): \([0-9.]*\)[,]* \([0-9.]*\)[,]* \([0-9.]*\)/1mn:\2\t5mn:\3\t15mn:\4/'); 

## Log the line
echo "$dt $up" >> $file
check_errs $? "Failed to log uptime" "Uptime logged!"