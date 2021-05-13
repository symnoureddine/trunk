<?php

global $argv;

$hostname = "";
$username = "";
$password = "";
$file     = "";
$port     = "21";
$source_mode = "active";
$transport_mode = FTP_BINARY;
$help = false;
$i = 0;
$command = array_shift($argv);

foreach ($argv as $key=>$arg) {
  switch ($arg) {
    case "-p":
      $port = $argv[$key+1];
      unset($argv[$key+1]);
      break;
    case "-m":
      $source_mode = "passive";
      break;
    case "-t":
      $transport_mode = FTP_ASCII;
      break;
    case "-h":
      $help = true;
      break;
    default:
      switch ($i) {
        case 0:
          $hostname = $arg;
          break;
        case 1:
          $username = $arg;
          break;
        case 2:
          $password = $arg;
          break;
        case 3:
          $file = $arg;
      }
      $i++;
  }  
}

echo chr(27)."[1m--- Send file by ftp (".date("l d F H:i:s").") ---".chr(27)."[0m"."\n";

if ($help) {
  echo "Usage : $command <hostname> <username> <password> <file> options\n
    <hostname> : host to connect\n
    <username> : username requesting\n
    <password> : password of the user\n
    <file>     : file to send\n
    Options : \n
      [ -p <port> ] : port to connect, default 21\n
      [ -m ] : switch to passive mode, default active\n
      [ -t ] : switch to ascii mode, default binary\n";
  return;
}

try {
  $connexion = @ftp_connect($hostname, $port, 5);
  
  if (!$connexion) {
    throw new Exception("Connection failed : $hostname");
  }
  
  if (!ftp_login($connexion, $username, $password)) {
    throw new Exception("Identification failed for user $username");
  }
  
  if ($source_mode == "passive" && !@ftp_pasv($connexion, true)) {
    throw new Exception("Failed to switch passive mode");
  }
  
  ftp_set_option($connexion, FTP_TIMEOUT_SEC, 5000);
  
  if (ftp_put($connexion, basename($file), $file, $transport_mode)) {
    echo "File $file successfully submitted";
  }
  
  ftp_close($connexion);
} catch(Exception $e) {
  echo $e->getMessage();
}