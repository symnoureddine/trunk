#!/bin/sh

BASH_PATH=$(dirname $0)
. $BASH_PATH/utils.sh
MB_PATH=$(cd $BASH_PATH/../; pwd);
export LANG=fr_FR.utf-8

########
# Mediboard SVN updater and rsyncer
########

if [ "$#" -lt 1 ]
then 
  echo "Usage: $0 <action> [-r <revision> -c </path/to/another/config> -d]"
  echo "  <action> The action to perform : info|real|noup"
  echo "     info: Shows the update log, no rsync"
  echo "     real: Performs the actual update and the rsync"
  echo "     noup: No update, only rsync"
  echo "  -r <revision> The revision number you want to update to, HEAD by default"
  echo "  -c </path/to/another/config> Another config file to parse"
  echo "  -u Force the update without asking"
  echo "  -o Force the clear cache without asking"
  echo "  -n Force the non clearing cache without asking"
  echo "  -d Dry run : simulation of the rsync"
  exit 1
fi

conf_file=$BASH_PATH/rsyncupdate.conf
dry_run=""
revision=""
force_update=""
force_clear=""
force_non_clear=""

args=$(getopt r:c:duon $*)
if [ $? != 0 ] ; then
  echo "Invalid argument. Check your command line"; exit 0;
fi

set -- $args
for i; do
  case "$i" in
    -r) revision="-r $2"; shift 2;;
    -c) conf_file=$2; shift 2;;
    -d) dry_run="-n"; shift;;
    -u) force_update="1"; shift;;
    -o) force_clear="1"; shift;;
    -n) force_non_clear="1"; shift;;
    --) shift; break ;;
  esac
done

action=$1

# Update
if [ "$action" != "noup" ]
then
  echo "sh $BASH_PATH/update.sh $action $revision"
  sh $BASH_PATH/update.sh $action $revision
  check_errs $? "Wrong parameters" "Successfully updated"
fi

#deprecated use cli deploy
#info_script "Install libs"
#php $MB_PATH/install/cli/install_libs.php

# File must exists (touch doesn't override)
touch $BASH_PATH/rsyncupdate.exclude

# Rsyncing -- Parsing rsyncupdate.conf
if [ "$action" != "info" ]
then
  while read line
  do
    first_character=$(expr substr "$line" 1 1)
    # Skip comment lines and empty lines
    if [ "$first_character" != "#" ] && [ "$first_character" != "" ]
    then
      REPLY="n"
      if [ "$force_update" = "" ] ; then
        echo "Do you want to update $line (y or n) [default n] ? \c" ; read REPLY < /dev/tty
      fi
      if [ "$force_update" = "1" ] || [ "$REPLY" = "y" ] ; then
        echo "-- Rsync $line --"
        touch $MB_PATH/tmp/clear_cache.flag
        eval rsync -avpgzC $dry_run --stats $MB_PATH/ --delete $line --exclude-from=$BASH_PATH/rsyncupdate.exclude --include=*.exe\
          --exclude includes/config_overload.php \
          --exclude /tmp \
          --exclude /files \
          --exclude includes/config.php \
          --exclude images/pictures/logo_custom.png
        check_errs $? "Failed to rsync $line" "Succesfully rsync-ed $line"
        eval rsync -avzp $dry_run $MB_PATH/tmp/svnlog.txt $line/tmp/
        eval rsync -avzp $dry_run $MB_PATH/tmp/svnstatus.txt $line/tmp/
        eval rsync -avzp $dry_run $MB_PATH/tmp/monitevent.txt $line/tmp/
        eval rsync -avzp $dry_run $MB_PATH/tmp/clear_cache.flag $line/tmp/
      fi

      # Call clear apc cache
      REPLY="n"
      if [ "$force_clear" = "" ] && [ "$force_non_clear" = "" ] ; then
        echo "Do you want to clear cache for $line (y or n) [default n] ? \c" ; read REPLY < /dev/tty
      fi
      if [ "$force_clear" = "1" ] || [ "$REPLY" = "y" ] ; then
        path=$(echo $line|grep -P "(/var/www/html|/var/www|/srv/www/htdocs).*" -o)
        path=${path#/var/www/html/}
        path=${path#/var/www/}
        path=${path#/srv/www/htdocs/}
        ip="localhost"

        if [ "$first_character" != "/" ]
        then
          ip=$(echo $line|grep -P "([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+|@[a-zA-Z\-0-9]+:)" -o)
        fi
        first_character_ip=$(expr substr "$ip" 1 1)
        if [ "$first_character_ip" = "@" ]
        then
          ip=$(echo $ip|sed 's/.\{1\}//'|sed 's/.$//')
        fi

        url="http://$ip/$path/modules/system/public/clear_cache.php"
        info_script "-- Clearing apc cache // $url --"
        wget $url -q -O -
      fi
    fi
  done < $conf_file

fi
exit 1
