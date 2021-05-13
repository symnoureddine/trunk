#!/bin/sh

BASH_PATH=$(dirname $0)
. $BASH_PATH/utils.sh
MB_PATH=$(cd $BASH_PATH/../; pwd);

########
# Backups AMMAX database on a daily basis
########

announce_script "AMMAX daily backup"

if [ "$#" -lt 2 ]
then
  sh $BASH_PATH/baseBackup.sh dump ammaxuser userammax AMMAX /var/backup
else
  user=$1
  pass=$2
  sh $BASH_PATH/baseBackup.sh dump $user $pass AMMAX /var/backup
fi
