<?php 
$currentDir = dirname($_SERVER["PHP_SELF"]);

echo exec("clear")."\n";
menu();

function menu() {
  echo chr(27)."[1m--- Main menu (".date("l d F H:i:s").") ---".chr(27)."[0m"."\n";
  echo "\nSelect a task:\n\n";
  
  echo "[1] Update Mediboard SVN and user-oriented logger\n";
  echo "[2] Configure groups and mods for Mediboard directories\n";
  echo "[3] Update and rsync Mediboard SVN\n";
  echo "[4] Launch Mediboard request\n";
  echo "[5] Replace Mediboard database\n";
  echo "[6] Backup database on a daily basis\n";
  echo "[7] Send a file by FTP\n";
  echo "[8] Log Ping for server load analysis\n";
  echo "[9] Log Uptime for server load analysis\n";
  echo "[10] Run MySQL performance tuning primer script\n";
  echo "[11] Rotate binlogs\n";
  echo "-------------------------------------------------------\n";
  echo "[0] Quit\n";
  
  // Waiting for input
  echo "\nSelected task: ";
  
  // Getting interactive input
  $task = trim(fgets(STDIN));
  
  // According to the task...
  switch ($task) {
  
    // Update Mediboard SVN
    case "1":
      echo exec("clear")."\n";
      task1();
      break;
      
    // Configure groups and mods
    case "2":
      echo exec("clear")."\n";
      task2();
      break;
      
    // Update and rsync Mediboard SVN
    case "3":
      echo exec("clear")."\n";
      task3();
      break;
      
    // Launch Mediboard request
    case "4":
      echo exec("clear")."\n";
      task4();
      break;
      
    // Replace Mediboard database
    case "5":
      echo exec("clear")."\n";
      task5();
      break;
    // Database backup
    case "6":
      echo exec("clear")."\n";
      task6();
      break;
      
    // Send a file by FTP
    case "7":
      echo exec("clear")."\n";
      task7();
      break;
      
    // Log Ping for server load analysis
    case "8":
      echo exec("clear")."\n";
      task8();
      break;
      
    // Log Uptime for server load analysis
    case "9":
      echo exec("clear")."\n";
      task9();
      break;
      
    // Run MySQL performance tuning primer script
    case "10":
      echo exec("clear")."\n";
      task10();
      break;
      
    case "11":
      echo exec("clear")."\n";
      task11();
      break;
      
    // Exit program
    case "0":
      exit();
      break;
      
    // No action
    default:
      echo exec("clear")."\n";
      echo "Incorrect input\n";
      menu();
  }
}

function task1() {

  echo "#################################################\n";
  echo "# Update Mediboard SVN and user-oriented logger #\n";
  echo "#################################################\n\n";
  
  echo "Action to perform:\n\n";
  echo "[1] Show the update log\n";
  echo "[2] Perform the actual update\n";
  echo "--------------------------------\n";
  echo "[0] Return to main menu\n";
  echo "\nSelected action: ";
  $action = trim(fgets(STDIN));
  
  switch ($action) {
  
    case "1":
      $action = "info";
      break;
      
    case "2":
      $action = "real";
      break;
      
    case "0":
      echo exec("clear")."\n";
      menu();
      break;
      
    default:
      echo exec("clear")."\n";
      echo "Incorrect input\n";
      task1();
  }
  
  echo "\nRevision number [default HEAD]: ";
  $revision = trim(fgets(STDIN));
  switch ($revision) {
  
    case "":
    
      echo shell_exec("sh ".$GLOBALS['currentDir']."/update.sh ".$action)."\n\n";
      menu();
      break;
      
    default:
    
      echo shell_exec("sh ".$GLOBALS['currentDir']."/update.sh ".$action." -r ".$revision)."\n\n";
      menu();
  }
}

function task2() {

  echo "#######################################################\n";
  echo "# Configure groups and mods for Mediboard directories #\n";
  echo "#######################################################\n\n";
  
  echo "[0] Return to main menu\n\n";
  
  echo "Sub-directory: ";
  $subDir = trim(fgets(STDIN));
  
  if ($subDir == "0") {
  
    echo exec("clear")."\n";
    menu();
  }
  
  echo "\nApache user's group [optional]: ";
  $apacheGrp = trim(fgets(STDIN));
  
  switch ($apacheGrp) {
  
    case "":
    
      echo shell_exec("sh ".$GLOBALS['currentDir']."/setup.sh ".$subDir)."\n\n";
      menu();
      break;
      
    default:
    
      echo shell_exec("sh ".$GLOBALS['currentDir']."/setup.sh ".$subDir." -g ".$apacheGrp)."\n\n";
      menu();
  }
}

function task3() {

  echo "##################################\n";
  echo "# Update and rsync Mediboard SVN #\n";
  echo "##################################\n\n";
  
  echo "Action to perform:\n\n";
  echo "[1] Show the update log\n";
  echo "[2] Perform the actual update\n";
  echo "[3] No update, only rsync\n";
  echo "--------------------------------\n";
  echo "[0] Return to main menu\n";
  echo "\nSelected action: ";
  $action = trim(fgets(STDIN));
  
  switch ($action) {
  
    case "1":
      $action = "info";
      break;
      
    case "2":
      $action = "real";
      break;
      
    case "3":
      $action = "noup";
      break;
      
    case "0":
      echo exec("clear")."\n";
      menu();
      break;
      
    default:
      echo exec("clear")."\n";
      echo "Incorrect input\n";
      task3();
  }
  
  echo "\nRevision number [default HEAD]: ";
  $revision = trim(fgets(STDIN));
  switch ($revision) {
  
    case "":
    
      echo shell_exec("sh ".$GLOBALS['currentDir']."/rsyncupdate.sh ".$action)."\n\n";
      menu();
      break;
      
    default:
    
      echo shell_exec("sh ".$GLOBALS['currentDir']."/rsyncupdate.sh ".$action." -r ".$revision)."\n\n";
      menu();
  }
}

function task4() {

  echo "############################\n";
  echo "# Launch Mediboard request #\n";
  echo "############################\n\n";
  
  echo "[0] Return to main menu\n\n";
  
  echo "Root URL (ie https://localhost/mediboard): ";
  $rootURL = trim(fgets(STDIN));
  
  if ($rootURL == "0") {
  
    echo exec("clear")."\n";
    menu();
  }
  
  echo "Username (ie cron): ";
  $username = trim(fgets(STDIN));
  
  $password = prompt_silent();
  
  echo "Params (ie m=dPpatients&tab=vw_medecins): ";
  $params = trim(fgets(STDIN));
  
  echo "Times (number of repetitions) [default 1]: ";
  $times = trim(fgets(STDIN));
  if ($times == "") {
  
    $times = 1;
  }
  
  echo "Delay (time between each repetition) [default 1]: ";
  $delay = trim(fgets(STDIN));
  if ($delay == "") {
  
    $delay = 1;
  }
  
  echo "File (file for the output, ie log.txt) [default no file]: ";
  $file = trim(fgets(STDIN));
  switch ($file) {
  
    case "":
    
      echo shell_exec("sh ".$GLOBALS['currentDir']."/request.sh ".$rootURL." ".$username." ".$password." \"".$params."\" -t ".$times." -d ".$delay)."\n\n";
      menu();
      break;
      
    default:
    
      echo shell_exec("sh ".$GLOBALS['currentDir']."/request.sh ".$rootURL." ".$username." ".$password." \"".$params."\" -t ".$times." -d ".$delay." -f ".$file)."\n\n";
      menu();
  }
}

function task5() {

  echo "##############################\n";
  echo "# Replace Mediboard database #\n";
  echo "##############################\n\n";
  
  echo "[0] Return to main menu\n\n";
  
  echo "Source location (ie user@host, if localhost 'symlink' instead of 'scp'): ";
  $srcLocation = trim(fgets(STDIN));
  
  if ($srcLocation == "0") {
  
    echo exec("clear")."\n";
    menu();
  }
  
  echo "Source directory (ie /var/backup): ";
  $srcDir = trim(fgets(STDIN));
  
  echo "Source database (ie mediboard): ";
  $srcDB = trim(fgets(STDIN));
  
  echo "Target directory (ie /tmp) [default /tmp]: ";
  $tgtDir = trim(fgets(STDIN));
  
  if ($tgtDir == "") {
  
    $tgtDir = "/tmp";
  }
  
  echo "Target database (ie target_mediboard): ";
  $tgtDB = trim(fgets(STDIN));
  
  echo "Restart MySQL Server (Warning) (ie for InnoDB) [y or n, default n]? ";
  $restart = trim(fgets(STDIN));
  
  echo "Make a safe copy of existing target database first [y or n, default n]? ";
  $safeCopy = trim(fgets(STDIN));
  
  echo "MySQL directory where databases are stored (ie /var/lib/mysql) [default /var/lib/mysql]: ";
  $mySQLDir = trim(fgets(STDIN));
  
  echo "SSH port [default 22]: ";
  $port = trim(fgets(STDIN));
  
  echo "Make a local copy (scp) [y or n, default y]? ";
  $localCopy = trim(fgets(STDIN));
  
  $commandLine = "sh ".$GLOBALS['currentDir']."/replaceBase.sh ".$srcLocation." ".$srcDir." ".$srcDB." ".$tgtDir." ".$tgtDB;
  
  if ($restart == "y") {
  
    $commandLine .= " -r";
  }
  
  if ($safeCopy == "y") {
  
    $commandLine .= " -s";
  }
  
  if ($mySQLDir != "") {
  
    $commandLine .= " -m ".$mySQLDir;
  } else {
  
    $commandLine .= " -m /var/lib/mysql";
  }
  
  if ($port != "") {
  
    $commandLine .= " -p ".$port;
  } else {
  
    $commandLine .= " -p 22";
  }
  
  if ($localCopy != "n") {
  
    $commandLine .= " -l";
  }
  
  echo shell_exec($commandLine)."\n\n";
  menu();
}

function task6() {

  echo "####################################\n";
  echo "# Backup database on a daily basis #\n";
  echo "####################################\n\n";
  
  echo "Method:\n\n";
  echo "[1] Hotcopy\n";
  echo "[2] Dump\n";
  echo "--------------------\n";
  echo "[0] Return to main menu\n";
  echo "\nSelected method: ";
  $method = trim(fgets(STDIN));
  
  switch ($method) {
  
    case "1":
      $method = "hotcopy";
      break;
      
    case "2":
      $method = "dump";
      break;
      
    case "0":
      echo exec("clear")."\n";
      menu();
      break;
      
    default:
      echo exec("clear")."\n";
      echo "Incorrect input\n";
      task6();
  }
  
  echo "Username (to access database): ";
  $username = trim(fgets(STDIN));
  
  $password = prompt_silent();
  
  echo "Database to backup (ie mediboard): ";
  $DBBackup = trim(fgets(STDIN));
  
  echo "Backup path (ie /var/backup): ";
  $backupPath = trim(fgets(STDIN));
  
  echo "Time (in days before removal of files) [default 7]: ";
  $time = trim(fgets(STDIN));
  
  echo "Create a binary log index [y or n, default n]? ";
  $binLog = trim(fgets(STDIN));
  
  echo "Send a mail when diskfull is detected [y or n, default n]? ";
  $mail = trim(fgets(STDIN));
  if ($mail == "y") {
  
    echo "Username (to send a mail): ";
    $usernameMail = trim(fgets(STDIN));
    
    $passwordMail = prompt_silent();
  }
  
  $commandLine = "sh ".$GLOBALS['currentDir']."/baseBackup.sh ".$method." ".$username." ".$password." ".$DBBackup." ".$backupPath;
  
  if ($time == "") {
  
    $commandLine .= " -t 7";
  } else {
  
    $commandLine .= " -t ".$time;
  }
  
  if ($binLog == "y") {
  
    $commandLine .= " -b";
  }
  
  if ($mail == "y") {
  
    $commandLine .= " -l ".$usernameMail.":".$passwordMail;
  }
  
  echo shell_exec($commandLine)."\n\n";
  menu();
}

function task7() {

  echo "######################\n";
  echo "# Send a file by FTP #\n";
  echo "######################\n\n";
  
  echo "[0] Return to main menu\n\n";
  
  echo "Hostname: ";
  $hostname = trim(fgets(STDIN));
  
  if ($hostname == "0") {
  
    echo exec("clear")."\n";
    menu();
  }
  
  echo "Username: ";
  $username = trim(fgets(STDIN));
  
  $password = prompt_silent();
  
  echo "File: ";
  $file = trim(fgets(STDIN));
  
  echo "Port [default 21]: ";
  $port = trim(fgets(STDIN));
  
  echo "Switch to passive mode [y or n, default n]? ";
  $passiveMode = trim(fgets(STDIN));
  
  echo "Switch to ASCII mode [y or n, default n]? ";
  $ASCIIMode = trim(fgets(STDIN));
  
  $commandLine = "php ".$GLOBALS['currentDir']."/sendFileFTP.php ".$hostname." ".$username." ".$password." ".$file;
  
  if ($port != "") {
  
    $commandLine .= " -p ".$port;
  }
  
  if ($passiveMode == "y") {
  
    $commandLine .= " -m";
  }
  
  if ($ASCIIMode == "y") {
  
    $commandLine .= " -t";
  }
  
  echo shell_exec($commandLine)."\n\n";
  menu();
}

function task8() {

  echo "#####################################\n";
  echo "# Log Ping for server load analysis #\n";
  echo "#####################################\n\n";
  
  echo "[0] Return to main menu\n\n";
  
  echo "Hostname: ";
  $hostname = trim(fgets(STDIN));
  
  if ($hostname == "0") {
  
    echo exec("clear")."\n";
    menu();
  }
  
  echo shell_exec("sh ".$GLOBALS['currentDir']."/logPing.sh ".$hostname)."\n";
  menu();
}

function task9() {

  echo "#######################################\n";
  echo "# Log Uptime for server load analysis #\n";
  echo "#######################################\n\n";
  
  echo "[0] Return to main menu\n\n";
  
  echo "File (target for log, ie /var/log/uptime.log) [default /var/log/uptime.log]: ";
  $file = trim(fgets(STDIN));
  
  switch ($file) {
  
    case "0":
      echo exec("clear")."\n";
      menu();
      break;
      
    case "":
      $file = "/var/log/uptime.log";
      break;
  }
  
  echo shell_exec("sh ".$GLOBALS['currentDir']."/logUptime.sh ".$file)."\n";
  menu();
}

function task10() {

  echo "##############################################\n";
  echo "# Run MySQL performance tuning primer script #\n";
  echo "##############################################\n\n";
  
  echo "Select a mode:\n\n";
  echo "[1] All (perform all checks [default]\n";
  echo "[2] Prompt (prompt for login credintials and socket and execution mode)\n";
  echo "[3] Memory (run checks for tunable options which effect memory usage)\n";
  echo "[4] Disk, file (run checks for options which effect i/o performance or file handle limits)\n";
  echo "[5] InnoDB (run InnoDB checks)\n";
  echo "[6] Misc (run checks for that don't categorise well Slow Queries, Binary logs, Used Connections and Worker Threads)\n";
  echo "[7] Banner (show banner info)\n";
  echo "-------------------------------------------------------------------------------\n";
  echo "[0] Return to main menu\n";
  echo "\nSelected mode: ";
  $mode = trim(fgets(STDIN));
  
  switch ($mode) {
  
    case "1":
      $mode = "all";
      break;
      
    case "2":
      $mode = "prompt";
      break;
      
    case "3":
      $mode = "memory";
      break;
      
    case "4":
      $mode = "file";
      break;
      
    case "5":
      $mode = "innodb";
      break;
      
    case "6":
      $mode = "misc";
      break;
      
    case "7":
      $mode = "banner";
      break;
      
    case "":
      $mode = "all";
      break;
      
    case "0":
      echo exec("clear")."\n";
      menu();
      break;
      
    default:
      echo exec("clear")."\n";
      echo "Incorrect input\n";
      task10();
  }
  
  echo shell_exec("sh ".$GLOBALS['currentDir']."/tuning-primer.sh ".$mode)."\n";
  menu();
}

function task11() {
  echo "##################\n";
  echo "# Rotate binlogs #\n";
  echo "##################\n\n";
  
  echo "[0] Return to main menu\n\n";
  
  echo "MySQL username: ";
  $userAdminDB = trim(fgets(STDIN));
  
  if ($userAdminDB === "0") {
    echo exec("clear")."\n";
    menu();
  }
  
  $passAdminDB = prompt_silent("MySQL user password: ");
  
  echo "BinLogs directory [default /var/log/mysql]: ";
  $binLogsDir = trim(fgets(STDIN));
  
  if ($binLogsDir === "") {
    $binLogsDir = "/var/log/mysql";
  }
  
  echo "BinLog index filename [default log-bin.index]: ";
  $binLogIndexFilename = trim(fgets(STDIN));
  
  if ($binLogIndexFilename === "") {
    $binLogIndexFilename = "log-bin.index";
  }
  
  echo "Backup directory [default /mbbackup/binlogs]: ";
  $backupDir = trim(fgets(STDIN));
  
  if ($backupDir === "") {
    $backupDir = "/mbbackup/binlogs";
  }
  
  echo "\n";
  echo shell_exec("sh ".$GLOBALS['currentDir']."/rotateBinlogs.sh ".$userAdminDB." ".$passAdminDB." ".$binLogsDir." ".$binLogIndexFilename." ".$backupDir)."\n";
  menu();
}

// In order to have a password prompt that works on many OS (works on Unix, Windows XP and Windows 2003 Server)
// Source : http://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
function prompt_silent($prompt = "Enter Password:") {
  if (preg_match('/^win/i', PHP_OS)) {
    $vbscript = sys_get_temp_dir().'prompt_password.vbs';
    file_put_contents($vbscript, 'wscript.echo(InputBox("'.addslashes($prompt).'", "", "password here"))');
    $command = "cscript //nologo ".escapeshellarg($vbscript);
    $password = rtrim(shell_exec($command));
    unlink($vbscript);
    return $password;
  } else {
    $command = "/usr/bin/env bash -c 'echo OK'";
    if (rtrim(shell_exec($command)) !== 'OK') {
      trigger_error("Can't invoke bash");
      return;
    }
    $command = "/usr/bin/env bash -c 'read -s -p \"".addslashes($prompt)."\" mypassword && echo \$mypassword'";
    $password = rtrim(shell_exec($command));
    echo "\n";
    return $password;
  }
}