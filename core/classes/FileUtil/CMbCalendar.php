<?php
/**
 * @package Mediboard\Core\FileUtil
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\FileUtil;

use Ox\Core\Calendar\ICalcreator\vcalendar;
use Ox\Core\CAppUI;

class CMbCalendar extends vcalendar {
  function __construct($name, $description = ""){
    parent::__construct();
    
    //Ajout de quelques proporiétés
    $this->setProperty("method", "PUBLISH");
    $this->setProperty("x-wr-calname", $name);
    
    if ($description) {
      $this->setProperty("X-WR-CALDESC", $description);
    }
    
    $this->setProperty("X-WR-TIMEZONE", CAppUI::conf("timezone"));
  }
  
  //fonction permettant de créer un evènement de calendrier de façon simplifiée
  function addEvent($location, $summary, $description, $comment, $guid, $start, $end, $cancelled = false) {
    $date_re = "/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/";
    
    preg_match($date_re, $start, $matches_start);
    $start = array(
      "year"  => $matches_start[1], 
      "month" => $matches_start[2], 
      "day"   => $matches_start[3], 
      "hour"  => $matches_start[4], 
      "min"   => $matches_start[5], 
      "sec"   => 0
    );
    
    preg_match($date_re, $end, $matches_end);
    $end = array(
      "year"  => $matches_end[1], 
      "month" => $matches_end[2], 
      "day"   => $matches_end[3], 
      "hour"  => $matches_end[4], 
      "min"   => $matches_end[5], 
      "sec"   => 0
    );
    
    $vevent = $this->newComponent("vevent");
    
    $vevent->setProperty("dtstart", $start);
    $vevent->setProperty("dtend", $end);
    $vevent->setProperty("LOCATION", $location);
    $vevent->setProperty("UID", $guid);
    $vevent->setProperty("summary", $summary);
    
    if ($description) {
      $vevent->setProperty("description", $description);
    }
    
    if ($comment) {
      $vevent->setProperty("comment", $comment);
    }

    if ($cancelled) {
      $vevent->setProperty('METHOD', 'CANCEL');
      $vevent->setProperty('STATUS', 'CANCELLED');
    }
    
    $this->setComponent($vevent, $guid);
  }
}
