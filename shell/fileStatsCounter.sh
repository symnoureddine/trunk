#!/bin/sh

BASH_PATH=$(dirname $0)
. $BASH_PATH/utils.sh
MB_PATH=$(cd $BASH_PATH/../; pwd);
tmp_file_name='/tmp/file_stats'

show_usage() {
  echo "Usage: $0 <thumbs_path> <editor_path> <csv_file_path> [-i <instance_name>] [-a] [-d]"
  echo "    <thumbs_path> Path to the thumbs dir"
  echo "    <editor_path> Path to the editor dir"
  echo "    <csv_file_path> Path to csv file"
  echo "    [-i <instance_name>] is the name of the instance (default is Mediboard)"
  echo "    [-a] Append the content to the given csv file"
  echo "    [-d] Dumps the content to the standard output"
}

get_files() {
  dir=$1
  find ${dir} -printf '%s\n' > $tmp_file_name
}

get_file_count() {
  ret_count=$(wc -l ${tmp_file_name}|cut -d' ' -f1)
  echo $ret_count
}

get_logical_file_size() {
  ret_size=$(paste -s -d+ ${tmp_file_name}|bc)
  echo $ret_size
}

get_physical_file_size() {
  dir=$1
  ret_physical_file_size=$(du -s --block-size=1 ${dir}|cut -f1)
  echo $ret_physical_file_size
}

generate_csv_file() {
  thumbs_files_count=$1
  thumbs_logical_size=$2
  thumbs_physical_size=$3
  editor_files_count=$4
  editor_logical_size=$5
  editor_physical_size=$6
  csv_path=$7
  instance_name=$8
  append=$9
  dump=${10}
  main_header=",Thumbs,Thumbs,Thumbs,Editor,Editor,Editor"
  header="Instance,count,logical size,cluster size,count,logical size,cluster size"
  data="$instance_name,$thumbs_files_count,$thumbs_logical_size,$thumbs_physical_size,$editor_files_count,$editor_logical_size,$editor_physical_size"


  if [ "$append" = 0 ]; then
    if [ "$dump" = 1 ]; then
      echo $main_header
      echo $header
    else
      echo $main_header > $csv_path
      echo $header >> $csv_path
    fi
  fi

  if [ "$dump" = 1 ]; then
    echo $data
  else
    echo $data >> $csv_path
  fi
}
#announce_script "File Stat Counter"

if [ "$#" -lt 3 ]
then
  echo "Invalid number of arguments"
  show_usage
  exit 1
fi

thumbs_path=$1
editor_path=$2
csv_path=$3
instance_name="Mediboard"
append=0
dump=0

args=$(getopt i:ad $*)

set -- $args
for i; do
  case "$i" in
    -i) instance_name=$2; shift 2;;
    -a) append=1; shift ;;
    -d) dump=1; shift ;;
    --) shift; break ;;
  esac
done

touch $tmp_file_name

# Thumbs
get_files $thumbs_path
thumbs_files_count=$(get_file_count)
thumbs_logical_file_size=$(get_logical_file_size)
thumbs_physical_file_size=$(get_physical_file_size $thumbs_path)

# Editor
get_files $editor_path
editor_files_count=$(get_file_count)
editor_logical_file_size=$(get_logical_file_size)
editor_physical_file_size=$(get_physical_file_size $editor_path)

# Putting data together
generate_csv_file $thumbs_files_count $thumbs_logical_file_size $thumbs_physical_file_size $editor_files_count $editor_logical_file_size $editor_physical_file_size $csv_path $instance_name $append $dump


rm $tmp_file_name