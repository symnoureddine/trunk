#!/bin/sh

BASH_PATH=$(dirname $0)
. $BASH_PATH/utils.sh

########
# Kill service depending on the load average
########

announce_script "Service suicide"

if [ "$#" -lt 3 ]
then
  echo "Usage: $0 <min> <max> <service> options"
  echo " <min> is minimal load average for restarting service"
  echo " <max> is the max authorised load average before killing service"
  echo " <service> is the service to manage, ie httpd"
  echo " Options:"
  echo "   [-d] dry run"
  exit 1
fi

DRY_RUN=0

args=$(getopt d $*)

if [ $? != 0 ] ; then
  echo "Invalid argument. Check your command line"; exit 0;
fi

set -- $args

for i; do
  case "$i" in
    -d) DRY_RUN=1; shift;;
    --) shift ; break ;;
  esac
done

MIN=$1
MAX=$2
SERVICE=$3

FILE=/tmp/apache-suicide.lock
test -f $FILE
NO_FILE=$?

P=$(pgrep $SERVICE)
test -z "$P"
RUNNING=$?

FTEXT='load average:'
# Get the load average for the last 1 minute
LOAD1MIN=$(uptime | awk -F "$FTEXT" '{ print $2 }' | cut -d. -f1 | sed 's/ //g')

if [ $LOAD1MIN -gt $MAX ] && [ $NO_FILE -eq 1 ] && [ $RUNNING -eq 1 ]
then
  echo "Stopping service, MAX load average: $MAX, CURRENT load average: $LOAD1MIN"

  if [ $DRY_RUN -eq 0 ]
  then
    service $SERVICE stop
  fi

  touch $FILE
  exit 0;
fi

if [ $LOAD1MIN -lt $MIN ] && [ $NO_FILE -eq 0 ] && [ $RUNNING -eq 0 ]
then
  echo "Starting service, MIN load average: $MIN, CURRENT load average: $LOAD1MIN"

  if [ $DRY_RUN -eq 0 ]
  then
    service $SERVICE start
  fi

  rm $FILE
  exit 0;
fi