#!/bin/sh

BASH_PATH=$(dirname $0)
. $BASH_PATH/utils.sh

########
# Mediboard Packager of external libs
########

announce_script "Mediboard Packager of external libs"

if [ "$#" -lt 1 ]
then 
  echo "Usage: $0 -l <lib_name>"
  echo "  -l <lib_name> : The library you want to package"
  exit 1
fi

args=$(getopt l: $*)

if [ $? != 0 ] ; then
  echo "Invalid argument. Check your command line"; exit 0;
fi

set -- $args
for i; do
  case "$i" in
    -l) lib_name=$2; shift 2;;
    --) shift ; break ;;
  esac
done

case $lib_name in
 dompdf)
   version=$(date +%m-%d-%y)
   package_lib dompdf https://dompdf.googlecode.com/svn/trunk/dompdf $version
   rm -rf ./tmp/dompdf
   break;;
 *)
   echo "Cette librairie ($lib_name) n'est pas disponible";;
esac
