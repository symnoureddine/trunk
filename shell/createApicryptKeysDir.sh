#!/bin/sh
# version 1.0

###################################################
# Create the directory for stocking apicrypt keys #
###################################################

if ! [ $(id -u) = 0 ]
then
  echo "This script must be run as root"
  exit 1;
fi

darwin_kernel=$(uname -a|cut -d' ' -f1)

# For Mac
if [ $darwin_kernel = "Darwin" ]
then
  APACHE_USER=$(ps -ef|grep httpd|grep -v grep|head -2|tail -1|cut -d' ' -f4)
  APACHE_GROUP=$(groups $APACHE_USER|cut -d' ' -f1)

# For linux
else
  APACHE_USER=$(ps -ef|grep apache|grep -v grep|head -2|tail -1|cut -d' ' -f1)
  APACHE_GROUP=$(groups $APACHE_USER|cut -d' ' -f3)
fi

echo "Creating the folder /var/apicrypt/"
mkdir -p /var/apicrypt/

echo "Changing the owner of the folder"
chown -R $APACHE_USER:$APACHE_GROUP /var/apicrypt

echo "Setting the rigth of the directory to 700"
chmod -R 700 /var/apicrypt

exit 0