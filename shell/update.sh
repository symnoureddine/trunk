#!/bin/sh

BASH_PATH=$(dirname $0)
. $BASH_PATH/utils.sh
MB_PATH=$(cd $BASH_PATH/../; pwd);

export LANG=fr_FR.utf-8

########
# Mediboard SVN updater and user-oriented logger
########

announce_script "Mediboard SVN updater"

if [ "$#" -lt 1 ]
then 
  echo "Usage: $0 <action> [-r <revision> -e <revision externals>]"
  echo "  <action> The action to perform : info|real"
  echo "     info : Shows the update log"
  echo "     real : Performs the actual update"
  echo "  -r <revision> The revision number you want to update to, HEAD by default"
  echo "  -e <revision external> The external revision number, same behavior than the above option"
  echo "  -i : ignore externals"
  exit 1
fi
   
log=$MB_PATH/tmp/svnlog.txt
tmp=$MB_PATH/tmp/svnlog.tmp
dif=$MB_PATH/tmp/svnlog.dif
status=$MB_PATH/tmp/svnstatus.txt
event=$MB_PATH/tmp/monitevent.txt
prefixes="erg|fnc|fct|bug|war|edi|sys|svn"
revision=HEAD
revision_external=HEAD
with_externals=1

# First external
externals=$(svn propget svn:externals modules)
first_external=$(echo $externals|head -n 1|cut -d' ' -f 1)
all_externals=$(echo $externals|tr ' ' '\n'|sed 'n;d'|awk -F ' ' '{ if ($1 != "") { print "modules/"$1 } }'|tr '\n' ' ')
path_external=$MB_PATH/modules/$first_external

# Choose the target revision

args=$(getopt r:e:i $*)

if [ $? != 0 ] ; then
  echo "Invalid argument. Check your command line"; exit 0;
fi

set -- $args

for i; do
  case "$i" in
    -r) revision=$2; shift 2;;
    -e) revision_external=$2; shift 2;;
    -i) with_externals=0; shift;;
    --) shift ; break ;;
    -*) echo "$0: error - unrecognized option $1" 1>&2; exit 1;;
  esac
done

case "$1" in
  info)
    svn info $MB_PATH | awk 'NR==5'
    svn log  $MB_PATH -r BASE:$revision | grep -i -E "(${prefixes})"
    svn info $MB_PATH -r $revision | awk 'NR==5'
    ;;
    
  real)
    # Concat the source (BASE) revision number : 5th line of SVN info (!)
    svn info $MB_PATH | awk 'NR==5' > $tmp
    check_errs $? "Failed to get source revision info" "SVN Revision source info written!"
    echo >> $tmp

    # Concat SVN Log from BASE to target revision
    # Add the external
    if [ "$first_external" != "" ]
    then
      svn info $path_external | awk 'NR==5' >> $tmp
    fi
    
    svn log $MB_PATH -r BASE:$revision > $dif
    check_errs $? "Failed to retrieve SVN log" "SVN log retrieved!"
    
    grep -i -E "(${prefixes})" $dif >> $tmp
    echo "SVN log parsed!"
    # Don't check because grep returns 1 if no occurence found
    rm -f $dif
    
    # Perform actual update
    if [ "$with_externals" -eq "1" ]
    then
      # If externals, update only the trunk without them and then update the externals
      if [ "$revision_external" != "HEAD" ] && [ "$first_external" != "" ]
      then
        svn update --ignore-externals --revision $revision
        svn update $all_externals --revision $revision_external
      else
        svn update $MB_PATH --revision $revision
      fi
    else
      svn update $MB_PATH --ignore-externals --revision $revision
    fi
    check_errs $? "Failed to perform SVN update" "SVN updated performed!"

    # Concat the target revision number
    echo >> $tmp
    # Add the external revision
    if [ "$first_external" != "" ]
    then
      svn info $path_external | awk 'NR==5' >> $tmp
      check_errs $? "Failed to get target external revision info" "SVN External Revision target info written!"
    fi
    svn info $MB_PATH | awk 'NR==5' >> $tmp
    check_errs $? "Failed to get target revision info" "SVN Revision target info written!"

    # Concat dating info
    echo "--- Updated Mediboard on $(date) ---" >> $tmp
    echo >> $tmp

    ## Concat tmp file to log file 

    # Ensure log file exists
    touch $log;
    
    # Log file is reversed, make it straight
    tac $log > $log.straight

    # Concat tmp file
    cat $tmp >> $log.straight
    
    # Reverse the log file for user convenience
    tac $log.straight > $log

    # Clean files
    rm -f $log.straight
    rm -f $tmp

    # Write status file
    svn info $MB_PATH | awk 'NR==5' > $status
    echo "Date: $(date +%Y-%m-%dT%H:%M:%S)" >> $status
    check_errs $? "Failed to write status file" "Status file written!"
    
    # Write event file
    echo "#$(date +%Y-%m-%dT%H:%M:%S)" >> $event
    echo "Mise a jour." $(svn info $MB_PATH | awk 'NR==5') >> $event
    ;;

  *)
    check_errs 2 "Action '$1' unknown"
    exit 1
    ;;
esac
