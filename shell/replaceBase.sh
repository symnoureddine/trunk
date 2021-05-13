#!/bin/sh

BASH_PATH=$(dirname $0)
. $BASH_PATH/utils.sh
MB_PATH=$(cd $BASH_PATH/../; pwd);

########
# Replace mediboard database
########

announce_script "Mediboard replace base"

if [ "$#" -lt 5 ]
then 
  echo "Usage: $0 <source_location> <source_directory> <source_database> <target_directory> <target_database> [options below]"
  echo " <source_location>  is the remote location, ie user@host. if localhost, symlink instead of scp"
  echo " <source_directory> is the remote directory, /var/backup"
  echo " <source_database>  is the source database name, ie mediboard"
  echo " <target_directory> is the temporary target directory, /tmp"
  echo " <target_database>  is the target database name, ie target_mediboard"
  echo " [-r ] to restart the Mysql server (Warning), ie for InnoDB"
  echo " [-n ] to not save the target database"
  echo " [-m <mysql_directory>] is the directory where databases are stored, ie /var/lib/mysql"
  echo " [-p <port>] is the ssh port af the target remote location, 22"
  echo " [-l ] to do a local copy (default scp)"
  exit 1
fi

port=22
restart=0
safe=1
args=$(getopt m:p:lrn $*)
mysql_directory=/var/lib/mysql
distant=1

if [ $? != 0 ] ; then
  echo "Invalid argument. Check your command line"; exit 0;
fi

set -- $args
for i; do
  case "$i" in
    -r) restart=1; shift;;
    -l) distant=0; shift;;
    -n) safe=0; shift;;
    -p) port=$2; shift 2;;
    -m) mysql_directory=$2; shift 2;;
    --) shift ; break ;;
  esac
done

source_location=$1
source_directory=$2
source_database=$3
target_directory=$4
target_database=$5

event=$MB_PATH/tmp/monitevent.txt

if [ $restart -eq 1 ]
then
echo "Warning !!!!!!!!!!!! This will restart the MySQL server"
fi

# Mysql Path
path=/etc/init.d/mysql
if [ -f "$path" ]
then 
  mysql_path=/etc/init.d/mysql
else
  mysql_path=/etc/init.d/mysqld
fi

# Retrieve archive 
archive="archive.tar.gz"
if [ $distant -eq 1 ]; then
  scp -P $port $source_location:$source_directory/$source_database-db/$source_database-latest.tar.gz $target_directory/$archive
  check_errs $? "Failed to retrieve remote archive" "Succesfully retrieved remote archive!"
else
  rm $target_directory/$archive
  cp -s $source_directory/$source_database-db/$source_database-latest.tar.gz $target_directory/$archive
  check_errs $? "Failed to symlink local archive" "Succesfully symlinked local archive!"
fi


# Extract base
cd $target_directory
tar -xf $archive
check_errs $? "Failed to extract files" "Succesfully extracted files"

# Stop mysql
if [ $restart -eq 1 ]
then
"$mysql_path" stop
check_errs $? "Failed to stop mysql" "Succesfully stopped mysql"
fi

dir_target=$mysql_directory/$target_database

if [ $safe -eq 1 ]
then
  # Copy database
  DATETIME=$(date +%Y_%m_%dT%H_%M_%S)
  mv $dir_target ${dir_target}_$DATETIME
  check_errs $? "Move mysql target directory" "Succesfully moved mysql target directory"
  mkdir $dir_target
  check_errs $? "Failed to create mysql target directory" "Succesfully created mysql target directory"
  chown mysql $dir_target
  chgrp mysql $dir_target
  check_errs $? "Failed to change owner and group" "Succesfully changed owner and group"
else
  # Delete files in mediboard database
  rm -f $dir_target/*
  check_errs $? "Failed to delete files" "Succesfully deleted files"
fi

# Move table files 
cd $source_database
mv * $dir_target
check_errs $? "Failed to move files" "Succesfully moved files"

# Change owner & group 
cd $dir_target
chown mysql *
chgrp mysql *
check_errs $? "Failed to change owner and group" "Succesfully changed owner and group"

# Start mysql
if [ $restart -eq 1 ]
then
"$mysql_path" start
check_errs $? "Failed to start mysql" "Succesfully started mysql"
fi

# Cleanup temporary archive
rm -rf $target_directory/$source_database
rm $target_directory/$archive
check_errs $? "Failed to delete temporary archive" "Succesfully deleted temporary archive"

# Write event file
echo "#$(date +%Y-%m-%dT%H:%M:%S)" >> $event
echo "replaceBase: <strong>$source_database</strong> to <strong>$target_database</strong>" >> $event