#!/bin/sh

show_usage() {
  echo "$0 -b <slow_log_path> -q <query_digest_path>"
  exit 0
}

date=$(date +'%Y%m%d')
slow_log_file='/var/log/mysql/mysql-slow.log'
query_digest_path='/var/log/mysql/query_digests'

args=$(getopt b:q: $*)
if [ $? != 0 ] ; then
  echo "Invalid argument. Check your command line"; exit 0;
fi

set -- $args
for i; do
  case "$i" in
    -b) slow_log_file=$2; shift 2;;
    -q) query_digest_path=$2; shift 2;;
    --) shift; break ;;
  esac
done

if [ ! -d $query_digest_path ]; then
  mkdir -p $query_digest_path
fi

pt-query-digest --filter 'return $event->{Query_time} >= 0.01' $slow_log_file >> $query_digest_path/mysql-slow.log-$date.digest-10ms
pt-query-digest --filter 'return $event->{Query_time} >= 0.1' $slow_log_file >> $query_digest_path/mysql-slow.log-$date.digest-100ms
pt-query-digest --filter 'return $event->{Query_time} >= 1.0' $slow_log_file >> $query_digest_path/mysql-slow.log-$date.digest-1000ms
pt-query-digest --filter 'return $event->{Query_time} >= 10'  $slow_log_file >> $query_digest_path/mysql-slow.log-$date.digest-10000ms
pt-query-digest --filter 'return $event->{Query_time} >= 100'  $slow_log_file >> $query_digest_path/mysql-slow.log-$date.digest-100000ms