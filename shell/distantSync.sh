#!/bin/sh

BASH_PATH=$(dirname $0)
. $BASH_PATH/utils.sh

########
# System and backups distant synchronisation
########

announce_script "Mediboard synchronisation"

if [ "$#" -lt 3 ]
then 
  echo "Usage: $0 <location> <source directory> <destination> options"
  echo " <source location>   is the remote location to be rsync-ed, ie root@oxmytto.homelinux.com"
  echo " <source directory>  is the remote directory to be rsync-ed, /home/root/"
  echo " <destination>       is the target remote location, /var/backup/"
  echo " Options:"
  echo "   [-p <port>]       is the ssh port af the target remote location, 22"
  echo "   [-c <passphrase>] is the passphrase to encrypt the archive"
  echo "   [-e <cryptage>]   is the cryptage method to use"
  exit 1
fi
   
port=22
passphrase=''
cryptage='aes-128-cbc'

args=$(getopt p:c:e: $*)
set -- $args
for i; do
  case "$i" in
    -p) port=$2; shift 2;;
    -c) passphrase=$2; shift 2;;
    -e) cryptage=$2; shift 2;;
    --) shift ; break ;;
  esac
done

location=$1
directory=$2
destination=$3
dir_dest=$(echo $location | cut -d'@' -f2)

# Backups directory
rsync -e "ssh -p $port" -avzP --copy-unsafe-links $location:$directory $destination/$dir_dest --exclude-from=$BASH_PATH/distantSync.exclude
check_errs $? "Failed to rsync Backups directory" "Succesfully rsync-ed Backups directory!"

if [ -n "$passphrase" ]; then
  tar -zcf - $destination/$dir_dest | openssl $cryptage -salt -out $destination/$dir_dest.tar.gz.aes -k $passphrase
  check_errs $? "Failed to crypt the folder" "Folder crypted!"

  rm -rf $destination/$dir_dest
  check_errs $? "Failed to delete folder non crypted" "Succesfully delete folder non crypted!"
fi
