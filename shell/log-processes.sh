#!/bin/sh

########
# Log processes depending on the load average
########

if [ "$#" -lt 2 ]
then
  echo "Usage: $0 <load> <n>"
  echo " <load> is the minimal load average for logging processes"
  echo " <n> is the number of processes to log"
  exit 1
fi

LOAD=$1.0
N=$(($2 + 1))

# Get the load average for the last 1 minute
FTEXT='load average:'
LOAD1MIN=$(uptime | awk -F "$FTEXT" '{ print $2 }' | cut -d, -f1 | sed 's/ //g')

COMP=`echo "$LOAD1MIN >= $LOAD" | bc`

if [ $COMP -eq 1 ]
then
  echo $(date +"[%Y-%m-%d %H:%M:%S] -") "Logging processes, Load average threshold: $LOAD, current: $LOAD1MIN"

  # Get the n processes sorted by CPU consumption
  PROCESSES=$(ps -e -o pcpu,cpu,nice,state,cputime,args --sort=-pcpu | sed "/^ 0.0 /d" | head -n $N)
  echo "$PROCESSES"

  exit 0;
fi