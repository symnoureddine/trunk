#!/bin/sh
# Openoffice memory leak...
# If over 10%, restart it.

percent=$(ps -C soffice.bin -o %mem --no-headers|sed -r 's/\s//'|cut -d"." -f1)

force_restart=$1

if [ $percent -ge 10 ] || [ "$force_restart" = "1" ]
then
  pkill soffice;
  export HOME=/tmp; /usr/bin/soffice --accept="socket,host=localhost,port=8100;urp;StarOffice.ServiceManager" --headless >> /dev/null &
fi

echo $percent